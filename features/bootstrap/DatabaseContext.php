<?php declare(strict_types=1);

namespace Tests;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Loader\NativeLoader;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tests\Helper\PropertyTypeResolver;
use Tests\Helper\RelationResolver;

/**
 * Defines application features from the specific context.
 */
class DatabaseContext implements Context, KernelAwareContext
{
    use KernelDictionary;

    /**
     * @var array|null
     */
    private static $resetDatabaseSql;

    private $manager;

    private $schemaTool;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->manager = $doctrine->getManager();
        $this->schemaTool = new SchemaTool($this->manager);
    }

    /**
     * @BeforeScenario @resetSchema
     */
    public function resetSchema()
    {
        if (self::$resetDatabaseSql == null) {
            $classes = $this->manager->getMetadataFactory()->getAllMetadata();
            $sql = $this->schemaTool->getDropSchemaSQL($classes);
            $this->executeQueries($sql);

            $sqlCreate = $this->schemaTool->getCreateSchemaSql($classes);
            $this->executeQueries($sqlCreate);

            self::$resetDatabaseSql = array_merge($this->schemaTool->getDropSchemaSQL($classes), $sqlCreate);
        } else {
            $this->executeQueries(self::$resetDatabaseSql);
        }
    }

    /**
     * @BeforeScenario @loadFixtures
     */
    public function loadFixtures()
    {

        $loader = new NativeLoader();
        $fileinfos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/fixtures/orm/')
        );
        $this->persistObjects($fileinfos, $loader);

        $this->manager->flush();
    }

    private function persistObjects(\Traversable $fileinfos, NativeLoader $loader)
    {

        foreach ($fileinfos as $pathname => $fileinfo) {
            /* @var \SplFileInfo $fileinfo */
            if ( ! $fileinfo->isFile()) continue;
            if ($fileinfo->getExtension() != 'yml') continue;

            $objectSet = $loader->loadFile($fileinfo->getRealPath());
            foreach ($objectSet->getObjects() as $object) {
                $this->manager->persist($object);
            }
        }
    }

    /**
     * @When clear object manager
     */
    public function clearObjectManager()
    {
        $this->manager->clear();
    }

    /**
     * @Given there are following :entityName entities in the database:
     */
    public function thereAreFollowingEntitiesInTheDatabase(string $entityName, TableNode $table)
    {
        $entityClassName = $this->manager->getRepository($entityName)->getClassName();
        $metadata = $this->manager->getClassMetadata($entityClassName);
        $accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();
        $rows = $table->getRows();
        $columns = array_shift($rows);

        $stringToAssociation = function (string $field, string $value) use ($metadata) {
            $targetEntity = $metadata->getAssociationMapping($field)['targetEntity'];
            if ($metadata->isCollectionValuedAssociation($field)) {
                $value = self::stringToArray($value);

                foreach ($value as &$v) {
                    $v = RelationResolver::search($this->manager, $targetEntity, $v);
                }
                unset($v);
            } elseif (null != $value) {
                $value = RelationResolver::search($this->manager, $targetEntity, $value);
            }

            return $value;
        };

        foreach ($rows as $entityData) {
            $entity = new $entityClassName();
            foreach ($entityData as $fieldIndex => $value) {
                $field = $columns[$fieldIndex];
                if (in_array($field, $metadata->getAssociationNames())) {
                    $value = $stringToAssociation($field, $value);
                } else {
                    if ( ! isset($metadata->fieldMappings[$field])) {
                        throw new \ReflectionException('Field ' . $field . ' does not exist in entity ' . $entityName);
                    }
                    $value = PropertyTypeResolver::formatToType($metadata->fieldMappings[$field]['type'], $value);
                }

                $reflection = new \ReflectionProperty($entityClassName, $field);
                $reflection->setAccessible(true);

                $accessor->setValue($entity, $field, $value);
            }
            $this->manager->persist($entity);
        }
        $this->manager->flush();
    }

    private static function stringToArray(string $value = null): array
    {
        if (null == $value) {
            return [];
        } elseif (strpos($value, ',') !== false) {
            return explode(',', $value);
        } else {
            return [$value];
        }
    }

    private function executeQueries(array $sql)
    {
        $conn = $this->manager->getConnection();
        foreach ($sql as $query) {
            try {
                $conn->executeQuery($query);
            } catch (\Exception $e) {
            }
        }
    }
}


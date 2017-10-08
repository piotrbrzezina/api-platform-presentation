<?php declare(strict_types=1);

namespace Tests\Helper;

use Doctrine\Common\Persistence\ObjectManager;

class RelationResolver
{
    /**
     * @return null|object
     */
    public static function search(ObjectManager $manager, $targetEntity, $value)
    {
        $targetRepository = $manager->getRepository($targetEntity);
        try {
            if ($result = $targetRepository->find($value)) {
                return $result;
            }
        } catch (\Exception $e) {
        }
        $targetMetadata = $manager->getClassMetadata($targetEntity);
        foreach ($targetMetadata->reflFields as $reflFields) {
            try {
                if ($targetMetadata->fieldMappings[$reflFields->name] && self::typeChecker($targetMetadata->fieldMappings[$reflFields->name]['type'], $value)) {
                    if ($result = $targetRepository->findOneBy([$reflFields->name => $value])) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    public static function typeChecker(string $type, $value): bool
    {
        switch ($type) {
            case 'bigint':
            case 'integer':
            case 'smallint':
                return is_int($value);
                break;
            case 'boolean':
                if (false !== in_array(strtolower($value), ['true', '1', 'false', '0'])) {
                    return true;
                }

                return false;
                break;
            case 'datetime':
            case 'date':
            case 'time':
                try {
                    new \DateTime($value);
                } catch (\Exception $e) {
                    return false;
                }
                return true;
                break;
            case 'decimal':
            case 'float':
                return is_numeric($value);
                break;
            default:
                return true;
        }
    }
}

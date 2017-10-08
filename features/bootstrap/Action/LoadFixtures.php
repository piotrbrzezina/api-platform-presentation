<?php declare(strict_types=1);

namespace Tests\Action;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class LoadFixtures
{

    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @Route( name="e2e_load_fixtures", path="/_e2e/load_fixtures")
     * @Method("GET")
     */
    public function __invoke()
    {

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'h:f:l',
            '--env' => 'e2e',
            '--bundle' => ['AppBundle'],
            '--no-interaction' => null,
            '-vv' => null,
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        return new Response($content, strpos($content, 'fixtures loaded') ? 200 : 400);
    }
}

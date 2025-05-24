<?php

namespace Khalil1608\LibBundle\Deploy;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractPostDeploy implements PostDeployInterface
{
    public function __construct(
        protected readonly KernelInterface $kernel,
    )
    {
    }

    protected function executeCommand(array $command): string
    {
        $env = $this->kernel->getEnvironment();
        $mergedCommand = array_merge($command, ['--env' => $env]);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($mergedCommand);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        return $output->fetch();
    }
}
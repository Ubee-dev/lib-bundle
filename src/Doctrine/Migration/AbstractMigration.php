<?php

namespace UbeeDev\LibBundle\Doctrine\Migration;

use UbeeDev\LibBundle\Migrations\Factory\MigrationInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractMigration extends \Doctrine\Migrations\AbstractMigration implements MigrationInterface
{
    protected string $accountDatabase;
    protected string $currentEnv;
    protected EntityManagerInterface $entityManager;
    protected KernelInterface $kernel;

    public function setAccountDatabase(string $accountDatabase): void
    {
        $this->accountDatabase = $accountDatabase;
    }

    public function setCurrentEnv(string $currentEnv): void
    {
        $this->currentEnv = $currentEnv;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setKernel(KernelInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    protected function executePostMigrationCommand(array $command): string
    {
        if ($this->currentEnv === 'test') {
            return '';
        }
        $mergedCommand = array_merge($command, ['--env' => $this->currentEnv]);
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
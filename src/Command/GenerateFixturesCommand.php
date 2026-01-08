<?php

namespace Khalil1608\LibBundle\Command;

use Khalil1608\LibBundle\Tests\Helper\CleanerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpKernel\KernelInterface;

#[When(env: 'test')]
#[When(env: 'dev')]
#[AsCommand(
    name: 'Khalil1608:fixtures:load',
)]
class GenerateFixturesCommand extends Command
{
    public function __construct(
        protected readonly CleanerInterface $cleaner,
        protected readonly KernelInterface  $kernel,
        ?string                             $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recreateDatabase($output);
        $this->restoreData($output);
        $this->executeMigrations($output);

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function executeCommand($command): string
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($command);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        return $output->fetch();
    }

    /**
     * @throws Exception
     */
    private function recreateDatabase(OutputInterface $output): void
    {
        $env = $this->kernel->getEnvironment();
        $content = $this->executeCommand([
            'command' => 'doctrine:database:drop',
            '--env' => $env,
            '--force' => true
        ]);
        $output->writeln("<info>".$content."</info>");

        $content = $this->executeCommand([
            'command' => 'doctrine:database:create',
            '--env' => $env
        ]);
        $output->writeln("<info>".$content."</info>");
    }

    /**
     * @throws Exception
     */
    private function restoreData(OutputInterface $output): void
    {
        $content = $this->executeCommand([
            'command' => 'backupdb:download:restore',
            '--env' => $this->kernel->getEnvironment()
        ]);
        $output->writeln("<info>".$content."</info>");
    }

    /**
     * @throws Exception
     */
    private function executeMigrations(OutputInterface $output): void
    {
        $content = $this->executeCommand([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true
        ]);
        $output->writeln("<info>".$content."</info>");
    }
}

<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Tests\Helper\CleanerInterface;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
#[AsCommand(
    name: 'UbeeDev:purge:tables'
)]
class PurgeTablesCommand extends Command
{
    public function __construct(
        private readonly CleanerInterface $cleaner,
        ?string                           $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cleaner->purgeAllTables();
        return Command::SUCCESS;
    }
}

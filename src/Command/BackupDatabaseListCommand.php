<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\ObjectStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'backupdb:list',
    description: 'List database backups in object storage'
)]
class BackupDatabaseListCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly ObjectStorageInterface $objectStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly string                 $backupBucket,
        ?string                                 $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'Database you want to list in object storage');
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $bucket = $this->backupBucket;
        $databaseName = $input->getOption('database') ?? $this->entityManager->getConnection()->getDatabase();

        $output->writeln("<info>Listing bucket <fg=yellow;>$bucket</> for database <fg=yellow;>$databaseName</>...</info>");

        $list = $this->objectStorage->list($bucket, $databaseName);

        if ($list) {
            $output->writeln("<fg=green;>Found " . count($list) . " backup(s):</>");
            foreach ($list as $dump) {
                $output->writeln("  - $dump");
            }
        } else {
            $output->writeln("<comment>No backups found in bucket $bucket for database $databaseName</comment>");
        }
    }
}

<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'backupdb:save',
    description: 'Backup database to object storage'
)]
class BackupDatabaseSaveCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly ObjectStorageInterface $objectStorage,
        private readonly BackupDatabase         $backupDatabase,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly string                 $backupBucket,
        ?string                                 $name = null
    ) {
        parent::__construct($name);
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $connection = $this->entityManager->getConnection();
        $databaseName = $connection->getDatabase();
        $tmpBackupFolder = $this->parameterBag->get('tmp_backup_folder');

        $output->writeln("<info>Dumping database <fg=yellow;>$databaseName</>...</info>");

        $tmpDatabaseFileName = $this->backupDatabase->dump($connection, $tmpBackupFolder);

        $output->writeln("<fg=green;>Dump created:</> $tmpDatabaseFileName");

        $remotePath = $databaseName . '/Dump_' . $databaseName . '_du_' . (new \DateTime())->format('Y-m-d H:i:s') . '.sql';

        $output->writeln("<info>Uploading to bucket <fg=yellow;>{$this->backupBucket}</>...</info>");
        $output->writeln("  Remote path: $remotePath");

        $url = $this->objectStorage->upload($tmpDatabaseFileName, $this->backupBucket, $remotePath, private: true);

        $output->writeln("<fg=green;>Upload complete:</> $url");
    }
}

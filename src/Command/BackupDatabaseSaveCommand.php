<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'backupdb:save',
    description: 'Backup database to S3'
)]
class BackupDatabaseSaveCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly S3Client               $s3Client,
        private readonly BackupDatabase         $backupDatabase,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly string                 $s3BackupBucket,
        ?string                                 $name = null
    ) {
        parent::__construct($name);
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $connection = $this->entityManager->getConnection();
        $databaseName = $connection->getDatabase();
        $tmpBackupFolder = $this->parameterBag->get('tmp_backup_folder');

        $output->writeln("<info>Start dumping $databaseName...</info>");

        $tmpDatabaseFileName = $this->backupDatabase->dump($connection, $tmpBackupFolder);

        $output->writeln("<fg=green;>$databaseName dumped</>");

        $output->writeln("<info>Start sending $databaseName to {$this->s3BackupBucket} bucket...</info>");

        $this->s3Client->upload(
            $tmpDatabaseFileName,
            $this->s3BackupBucket,
            $databaseName . '/Dump_' . $databaseName . '_du_' . (new \DateTime())->format('Y-m-d H:i:s') . '.sql'
        );

        $output->writeln("<fg=green;>$databaseName uploaded</>");
    }
}

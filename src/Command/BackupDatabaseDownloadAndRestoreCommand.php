<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\BackupDatabase;
use UbeeDev\LibBundle\Service\ObjectStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

#[AsCommand(
    name: 'backupdb:download:restore',
    description: 'Get last database backup from object storage and restore database'
)]
class BackupDatabaseDownloadAndRestoreCommand extends AbstractMonitoredCommand
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

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('key', null, InputOption::VALUE_OPTIONAL, 'File you want to download from object storage', null);
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $connection = $this->entityManager->getConnection();
        $databaseName = $connection->getDatabase();
        $tmpBackupFolder = $this->parameterBag->get('tmp_backup_folder');
        $s3Key = $input->getOption('key');

        $backupFilePath = $this->downloadBackupFileIfNotExist($output, $databaseName, $tmpBackupFolder, $s3Key);

        $output->writeln("<info>Restoring database <fg=yellow;>$databaseName</> from $backupFilePath...</info>");

        $this->backupDatabase->restore($connection, $backupFilePath);

        $output->writeln("<fg=green;>Database $databaseName restored successfully</>");
    }

    private function getLastDump(string $databaseName, ?string $s3Key): string
    {
        if ($s3Key) {
            return $s3Key;
        }

        $dumpsFile = $this->objectStorage->list($this->backupBucket, $databaseName);

        return array_values(array_slice($dumpsFile, -1))[0];
    }

    private function downloadBackupFileIfNotExist(OutputInterface $output, string $databaseName, string $tmpBackupFolder, ?string $s3Key): string
    {
        $lastDump = $this->getLastDump($databaseName, $s3Key);
        $fileName = basename($lastDump);
        $backupFolder = $tmpBackupFolder . '/' . $databaseName;
        $backupFilePath = $backupFolder . '/' . $fileName;

        if (!file_exists($backupFilePath)) {
            $output->writeln("<info>Downloading <fg=yellow;>$lastDump</> from bucket <fg=yellow;>{$this->backupBucket}</>...</info>");

            $backupFilePath = $this->objectStorage->download($this->backupBucket, $lastDump, $backupFolder, $fileName);

            if (!$backupFilePath) {
                throw new FileNotFoundException('File ' . $lastDump . ' not found');
            }

            $output->writeln("<fg=green;>Downloaded to:</> $backupFilePath");
        } else {
            $output->writeln("<comment>File already exists locally:</comment> $backupFilePath");
        }

        return $backupFilePath;
    }
}

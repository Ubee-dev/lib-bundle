<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

#[AsCommand(
    name: 'backupdb:download:restore',
    description: 'Get last database backup from s3 and restore database'
)]
class BackupDatabaseDownloadAndRestoreCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly S3Client               $s3Client,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly string                 $s3BackupBucket,
        ?string                                 $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('key', null, InputOption::VALUE_OPTIONAL, 'File you want to download in S3', null);
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $databaseName = $this->entityManager->getConnection()->getDatabase();
        $tmpBackupFolder = $this->parameterBag->get('tmp_backup_folder');
        $s3Key = $input->getOption('key');

        $backupFilePath = $this->downloadBackupFileIfNotExist($output, $databaseName, $tmpBackupFolder, $s3Key);
        $this->restoreDatabase($backupFilePath, $databaseName);

        $output->writeln("<info>Database $databaseName restored...</info>");
    }

    private function getLastDump(string $databaseName, ?string $s3Key): string
    {
        if ($s3Key) {
            return $s3Key;
        }

        $dumpsFile = $this->s3Client->list([
            'Bucket' => $this->s3BackupBucket,
            'Prefix' => $databaseName,
        ]);

        return array_values(array_slice($dumpsFile, -1))[0];
    }

    private function downloadBackupFileIfNotExist(OutputInterface $output, string $databaseName, string $tmpBackupFolder, ?string $s3Key): string
    {
        $lastDump = $this->getLastDump($databaseName, $s3Key);
        $fileName = basename($lastDump);
        $backupFolder = $tmpBackupFolder . '/' . $databaseName;
        $backupFilePath = $backupFolder . '/' . $fileName;

        if (!file_exists($backupFilePath)) {
            $output->writeln("<info>Start downloading $lastDump...</info>");

            $backupFilePath = $this->s3Client->download($this->s3BackupBucket, $lastDump, $backupFolder, $fileName);

            if (!$backupFilePath) {
                throw new FileNotFoundException('File ' . $lastDump . ' not found');
            }

            $output->writeln("<info>Database $databaseName downloaded...</info>");
        }

        return $backupFilePath;
    }

    private function restoreDatabase(string $backupFilePath, string $databaseName): void
    {
        $connection = $this->entityManager->getConnection()->getParams();

        $fileContent = file($backupFilePath);
        $filteredContent = array_filter($fileContent, function ($line) {
            return strpos($line, 'enable the sandbox mode') === false;
        });

        $tempFilePath = sys_get_temp_dir() . '/filtered_backup.sql';
        file_put_contents($tempFilePath, implode("", $filteredContent));

        $command = "mysql --force -u" . escapeshellarg($connection['user'])
            . " --password=" . escapeshellarg($connection['password'])
            . " -h" . escapeshellarg($connection['host'])
            . " " . escapeshellarg($databaseName)
            . " < " . escapeshellarg($tempFilePath);

        exec($command);
    }
}

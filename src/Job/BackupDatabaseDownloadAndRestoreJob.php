<?php

namespace Khalil1608\LibBundle\Job;

use Khalil1608\LibBundle\Service\Mailer;
use Khalil1608\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BackupDatabaseDownloadAndRestoreJob extends AbstractJob
{
    public function __construct(
        array $options,
        OutputInterface $output,
        Mailer $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly S3Client $s3Client,
        private readonly string $s3BackupBucket,
        private readonly string $tmpBackupFolder,
        private readonly string $databaseName,
        private readonly ?string $s3Key = null
    )
    {
        parent::__construct($options, $output, $mailer);
    }

    public function run()
    {
        try {
            $backupFilePath = $this->downloadBackupFileIfNotExist();
            $this->restoreDatabase($backupFilePath);

            $this->output->writeln("<info>Database ".$this->databaseName." restored...</info>");

        } catch(FileNotFoundException $e) {
            $this->handleJobException('Failed to download dump.', $e, $this->output);
            throw new FileNotFoundException($e->getMessage());
        } catch (\Exception $e) {
            $this->handleJobException('Failed to restore database.', $e, $this->output);
        }
    }

    public function getProjectName(): string
    {
        return '';
    }

    private function getLastDump(): string
    {
        $lastDump = $this->s3Key;

        if(!$lastDump) {
            $prefix = $this->databaseName;
            // get all dumps and returns last one
            $dumpsFile = $this->s3Client->list(['Bucket' => $this->s3BackupBucket, 'Prefix' => $prefix]);
            $lastDump = array_values(array_slice($dumpsFile, -1))[0];
        }

        return $lastDump;
    }

    private function getBackupFolder(): string
    {
        return $this->tmpBackupFolder.'/'.$this->databaseName;
    }

    private function downloadBackupFileIfNotExist(): string
    {
        $lastDump = $this->getLastDump();
        $fileName = basename($lastDump);
        $backupFilePath = $this->getBackupFolder().'/'.$fileName;

        if(!file_exists($backupFilePath)) {
            $backupFilePath = $this->downloadLastDump();
        }

        return $backupFilePath;
    }

    private function restoreDatabase(string $backupFilePath): void
    {
        $connection = $this->entityManager->getConnection()->getParams();

        $fileContent = file($backupFilePath);

        $filteredContent = array_filter($fileContent, function ($line) {
            return strpos($line, 'enable the sandbox mode') === false;
        });

        $tempFilePath = sys_get_temp_dir() . '/filtered_backup.sql';
        file_put_contents($tempFilePath, implode("", $filteredContent));

        $command = "mysql --force -u".escapeshellarg($connection['user'])." --password=".escapeshellarg($connection['password'])." -h".escapeshellarg($connection['host'])." ".escapeshellarg($this->databaseName)." < ".escapeshellarg($tempFilePath);

        echo exec($command);
    }

    private function downloadLastDump(): string
    {
        $lastDump = $this->getLastDump();
        $fileName = basename($lastDump);
        $this->output->writeln("<info>Start downloading ".$lastDump."...</info>");

        $backupFilePath = $this->s3Client->download($this->s3BackupBucket, $lastDump,  $this->getBackupFolder(), $fileName);

        if(!$backupFilePath) {
            throw new FileNotFoundException('File '.$this->s3Key.' not found');
        }

        $this->output->writeln("<info>Database ".$this->databaseName." downloaded...</info>");

        return $backupFilePath;
    }
}

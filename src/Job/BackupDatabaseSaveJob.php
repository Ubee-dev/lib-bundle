<?php

namespace Khalil1608\LibBundle\Job;

use Khalil1608\LibBundle\Service\BackupDatabase;
use Khalil1608\LibBundle\Service\S3Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

class BackupDatabaseSaveJob extends AbstractJob
{

    /** @var BackupDatabase */
    private $backupDatabaseService;

    /** @var S3Client */
    private $s3Client;

    /** @var string */
    private $s3BackupBucket;

    /** @var string */
    private $tmpBackupFolder;

    /** @var string */
    private $databaseHost;

    /** @var string */
    private $databaseName;

    /** @var string */
    private $databaseUser;

    /** @var string */
    private $databasePassword;


    /**
     * BackupDatabaseSaveJob constructor.
     * @param array $options
     * @param OutputInterface $output
     * @param $mailer
     * @param $backupDatabaseService
     * @param $s3Client
     * @param $s3BackupBucket
     * @param $tmpBackupFolder
     * @param $databaseHost
     * @param $databaseName
     * @param $databaseUser
     * @param $databasePassword
     */
    public function __construct(array $options, OutputInterface $output, $mailer, $backupDatabaseService, $s3Client, $s3BackupBucket, $tmpBackupFolder, $databaseHost, $databaseName, $databaseUser, $databasePassword)
    {
        parent::__construct($options, $output, $mailer);
        $this->backupDatabaseService = $backupDatabaseService;
        $this->s3Client = $s3Client;
        $this->s3BackupBucket = $s3BackupBucket;
        $this->tmpBackupFolder = $tmpBackupFolder;
        $this->databaseHost = $databaseHost;
        $this->databaseName = $databaseName;
        $this->databaseUser = $databaseUser;
        $this->databasePassword = $databasePassword;
    }

    public function run()
    {
        $output = $this->getOutput();

        try {
            $output->writeln("<info>Start dumping ".$this->databaseName."...</info>");
            $tmpDatabaseFileName = $this->backupDatabaseService->dump($this->tmpBackupFolder, $this->databaseHost, $this->databaseName, $this->databaseUser, $this->databasePassword);
            $output->writeln("<fg=green;>".$this->databaseName." dumped</>");

            $output->writeln("<info>Start sending ".$this->databaseName." to ".$this->s3BackupBucket." bucket...</info>");
            $databaseUrl = $this->s3Client->upload($tmpDatabaseFileName, $this->s3BackupBucket, $this->databaseName."/Dump_".$this->databaseName."_du_". (new \DateTime())->format('Y-m-d H:i:s').'.sql');
            $output->writeln("<fg=green;>".$this->databaseName." uploaded</>");
        } catch (\Exception $e) {
            $this->handleJobException('Failed to dump database.', $e, $output);
        }

    }
    
    public function getProjectName()
    {
        return '';
    }
}

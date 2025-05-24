<?php

namespace Khalil1608\LibBundle\Job;

use Khalil1608\LibBundle\Service\S3Client;
use Symfony\Component\Console\Output\OutputInterface;

class BackupDatabaseListJob extends AbstractJob
{
    /** @var S3Client */
    private $s3Client;

    /** @var string */
    private $s3BackupBucket;

    /** @var string|null */
    private $databaseName;

    /**
     * BackupDatabaseSaveJob constructor.
     * @param array $options
     * @param OutputInterface $output
     * @param $mailer
     * @param $s3Client
     * @param $s3BackupBucket
     * @param $databaseName
     */
    public function __construct(array $options, OutputInterface $output, $mailer, $s3Client, $s3BackupBucket, $databaseName= null)
    {
        parent::__construct($options, $output, $mailer);
        $this->s3Client = $s3Client;
        $this->s3BackupBucket = $s3BackupBucket;
        $this->databaseName = $databaseName;
    }

    public function run()
    {
        $output = $this->getOutput();

        try {
            $output->writeln("<info>Start listing ".$this->s3BackupBucket."...</info>");
            $options = ['Bucket' => $this->s3BackupBucket];
            if($this->databaseName) {
                $options['Prefix'] = $this->databaseName;
            }
            $list = $this->s3Client->list($options);

            if($list){
                foreach ($list as $dump) {
                    $output->writeln("<info>".$dump."</info>");
                }
            } else {
                $message = "There is no dump in the bucket ".$this->s3BackupBucket;
                if($this->databaseName) {
                    $message .= ' for the database '.$this->databaseName;
                }
                $output->writeln("<info>$message</info>");
            }


        } catch (\Exception $e) {
            $this->handleJobException('Failed to dump database.', $e, $output);
        }

    }
    
    public function getProjectName()
    {
        return '';
    }
}
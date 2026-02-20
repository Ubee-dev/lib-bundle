<?php

namespace UbeeDev\LibBundle\Command;

use UbeeDev\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'backupdb:list',
    description: 'List Backup database in S3'
)]
class BackupDatabaseListCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly S3Client               $s3Client,
        private readonly EntityManagerInterface $entityManager,
        private readonly string                 $s3BackupBucket,
        ?string                                 $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'Database you want to list in S3');
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $bucket = $this->s3BackupBucket;
        $databaseName = $input->getOption('database') ?? $this->entityManager->getConnection()->getDatabase();

        $output->writeln("<info>Start listing $bucket...</info>");

        $options = ['Bucket' => $bucket];
        if ($databaseName) {
            $options['Prefix'] = $databaseName;
        }

        $list = $this->s3Client->list($options);

        if ($list) {
            foreach ($list as $dump) {
                $output->writeln("<info>$dump</info>");
            }
        } else {
            $message = "There is no dump in the bucket $bucket";
            if ($databaseName) {
                $message .= ' for the database ' . $databaseName;
            }
            $output->writeln("<info>$message</info>");
        }
    }
}

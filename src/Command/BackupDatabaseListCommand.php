<?php

namespace Khalil1608\LibBundle\Command;

use Khalil1608\LibBundle\Job\BackupDatabaseListJob;
use Khalil1608\LibBundle\Service\Mailer;
use Khalil1608\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        private readonly Mailer                 $mailer,
        private readonly EntityManagerInterface $entityManager,
        ?string                                 $name = null
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'Database you want to list in S3')
            ->addOption('fromEnv', null, InputOption::VALUE_OPTIONAL, 'Env in S3 you went to list', 'dev');
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $job = new BackupDatabaseListJob(
            [],
            $output,
            $this->mailer,
            $this->s3Client,
            'Khalil1608-backup-' . $input->getOption('fromEnv'),
            $input->getOption('database') ?? $this->entityManager->getConnection()->getDatabase()
        );
        $job->run();
    }
}

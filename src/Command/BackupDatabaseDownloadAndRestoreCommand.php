<?php

namespace Khalil1608\LibBundle\Command;

use Khalil1608\LibBundle\Job\BackupDatabaseDownloadAndRestoreJob;
use Khalil1608\LibBundle\Service\Mailer;
use Khalil1608\LibBundle\Service\S3Client;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'backupdb:download:restore',
    description: 'Get last database backup from s3 and restore database'
)]
class BackupDatabaseDownloadAndRestoreCommand extends AbstractMonitoredCommand
{
    public function __construct(
        private readonly S3Client               $s3Client,
        private readonly EntityManagerInterface $entityManager,
        private readonly Mailer                 $mailer,
        private readonly ParameterBagInterface  $parameterBag,
        ?string                                 $name = null
    )
    {
        parent::__construct($name);
    }


    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('key', null, InputOption::VALUE_OPTIONAL, 'File you want to download in S3', null)
            ->addOption('fromEnv', null, InputOption::VALUE_OPTIONAL, 'File you want to restore in S3', 'prod');
    }

    /**
     * @throws Exception
     */
    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $databaseName = $this->entityManager->getConnection()->getDatabase();

        $job = new BackupDatabaseDownloadAndRestoreJob(
            [],
            $output,
            $this->mailer,
            $this->entityManager,
            $this->s3Client,
            'Khalil1608-backup-' . $input->getOption('fromEnv'),
            $this->parameterBag->get('tmp_backup_folder'),
            $databaseName,
            $input->getOption('key')
        );
        $job->run();
    }
}

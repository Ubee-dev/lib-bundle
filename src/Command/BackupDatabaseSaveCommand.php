<?php

namespace Khalil1608\LibBundle\Command;

use Khalil1608\LibBundle\Job\BackupDatabaseSaveJob;
use Khalil1608\LibBundle\Service\BackupDatabase;
use Khalil1608\LibBundle\Service\Mailer;
use Khalil1608\LibBundle\Service\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        private readonly Mailer                 $mailer,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly string                 $s3BackupBucket,
        ?string                                 $name = null
    )
    {
        parent::__construct($name);
    }

    public function perform(InputInterface $input, OutputInterface $output): void
    {
        $connexion = $this->entityManager->getConnection();
        $params = $connexion->getParams();

        $job = new BackupDatabaseSaveJob(
            [],
            $output,
            $this->mailer,
            $this->backupDatabase,
            $this->s3Client,
            $this->s3BackupBucket,
            $this->parameterBag->get('tmp_backup_folder'),
            $params['host'],
            $connexion->getDatabase(),
            $params['user'],
            $params['password'],
        );
        $job->run();
    }
}

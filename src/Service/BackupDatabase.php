<?php

namespace Khalil1608\LibBundle\Service;


use Khalil1608\LibBundle\Traits\ProcessTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class BackupDatabase
{
    use ProcessTrait;

    /**
     * @throws \Exception
     */
    public function dump($backupFolder, $hostName, $databaseName, $databaseUser, $databasePassword): string
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($backupFolder.'/'.$databaseName);
        $tmpBackupFileName = $backupFolder.'/'.$databaseName.'/'.(new \DateTime())->format('Y-m-d H:i:s').'.sql';
        $this->executeCommand("mysqldump --user=".$databaseUser." --host=".$hostName." --password=".$databasePassword." --databases ".$databaseName." > '".$tmpBackupFileName."'");
        
        return $tmpBackupFileName;
    }

    public function restore($dumpFile)
    {
        $process = new Process(
            ['bin/console doctrine:database:import '.$dumpFile]
        );

        return $process->run();

    }
}
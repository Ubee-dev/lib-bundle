<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Cleaner implements CleanerInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected string $currentEnv
    )
    {
    }

    /**
     * @param $table
     * @throws Exception
     */
    public function purgeTable($table)
    {
        $this->em->getConnection()->prepare('DELETE FROM ' . $table)->executeStatement();
    }

    /**
     * @param $folderName
     */
    public function cleanFolder($folderName)
    {
        $fs = new Filesystem();
        $fs->remove($folderName);
        $fs->mkdir($folderName);
    }

    /**
     */
    public function purgeAllTables(): void
    {
        $purger = new ORMPurger($this->em, ['migration_versions']);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $purger->purge();
    }
}

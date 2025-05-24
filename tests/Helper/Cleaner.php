<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Logging\Middleware;
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
     * @throws Exception
     */
    public function purgeAllTables(): void
    {
        $configuration = $this->em->getConnection()->getConfiguration();
        $middlewares = array_filter($configuration->getMiddlewares(), static function ($middleware) {
            return !$middleware instanceof Middleware;
        });
        $configuration->setMiddlewares($middlewares);

        $this->em->getConnection()->prepare("SET FOREIGN_KEY_CHECKS = 0;")->executeStatement();

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        foreach ($schemaManager->listTableNames() as $tableNames) {
            if ($tableNames !== 'migration_versions') {
                $sql = 'DELETE FROM ' . '`'.$tableNames.'`';
                $this->em->getConnection()->prepare($sql)->executeStatement();
            }
        }

        $this->em->getConnection()->prepare("SET FOREIGN_KEY_CHECKS = 1;")->executeStatement();
    }
}

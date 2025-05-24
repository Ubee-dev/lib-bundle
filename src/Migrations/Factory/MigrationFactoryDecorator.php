<?php

declare(strict_types=1);

namespace Khalil1608\LibBundle\Migrations\Factory;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationFactoryDecorator implements MigrationFactory
{
    private MigrationFactory $migrationFactory;
    private ?string $currentEnv;
    private ?EntityManagerInterface $entityManager;
    private ?KernelInterface $kernel;

    public function __construct(
        MigrationFactory       $migrationFactory,
        string                 $currentEnv,
        EntityManagerInterface $entityManager,
        KernelInterface        $kernel,
    )
    {
        $this->migrationFactory = $migrationFactory;
        $this->currentEnv = $currentEnv;
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof MigrationInterface) {
            $instance->setEntityManager($this->entityManager);
            $instance->setCurrentEnv($this->currentEnv);
            $instance->setKernel($this->kernel);
        }

        return $instance;
    }
}
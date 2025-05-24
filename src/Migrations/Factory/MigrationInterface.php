<?php

namespace Khalil1608\LibBundle\Migrations\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

interface MigrationInterface
{
    public function setAccountDatabase(string $accountDatabase): void;
    public function setCurrentEnv(string $currentEnv): void;
    public function setEntityManager(EntityManagerInterface $entityManager): void;
    public function setKernel(KernelInterface $kernel): void;
}
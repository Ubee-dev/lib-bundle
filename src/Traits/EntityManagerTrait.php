<?php

namespace UbeeDev\LibBundle\Traits;

use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerTrait
{
    public function ensureDatabaseConnectedAndEmCacheIsCleared(EntityManagerInterface $entityManager): void
    {
        $entityManager->clear();
    }
}

<?php

namespace Khalil1608\LibBundle\Traits;

use Doctrine\ORM\EntityManagerInterface;

trait EntityManagerTrait
{
    public function ensureDatabaseConnectedAndEmCacheIsCleared(EntityManagerInterface $entityManager)
    {
        $entityManager->clear();
    }
}

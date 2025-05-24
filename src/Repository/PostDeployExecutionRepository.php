<?php

namespace Khalil1608\LibBundle\Repository;

use Khalil1608\LibBundle\Entity\PostDeployExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostDeployExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, PostDeployExecution::class);
    }
}
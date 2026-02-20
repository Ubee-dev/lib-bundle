<?php

namespace UbeeDev\LibBundle\Repository;

use UbeeDev\LibBundle\Entity\PostDeployExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostDeployExecutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, PostDeployExecution::class);
    }
}
<?php


namespace Khalil1608\LibBundle\Service;


use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorFactory
{
    public function initPaginator(QueryBuilder $queryBuilder)
    {
        return new Paginator($queryBuilder);
    }
}

<?php

namespace UbeeDev\LibBundle\Service;

use UbeeDev\LibBundle\Model\PaginatedResult;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class Paginator
{
    public function __construct(
        private readonly PaginatorFactory $paginatorFactory,
        private readonly int              $listPageSize)
    {
    }

    public function getPaginatedQueryResult(
        QueryBuilder $queryBuilder,
        Request $request,
        ?string $dtoClass = null,
        array $params = []
    ): PaginatedResult
    {
        $page = (int)($request->query->get('page', 1) ?? 1);
        $listPageSize = (int)($request->query->get('per', $this->listPageSize) ?? $this->listPageSize);
        $listPageSize = min($listPageSize, 50);
        $offset = $listPageSize * ($page - 1);

        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($listPageSize);

        $paginator = $this->paginatorFactory->initPaginator($queryBuilder);

        $results = [];
        foreach ($paginator->getIterator() as $iterator) {
            if (!$dtoClass) {
                $results[] = $iterator;
            } else {
                $results[] = new $dtoClass($iterator, ...$params);
            }
        }

        $nbItems = $paginator->count();
        $nbDisplayedResults = (($page - 1) * $listPageSize) + count($results);

        return new PaginatedResult($results, $nbItems, $nbDisplayedResults, $page, $listPageSize);
    }
}
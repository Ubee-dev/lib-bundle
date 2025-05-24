<?php

namespace Khalil1608\LibBundle\Model;

class PaginatedResult
{
    public function __construct(
        private readonly array $currentPageResults,
        private readonly int $nbTotalResults,
        private readonly int $nbCumulativeResults,
        private readonly int $pageNumber,
        private readonly int $pageSize
    )
    {
    }

    public function getCurrentPageResults(): array
    {
        return $this->currentPageResults;
    }

    public function getNbTotalResults(): int
    {
        return $this->nbTotalResults;
    }

    public function getNbCumulativeResults(): int
    {
        return $this->nbCumulativeResults;
    }


    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }


    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getCurrentPageSize(): int
    {
        return count($this->currentPageResults);
    }

    public function getLastPageNumber(): int
    {
        return ceil($this->nbTotalResults / $this->pageSize);
    }

    public function getPreviousPageNumber(): ?int
    {
        return $this->isFirstPage() ? null : $this->pageNumber - 1;
    }

    public function getNextPageNumber(): ?int
    {
        return ($this->isLastPage()) ? null : $this->pageNumber + 1;
    }

    public function isLastPage(): bool
    {
        return $this->pageNumber === $this->getLastPageNumber();
    }

    public function isFirstPage(): bool
    {
        return $this->pageNumber === 1;
    }

    public function hasPreviousPage(): bool
    {
        return !$this->isFirstPage();
    }

    public function hasNextPage(): bool
    {
        return !$this->isLastPage();
    }

}
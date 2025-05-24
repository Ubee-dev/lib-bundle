<?php

namespace Khalil1608\LibBundle\Tests\Model;

use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Khalil1608\LibBundle\Model\PaginatedResult;

class PaginatedResultTest extends AbstractWebTestCase
{
    public function testConstructorAndAccessors(): void
    {
        $resultSet = ['one'];
        $paginatedResult = new PaginatedResult(
            $resultSet,
            10,
            6,
            2,
            3
        );
        $this->assertEquals($resultSet, $paginatedResult->getCurrentPageResults());
        $this->assertEquals(10, $paginatedResult->getNbTotalResults());
        $this->assertEquals(6, $paginatedResult->getNbCumulativeResults());
        $this->assertEquals(2, $paginatedResult->getPageNumber());
        $this->assertEquals(3, $paginatedResult->getPageSize());
        $this->assertEquals(1, $paginatedResult->getCurrentPageSize());
        $this->assertEquals(4, $paginatedResult->getLastPageNumber());
        $this->assertEquals(1, $paginatedResult->getPreviousPageNumber());
        $this->assertEquals(3, $paginatedResult->getNextPageNumber());
        $this->assertFalse($paginatedResult->isFirstPage());
        $this->assertFalse($paginatedResult->isLastPage());
        $this->assertTrue($paginatedResult->hasNextPage());
        $this->assertTrue($paginatedResult->hasPreviousPage());
    }

    public function testPreviousPageNumberIsNullWhenOnFirstPage(): void
    {
        $resultSet = ['one'];
        $paginatedResult = new PaginatedResult(
            $resultSet,
            10,
            6,
            1,
            3
        );
        $this->assertEquals(null, $paginatedResult->getPreviousPageNumber());
        $this->assertEquals(2, $paginatedResult->getNextPageNumber());
        $this->assertTrue($paginatedResult->isFirstPage());
        $this->assertFalse($paginatedResult->isLastPage());
        $this->assertTrue($paginatedResult->hasNextPage());
        $this->assertFalse($paginatedResult->hasPreviousPage());
    }

    public function testNextPageNumberIsNullWhenOnLastPage(): void
    {
        $resultSet = ['one'];
        $paginatedResult = new PaginatedResult(
            $resultSet,
            10,
            6,
            4,
            3
        );
        $this->assertEquals(3, $paginatedResult->getPreviousPageNumber());
        $this->assertEquals(null, $paginatedResult->getNextPageNumber());
        $this->assertFalse($paginatedResult->isFirstPage());
        $this->assertTrue($paginatedResult->isLastPage());
        $this->assertFalse($paginatedResult->hasNextPage());
        $this->assertTrue($paginatedResult->hasPreviousPage());
    }
}
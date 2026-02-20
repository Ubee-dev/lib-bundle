<?php

namespace UbeeDev\LibBundle\Tests\Service;

use ArrayIterator;
use UbeeDev\LibBundle\Service\Paginator;
use UbeeDev\LibBundle\Service\PaginatorFactory;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use UbeeDev\LibBundle\Tests\Helper\DummyDTO;
use UbeeDev\LibBundle\Tests\Helper\DummyObject;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

class PaginatorTest extends AbstractWebTestCase
{
    /** @var Paginator */
    private Paginator $paginator;
    private int $defaultListPageSize = 2;
    private QueryBuilder|MockObject $queryBuilderMock;
    private PaginatorFactory|MockObject $paginatorFactoryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->paginatorFactoryMock = $this->getMockedClass(PaginatorFactory::class);
        $this->queryBuilderMock = $this->getMockedClass(QueryBuilder::class);
        $this->paginator = $this->initManager();
        $this->mockRequest();
    }

    public function testGetPaginatedQueryResultWithDefaultPerParameterSuccessfully(): void
    {

        $this->addQueryParamsToMockedRequest([
            'page' => $currentPage = 3,
        ]);

        $this->expectQueryBuilderShouldSetFirstResultAndMaxResultWith($currentPage, $this->defaultListPageSize);
        $paginatorMock = $this->mockPaginatorWithGivenData($data = ['some' => 'data1', 'some2' => 'data2']);
        $this->expectPaginatorShouldBeInitializedWithGivenPaginator($paginatorMock);
        $nbCumulativeResults = $this->getNbCumulativeResultsForCurrentPage($currentPage, $this->defaultListPageSize, $data);

        $paginatedResult = $this->paginator->getPaginatedQueryResult($this->queryBuilderMock, $this->requestMock);

        $this->assertEquals(['data1', 'data2'], $paginatedResult->getCurrentPageResults());
        $this->assertEquals(2, $paginatedResult->getNbTotalResults());
        $this->assertEquals($this->defaultListPageSize, $paginatedResult->getPageSize());
        $this->assertEquals(3, $paginatedResult->getPageNumber());
        $this->assertEquals(2, $paginatedResult->getCurrentPageSize());
        $this->assertEquals($nbCumulativeResults, $paginatedResult->getNbCumulativeResults());
    }

    /**
     * @throws Exception
     */
    public function testPaginatedApiResponseWithPerParameterOverTheLimitSuccessfully(): void
    {
        $this->addQueryParamsToMockedRequest([
            'page' => $currentPage = 3,
            'per' => 100,
        ]);

        $this->expectQueryBuilderShouldSetFirstResultAndMaxResultWith($currentPage, 50);
        $paginatorMock = $this->mockPaginatorWithGivenData($data = ['some' => 'data1', 'some2' => 'data2']);
        $this->expectPaginatorShouldBeInitializedWithGivenPaginator($paginatorMock);
        $nbCumulativeResults = $this->getNbCumulativeResultsForCurrentPage($currentPage, 50, $data);

        $paginatedResult = $this->paginator->getPaginatedQueryResult($this->queryBuilderMock, $this->requestMock);

        $this->assertEquals(['data1', 'data2'], $paginatedResult->getCurrentPageResults());
        $this->assertEquals(2, $paginatedResult->getNbTotalResults());
        $this->assertEquals(50, $paginatedResult->getPageSize());
        $this->assertEquals(3, $paginatedResult->getPageNumber());
        $this->assertEquals(2, $paginatedResult->getCurrentPageSize());
        $this->assertEquals($nbCumulativeResults, $paginatedResult->getNbCumulativeResults());

    }

    /**
     * @throws Exception
     */
    public function testPaginatedApiResponseWithGivenPerParameterSuccessfully(): void
    {
        $this->addQueryParamsToMockedRequest([
            'page' => $currentPage = 4,
            'per' => $per = 10,
        ]);

        $this->expectQueryBuilderShouldSetFirstResultAndMaxResultWith($currentPage, $per);
        $paginatorMock = $this->mockPaginatorWithGivenData($data = ['some' => 'data1', 'some2' => 'data2']);
        $this->expectPaginatorShouldBeInitializedWithGivenPaginator($paginatorMock);
        $nbCumulativeResults = $this->getNbCumulativeResultsForCurrentPage($currentPage, $per, $data);

        $paginatedResult = $this->paginator->getPaginatedQueryResult($this->queryBuilderMock, $this->requestMock);

        $this->assertEquals(['data1', 'data2'], $paginatedResult->getCurrentPageResults());
        $this->assertEquals(2, $paginatedResult->getNbTotalResults());
        $this->assertEquals(10, $paginatedResult->getPageSize());
        $this->assertEquals(4, $paginatedResult->getPageNumber());
        $this->assertEquals(2, $paginatedResult->getCurrentPageSize());
        $this->assertEquals($nbCumulativeResults, $paginatedResult->getNbCumulativeResults());
    }

    /**
     * @throws Exception
     */
    public function testPaginatedApiResponseShouldReturnsDTOsWithItemsGivenInParametersWithOptionalParametersSuccessfully(): void
    {
        $this->expectQueryBuilderShouldSetFirstResultAndMaxResultWith(1, $this->defaultListPageSize);
        $paginatorMock = $this->mockPaginatorWithGivenData($data = [
            'object1' => $dummyObject1 = new DummyObject('Some title 1'),
            'object2' => $dummyObject2 = new DummyObject('Some title 2'),
        ]);
        $this->expectPaginatorShouldBeInitializedWithGivenPaginator($paginatorMock);
        $nbCumulativeResults = $this->getNbCumulativeResultsForCurrentPage(1, $this->defaultListPageSize, $data);
        $expectedData = [
            new DummyDTO($dummyObject1, 'Goku', 'San'),
            new DummyDTO($dummyObject2, 'Goku', 'San'),
        ];

        $paginatedResult = $this->paginator->getPaginatedQueryResult(
            $this->queryBuilderMock,
            $this->requestMock,
            DummyDTO::class,
            ['firstName' => 'Goku', 'lastName' => 'San']
        );

        $this->assertEquals($expectedData, $paginatedResult->getCurrentPageResults());
        $this->assertEquals(2, $paginatedResult->getNbTotalResults());
        $this->assertEquals($this->defaultListPageSize, $paginatedResult->getPageSize());
        $this->assertEquals(1, $paginatedResult->getPageNumber());
        $this->assertEquals(2, $paginatedResult->getCurrentPageSize());
        $this->assertEquals($nbCumulativeResults, $paginatedResult->getNbCumulativeResults());
    }


    private function expectQueryBuilderShouldSetFirstResultAndMaxResultWith(int $page, $listPageSize): void
    {
        $this->queryBuilderMock->expects($this->once())
            ->method('setFirstResult')
            ->with($this->equalTo($listPageSize * ($page - 1)))
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->once())
            ->method('setMaxResults')
            ->with($this->equalTo($listPageSize))
            ->willReturn($this->queryBuilderMock);
    }

    private function mockPaginatorWithGivenData(array $data): MockObject|ORMPaginator
    {
        $paginatorMock = $this->getMockedClass(ORMPaginator::class);
        $paginatorMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($data));

        $paginatorMock->expects($this->once())
            ->method('count')
            ->willReturn(count($data));

        return $paginatorMock;
    }

    private function expectPaginatorShouldBeInitializedWithGivenPaginator(MockObject|Paginator $paginatorMock): void
    {
        $this->paginatorFactoryMock->expects($this->once())
            ->method('initPaginator')
            ->with($this->equalTo($this->queryBuilderMock))
            ->willReturn($paginatorMock);
    }

    private function getNbCumulativeResultsForCurrentPage(int $currentPage, int $pageSize, array $data): float|int|null
    {
        // nb displayed results equal pageSize * (currentPage - 1) + nbItems
        return ($pageSize * ($currentPage - 1)) + count($data);
    }


    private function initManager(): Paginator
    {
        return new Paginator(
            $this->paginatorFactoryMock,
            $this->defaultListPageSize,
        );
    }

}

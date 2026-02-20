<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Builder\Expect;
use UbeeDev\LibBundle\Service\ApiManager;
use UbeeDev\LibBundle\Service\OptionsResolver;
use UbeeDev\LibBundle\Service\Paginator;
use UbeeDev\LibBundle\Service\PaginatorFactory;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class TestSearchDto
{
    public function __construct(
        public string $tab,
        public ?string $search,
        public array $ids,
    ) {
    }
}

class ApiManagerTest extends AbstractWebTestCase
{
    private ApiManager $apiManager;

    public function setUp(): void
    {
        parent::setUp();

        $entityManagerMock = $this->createStub(EntityManagerInterface::class);
        $optionsResolver = new OptionsResolver($entityManagerMock);
        $paginatorFactoryMock = $this->createStub(PaginatorFactory::class);
        $paginator = new Paginator($paginatorFactoryMock, 20);

        $this->apiManager = new ApiManager($paginator, $optionsResolver);
    }

    public function testSanitizeParametersReturnsArray(): void
    {
        $result = $this->apiManager->sanitizeParameters(
            data: ['tab' => 'public', 'search' => 'catan'],
            sanitizingExpectations: [
                'tab' => Expect::string(),
                'search' => Expect::string()->optional(),
            ],
        );

        $this->assertIsArray($result);
        $this->assertSame(['tab' => 'public', 'search' => 'catan'], $result);
    }

    public function testSanitizeToDtoReturnsDto(): void
    {
        $result = $this->apiManager->sanitizeToDto(
            data: ['tab' => 'public', 'search' => 'catan', 'ids' => ['1', '2']],
            sanitizingExpectations: [
                'tab' => Expect::string(),
                'search' => Expect::string()->optional(),
                'ids' => Expect::array()->optional(),
            ],
            dtoClass: TestSearchDto::class,
        );

        $this->assertInstanceOf(TestSearchDto::class, $result);
        $this->assertSame('public', $result->tab);
        $this->assertSame('catan', $result->search);
        $this->assertSame(['1', '2'], $result->ids);
    }

    public function testSanitizeToDtoWithDefaultValues(): void
    {
        $result = $this->apiManager->sanitizeToDto(
            data: [],
            sanitizingExpectations: [
                'tab' => Expect::string()->optional(),
                'search' => Expect::string()->optional(),
                'ids' => Expect::array()->optional(),
            ],
            dtoClass: TestSearchDto::class,
            defaultValues: [
                'tab' => 'public',
                'search' => null,
                'ids' => [],
            ],
        );

        $this->assertInstanceOf(TestSearchDto::class, $result);
        $this->assertSame('public', $result->tab);
        $this->assertNull($result->search);
        $this->assertSame([], $result->ids);
    }

    public function testSanitizeJsonParametersReturnsValidData(): void
    {
        $request = new Request(content: json_encode(['code' => '123456']));

        $result = $this->apiManager->sanitizeJsonParameters(
            request: $request,
            sanitizingExpectations: ['code' => Expect::string()],
        );

        $this->assertSame(['code' => '123456'], $result);
    }

    public function testSanitizeJsonParametersWithEmptyContent(): void
    {
        $request = new Request(content: '');

        $result = $this->apiManager->sanitizeJsonParameters(
            request: $request,
            sanitizingExpectations: ['code' => Expect::string()],
            defaultValues: ['code' => 'default'],
        );

        $this->assertSame(['code' => 'default'], $result);
    }

    public function testSanitizeJsonParametersWithAllowedValues(): void
    {
        $request = new Request(
            content: json_encode(['status' => 'active', 'name' => 'test']),
        );

        $result = $this->apiManager->sanitizeJsonParameters(
            request: $request,
            sanitizingExpectations: [
                'status' => Expect::string(),
                'name' => Expect::string(),
            ],
            allowedValues: ['status' => ['active', 'inactive']],
        );

        $this->assertSame(['status' => 'active', 'name' => 'test'], $result);
    }

    public function testSanitizeJsonToDtoReturnsDto(): void
    {
        $request = new Request(
            content: json_encode(['tab' => 'public', 'search' => 'catan', 'ids' => ['1', '2']]),
        );

        $result = $this->apiManager->sanitizeJsonToDto(
            request: $request,
            sanitizingExpectations: [
                'tab' => Expect::string(),
                'search' => Expect::string()->optional(),
                'ids' => Expect::array()->optional(),
            ],
            dtoClass: TestSearchDto::class,
        );

        $this->assertInstanceOf(TestSearchDto::class, $result);
        $this->assertSame('public', $result->tab);
        $this->assertSame('catan', $result->search);
        $this->assertSame(['1', '2'], $result->ids);
    }

    public function testSanitizeJsonToDtoWithDefaultValues(): void
    {
        $request = new Request(content: '{}');

        $result = $this->apiManager->sanitizeJsonToDto(
            request: $request,
            sanitizingExpectations: [
                'tab' => Expect::string()->optional(),
                'search' => Expect::string()->optional(),
                'ids' => Expect::array()->optional(),
            ],
            dtoClass: TestSearchDto::class,
            defaultValues: [
                'tab' => 'public',
                'search' => null,
                'ids' => [],
            ],
        );

        $this->assertInstanceOf(TestSearchDto::class, $result);
        $this->assertSame('public', $result->tab);
        $this->assertNull($result->search);
        $this->assertSame([], $result->ids);
    }
}

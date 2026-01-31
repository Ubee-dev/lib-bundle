<?php

namespace Khalil1608\LibBundle\Tests;

use Khalil1608\LibBundle\Entity\Date;
use Khalil1608\LibBundle\Entity\DateTime;
use Khalil1608\LibBundle\Model\PaginatedResult;
use Khalil1608\LibBundle\Service\Mailer;
use Khalil1608\LibBundle\Tests\Helper\Cleaner;
use Khalil1608\LibBundle\Tests\Helper\CleanerInterface;
use Khalil1608\LibBundle\Tests\Helper\DateMock;
use Khalil1608\LibBundle\Tests\Helper\DateTimeMock;
use Khalil1608\LibBundle\Tests\Helper\Factory;
use Khalil1608\LibBundle\Tests\Helper\FactoryInterface;
use Khalil1608\LibBundle\Tests\Helper\PHPUnitHelper;
use Khalil1608\LibBundle\Tests\Helper\ValidationReporter;
use Khalil1608\LibBundle\Traits\DateTimeTrait;
use Khalil1608\LibBundle\Validator\Validator;
use DG\BypassFinals;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use JetBrains\PhpStorm\ArrayShape;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionException;
use SlopeIt\ClockMock\ClockMock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Zfekete\BypassReadonly\BypassReadonly;

abstract class AbstractWebTestCase extends WebTestCase
{
    use DateTimeTrait;
    use PHPUnitHelper;

    protected ConsoleOutput $output;
    protected EntityManagerInterface $entityManager;
    /** @var Factory */
    protected FactoryInterface $factory;
    /** @var Cleaner */
    protected CleanerInterface $cleaner;
    protected Request|MockObject $requestMock;
    protected bool $purgeTables = true;
    protected AbstractController $controller;
    protected Validator|MockObject|Stub $validatorMock;
    protected ValidationReporter $validator;
    protected ?Filesystem $fileSystem;
    protected ?string $uploadPathForTest;
    protected ContainerInterface $container;
    protected array $params;
    protected bool $initClient = false;
    protected ?KernelBrowser $client = null;
    protected array $mockedServices = [];
    protected ?CommandTester $commandTester = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        if ($this->initClient) {
            $this->client = static::createClient();
        } else {
            self::bootKernel();
        }
        $this->output = new ConsoleOutput();
        $this->container = static::getContainer();
        $this->factory = $this->container->get(FactoryInterface::class);
        $this->cleaner = $this->container->get(CleanerInterface::class);
        // Use stub by default - mock created when assertValidatorShouldBeCalledWith is called
        $this->validatorMock = $this->createStub(Validator::class);
        $this->validator = $this->container->get(ValidationReporter::class);
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        if ($this->purgeTables) {
            $this->cleaner->purgeAllTables();
        }
        $this->purgeMockTimeFiles();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // reset mock DateTime
        $this->resetMockTime();
    }

    /**
     * Returns the absolute filename of an asset in the test directory's 'assets' subdirectory.
     * E.g. if the project has a test directory <project root>/tests and you request 'document.pdf',
     * '<project root>/tests/assets/document.pdf' will be returned.
     */
    public function getAsset(string $filename = 'document.pdf'): string
    {
        $reflector = new ReflectionClass(get_class($this));

        $testDir = $this->container->getParameter('kernel.project_dir') . '/tests';

        $file = implode(DIRECTORY_SEPARATOR, [$testDir, 'assets', $filename]);
        if (!file_exists($file)) {
            throw new InvalidArgumentException("Asset file '" . $filename . "' not found.");
        }
        return realpath($file);
    }

    /**
     * Get UploadedFile for a test asset document.
     */
    public function getUploadedFile(string $fileName = 'document.pdf'): UploadedFile
    {
        # Select the file from the filesystem
        return new UploadedFile(
        # Path to the file to send
            $this->getAsset($fileName),
            # Name of the sent file
            $fileName,
            # MIME type
            'application/pdf',
            null,
            true,
        );
    }

    public function cleanHtml($html): array|string|null
    {
        return preg_replace('/(\s|&nbsp;)+/', ' ', strip_tags($html));
    }

    protected function mockSession(): Session|MockObject
    {
        return $this->getMockedClass(Session::class);
    }

    protected function mockRequest(): Request
    {
        $this->requestMock = new Request();
        return $this->requestMock;
    }

    protected function getMockedClass($class): MockObject
    {
        return $this->createMock($class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T&MockObject
     */
    protected function getStubbedMock(string $class): MockObject
    {
        $mock = $this->createMock($class);
        // Configure the mock to not trigger "no expectations" notice
        // by setting a default expectation that accepts any calls
        $mock->expects($this->any())->method($this->anything());
        return $mock;
    }

    /**
     * @deprecated Use client instead
     */
    protected function initController(string $controller, array $argumentsInConstructor = []): void
    {
        $this->controller = new $controller(...$argumentsInConstructor);
        $this->controller->setContainer($this->container);
    }

    /**
     * @deprecated Use client instead
     */
    protected function initControllerWithMockedRequest(string $controller, array $argumentsInConstructor = []): void
    {
        $this->initController($controller, $argumentsInConstructor);
        $this->requestMock = $this->mockRequest();
    }

    protected function addQueryParamsToMockedRequest(array $params): void
    {
        foreach ($params as $key => $param) {
            $this->params[$key] = $param;
        }
        $this->requestMock->query->add($params);
    }

    protected function addPostParamsToMockedRequest(array $params): void
    {
        $this->requestMock->request->add($params);
    }

    protected function addFileParamsToMockedRequest(array $params): void
    {
        $this->requestMock->files->add($params);
    }

    protected function addParamsToMockedRequestHeaders(array $params): void
    {
        $this->requestMock->headers->add($params);
    }

    protected function addCustomParamsToMockedRequest(array $params): void
    {
        $this->requestMock->attributes->add($params);
    }

    protected function assertJsonResponse(JsonResponse|Response $response, mixed $data, int $status = 200): void
    {
        $this->assertEquals(
            $this->convertDataToJsonResponseWithStatus($data, $status)->getContent(),
            $response->getContent()
        );
    }

    protected function assertResponse(Response $response, mixed $data, int $status = 200): void
    {
        $this->assertEquals((new Response($data, $status))->getContent(), $response->getContent());
    }

    protected function assertValidatorShouldBeCalledWith(string $message, string $className, array $entityData): InvocationMocker
    {
        // Ensure we have a mock (not a stub) for expectations
        if (!$this->validatorMock instanceof MockObject) {
            $this->validatorMock = $this->getMockedClass(Validator::class);
        }

        $this->validatorMock->expects($this->once())
            ->method('setMessage')
            ->with($this->equalTo($message))
            ->willReturn($this->validatorMock);

        $this->validatorMock->expects($this->once())
            ->method('addValidation')
            ->with($this->callback(
                function ($entity) use ($entityData, $className) {
                    $this->assertInstanceOf($className, $entity);
                    foreach ($entityData as $key => $property) {
                        $method = 'get' . $key;
                        if (!method_exists($entity, $method)) {
                            $method = 'is' . $key;
                        }
                        if (!method_exists($entity, $method)) {
                            $method = 'was' . $key;
                        }

                        $entityValue = $entity->$method();

                        if ($entityValue instanceof Collection) {
                            $entityValue = array_values($entityValue->toArray());
                        }

                        if ($entityValue instanceof DateTime) {
                            $this->assertTrue($this->dateStartDuringGivenDay($property, $entityValue));
                        } else {
                            $this->assertEquals($entityValue, $property);
                        }
                    }
                    return true;
                }
            ))
            ->willReturn($this->validatorMock);

        return $this->validatorMock->expects($this->once())
            ->method('validate');
    }


    /**
     * @param $data
     * @param int $status
     * @return JsonResponse
     */
    protected function convertDataToJsonResponseWithStatus($data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status, [], false);
    }

    protected function initFileSystem()
    {
        $this->fileSystem = new Filesystem();
        $this->uploadPathForTest = $this->getPublicDirectory() . $this->container->getParameter('upload_dir') . '/' . Factory::UPLOAD_CONTEXT . '/';

        if ($this->fileSystem->exists($this->uploadPathForTest)) {
            $this->fileSystem->remove($this->uploadPathForTest);
        }

        $this->fileSystem->mkdir($this->uploadPathForTest);
    }

    /**
     * @return string
     */
    protected function getPublicDirectory(): string
    {
        return $this->container->getParameter('kernel.project_dir') . '/public';
    }

    protected function getPrivateDirectory(): string
    {
        return $this->container->getParameter('kernel.project_dir') . '/private';
    }

    protected function createFile($filename)
    {
        $filePath = $this->uploadPathForTest . $filename;

        $this->fileSystem->touch($filePath);
    }


    /**
     * @throws Exception
     */
    protected function removeDoctrineEntityListeners(
        array $entitiesWithListeners = [],
        array $listenersToDisable = []
    )
    {
        foreach ($entitiesWithListeners as $entity) {
            $metadata = $this->entityManager->getMetadataFactory()->getMetadataFor($entity);

            foreach ($metadata->entityListeners as $event => $listeners) {
                foreach ($listeners as $key => $listener) {
                    if (in_array($listener['class'], $listenersToDisable)) {
                        unset($listeners[$key]);
                    }
                }
                $metadata->entityListeners[$event] = $listeners;
            }
            $this->entityManager->getMetadataFactory()->setMetadataFor($entity, $metadata);
        }
    }

    /**
     * @throws Exception
     */
    protected function removeDoctrineEventListeners(
        string $listenerToDisable,
        array  $eventsToDisable = ['postPersist', 'postUpdate']
    )
    {
        $this->entityManager->getEventManager()->removeEventListener(
            $eventsToDisable,
            $this->container->get($listenerToDisable)
        );
    }


    /**
     * @throws Exception
     * @var string $time 2022-10-27T00:00:00+01:00
     */
    protected function mockTime(string $time): void
    {
        ClockMock::freeze(new DateTime($time));
        uopz_set_mock(DateTime::class, DateTimeMock::class);
        uopz_set_mock(Date::class, DateMock::class);
    }

    protected function resetMockTime(): void
    {
        try {
            ClockMock::reset();
            uopz_unset_mock(DateTime::class);
            uopz_unset_mock(Date::class);
        } catch (Exception) {
        }
    }

    protected function convertToBytes(string $from): ?int
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));

        //B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        if ($exponent === null) {
            return null;
        }


        return $number * (1024 ** $exponent);
    }

    /**
     * @throws Exception
     */
    protected function getRepository(string $className, array $parameters = []): ServiceEntityRepository
    {
        return new $className($this->container->get(ManagerRegistry::class), ...$parameters);
    }

    /**
     * @throws ReflectionException
     */
    protected function entityUseTrait(object $entity, string $trait): bool
    {
        return in_array($trait,
            array_keys((new ReflectionClass($entity::class))->getTraits())
        );
    }

    protected function createPaginatedResultMock(
        array $currentPageResult,
        int   $nbTotalResults = 15,
        int   $nbCumulativeResults = 10,
        int   $pageSize = 5,
    ): MockObject
    {
        $paginatedResult = $this->getMockedClass(PaginatedResult::class);

        $paginatedResult
            ->method('getCurrentPageResults')
            ->willReturn($currentPageResult);

        $paginatedResult
            ->method('getNbTotalResults')
            ->willReturn($nbTotalResults);

        $paginatedResult
            ->method('getNbCumulativeResults')
            ->willReturn($nbCumulativeResults);

        $paginatedResult
            ->method('getPageSize')
            ->willReturn($pageSize);

        return $paginatedResult;
    }


    /**
     * @param $responseData
     * @return MockObject
     */
    protected function jsonResponseMock($responseData): MockObject
    {
        $responseMock = $this->getMockedClass(ResponseInterface::class);

        $responseMock->expects($this->any())
            ->method('getHeaders')
            ->willReturn(['content-type' => ['application/json; charset=utf8']]);
        $responseMock->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($responseData));

        return $responseMock;
    }

    protected function mockServiceInContainer(string $serviceName, object $mockedService): void
    {
        $this->container->set(
            $serviceName,
            $mockedService
        );

        $this->mockedServices[] = [
            $serviceName,
            $mockedService
        ];
    }

    protected function mockServicesInContainer(array $services): void
    {
        foreach ($services as $service) {
            $this->mockServiceInContainer($service[0], $service[1]);
        }

    }

    protected function clientRequest(string $method, string $apiUrl, array $params = [], array $headers = []): void
    {
        $formattedHeaders = ['HTTP_ACCEPT' => 'application/json'];

        foreach ($headers as $headerName => $headerValue) {
            $formattedHeaders['HTTP_' . $headerName] = $headerValue;
        }
        $this->client->request(
            $method,
            $apiUrl,
            $params,
            [],
            $formattedHeaders
        );

        // Reboot kernel manually
        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
        // Prevent client from rebooting the kernel
        $this->client->disableReboot();

        foreach ($this->mockedServices as $service) {
            $this->container->set($service[0], $service[1]);
        }
    }

    protected function assertRoutesReturnStatusCode(int $expectedStatusCode, array $routes): void
    {
        foreach ($routes as $route) {
            $this->clientRequest($route[0], $route[1], params: $route[2] ?? [], headers: $route[3] ?? []);
            $this->assertEquals($expectedStatusCode, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        }
    }

    public static function createArgumentResolver(object $resolver): ArgumentResolver
    {
        $namedResolvers = [$resolver::class => $resolver];
        $namedResolvers = new ServiceLocator(array_map(fn($resolver) => fn() => $resolver, $namedResolvers));

        return new ArgumentResolver(new ArgumentMetadataFactory(), [], $namedResolvers);
    }

    /**
     * @throws MockEnabledException
     */
    protected function mockBuiltInFunction(string $namespace, string $functionName, mixed $expectedValue): void
    {
        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
            ->setName($functionName)
            ->setFunction(
                function () use ($expectedValue) {
                    return $expectedValue;
                }
            );

        $mock = $builder->build();
        $mock->disable();
        $mock->enable();
    }

    protected function mockClasses(array $classesToMock): void
    {
        foreach ($classesToMock as $class) {
            // creates a variable name from the class name, e.g. OfferRepository becomes offerRepositoryMock
            $varName = lcfirst(substr($class, strrpos($class, '\\') + 1)) . 'Mock';
            // Use getMockedClass which sets atLeast(0) to satisfy PHPUnit's "expectations configured" check
            $this->{$varName} = $this->getMockedClass($class);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T&Stub
     */
    protected function createStubbedClass(string $class): Stub
    {
        return $this->createStub($class);
    }

    protected function stubClasses(array $classesToStub): void
    {
        foreach ($classesToStub as $class) {
            // creates a variable name from the class name, e.g. OfferRepository becomes offerRepositoryStub
            $varName = lcfirst(substr($class, strrpos($class, '\\') + 1)) . 'Stub';
            $this->{$varName} = $this->createStubbedClass($class);
        }
    }

    protected function executeCommand(string $commandName, array $params = [], array $inputs = []): int
    {
        $application = new Application(self::$kernel);

        $command = $application->find($commandName);
        $this->commandTester = new CommandTester($command);

        if ($inputs) {
            $this->commandTester->setInputs($inputs);
        }
        // Execute the command
        return $this->commandTester->execute(array_merge($params, [
            '--env' => 'test',
        ]));
    }

    protected function assertCommandOutputContains(string $expectedOutput): void
    {
        $this->assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
    }

    private function purgeMockTimeFiles(): void
    {
        $fileSystem = new Filesystem();
        $filePath = $this->container->getParameter('kernel.project_dir')
            . '/tests/assets/mockTime'
            . getenv('TEST_TOKEN') . '.txt';
        if ($fileSystem->exists($filePath)) {
            unlink($filePath);
        }
    }
}

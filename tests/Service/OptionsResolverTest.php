<?php

namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Builder\Expect;
use Khalil1608\LibBundle\Config\CustomEnumInterface;
use Khalil1608\LibBundle\Config\ParameterType;
use Khalil1608\LibBundle\Exception\InvalidArgumentException;
use Khalil1608\LibBundle\Model\Type\Email;
use Khalil1608\LibBundle\Model\Type\Name;
use Khalil1608\LibBundle\Model\Type\PhoneNumber;
use Khalil1608\LibBundle\Model\Type\Url;
use Khalil1608\LibBundle\Service\OptionsResolver;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Khalil1608\LibBundle\Tests\Helper\DummyEnum;
use Khalil1608\LibBundle\Tests\Helper\PHPUnitHelper;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Contracts\Translation\TranslatorInterface;

class OptionsResolverTest extends AbstractWebTestCase
{
    use PHPUnitHelper;
    private OptionsResolver $optionsResolver;
    private MockObject|Stub $entityManagerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->entityManagerMock = $this->createStub(EntityManagerInterface::class);
        $this->initResolver();
    }

    public function testSanitizeString(): void
    {
        $params = ['field' => 'some value', 'field2' => 'other value'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'field' => ParameterType::STRING,
                'field2' => Expect::string(),
            ])
            ->resolve();

        $this->assertSame('some value', $result['field']);
        $this->assertSame('other value', $result['field2']);
    }

    public function testSanitizeStringStripsHtml(): void
    {
        $params = ['script' => '<script>alert("test")</script>', 'script2' => '<script>alert("test2")</script>'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'script' => ParameterType::STRING,
                'script2' => Expect::string(),
            ])
            ->resolve();

        $this->assertSame('alert("test")', $result['script']);
        $this->assertSame('alert("test2")', $result['script2']);
    }

    public function testSanitizeStringKeepsHtmlWhenConfigured(): void
    {
        $params = ['field' => '<p>some text</p>', 'field2' => '<p>other text</p>'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'field' => ['type' => ParameterType::STRING, 'stripHtml' => false],
                'field2' => Expect::string()->keepHtml(),
            ])
            ->resolve();

        $this->assertSame('<p>some text</p>', $result['field']);
        $this->assertSame('<p>other text</p>', $result['field2']);
    }

    public function testSanitizeInteger(): void
    {
        $params = ['int1' => '3', 'int2' => '0', 'int3' => '5', 'int4' => '7'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'int1' => ParameterType::INT,
                'int2' => ParameterType::INT,
                'int3' => Expect::int(),
                'int4' => Expect::int(),
            ])
            ->resolve();

        $this->assertSame(3, $result['int1']);
        $this->assertSame(0, $result['int2']);
        $this->assertSame(5, $result['int3']);
        $this->assertSame(7, $result['int4']);
    }

    public function testSanitizeFloat(): void
    {
        $params = ['float1' => '15.5', 'float2' => '18,6', 'float3' => '20.5', 'float4' => '22,8'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'float1' => ParameterType::FLOAT,
                'float2' => ParameterType::FLOAT,
                'float3' => Expect::float(),
                'float4' => Expect::float(),
            ])
            ->resolve();

        $this->assertSame(15.5, $result['float1']);
        $this->assertSame(18.6, $result['float2']);
        $this->assertSame(20.5, $result['float3']);
        $this->assertSame(22.8, $result['float4']);
    }

    public function testSanitizeBool(): void
    {
        $params = [
            'fromStringTrue' => 'true',
            'fromStringFalse' => 'false',
            'fromStringNumberTrue' => '1',
            'fromStringNumberFalse' => '0',
            'fromIntTrue' => 1,
            'fromIntFalse' => 0,
            'builderTrue' => 'true',
            'builderFalse' => 0,
        ];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'fromStringTrue' => ParameterType::BOOL,
                'fromStringFalse' => ParameterType::BOOL,
                'fromStringNumberTrue' => ParameterType::BOOL,
                'fromStringNumberFalse' => ParameterType::BOOL,
                'fromIntTrue' => ParameterType::BOOL,
                'fromIntFalse' => ParameterType::BOOL,
                'builderTrue' => Expect::bool(),
                'builderFalse' => Expect::bool(),
            ])
            ->resolve();

        $this->assertTrue($result['fromStringTrue']);
        $this->assertFalse($result['fromStringFalse']);
        $this->assertTrue($result['fromStringNumberTrue']);
        $this->assertFalse($result['fromStringNumberFalse']);
        $this->assertTrue($result['fromIntTrue']);
        $this->assertFalse($result['fromIntFalse']);
        $this->assertTrue($result['builderTrue']);
        $this->assertFalse($result['builderFalse']);
    }

    public function testSanitizeDate(): void
    {
        $params = ['date1' => '2020-02-08', 'date2' => '2021-03-15'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'date1' => ParameterType::DATE,
                'date2' => Expect::date(),
            ])
            ->resolve();

        $this->assertEquals($this->date('2020-02-08'), $result['date1']);
        $this->assertEquals($this->date('2021-03-15'), $result['date2']);
    }

    public function testSanitizeDatetime(): void
    {
        $params = [
            'datetime' => '2021-12-30T20:13:00 Africa/Accra',
            'timestamp' => '1597611050',
            'datetimeBuilder' => '2022-06-15T10:00:00 Europe/London',
            'timestampBuilder' => '1609459200',
        ];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'datetime' => ParameterType::DATETIME,
                'timestamp' => ParameterType::DATETIME,
                'datetimeBuilder' => Expect::datetime(),
                'timestampBuilder' => Expect::datetime(),
            ])
            ->resolve();

        $this->assertEquals($this->dateTime('2021-12-30 21:13:00'), $result['datetime']);
        $this->assertEquals($this->dateTime()->setTimestamp('1597611050'), $result['timestamp']);
        $this->assertTrue($result['datetime']->getTimezone() == new DateTimeZone('Europe/Paris'));
        $this->assertTrue($result['timestamp']->getTimezone() == new DateTimeZone('Europe/Paris'));
        $this->assertTrue($result['datetimeBuilder']->getTimezone() == new DateTimeZone('Europe/Paris'));
        $this->assertTrue($result['timestampBuilder']->getTimezone() == new DateTimeZone('Europe/Paris'));
    }

    public function testSanitizeMoney(): void
    {
        $params = ['price1' => '300', 'price2' => '500'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'price1' => 'money',
                'price2' => Expect::money(),
            ])
            ->resolve();

        $this->assertEquals(Money::EUR(300), $result['price1']);
        $this->assertEquals(Money::EUR(500), $result['price2']);
    }

    public function testSanitizeEnum(): void
    {
        $params = ['enum' => 1, 'enumBuilder' => 2];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'enum' => ['type' => ParameterType::ENUM, 'class' => DummyEnum::class],
                'enumBuilder' => Expect::enum(DummyEnum::class),
                'defaultEnum' => ['type' => ParameterType::ENUM, 'class' => DummyEnum::class],
                'defaultEnumBuilder' => Expect::enum(DummyEnum::class),
            ])
            ->setDefaultValues([
                'defaultEnum' => DummyEnum::SomeOtherValue,
                'defaultEnumBuilder' => DummyEnum::SomeValue,
            ])
            ->resolve();

        $this->assertSame(DummyEnum::SomeValue, $result['enum']);
        $this->assertSame(DummyEnum::SomeOtherValue, $result['enumBuilder']);
        $this->assertSame(DummyEnum::SomeOtherValue, $result['defaultEnum']);
        $this->assertSame(DummyEnum::SomeValue, $result['defaultEnumBuilder']);
    }

    public function testSanitizeCustomEnum(): void
    {
        $params = ['enum' => 1, 'enumBuilder' => 2];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'enum' => ['type' => ParameterType::CUSTOM_ENUM, 'class' => DummyCustomEnum::class],
                'enumBuilder' => Expect::customEnum(DummyCustomEnum::class),
                'defaultEnum' => ['type' => ParameterType::CUSTOM_ENUM, 'class' => DummyCustomEnum::class],
                'defaultEnumBuilder' => Expect::customEnum(DummyCustomEnum::class),
            ])
            ->setDefaultValues([
                'defaultEnum' => DummyCustomEnum::from(DummyCustomEnum::SomeOtherValue),
                'defaultEnumBuilder' => DummyCustomEnum::from(DummyCustomEnum::SomeValue),
            ])
            ->resolve();

        $this->assertEquals(DummyCustomEnum::from(DummyCustomEnum::SomeValue), $result['enum']);
        $this->assertEquals(DummyCustomEnum::from(DummyCustomEnum::SomeOtherValue), $result['enumBuilder']);
        $this->assertEquals(DummyCustomEnum::from(DummyCustomEnum::SomeOtherValue), $result['defaultEnum']);
        $this->assertEquals(DummyCustomEnum::from(DummyCustomEnum::SomeValue), $result['defaultEnumBuilder']);
    }

    public function testSanitizeEmail(): void
    {
        $params = ['email1' => 'toto@gmail.com', 'email2' => 'test@example.com'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'email1' => ParameterType::EMAIL,
                'email2' => Expect::email(),
            ])
            ->resolve();

        $this->assertEquals(Email::from('toto@gmail.com'), $result['email1']);
        $this->assertEquals(Email::from('test@example.com'), $result['email2']);
    }

    public function testSanitizeName(): void
    {
        $params = ['name1' => 'Goku', 'name2' => 'Vegeta'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'name1' => ParameterType::NAME,
                'name2' => Expect::name(),
            ])
            ->resolve();

        $this->assertEquals(Name::from('Goku'), $result['name1']);
        $this->assertEquals(Name::from('Vegeta'), $result['name2']);
    }

    public function testSanitizeUrl(): void
    {
        $params = ['url1' => 'https://www.google.fr', 'url2' => 'https://example.com'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'url1' => ParameterType::URL,
                'url2' => Expect::url(),
            ])
            ->resolve();

        $this->assertEquals(Url::from('https://www.google.fr'), $result['url1']);
        $this->assertEquals(Url::from('https://example.com'), $result['url2']);
    }

    public function testSanitizePhoneNumber(): void
    {
        $params = ['phone1' => '+33625097439', 'phone2' => '+33612345678'];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'phone1' => ParameterType::PHONE_NUMBER,
                'phone2' => Expect::phoneNumber(),
            ])
            ->resolve();

        $this->assertEquals(PhoneNumber::from('+33625097439'), $result['phone1']);
        $this->assertEquals(PhoneNumber::from('+33612345678'), $result['phone2']);
    }

    public function testSanitizeEntity(): void
    {
        $expectedEntity = new FakeEntity('Toto');
        $repositoryStub = $this->createStub(ServiceEntityRepository::class);

        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($repositoryStub);

        $repositoryStub
            ->method('findOneBy')
            ->with(['id' => "18"])
            ->willReturn($expectedEntity);

        $result = $this->optionsResolver
            ->setParameters(['entity1' => "18", 'entity2' => "18"])
            ->setSanitizingExpectations([
                'entity1' => ['type' => ParameterType::ENTITY, 'class' => FakeEntity::class],
                'entity2' => Expect::entity(FakeEntity::class),
            ])
            ->resolve();

        $this->assertSame($expectedEntity, $result['entity1']);
        $this->assertSame($expectedEntity, $result['entity2']);
    }

    public function testSanitizeEntityWithKeyParamAndExtraParams(): void
    {
        $otherEntity1 = new FakeEntity('Tata');
        $otherEntity2 = new FakeEntity('Titi');
        $repositoryStub = $this->createStub(ServiceEntityRepository::class);

        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($repositoryStub);

        $repositoryStub
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($otherEntity1, $otherEntity2) {
                if ($criteria === ['id' => "19", 'extra' => 'params1']) {
                    return $otherEntity1;
                }
                if ($criteria === ['slug' => 'some-slug', 'extra' => 'params2']) {
                    return $otherEntity2;
                }
                return null;
            });

        $result = $this->optionsResolver
            ->setParameters(['entity1' => "19", 'entity2' => "some-slug"])
            ->setSanitizingExpectations([
                'entity1' => [
                    'type' => ParameterType::ENTITY,
                    'class' => FakeEntity::class,
                    'keyParam' => 'id',
                    'extraParams' => ['extra' => 'params1']
                ],
                'entity2' => Expect::entity(FakeEntity::class)
                    ->by('slug')
                    ->extraParams(['extra' => 'params2']),
            ])
            ->resolve();

        $this->assertSame($otherEntity1, $result['entity1']);
        $this->assertSame($otherEntity2, $result['entity2']);
    }

    public function testSanitizeArray(): void
    {
        $params = [
            'array1' => [
                'some' => '<script>alert("test")</script>',
                'nested' => ['value' => '<script>alert("nested")</script>']
            ],
            'array2' => [
                'other' => '<script>alert("test2")</script>',
            ]
        ];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'array1' => ParameterType::ARRAY,
                'array2' => Expect::array(),
            ])
            ->resolve();

        $this->assertEquals([
            'some' => 'alert("test")',
            'nested' => ['value' => 'alert("nested")']
        ], $result['array1']);
        $this->assertEquals(['other' => 'alert("test2")'], $result['array2']);
    }

    public function testSanitizeNestedArray(): void
    {
        $params = [
            'nested1' => [
                ['firstName' => 'Goku', 'lastName' => 'Son'],
                ['firstName' => 'Harry', 'lastName' => 'Potter'],
            ],
            'nested2' => [
                ['firstName' => 'Vegeta', 'lastName' => 'Prince'],
            ]
        ];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'nested1' => [
                    'type' => ParameterType::ARRAY,
                    'items' => [
                        'firstName' => ParameterType::NAME,
                        'lastName' => ParameterType::NAME,
                    ]
                ],
                'nested2' => Expect::array()->items([
                    'firstName' => Expect::name(),
                    'lastName' => Expect::name(),
                ]),
            ])
            ->resolve();

        $this->assertEquals([
            ['firstName' => Name::from('Goku'), 'lastName' => Name::from('Son')],
            ['firstName' => Name::from('Harry'), 'lastName' => Name::from('Potter')],
        ], $result['nested1']);
        $this->assertEquals([
            ['firstName' => Name::from('Vegeta'), 'lastName' => Name::from('Prince')],
        ], $result['nested2']);
    }

    public function testSanitizeNestedArrayWithMixedTypes(): void
    {
        $params = [
            'signatures1' => [
                ["firstName" => "toto", "lastName" => "toto", "email" => "toto@gmail.com", "signUrl" => "https://toto.com"]
            ],
            'signatures2' => [
                ["firstName" => "tata", "lastName" => "tata", "email" => "tata@gmail.com", "signUrl" => "https://tata.com"]
            ]
        ];

        $result = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations([
                'signatures1' => [
                    'type' => ParameterType::ARRAY,
                    'items' => [
                        'firstName' => ParameterType::NAME,
                        'lastName' => ParameterType::NAME,
                        'email' => ParameterType::EMAIL,
                        'signUrl' => ParameterType::URL
                    ]
                ],
                'signatures2' => Expect::array()->items([
                    'firstName' => Expect::name(),
                    'lastName' => Expect::name(),
                    'email' => Expect::email(),
                    'signUrl' => Expect::url()
                ]),
            ])
            ->resolve();

        $this->assertEquals([
            ["firstName" => Name::from('toto'), "lastName" => Name::from('toto'), "email" => Email::from('toto@gmail.com'), "signUrl" => Url::from('https://toto.com')]
        ], $result['signatures1']);
        $this->assertEquals([
            ["firstName" => Name::from('tata'), "lastName" => Name::from('tata'), "email" => Email::from('tata@gmail.com'), "signUrl" => Url::from('https://tata.com')]
        ], $result['signatures2']);
    }

    public function testSanitizeEmptyNotRequiredArray(): void
    {
        $result = $this->optionsResolver
            ->setParameters(['array1' => null, 'array2' => null])
            ->setSanitizingExpectations([
                'array1' => [
                    'type' => ParameterType::ARRAY,
                    'items' => ['amount' => ParameterType::INT],
                    'required' => false,
                ],
                'array2' => Expect::array()
                    ->items(['amount' => ParameterType::INT])
                    ->optional(),
            ])
            ->resolve();

        $this->assertEquals([], $result['array1']);
        $this->assertEquals([], $result['array2']);
    }

    public function testSanitizeFile(): void
    {
        $file = $this->getUploadedFile();

        $result = $this->optionsResolver
            ->setParameters([
                'file1' => $file,
                'file2' => $file,
                'file3' => $file,
            ])
            ->setSanitizingExpectations([
                'file1' => 'file',
                'file2' => ParameterType::FILE,
                'file3' => Expect::file(),
            ])
            ->resolve();

        $this->assertSame($file, $result['file1']);
        $this->assertSame($file, $result['file2']);
        $this->assertSame($file, $result['file3']);
    }

    public function testSanitizeFileWithExtensions(): void
    {
        $file = $this->getUploadedFile('test.csv', 'text/csv');

        $result = $this->optionsResolver
            ->setParameters(['file1' => $file, 'file2' => $file])
            ->setSanitizingExpectations([
                'file1' => ['type' => ParameterType::FILE, 'extensions' => ['.csv', '.xlsx']],
                'file2' => Expect::file()->extensions(['.csv', '.xlsx']),
            ])
            ->resolve();

        $this->assertSame($file, $result['file1']);
        $this->assertSame($file, $result['file2']);
    }

    public function testSanitizeFileWithMimetypes(): void
    {
        $file = $this->getUploadedFile('test.csv', 'text/csv');

        $result = $this->optionsResolver
            ->setParameters(['file1' => $file, 'file2' => $file])
            ->setSanitizingExpectations([
                'file1' => ['type' => ParameterType::FILE, 'mimetypes' => ['text/csv', 'application/vnd.ms-excel']],
                'file2' => Expect::file()->mimetypes(['text/csv', 'application/vnd.ms-excel']),
            ])
            ->resolve();

        $this->assertSame($file, $result['file1']);
        $this->assertSame($file, $result['file2']);
    }

    public function testSanitizeFileWithExtensionsAndMimetypes(): void
    {
        $file = $this->getUploadedFile('test.csv', 'text/csv');

        $result = $this->optionsResolver
            ->setParameters(['file1' => $file, 'file2' => $file])
            ->setSanitizingExpectations([
                'file1' => [
                    'type' => ParameterType::FILE,
                    'extensions' => ['.csv', '.xlsx'],
                    'mimetypes' => ['text/csv', 'application/vnd.ms-excel'],
                ],
                'file2' => Expect::file()
                    ->extensions(['.csv', '.xlsx'])
                    ->mimetypes(['text/csv', 'application/vnd.ms-excel']),
            ])
            ->resolve();

        $this->assertSame($file, $result['file1']);
        $this->assertSame($file, $result['file2']);
    }

    public function testSanitizeFileShouldThrowExceptionIfInvalidExtension(): void
    {
        $file = $this->getUploadedFile('test.pdf', 'application/pdf');

        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters(['file' => $file])
                ->setSanitizingExpectations([
                    'file' => Expect::file()->extensions(['.csv', '.xlsx']),
                ])
                ->resolve();
        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals([
                'file' => "L'extension .pdf n'est pas autorisée. Extensions autorisées: .csv, .xlsx.",
            ], $exception->getErrors());
        }
        $this->assertTrue($errorCatched);
    }

    public function testSanitizeFileShouldThrowExceptionIfInvalidMimetype(): void
    {
        $file = $this->getUploadedFile('test.pdf', 'application/pdf');

        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters(['file' => $file])
                ->setSanitizingExpectations([
                    'file' => Expect::file()->mimetypes(['text/csv', 'application/vnd.ms-excel']),
                ])
                ->resolve();
        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals([
                'file' => "Le type de fichier application/pdf n'est pas autorisé. Types autorisés: text/csv, application/vnd.ms-excel.",
            ], $exception->getErrors());
        }
        $this->assertTrue($errorCatched);
    }

    public function testSanitizeWithDefaultValues(): void
    {
        $result = $this->optionsResolver
            ->setParameters(['value1' => 'null', 'value2' => 'null'])
            ->setSanitizingExpectations([
                'value1' => ParameterType::STRING,
                'value2' => Expect::string(),
            ])
            ->setDefaultValues([
                'value1' => 'hello',
                'value2' => 'world',
            ])
            ->resolve();

        $this->assertSame('hello', $result['value1']);
        $this->assertSame('world', $result['value2']);
    }

    public function testSanitizeWithAllowedValues(): void
    {
        $result = $this->optionsResolver
            ->setParameters(['int1' => '3', 'int2' => '2'])
            ->setSanitizingExpectations([
                'int1' => ParameterType::INT,
                'int2' => Expect::int(),
            ])
            ->setAllowedValues([
                'int1' => [1, 2, 3],
                'int2' => [1, 2, 3],
            ])
            ->resolve();

        $this->assertSame(3, $result['int1']);
        $this->assertSame(2, $result['int2']);
    }

    public function testSanitizeOptionalFieldsReturnNull(): void
    {
        $result = $this->optionsResolver
            ->setParameters([])
            ->setSanitizingExpectations([
                'field1' => ['type' => ParameterType::STRING, 'required' => false],
                'field2' => Expect::string()->optional(),
            ])
            ->resolve();

        $this->assertNull($result['field1']);
        $this->assertNull($result['field2']);
    }

    public function testSanitizeOptionalBoolWithDefaultValue(): void
    {
        $result = $this->optionsResolver
            ->setParameters([])
            ->setSanitizingExpectations([
                'bool1' => ['type' => ParameterType::BOOL, 'required' => false],
                'bool2' => Expect::bool()->optional(),
            ])
            ->setDefaultValues(['bool1' => true, 'bool2' => false])
            ->resolve();

        $this->assertTrue($result['bool1']);
        $this->assertFalse($result['bool2']);
    }

    public function testStrictModeRemovesUnexpectedFields(): void
    {
        $result = $this->optionsResolver
            ->setParameters([
                'requiredField' => 'some field',
                'unWantedValue' => 'some bad value',
            ])
            ->setSanitizingExpectations(['requiredField' => ParameterType::STRING])
            ->setStrictMode(true)
            ->resolve();

        $this->assertArrayHasKey('requiredField', $result);
        $this->assertArrayNotHasKey('unWantedValue', $result);
    }

    public function testNonStrictModeKeepsUnexpectedFields(): void
    {
        $result = $this->optionsResolver
            ->setParameters([
                'requiredField' => 'some field',
                'unWantedValue' => 'some bad value',
            ])
            ->setSanitizingExpectations(['requiredField' => ParameterType::STRING])
            ->setStrictMode(false)
            ->resolve();

        $this->assertArrayHasKey('requiredField', $result);
        $this->assertArrayHasKey('unWantedValue', $result);
        $this->assertSame('some bad value', $result['unWantedValue']);
    }

    /**
     * @throws Exception
     */
    public function testSanitizeShouldThrownAnExceptionIfRequiredFieldIsMissing(): void
    {
        $sanitizingExpectations = [
            'requiredField' => ParameterType::STRING,
            'otherRequiredField' => ['type' => ParameterType::STRING, 'required' => true],
            'requiredFieldWithDefaultValue' => ParameterType::STRING,
            'notRequiredFields' => ['type' => ParameterType::STRING, 'required' => false],
            'array' => ParameterType::ARRAY,
            'emptyNestedArray' => [
                'type' => ParameterType::ARRAY,
                'required' => false,
                'items' => ['firstName' => ParameterType::NAME]
            ],
            'nestedArray' => [
                'type' => ParameterType::ARRAY,
                'required' => false,
                'items' => ['firstName' => ParameterType::NAME]
            ],
            'requiredNestedArray' => [
                'type' => ParameterType::ARRAY,
                'items' => [
                    'firstName' => ['required' => false, 'type' => ParameterType::NAME],
                    'lastName' => ParameterType::NAME,
                ]
            ],
            'requiredNestedArrayWithoutData' => [
                'type' => ParameterType::ARRAY,
                'required' => true,
                'items' => [
                    'firstName' => ['required' => false, 'type' => ParameterType::NAME],
                    'lastName' => ParameterType::NAME,
                ]
            ]
        ];

        $defaultValues = [
            'requiredFieldWithDefaultValue' => 'hello',
        ];

        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters($parameters = [
                    'someParameters' => 'blabla',
                    'requiredField' => ' ',
                    'array' => ' ',
                    'nestedArray' => [['firstName' => null]],
                    'emptyNestedArray' => [],
                    'requiredNestedArray' => [
                        ['firstName' => 'Goku', 'lastName' => 'Son', 'fieldRemovedByStrictMode' => 'someValue'],
                        ['firstName' => 'Harry'],
                    ],
                    'requiredNestedArrayWithoutData' => [],
                ])
                ->setSanitizingExpectations($sanitizingExpectations)
                ->setDefaultValues($defaultValues)
                ->setStrictMode(true)
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'otherRequiredField' => 'Ce champ est obligatoire.',
                'requiredField' => 'Ce champ est obligatoire.',
                'array' => 'Ce champ est obligatoire.',
                'nestedArray' => [
                    0 => ['firstName' => 'Ce champ est obligatoire.'],
                ],
                'requiredNestedArray' => [
                    1 => ['lastName' => 'Ce champ est obligatoire.'],
                ],
                'requiredNestedArrayWithoutData' => 'Ce champ est obligatoire.',
            ], $exception->getErrors());
            $this->assertEquals($parameters, $exception->getData());
        }
        $this->assertTrue($errorCatched);
    }

    public function testSanitizeShouldThrowAnExceptionIfNonNullableFieldIsPassedNull(): void
    {
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters($parameters = [
                    'nonNullableInt' => '',
                ])
                ->setSanitizingExpectations(['nonNullableInt' => ParameterType::INT])
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'nonNullableInt' => 'Ce champ est obligatoire.'
            ], $exception->getErrors());
            $this->assertEquals($parameters, $exception->getData());

        }
        $this->assertTrue($errorCatched);
    }

    /**
     * @throws \Exception
     */
    public function testSanitizeShouldThrowAnExceptionIfFieldHasNotAllowedValues(): void
    {
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters(['badValue' => 18, 'otherBadValue' => false])
                ->setSanitizingExpectations(['badValue' => ParameterType::INT, 'otherBadValue' => ParameterType::BOOL])
                ->setAllowedValues(['badValue' => [1, 2], 'otherBadValue' => true])
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badValue' => 'La valeur 18 est invalide. Les valeurs acceptées sont: 1, 2.',
                'otherBadValue' => 'La valeur false est invalide. Les valeurs acceptées sont: true.',
            ], $exception->getErrors());
            $this->assertEquals(['badValue' => 18, 'otherBadValue' => false], $exception->getData());
        }
        $this->assertTrue($errorCatched);

        // allowed values are checked even if param is not required
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters(['badValue' => 18])
                ->setSanitizingExpectations(['badValue' => ['type' => ParameterType::INT, 'required' => false]])
                ->resolve();
        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badValue' => 'La valeur 18 est invalide. Les valeurs acceptées sont: 1, 2.'
            ], $exception->getErrors());
            $this->assertEquals(['badValue' => 18], $exception->getData());
        }

        $this->assertTrue($errorCatched);

        // check bad values for enum type
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters([
                    'badEnum' => 8,
                    'badCustomEnum' => 8,
                ])
                ->setSanitizingExpectations([
                    'badEnum' => ['type' => ParameterType::ENUM, 'class' => DummyEnum::class],
                    'badCustomEnum' => ['type' => ParameterType::CUSTOM_ENUM, 'class' => DummyCustomEnum::class],
                ])
                ->setAllowedValues([
                    'badEnum' => [
                        DummyEnum::SomeValue,
                        DummyEnum::SomeOtherValue
                    ],
                    'badCustomEnum' => [
                        DummyCustomEnum::SomeValue,
                        DummyCustomEnum::SomeOtherValue
                    ]
                ])
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badEnum' => 'La valeur 8 est invalide. Les valeurs acceptées sont: 1, 2.',
                'badCustomEnum' => 'La valeur 8 est invalide. Les valeurs acceptées sont: 1, 2.',
            ], $exception->getErrors());
            $this->assertEquals(['badEnum' => 8, 'badCustomEnum' => 8], $exception->getData());
        }
        $this->assertTrue($errorCatched, 'Error not catched on checking enum bad value');

        // check excluded values for enum type
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters([
                    'badEnum' => 2,
                    'badCustomEnum' => 2,
                ])
                ->setSanitizingExpectations([
                    'badEnum' => ['type' => ParameterType::ENUM, 'class' => DummyEnum::class],
                    'badCustomEnum' => ['type' => ParameterType::CUSTOM_ENUM, 'class' => DummyCustomEnum::class],
                ])
                ->setAllowedValues([
                    'badEnum' => [DummyEnum::SomeValue],
                    'badCustomEnum' => [DummyCustomEnum::SomeValue]
                ])
                ->resolve();
        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badEnum' => 'La valeur 2 est invalide. Les valeurs acceptées sont: 1.',
                'badCustomEnum' => 'La valeur 2 est invalide. Les valeurs acceptées sont: 1.',
            ], $exception->getErrors());
            $this->assertEquals(['badEnum' => 2, 'badCustomEnum' => 2], $exception->getData());
        }
        $this->assertTrue($errorCatched, 'Error not catched on checking enum bad value');
        $this->assertTrue($errorCatched);
    }

    /**
     * @throws Exception
     */
    public function testSanitizeShouldThrownAnExceptionIfBadEmailUrlPhoneNumberAndName(): void
    {
        $sanitizingExpectations = [
            'email' => ParameterType::EMAIL,
            'name' => ParameterType::NAME,
            'url' => ParameterType::URL,
            'phoneNumber' => ParameterType::PHONE_NUMBER,
        ];

        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters($parameters = [
                    'email' => 'badEmail',
                    'name' => '155151',
                    'url' => 'badUrl',
                    'phoneNumber' => 'badPhoneNumber',
                ])
                ->setSanitizingExpectations($sanitizingExpectations)
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'email' => 'Ce champ est invalide.',
                'name' => 'Ce champ est invalide.',
                'url' => 'Ce champ est invalide.',
                'phoneNumber' => 'Ce champ est invalide.',
            ], $exception->getErrors());
            $this->assertEquals($parameters, $exception->getData());
        }
        $this->assertTrue($errorCatched);
    }

    private function initResolver(): void
    {
        $this->optionsResolver = new OptionsResolver(
            $this->container->get(TranslatorInterface::class),
            $this->entityManagerMock
        );
    }
}

class FakeEntity {
    public function __construct(
        private readonly string $firstName
    ){}
}

class DummyCustomEnum implements CustomEnumInterface
{
    public const int SomeValue = 1;
    public const int SomeOtherValue = 2;

    public static function from(mixed $value): self
    {
        return match ((int)$value) {
            self::SomeValue => new self(self::SomeValue),
            self::SomeOtherValue => new self(self::SomeOtherValue),
            default => throw new \ValueError('Invalid enum value '.$value),
        };
    }

    public function __construct(private readonly int $value) {}

    public static function tryFrom(mixed $value): ?self
    {
        try {
            return self::from($value);
        } catch (\ValueError $e) {
            return null;
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }
}

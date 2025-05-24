<?php

namespace Khalil1608\LibBundle\Tests\Service;

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
use http\Message;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class OptionsResolverTest extends AbstractWebTestCase
{
    use PHPUnitHelper;
    private OptionsResolver $optionsResolver;
    private MockObject $entityManagerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->entityManagerMock = $this->getMockedClass(EntityManagerInterface::class);
        $this->initResolver();
    }

    /**
     * @throws Exception
     */
    public function testSanitizeSuccessfully(): void
    {
        $params = [
            'requiredField' => 'some field',
            'otherRequiredField' => 'some other field',
            'someInteger' => '3',
            'someOtherInteger' => '0',
            'defaultValue' => 'null',
            'defaultValueWithZero' => 'null',
            'unWantedValue' => 'some bad value',
            'script' => '<script>alert("test")</script>',
            'nestedArray' => [
                ['firstName' => 'Goku', 'lastName' => 'Son'],
                ['firstName' => 'Harry', 'lastName' => 'Potter'],
            ],
            'arrayValue' => [
                'some' => '<script>alert("test2")</script>',
                'some2' => [
                    'some3' => '<script>alert("test3")</script>'
                ]
            ],
            'fieldWithNotStripedHtml' => '<p>some text</p>',
            'uploadedFile' => $file = $this->getUploadedFile(),
            'float' => '15.5',
            'float2' => '18,6',
            'bool' => 'true',
            'bool2' => 'false',
            'trueBool' => '1',
            'falseBool' => '0',
            'trueBool2' => 1,
            'falseBool2' => 0,
            'date' => '2020-02-08',
            'timestamp' => '1597611050',
            'price' => '300',
            'datetime' => '2021-12-30T20:13:00 Africa/Accra',
            'someEnum' => 1,
            'email' => 'toto@gmail.com',
            'firstName' => 'Goku',
            'url' => 'https://www.google.fr',
            'entity' => "18",
            'otherEntity1' => "19",
            'otherEntity2' => "some-slug",
            'emptyNotRequiredArray' => null,
            'signatures' => [
                [
                  "firstName" => "toto",
                  "lastName" => "toto",
                  "email" => "toto@gmail.com",
                  "signUrl" => "htttp://toto.com",
                ]
            ],
            'phoneNumber' => '+33625097439',
        ];

        $sanitizingExpectations = [
            'requiredField' => 'string',
            'otherRequiredField' => ['type' => 'string', 'required' => true],
            'someInteger' => 'int',
            'someOtherInteger' => 'int',
            'defaultValue' => 'string',
            'defaultValueWithZero' => 'int',
            'notRequiredFields' => ['type' => 'string', 'required' => false],
            'isPinned' => ['type' => 'bool', 'required' => false],
            'script' => 'string',
            'nestedArray' => [
                'type' => 'array',
                'items' => [
                    'firstName' => 'name',
                    'lastName' => 'name',
                ]
            ],
            'arrayValue' => 'array',
            'fieldWithNotStripedHtml' => ['type' => 'string', 'stripHtml' => false],
            'uploadedFile' => 'file',
            'float' => 'float',
            'float2' => 'float',
            'bool' => 'bool',
            'bool2' => 'bool',
            'trueBool' => 'bool',
            'falseBool' => 'bool',
            'trueBool2' => 'bool',
            'falseBool2' => 'bool',
            'date' => 'date',
            'timestamp' => 'datetime',
            'price' => 'money',
            'datetime' => 'datetime',
            'someEnum' => ['type' => 'enum', 'class' => DummyEnum::class],
            'defaultEnum' => ['type' => 'enum', 'class' => DummyEnum::class],
            'email' => 'email',
            'firstName' => 'name',
            'url' => 'url',
            'entity' => ['type' => 'entity', 'class' => FakeEntity::class],
            'otherEntity1' => [
                'type' => 'entity',
                'class' => FakeEntity::class,
                'keyParam' => 'id',
                'extraParams' => ['extra' => 'params1']
            ],
            'otherEntity2' => [
                'type' => 'entity',
                'class' => FakeEntity::class,
                'keyParam' => 'slug',
                'extraParams' => ['extra' => 'params2']
            ],
            'emptyNotRequiredArray' => [
                'type' => 'array',
                'items' => [
                    'amount' => 'int',
                ],
                'required' => false,
            ],
            'signatures' => [
                'type' => 'array',
                'items' => [
                    'firstName' => 'name',
                    'lastName' => 'name',
                    'email' => 'email',
                    'signUrl' => 'url'
                ]
            ],
            'phoneNumber' => 'phoneNumber',
        ];

        $allowedValues = [
            'someInteger' => [1, 2, 3],
        ];

        $defaultValues = [
            'defaultValue' => 'hello',
            'defaultValueWithZero' => 0,
            'isPinned' => true,
            'defaultEnum' => DummyEnum::SomeOtherValue,
        ];

        $expectedEntity = new FakeEntity('Toto');
        $otherEntity1 = new FakeEntity('Tata');
        $otherEntity2 = new FakeEntity('Titi');
        $mockedRepository = $this->getMockBuilder(ServiceEntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $this->entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo(FakeEntity::class))
            ->willReturn($mockedRepository);

        $mockedRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($expectedEntity, $otherEntity1, $otherEntity2) {
                if ($criteria === ['id' => "18"]) {
                    return $expectedEntity;
                }
                if ($criteria === ['id' => "19", 'extra' => 'params1']) {
                    return $otherEntity1;
                }
                if ($criteria === ['slug' => 'some-slug', 'extra' => 'params2']) {
                    return $otherEntity2;
                }
                return null;
            })
        ;

        $sanitizedParameters = $this->optionsResolver
            ->setParameters($params)
            ->setSanitizingExpectations($sanitizingExpectations)
            ->setAllowedValues($allowedValues)
            ->setDefaultValues($defaultValues)
            ->setStrictMode(true)
            ->resolve();

        $expectedData = [
            'requiredField' => 'some field',
            'otherRequiredField' => 'some other field',
            'someInteger' => 3,
            'someOtherInteger' => 0,
            'defaultValue' => 'hello',
            'defaultValueWithZero' => 0,
            'script' => 'alert("test")',
            'nestedArray' => [
                ['firstName' => Name::from('Goku'), 'lastName' => Name::from('Son')],
                ['firstName' => Name::from('Harry'), 'lastName' => Name::from('Potter')],
            ],
            'arrayValue' => [
                'some' => 'alert("test2")',
                'some2' => [
                    'some3' => 'alert("test3")'
                ]
            ],
            'fieldWithNotStripedHtml' => '<p>some text</p>',
            'uploadedFile' => $file,
            'float' => 15.5,
            'float2' => 18.6,
            'bool' => true,
            'bool2' => false,
            'trueBool' => true,
            'falseBool' => false,
            'trueBool2' => true,
            'falseBool2' => false,
            'date' => $this->date('2020-02-08'),
            'datetime' => $this->dateTime('2021-12-30 21:13:00'),
            'timestamp' => $this->dateTime()->setTimestamp('1597611050'),
            'price' => Money::EUR(300),
            'someEnum' => DummyEnum::SomeValue,
            'defaultEnum' => DummyEnum::SomeOtherValue,
            'isPinned' => true,
            'notRequiredFields' => null,
            'email' => Email::from('toto@gmail.com'),
            'firstName' => Name::from('Goku'),
            'url' => Url::from('https://www.google.fr'),
            'entity' => $expectedEntity,
            'otherEntity1' => $otherEntity1,
            'otherEntity2' => $otherEntity2,
            'emptyNotRequiredArray' => [],
            'signatures' => [
                [
                    "firstName" => Name::from('toto'),
                    "lastName" => Name::from('toto'),
                    "email" => Email::from('toto@gmail.com'),
                    "signUrl" => Url::from('htttp://toto.com'),
                ]
            ],
            'phoneNumber' => PhoneNumber::from('+33625097439'),
        ];

        $this->assertEquals($expectedData, $sanitizedParameters);
        // test with strict equals for integer and floats
        $this->assertSame(3, $sanitizedParameters['someInteger']);
        $this->assertSame(0, $sanitizedParameters['someOtherInteger']);
        $this->assertSame(0, $sanitizedParameters['defaultValueWithZero']);
        $this->assertSame(15.5, $sanitizedParameters['float']);
        $this->assertSame(18.6, $sanitizedParameters['float2']);
        $this->assertTrue($sanitizedParameters['timestamp']->getTimezone() == new DateTimeZone('Europe/Paris'));
        $this->assertTrue($sanitizedParameters['datetime']->getTimezone() == new DateTimeZone('Europe/Paris'));

        $sanitizedParameters =  $this->optionsResolver
            ->setStrictMode(false)
            ->resolve()
        ;

        $expectedData['unWantedValue'] = "some bad value";
        $this->assertEquals($expectedData, $sanitizedParameters);

    }

    /**
     * @throws Exception
     */
    public function testSanitizeShouldThrownAnExceptionIfRequiredFieldIsMissing(): void
    {
        $sanitizingExpectations = [
            'requiredField' => 'string',
            'otherRequiredField' => ['type' => 'string', 'required' => true],
            'requiredFieldWithDefaultValue' => 'string',
            'notRequiredFields' => ['type' => 'string', 'required' => false],
            'array' => 'array',
            'emptyNestedArray' => [
                'type' => 'array',
                'required' => false,
                'items' => ['firstName' => 'name']
            ],
            'nestedArray' => [
                'type' => 'array',
                'required' => false,
                'items' => ['firstName' => 'name']
            ],
            'requiredNestedArray' => [
                'type' => 'array',
                'items' => [
                    'firstName' => ['required' => false, 'type' => 'name'],
                    'lastName' => 'name',
                ]
            ],
            'requiredNestedArrayWithoutData' => [
                'type' => 'array',
                'required' => true,
                'items' => [
                    'firstName' => ['required' => false, 'type' => 'name'],
                    'lastName' => 'name',
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
                ->setSanitizingExpectations(['nonNullableInt' => 'int'])
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
                ->setSanitizingExpectations(['badValue' => 'int', 'otherBadValue' => 'bool'])
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
                ->setSanitizingExpectations(['badValue' => ['type' => 'int', 'required' => false]])
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
                ->setParameters(['badEnum' => 8])
                ->setSanitizingExpectations(['badEnum' => ['type' => 'enum', 'class' => DummyEnum::class]])
                ->setAllowedValues([
                    'badEnum' => [
                        DummyEnum::SomeValue,
                        DummyEnum::SomeOtherValue
                    ]
                ])
                ->resolve();

        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badEnum' => 'La valeur 8 est invalide. Les valeurs acceptées sont: 1, 2.'
            ], $exception->getErrors());
            $this->assertEquals(['badEnum' => 8], $exception->getData());
        }
        $this->assertTrue($errorCatched, 'Error not catched on checking enum bad value');

        // check excluded values for enum type
        $errorCatched = false;
        try {
            $this->optionsResolver
                ->setParameters(['badEnum' => 2])
                ->setSanitizingExpectations(['badEnum' => ['type' => 'enum', 'class' => DummyEnum::class]])
                ->setAllowedValues([
                    'badEnum' => [DummyEnum::SomeValue]
                ])
                ->resolve();
        } catch (InvalidArgumentException $exception) {
            $errorCatched = true;
            $this->assertEquals('Parameters fail the sanitizing expectations.', $exception->getMessage());
            $this->assertEquals([
                'badEnum' => 'La valeur 2 est invalide. Les valeurs acceptées sont: 1.'
            ], $exception->getErrors());
            $this->assertEquals(['badEnum' => 2], $exception->getData());
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
            'email' => 'email',
            'name' => 'name',
            'url' => 'url',
            'phoneNumber' => 'phoneNumber',
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
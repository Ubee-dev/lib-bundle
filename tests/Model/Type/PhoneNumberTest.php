<?php

namespace UbeeDev\LibBundle\Tests\Model\Type;

use UbeeDev\LibBundle\Model\Type\PhoneNumber;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class PhoneNumberTest extends AbstractWebTestCase
{
    public function testTryFrom(): void
    {
        $phoneNumber = PhoneNumber::tryFrom('33625097439');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::tryFrom('+33625097439');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::tryFrom('06 25 09 74 39');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::tryFrom('+33 6 25 09 74 39');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::tryFrom('+85515327004');
        $this->assertEquals('15327004', $phoneNumber->phoneNumber);
        $this->assertEquals('855', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::tryFrom('+855 015 327 004');
        $this->assertEquals('15327004', $phoneNumber->phoneNumber);
        $this->assertEquals('855', $phoneNumber->countryCallingCode);

        $invalidNumbers = [
            'badNumber',
            '',
            '85515327004',
        ];

        foreach ($invalidNumbers as $invalidNumber) {
            $this->assertNull(PhoneNumber::tryFrom($invalidNumber));
        }
    }

    public function testFrom(): void
    {
        $phoneNumber = PhoneNumber::from('33625097439');
        $this->assertInstanceOf(PhoneNumber::class, $phoneNumber);
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::from('+33625097439');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::from('06 25 09 74 39');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::from('+33 6 25 09 74 39');
        $this->assertEquals('625097439', $phoneNumber->phoneNumber);
        $this->assertEquals('33', $phoneNumber->countryCallingCode);

        $phoneNumber = PhoneNumber::from('+85515327004');
        $this->assertEquals('15327004', $phoneNumber->phoneNumber);
        $this->assertEquals('855', $phoneNumber->countryCallingCode);

        $this->expectException(\ValueError::class);
        PhoneNumber::from('badPhoneNumber');
    }

    public function testJsonSerialize(): void
    {
        $honeNumber = PhoneNumber::from('+33625097439');
        $this->assertEquals([
            'countryCallingCode' => '33',
            'phoneNumber' => '625097439'
        ], $honeNumber->jsonSerialize());
    }

    public function testToString(): void
    {
        $phoneNumber = PhoneNumber::from('+33625097439');
        $this->assertEquals('+33625097439', $phoneNumber->__toString());
    }

}
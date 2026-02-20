<?php

namespace UbeeDev\LibBundle\Tests\Model\Type;

use UbeeDev\LibBundle\Model\Type\HtmlName;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class HtmlNameTest extends AbstractWebTestCase
{
    public function testTryFrom(): void
    {
        $validNames = [
            'email',
            'user_name',
            'user-name',
            'tags[]',
            'user[age]',
            'user[address][city]',
            '_privateField',
            'user1',
            'u1[info]',
        ];

        foreach ($validNames as $validName) {
            $name = HtmlName::tryFrom($validName);

            $this->assertInstanceOf(HtmlName::class, $name);
            $this->assertEquals($validName, $name->value);
        }

        $invalidNames = [
            '1user',
            'user name',
            'user@name',
            'user[]name',
            'user[age',
            'user[age]]',
            '',
            '   ',
            null,
        ];

        foreach ($invalidNames as $invalidName) {
            $this->assertNull(HtmlName::tryFrom($invalidName));
        }
    }

    public function testFrom(): void
    {
        $name = HtmlName::from('u1[info]');

        $this->assertInstanceOf(HtmlName::class, $name);
        $this->assertEquals('u1[info]', $name->value);

        $this->expectException(\ValueError::class);
        HtmlName::from('1user');
    }

    public function testJsonSerialize(): void
    {
        $name = HtmlName::from('email');
        $this->assertEquals('email', $name->jsonSerialize());
    }

    public function testToString(): void
    {
        $name = HtmlName::from('email');
        $this->assertEquals('email', $name->__toString());
    }
}
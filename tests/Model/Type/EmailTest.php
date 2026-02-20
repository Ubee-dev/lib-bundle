<?php

namespace UbeeDev\LibBundle\Tests\Model\Type;

use UbeeDev\LibBundle\Model\Type\Email;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class EmailTest extends AbstractWebTestCase
{
    public function testTryFrom(): void
    {
        $email = Email::tryFrom('toto@gmail.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('toto@gmail.com', $email->value);

        $this->assertNull(Email::tryFrom('bademail'));
    }

    public function testFrom(): void
    {
        $email = Email::from('toto@gmail.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('toto@gmail.com', $email->value);

        $this->expectException(\ValueError::class);
        Email::from('bademail');
    }

    public function testJsonSerialize(): void
    {
        $email = Email::from('toto@gmail.com');
        $this->assertEquals('toto@gmail.com', $email->jsonSerialize());
    }

    public function testToString(): void
    {
        $email = Email::from('toto@gmail.com');
        $this->assertEquals('toto@gmail.com', $email->__toString());
    }

}
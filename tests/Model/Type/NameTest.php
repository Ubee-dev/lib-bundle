<?php

namespace UbeeDev\LibBundle\Tests\Model\Type;

use UbeeDev\LibBundle\Model\Type\Name;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class NameTest extends AbstractWebTestCase
{
    public function testTryFrom(): void
    {
        $validNames = [
            'Brandon T’KINT\'-DE ROODENBEKE.',
            'Élodie D\'Avignon',
            'José Álvarez-García',
            'Örjan Sörensen',
            'Łukasz Kowalski',
            'Mário Štefánik',
            'François L’Huillier',
            'Anne-Marie O’Connor',
            'Grégory Dupont.',
            'André-Benoît',
            'Àççénts Üñîqûês',
            'Peña García',
            'Niño de Guzmán',
        ];

        foreach ($validNames as $validName) {
            $name = Name::tryFrom($validName);

            $this->assertInstanceOf(Name::class, $name);
            $this->assertEquals($validName, $name->value);
        }

        $invalidNames = [
            'bad@name',
            '12345',
            'Brandon!T’KINT',
            'Name_with_underscores',
            '',
            null,
            '   ', // spaces only
            'Name With$Special#Chars',
        ];

        foreach ($invalidNames as $invalidName) {
            $this->assertNull(Name::tryFrom($invalidName));
        }
    }

    public function testFrom(): void
    {
        $name = Name::from('Brandon T’KINT\'-DE ROODENBEKE.');

        $this->assertInstanceOf(Name::class, $name);
        $this->assertEquals('Brandon T’KINT\'-DE ROODENBEKE.', $name->value);

        $this->expectException(\ValueError::class);
        Name::from('bad@name');
    }

    public function testJsonSerialize(): void
    {
        $name = Name::from('Brandon');
        $this->assertEquals('Brandon', $name->jsonSerialize());
    }

    public function testToString(): void
    {
        $name = Name::from('Brandon');
        $this->assertEquals('Brandon', $name->__toString());
    }

}
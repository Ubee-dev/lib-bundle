<?php

namespace Khalil1608\LibBundle\Tests\Helper;

class DummyDTO implements \JsonSerializable
{
    private DummyObject $dummyObject;
    private string $firstName;
    private string $lastName;

    public function __construct(DummyObject $dummyObject, string $firstName, string $lastName)
    {
        $this->dummyObject = $dummyObject;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getDummyObject(): DummyObject
    {
        return $this->dummyObject;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function jsonSerialize(): array
    {
        return [
            'dummyObject' => $this->getDummyObject(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
        ];
    }
}

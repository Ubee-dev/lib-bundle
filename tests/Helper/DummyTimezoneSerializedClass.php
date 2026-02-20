<?php


namespace UbeeDev\LibBundle\Tests\Helper;

use UbeeDev\LibBundle\Entity\DateTime;

class DummyTimezoneSerializedClass implements \JsonSerializable
{

    private int $id;
    private DateTime $startAt;
    private DateTime $endAt;
    private string $firstName;
    private string $lastName;

    public function __construct(int $id, string $firstName, string $lastName, DateTime $startAt, DateTime $endAt)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'startAt' => $this->startAt,
            'objects' => [[
                'firstName' => $this->firstName,
                'startAt' => $this->startAt,
                'endAt' => $this->endAt,
            ],
            [
                'lastName' => $this->lastName,
                'startAt' => $this->startAt,
                'endAt' => $this->endAt,
            ]]
        ];
    }
}

<?php


namespace UbeeDev\LibBundle\Tests\Helper;

use UbeeDev\LibBundle\Model\JsonSerializable;

class DummySerializedClass implements JsonSerializable
{

    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @param array $params
     * @return array
     */
    public function jsonSerialize(array $params = []): array
    {
        return [
            'id' => $this->id,
            'someData' => $params['someData'] ?? null
        ];
    }
}

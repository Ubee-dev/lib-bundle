<?php


namespace Khalil1608\LibBundle\Tests\Helper;

use Khalil1608\LibBundle\Model\JsonSerializable;

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

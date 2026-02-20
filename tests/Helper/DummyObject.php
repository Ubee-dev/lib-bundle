<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use JetBrains\PhpStorm\ArrayShape;

class DummyObject implements \JsonSerializable
{
    private string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    #[ArrayShape(['title' => "string"])]
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->getTitle(),
        ];
    }
}

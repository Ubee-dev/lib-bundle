<?php

namespace UbeeDev\LibBundle\Exception;

class InvalidArgumentException extends \Exception implements \JsonSerializable
{
    private array $errors;
    private mixed $data;

    public function __construct(string $message, array $errors = [], mixed $data = null)
    {
        $this->errors = $errors;
        $this->data = $data;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->getErrors()
        ];
    }
}

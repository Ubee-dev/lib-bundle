<?php


namespace Khalil1608\LibBundle\Exception;


class InvalidArgumentException extends \Exception implements \JsonSerializable
{
    private $errors = [];
    private $data = null;

    public function __construct($message, $errors = [], $data = null)
    {
        $this->errors = $errors;
        $this->data = $data;
        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getData()
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

<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationReporter
{
    private $validator;
    private $validationGroups;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->validationGroups = ['Default'];
    }

    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    public function report($entity)
    {
        $err = $this->validator->validate($entity, null, $this->validationGroups);
        return [
            'err' => $err,
            'count' => count($err),
            'message' => (string)$err,
        ];
    }

    public function validate($entity)
    {
        return $this->validator->validate($entity, null, $this->validationGroups);
    }
}
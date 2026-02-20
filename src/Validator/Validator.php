<?php

namespace UbeeDev\LibBundle\Validator;

use UbeeDev\LibBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\{ConstraintViolation, Validator\ValidatorInterface};

class Validator
{
    private ValidatorInterface $validator;
    private string $message = '';
    private array $validations = [];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function addValidation(object $entity, ?string $prefix = null): self
    {
        $this->validations[] = [$entity, $prefix];
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        $errorResponse = [];
        foreach ($this->validations as $validation) {

            $errors = $this->validator->validate($validation[0]);
            $prefix = $validation[1];
            if(count($errors)) {

                /** @var ConstraintViolation $error */
                foreach ($errors as $error) {
                    if($prefix) {
                        $errorResponse[$prefix][$error->getPropertyPath()] = $error->getMessage();
                    } else {
                        $errorResponse[$error->getPropertyPath()] = $error->getMessage();
                    }

                }
            }
        }

        $this->clearValidations();

        if(count($errorResponse)) {
            throw new InvalidArgumentException($this->message, $errorResponse);
        }
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    private function clearValidations(): void
    {
        $this->validations = [];
    }
}

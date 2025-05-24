<?php


namespace Khalil1608\LibBundle\Validator;

use Khalil1608\LibBundle\Exception\InvalidArgumentException;
use Symfony\Component\Validator\{ConstraintViolation, Validator\ValidatorInterface};

class Validator
{

    /** @var ValidatorInterface  */
    private $validator;

    /** @var string */
    private $message;

    /** @var array */
    private $validations = [];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param $entity
     * @param string|null $prefix
     * @return $this
     */
    public function addValidation($entity, $prefix = null)
    {
        $this->validations[] = [$entity, $prefix];
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate()
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

    /**
     * @param $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    private function clearValidations()
    {
        $this->validations = [];
    }
}

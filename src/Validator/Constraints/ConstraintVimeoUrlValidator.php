<?php

namespace  Khalil1608\LibBundle\Validator\Constraints;


use Khalil1608\LibBundle\Traits\VideoTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConstraintVimeoUrlValidator extends ConstraintValidator
{
    use VideoTrait;

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ConstraintVimeoUrl) {
            throw new UnexpectedTypeException($constraint, ConstraintVimeoUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if ($this->isVimeoUrl($value) === false) {
            $this->context->buildViolation($constraint->validationFailMessage)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}

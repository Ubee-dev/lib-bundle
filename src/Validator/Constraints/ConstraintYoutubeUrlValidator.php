<?php

namespace  UbeeDev\LibBundle\Validator\Constraints;


use UbeeDev\LibBundle\Traits\VideoTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConstraintYoutubeUrlValidator extends ConstraintValidator
{
    use VideoTrait;

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ConstraintYoutubeUrl) {
            throw new UnexpectedTypeException($constraint, ConstraintYoutubeUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if ($this->isYoutubeUrl($value) === false) {
            $this->context->buildViolation($constraint->validationFailMessage)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}

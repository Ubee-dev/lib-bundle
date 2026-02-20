<?php

namespace UbeeDev\LibBundle\Validator\Constraints;

use App\Entity\Media;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AllowedContentTypeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllowedContentType) {
            throw new UnexpectedTypeException($constraint, AllowedContentType::class);
        }

        // custom constraints should ignore null values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        // return if media doesn't have restriction on file
        if (is_null($value) || !$constraint->allowedMimeTypes) {
            return;
        }

        if (!$value instanceof Media) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, Media::class);
        }

        $currentContentType = $value->getContentType();

        // check if content type matches allowed contentType
        $isMatching = array_filter($constraint->allowedMimeTypes, function ($v) use ($currentContentType) {
            return preg_match('~' . $v . '~', $currentContentType);
        });

        if (!$isMatching) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ mime_type }}', implode(', ', $constraint->allowedMimeTypes))
                ->addViolation();
        }
    }
}

<?php

namespace UbeeDev\LibBundle\Validator\Constraints;

use UbeeDev\LibBundle\Traits\VideoTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ConstraintVideoProviderUrlValidator extends ConstraintValidator
{
    use VideoTrait;

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ConstraintVideoProviderUrl) {
            throw new UnexpectedTypeException($constraint, ConstraintVideoProviderUrl::class);
        }

        // Empty values are considered valid
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $isValid = false;

        foreach ($constraint->providers as $provider) {
            switch (strtolower($provider)) {
                case 'youtube':
                    if ($this->isYoutubeUrl($value)) {
                        $isValid = true;
                    }
                    break;
                case 'vimeo':
                    if ($this->isVimeoUrl($value)) {
                        $isValid = true;
                    }
                    break;
                case 'facebook':
                    if ($this->isFacebookEmbedUrl($value)) {
                        $isValid = true;
                    }
                    break;
            }

            if ($isValid) {
                break;
            }
        }

        if (!$isValid) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ url }}', $value)
                ->setParameter('{{ providers }}', implode(', ', $constraint->providers))
                ->addViolation();
        }
    }
}
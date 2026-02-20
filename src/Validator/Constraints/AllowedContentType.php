<?php

namespace UbeeDev\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class AllowedContentType extends Constraint
{
    #[HasNamedArguments]
    public function __construct(
        public array $allowedMimeTypes = [],
        public string $message = 'media.content_type.invalid_mime_type',
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}

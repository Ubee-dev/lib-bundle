<?php

namespace UbeeDev\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ConstraintVideoProviderUrl extends Constraint
{
    public string $message = 'L\'URL "{{ url }}" n\'est pas une URL vidéo valide pour les fournisseurs autorisés.';
    public array $providers = ['youtube', 'vimeo', 'facebook'];

    public function __construct(
        ?array $providers = null,
        ?string $message = null,
        ?array $options = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        $options = $options ?? [];

        if ($providers !== null) {
            $options['providers'] = $providers;
        }

        if ($message !== null) {
            $options['message'] = $message;
        }

        parent::__construct($options, $groups, $payload);
    }

    public function getDefaultOption(): ?string
    {
        return 'providers';
    }

    public function getRequiredOptions(): array
    {
        return ['providers'];
    }
}
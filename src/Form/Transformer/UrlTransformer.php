<?php

namespace UbeeDev\LibBundle\Form\Transformer;

use UbeeDev\LibBundle\Model\Type\Url;
use Symfony\Component\Form\DataTransformerInterface;

class UrlTransformer implements DataTransformerInterface
{
    /**
     * Transform object Url to string for display in form.
     */
    public function transform(mixed $value): ?string
    {
        if ($value instanceof Url) {
            return $value->value;
        }

        return null;
    }

    /**
     * Transform string to Url object when form is submitted.
     */
    public function reverseTransform(mixed $value): ?Url
    {
        return Url::tryFrom($value);
    }
}
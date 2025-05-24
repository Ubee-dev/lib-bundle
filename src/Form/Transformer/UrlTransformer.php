<?php

namespace Khalil1608\LibBundle\Form\Transformer;

use Khalil1608\LibBundle\Model\Type\Url;
use Symfony\Component\Form\DataTransformerInterface;

class UrlTransformer implements DataTransformerInterface
{
    /**
     * Transform object Url to string for display in form.
     */
    public function transform($value): ?string
    {
        if ($value instanceof Url) {
            return $value->value;
        }

        return null;
    }

    /**
     * Transform string to Url object when form is submitted.
     */
    public function reverseTransform($value): ?Url
    {
        return Url::tryFrom($value);
    }
}
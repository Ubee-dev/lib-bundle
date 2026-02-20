<?php

namespace UbeeDev\LibBundle\Form\Transformer;

use UbeeDev\LibBundle\Model\Type\HtmlName;
use Symfony\Component\Form\DataTransformerInterface;

class HtmlNameTransformer implements DataTransformerInterface
{
    /**
     * Transform object HtmlName to string for display in form.
     */
    public function transform($value): ?string
    {
        if ($value instanceof HtmlName) {
            return $value->value;
        }

        return null;
    }

    /**
     * Transform string to HtmlName object when form is submitted.
     */
    public function reverseTransform($value): ?HtmlName
    {
        return HtmlName::tryFrom($value);
    }
}
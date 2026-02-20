<?php
namespace UbeeDev\LibBundle\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Markdown
{
    public function __construct(
        public string $mappedProperty
    ) {
    }
}
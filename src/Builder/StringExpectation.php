<?php

namespace UbeeDev\LibBundle\Builder;

use UbeeDev\LibBundle\Config\ParameterType;

class StringExpectation extends ExpectationBuilder
{
    public function stripHtml(bool $stripHtml = true): static
    {
        return $this->setExpectation('stripHtml', $stripHtml);
    }

    public function keepHtml(): static
    {
        return $this->stripHtml(false);
    }
}

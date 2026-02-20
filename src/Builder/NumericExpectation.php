<?php

namespace UbeeDev\LibBundle\Builder;

class NumericExpectation extends ExpectationBuilder
{
    public function min(int|float $min): static
    {
        return $this->setExpectation('min', $min);
    }

    public function max(int|float $max): static
    {
        return $this->setExpectation('max', $max);
    }

    public function range(int|float $min, int|float $max): static
    {
        return $this->min($min)->max($max);
    }
}

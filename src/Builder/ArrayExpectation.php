<?php

namespace Khalil1608\LibBundle\Builder;

class ArrayExpectation extends ExpectationBuilder
{
    /**
     * Define the structure of array items
     */
    public function items(array $itemsExpectation): static
    {
        return $this->setExpectation('items', $itemsExpectation);
    }
}

<?php

namespace Khalil1608\LibBundle\Builder;

use Khalil1608\LibBundle\Config\ParameterType;

class EnumExpectation extends ExpectationBuilder
{
    public function __construct(ParameterType $type, string $class)
    {
        parent::__construct($type);
        $this->setExpectation('class', $class);
    }
}

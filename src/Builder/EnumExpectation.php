<?php

namespace UbeeDev\LibBundle\Builder;

use UbeeDev\LibBundle\Config\ParameterType;

class EnumExpectation extends ExpectationBuilder
{
    public function __construct(ParameterType $type, string $class)
    {
        parent::__construct($type);
        $this->setExpectation('class', $class);
    }
}

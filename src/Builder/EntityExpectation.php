<?php

namespace UbeeDev\LibBundle\Builder;

use UbeeDev\LibBundle\Config\ParameterType;

class EntityExpectation extends ExpectationBuilder
{
    public function __construct(ParameterType $type, string $class)
    {
        parent::__construct($type);
        $this->setExpectation('class', $class);
    }

    /**
     * Define the key parameter to search for (default: 'id')
     */
    public function keyParam(string $keyParam): static
    {
        return $this->setExpectation('keyParam', $keyParam);
    }

    /**
     * Add extra parameters for the entity search
     */
    public function extraParams(array $extraParams): static
    {
        return $this->setExpectation('extraParams', $extraParams);
    }

    /**
     * Search by any field name
     */
    public function by(string $fieldName): static
    {
        return $this->keyParam($fieldName);
    }
}

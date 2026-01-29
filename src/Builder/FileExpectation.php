<?php

namespace Khalil1608\LibBundle\Builder;

use Khalil1608\LibBundle\Config\ParameterType;

class FileExpectation extends ExpectationBuilder
{
    public function __construct(ParameterType $type)
    {
        parent::__construct($type);
    }

    /**
     * Define allowed file extensions (e.g., ['.csv', '.xlsx', '.xls'])
     *
     * @param string[] $extensions
     */
    public function extensions(array $extensions): static
    {
        return $this->setExpectation('extensions', $extensions);
    }

    /**
     * Define allowed MIME types (e.g., ['text/csv', 'application/vnd.ms-excel'])
     *
     * @param string[] $mimetypes
     */
    public function mimetypes(array $mimetypes): static
    {
        return $this->setExpectation('mimetypes', $mimetypes);
    }
}

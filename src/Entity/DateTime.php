<?php


namespace Khalil1608\LibBundle\Entity;

class DateTime extends AbstractDateTime
{
    public function __toString(): string
    {
        return $this->formatDate($this, 'c');
    }
}

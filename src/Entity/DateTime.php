<?php


namespace UbeeDev\LibBundle\Entity;

class DateTime extends AbstractDateTime
{
    public function __toString(): string
    {
        return $this->formatDate($this, 'c');
    }
}

<?php


namespace Khalil1608\LibBundle\Entity;

use DateTimeZone;

class Date extends AbstractDateTime
{
    public function __construct($time = 'now', DateTimeZone $timezone = null)
    {
        parent::__construct($time, $timezone);
        $this->setTime(0,0,0,0);
    }

    public function jsonSerialize(): string
    {
        return $this->formatDate($this, 'Y-m-d');
    }

    public function __toString()
    {
        return $this->formatDate($this, 'Y-m-d');
    }
}

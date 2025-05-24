<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Khalil1608\LibBundle\Entity\Date;
use DateTimeZone;
use SlopeIt\ClockMock\ClockMock;

class DateMock extends Date
{
    public function __construct(?string $datetime = 'now', ?DateTimeZone $timezone = null)
    {
        $datetime = $datetime ?? 'now';

        parent::__construct($datetime, $timezone);

        $this->setTimestamp(strtotime($datetime, ClockMock::getFrozenDateTime()->getTimestamp()));

        $this->setTime(0,0);
    }

    private function shouldUseMicrosecondsOfFrozenDate(string $datetime): bool
    {
        // After some empirical tests, we've seen that microseconds are set to the current actual ones only when all of
        // these variables are false (i.e. when an absolute date or time is not provided).
        $parsedDate = date_parse($datetime);
        return $parsedDate['year'] === false
            && $parsedDate['month'] === false
            && $parsedDate['day'] === false
            && $parsedDate['hour'] === false
            && $parsedDate['minute'] === false
            && $parsedDate['second'] === false;
    }
}
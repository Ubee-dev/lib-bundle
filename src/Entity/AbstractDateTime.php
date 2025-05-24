<?php


namespace Khalil1608\LibBundle\Entity;

use Khalil1608\LibBundle\Traits\DateTimeTrait;
use IntlDateFormatter;

abstract class AbstractDateTime extends \DateTime implements \JsonSerializable
{
    const DEFAULT_TIMEZONE = 'Europe/Paris';

    use DateTimeTrait;

    /**
     * @param \DateTimeInterface $dateTime
     * @param bool $strict
     * @return bool
     */
    public function isLater(\DateTimeInterface $dateTime, bool $strict = false): bool
    {
        return  $strict ? $this > $dateTime : $this >= $dateTime;
    }

    /**
     * @param \DateTimeInterface $dateTime
     * @param bool $strict
     * @return bool
     */
    public function isBefore(\DateTimeInterface $dateTime, bool $strict = false): bool
    {
        return $strict ? $this < $dateTime : $this <= $dateTime;
    }

    /**
     * @param bool $withDay
     * @return string
     */
    public function convertToString(bool $withDay = false): string
    {
        $locale = 'fr_FR';
        $pattern = $withDay ? 'EEEE d MMMM yyyy' : 'd MMMM yyyy';
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, self::DEFAULT_TIMEZONE, IntlDateFormatter::GREGORIAN, $pattern);

        return $formatter->format($this);
    }

    /**
     * @param \DateTime $givenDay
     * @return bool
     */
    public function startDuringGivenDay(\DateTimeInterface $givenDay): bool
    {
        return $this->format('d-m-y') === $givenDay->format('d-m-y');
    }

    public function jsonSerialize(): string
    {
        return $this->formatDate($this, 'c');
    }

    public function isBetween(\DateTimeInterface $dateMin, \DateTimeInterface $dateMax): bool
    {
        return $this->isLater($dateMin) && $this->isBefore($dateMax);
    }
}

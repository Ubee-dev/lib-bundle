<?php

namespace Khalil1608\LibBundle\Traits;

use Khalil1608\LibBundle\Entity\AbstractDateTime;
use Khalil1608\LibBundle\Entity\Date;
use Khalil1608\LibBundle\Entity\DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;

trait DateTimeTrait
{
    use StringTrait;

    public function getSecondsBetweenDates(DateTimeInterface $date1, DateTimeInterface $date2): int
    {
        return $date1->getTimestamp() - $date2->getTimestamp();
    }

    public function convertHoursToSeconds($nbHours): float|int
    {
        return $nbHours * 3600;
    }

    public function addSecondsToDate(DateTime $date, int $nbSeconds): DateTime
    {
        return $this->modifyDateUnit($date, $nbSeconds, 'seconds');
    }

    public function addMinutesToDate(DateTimeInterface $date, int $nbMinutes): DateTimeInterface
    {
        return $this->modifyDateUnit($date, $nbMinutes, 'minutes');
    }

    public function addHoursToDate(DateTimeInterface $date, int $nbHours): DateTimeInterface
    {
        return $this->modifyDateUnit($date, $nbHours, 'hours');
    }

    public function addDaysToDate(DateTimeInterface $date, int $nbDays): DateTimeInterface
    {
        return $this->modifyDateUnit($date, $nbDays, 'days');
    }

    public function addMonthsToDate(DateTimeInterface $date, int $nbMonths): DateTime
    {
        $year = $date->format('Y');
        $month = $date->format('n');
        $day = $date->format('d');
        $hours = $date->format('H');
        $minutes = $date->format('i');
        $seconds = $date->format('s');

        $date = DateTime::createFromFormat('Y-n-d', $year . '-' . $month . '-01');
        $date->modify($nbMonths.' months');

        if (!checkdate($date->format('n'), $day, $date->format('Y'))) {
            $date->modify('last day of');
        } else {
            $date = DateTime::createFromFormat('Y-n-d', $date->format('Y') . '-' . $date->format('n') . '-' . $day);
        }

        $date->setTime($hours, $minutes, $seconds);
        return (new DateTime())->setTimestamp($date->getTimestamp());
    }

    public function dateStartDuringGivenDay(DateTimeInterface $date, DateTimeInterface $givenDay): bool
    {
        return $date->format('d-m-y') === $givenDay->format('d-m-y');
    }

    /**
     * @throws Exception
     */
    public function dateTime(?string $datetimeString = 'now', $timezone = AbstractDateTime::DEFAULT_TIMEZONE): DateTime
    {
        $datetimeString = $datetimeString ?? 'now';

        // 15-{+2 months} || 15-{+2 years} || 15-{+1 year} || 15-{+1 month}
        if (preg_match('/(\d{1,2})[.\-]\{(\+{0,1}|\-{0,1})(\d*) (month|year)s{0,1}\}/', $datetimeString, $outputArray)) {
            $date = $this->convertCustomDateRegexResultToDate($outputArray, $timezone);

        // 15/{+2 months} || 15/{+1 month} || 15/{+1 year} || 15/{+2 years}
        } elseif (preg_match('/(\d{1,2})[.\/]\{(\+{0,1}|\-{0,1})(\d*) (month|year)s{0,1}\}/', $datetimeString, $outputArray)) {
            $date = $this->convertCustomDateRegexResultToDate($outputArray, $timezone);

        }  else {
            $date = new DateTime($datetimeString, new DateTimeZone($timezone));
        }

        return $date->setTime( $date->format('H'), $date->format('i'), 0,0);
    }
    
    public function date(?string $datetimeString = null): Date
    {
        $dateTime = $this->dateTime($datetimeString);

        return (new Date($dateTime->format('d-m-Y')));
    }

    /**
     * @throws Exception
     */
    public function convertDateTimeToString(string $date, $withDay = false, $withYear = true): string
    {
        $format = str_contains($date, '/') ? 'slash' : 'string';
        $dateTime = $this->dateTime($date);

        if($format === 'slash') {
            return $withYear ? $dateTime->format('d/m/Y') : $dateTime->format('d/m');
        }

        setlocale(LC_TIME, 'fr_FR.utf8');
        // with year : 1 janvier 2022
        // without : 1 janvier
        $format = $withYear ? 'd MMMM y' : 'd MMMM';

        if($withDay) {
            $format = 'EEEE '.$format;
        }

        $output = $this->convertDateToString($dateTime, $format);
        $date = strtolower(ucwords(trim($output)));

        // remove double space
        return preg_replace('/\s+/', ' ', $date);
    }

    /**
     * @throws Exception
     */
    public function convertJsonDateTimeToFormattedDate(string $json): string
    {
        $json = json_decode($json, true);
        $format = $json['format'];
        $day = $json['day'] ?? null;
        $month = $json['month'] ?? null;
        $year = $json['year'] ?? null;
        $hour = $json['hour'] ?? null;
        $date = $this->dateTime();

        if($hour) {
            $date->modify($hour);
        }
        if($day) {
            if(is_numeric($day)) {
                $date->modify($date->format('Y').'-'.$date->format('m').'-'.$day);
            } else {
                $date->modify($day);
            }
        }

        if($month) {
            if(is_numeric($month)) {
                $date->modify($date->format('Y').'-'.$month.'-'.$date->format('d'));
            } else {
                preg_match('/(\+{0,1}|\-{0,1})(\d*) (month)s{0,1}/', $month, $outputArray);
                $date = $this->addMonthsToDate($date, $outputArray[1].$outputArray[2]);
            }
        }

        if($year) {
            if(is_numeric($year)) {
                $date->modify($year.'-'.$date->format('m').'-'.$date->format('d'));
            } else {
                if($year === 'next') {
                    $currentDate = new DateTime();
                    $currentYear = $currentDate->format('Y-m-d') > $date ? $currentDate->modify('+1 year') : $currentDate;
                    $date->modify($currentYear->format('Y').'-'.$date->format('m').'-'.$date->format('d'));
                } else {
                    $currentYear = $this->dateTime($year);
                    $date->modify($currentYear->format('Y').'-'.$date->format('m').'-'.$date->format('d'));
                }

            }
        }

        setlocale(LC_TIME, 'fr_FR.utf8');
        $type = $json['type'] ?? null;
        if($type === 'strftime') {
            return ucwords($this->convertDateToString($date, $format));
        } elseif ($format === "string") {
            // 1 janvier 2022
            return $this->convertDateToString($date, 'd MMMM y');
        } else {
            return $date->format($format);
        }
    }

    /**
     * @throws Exception
     */
    public function isToday(DateTimeInterface $dateTime): bool
    {
        return ($dateTime->format('d-m-Y') == (new DateTime())->format('d-m-Y'));
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return bool
     * @throws Exception
     */
    public function isPast(DateTimeInterface $dateTime): bool
    {
        return $dateTime < new \DateTime();
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return bool
     * @throws Exception
     */
    public function isFuture(DateTimeInterface $dateTime): bool
    {
        return $dateTime > new \DateTime();
    }

    public function formatDate(?DateTimeInterface $dateTime, $format = 'Y-m-d'): ?string
    {
        return $dateTime?->format($format);
    }

    public function diffBetweenDates(DateTimeInterface $date1, DateTimeInterface $date2, string $type): int
    {
        switch ($type) {
            case 'years':
                return $date2->diff($date1)->y;
            case 'months':
                $months = $date2->diff($date1);
                return (($months->y) * 12) + ($months->m);
            case 'days':
                return $date2->diff($date1)->format("%a");
            case 'hours':
                $diff = $date2->diff($date1);
                $hours = $diff->h;
                return $hours + ($diff->days*24);
            case 'minutes':
                $diff = $date2->diff($date1);
                $minutes = $diff->days * 24 * 60;
                $minutes += $diff->h * 60;
                $minutes += $diff->i;
                return $minutes;
            case 'seconds':
                return $date2->getTimestamp() - $date1->getTimestamp();
        }
    }

    /**
     * @param DateTimeInterface $date
     * @param string $timeZone
     * @param string|null $time
     * @return DateTimeInterface
     * @throws Exception
     */
    public function convertLocalDateTimeToDefaultTimezone(DateTimeInterface $date, string $timeZone, string $time = null): DateTimeInterface
    {
        if($time) {
            $time = explode(':', $time);
        }

        if($time && count($time) !== 3) {
            throw new Exception('If you pass time, you must give hour:minute:second');
        }

        $date->setTimezone(new DateTimeZone($timeZone));
        $date->setTime($time[0] ?? $date->format('H'), $time[1] ?? $date->format('i'), $time[2] ?? $date->format('s'), 0);
        $date->setTimezone(new DateTimeZone(AbstractDateTime::DEFAULT_TIMEZONE));
        return $date;
    }

    /**
     * @param AbstractDateTime $date1
     * @param AbstractDateTime $date2
     * @return AbstractDateTime
     */
    public function getEarliestDate(AbstractDateTime $date1, AbstractDateTime $date2): AbstractDateTime
    {
        return clone ($date1->isBefore($date2) ? $date1 : $date2);
    }

    /**
     * @param AbstractDateTime $date1
     * @param AbstractDateTime $date2
     * @return AbstractDateTime
     */
    public function getLatestDate(AbstractDateTime $date1, AbstractDateTime $date2): AbstractDateTime
    {
        return clone ($date1->isLater($date2) ? $date1 : $date2);
    }

    /**
     * @param string $timezone
     * @return string returns a string like +01:00
     * @throws Exception
     */
    public function computeOffsetForGivenTimezone(string $timezone): string
    {
        $tz = new DateTimeZone($timezone);
        $offset = $tz->getOffset(new \DateTime);
        $offsetPrefix = $offset < 0 ? '-' : '+';
        $offsetFormatted = gmdate('H:i', abs($offset));

        return "{$offsetPrefix}{$offsetFormatted}";
    }

    public function convertDateToString(AbstractDateTime $date, string $pattern): string
    {
        $fmt = datefmt_create(
            'fr_FR', // The output language.
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            pattern: $pattern
        );
        return trim(datefmt_format($fmt, $date->getTimestamp()));
    }

    private function modifyDateUnit(DateTimeInterface $date, int $numUnits, string $unit): DateTimeInterface
    {
        $newDate = clone($date);
        if($numUnits < 0) {
            $newDate->modify($numUnits.' '.$unit);
        } else {
            $newDate->modify('+'.$numUnits.' '.$unit);
        }

        return $newDate;
    }
}

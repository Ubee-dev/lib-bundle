<?php


namespace UbeeDev\LibBundle\Tests\Traits;


use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use UbeeDev\LibBundle\Traits\DateTimeTrait;

class DateTimeTraitTest extends AbstractWebTestCase
{
    use DateTimeTrait;

    public function testGetSecondsBetweenDates()
    {
        $date1 = new DateTime('2019-08-16');
        $date1->setTime(23,59,40);

        $date2 = new DateTime('2019-08-16');
        $date2->setTime(23,59,50);

        $secondsBetweenDates = $this->getSecondsBetweenDates($date1, $date2);
        $this->assertEquals(-10, $secondsBetweenDates);

        $secondsBetweenDates = $this->getSecondsBetweenDates($date2, $date1);
        $this->assertEquals(10, $secondsBetweenDates);
    }

    public function testConvertHoursToSeconds()
    {
        $this->assertEquals(7200, $this->convertHoursToSeconds(2));
    }

    /**
     * @throws \Exception
     */
    public function testAddSecondsToDate()
    {
        $date = new DateTime('2019-08-16');
        $date->setTime(23,59,40);
        $newDate = $this->addSecondsToDate($date, 10);
        $this->assertEquals($date->getTimestamp() + 10, $newDate->getTimestamp());
    }

    /**
     * @throws \Exception
     */
    public function testAddMinutesToDate()
    {
        $date = new DateTime('2019-08-16');
        $date->setTime(23,40);
        $newDate = $this->addMinutesToDate($date, 10);
        $this->assertEquals($date->getTimestamp() + (10 * 60), $newDate->getTimestamp());
    }

    public function testAddHoursToDate()
    {
        $date = new DateTime('2019-08-16');
        $date->setTime(13, 0);
        $newDate = $this->addHoursToDate($date, 10);
        $this->assertEquals($date->getTimestamp() + (10 * 60 * 60), $newDate->getTimestamp());
    }

    public function testAddDaysToDate()
    {
        $date = new DateTime('2019-12-30');
        $newDate = $this->addDaysToDate($date, 2);
        $this->assertEquals('2020-01-01', $newDate->format('Y-m-d'));
    }

    public function testAddMonthsToDate()
    {
        $date = new DateTime('2019-08-16');
        $date = $this->addMonthsToDate($date, 5);
        $this->assertEquals('2020-01-16', $date->format('Y-m-d'));
    }

    public function testDateStartDuringGivenDay()
    {
        $date1 = new DateTime('2019-08-16');
        $date2 = new DateTime('2019-08-17');
        $date3 = new DateTime('2019-08-16');

        $this->assertTrue($this->dateStartDuringGivenDay($date1, $date3));
        $this->assertFalse($this->dateStartDuringGivenDay($date1, $date2));
    }

    /**
     * @throws \Exception
     */
    public function testDateTime()
    {
        $date = $this->dateTime('2019-08-16');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertEquals('2019-08-16', $date->format('2019-08-16'));

        $date = $this->dateTime('+1 month');
        $expectedDate = new DateTime('+1 month');
        $this->assertEquals($expectedDate->format('Y-m-d'), $date->format('Y-m-d'));

        $date = $this->dateTime('05-{+5 months}');
        $in5months = new DateTime('+5 months');
        $this->assertEquals($in5months->format('m'), $date->format('m'));
        $this->assertEquals('05', $date->format('d'));
    }

    /**
     * @throws \Exception
     */
    public function testConvertDateTimeToString()
    {
        $dateTimeToString = $this->convertDateTimeToString('2019-07-16');
        $this->assertEquals('16 juillet 2019', $dateTimeToString);
    }

    public function testFormatDate()
    {
        $formattedDate = new DateTime('2019-08-16');
        $this->assertEquals('2019-08-16', $this->formatDate($formattedDate));
        $this->assertEquals('16-08-2019', $this->formatDate($formattedDate, 'd-m-Y'));
    }

    /**
     * @throws \Exception
     */
    public function testDiffBetweenDates()
    {
        $date1 = $this->dateTime('2018-08-16');
        $date2 = $this->dateTime('2020-09-23');

        $difference = $this->diffBetweenDates($date1, $date2, 'years');
        $this->assertEquals(2, $this->diffBetweenDates($date1, $date2, 'years'));
        $this->assertEquals(25, $this->diffBetweenDates($date1, $date2, 'months'));
        $this->assertEquals(769, $this->diffBetweenDates($date1, $date2, 'days'));
        $this->assertEquals(769 * 24, $this->diffBetweenDates($date1, $date2, 'hours'));
        $this->assertEquals(769 * 24 * 60, $this->diffBetweenDates($date1, $date2, 'minutes'));
        $this->assertEquals(769 * 24 * 60 * 60, $this->diffBetweenDates($date1, $date2, 'seconds'));
    }

    /**
     * @throws \Exception
     */
    public function testIsToday()
    {
        $this->assertTrue($this->isToday(new \DateTime('+1 minutes')));
        $this->assertFalse($this->isToday(new \DateTime('+1 days')));
        $this->assertFalse($this->isToday(new \DateTime('-1 days')));
    }

    /**
     * @throws \Exception
     */
    public function testIsPast()
    {
        $this->assertTrue($this->isPast(new \DateTime('-1 minutes')));
        $this->assertFalse($this->isPast(new \DateTime('+1 minutes')));
    }

    /**
     * @throws \Exception
     */
    public function testIsFuture()
    {
        $this->assertTrue($this->isFuture(new \DateTime('+1 minutes')));
        $this->assertFalse($this->isFuture(new \DateTime('-1 minutes')));
    }

    /**
     * @throws \Exception
     */
    public function testConvertLocalDateTimeToDefaultTimezone()
    {
        $date = $this->dateTime('2021-04-14T13:00:00');
        $updatedDate = $this->convertLocalDateTimeToDefaultTimezone($date, 'Europe/London', '15:26:18');
        $this->assertEquals('2021-04-14 16:26:18', $updatedDate->format('Y-m-d H:i:s'));

        $updatedDate = $this->convertLocalDateTimeToDefaultTimezone($date, 'Europe/London');
        $this->assertEquals('2021-04-14 '.$date->format('H').':'.$date->format('i').':'.$date->format('s'), $updatedDate->format('Y-m-d H:i:s'));
    }

    /**
     * @throws \Exception
     */
    public function testGetSoonestAndFarthestDate()
    {
        $date1 = $this->dateTime('+2 days');
        $date2 = $this->dateTime('+3 days');

        $this->assertEquals($date1, $this->getEarliestDate($date1, $date2));
        $this->assertEquals($date2, $this->getLatestDate($date1, $date2));
    }
}
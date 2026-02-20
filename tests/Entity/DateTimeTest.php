<?php


namespace UbeeDev\LibBundle\Tests\Entity;

use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class DateTimeTest extends AbstractWebTestCase
{

    public function testIsLaterAndIsBefore(): void
    {
        $currentDate = new DateTime('+2 months');

        $this->assertTrue($currentDate->isLater(new DateTime('+1 month')));
        $this->assertFalse($currentDate->isLater(new DateTime('+2 months')));

        $this->assertTrue($currentDate->isBefore(new DateTime('+2 months')));
        $this->assertFalse($currentDate->isBefore(new DateTime('+1 month')));
    }

    public function testConvertToString(): void
    {
        $currentDate = new DateTime('2019-08-16');
        $this->assertEquals('16 août 2019', $currentDate->convertToString());

        $currentDate = new DateTime('2019-08-16');
        $this->assertEquals('vendredi 16 août 2019', $currentDate->convertToString(withDay: true));
    }

    public function testStartDuringGivenDay(): void
    {
        $currentDate = new DateTime('2019-08-16');

        $this->assertTrue($currentDate->startDuringGivenDay(new DateTime('2019-08-16')));
        $this->assertFalse($currentDate->startDuringGivenDay(new DateTime('2019-08-18')));
    }
}

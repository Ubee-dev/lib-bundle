<?php


namespace UbeeDev\LibBundle\Tests\Traits;

use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use UbeeDev\LibBundle\Traits\PhoneNumberTrait;

class PhoneNumberTraitTest extends AbstractWebTestCase
{
    use PhoneNumberTrait;

    public function testFormattedPhoneNumber(): void
    {
        //France
        $this->assertEquals('+33 6 25 26 24 25', $this->getFormattedPhoneNumber(33, '0625262425'));

        //Marroco
        $this->assertEquals('+212 625-262425', $this->getFormattedPhoneNumber(212, '0625262425'));

        $this->assertNull($this->getFormattedPhoneNumber(33, null));
        $this->assertNull($this->getFormattedPhoneNumber(33, ''));
    }
}

<?php

namespace Khalil1608\LibBundle\Tests\Behat;

use Behat\Mink\Driver\Selenium2Driver;

class CustomSelenium2Driver extends Selenium2Driver
{
    public function switchToIFrame(?string $name = null)
    {
        $this->getWebDriverSession()->frame(array('id' => (int)$name));
    }
}
<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\Signer;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class SignerTest extends AbstractWebTestCase
{

    public function testSign()
    {
        $mySecret = 'YOU CANNOT GUESS IT';
        $signer = new Signer();
        $hash = hash('sha256', 'fooobjectif-barreau123456'.$mySecret);
        $this->assertEquals($hash, $signer->sign(['foo', 'objectif-barreau', 123456], $mySecret));
    }
}

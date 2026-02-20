<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\TextParser;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class TextParserTest extends AbstractWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSign()
    {
        $textParser = new TextParser();
        $text = 'My text with \n';

        $this->assertEquals('My text with <br>', $textParser->parse($text));
    }
}

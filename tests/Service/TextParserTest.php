<?php

namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Service\TextParser;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;

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

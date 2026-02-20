<?php

namespace UbeeDev\LibBundle\Tests\Service\Slack;

use UbeeDev\LibBundle\Service\Slack\AbstractSnippet;
use UbeeDev\LibBundle\Service\Slack\TextSnippet;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class TextSnippetTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $textSnippet = new TextSnippet('some-text');
        $this->assertInstanceOf(AbstractSnippet::class, $textSnippet);
        $this->assertEquals('text', $textSnippet->getFileName());
        $this->assertEquals('text', $textSnippet->getSnippetType());
        $this->assertEquals('some-text', $textSnippet->getContent());
    }
}
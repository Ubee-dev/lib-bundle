<?php

namespace UbeeDev\LibBundle\Tests\Service\Slack;

use UbeeDev\LibBundle\Service\Slack\AbstractSnippet;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class JsonSnippetTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $jsonSnippet = new JsonSnippet(['some' => 'data']);
        $this->assertInstanceOf(AbstractSnippet::class, $jsonSnippet);
        $this->assertEquals('data', $jsonSnippet->getFileName());
        $this->assertEquals('json', $jsonSnippet->getSnippetType());
        $this->assertEquals(
            json_encode(['some' => 'data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $jsonSnippet->getContent()
        );
    }
}
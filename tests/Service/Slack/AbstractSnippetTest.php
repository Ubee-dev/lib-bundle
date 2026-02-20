<?php

namespace UbeeDev\LibBundle\Tests\Service\Slack;

use UbeeDev\LibBundle\Service\Slack\AbstractSnippet;
use UbeeDev\LibBundle\Service\Slack\JsonSnippet;
use UbeeDev\LibBundle\Service\Slack\SlackSnippetInterface;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class AbstractSnippetTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $snippet = new class('my content') extends AbstractSnippet
        {
            public function getFileName(): string
            {
                return 'data';
            }

            public function getSnippetType(): ?string
            {
                return 'json';
            }

            public function getContent(): string
            {
                return $this->content;
            }
        };
        $this->assertInstanceOf(SlackSnippetInterface::class, $snippet);
        $this->assertEquals('data', $snippet->getFileName());
        $this->assertEquals('json', $snippet->getSnippetType());
        $this->assertEquals('my content', $snippet->getContent());
        $this->assertEquals('my content', $snippet->getContent());

        $this->assertEquals(
            [
                'content' => 'my content',
                'class' => $snippet::class,
            ],
            $snippet->jsonSerialize()
        );
    }
}
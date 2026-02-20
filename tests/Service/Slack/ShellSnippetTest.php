<?php

namespace UbeeDev\LibBundle\Tests\Service\Slack;

use UbeeDev\LibBundle\Service\Slack\AbstractSnippet;
use UbeeDev\LibBundle\Service\Slack\ShellSnippet;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class ShellSnippetTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $shellSnippet = new ShellSnippet($command = 'cd /var/www && ls -la');
        $this->assertInstanceOf(AbstractSnippet::class, $shellSnippet);
        $this->assertEquals('command', $shellSnippet->getFileName());
        $this->assertEquals('shell', $shellSnippet->getSnippetType());
        $this->assertEquals($command, $shellSnippet->getContent());
    }
}
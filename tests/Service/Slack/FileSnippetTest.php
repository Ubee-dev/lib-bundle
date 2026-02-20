<?php

namespace UbeeDev\LibBundle\Tests\Service\Slack;

use UbeeDev\LibBundle\Service\Slack\AbstractSnippet;
use UbeeDev\LibBundle\Service\Slack\FileSnippet;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class FileSnippetTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $jsFileSnippet = new FileSnippet($filePath = $this->getAsset('my-file.js'));
        $this->assertInstanceOf(AbstractSnippet::class, $jsFileSnippet);
        $this->assertEquals('my-file.js', $jsFileSnippet->getFileName());
        $this->assertEquals('javascript', $jsFileSnippet->getSnippetType());
        $this->assertEquals(file_get_contents($filePath), $jsFileSnippet->getContent());

        $pdfFileSnippet = new FileSnippet($filePath = $this->getAsset('document.pdf'));
        $this->assertInstanceOf(AbstractSnippet::class, $pdfFileSnippet);
        $this->assertEquals('document.pdf', $pdfFileSnippet->getFileName());
        $this->assertNull($pdfFileSnippet->getSnippetType());
        $this->assertEquals(file_get_contents($filePath), $pdfFileSnippet->getContent());
    }
}
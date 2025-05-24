<?php

namespace Khalil1608\LibBundle\Tests\Entity;

use Khalil1608\LibBundle\Entity\AbstractEntity;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Khalil1608\LibBundle\Tests\Helper\DummyMedia;

class MediaTest extends AbstractWebTestCase
{
    public function testProperties(): void
    {
        $media = new DummyMedia();
        $this->assertInstanceOf(AbstractEntity::class, $media);
        $report = $this->validator->report($media);
        $this->assertStringContainsString("filename:\n    Cette valeur ne doit pas être nulle.", $report['message']);
        $this->assertStringContainsString("context:\n    Cette valeur ne doit pas être nulle.", $report['message']);
        $this->assertStringContainsString("contentType:\n    Cette valeur ne doit pas être nulle.", $report['message']);
        $this->assertStringContainsString("contentSize:\n    Cette valeur ne doit pas être nulle.", $report['message']);

        $media->setFilename('SOME_FILE_NAME')
            ->setContext('SOME_CONTEXT')
            ->setContentType('SOME_CONTENT_TYPE')
            ->setContentSize(18);

        $this->assertEquals('SOME_FILE_NAME', $media->getFilename());
        $this->assertEquals('SOME_CONTEXT', $media->getContext());
        $this->assertEquals('SOME_CONTENT_TYPE', $media->getContentType());
        $this->assertEquals(18, $media->getContentSize());
    }
}
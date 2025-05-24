<?php

namespace Khalil1608\LibBundle\Tests\Consumer;

use Khalil1608\LibBundle\Consumer\CompressPdfConsumer;
use Khalil1608\LibBundle\Producer\CompressPdfProducer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;

class CompressPdfConsumerTest extends AbstractConsumerCase
{
    private CompressPdfConsumer $compressPdfConsumer;
    private CompressPdfProducer|MockObject $compressPdfProducerMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->compressPdfProducerMock = $this->getMockedClass(CompressPdfProducer::class);
        $this->fileSystem = new Filesystem();
        $this->initConsumer();
        $this->fileSystem->remove('/tmp/compress-pdf/to-compress.pdf');
    }

    public function testCompressPdf(): void
    {
        $this->createAMPMessage([
            'filePath' => '/tmp/compress-pdf/to-compress.pdf',
        ]);

        $pdfFile = $this->getAsset('to-compress.pdf');
        $this->fileSystem->copy($pdfFile, '/tmp/compress-pdf/to-compress.pdf');
        $currentFileSize = filesize('/tmp/compress-pdf/to-compress.pdf');
        $this->compressPdfConsumer->execute($this->message);
        // the results of filesize are cached. Clean it checking new size
        clearstatcache();
        $this->assertTrue(filesize('/tmp/compress-pdf/to-compress.pdf') < $currentFileSize);
    }

    public function testCompressPdfShouldSendNotificationIfFails(): void
    {
        $this->createAMPMessage($parameters = [
            'filePath' => '/badpath/bad-file-path.pdf',
        ]);

        $this->errorProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(CompressPdfConsumer::class),
                $this->equalTo('sendCompressPdf'),
                $this->equalTo($parameters),
                $this->stringContains('Unable to open the initial device'),
            );

        $this->compressPdfConsumer->execute($this->message);
    }

    private function initConsumer(): void
    {
        $this->compressPdfConsumer = new CompressPdfConsumer(
            $this->errorProducerMock,
            $this->compressPdfProducerMock
        );
    }


}
<?php


namespace Khalil1608\LibBundle\Tests\Service;

use Khalil1608\LibBundle\Service\PdfGenerator;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPUnit\Framework\MockObject\MockObject;

class PdfGeneratorTest extends AbstractWebTestCase
{
    private PdfGenerator $pdfGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initManager();
//        $this->mockBuiltInFunction('Khalil1608\LibBundle\Service', 'uniqid', '123456');
//        $this->mockBuiltInFunction('Khalil1608\LibBundle\Service', 'sha1_file', 'sha1_file_result');
//        $this->mockBuiltInFunction('Khalil1608\LibBundle\Service', 'sha1', 'sha1_result');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testHtmlToPdfContent(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $this->mockDompdfWithHtmlAndOptions($html, expectedContent: 'some-pdf-content');
        $pdfContent = $this->pdfGenerator->htmlToPdfContent($html);
        
        $this->assertEquals(
            'some-pdf-content',
            $pdfContent
        );
    }

    private function mockDompdfWithHtmlAndOptions(string $html, string $expectedContent): MockObject
    {
        $optionsMock = $this->getOptionsMock();
        
        $optionsMock->expects($this->exactly(3))
            ->method('set')
            ->with(...self::withConsecutive(
                ['isHtml5ParserEnabled', true],
                ['isPhpEnabled', true],
                ['isRemoteEnabled', true],
            ))->willReturn($optionsMock);

        uopz_set_mock(Options::class, $optionsMock);
        
        $domPdfMock = $this->getDomPdfMock($optionsMock);
        $domPdfMock->expects($this->once())
            ->method('loadHtml')
            ->with(
                $this->equalTo($html),
            )->willReturn($domPdfMock);

        $domPdfMock->expects($this->once())
            ->method('setPaper')
            ->with(
                $this->equalTo('A4'),
                $this->equalTo('portrait'),
            )->willReturn($domPdfMock);

        $domPdfMock->expects($this->once())
            ->method('render')
            ->willReturn($domPdfMock);
        
        $domPdfMock->expects($this->once())
            ->method('output')
            ->willReturn('some-pdf-content');

        uopz_set_mock(Dompdf::class, $domPdfMock);

        return $domPdfMock;
    }
    
    private function getOptionsMock(): MockObject
    {
        return $this->getMockBuilder(Options::class)
            ->onlyMethods(['set'])
            ->getMock();
    }

    private function getDompdfMock(MockObject $options): MockObject
    {
        return $this->getMockBuilder(Dompdf::class)
            ->onlyMethods(['loadHtml', 'setPaper', 'render', 'output'])
            ->setConstructorArgs([$options])
            ->getMock();
    }

    private function initManager(): void
    {
        $this->pdfGenerator = new PdfGenerator();
    }
}

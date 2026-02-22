<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UbeeDev\LibBundle\Controller\ImageResizeController;
use UbeeDev\LibBundle\Service\ImageResizeService;

final class ImageResizeControllerTest extends TestCase
{
    private ImageResizeController $controller;
    private string $testImagePath;

    protected function setUp(): void
    {
        $this->controller = new ImageResizeController();

        $this->testImagePath = sys_get_temp_dir().'/resize_ctrl_test_'.uniqid().'.webp';
        $imagick = new \Imagick();
        $imagick->newImage(100, 100, new \ImagickPixel('red'));
        $imagick->setImageFormat('webp');
        $imagick->writeImage($this->testImagePath);
        $imagick->clear();
        $imagick->destroy();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
    }

    public function testReturns404WhenOriginalNotFound(): void
    {
        $mock = $this->createMock(ImageResizeService::class);
        $mock->expects($this->once())
            ->method('resize')
            ->with(
                $this->equalTo(400),
                $this->equalTo('game/202602/nonexistent.webp'),
            )
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        ($this->controller)(400, 'game/202602/nonexistent.webp', $mock);
    }

    public function testReturnsResizedWebpImage(): void
    {
        $mock = $this->createMock(ImageResizeService::class);
        $mock->expects($this->once())
            ->method('resize')
            ->with(
                $this->equalTo(400),
                $this->equalTo('game/202602/test.webp'),
            )
            ->willReturn($this->testImagePath);

        $response = ($this->controller)(400, 'game/202602/test.webp', $mock);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=2592000', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
    }

    public function testReturnsJpegContentType(): void
    {
        $jpgPath = sys_get_temp_dir().'/resize_ctrl_test_'.uniqid().'.jpg';
        $imagick = new \Imagick();
        $imagick->newImage(100, 100, new \ImagickPixel('red'));
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage($jpgPath);
        $imagick->destroy();

        $stub = $this->createStub(ImageResizeService::class);
        $stub->method('resize')->willReturn($jpgPath);

        $response = ($this->controller)(400, 'game/202602/test.jpg', $stub);

        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));

        unlink($jpgPath);
    }

    public function testReturnsPngContentType(): void
    {
        $pngPath = sys_get_temp_dir().'/resize_ctrl_test_'.uniqid().'.png';
        $imagick = new \Imagick();
        $imagick->newImage(100, 100, new \ImagickPixel('red'));
        $imagick->setImageFormat('png');
        $imagick->writeImage($pngPath);
        $imagick->destroy();

        $stub = $this->createStub(ImageResizeService::class);
        $stub->method('resize')->willReturn($pngPath);

        $response = ($this->controller)(400, 'game/202602/test.png', $stub);

        $this->assertSame('image/png', $response->headers->get('Content-Type'));

        unlink($pngPath);
    }
}

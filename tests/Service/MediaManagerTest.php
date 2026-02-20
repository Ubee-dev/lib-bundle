<?php


namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;
use UbeeDev\LibBundle\Service\MediaManager;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use UbeeDev\LibBundle\Tests\Helper\Factory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaManagerTest extends AbstractWebTestCase
{
    private MediaManager $mediaManager;
    private string $publicFilePath;
    private string $privateFilePath;
    private string $projectDir;
    private Media $publicMedia;
    private Media $privateMedia;
    private MockObject $emMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initFileSystem();
        $this->mockTime('2023-01-01');
        // use lib-bundle factory and prevent override by admin-bundle factory
        $this->factory = $this->container->get(Factory::class);
        $this->initManager();
        $this->mockBuiltInFunction('UbeeDev\LibBundle\Service', 'uniqid', '123456');
        $this->mockBuiltInFunction('UbeeDev\LibBundle\Service', 'sha1_file', 'sha1_file_result');
        $this->mockBuiltInFunction('UbeeDev\LibBundle\Service', 'sha1', 'sha1_result');

        $this->publicMedia = $this->factory->createMedia(['filename' => 'testpublicfile.txt']);
        $this->privateMedia = $this->factory->createMedia(['filename' => 'testprivatefile.txt', 'private' => true]);
        $this->projectDir = $this->container->get(ParameterBagInterface::class)->get('kernel.project_dir');
        $this->publicFilePath = $this->getPublicPathForMedia($this->publicMedia);
        $this->privateFilePath = $this->getPrivatePathForMedia($this->privateMedia);
    }

    /**
     * @throws Exception
     */
    public function testDeleteMedia(): void
    {
        $publicMediaId = $this->publicMedia->getId();
        $privateMediaId = $this->privateMedia->getId();

        $this->fileSystem->dumpFile($this->publicFilePath, '');
        $this->fileSystem->dumpFile($this->privateFilePath, '');

        $this->mediaManager->delete($this->publicMedia);
        $this->mediaManager->delete($this->privateMedia);

        $this->assertFalse($this->fileSystem->exists($this->publicFilePath));
        $this->assertFalse($this->fileSystem->exists($this->privateFilePath));
        $this->assertNull($this->entityManager->getRepository($this->container->getParameter('mediaClassName'))->find($publicMediaId));
        $this->assertNull($this->entityManager->getRepository($this->container->getParameter('mediaClassName'))->find($privateMediaId));
    }

    /**
     * @throws Exception
     */
    public function testDeleteAsset(): void
    {
        $this->fileSystem->dumpFile($this->publicFilePath, '');
        $this->fileSystem->dumpFile($this->privateFilePath, '');

        $this->mediaManager->deleteAsset($this->publicMedia);
        $this->mediaManager->deleteAsset($this->privateMedia);

        $this->assertFalse($this->fileSystem->exists($this->publicFilePath));
        $this->assertFalse($this->fileSystem->exists($this->privateFilePath));
        $this->assertNotNull($this->entityManager->getRepository($this->container->getParameter('mediaClassName'))->find($this->publicMedia->getId()));
        $this->assertNotNull($this->entityManager->getRepository($this->container->getParameter('mediaClassName'))->find($this->privateMedia->getId()));
    }

    /**
     * @throws Exception
     */
    public function testGetWebPath(): void
    {
        $this->assertEquals('/uploads/tests/' . $this->dateTime()->format('Ym') . '/testpublicfile.txt', $this->mediaManager->getWebPath($this->publicMedia));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot get web path for private media');
        $this->mediaManager->getWebPath($this->privateMedia);
    }

    /**
     * @throws Exception
     */
    public function testGetRelativePath(): void
    {
        $now = $this->dateTime();
        $this->assertEquals($this->projectDir.'/public/uploads/tests/' . $now->format('Ym') . '/testpublicfile.txt', $this->mediaManager->getRelativePath($this->publicMedia));
        $this->assertEquals($this->projectDir.'/private/uploads/tests/' . $now->format('Ym') . '/testprivatefile.txt', $this->mediaManager->getRelativePath($this->privateMedia));
    }

    /**
     * @throws Exception
     */
    public function testUploadPublicMedia(): void
    {
        $now = $this->dateTime();
        $file = $this->createAndGetUploadedFile('document.pdf');
        $fileSize = $file->getSize();

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForFile(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager();

        // upload public media
        $media = $this->mediaManager->upload($file, Factory::UPLOAD_CONTEXT);

        $this->assertMediaIsCreated(
            media: $media,
            expectedFilePath: $this->getPublicPathForMedia($media),
            expectedFileSize: $fileSize,
            expectedContext: Factory::UPLOAD_CONTEXT,
            expectedContentType: 'application/pdf',
            isPrivate: false
        );
    }

    /**
     * @throws Exception
     */
    public function testUploadPrivateMedia(): void
    {
        $now = $this->dateTime();
        $file = $this->createAndGetUploadedFile('document.pdf');
        $fileSize = $file->getSize();

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForFile(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager();

        // upload public media
        $media = $this->mediaManager->upload(
            $file,
            Factory::UPLOAD_CONTEXT,
            private: true
        );

        $this->assertMediaIsCreated(
            media: $media,
            expectedFilePath: $this->getPrivatePathForMedia($media),
            expectedFileSize: $fileSize,
            expectedContext: Factory::UPLOAD_CONTEXT,
            expectedContentType: 'application/pdf',
            isPrivate: true
        );
    }

    /**
     * @throws Exception
     */
    public function testUploadMediaShouldDeleteCreatedFileIfMediaCannotBeFlush(): void
    {
        $file = $this->createAndGetUploadedFile('document.pdf');
        $fileSize = $file->getSize();

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForFile(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager(mockEntityManager: true);
        $this->emMock
            ->method('flush')
            ->willThrowException(new Exception('test exception'));

        $errorCatched = false;
        try {
            $this->mediaManager->upload($file, Factory::UPLOAD_CONTEXT);
        } catch (\Exception) {
            $errorCatched = true;
        }

        $this->assertTrue($errorCatched);
        $this->assertFalse($this->fileSystem->exists('public' . $this->container->getParameter('upload_dir') . '/' . Factory::UPLOAD_CONTEXT . '/' . $this->dateTime()->format('Ym') . '/' .$this->getMockedNameForFile()));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCreatePublicPdfFromHtml(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $fileSize = filesize($this->getAsset('html-to-pdf.pdf'));

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForContent(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager();
        $media = $this->mediaManager->createPdfFromHtml($html, Factory::UPLOAD_CONTEXT);

        $filePath = $this->getPublicPathForMedia($media);

        $this->assertMediaIsCreated(
            media: $media,
            expectedFilePath: $filePath,
            expectedFileSize: $fileSize,
            expectedContext: Factory::UPLOAD_CONTEXT,
            expectedContentType: 'application/pdf',
            isPrivate: false
        );

        $this->assertEquals(
            strlen(file_get_contents($this->getAsset('html-to-pdf.pdf'))),
            strlen(file_get_contents($filePath))
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCreatePrivatePdfFromHtml(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $fileSize = filesize($this->getAsset('html-to-pdf.pdf'));

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForContent(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager();
        $media = $this->mediaManager->createPdfFromHtml($html, Factory::UPLOAD_CONTEXT, private: true);

        $filePath = $this->getPrivatePathForMedia($media);

        $this->assertMediaIsCreated(
            media: $media,
            expectedFilePath: $filePath,
            expectedFileSize: $fileSize,
            expectedContext: Factory::UPLOAD_CONTEXT,
            expectedContentType: 'application/pdf',
            isPrivate: true
        );

        $this->assertEquals(
            strlen(file_get_contents($this->getAsset('html-to-pdf.pdf'))),
            strlen(file_get_contents($filePath))
        );
    }

    /**
     * @throws Exception
     */
    public function testCreatePdfFromHtmlShouldDeleteCreatedFileIfMediaCannotBeFlush(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $fileSize = filesize($this->getAsset('html-to-pdf.pdf'));

        $this->assertValidatorShouldBeCalledWith('Invalid media input', Media::class, [
            'fileName' => $this->getMockedNameForContent(),
            'contentSize' => $fileSize,
            'context' => Factory::UPLOAD_CONTEXT,
            'contentType' => 'application/pdf',
        ]);
        $this->initManager(mockEntityManager: true);
        $this->emMock
            ->method('flush')
            ->willThrowException(new Exception('test exception'));

        $errorCatched = false;
        try {
            $this->mediaManager->createPdfFromHtml($html, Factory::UPLOAD_CONTEXT);
        } catch (\Exception) {
            $errorCatched = true;
        }

        $this->assertTrue($errorCatched);
        $this->assertFalse($this->fileSystem->exists('public' . $this->container->getParameter('upload_dir') . '/' . Factory::UPLOAD_CONTEXT . '/' . $this->dateTime()->format('Ym') . '/' .$this->getMockedNameForContent()));
    }

    private function createAndGetUploadedFile(string $fileName): UploadedFile
    {
        $folder = '/tmp/nsys/tests';
        $filePath = $folder . '/' . $fileName;
        $this->fileSystem->remove($folder . '/' . $fileName);
        $this->fileSystem->copy($this->getAsset($fileName), $filePath);
        $this->fileSystem->chmod($folder, 0777, 0000, true);
        return  # Select the file from the filesystem
            new UploadedFile(
                $filePath,
                $fileName,
                'application/pdf',
                null,
                true
            );
    }

    private function assertMediaIsCreated(
        Media  $media,
        string $expectedFilePath,
        int    $expectedFileSize,
        string $expectedContext,
        string $expectedContentType,
        bool $isPrivate,
    ): void
    {
        $this->assertNotNull($media->getId());
        $this->assertNotNull($media->getFilename());
        $this->assertTrue($this->fileSystem->exists($expectedFilePath));
        $this->assertEquals($expectedContext, $media->getContext());
        $this->assertEquals($expectedFileSize, $media->getContentSize());
        $this->assertEquals($expectedContentType, $media->getContentType());
        $this->assertEquals($isPrivate, $media->isPrivate());
    }

    /**
     * @throws Exception
     */
    private function getPublicPathForMedia(Media $media): string
    {
        return $this->projectDir.'/public' . $this->container->getParameter('upload_dir') . '/' . Factory::UPLOAD_CONTEXT . '/' . $this->dateTime()->format('Ym') . '/' . $media->getFilename();
    }

    /**
     * @throws Exception
     */
    private function getPrivatePathForMedia(Media $media): string
    {
        return $this->projectDir.'/private' . $this->container->getParameter('upload_dir') . '/' . Factory::UPLOAD_CONTEXT . '/' . $this->dateTime()->format('Ym') . '/' . $media->getFilename();
    }

    private function initManager(bool $mockEntityManager = false): void
    {
        if ($mockEntityManager) {
            $em = $this->emMock = $this->getMockedClass(EntityManagerInterface::class);
        } else {
            $em = $this->entityManager;
        }

        $this->mediaManager = new MediaManager(
            $this->container->get(ParameterBagInterface::class),
            $em,
            $this->validatorMock
        );
    }

    private function getMockedNameForFile(): string
    {
        return 'sha1_file_result123456.pdf';
    }

    private function getMockedNameForContent(): string
    {
        return 'sha1_result123456.pdf';
    }
}

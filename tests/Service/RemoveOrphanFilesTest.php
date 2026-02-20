<?php


namespace UbeeDev\LibBundle\Tests\Service;


use UbeeDev\LibBundle\Service\OrphanFilesRemover;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class RemoveOrphanFilesTest extends AbstractWebTestCase
{

    private $orphanFolder = '/tmp/orphanFolder';

    public function setUp(): void
    {
        parent::setUp();
        $this->initFileSystem();
        if(!$this->fileSystem->exists($this->orphanFolder)) {
            $this->fileSystem->mkdir($this->orphanFolder);
            $this->fileSystem->mkdir($this->orphanFolder.'/test');
        } else {
            $this->fileSystem->remove($this->orphanFolder.'/*');
            $this->fileSystem->mkdir($this->orphanFolder.'/test');
        }
    }

    public function testRemoveOrphanFiles()
    {
        $file = $this->orphanFolder.'/NOTOrphanFile.txt';
        $orphanFile = $this->orphanFolder.'/orphanFile.txt';
        $orphanFileInSubFolder = $this->orphanFolder.'/test/other-orphanFile.txt';

        fopen($file, 'w');
        fopen($orphanFile, 'w');
        fopen($orphanFileInSubFolder, 'w');

        $orphanFilesRemover = new OrphanFilesRemover();
        $orphanFilesRemover->removeOrphanFiles(['NOTOrphanFile.txt'], $this->orphanFolder);

        $this->assertTrue($this->fileSystem->exists($file));
        $this->assertFalse($this->fileSystem->exists($orphanFile));
        $this->assertFalse($this->fileSystem->exists($orphanFileInSubFolder));

    }
}

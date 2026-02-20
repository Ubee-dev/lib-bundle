<?php


namespace UbeeDev\LibBundle\Service;


use SplFileInfo;

class OrphanFilesRemover
{
    public function removeOrphanFiles(array $mediaProviderReferences, string $folder): void
    {
        $directory = new \RecursiveDirectoryIterator($folder);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = array();
        $count = 0;
        /** @var SplFileInfo $info */
        foreach ($iterator as $info) {

            if(is_file($info->getPathname()) && !in_array($info->getFilename(), $mediaProviderReferences)) {
                $count++;
                dump($info->getPathname().' deleted');
                unlink($info->getPathname());
            }

        }
        dump($count.' files deleted');
    }
}

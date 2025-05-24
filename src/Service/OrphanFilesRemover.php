<?php


namespace Khalil1608\LibBundle\Service;


use SplFileInfo;

class OrphanFilesRemover
{
    /**
     * @param array $mediaProviderReferences
     * @param string $folder
     */
    public function removeOrphanFiles(array $mediaProviderReferences, string $folder)
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

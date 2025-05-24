<?php


namespace Khalil1608\LibBundle\Traits;


trait ProcessTrait
{
    /**
     * @param $command
     * @return string
     * @throws \Exception
     */
    public function executeCommand($command)
    {
        exec($command, $output);
        return $output;
    }
}
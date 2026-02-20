<?php


namespace UbeeDev\LibBundle\Traits;


trait ProcessTrait
{
    public function executeCommand(string $command): array
    {
        exec($command, $output);
        return $output;
    }
}
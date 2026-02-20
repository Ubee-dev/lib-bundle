<?php

namespace UbeeDev\LibBundle\Service;

use Doctrine\DBAL\Connection;

interface DatabaseDumperInterface
{
    public function dump(Connection $connection, string $outputFile): void;

    public function restore(Connection $connection, string $inputFile): void;
}

<?php

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use UbeeDev\LibBundle\Tests\TestKernel;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(dirname(__DIR__) . '/.env.test');
if (file_exists(dirname(__DIR__) . '/.env.test.local')) {
    $dotenv->overload(dirname(__DIR__) . '/.env.test.local');
}

$kernel = new TestKernel('test', true);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$schemaTool = new SchemaTool($em);
$schemaTool->dropDatabase();
$schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

$kernel->shutdown();

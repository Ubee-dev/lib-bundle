<?php


namespace UbeeDev\LibBundle\Tests\Entity;


use UbeeDev\LibBundle\Entity\PostDeployExecution;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;


class PostDeployExecutionTest extends AbstractWebTestCase
{
    public function testConstructorAndProperties(): void
    {
        $postDeployCommand = new PostDeployExecution();

        $report = $this->validator->report($postDeployCommand);

        $this->assertStringContainsString("name:\n    Cette valeur ne doit pas être vide.", $report['message']);
        $this->assertStringContainsString("executionTime:\n    Cette valeur ne doit pas être nulle.", $report['message']);
        $this->assertStringContainsString("executedAt:\n    Cette valeur ne doit pas être nulle.", $report['message']);

        $postDeployCommand->setName('test')
            ->setExecutionTime(1)
            ->setExecutedAt($executedAt = $this->dateTime('-2 days'));

        $this->assertEquals('test', $postDeployCommand->getName());
        $this->assertEquals(1, $postDeployCommand->getExecutionTime());
        $this->assertEquals($executedAt, $postDeployCommand->getExecutedAt());
    }

    public function testUniqueName(): void
    {
        $name = 'Some name';

        $this->factory->createPostDeployCommand(['name' => $name]);
        $otherPostDeployCommand = $this->factory->buildPostDeployCommand(['name' => $name]);

        $report = $this->validator->report($otherPostDeployCommand);
        $this->assertStringContainsString("name:\n    Cette valeur est déjà utilisée.", $report['message']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->factory->createPostDeployCommand(['name' => $name]);
    }
}

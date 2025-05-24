<?php

namespace Khalil1608\LibBundle\DependencyInjection\Compiler;

use Khalil1608\LibBundle\Traits\StringTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

class PostDeployPass implements CompilerPassInterface
{
//    use StringTrait;

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir'); // Getting project directory
        $postDeployDirectory = $projectDir.'/src/PostDeploy/';

        if (!file_exists($postDeployDirectory)) {
            return;
        }

        $finder = new Finder();

        $files = $finder->files()->in($postDeployDirectory);

        foreach ($files as $file) {
            $className = self::getClassNameWithNamespaceFromFile($file->getRealPath());
            $container->register($className, $className) // Dynamically register service
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true);
        }

    }

    private static function getClassNameWithNamespaceFromFile(string $filePath): ?string
    {
        // Read the file content
        $content = file_get_contents($filePath);

        // Use regular expressions to find the namespace and class name
        if (preg_match('/namespace\s+([^;]+);/i', $content, $namespaceMatches) &&
            preg_match('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/i', $content, $classMatches)) {

            // Combine the namespace and class name
            $namespace = trim($namespaceMatches[1]);
            $className = $classMatches[1];

            if (!empty($namespace)) {
                return $namespace . '\\' . $className;
            }

            return $className;
        }

        return null;
    }
}
<?php

namespace Khalil1608\LibBundle;

use Khalil1608\LibBundle\DependencyInjection\Compiler\PostDeployPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class Khalil1608LibBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('s3_region')->end()
            ->scalarNode('s3_version')->end()
            ->scalarNode('export_dir')->end()
            ->scalarNode('tmp_backup_folder')->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        foreach ($config as $key => $c) {
            $container->parameters()->set($key, $c);
        }
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new PostDeployPass());
    }
}

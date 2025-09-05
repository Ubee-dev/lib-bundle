<?php

namespace Khalil1608\LibBundle\DependencyInjection\Compiler;

use Khalil1608\LibBundle\Service\AntiRobot\AntiRobotVerifierFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AntiRobotVerifierPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AntiRobotVerifierFactory::class)) {
            return;
        }

        $definition = $container->findDefinition(AntiRobotVerifierFactory::class);

        $taggedServices = $container->findTaggedServiceIds('app.anti_robot_verifier');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addVerifier', [new Reference($id)]);
        }
    }
}
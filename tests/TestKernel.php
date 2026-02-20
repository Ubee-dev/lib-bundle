<?php

namespace UbeeDev\LibBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use OldSound\RabbitMqBundle\OldSoundRabbitMqBundle;
use Snc\RedisBundle\SncRedisBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;
use UbeeDev\LibBundle\UbeeDevLibBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DoctrineMigrationsBundle(),
            new OldSoundRabbitMqBundle(),
            new SncRedisBundle(),
            new TwigBundle(),
            new TwigExtraBundle(),
            new MonologBundle(),
            new SecurityBundle(),
            new UbeeDevLibBundle(),
        ];
    }

    protected function build(ContainerBuilder $container): void
    {
        // Remove services whose class is not available (e.g. Behat contexts when Behat is not installed)
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ($container->getDefinitions() as $id => $definition) {
                    if (str_starts_with($id, '.')) {
                        continue;
                    }
                    $class = $definition->getClass() ?? $id;
                    if (!is_string($class) || !str_contains($class, '\\')) {
                        continue;
                    }
                    try {
                        $exists = class_exists($class) || interface_exists($class);
                    } catch (\Throwable $e) {
                        $exists = false;
                    }
                    if (!$exists) {
                        $container->removeDefinition($id);
                    }
                }
            }
        });
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // Load bundle's own config files (includes when@test sections)
        $container->import($this->getProjectDir() . '/config/packages/*.yaml');

        // Framework config (normally provided by the host app)
        $container->extension('framework', [
            'secret' => '%env(APP_SECRET)%',
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'default_locale' => 'fr',
        ]);

        // Doctrine DBAL connection (normally provided by the host app)
        $container->extension('doctrine', [
            'dbal' => [
                'url' => '%env(resolve:DATABASE_URL)%',
            ],
            'orm' => [
                'auto_mapping' => true,
                'mappings' => [
                    'UbeeDevLibBundle' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/src/Entity',
                        'prefix' => 'UbeeDev\LibBundle\Entity',
                    ],
                    'UbeeDevLibBundleTests' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/tests/Helper',
                        'prefix' => 'UbeeDev\LibBundle\Tests\Helper',
                    ],
                ],
            ],
        ]);

        // Security (normally provided by the host app)
        $container->extension('security', [
            'firewalls' => [
                'main' => ['security' => false],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir() . '/config/routing.yml');
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/ubee_dev_lib_bundle/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/ubee_dev_lib_bundle/logs';
    }
}

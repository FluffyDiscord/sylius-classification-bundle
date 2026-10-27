<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class SyliusClassificationExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function getAlias(): string
    {
        return 'sylius_classification';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{menu: array{parent: string, position: int|null}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('sylius_classification.menu.parent', $config['menu']['parent']);
        $container->setParameter('sylius_classification.menu.position', $config['menu']['position']);

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        /** @var array{resources: array<string, mixed>} $config */
        $config = $this->getCurrentConfiguration($container);

        $this->registerResources($this->getAlias(), 'doctrine/orm', $config['resources'], $container);
        $this->prependDoctrineMigrations($container);
        $this->prependApiPlatformMapping($container);
        $this->prependConfigurationFiles($container, 'sylius_twig_hooks', $this->getConfigDirectory() . '/twig_hooks');
    }

    private function getConfigDirectory(): string
    {
        return \dirname(__DIR__, 2) . '/config';
    }

    private function prependApiPlatformMapping(ContainerBuilder $container): void
    {
        $parameterName = 'sylius_classification_api_platform_mapping_path';
        if (! $container->hasParameter($parameterName)) {
            $container->setParameter($parameterName, $this->getConfigDirectory() . '/api_platform');
        }

        $container->prependExtensionConfig('api_platform', [
            'mapping' => [
                'paths' => ['%' . $parameterName . '%'],
            ],
        ]);
    }

    private function prependConfigurationFiles(ContainerBuilder $container, string $extensionName, string $directory): void
    {
        foreach ($this->findConfigurationFiles($directory) as $file) {
            /** @var array<string, mixed>|null $parsed */
            $parsed = Yaml::parseFile($file, Yaml::PARSE_CONSTANT);
            if (! is_array($parsed)) {
                continue;
            }

            $container->addResource(new FileResource($file));

            $extensionConfig = $parsed[$extensionName] ?? null;
            if (is_array($extensionConfig)) {
                /** @var array<string, mixed> $extensionConfig */
                $container->prependExtensionConfig($extensionName, $extensionConfig);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function findConfigurationFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $finder = (new Finder())->files()->in($directory)->name('*.yaml')->sortByName();

        return array_values(array_map(
            static fn(\SplFileInfo $file): string => $file->getPathname(),
            iterator_to_array($finder),
        ));
    }

    protected function getMigrationsNamespace(): string
    {
        return 'FluffyDiscord\SyliusClassificationBundle\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SyliusClassificationBundle/src/Migrations';
    }

    /**
     * @return list<string>
     */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\Bundle\CoreBundle\Migrations'];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getCurrentConfiguration(ContainerBuilder $container): array
    {
        /** @var ConfigurationInterface $configuration */
        $configuration = $this->getConfiguration([], $container);
        $configs = $container->getExtensionConfig($this->getAlias());

        return $this->processConfiguration($configuration, $configs);
    }
}

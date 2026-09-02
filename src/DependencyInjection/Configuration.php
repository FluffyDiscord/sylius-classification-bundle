<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\DependencyInjection;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationTranslation;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueTranslation;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationTranslationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueTranslationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueType;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepository;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepository;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepository;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\Factory\Factory;
use Sylius\Component\Resource\Factory\TranslatableFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sylius_classification');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('menu')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('parent')->defaultValue($this->getDefaultMenuParent())->cannotBeEmpty()->end()
            ->integerNode('position')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
        ;

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function getDefaultMenuParent(): string
    {
        return 'catalog';
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
            ->arrayNode('resources')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('classification')
            ->addDefaultsIfNotSet()
            ->children()
            ->variableNode('options')->end()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue(Classification::class)->cannotBeEmpty()->end()
            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
            ->scalarNode('repository')->defaultValue(ClassificationRepository::class)->cannotBeEmpty()->end()
            ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->cannotBeEmpty()->end()
            ->scalarNode('form')->defaultValue(ClassificationType::class)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->arrayNode('translation')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue(ClassificationTranslation::class)->cannotBeEmpty()->end()
            ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
            ->scalarNode('form')->defaultValue(ClassificationTranslationType::class)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('classification_value')
            ->addDefaultsIfNotSet()
            ->children()
            ->variableNode('options')->end()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue(ClassificationValue::class)->cannotBeEmpty()->end()
            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
            ->scalarNode('repository')->defaultValue(ClassificationValueRepository::class)->cannotBeEmpty()->end()
            ->scalarNode('factory')->defaultValue(TranslatableFactory::class)->cannotBeEmpty()->end()
            ->scalarNode('form')->defaultValue(ClassificationValueType::class)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->arrayNode('translation')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue(ClassificationValueTranslation::class)->cannotBeEmpty()->end()
            ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
            ->scalarNode('form')->defaultValue(ClassificationValueTranslationType::class)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('product_variant_classification_value')
            ->addDefaultsIfNotSet()
            ->children()
            ->variableNode('options')->end()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue(ProductVariantClassificationValue::class)->cannotBeEmpty()->end()
            ->scalarNode('controller')->defaultValue(ResourceController::class)->cannotBeEmpty()->end()
            ->scalarNode('repository')->defaultValue(ProductVariantClassificationValueRepository::class)->cannotBeEmpty()->end()
            ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }
}

<?php

declare(strict_types=1);

use FluffyDiscord\SyliusClassificationBundle\Doctrine\ORM\ClassificationValuesMappingSubscriber;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationTranslationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueAutocompleteType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueTranslationType;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueType;
use FluffyDiscord\SyliusClassificationBundle\Grid\ClassificationGrid;
use FluffyDiscord\SyliusClassificationBundle\Grid\ClassificationValueGrid;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationValueExpressionBuilder;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ProductClassificationFilter;
use FluffyDiscord\SyliusClassificationBundle\Menu\AdminMenuListener;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolver;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolverInterface;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationValueCodeDeriver;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationValueCodeDeriverInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProvider;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\RenderableClassificationProvider;
use FluffyDiscord\SyliusClassificationBundle\Provider\RenderableClassificationProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\State\AssignClassificationValueProcessor;
use FluffyDiscord\SyliusClassificationBundle\State\ClassificationUniqueCodeProcessor;
use FluffyDiscord\SyliusClassificationBundle\State\VariantClassificationValuesProvider;
use FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin\ClassificationFormComponent;
use FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin\ClassificationValueFormComponent;
use FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin\VariantClassificationsComponent;
use FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin\VariantClassificationValueRowComponent;
use FluffyDiscord\SyliusClassificationBundle\Validator\SingleSelectCardinalityValidator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire(false)
        ->autoconfigure(true)
    ;

    $services->alias(ClassificationRepositoryInterface::class, 'sylius_classification.repository.classification');
    $services->alias(ClassificationValueRepositoryInterface::class, 'sylius_classification.repository.classification_value');
    $services->alias(
        ProductVariantClassificationValueRepositoryInterface::class,
        'sylius_classification.repository.product_variant_classification_value',
    );

    $services
        ->set('sylius_classification.product.label_resolver', ClassificationLabelResolver::class)
        ->args([service('request_stack'), param('kernel.default_locale')])
    ;
    $services->alias(ClassificationLabelResolverInterface::class, 'sylius_classification.product.label_resolver');

    $services
        ->set('sylius_classification.product.code_deriver', ClassificationValueCodeDeriver::class)
        ->args([service(ClassificationValueRepositoryInterface::class), param('kernel.default_locale')])
    ;
    $services->alias(ClassificationValueCodeDeriverInterface::class, 'sylius_classification.product.code_deriver');

    $services
        ->set('sylius_classification.provider.assigned_classification_value', AssignedClassificationValueProvider::class)
        ->args([service(ProductVariantClassificationValueRepositoryInterface::class)])
    ;
    $services->alias(
        AssignedClassificationValueProviderInterface::class,
        'sylius_classification.provider.assigned_classification_value',
    );

    $services
        ->set('sylius_classification.provider.renderable_classification', RenderableClassificationProvider::class)
        ->args([service(ClassificationRepositoryInterface::class), service(ClassificationLabelResolverInterface::class)])
        ->tag('kernel.reset', ['method' => 'reset'])
    ;
    $services->alias(
        RenderableClassificationProviderInterface::class,
        'sylius_classification.provider.renderable_classification',
    );

    $services
        ->set('sylius_classification.form.type.classification', ClassificationType::class)
        ->args([param('sylius_classification.model.classification.class')])
        ->tag('form.type')
    ;

    $services
        ->set('sylius_classification.form.type.classification_translation', ClassificationTranslationType::class)
        ->args([param('sylius_classification.model.classification_translation.class')])
        ->tag('form.type')
    ;

    $services
        ->set('sylius_classification.form.type.classification_value', ClassificationValueType::class)
        ->args([
            service(ClassificationValueCodeDeriverInterface::class),
            service(ClassificationValueRepositoryInterface::class),
            param('sylius_classification.model.classification_value.class'),
            param('sylius_classification.model.classification.class'),
        ])
        ->tag('form.type')
    ;

    $services
        ->set('sylius_classification.form.type.classification_value_translation', ClassificationValueTranslationType::class)
        ->args([param('sylius_classification.model.classification_value_translation.class')])
        ->tag('form.type')
    ;

    $services
        ->set('sylius_classification.form.type.classification_value_autocomplete', ClassificationValueAutocompleteType::class)
        ->args([param('sylius_classification.model.classification_value.class')])
        ->tag('form.type')
    ;

    $services
        ->set('sylius_classification.validator.single_select_cardinality', SingleSelectCardinalityValidator::class)
        ->args([service(AssignedClassificationValueProviderInterface::class)])
        ->tag('validator.constraint_validator')
    ;

    $services
        ->set('sylius_classification.grid.filter.expression_builder', ClassificationValueExpressionBuilder::class)
        ->args([param('sylius_classification.model.product_variant_classification_value.class')])
        ->public()
    ;
    $services->alias(ClassificationValueExpressionBuilder::class, 'sylius_classification.grid.filter.expression_builder');

    $services
        ->set('sylius_classification.grid.filter.classification_value', ProductClassificationFilter::class)
        ->args([service(ClassificationValueExpressionBuilder::class)])
    ;

    $services
        ->set('sylius_classification.grid.admin_classification', ClassificationGrid::class)
        ->args([param('sylius_classification.model.classification.class')])
    ;

    $services
        ->set('sylius_classification.grid.admin_classification_value', ClassificationValueGrid::class)
        ->args([
            param('sylius_classification.model.classification_value.class'),
            param('sylius_classification.model.classification.class'),
        ])
    ;

    $services
        ->set('sylius_classification.doctrine.classification_values_mapping_subscriber', ClassificationValuesMappingSubscriber::class)
        ->args([
            param('sylius_classification.model.product_variant_classification_value.class'),
            param('sylius.model.product_variant.class'),
        ])
    ;

    $services
        ->set('sylius_classification.menu.admin_menu_listener', AdminMenuListener::class)
        ->args([param('sylius_classification.menu.parent'), param('sylius_classification.menu.position')])
    ;

    $services
        ->set('sylius_classification.twig.component.admin.classification_form', ClassificationFormComponent::class)
        ->args([
            service('sylius_classification.repository.classification'),
            service('form.factory'),
            param('sylius_classification.model.classification.class'),
        ])
    ;

    $services
        ->set('sylius_classification.twig.component.admin.classification_value_form', ClassificationValueFormComponent::class)
        ->args([
            service('sylius_classification.repository.classification_value'),
            service('form.factory'),
            param('sylius_classification.model.classification_value.class'),
        ])
    ;

    $services
        ->set('sylius_classification.twig.component.admin.variant_classifications', VariantClassificationsComponent::class)
        ->args([
            service('sylius.repository.product_variant'),
            service(RenderableClassificationProviderInterface::class),
            service(AssignedClassificationValueProviderInterface::class),
        ])
    ;

    $services
        ->set('sylius_classification.twig.component.admin.variant_classification_row', VariantClassificationValueRowComponent::class)
        ->args([
            service('sylius.repository.product_variant'),
            service(ClassificationRepositoryInterface::class),
            service(ClassificationValueRepositoryInterface::class),
            service(ProductVariantClassificationValueRepositoryInterface::class),
            service(AssignedClassificationValueProviderInterface::class),
            service(ClassificationLabelResolverInterface::class),
            service('doctrine.orm.entity_manager'),
            param('sylius_classification.model.product_variant_classification_value.class'),
        ])
    ;

    $services
        ->set('sylius_classification.state.assign_classification_value', AssignClassificationValueProcessor::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service(ProductVariantClassificationValueRepositoryInterface::class),
            service('api_platform.doctrine.orm.state.persist_processor'),
        ])
    ;
    $services->alias(AssignClassificationValueProcessor::class, 'sylius_classification.state.assign_classification_value');

    $services
        ->set('sylius_classification.state.unique_code', ClassificationUniqueCodeProcessor::class)
        ->args([
            service('api_platform.doctrine.orm.state.persist_processor'),
            service('translator'),
            param('sylius_classification.model.classification.class'),
        ])
    ;
    $services->alias(ClassificationUniqueCodeProcessor::class, 'sylius_classification.state.unique_code');

    $services
        ->set('sylius_classification.state.variant_classification_values', VariantClassificationValuesProvider::class)
        ->args([
            service(ProductVariantClassificationValueRepositoryInterface::class),
            service('sylius.repository.product_variant'),
        ])
    ;
    $services->alias(VariantClassificationValuesProvider::class, 'sylius_classification.state.variant_classification_values');
};

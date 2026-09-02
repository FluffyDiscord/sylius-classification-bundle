<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaValidator;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationValueExpressionBuilder;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ProductClassificationFilter;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\RenderableClassificationProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Tests\Application\Entity\ProductVariant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ContainerAndMappingTest extends KernelTestCase
{
    public function testPluginServicesAreWired(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertTrue($container->has(ClassificationRepositoryInterface::class));
        self::assertTrue($container->has(ClassificationValueRepositoryInterface::class));
        self::assertTrue($container->has(ProductVariantClassificationValueRepositoryInterface::class));
        self::assertTrue($container->has(AssignedClassificationValueProviderInterface::class));
        self::assertTrue($container->has(RenderableClassificationProviderInterface::class));
        self::assertTrue($container->has(ClassificationValueExpressionBuilder::class));
    }

    public function testGridFilterTypeIsRegistered(): void
    {
        self::bootKernel();

        self::assertTrue(self::getContainer()->has('sylius_classification.grid.filter.classification_value'));
        self::assertInstanceOf(
            ProductClassificationFilter::class,
            self::getContainer()->get('sylius_classification.grid.filter.classification_value'),
        );
    }

    public function testDoctrineMappingIsValid(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        $validator = new SchemaValidator($entityManager);
        $errors = $validator->validateMapping();

        self::assertSame([], $errors, print_r($errors, true));
    }

    public function testRuntimeInverseMappingIsInstalledOnAwareVariant(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        /** @var ClassMetadata<object> $variantMetadata */
        $variantMetadata = $entityManager->getClassMetadata(ProductVariant::class);

        self::assertTrue($variantMetadata->hasAssociation('classificationValues'));

        $association = $variantMetadata->getAssociationMapping('classificationValues');
        self::assertTrue($association->isOneToMany());
        self::assertSame('productVariant', $association->mappedBy);

        $assignmentMetadata = $entityManager->getClassMetadata($association->targetEntity);
        $owningMapping = $assignmentMetadata->getAssociationMapping('productVariant');
        self::assertSame('classificationValues', $owningMapping->inversedBy);
    }
}

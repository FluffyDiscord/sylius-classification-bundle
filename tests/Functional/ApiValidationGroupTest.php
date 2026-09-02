<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Functional;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiValidationGroupTest extends KernelTestCase
{
    /**
     * Every write operation must validate with the plugin's `sylius` group — with the default
     * `Default` group none of the plugin's constraints (single-select, NotNull, code Regex) fire.
     *
     * @return iterable<string, array{class-string}>
     */
    public static function writeResourceProvider(): iterable
    {
        yield 'classification' => [Classification::class];
        yield 'classification value' => [ClassificationValue::class];
        yield 'variant assignment' => [ProductVariantClassificationValue::class];
    }

    /**
     * @param class-string $resourceClass
     */
    #[DataProvider('writeResourceProvider')]
    public function testWriteOperationsValidateWithSyliusGroup(string $resourceClass): void
    {
        self::bootKernel();

        /** @var ResourceMetadataCollectionFactoryInterface $factory */
        $factory = self::getContainer()->get('api_platform.metadata.resource.metadata_collection_factory');
        $metadataCollection = $factory->create($resourceClass);

        $assertedAtLeastOneWriteOperation = false;

        foreach ($metadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() ?? [] as $operation) {
                if (! $operation instanceof HttpOperation) {
                    continue;
                }

                $method = $operation->getMethod();
                $isWrite = in_array($method, [HttpOperation::METHOD_POST, HttpOperation::METHOD_PUT, HttpOperation::METHOD_PATCH], true);
                if (! $isWrite) {
                    continue;
                }

                $validationGroups = $operation->getValidationContext()['groups'] ?? [];

                self::assertContains('sylius', $validationGroups, sprintf('%s %s must validate with the sylius group', $method, (string) $operation->getUriTemplate()));
                $assertedAtLeastOneWriteOperation = true;
            }
        }

        self::assertTrue($assertedAtLeastOneWriteOperation, 'expected at least one write operation');
    }
}

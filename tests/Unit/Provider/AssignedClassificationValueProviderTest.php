<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Provider;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareTrait;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProvider;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class AssignedClassificationValueProviderTest extends TestCase
{
    public function testReadsInMemoryCollectionWithoutQueryingWhenVariantIsAware(): void
    {
        $variant = $this->createAwareVariant();
        $variant->addClassificationValueAssignment($this->createAssignment('color', 'color_red'));

        $repository = $this->createStub(ProductVariantClassificationValueRepositoryInterface::class);
        $repository->method('findByVariant')->willThrowException(new \LogicException('repository must not be queried'));

        $provider = new AssignedClassificationValueProvider($repository);

        $values = $provider->getValues($variant, 'color');

        self::assertCount(1, $values);
        self::assertSame('color_red', $values[0]->getCode());
    }

    public function testQueriesRepositoryWhenVariantIsNotAware(): void
    {
        $variant = new ProductVariant();
        $assignment = $this->createAssignment('color', 'color_red');

        $repository = $this->createStub(ProductVariantClassificationValueRepositoryInterface::class);
        $repository->method('findByVariant')->willReturn([$assignment]);

        $provider = new AssignedClassificationValueProvider($repository);

        $assignments = $provider->getAssignments($variant, 'color');

        self::assertSame([$assignment], $assignments);
    }

    private function createAwareVariant(): ProductVariantInterface&ClassificationValuesAwareInterface
    {
        return new class extends ProductVariant implements ClassificationValuesAwareInterface {
            use ClassificationValuesAwareTrait;

            public function __construct()
            {
                parent::__construct();
                $this->initializeClassificationValues();
            }
        };
    }

    private function createAssignment(string $classificationCode, string $valueCode): ProductVariantClassificationValue
    {
        $classification = new Classification();
        $classification->setCode($classificationCode);

        $value = new ClassificationValue();
        $value->setClassification($classification);
        $value->setCode($valueCode);

        $assignment = new ProductVariantClassificationValue();
        $assignment->setClassificationValue($value);

        return $assignment;
    }
}

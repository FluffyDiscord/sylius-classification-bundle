<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Entity;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareTrait;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariant;

final class ClassificationValuesAwareTraitTest extends TestCase
{
    public function testAddSetsOwningSideAndIsIdempotent(): void
    {
        $variant = $this->createVariant();
        $assignment = $this->createAssignment('color', 'color_red');

        $variant->addClassificationValueAssignment($assignment);
        $variant->addClassificationValueAssignment($assignment);

        self::assertCount(1, $variant->getClassificationValueAssignments());
        self::assertSame($variant, $assignment->getProductVariant());
    }

    public function testGetClassificationValuesFiltersByAxis(): void
    {
        $variant = $this->createVariant();
        $variant->addClassificationValueAssignment($this->createAssignment('color', 'color_red'));
        $variant->addClassificationValueAssignment($this->createAssignment('size', 'size_l'));

        $colors = $variant->getClassificationValues('color');

        self::assertCount(1, $colors);
        self::assertSame('color_red', $colors[0]->getCode());
    }

    public function testGetClassificationValuesEmptyForUnknownCode(): void
    {
        $variant = $this->createVariant();
        $variant->addClassificationValueAssignment($this->createAssignment('color', 'color_red'));

        self::assertSame([], $variant->getClassificationValues('unknown'));
    }

    public function testHasClassificationValue(): void
    {
        $variant = $this->createVariant();
        $variant->addClassificationValueAssignment($this->createAssignment('color', 'color_red'));

        self::assertTrue($variant->hasClassificationValue('color_red'));
        self::assertFalse($variant->hasClassificationValue('color_blue'));
    }

    private function createVariant(): ProductVariant&ClassificationValuesAwareInterface
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

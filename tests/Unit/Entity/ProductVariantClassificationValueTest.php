<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Entity;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use PHPUnit\Framework\TestCase;

final class ProductVariantClassificationValueTest extends TestCase
{
    public function testSettingValueDenormalizesClassification(): void
    {
        $classification = new Classification();
        $classification->setCode('color');

        $value = new ClassificationValue();
        $value->setClassification($classification);

        $assignment = new ProductVariantClassificationValue();
        $assignment->setClassificationValue($value);

        self::assertSame($classification, $assignment->getClassification());
    }

    public function testClassificationIsNullWhenValueNull(): void
    {
        $assignment = new ProductVariantClassificationValue();
        $assignment->setClassificationValue(null);

        self::assertNull($assignment->getClassification());
    }
}

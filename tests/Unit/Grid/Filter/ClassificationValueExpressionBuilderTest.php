<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Grid\Filter;

use Doctrine\ORM\QueryBuilder;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationFilterRoot;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationValueExpressionBuilder;
use PHPUnit\Framework\TestCase;

final class ClassificationValueExpressionBuilderTest extends TestCase
{
    public function testProductRootedExpressionWithEnabledFilter(): void
    {
        $expression = $this->build('color', ['color_red', 'color_blue'], ClassificationFilterRoot::Product, true);

        self::assertStringContainsString('EXISTS', $expression);
        self::assertStringContainsString(ProductVariantClassificationValue::class, $expression);
        self::assertStringContainsString('.productVariant', $expression);
        self::assertStringContainsString('.product = root', $expression);
        self::assertStringContainsString('.enabled = true', $expression);
        self::assertStringContainsString('.code IN (:', $expression);
    }

    public function testVariantRootedExpressionWithoutEnabledFilter(): void
    {
        $expression = $this->build('color', ['color_red'], ClassificationFilterRoot::ProductVariant, false);

        self::assertStringNotContainsString('.enabled = true', $expression);
        self::assertMatchesRegularExpression('/classificationVariant_[0-9a-f]+ = root/', $expression);
    }

    public function testDistinctCodesProduceDistinctSuffixes(): void
    {
        $first = $this->build('a-b', ['x'], ClassificationFilterRoot::Product, true);
        $second = $this->build('a.b', ['x'], ClassificationFilterRoot::Product, true);

        self::assertNotSame(
            $this->extractSuffix($first),
            $this->extractSuffix($second),
        );
    }

    /**
     * @param list<string> $valueCodes
     */
    private function build(string $classificationCode, array $valueCodes, ClassificationFilterRoot $root, bool $enabledOnly): string
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['root']);
        $queryBuilder->method('setParameter')->willReturnSelf();

        $builder = new ClassificationValueExpressionBuilder(ProductVariantClassificationValue::class);

        return $builder->build($queryBuilder, $classificationCode, $valueCodes, $root, $enabledOnly);
    }

    private function extractSuffix(string $expression): string
    {
        self::assertMatchesRegularExpression('/classificationAssignment_([0-9a-f]+)/', $expression);
        preg_match('/classificationAssignment_([0-9a-f]+)/', $expression, $matches);

        return $matches[1];
    }
}

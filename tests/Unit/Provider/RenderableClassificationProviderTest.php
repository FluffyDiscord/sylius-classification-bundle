<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Provider;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolverInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\RenderableClassificationProvider;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class RenderableClassificationProviderTest extends TestCase
{
    public function testMemoizesAcrossCallsAndRefetchesAfterReset(): void
    {
        $classification = new Classification();
        $classification->setCode('color');
        $classification->setMultiple(true);
        $classification->setPosition(3);

        $repository = $this->createMock(ClassificationRepositoryInterface::class);
        $repository->expects(self::exactly(2))
            ->method('findWithValuesAndTranslations')
            ->willReturn([$classification]);

        $labelResolver = $this->createStub(ClassificationLabelResolverInterface::class);
        $labelResolver->method('getLabel')->willReturn('Color');

        $provider = new RenderableClassificationProvider($repository, $labelResolver);

        $first = $provider->getClassifications();
        $second = $provider->getClassifications();

        self::assertSame($first, $second);
        self::assertCount(1, $first);
        self::assertSame('color', $first[0]->code);
        self::assertSame('Color', $first[0]->label);
        self::assertTrue($first[0]->multiple);
        self::assertSame(3, $first[0]->position);

        $provider->reset();
        $provider->getClassifications();
    }

    public function testSkipsClassificationsWithBlankCode(): void
    {
        $classification = new Classification();

        $repository = $this->createStub(ClassificationRepositoryInterface::class);
        $repository->method('findWithValuesAndTranslations')->willReturn([$classification]);

        $labelResolver = $this->createStub(ClassificationLabelResolverInterface::class);
        $labelResolver->method('getLabel')->willReturn('');

        $provider = new RenderableClassificationProvider($repository, $labelResolver);

        self::assertSame([], $provider->getClassifications());
    }
}

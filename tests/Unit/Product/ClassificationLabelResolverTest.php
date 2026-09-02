<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ClassificationLabelResolverTest extends TestCase
{
    public function testReturnsAnyNameWhenRequestAndDefaultLocaleMissing(): void
    {
        $classification = new Classification();
        $classification->setCode('size');
        $classification->setCurrentLocale('de');
        $classification->setFallbackLocale('de');
        $classification->setName('Größe');

        $resolver = new ClassificationLabelResolver($this->createRequestStack('en'), 'en');

        self::assertSame('Größe', $resolver->getLabel($classification));
    }

    public function testDoesNotAttachEmptyTranslationForMissingLocale(): void
    {
        $classification = new Classification();
        $classification->setCode('size');
        $classification->setCurrentLocale('de');
        $classification->setFallbackLocale('de');
        $classification->setName('Größe');

        $resolver = new ClassificationLabelResolver($this->createRequestStack('en'), 'en');
        $resolver->getLabel($classification);

        self::assertCount(1, $classification->getTranslations());
    }

    public function testFallsBackToCodeWhenAllNamesBlank(): void
    {
        $classification = new Classification();
        $classification->setCode('size');
        $classification->setCurrentLocale('en');
        $classification->setFallbackLocale('en');
        $classification->setName('   ');

        $resolver = new ClassificationLabelResolver($this->createRequestStack('en'), 'en');

        self::assertSame('size', $resolver->getLabel($classification));
    }

    public function testPrefersRequestLocaleOverDefault(): void
    {
        $classification = new Classification();
        $classification->setCode('size');
        $classification->setCurrentLocale('en');
        $classification->setFallbackLocale('en');
        $classification->setName('Size');
        $classification->setCurrentLocale('cs');
        $classification->setName('Velikost');

        $resolver = new ClassificationLabelResolver($this->createRequestStack('cs'), 'en');

        self::assertSame('Velikost', $resolver->getLabel($classification));
    }

    private function createRequestStack(string $locale): RequestStack
    {
        $request = new Request();
        $request->setLocale($locale);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}

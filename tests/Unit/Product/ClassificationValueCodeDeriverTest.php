<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationValueCodeDeriver;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ClassificationValueCodeDeriverTest extends TestCase
{
    public function testDerivesSluggedCodeFromDefaultLocaleName(): void
    {
        $deriver = $this->createDeriver();
        $value = $this->createValue('size', [
            'en' => 'Velké číslo',
        ]);

        $deriver->derive($value);

        self::assertSame('size_velke-cislo', $value->getCode());
    }

    public function testTruncatesToNinetySixCharacters(): void
    {
        $deriver = $this->createDeriver();
        $value = $this->createValue('axis', [
            'en' => str_repeat('a', 200),
        ]);

        $deriver->derive($value);

        self::assertNotNull($value->getCode());
        self::assertSame(96, mb_strlen((string) $value->getCode()));
    }

    public function testDoesNotOverwriteExistingCode(): void
    {
        $deriver = $this->createDeriver();
        $value = $this->createValue('size', [
            'en' => 'Large',
        ]);
        $value->setCode('already_set');

        $deriver->derive($value);

        self::assertSame('already_set', $value->getCode());
    }

    public function testDoesNothingWhenClassificationMissing(): void
    {
        $deriver = $this->createDeriver();
        $value = new ClassificationValue();
        $value->setCurrentLocale('en');
        $value->setFallbackLocale('en');
        $value->setName('Large');

        $deriver->derive($value);

        self::assertNull($value->getCode());
    }

    public function testFallsBackToFirstTranslationWhenDefaultLocaleMissing(): void
    {
        $deriver = $this->createDeriver();
        $value = $this->createValue('size', [
            'de' => 'Gross',
        ]);

        $deriver->derive($value);

        self::assertSame('size_gross', $value->getCode());
    }

    public function testDoesNothingWhenAllNamesBlank(): void
    {
        $deriver = $this->createDeriver();
        $value = $this->createValue('size', [
            'en' => '   ',
        ]);

        $deriver->derive($value);

        self::assertNull($value->getCode());
    }

    public function testAppendsSuffixOnCollision(): void
    {
        $deriver = $this->createDeriver(takenCodes: ['size_modra']);
        $value = $this->createValue('size', [
            'en' => 'Modrá',
        ]);

        $deriver->derive($value);

        self::assertSame('size_modra_2', $value->getCode());
    }

    /**
     * @param array<string, string> $translations
     */
    private function createValue(string $classificationCode, array $translations): ClassificationValue
    {
        $classification = new Classification();
        $classification->setCode($classificationCode);

        $value = new ClassificationValue();
        $value->setClassification($classification);

        foreach ($translations as $locale => $name) {
            $value->setCurrentLocale($locale);
            $value->setFallbackLocale($locale);
            $value->setName($name);
        }

        return $value;
    }

    /**
     * @param list<string> $takenCodes
     */
    private function createDeriver(array $takenCodes = []): ClassificationValueCodeDeriver
    {
        $repository = $this->createStub(ClassificationValueRepositoryInterface::class);
        $repository->method('findOneByCode')->willReturnCallback(
            function (string $code) use ($takenCodes): ?ClassificationValueInterface {
                if (! in_array($code, $takenCodes, true)) {
                    return null;
                }

                $taken = new ClassificationValue();
                $taken->setCode($code);

                return $taken;
            },
        );

        return new ClassificationValueCodeDeriver($repository, 'en');
    }
}

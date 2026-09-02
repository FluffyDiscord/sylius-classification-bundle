<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Form\Type;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueAutocompleteType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClassificationValueAutocompleteTypeTest extends TestCase
{
    public function testRequiresClassificationCode(): void
    {
        $resolver = $this->createResolver();

        $this->expectException(MissingOptionsException::class);

        $resolver->resolve([
            'extra_options' => [],
        ]);
    }

    public function testRejectsNonStringClassificationCode(): void
    {
        $resolver = $this->createResolver();

        $this->expectException(MissingOptionsException::class);

        $resolver->resolve([
            'extra_options' => [
                'classification_code' => 123,
            ],
        ]);
    }

    public function testAcceptsStringClassificationCode(): void
    {
        $resolver = $this->createResolver();

        $resolved = $resolver->resolve([
            'extra_options' => [
                'classification_code' => 'color',
            ],
        ]);

        self::assertSame(ClassificationValue::class, $resolved['class']);
        self::assertIsCallable($resolved['filter_query']);
    }

    private function createResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        (new ClassificationValueAutocompleteType(ClassificationValue::class))->configureOptions($resolver);

        return $resolver;
    }
}

<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\DependencyInjection;

use FluffyDiscord\SyliusClassificationBundle\DependencyInjection\Configuration;
use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationTranslation;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueTranslation;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertSame('catalog', $config['menu']['parent']);
        self::assertNull($config['menu']['position']);
        self::assertSame(Classification::class, $config['resources']['classification']['classes']['model']);
        self::assertSame(ClassificationTranslation::class, $config['resources']['classification']['translation']['classes']['model']);
        self::assertSame(ClassificationValue::class, $config['resources']['classification_value']['classes']['model']);
        self::assertSame(ClassificationValueTranslation::class, $config['resources']['classification_value']['translation']['classes']['model']);
        self::assertSame(ProductVariantClassificationValue::class, $config['resources']['product_variant_classification_value']['classes']['model']);
    }

    public function testModelOverride(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [
            [
                'resources' => [
                    'classification' => [
                        'classes' => [
                            'model' => 'App\Entity\Classification',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame('App\Entity\Classification', $config['resources']['classification']['classes']['model']);
    }

    public function testRejectsUnknownKey(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'unknown_key' => true,
        ]]);
    }
}

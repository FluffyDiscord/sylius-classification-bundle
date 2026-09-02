<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<ProductVariantClassificationValueInterface>
 */
interface ProductVariantClassificationValueRepositoryInterface extends RepositoryInterface
{
    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariantAndClassification(
        ProductVariantInterface $productVariant,
        ClassificationInterface $classification,
    ): array;

    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariantForSerialization(ProductVariantInterface $productVariant): array;

    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariant(ProductVariantInterface $productVariant, ?string $classificationCode = null): array;
}

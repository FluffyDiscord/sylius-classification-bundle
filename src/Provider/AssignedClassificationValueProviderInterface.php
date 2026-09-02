<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Provider;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface AssignedClassificationValueProviderInterface
{
    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function getAssignments(ProductVariantInterface $productVariant, ?string $classificationCode = null): array;

    /**
     * @return list<ClassificationValueInterface>
     */
    public function getValues(ProductVariantInterface $productVariant, string $classificationCode): array;
}

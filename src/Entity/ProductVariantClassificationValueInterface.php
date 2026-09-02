<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

interface ProductVariantClassificationValueInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getProductVariant(): ?ProductVariantInterface;

    public function setProductVariant(?ProductVariantInterface $productVariant): void;

    public function getClassificationValue(): ?ClassificationValueInterface;

    public function setClassificationValue(?ClassificationValueInterface $classificationValue): void;

    public function getClassification(): ?ClassificationInterface;

    public function getPosition(): int;

    public function setPosition(int $position): void;
}

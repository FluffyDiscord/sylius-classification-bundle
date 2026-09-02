<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Sylius\Component\Core\Model\ProductVariantInterface;

class ProductVariantClassificationValue implements ProductVariantClassificationValueInterface
{
    protected ?int $id = null;

    protected ?ProductVariantInterface $productVariant = null;

    protected ?ClassificationValueInterface $classificationValue = null;

    protected ?ClassificationInterface $classification = null;

    protected int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductVariant(): ?ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariantInterface $productVariant): void
    {
        $this->productVariant = $productVariant;
    }

    public function getClassificationValue(): ?ClassificationValueInterface
    {
        return $this->classificationValue;
    }

    public function setClassificationValue(?ClassificationValueInterface $classificationValue): void
    {
        $this->classificationValue = $classificationValue;
        $this->classification = $classificationValue?->getClassification();
    }

    public function getClassification(): ?ClassificationInterface
    {
        return $this->classification;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}

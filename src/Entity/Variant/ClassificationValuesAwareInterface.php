<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity\Variant;

use Doctrine\Common\Collections\Collection;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;

interface ClassificationValuesAwareInterface
{
    public function initializeClassificationValues(): void;

    /**
     * @return Collection<int, ProductVariantClassificationValueInterface>
     */
    public function getClassificationValueAssignments(): Collection;

    /**
     * @return list<ClassificationValueInterface>
     */
    public function getClassificationValues(string $classificationCode): array;

    public function hasClassificationValue(string $valueCode): bool;

    public function addClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void;

    public function removeClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void;
}

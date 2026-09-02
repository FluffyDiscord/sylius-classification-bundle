<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity\Variant;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

trait ClassificationValuesAwareTrait
{
    /**
     * @var Collection<int, ProductVariantClassificationValueInterface>
     */
    protected Collection $classificationValues;

    public function initializeClassificationValues(): void
    {
        $this->classificationValues = new ArrayCollection();
    }

    /**
     * @return Collection<int, ProductVariantClassificationValueInterface>
     */
    public function getClassificationValueAssignments(): Collection
    {
        return $this->classificationValues;
    }

    /**
     * @return list<ClassificationValueInterface>
     */
    public function getClassificationValues(string $classificationCode): array
    {
        $values = [];
        foreach ($this->classificationValues as $assignment) {
            $assignedClassificationCode = $assignment->getClassification()?->getCode();
            if ($assignedClassificationCode !== $classificationCode) {
                continue;
            }

            $value = $assignment->getClassificationValue();
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function hasClassificationValue(string $valueCode): bool
    {
        foreach ($this->classificationValues as $assignment) {
            $assignedValueCode = $assignment->getClassificationValue()?->getCode();
            if ($assignedValueCode === $valueCode) {
                return true;
            }
        }

        return false;
    }

    public function addClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void
    {
        if ($this->classificationValues->contains($assignment)) {
            return;
        }

        $this->classificationValues->add($assignment);

        /** @var ProductVariantInterface $this */
        $assignment->setProductVariant($this);
    }

    public function removeClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void
    {
        $this->classificationValues->removeElement($assignment);
    }
}

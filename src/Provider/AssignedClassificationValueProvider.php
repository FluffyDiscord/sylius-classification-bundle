<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Provider;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

readonly class AssignedClassificationValueProvider implements AssignedClassificationValueProviderInterface
{
    public function __construct(
        private ProductVariantClassificationValueRepositoryInterface $assignmentRepository,
    ) {}

    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function getAssignments(ProductVariantInterface $productVariant, ?string $classificationCode = null): array
    {
        if (! $productVariant instanceof ClassificationValuesAwareInterface) {
            return $this->assignmentRepository->findByVariant($productVariant, $classificationCode);
        }

        $assignments = $productVariant->getClassificationValueAssignments()->toArray();
        if ($classificationCode === null) {
            return array_values($assignments);
        }

        return array_values(array_filter(
            $assignments,
            static fn(ProductVariantClassificationValueInterface $assignment): bool =>
                $assignment->getClassification()?->getCode() === $classificationCode,
        ));
    }

    /**
     * @return list<ClassificationValueInterface>
     */
    public function getValues(ProductVariantInterface $productVariant, string $classificationCode): array
    {
        $values = [];
        foreach ($this->getAssignments($productVariant, $classificationCode) as $assignment) {
            $value = $assignment->getClassificationValue();
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return $values;
    }
}

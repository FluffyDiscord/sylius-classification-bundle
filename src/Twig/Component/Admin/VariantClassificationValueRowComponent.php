<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolverInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'sylius_classification:admin:variant_classification_row',
    template: '@SyliusClassification/admin/product_variant/variant_classification_row.html.twig',
)]
class VariantClassificationValueRowComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?int $variantId = null;

    #[LiveProp]
    public string $classificationCode = '';

    #[LiveProp(writable: true)]
    public string $search = '';

    private function getSearchResultLimit(): int
    {
        return 8;
    }

    /**
     * @param RepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.product_variant')]
        private readonly RepositoryInterface $productVariantRepository,
        private readonly ClassificationRepositoryInterface $classificationRepository,
        private readonly ClassificationValueRepositoryInterface $classificationValueRepository,
        private readonly ProductVariantClassificationValueRepositoryInterface $assignmentRepository,
        private readonly AssignedClassificationValueProviderInterface $assignedValueProvider,
        private readonly ClassificationLabelResolverInterface $labelResolver,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(param: 'sylius_classification.model.product_variant_classification_value.class')]
        private readonly string $assignmentClass,
    ) {}

    public function getLabel(): string
    {
        $classification = $this->getClassification();

        return $classification === null ? $this->classificationCode : $this->labelResolver->getLabel($classification);
    }

    public function isMultiple(): bool
    {
        return $this->getClassification()?->isMultiple() ?? false;
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getValues(): array
    {
        $variant = $this->getVariant();
        if ($variant === null) {
            return [];
        }

        return array_map(
            fn(ClassificationValueInterface $value): array => [
                'code' => (string) $value->getCode(),
                'label' => $this->labelResolver->getLabel($value),
            ],
            $this->assignedValueProvider->getValues($variant, $this->classificationCode),
        );
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function getCandidates(): array
    {
        $variant = $this->getVariant();
        $search = trim($this->search);
        if ($variant === null || $search === '') {
            return [];
        }

        $excludedIds = array_values(array_filter(array_map(
            static fn(ClassificationValueInterface $value): ?int => $value->getId(),
            $this->assignedValueProvider->getValues($variant, $this->classificationCode),
        )));

        return array_map(
            fn(ClassificationValueInterface $value): array => [
                'id' => (int) $value->getId(),
                'label' => $this->labelResolver->getLabel($value),
            ],
            $this->classificationValueRepository->findForPicker($this->classificationCode, $search, $excludedIds, $this->getSearchResultLimit()),
        );
    }

    #[LiveAction]
    public function addValue(#[LiveArg] string $valueId): void
    {
        $variant = $this->getVariant();
        $classification = $this->getClassification();
        $value = $this->classificationValueRepository->find($valueId);
        if ($variant === null || $classification === null || ! $value instanceof ClassificationValueInterface) {
            return;
        }

        if ($value->getClassification()?->getCode() !== $this->classificationCode) {
            return;
        }

        $existingAssignments = $this->assignmentRepository->findByVariantAndClassification($variant, $classification);
        foreach ($existingAssignments as $assignment) {
            if ($assignment->getClassificationValue()?->getId() === $value->getId()) {
                return;
            }

            if (! $classification->isMultiple()) {
                $this->entityManager->remove($assignment);
            }
        }

        $assignment = $this->createAssignment();
        $assignment->setProductVariant($variant);
        $assignment->setClassificationValue($value);
        $assignment->setPosition(count($existingAssignments));

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        $this->search = '';
    }

    #[LiveAction]
    public function removeValue(#[LiveArg] string $valueCode): void
    {
        $variant = $this->getVariant();
        if ($variant === null) {
            return;
        }

        foreach ($this->assignmentRepository->findByVariant($variant, $this->classificationCode) as $assignment) {
            if ($assignment->getClassificationValue()?->getCode() === $valueCode) {
                $this->entityManager->remove($assignment);
                $this->entityManager->flush();

                return;
            }
        }
    }

    private function getClassification(): ?ClassificationInterface
    {
        $classification = $this->classificationRepository->findOneBy([
            'code' => $this->classificationCode,
        ]);

        return $classification instanceof ClassificationInterface ? $classification : null;
    }

    private function getVariant(): ?ProductVariantInterface
    {
        if ($this->variantId === null) {
            return null;
        }

        $variant = $this->productVariantRepository->find($this->variantId);

        return $variant instanceof ProductVariantInterface ? $variant : null;
    }

    private function createAssignment(): ProductVariantClassificationValueInterface
    {
        /** @var ProductVariantClassificationValueInterface $assignment */
        $assignment = new $this->assignmentClass();

        return $assignment;
    }
}

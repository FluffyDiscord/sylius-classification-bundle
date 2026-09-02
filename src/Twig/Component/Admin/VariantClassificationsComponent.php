<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin;

use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\RenderableClassificationProviderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'sylius_classification:admin:variant_classifications',
    template: '@SyliusClassification/admin/product_variant/variant_classifications.html.twig',
)]
class VariantClassificationsComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?int $variantId = null;

    /**
     * @var list<string>
     */
    #[LiveProp(writable: true)]
    public array $openTypeCodes = [];

    #[LiveProp(writable: true)]
    public string $selectedTypeToAdd = '';

    /**
     * @param RepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        #[Autowire(service: 'sylius.repository.product_variant')]
        private readonly RepositoryInterface $productVariantRepository,
        private readonly RenderableClassificationProviderInterface $renderableClassificationProvider,
        private readonly AssignedClassificationValueProviderInterface $assignedValueProvider,
    ) {}

    public function mount(ProductVariantInterface $variant): void
    {
        $id = $variant->getId();
        $this->variantId = is_numeric($id) ? (int) $id : null;
    }

    public function isPersisted(): bool
    {
        return $this->getVariant() !== null;
    }

    /**
     * @return list<string>
     */
    public function getShownTypeCodes(): array
    {
        $variant = $this->getVariant();
        if ($variant === null) {
            return [];
        }

        $shown = [];
        foreach ($this->renderableClassificationProvider->getClassifications() as $classification) {
            $hasAssignments = $this->assignedValueProvider->getValues($variant, $classification->code) !== [];
            $isOpen = in_array($classification->code, $this->openTypeCodes, true);
            if ($hasAssignments || $isOpen) {
                $shown[] = $classification->code;
            }
        }

        return $shown;
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public function getAddableTypes(): array
    {
        $shownCodes = $this->getShownTypeCodes();

        $addable = [];
        foreach ($this->renderableClassificationProvider->getClassifications() as $classification) {
            if (! in_array($classification->code, $shownCodes, true)) {
                $addable[] = [
                    'code' => $classification->code,
                    'label' => $classification->label,
                ];
            }
        }

        return $addable;
    }

    #[LiveAction]
    public function addSelectedType(): void
    {
        $classificationCode = $this->selectedTypeToAdd;
        $this->selectedTypeToAdd = '';

        if ($classificationCode !== '' && ! in_array($classificationCode, $this->openTypeCodes, true)) {
            $this->openTypeCodes[] = $classificationCode;
        }
    }

    private function getVariant(): ?ProductVariantInterface
    {
        if ($this->variantId === null) {
            return null;
        }

        $variant = $this->productVariantRepository->find($this->variantId);

        return $variant instanceof ProductVariantInterface ? $variant : null;
    }
}

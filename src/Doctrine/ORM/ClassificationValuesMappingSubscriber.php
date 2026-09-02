<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: Events::loadClassMetadata)]
readonly class ClassificationValuesMappingSubscriber
{
    public function __construct(
        #[Autowire(param: 'sylius_classification.model.product_variant_classification_value.class')]
        private string $assignmentClass,
        #[Autowire(param: 'sylius.model.product_variant.class')]
        private string $productVariantClass,
    ) {}

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if (is_a($metadata->getName(), ClassificationValuesAwareInterface::class, true)) {
            $this->mapInverseSide($metadata);

            return;
        }

        if ($metadata->getName() === $this->assignmentClass) {
            $this->mapOwningSide($metadata);
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function mapInverseSide(ClassMetadata $metadata): void
    {
        if ($metadata->hasAssociation($this->getInverseFieldName())) {
            return;
        }

        $metadata->mapOneToMany([
            'fieldName' => $this->getInverseFieldName(),
            'targetEntity' => $this->assignmentClass,
            'mappedBy' => $this->getOwningFieldName(),
            'cascade' => ['persist'],
            'orphanRemoval' => true,
            'orderBy' => [
                'position' => 'ASC',
            ],
        ]);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function mapOwningSide(ClassMetadata $metadata): void
    {
        $variantIsAware = is_a($this->productVariantClass, ClassificationValuesAwareInterface::class, true);
        if (! $variantIsAware) {
            return;
        }

        $owningField = $this->getOwningFieldName();
        if (! $metadata->hasAssociation($owningField)) {
            return;
        }

        $owningMapping = $metadata->associationMappings[$owningField];
        if (! $owningMapping instanceof ManyToOneAssociationMapping) {
            return;
        }

        $owningMapping->inversedBy = $this->getInverseFieldName();
    }

    private function getInverseFieldName(): string
    {
        return 'classificationValues';
    }

    private function getOwningFieldName(): string
    {
        return 'productVariant';
    }
}

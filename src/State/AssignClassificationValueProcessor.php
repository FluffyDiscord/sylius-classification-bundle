<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<ProductVariantClassificationValueInterface, ProductVariantClassificationValueInterface>
 */
readonly class AssignClassificationValueProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<ProductVariantClassificationValueInterface, ProductVariantClassificationValueInterface> $persistProcessor
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductVariantClassificationValueRepositoryInterface $assignmentRepository,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (! $data instanceof ProductVariantClassificationValueInterface) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $productVariant = $data->getProductVariant();
        $classificationValue = $data->getClassificationValue();
        $classification = $classificationValue?->getClassification();
        if ($productVariant === null || $classificationValue === null || $classification === null) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var ProductVariantClassificationValueInterface $result */
        $result = $this->entityManager->wrapInTransaction(
            fn(): mixed => $this->assign($data, $operation, $uriVariables, $context),
        );

        return $result;
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    private function assign(
        ProductVariantClassificationValueInterface $data,
        Operation $operation,
        array $uriVariables,
        array $context,
    ): mixed {
        /** @var \Sylius\Component\Core\Model\ProductVariantInterface $productVariant */
        $productVariant = $data->getProductVariant();
        /** @var \FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface $classificationValue */
        $classificationValue = $data->getClassificationValue();
        /** @var \FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface $classification */
        $classification = $classificationValue->getClassification();

        $this->entityManager->lock($productVariant, LockMode::PESSIMISTIC_WRITE);

        $existingAssignments = $this->assignmentRepository->findByVariantAndClassification($productVariant, $classification);

        foreach ($existingAssignments as $assignment) {
            $isSameValue = $assignment->getClassificationValue()?->getCode() === $classificationValue->getCode();
            if ($isSameValue) {
                $assignment->setPosition($data->getPosition());

                return $assignment;
            }

            if (! $classification->isMultiple()) {
                $this->entityManager->remove($assignment);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

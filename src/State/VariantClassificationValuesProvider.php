<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ProductVariantClassificationValueRepositoryInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ProductVariantClassificationValueInterface>
 */
readonly class VariantClassificationValuesProvider implements ProviderInterface
{
    /**
     * @param RepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private ProductVariantClassificationValueRepositoryInterface $assignmentRepository,
        #[Autowire(service: 'sylius.repository.product_variant')]
        private RepositoryInterface $productVariantRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $code = $uriVariables['code'] ?? null;
        $productVariant = is_string($code) ? $this->productVariantRepository->findOneBy([
            'code' => $code,
        ]) : null;

        if (! $productVariant instanceof ProductVariantInterface) {
            throw new NotFoundHttpException('Product variant not found.');
        }

        return $this->assignmentRepository->findByVariantForSerialization($productVariant);
    }
}

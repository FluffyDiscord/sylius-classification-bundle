<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use Doctrine\Common\Collections\Order;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductVariantInterface;

class ProductVariantClassificationValueRepository extends EntityRepository implements ProductVariantClassificationValueRepositoryInterface
{
    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariantAndClassification(
        ProductVariantInterface $productVariant,
        ClassificationInterface $classification,
    ): array {
        /** @var list<ProductVariantClassificationValueInterface> $assignments */
        $assignments = $this->findBy([
            'productVariant' => $productVariant,
            'classification' => $classification,
        ]);

        return $assignments;
    }

    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariantForSerialization(ProductVariantInterface $productVariant): array
    {
        /** @var list<ProductVariantClassificationValueInterface> $assignments */
        $assignments = $this->createQueryBuilder('assignment')
            ->addSelect('classificationValue', 'classificationValueTranslation', 'classification')
            ->innerJoin('assignment.classificationValue', 'classificationValue')
            ->leftJoin('classificationValue.translations', 'classificationValueTranslation')
            ->innerJoin('assignment.classification', 'classification')
            ->andWhere('assignment.productVariant = :productVariant')
            ->setParameter('productVariant', $productVariant)
            ->orderBy('assignment.position', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        return $assignments;
    }

    /**
     * @return list<ProductVariantClassificationValueInterface>
     */
    public function findByVariant(ProductVariantInterface $productVariant, ?string $classificationCode = null): array
    {
        $queryBuilder = $this->createQueryBuilder('assignment')
            ->addSelect('classificationValue', 'classification')
            ->innerJoin('assignment.classificationValue', 'classificationValue')
            ->innerJoin('assignment.classification', 'classification')
            ->andWhere('assignment.productVariant = :productVariant')
            ->setParameter('productVariant', $productVariant)
            ->orderBy('assignment.position', Order::Ascending->value)
        ;

        if ($classificationCode !== null) {
            $queryBuilder
                ->andWhere('classification.code = :classificationCode')
                ->setParameter('classificationCode', $classificationCode)
            ;
        }

        /** @var list<ProductVariantClassificationValueInterface> $assignments */
        $assignments = $queryBuilder->getQuery()->getResult();

        return $assignments;
    }
}

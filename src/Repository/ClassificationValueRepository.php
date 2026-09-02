<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ClassificationValueRepository extends EntityRepository implements ClassificationValueRepositoryInterface
{
    public function createByClassificationCodeQueryBuilder(string $classificationCode): QueryBuilder
    {
        return $this->createQueryBuilder('value')
            ->innerJoin('value.classification', 'classification')
            ->andWhere('classification.code = :classificationCode')
            ->setParameter('classificationCode', $classificationCode)
        ;
    }

    public function findOneByCode(string $code): ?ClassificationValueInterface
    {
        /** @var ClassificationValueInterface|null $value */
        $value = $this->findOneBy([
            'code' => $code,
        ]);

        return $value;
    }

    /**
     * @param list<int> $excludedIds
     *
     * @return list<ClassificationValueInterface>
     */
    public function findForPicker(string $classificationCode, string $search, array $excludedIds, int $limit): array
    {
        $queryBuilder = $this->createByClassificationCodeQueryBuilder($classificationCode)
            ->leftJoin('value.translations', 'translation')
            ->andWhere('value.code LIKE :search OR translation.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->addOrderBy('value.position', Order::Ascending->value)
            ->addOrderBy('value.code', Order::Ascending->value)
            ->setMaxResults($limit)
        ;

        if ($excludedIds !== []) {
            $queryBuilder
                ->andWhere('value.id NOT IN (:excludedIds)')
                ->setParameter('excludedIds', $excludedIds)
            ;
        }

        /** @var list<ClassificationValueInterface> $values */
        $values = $queryBuilder->getQuery()->getResult();

        return $values;
    }

    public function getNextPosition(ClassificationInterface $classification): int
    {
        $highestPosition = $this->createQueryBuilder('value')
            ->select('MAX(value.position)')
            ->andWhere('value.classification = :classification')
            ->setParameter('classification', $classification)
            ->getQuery()
            ->getSingleScalarResult();

        if ($highestPosition === null) {
            return 0;
        }

        return (int) $highestPosition + 1;
    }
}

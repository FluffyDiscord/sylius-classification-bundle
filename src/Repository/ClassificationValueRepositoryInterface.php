<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<ClassificationValueInterface>
 */
interface ClassificationValueRepositoryInterface extends RepositoryInterface
{
    public function createByClassificationCodeQueryBuilder(string $classificationCode): QueryBuilder;

    public function findOneByCode(string $code): ?ClassificationValueInterface;

    public function getNextPosition(ClassificationInterface $classification): int;

    /**
     * @param list<int> $excludedIds
     *
     * @return list<ClassificationValueInterface>
     */
    public function findForPicker(string $classificationCode, string $search, array $excludedIds, int $limit): array;
}

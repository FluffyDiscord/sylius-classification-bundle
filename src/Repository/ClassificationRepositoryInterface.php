<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<ClassificationInterface>
 */
interface ClassificationRepositoryInterface extends RepositoryInterface
{
    /**
     * @return list<ClassificationInterface>
     */
    public function findWithValuesAndTranslations(): array;
}

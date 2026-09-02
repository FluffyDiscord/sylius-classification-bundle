<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Repository;

use Doctrine\Common\Collections\Order;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ClassificationRepository extends EntityRepository implements ClassificationRepositoryInterface
{
    /**
     * @return list<ClassificationInterface>
     */
    public function findWithValuesAndTranslations(): array
    {
        /** @var list<ClassificationInterface> $classifications */
        $classifications = $this->createQueryBuilder('classification')
            ->addSelect('translation')
            ->leftJoin('classification.translations', 'translation')
            ->andWhere('EXISTS (SELECT 1 FROM ' . $this->getEntityName() . ' owner INNER JOIN owner.values value WHERE owner = classification)')
            ->orderBy('classification.position', Order::Ascending->value)
            ->addOrderBy('classification.code', Order::Ascending->value)
            ->getQuery()
            ->getResult();

        return $classifications;
    }
}

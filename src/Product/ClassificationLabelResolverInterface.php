<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;

interface ClassificationLabelResolverInterface
{
    public function getLabel(ClassificationInterface|ClassificationValueInterface $subject): string;
}

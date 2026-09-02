<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;

interface ClassificationValueCodeDeriverInterface
{
    public function derive(ClassificationValueInterface $classificationValue): void;
}

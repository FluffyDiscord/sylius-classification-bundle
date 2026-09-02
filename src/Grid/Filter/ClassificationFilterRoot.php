<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Grid\Filter;

enum ClassificationFilterRoot: string
{
    case Product = 'product';
    case ProductVariant = 'product_variant';
}

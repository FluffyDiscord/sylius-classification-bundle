<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareTrait;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product_variant')]
class ProductVariant extends BaseProductVariant implements ClassificationValuesAwareInterface
{
    use ClassificationValuesAwareTrait;

    public function __construct()
    {
        parent::__construct();

        $this->initializeClassificationValues();
    }
}

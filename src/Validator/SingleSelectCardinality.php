<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class SingleSelectCardinality extends Constraint
{
    public string $message = 'sylius_classification.variant_value.single_select';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

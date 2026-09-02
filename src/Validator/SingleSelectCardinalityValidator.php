<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Validator;

use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SingleSelectCardinalityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AssignedClassificationValueProviderInterface $assignedValueProvider,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof SingleSelectCardinality) {
            throw new UnexpectedValueException($constraint, SingleSelectCardinality::class);
        }

        if (! $value instanceof ProductVariantClassificationValueInterface) {
            return;
        }

        $assignedValue = $value->getClassificationValue();
        $productVariant = $value->getProductVariant();
        if ($assignedValue === null || $productVariant === null) {
            return;
        }

        $classification = $assignedValue->getClassification();
        if ($classification === null || $classification->isMultiple()) {
            return;
        }

        $classificationCode = $classification->getCode();
        if ($classificationCode === null) {
            return;
        }

        foreach ($this->assignedValueProvider->getAssignments($productVariant, $classificationCode) as $existing) {
            if ($existing === $value) {
                continue;
            }

            $isDifferentValue = $existing->getClassificationValue()?->getCode() !== $assignedValue->getCode();
            if ($isDifferentValue) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('classificationValue')
                    ->addViolation();

                return;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Unit\Validator;

use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Provider\AssignedClassificationValueProviderInterface;
use FluffyDiscord\SyliusClassificationBundle\Validator\SingleSelectCardinality;
use FluffyDiscord\SyliusClassificationBundle\Validator\SingleSelectCardinalityValidator;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariant;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class SingleSelectCardinalityValidatorTest extends TestCase
{
    public function testRaisesViolationWhenSingleSelectAxisAlreadyHasDifferentValue(): void
    {
        $classification = $this->createClassification('color', multiple: false);
        $existing = $this->createAssignment($classification, 'color_red');
        $submitted = $this->createAssignment($classification, 'color_blue');

        $violationRaised = $this->validate($submitted, [$existing]);

        self::assertTrue($violationRaised);
    }

    public function testNoViolationWhenSameValue(): void
    {
        $classification = $this->createClassification('color', multiple: false);
        $existing = $this->createAssignment($classification, 'color_red');
        $submitted = $this->createAssignment($classification, 'color_red');

        self::assertFalse($this->validate($submitted, [$existing]));
    }

    public function testNoViolationForMultipleAxis(): void
    {
        $classification = $this->createClassification('tags', multiple: true);
        $existing = $this->createAssignment($classification, 'tags_a');
        $submitted = $this->createAssignment($classification, 'tags_b');

        self::assertFalse($this->validate($submitted, [$existing]));
    }

    public function testNoViolationWhenValueMissing(): void
    {
        $submitted = new ProductVariantClassificationValue();
        $submitted->setProductVariant(new ProductVariant());

        self::assertFalse($this->validate($submitted, []));
    }

    private function createClassification(string $code, bool $multiple): Classification
    {
        $classification = new Classification();
        $classification->setCode($code);
        $classification->setMultiple($multiple);

        return $classification;
    }

    private function createAssignment(Classification $classification, string $valueCode): ProductVariantClassificationValue
    {
        $value = new ClassificationValue();
        $value->setClassification($classification);
        $value->setCode($valueCode);

        $assignment = new ProductVariantClassificationValue();
        $assignment->setProductVariant(new ProductVariant());
        $assignment->setClassificationValue($value);

        return $assignment;
    }

    /**
     * @param list<ProductVariantClassificationValue> $existingAssignments
     */
    private function validate(ProductVariantClassificationValue $submitted, array $existingAssignments): bool
    {
        $provider = $this->createStub(AssignedClassificationValueProviderInterface::class);
        $provider->method('getAssignments')->willReturn($existingAssignments);

        $violationRaised = false;

        $builder = $this->createStub(ConstraintViolationBuilderInterface::class);
        $builder->method('atPath')->willReturnSelf();
        $builder->method('addViolation')->willReturnCallback(function () use (&$violationRaised): void {
            $violationRaised = true;
        });

        $context = $this->createStub(ExecutionContextInterface::class);
        $context->method('buildViolation')->willReturn($builder);

        $validator = new SingleSelectCardinalityValidator($provider);
        $validator->initialize($context);
        $validator->validate($submitted, new SingleSelectCardinality());

        return $violationRaised;
    }
}

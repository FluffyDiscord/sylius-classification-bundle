<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Classification;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Entity\ProductVariantClassificationValue;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationFilterRoot;
use FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ClassificationValueExpressionBuilder;
use FluffyDiscord\SyliusClassificationBundle\Tests\Application\Entity\ProductVariant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GridFilterAndValidationTest extends KernelTestCase
{
    public function testExpressionBuilderProducesParseableDql(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        /** @var ClassificationValueExpressionBuilder $builder */
        $builder = self::getContainer()->get(ClassificationValueExpressionBuilder::class);

        $queryBuilder = $entityManager->getRepository($this->getProductClass())->createQueryBuilder('o');
        $expression = $builder->build($queryBuilder, 'color', ['color_red'], ClassificationFilterRoot::Product, true);
        $queryBuilder->andWhere($expression);

        $sql = $queryBuilder->getQuery()->getSQL();

        self::assertIsString($sql);
        self::assertStringContainsStringIgnoringCase('exists', $sql);
    }

    public function testFilterRejectsMissingClassificationCode(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        /** @var \FluffyDiscord\SyliusClassificationBundle\Grid\Filter\ProductClassificationFilter $filter */
        $filter = self::getContainer()->get('sylius_classification.grid.filter.classification_value');

        $queryBuilder = $entityManager->getRepository($this->getProductClass())->createQueryBuilder('o');
        $dataSource = new \Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource($queryBuilder, false, false);

        $this->expectException(\InvalidArgumentException::class);

        $filter->apply($dataSource, 'facet', ['color_red'], []);
    }

    public function testSingleSelectCardinalityFiresUnderSyliusGroup(): void
    {
        self::bootKernel();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        $classification = new Classification();
        $classification->setCode('color');
        $classification->setMultiple(false);

        $variant = new ProductVariant();
        $variant->addClassificationValueAssignment($this->createAssignment($classification, 'color_red', $variant));

        $submitted = $this->createAssignment($classification, 'color_blue', $variant);

        $violations = $validator->validate($submitted, null, ['sylius']);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testMissingRequiredFieldsFireUnderSyliusGroup(): void
    {
        self::bootKernel();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');

        $violations = $validator->validate(new ProductVariantClassificationValue(), null, ['sylius']);

        self::assertGreaterThanOrEqual(2, $violations->count());
    }

    public function testGetNextPositionStartsAtZeroThenIncrements(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        /** @var \FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface $repository */
        $repository = self::getContainer()->get(\FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface::class);

        $classification = new Classification();
        $classification->setCode('color_' . uniqid());
        $classification->setCurrentLocale('en');
        $classification->setFallbackLocale('en');
        $classification->setName('Color');
        $entityManager->persist($classification);
        $entityManager->flush();

        self::assertSame(0, $repository->getNextPosition($classification));

        $value = new ClassificationValue();
        $value->setClassification($classification);
        $value->setCode('color_red_' . uniqid());
        $value->setPosition(5);
        $value->setCurrentLocale('en');
        $value->setFallbackLocale('en');
        $value->setName('Red');
        $entityManager->persist($value);
        $entityManager->flush();

        self::assertSame(6, $repository->getNextPosition($classification));

        $entityManager->remove($value);
        $entityManager->remove($classification);
        $entityManager->flush();
    }

    private function createAssignment(Classification $classification, string $valueCode, ProductVariant $variant): ProductVariantClassificationValue
    {
        $value = new ClassificationValue();
        $value->setClassification($classification);
        $value->setCode($valueCode);

        $assignment = new ProductVariantClassificationValue();
        $assignment->setProductVariant($variant);
        $assignment->setClassificationValue($value);

        return $assignment;
    }

    /**
     * @return class-string
     */
    private function getProductClass(): string
    {
        /** @var class-string $class */
        $class = self::getContainer()->getParameter('sylius.model.product.class');

        return $class;
    }
}

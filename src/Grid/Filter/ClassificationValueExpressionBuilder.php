<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Grid\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ClassificationValueExpressionBuilder
{
    public function __construct(
        #[Autowire(param: 'sylius_classification.model.product_variant_classification_value.class')]
        private string $assignmentClass,
    ) {}

    /**
     * @param list<string> $valueCodes
     */
    public function build(
        QueryBuilder $queryBuilder,
        string $classificationCode,
        array $valueCodes,
        ClassificationFilterRoot $root = ClassificationFilterRoot::Product,
        bool $enabledVariantsOnly = true,
    ): string {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $suffix = $this->buildAliasSuffix($classificationCode);

        $assignmentAlias = 'classificationAssignment_' . $suffix;
        $variantAlias = 'classificationVariant_' . $suffix;
        $classificationAlias = 'classificationAxis_' . $suffix;
        $valueAlias = 'classificationValue_' . $suffix;
        $codeParameter = 'classificationCode_' . $suffix;
        $valuesParameter = 'classificationValues_' . $suffix;

        $queryBuilder
            ->setParameter($codeParameter, $classificationCode)
            ->setParameter($valuesParameter, $valueCodes)
        ;

        $correlation = $root === ClassificationFilterRoot::Product
            ? sprintf('%s.product = %s', $variantAlias, $rootAlias)
            : sprintf('%s = %s', $variantAlias, $rootAlias);

        $predicates = [
            $correlation,
            sprintf('%s.code = :%s', $classificationAlias, $codeParameter),
            sprintf('%s.code IN (:%s)', $valueAlias, $valuesParameter),
        ];

        if ($enabledVariantsOnly) {
            $predicates[] = sprintf('%s.enabled = true', $variantAlias);
        }

        return sprintf(
            'EXISTS (SELECT 1 FROM %s %s JOIN %s.productVariant %s JOIN %s.classification %s JOIN %s.classificationValue %s WHERE %s)',
            $this->assignmentClass,
            $assignmentAlias,
            $assignmentAlias,
            $variantAlias,
            $assignmentAlias,
            $classificationAlias,
            $assignmentAlias,
            $valueAlias,
            implode(' AND ', $predicates),
        );
    }

    private function buildAliasSuffix(string $classificationCode): string
    {
        return substr(hash('xxh128', $classificationCode), 0, 12);
    }
}

<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Grid\Filter;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource;
use Sylius\Component\Grid\Attribute\AsFilter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filtering\FilterInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

#[AsFilter(type: 'classification_value', formType: HiddenType::class)]
readonly class ProductClassificationFilter implements FilterInterface
{
    public function __construct(
        private ClassificationValueExpressionBuilder $expressionBuilder,
    ) {}

    /**
     * @param array{classification_code?: string, root?: string, enabled_variants_only?: bool} $options
     */
    public function apply(DataSourceInterface $dataSource, string $name, $data, array $options): void
    {
        if (! $dataSource instanceof DataSource) {
            return;
        }

        $classificationCode = $options['classification_code'] ?? null;
        $classificationCodeIsUsable = is_string($classificationCode) && $classificationCode !== '';
        if (! $classificationCodeIsUsable) {
            throw new \InvalidArgumentException(sprintf('Grid filter "%s" requires a non-empty "classification_code" option.', $name));
        }

        if (! is_array($data)) {
            return;
        }

        $valueCodes = array_values(array_filter(
            $data,
            static fn(mixed $value): bool => is_string($value) && $value !== '',
        ));
        if ($valueCodes === []) {
            return;
        }

        $root = $this->resolveRoot($options['root'] ?? null);
        $enabledVariantsOnly = $options['enabled_variants_only'] ?? true;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $dataSource->getQueryBuilder();

        $expression = $this->expressionBuilder->build($queryBuilder, $classificationCode, $valueCodes, $root, $enabledVariantsOnly);

        $queryBuilder->andWhere($expression);
    }

    private function resolveRoot(?string $root): ClassificationFilterRoot
    {
        if ($root === null) {
            return ClassificationFilterRoot::Product;
        }

        return ClassificationFilterRoot::tryFrom($root) ?? ClassificationFilterRoot::Product;
    }
}

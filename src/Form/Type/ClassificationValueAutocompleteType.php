<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Form\Type;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\AdminBundle\Form\Type\TranslatableAutocompleteType;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * @extends AbstractType<\FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface>
 */
#[AsEntityAutocompleteField(
    alias: 'sylius_classification_value',
    route: 'sylius_admin_entity_autocomplete',
)]
class ClassificationValueAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly string $classificationValueClass,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'class' => $this->classificationValueClass,
                'choice_label' => 'name',
                'entity_fields' => ['code'],
                'translation_fields' => ['name'],
            ])
            ->setRequired('extra_options')
            ->setNormalizer('extra_options', static function (Options $options, array $extraOptions): array {
                $classificationCode = $extraOptions['classification_code'] ?? null;
                if (! is_string($classificationCode) || $classificationCode === '') {
                    throw new MissingOptionsException('Missing node "classification_code" within the "extra_options" option.');
                }

                return $extraOptions;
            })
            ->setDefault('filter_query', fn(Options $options): callable => $this->getClassificationFilter($this->getClassificationCode($options)))
        ;
    }

    public function getParent(): string
    {
        return TranslatableAutocompleteType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_classification_classification_value_autocomplete';
    }

    /**
     * @param Options<array<string, mixed>> $options
     */
    private function getClassificationCode(Options $options): string
    {
        /** @var array{classification_code: string} $extraOptions */
        $extraOptions = $options['extra_options'];

        return $extraOptions['classification_code'];
    }

    private function getClassificationFilter(string $classificationCode): callable
    {
        $entityAlias = TranslatableAutocompleteType::ENTITY_ALIAS;

        return static function (QueryBuilder $queryBuilder, string $query, EntityRepository $repository) use ($entityAlias, $classificationCode): void {
            $queryBuilder
                ->innerJoin(sprintf('%s.classification', $entityAlias), 'classification')
                ->andWhere('classification.code = :classificationCode')
                ->setParameter('classificationCode', $classificationCode)
                ->addOrderBy(sprintf('%s.position', $entityAlias), Order::Ascending->value)
                ->addOrderBy(sprintf('%s.code', $entityAlias), Order::Ascending->value)
            ;
        };
    }
}

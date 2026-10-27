<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Grid;

use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Filter\EntityFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Component\Grid\Attribute\AsGrid;
use Sylius\Component\Grid\Builder\GridBuilderInterface;

#[AsGrid(name: 'sylius_classification_admin_classification_value')]
readonly class ClassificationValueGrid
{
    public function __construct(
        private string $classificationValueClass,
        private string $classificationClass,
    ) {}

    public function __invoke(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->setDriverOption('class', $this->classificationValueClass)
            ->addOrderBy('position', 'asc')
            ->addOrderBy('code', 'asc')
            ->setLimits([10, 25, 50])
            ->addField(StringField::create('position')->setLabel('sylius.ui.position')->setSortable(true))
            ->addField(StringField::create('code')->setLabel('sylius.ui.code')->setSortable(true))
            ->addField(StringField::create('name')->setLabel('sylius.ui.name'))
            ->addFilter(
                StringFilter::create('search', ['code'], StringFilter::TYPE_CONTAINS)->setLabel('sylius.ui.search'),
            )
            ->addFilter(
                EntityFilter::create('classification', $this->classificationClass, null, ['classification.id'])
                    ->setLabel('sylius_classification.ui.classification')
                    ->addFormOption('choice_label', 'code')
                    ->addFormOption('autocomplete', true),
            )
            ->addActionGroup(MainActionGroup::create(CreateAction::create()))
            ->addActionGroup(ItemActionGroup::create(UpdateAction::create(), DeleteAction::create()))
            ->addActionGroup(BulkActionGroup::create(DeleteAction::create()))
        ;
    }
}

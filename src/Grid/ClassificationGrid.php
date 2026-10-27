<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Grid;

use Sylius\Bundle\GridBundle\Builder\Action\Action;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Component\Grid\Attribute\AsGrid;
use Sylius\Component\Grid\Builder\Action\ActionInterface;
use Sylius\Component\Grid\Builder\GridBuilderInterface;

#[AsGrid(name: 'sylius_classification_admin_classification')]
readonly class ClassificationGrid
{
    public function __construct(
        private string $classificationClass,
    ) {}

    public function __invoke(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->setDriverOption('class', $this->classificationClass)
            ->addOrderBy('position', 'asc')
            ->setLimits([10, 25, 50])
            ->addField(StringField::create('position')->setLabel('sylius.ui.position')->setSortable(true))
            ->addField(StringField::create('code')->setLabel('sylius.ui.code')->setSortable(true))
            ->addField(StringField::create('name')->setLabel('sylius.ui.name'))
            ->addField(
                TwigField::create('multiple', '@SyliusAdmin/shared/grid/field/boolean.html.twig')
                    ->setLabel('sylius_classification.ui.multiple')
                    ->setOption('vars', [
                        'th_class' => 'text-center',
                    ]),
            )
            ->addFilter(
                StringFilter::create('search', ['code'], StringFilter::TYPE_CONTAINS)->setLabel('sylius.ui.search'),
            )
            ->addActionGroup(MainActionGroup::create(CreateAction::create()))
            ->addActionGroup(ItemActionGroup::create(
                $this->createShowValuesAction(),
                UpdateAction::create(),
                DeleteAction::create(),
            ))
            ->addActionGroup(BulkActionGroup::create(DeleteAction::create()))
        ;
    }

    private function createShowValuesAction(): ActionInterface
    {
        return Action::create('show_values', 'show')
            ->setLabel('sylius_classification.ui.show_values')
            ->setIcon('tabler:list-details')
            ->setOptions([
                'link' => [
                    'route' => 'sylius_classification_admin_classification_value_index',
                    'parameters' => [
                        'criteria[classification]' => 'resource.id',
                    ],
                ],
            ])
        ;
    }
}

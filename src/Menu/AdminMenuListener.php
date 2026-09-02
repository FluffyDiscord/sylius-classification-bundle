<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'sylius.menu.admin.main', method: 'addClassificationMenu')]
readonly class AdminMenuListener
{
    public function __construct(
        #[Autowire(param: 'sylius_classification.menu.parent')]
        private string $parentName,
        #[Autowire(param: 'sylius_classification.menu.position')]
        private ?int $position,
    ) {}

    public function addClassificationMenu(MenuBuilderEvent $event): void
    {
        $parent = $this->resolveParent($event->getMenu());

        $parent
            ->addChild('classification_types', [
                'route' => 'sylius_classification_admin_classification_index',
                'extras' => [
                    'routes' => [
                        [
                            'route' => 'sylius_classification_admin_classification_create',
                        ],
                        [
                            'route' => 'sylius_classification_admin_classification_update',
                        ],
                    ],
                ],
            ])
            ->setLabel('sylius_classification.menu.admin.main.classification_types')
            ->setLabelAttribute('icon', 'tabler:tag');

        $parent
            ->addChild('classification_values', [
                'route' => 'sylius_classification_admin_classification_value_index',
                'extras' => [
                    'routes' => [
                        [
                            'route' => 'sylius_classification_admin_classification_value_create',
                        ],
                        [
                            'route' => 'sylius_classification_admin_classification_value_update',
                        ],
                    ],
                ],
            ])
            ->setLabel('sylius_classification.menu.admin.main.classification_values')
            ->setLabelAttribute('icon', 'tabler:tag-starred');

        $this->applyPosition($parent, ['classification_types', 'classification_values']);
    }

    private function resolveParent(ItemInterface $menu): ItemInterface
    {
        return $menu->getChild($this->parentName) ?? $menu;
    }

    /**
     * @param list<string> $insertedNames
     */
    private function applyPosition(ItemInterface $parent, array $insertedNames): void
    {
        if ($this->position === null) {
            return;
        }

        $orderedNames = array_keys($parent->getChildren());

        $remainingNames = array_values(array_filter(
            $orderedNames,
            static fn(string $name): bool => ! in_array($name, $insertedNames, true),
        ));

        array_splice($remainingNames, $this->position, 0, $insertedNames);

        $parent->reorderChildren($remainingNames);
    }
}

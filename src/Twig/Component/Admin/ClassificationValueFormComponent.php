<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationValueType;
use Sylius\Bundle\UiBundle\Twig\Component\LiveCollectionTrait;
use Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponentTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent(
    name: 'sylius_classification:admin:classification_value:form',
    template: '@SyliusUi/components/default.html.twig',
    route: 'sylius_admin_live_component',
)]
class ClassificationValueFormComponent
{
    use LiveCollectionTrait;
    use TemplatePropTrait;

    /** @use ResourceFormComponentTrait<ClassificationValueInterface> */
    use ResourceFormComponentTrait;

    /**
     * @param RepositoryInterface<ClassificationValueInterface> $repository
     */
    public function __construct(
        #[Autowire(service: 'sylius_classification.repository.classification_value')]
        RepositoryInterface $repository,
        FormFactoryInterface $formFactory,
        #[Autowire(param: 'sylius_classification.model.classification_value.class')]
        string $resourceClass,
    ) {
        $this->initialize($repository, $formFactory, $resourceClass, ClassificationValueType::class);
    }
}

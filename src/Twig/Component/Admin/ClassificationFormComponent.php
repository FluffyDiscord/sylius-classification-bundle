<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Twig\Component\Admin;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Form\Type\ClassificationType;
use Sylius\Bundle\UiBundle\Twig\Component\LiveCollectionTrait;
use Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponentTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent(
    name: 'sylius_classification:admin:classification:form',
    template: '@SyliusUi/components/default.html.twig',
    route: 'sylius_admin_live_component',
)]
class ClassificationFormComponent
{
    use LiveCollectionTrait;
    use TemplatePropTrait;

    /** @use ResourceFormComponentTrait<ClassificationInterface> */
    use ResourceFormComponentTrait;

    /**
     * @param RepositoryInterface<ClassificationInterface> $repository
     */
    public function __construct(
        #[Autowire(service: 'sylius_classification.repository.classification')]
        RepositoryInterface $repository,
        FormFactoryInterface $formFactory,
        #[Autowire(param: 'sylius_classification.model.classification.class')]
        string $resourceClass,
    ) {
        $this->initialize($repository, $formFactory, $resourceClass, ClassificationType::class);
    }
}

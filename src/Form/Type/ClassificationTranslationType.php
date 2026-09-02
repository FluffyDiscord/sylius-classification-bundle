<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationTranslationInterface>
 */
class ClassificationTranslationType extends AbstractType
{
    public function __construct(
        private readonly string $dataClass,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'sylius_classification.form.classification.name',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', $this->dataClass);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_classification_classification_translation';
    }
}

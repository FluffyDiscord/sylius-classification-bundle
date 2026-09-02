<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Form\Type;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface>
 */
class ClassificationType extends AbstractType
{
    public function __construct(
        private readonly string $dataClass,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => ClassificationTranslationType::class,
                'label' => 'sylius_classification.form.classification.name',
            ])
            ->add('multiple', CheckboxType::class, [
                'required' => false,
                'label' => 'sylius_classification.form.classification.multiple',
            ])
            ->add('position', IntegerType::class, [
                'required' => false,
                'label' => 'sylius_classification.form.classification.position',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->addCodeField(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'validation_groups' => ['sylius'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_classification_classification';
    }

    private function addCodeField(FormEvent $event): void
    {
        $classification = $event->getData();
        $isExisting = $classification instanceof ClassificationInterface && $classification->getId() !== null;

        $event->getForm()->add('code', TextType::class, [
            'label' => 'sylius.ui.code',
            'disabled' => $isExisting,
        ]);
    }
}

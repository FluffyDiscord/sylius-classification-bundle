<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Form\Type;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationValueCodeDeriverInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<\FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface>
 */
class ClassificationValueType extends AbstractType
{
    public function __construct(
        private readonly ClassificationValueCodeDeriverInterface $codeDeriver,
        private readonly ClassificationValueRepositoryInterface $classificationValueRepository,
        private readonly string $dataClass,
        private readonly string $classificationClass,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => ClassificationValueTranslationType::class,
                'label' => 'sylius_classification.form.classification_value.name',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->addFields(...));
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->deriveCode(...), priority: 100);
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->assignNextPosition(...), priority: 90);
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
        return 'sylius_classification_classification_value';
    }

    private function addFields(FormEvent $event): void
    {
        $classificationValue = $event->getData();
        $isExisting = $classificationValue instanceof ClassificationValueInterface && $classificationValue->getId() !== null;

        $form = $event->getForm();

        $form->add('classification', EntityType::class, [
            'class' => $this->classificationClass,
            'choice_label' => 'code',
            'disabled' => $isExisting,
            'label' => 'sylius_classification.form.classification_value.classification',
        ]);

        $form->add('code', TextType::class, [
            'required' => false,
            'disabled' => $isExisting,
            'label' => 'sylius.ui.code',
            'help' => 'sylius_classification.form.classification_value.code_help',
        ]);

        if ($isExisting) {
            $form->add('position', IntegerType::class, [
                'required' => false,
                'label' => 'sylius_classification.form.classification_value.position',
            ]);
        }
    }

    private function deriveCode(FormEvent $event): void
    {
        $classificationValue = $event->getData();
        if ($classificationValue instanceof ClassificationValueInterface) {
            $this->codeDeriver->derive($classificationValue);
        }
    }

    private function assignNextPosition(FormEvent $event): void
    {
        $classificationValue = $event->getData();
        if (! $classificationValue instanceof ClassificationValueInterface || $classificationValue->getId() !== null) {
            return;
        }

        $classification = $classificationValue->getClassification();
        if ($classification === null) {
            return;
        }

        $nextPosition = $this->classificationValueRepository->getNextPosition($classification);
        $classificationValue->setPosition($nextPosition);
    }
}

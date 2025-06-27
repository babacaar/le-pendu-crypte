<?php

namespace App\Form;

use App\Entity\FreeMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FreeModeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('difficultyLevel', ChoiceType::class, [
            'label' => 'Niveau de difficulté',
            'choices' => [
                'Facile' => 'easy',
                'Moyen' => 'medium',
                'Difficile' => 'hard',
            ],
            'expanded' => false, // Dropdown (select field)
            'multiple' => false,
            'attr' => ['class' => 'form-select'], // Bootstrap styling
        ])
        ->add('createdAt', DateTimeType::class, [
            'label' => 'Date de création',
            'widget' => 'single_text',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('word', TextType::class, [
            'label' => 'Mot à deviner',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('hint', TextType::class, [
            'label' => 'Indice',
            'required' => false,
            'attr' => ['class' => 'form-control'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FreeMode::class,
        ]);
    }
}

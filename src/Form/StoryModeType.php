<?php

namespace App\Form;

use App\Entity\StoryMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoryModeType extends AbstractType
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
            ])
            
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'histoire',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (généré automatiquement si laissé vide)',
                'required' => false
            ])
            ->add('picture', FileType::class, [
                'label' => 'Image de couverture',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => 'image/*']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('chapters', CollectionType::class, [
                'label' => 'Chapitres',
                'entry_type' => ChapterType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoryMode::class,
        ]);
    }
}

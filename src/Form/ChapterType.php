<?php

namespace App\Form;

use App\Entity\Chapter;
use App\Entity\StoryMode;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChapterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('chapterText', TextareaType::class, [
                'label'=>"Texte du chapitre"
            ])
            ->add('wordToGuess', TextType::class, [
                "label" => "Mot à deviner",
            ])
            ->add('hint', TextType::class, [
                "label" => "Indice",
                "required" => false,
            ])
            ->add('chapterNumber', IntegerType::class, [
                'label' => "Numéro du chapitre",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chapter::class,
        ]);
    }
}

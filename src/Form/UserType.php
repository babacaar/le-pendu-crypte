<?php

namespace App\Form;

use App\Entity\PointSystem;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'multiple' => true, 
                'expanded' => true,
                'label' => 'Roles'
            ])
            ->add('lastname')
            ->add('firstname')
            ->add('pseudo')
            ->add('birthday', null, [
                'widget' => 'single_text',
            ])
            ->add('avatarImage')
            ->add('isVerified')
            ->add('pointSystem', EntityType::class, [
                'class' => PointSystem::class,
                'choice_label' => 'id',
            ]);
            if ($options['is_new']) {
                $builder->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Mot de passe'],
                    'second_options' => ['label' => 'Confirmer le mot de passe'],
                    'invalid_message' => 'Les mots de passe doivent correspondre.',
                    'mapped' => false, 
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Le mot de passe est requis.']),
                        new Assert\Length([
                            'min' => 6,
                            'minMessage' => 'Le mot de passe doit contenir au moins 6 caractères.'
                        ]),
                    ],
                ]);
            }
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false, // Default is false (edit mode)
        ]);
    }
}

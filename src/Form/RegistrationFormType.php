<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Routing\RouterInterface;


class RegistrationFormType extends AbstractType
{
    private RouterInterface $router;
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('firstname', TextType::class, [
            'label' => 'Prénom',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                new Length([
                    'max' => 64,
                    'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                ]),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add('lastname', TextType::class, [
            'label' => 'Nom',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer votre nom']),
                new Length([
                    'max' => 64,
                    'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                ]),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add('pseudo', TextType::class, [
            'label' => 'Pseudo',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer un pseudo']),
                new Length([
                    'max' => 64,
                    'maxMessage' => 'Le pseudo ne peut pas dépasser {{ limit }} caractères'
                ]),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add('email', EmailType::class, [
            'label' => 'Adresse e-mail',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer une adresse e-mail']),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add('birthday', BirthdayType::class, [
            'label' => 'Date de naissance',
            'widget' => 'single_text',
            'attr' => ['class' => 'form-control'],
        ])
           
        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options' => [
                'label' => 'Mot de passe',
                'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer un mot de passe',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    'max' => 4096,
                ]),
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas.', // Error if passwords do not match
        ])
        ->add('consent', CheckboxType::class, [
            'mapped' => false,
            'constraints' => [
                new IsTrue([
                    'message' => 'Vous devez accepter la politique de confidentialité.',
                ]),
            ],
            'label' => 'En vous inscrivant, vous acceptez notre <a href="' . $this->router->generate('app_privacy_policy') . '" target="_blank">politique de confidentialité</a>.',
            'label_html' => true,
        ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

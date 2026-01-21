<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre adresse mail',
                ],
            ])
            ->add('firstname', TextType::class, [
                'required' => true,
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Entrez votre prénom',
                ],
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrez votre nom',
                ],
            ])
//            Lors de la modification de son profil on ne veut pas être obligé d'entrer les 2 mots de passe si on ne les modifie pas
            ->add('password', PasswordType::class, [
                'required' => !$options['is_edit'],
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Entrez votre mot de passe',
                    'autocomplete' => 'new-password',
                ],
                'empty_data' => '',
            ])
            // même logique pour ne pas avoir à taper le mot de passe s'il n'y a pas de modification à faire
            ->add('confirm_password', PasswordType::class, [
                'required' => !$options['is_edit'],
                'label' => 'Confirmation du mot de passe',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Entrez à nouveau votre mot de passe',
                ],
            ]);
        // Ajout conditionnel du champ "roles" réservé à ADMIN. On ne veut pas qu'ils apparaissent dans le formulaire d'inscription
        if ($options['is_admin']) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Editeur' => 'ROLE_EDITOR',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'Rôles'
            ]);
        }
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false, // par défaut on n'est pas en mode admin
            'is_edit' => false,  // par défaut on n'est pas en mode modification
        ]);
    }
}
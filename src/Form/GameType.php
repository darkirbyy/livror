<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('steamId', IntegerType::class, [
                'required' => false,
            ])
            ->add('steamSearch', SubmitType::class, [
                'validation_groups' => 'steamSearch',
                'attr' => [
                    'value' => 'steamSearch',
                ],
            ])
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('developers', TextType::class, [
                'required' => false,
            ])
            ->add('releaseDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('fullPrice', MoneyType::class, [
                'required' => false,
                'divisor' => 100,
                'input' => 'integer',
            ])
            ->add('genres', TextType::class, [
                'required' => false,
                'help' => 'Séparés par des virgules',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('imgUrl', UrlType::class, [
                'required' => false,
                'default_protocol' => 'http',
                'attr' => [],
            ])
            ->add('submit', SubmitType::class, [
                'validation_groups' => ['Default', 'submit'],
                'attr' => [
                    'value' => 'submit',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}

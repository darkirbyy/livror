<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Game;
use App\Enum\TypePriceEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
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
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('developers', TextType::class, [
                'required' => false,
            ])
            ->add('releaseYear', IntegerType::class, [
                'required' => false,
            ])
            ->add('typePrice', EnumType::class, [
                'required' => false,
                'mapped' => false,
                'class' => TypePriceEnum::class,
                'expanded' => true,
                'multiple' => false,
                'empty_data' => TypePriceEnum::UNKNOWN,
                'placeholder' => false,
            ])
            ->add('fullPrice', MoneyType::class, [
                'required' => false,
                'divisor' => 100,
                'input' => 'integer',
                'currency' => $options['currency'],
            ])
            ->add('genres', TextType::class, [
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('imgUrl', UrlType::class, [
                'required' => false,
                'default_protocol' => 'http',
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Game::class,
                'attr' => [
                    'novalidate' => 'novalidate',
                ],
                'translation_domain' => 'validators',
            ])
            ->setRequired(['currency']);
    }
}

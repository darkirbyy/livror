<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Main\Review;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends DefaultType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hourSpend', IntegerType::class, [
                'required' => false,
            ])
            ->add('firstPlay', DateType::class, [
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'messages',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}

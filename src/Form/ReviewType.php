<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Repository\GameRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends DefaultType
{
    public function __construct(protected bool $htmlValidation, private GameRepository $gameRepo)
    {
        parent::__construct($htmlValidation);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['newReview']) {
            $builder->add('game', EntityType::class, [
                'required' => true,
                'class' => Game::class,
                'choices' => $this->gameRepo->findNotCommented($options['userId']),
                'choice_label' => fn (Game $game) => $game->getName(),
            ]);
        }
        $builder
            ->add('rating', RangeType::class, [
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 6,
                    'step' => '0.1',
                    'list' => 'ratingList',
                ],
            ])
            ->add('hourSpend', IntegerType::class, [
                'required' => false,
            ])
            ->add('firstPlay', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'translation_domain' => 'messages',
            ]);

        if (!empty($options['gameId'])) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                $review = $event->getData();
                $review->setGame($this->gameRepo->find($options['gameId']));
            });
        }
        if ($options['newReview']) {
            $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
                $review = $event->getData();
                $review->setUserId($options['userId']);
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['newReview', 'userId', 'gameId']);
        $resolver->setDefaults([
            'gameId' => null,
            'data_class' => Review::class,
        ]);
    }
}

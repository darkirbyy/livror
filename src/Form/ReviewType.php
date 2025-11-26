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
        if ($options['userId']) {
            $builder->add('game', EntityType::class, [
                'required' => true,
                'class' => Game::class,
                'choices' => [],
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
                    'list' => 'rating-list',
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
                $form = $event->getForm();

                $game = $this->gameRepo->find($options['gameId']);
                $review->setGame($game);
                $options = $form->get('game')->getConfig()->getOptions();
                $options['choices'] = [$game];
                $form->add('game', EntityType::class, $options);
            });
        }
        if ($options['userId']) {
            $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
                $review = $event->getData();

                $review->setUserId($options['userId']);
            });

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $review = $event->getData();
                $form = $event->getForm();

                if (!empty($review['game'])) {
                    $options = $form->get('game')->getConfig()->getOptions();
                    $game = $this->gameRepo->find($review['game']);
                    $options['choices'] = [$game];
                    $form->add('game', EntityType::class, $options);
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['userId', 'gameId']);
        $resolver->setDefaults([
            'userId' => null,
            'gameId' => null,
            'data_class' => Review::class,
        ]);
    }
}

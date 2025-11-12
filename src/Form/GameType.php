<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\FlashMessage;
use App\Entity\Main\Game;
use App\Enum\SteamSearchStatusEnum;
use App\Enum\TypePriceEnum;
use App\Service\SteamSearchManager;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class GameType extends DefaultType
{
    public function __construct(
        private SteamSearchManager $steamSearch,
        private TranslatorInterface $trans,
        private RequestStack $requestStack,
        protected bool $htmlValidation,
        private string $currency,
    ) {
        parent::__construct($htmlValidation);
    }

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
                'required' => true,
                'mapped' => false,
                'class' => TypePriceEnum::class,
                'expanded' => true,
                'multiple' => false,
                'empty_data' => TypePriceEnum::UNKNOWN->value,
                'placeholder' => false, // To prevent the 'None' choice, as it is handled through JavaScript
            ])
            ->add('fullPrice', MoneyType::class, [
                'required' => false,
                'divisor' => 100, // The price is stored in cent as an integer, or 0 for free, or null for unknown
                'input' => 'integer',
                'currency' => $this->currency,
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

        // Handling steam search with steamId parameter
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $game = $event->getData();
            $form = $event->getForm();

            $steamId = $options['steamId'];

            if (null != $steamId) {
                $steamIdError = null;
                $flashBag = $this->requestStack->getSession()->getFlashBag();

                if (!\ctype_digit($steamId)) {
                    $steamIdError = $this->trans->trans('game.error.steamId.invalid', [], 'validators');
                } else {
                    $this->steamSearch->fetchSteamGame((int) $steamId);
                    if (SteamSearchStatusEnum::OK === $this->steamSearch->getStatus()) {
                        $game = $this->steamSearch->fillGame($game);
                        $flashBag->add('livror/success', new FlashMessage('game.edit.flash.steamSearch.success'));
                    } elseif (SteamSearchStatusEnum::NOT_FOUND === $this->steamSearch->getStatus()) {
                        $steamIdError = $this->trans->trans('game.error.steamId.notFound', [], 'validators');
                    } else {
                        $flashBag->add('livror/danger', new FlashMessage('game.edit.flash.steamSearch.fail'));
                    }
                }

                null != $steamIdError ? $form->get('steamId')->addError(new FormError($steamIdError)) : null;
            }
        });

        // Convert to price to a type of price for the non-mapped choice field
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $game = $event->getData();
            $form = $event->getForm();

            $typePrice = TypePriceEnum::fromPrice($game->getFullPrice());
            $form->get('typePrice')->setData($typePrice);
            TypePriceEnum::PAYING !== $typePrice ? $form->get('fullPrice')->setData(null) : null;
        });

        // Convert the non-mapped choice-field to a (null or 0) t obe consistent accross the app
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $game = $event->getData();
            $form = $event->getForm();

            $game->setFullPrice(TypePriceEnum::toPrice($form->get('typePrice')->getData(), $game->getFullPrice()));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['steamId']);
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}

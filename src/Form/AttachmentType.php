<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Main\Attachment;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class AttachmentType extends DefaultType
{
    public function __construct(protected bool $htmlValidation)
    {
        parent::__construct(true);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', VichFileType::class, [
                'required' => true,
                'allow_delete' => false,
                'download_uri' => true,
                'download_label' => '',
            ])
            ->add('description', TextType::class, [
                'required' => true,
            ]);

        // Retrieve the filename and put it in the download label options for display
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $attachment = $event->getData();
            $form = $event->getForm();

            if (empty($attachment)) {
                return;
            }

            $fileOptions = $form->get('file')->getConfig()->getOptions();
            $fileOptions['download_label'] = $attachment->getFileMeta()->getName();
            $form->add('file', VichFileType::class, $fileOptions);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Attachment::class,
        ]);
    }
}

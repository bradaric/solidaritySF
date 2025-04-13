<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm', CheckboxType::class, [
                'label' => $options['message'] ?? 'none',
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['submit_message'] ?? 'Potvrdi',
                'attr' => [
                    'class' => $options['submit_class'] ?? 'btn btn-success',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'message' => null,
            'submit_message' => null,
            'submit_class' => null,
        ]);
    }
}

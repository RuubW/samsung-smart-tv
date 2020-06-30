<?php

namespace App\Form\Type;

use App\Form\RemoteForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RemoteType.
 *
 * @package App\Form\Type
 */
class RemoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('keys', ChoiceType::class, [
                'required' => true,
                'expanded' => false,
                'multiple' => true,
                'label' => 'remote.form.keys',
                'choices' => $options['choices'],
                'choice_label' => function ($choice, $key, $value) {
                    return strtoupper($choice);
                },
                'help' => 'remote.form.help'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'remote.form.send'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RemoteForm::class,
            'choices' => []
        ]);
    }
}

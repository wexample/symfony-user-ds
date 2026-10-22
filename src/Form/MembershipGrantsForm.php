<?php

namespace Wexample\SymfonyUserDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyUserDs\Traits\MembershipGrantsFormTrait;

/**
 * The grants of one member of a group.
 */
class MembershipGrantsForm extends AbstractForm
{
    use MembershipGrantsFormTrait;

    public static bool $ajax = true;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $this->configureGrantsOption($resolver);
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $this->builderAddGrants($builder, $options);
        $this->builderAddSubmit($builder);
    }
}

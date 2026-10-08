<?php

namespace Wexample\SymfonyUserDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Wexample\SymfonyForms\Form\AbstractForm;

/**
 * A member taken out of a group: a button, which the form's token keeps from
 * being clicked from another site.
 */
class MembershipRemoveForm extends AbstractForm
{
    public static bool $ajax = true;

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $this->builderAddSubmit($builder);
    }
}

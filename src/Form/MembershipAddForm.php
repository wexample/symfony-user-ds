<?php

namespace Wexample\SymfonyUserDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonySearch\Form\Type\EntitySearchInputType;
use Wexample\SymfonyUserDs\Traits\MembershipGrantsFormTrait;

/**
 * A member added to a group: found by searching — the member class carries
 * `#[Searchable]` —, then given its grants.
 */
class MembershipAddForm extends AbstractForm
{
    use MembershipGrantsFormTrait;

    public const string FIELD_MEMBER = 'member';

    public const string OPTION_MEMBER_CLASS = 'member_class';

    public static bool $ajax = true;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $this->configureGrantsOption($resolver);
        $resolver->setRequired(self::OPTION_MEMBER_CLASS);
        $resolver->setAllowedTypes(self::OPTION_MEMBER_CLASS, 'string');
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add(self::FIELD_MEMBER, EntitySearchInputType::class, [
            self::FIELD_OPTION_NAME_LABEL => true,
            self::FIELD_OPTION_NAME_REQUIRED => true,
            EntitySearchInputType::OPTION_CLASS => $options[self::OPTION_MEMBER_CLASS],
            'placeholder' => true,
        ]);
        $this->builderAddGrants($builder, $options);
        $this->builderAddSubmit($builder);
    }
}

<?php

namespace Wexample\SymfonyUserDs\Traits;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\SwitchInputType;

/**
 * The grants of a membership, one switch each, in the order the application
 * declared them. The label is the grant itself, as the roles form shows the
 * role: the application invented the word.
 */
trait MembershipGrantsFormTrait
{
    public const string OPTION_GRANTS = 'grants';

    public static function getGrantFieldName(string $grant): string
    {
        return 'grant_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($grant));
    }

    /**
     * @param list<string> $offered
     *
     * @return list<string>
     */
    public static function getSubmittedGrants(FormInterface $form, array $offered): array
    {
        return array_values(array_filter(
            $offered,
            static fn (string $grant) => $form->get(self::getGrantFieldName($grant))->getData() === true
        ));
    }

    protected function configureGrantsOption(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_GRANTS, []);
        $resolver->setAllowedTypes(self::OPTION_GRANTS, 'array');
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function builderAddGrants(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options[self::OPTION_GRANTS] as $grant) {
            $builder->add(self::getGrantFieldName($grant), SwitchInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => ucfirst($grant),
                self::FIELD_OPTION_NAME_REQUIRED => false,
                'translation_domain' => false,
            ]);
        }
    }
}

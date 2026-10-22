<?php

namespace Wexample\SymfonyUserDs\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyUserDs\Form\MembershipGrantsForm;

/**
 * The grants of the membership the url names.
 */
class MembershipGrantsFormProcessor extends AbstractMembershipFormProcessor
{
    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $this->setTarget($data);
        $offered = $this->grantHierarchy->getGrants();
        $held = $this->membership?->getGrants() ?? [];

        return parent::createForm(
            array_combine(
                array_map(MembershipGrantsForm::getGrantFieldName(...), $offered),
                array_map(static fn (string $grant) => in_array($grant, $held, true), $offered)
            ),
            $options + [MembershipGrantsForm::OPTION_GRANTS => $offered]
        );
    }

    protected function apply(FormInterface $form): void
    {
        $this->administration->changeGrants(
            $this->getActor(),
            $this->membership,
            MembershipGrantsForm::getSubmittedGrants($form, $this->grantHierarchy->getGrants())
        );
    }
}

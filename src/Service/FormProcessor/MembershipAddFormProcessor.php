<?php

namespace Wexample\SymfonyUserDs\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyUserDs\Form\MembershipAddForm;

/**
 * A member added to the group the url names.
 */
class MembershipAddFormProcessor extends AbstractMembershipFormProcessor
{
    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $this->setTarget($data);

        return parent::createForm(null, $options + [
            MembershipAddForm::OPTION_GRANTS => $this->grantHierarchy->getGrants(),
            MembershipAddForm::OPTION_MEMBER_CLASS => $this->targets->getManagerForGroup($this->group)->getMemberClass(),
        ]);
    }

    protected function apply(FormInterface $form): void
    {
        $this->administration->add(
            $this->getActor(),
            $this->group,
            $form->get(MembershipAddForm::FIELD_MEMBER)->getData(),
            MembershipAddForm::getSubmittedGrants($form, $this->grantHierarchy->getGrants())
        );
    }
}

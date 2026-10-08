<?php

namespace Wexample\SymfonyUserDs\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;

/**
 * The membership the url names, removed.
 */
class MembershipRemoveFormProcessor extends AbstractMembershipFormProcessor
{
    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $this->setTarget($data);

        return parent::createForm(null, $options);
    }

    protected function apply(FormInterface $form): void
    {
        $this->administration->remove($this->getActor(), $this->membership);
    }
}

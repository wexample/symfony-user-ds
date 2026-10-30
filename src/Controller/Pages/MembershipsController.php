<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\MembershipAdministrationService;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipGrantsFormProcessor;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipRemoveFormProcessor;
use Wexample\SymfonyUserDs\Service\MembershipTargetResolver;
use Wexample\SymfonyUserDs\Service\MembershipsScreenService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * The members of a group, for its members to read and its administrators to
 * change. The group is named `{kind}:{id}` — `team:0193…` —, the kind being a
 * MembershipManagerInterface of the application. Whoever is neither gets a
 * 404: the group is not theirs to know of.
 */
#[Route(path: '/memberships')]
#[IsGranted('IS_AUTHENTICATED')]
final class MembershipsController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    /**
     * The members screen, here for a kind of group no page of the application
     * hosts; one that does is sent there (HostsMembershipsInterface).
     */
    #[Route(path: '/{id}', name: UserRoute::MEMBERSHIPS)]
    public function index(
        string $id,
        MembershipTargetResolver $targets,
        MembershipsScreenService $screen,
    ): Response {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $group || $membership || ! $screen->mayRead($group)) {
            throw $this->createNotFoundException();
        }

        if ($targets->isHosted($group)) {
            return $this->redirect($targets->getMembershipsUrl($group));
        }

        return $this->renderPage('index', [
            'group' => $group,
        ]);
    }

    #[Route(path: '/{id}/member', name: UserRoute::MEMBERSHIP)]
    public function show(
        string $id,
        MembershipTargetResolver $targets,
        MembershipAdministrationService $administration,
        MembershipGrantsFormProcessor $grantsFormProcessor,
        MembershipRemoveFormProcessor $removeFormProcessor
    ): Response {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $membership || ! $administration->mayAdminister($this->getAbstractUser(), $group)) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage('show', [
            'group' => $group,
            'member_label' => $membership->getMember()->getMembershipLabel(),
            'membership_grants_form' => $grantsFormProcessor->createForm($membership)->createView(),
            'membership_remove_form' => $removeFormProcessor->createForm($membership)->createView(),
        ]);
    }

    private function getAbstractUser(): AbstractUser
    {
        $user = $this->getUser();

        if (! $user instanceof AbstractUser) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}

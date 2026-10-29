<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Interface\MemberInterface;
use Wexample\SymfonyUser\Interface\MembershipInterface;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\GrantHierarchyService;
use Wexample\SymfonyUser\Service\MembershipAdministrationService;
use Wexample\SymfonyUser\Service\MembershipService;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipAddFormProcessor;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipGrantsFormProcessor;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipRemoveFormProcessor;
use Wexample\SymfonyUserDs\Service\MembershipTargetResolver;
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

    #[Route(path: '/{id}', name: UserRoute::MEMBERSHIPS)]
    public function index(
        string $id,
        MembershipTargetResolver $targets,
        MembershipService $membershipService,
        MembershipAdministrationService $administration,
        GrantHierarchyService $grantHierarchy,
        MembershipAddFormProcessor $addFormProcessor
    ): Response {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $group || $membership) {
            throw $this->createNotFoundException();
        }

        $actor = $this->getAbstractUser();
        $administers = $administration->mayAdminister($actor, $group);

        if (! $administers && ! ($actor instanceof MemberInterface && $membershipService->isMember($actor, $group))) {
            throw $this->createNotFoundException();
        }

        $memberships = [...$group->getMemberships()];
        $order = array_flip($grantHierarchy->getGrants());
        // The highest grant first, then by name.
        usort($memberships, fn (MembershipInterface $a, MembershipInterface $b) => [$this->rank($a, $order), $a->getMember()->getMembershipLabel()]
            <=> [$this->rank($b, $order), $b->getMember()->getMembershipLabel()]);

        return $this->renderPage('index', [
            'group' => $group,
            'administers' => $administers,
            'rows' => array_map(fn (MembershipInterface $membership) => [
                'label' => $membership->getMember()->getMembershipLabel(),
                'grants' => $membership->getGrants(),
                'url' => $this->generateUrl(UserRoute::MEMBERSHIP, ['id' => $targets->getKey($group, $membership)]),
            ], $memberships),
            'membership_add_form' => $administers ? $addFormProcessor->createForm($group)->createView() : null,
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

    /**
     * @param array<string, int> $order
     */
    private function rank(MembershipInterface $membership, array $order): int
    {
        return min(array_map(static fn (string $grant) => $order[$grant] ?? PHP_INT_MAX, $membership->getGrants() ?: ['']));
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

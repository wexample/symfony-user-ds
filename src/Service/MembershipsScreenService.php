<?php

namespace Wexample\SymfonyUserDs\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Interface\MemberInterface;
use Wexample\SymfonyUser\Interface\MembershipGroupInterface;
use Wexample\SymfonyUser\Interface\MembershipInterface;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\GrantHierarchyService;
use Wexample\SymfonyUser\Service\MembershipAdministrationService;
use Wexample\SymfonyUser\Service\MembershipService;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipAddFormProcessor;

/**
 * The members screen of a group, wherever it is drawn: on the package's page,
 * or on the application's when its manager hosts them
 * (HostsMembershipsInterface). One place says who may read it and what it
 * shows; MembershipTargetResolver says where it lives.
 */
class MembershipsScreenService
{
    public function __construct(
        private readonly Security $security,
        private readonly MembershipService $membershipService,
        private readonly MembershipAdministrationService $administration,
        private readonly GrantHierarchyService $grantHierarchy,
        private readonly MembershipTargetResolver $targets,
        private readonly MembershipAddFormProcessor $addFormProcessor,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The signed-in account reads the members of $group: one of them, or one
     * administering it.
     */
    public function mayRead(MembershipGroupInterface $group): bool
    {
        $user = $this->security->getUser();

        if (! $user instanceof AbstractUser || ! $this->targets->getManagerForGroup($group)) {
            return false;
        }

        return ($user instanceof MemberInterface && $this->membershipService->isMember($user, $group))
            || $this->administration->mayAdminister($user, $group);
    }

    /**
     * What the screen shows to the signed-in account, or null when it may not
     * read it: the members, the highest grant first, and for an administrator
     * the way to change each and to add one.
     *
     * @return array{group: MembershipGroupInterface, administers: bool, rows: list<array{label: string, grants: list<string>, url: string, remove_url: string}>, membership_add_form: ?\Symfony\Component\Form\FormView}|null
     */
    public function build(MembershipGroupInterface $group): ?array
    {
        if (! $this->mayRead($group)) {
            return null;
        }

        $user = $this->security->getUser();
        $administers = $user instanceof AbstractUser && $this->administration->mayAdminister($user, $group);

        $memberships = [...$group->getMemberships()];
        $order = array_flip($this->grantHierarchy->getGrants());
        usort($memberships, fn (MembershipInterface $a, MembershipInterface $b) => [$this->rank($a, $order), $a->getMember()->getMembershipLabel()]
            <=> [$this->rank($b, $order), $b->getMember()->getMembershipLabel()]);

        return [
            'group' => $group,
            'administers' => $administers,
            'rows' => array_map(fn (MembershipInterface $membership) => [
                'label' => $membership->getMember()->getMembershipLabel(),
                'grants' => $membership->getGrants(),
                'url' => $this->urlGenerator->generate(UserRoute::MEMBERSHIP, ['id' => $this->targets->getKey($group, $membership)]),
                'remove_url' => $this->urlGenerator->generate(UserRoute::MEMBERSHIP_REMOVE, ['id' => $this->targets->getKey($group, $membership)]),
            ], $memberships),
            'membership_add_form' => $administers ? $this->addFormProcessor->createForm($group)->createView() : null,
        ];
    }

    /**
     * @param array<string, int> $order
     */
    private function rank(MembershipInterface $membership, array $order): int
    {
        return min(array_map(static fn (string $grant) => $order[$grant] ?? PHP_INT_MAX, $membership->getGrants() ?: ['']));
    }
}

<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUser\Interface\MemberInterface;
use Wexample\SymfonyUser\Interface\MembershipGroupInterface;
use Wexample\SymfonyUser\Service\MembershipService;
use Wexample\SymfonyUserDs\Service\MembershipTargetResolver;
use Wexample\SymfonyUserDs\Service\MembershipsScreenService;

class MembershipExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly MembershipService $membershipService,
        private readonly MembershipsScreenService $screen,
        private readonly MembershipTargetResolver $targets,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('memberships_url', $this->membershipsUrl(...)),
            new TwigFunction('user_memberships', $this->userMemberships(...)),
        ];
    }

    /**
     * The page listing the members of $group, for an account allowed to read
     * it: one of its members, or one administering it.
     */
    public function membershipsUrl(MembershipGroupInterface $group): ?string
    {
        return $this->screen->mayRead($group) ? $this->targets->getMembershipsUrl($group) : null;
    }

    /**
     * The groups of the signed-in account, each with the grants it holds
     * there — through the members it represents too — and its members page.
     *
     * @return list<array{label: string, grants: list<string>, url: ?string}>
     */
    public function userMemberships(): array
    {
        $user = $this->security->getUser();

        if (! $user instanceof MemberInterface) {
            return [];
        }

        return array_map(fn (MembershipGroupInterface $group) => [
            'label' => $group->getMembershipLabel(),
            'grants' => $this->getGivenGrants($user, $group),
            'url' => $this->membershipsUrl($group),
        ], $this->membershipService->getGroups($user));
    }

    /**
     * The grants given, without the ones they include: `administrator`, not
     * `administrator, contributor, watcher`.
     *
     * @return list<string>
     */
    private function getGivenGrants(MemberInterface $member, MembershipGroupInterface $group): array
    {
        $own = $this->membershipService->getMembership($member, $group)?->getGrants() ?? [];

        return $own ?: $this->membershipService->getGrants($member, $group);
    }
}

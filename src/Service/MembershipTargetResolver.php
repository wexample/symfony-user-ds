<?php

namespace Wexample\SymfonyUserDs\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyUser\Interface\MembershipGroupInterface;
use Wexample\SymfonyUser\Interface\MembershipInterface;
use Wexample\SymfonyUser\Interface\MembershipManagerInterface;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\MembershipManagerRegistry;
use Wexample\SymfonyUserDs\Interface\HostsMembershipsInterface;

/**
 * Which group — and which membership in it — a page or a form submission is
 * about, written in one url segment: `team:{group id}` or
 * `team:{group id}:{membership id}`. The kind comes first, so the group is
 * read from the manager declaring it and never from a class the url names.
 */
class MembershipTargetResolver
{
    private const string SEPARATOR = ':';

    public function __construct(
        private readonly MembershipManagerRegistry $managers,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The page listing the members of $group: the application's when its
     * manager hosts them (HostsMembershipsInterface), the package's otherwise.
     */
    public function getMembershipsUrl(MembershipGroupInterface $group): string
    {
        $manager = $this->managers->getForGroup($group);

        return $manager instanceof HostsMembershipsInterface
            ? $manager->getMembershipsUrl($group)
            : $this->urlGenerator->generate(UserRoute::MEMBERSHIPS, ['id' => $this->getKey($group)]);
    }

    /**
     * Whether an application page hosts the members of $group.
     */
    public function isHosted(MembershipGroupInterface $group): bool
    {
        return $this->managers->getForGroup($group) instanceof HostsMembershipsInterface;
    }

    public function getKey(MembershipGroupInterface $group, ?MembershipInterface $membership = null): string
    {
        $manager = $this->managers->getForGroup($group);

        return implode(self::SEPARATOR, array_filter([
            $manager::getName(),
            (string) $group->getId(),
            $membership ? (string) $membership->getId() : null,
        ]));
    }

    public function getManager(string $type): ?MembershipManagerInterface
    {
        return $this->managers->get($type);
    }

    public function getManagerForGroup(MembershipGroupInterface $group): ?MembershipManagerInterface
    {
        return $this->managers->getForGroup($group);
    }

    public function findGroup(string $type, string $id): ?MembershipGroupInterface
    {
        $manager = $this->managers->get($type);

        return $manager ? $this->managers->findGroup($manager, $id) : null;
    }

    /**
     * @return array{MembershipGroupInterface, ?MembershipInterface}|null
     */
    public function resolve(string $key): ?array
    {
        $parts = explode(self::SEPARATOR, $key);

        if (count($parts) < 2 || ! $group = $this->findGroup($parts[0], $parts[1])) {
            return null;
        }

        if (! isset($parts[2])) {
            return [$group, null];
        }

        $membership = $this->findMembership($group, $parts[2]);

        return $membership ? [$group, $membership] : null;
    }

    public function findMembership(MembershipGroupInterface $group, string $id): ?MembershipInterface
    {
        foreach ($group->getMemberships() as $membership) {
            if ((string) $membership->getId() === $id) {
                return $membership;
            }
        }

        return null;
    }
}

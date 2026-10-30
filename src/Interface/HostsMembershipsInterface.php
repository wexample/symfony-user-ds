<?php

namespace Wexample\SymfonyUserDs\Interface;

use Wexample\SymfonyUser\Interface\MembershipGroupInterface;

/**
 * A membership manager whose groups list their members on a page of the
 * application — `/projects/{id}/members`, in the project's layout — rather
 * than on the package's `/memberships/{kind}:{id}`. That page draws them with
 * `memberships(group)`; the package's links and its forms, once they changed
 * a member, lead there.
 */
interface HostsMembershipsInterface
{
    /**
     * The application's page listing the members of $group.
     */
    public function getMembershipsUrl(MembershipGroupInterface $group): string;
}

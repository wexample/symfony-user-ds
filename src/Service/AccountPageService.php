<?php

namespace Wexample\SymfonyUserDs\Service;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyActivity\Repository\ActivityRepository;
use Wexample\SymfonyUserDs\Controller\Pages\ImpersonationController;
use Wexample\SymfonyUser\Controller\AccountActionController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Enum\AccountAction;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountAdministrationService;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\ImpersonationService;

/**
 * What every view of one account in the administration needs, whichever
 * package draws it (`pages/accounts/layout/account`): who may see it, and the
 * account, the actions its state allows and their token.
 */
class AccountPageService
{
    public function __construct(
        private readonly AccountAdministrationService $administration,
        private readonly AccountDirectoryService $directory,
        private readonly ImpersonationService $impersonation,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?ActivityRepository $activityRepository = null,
    ) {
    }

    /**
     * 404 where nobody administers, 403 where this one does not: the first
     * hides a feature the application did not ask for, the second refuses a
     * page that is there.
     */
    public function assertMayAdminister(): void
    {
        if (! $this->administration->getPageRole()) {
            throw new NotFoundHttpException();
        }

        if (! $this->administration->canAdminister()) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * The account the page names, once its viewer may administer it.
     *
     * @throws NotFoundHttpException when no account has this id
     */
    public function findAdministered(string $id): AbstractUser
    {
        $this->assertMayAdminister();

        return $this->directory->find($id) ?? throw new NotFoundHttpException();
    }

    /**
     * The parameters of the account's frame.
     *
     * `impersonate_url`: where to post — signed with `impersonate_token_id` —
     * to take the account at once, for one who may; null otherwise.
     *
     * @return array{account: array, actions: list<AccountAction>, csrf_token_id: string, has_activity: bool, impersonate_url: ?string, impersonate_token_id: string}
     */
    public function getFrameParameters(AbstractUser $account): array
    {
        return [
            'account' => $this->directory->describeAccount($account),
            'actions' => array_values(array_filter(
                AccountAction::cases(),
                static fn (AccountAction $action) => $action->suits($account->isEnabled(), $account->isLocked())
            )),
            'csrf_token_id' => AccountActionController::CSRF_TOKEN_ID,
            'has_activity' => null !== $this->activityRepository,
            'impersonate_url' => $this->impersonation->mayTake($account)
                ? $this->urlGenerator->generate(UserRoute::IMPERSONATE_ACCOUNT, ['identifier' => $account->getUserIdentifier()])
                : null,
            'impersonate_token_id' => ImpersonationController::CSRF_ACCOUNT,
        ];
    }
}

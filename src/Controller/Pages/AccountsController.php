<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyActivity\Class\ActivitySubject;
use Wexample\SymfonyActivity\Repository\ActivityRepository;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Controller\AccountActionController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Enum\AccountAction;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountAdministrationService;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\AccountCreateFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\AccountRolesFormProcessor;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * The accounts an administrator opens, finds, and acts on. Absent where the
 * application declared no `administration.page_role` — the pages answer 404,
 * as the impersonation page does without `switch_user` —, refused to whoever
 * lacks that role.
 *
 * What each administrator may then do is AccountAdministrationService's: the
 * roles they administer, the accounts their guards allow, never themselves.
 */
#[Route(path: '/accounts')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AccountsController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    private const int PER_PAGE = 25;

    /** The entries the account page shows of its history. */
    private const int RECENT_ACTIVITY = 10;

    #[Route(path: '', name: UserRoute::ACCOUNTS)]
    public function index(
        Request $request,
        AccountAdministrationService $administration,
        AccountDirectoryService $directory,
        AccountCreateFormProcessor $createFormProcessor
    ): Response {
        $this->assertMayAdminister($administration);

        $query = trim((string) $request->query->get('q'));
        $page = max(1, $request->query->getInt('page', 1));
        $builder = $directory->createQuery();

        // Too short to search with: the whole list, rather than none of it —
        // an administrator who typed one letter has not asked for nothing.
        $searching = $query !== '' && $directory->applySearch($builder, $query);

        $found = $directory->paginate($builder, $page, self::PER_PAGE);

        return $this->renderPage('index', [
            'query' => $query,
            'searching' => $searching,
            'search_min_length' => AccountDirectoryService::SEARCH_MIN_LENGTH,
            'count' => $found['count'],
            'page' => $page,
            'pages_count' => $found['pages_count'],
            'rows' => array_map(
                fn (AbstractUser $account) => $this->describe($account, $directory),
                $found['accounts']
            ),
            'account_create_form' => $createFormProcessor->createForm()->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: UserRoute::ACCOUNT)]
    public function show(
        string $id,
        Request $request,
        AccountAdministrationService $administration,
        AccountDirectoryService $directory,
        AccountRolesFormProcessor $rolesFormProcessor,
        ?ActivityRepository $activityRepository = null
    ): Response {
        $this->assertMayAdminister($administration);

        if (! $account = $directory->find($id)) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage('show', [
            // Null where symfony-activity is not installed: no history block.
            'activity' => $activityRepository?->findRecent($this->subject($account), self::RECENT_ACTIVITY),
            'account' => $this->describe($account, $directory),
            'actions' => array_values(array_filter(
                AccountAction::cases(),
                static fn (AccountAction $action) => $action->suits($account->isEnabled(), $account->isLocked())
            )),
            'csrf_token_id' => AccountActionController::CSRF_TOKEN_ID,
            'done' => $request->query->get(AccountActionController::PARAMETER_DONE),
            'refused' => $request->query->get(AccountActionController::PARAMETER_REFUSED),
            'account_roles_form' => $rolesFormProcessor->createForm($account)->createView(),
        ]);
    }

    /**
     * The whole history of one account, a page at a time, narrowed to one
     * category. There only where symfony-activity is installed.
     */
    #[Route(path: '/{id}/activity', name: UserRoute::ACCOUNT_ACTIVITY, methods: [Request::METHOD_GET])]
    public function activity(
        string $id,
        Request $request,
        AccountAdministrationService $administration,
        AccountDirectoryService $directory,
        ?ActivityRepository $activityRepository = null
    ): Response {
        $this->assertMayAdminister($administration);

        if (! $activityRepository || ! $account = $directory->find($id)) {
            throw $this->createNotFoundException();
        }

        $subject = $this->subject($account);
        $categories = $activityRepository->findSubjectCategories($subject);
        $category = $request->query->get('category');
        $category = in_array($category, $categories, true) ? $category : null;
        $page = max(1, $request->query->getInt('page', 1));

        return $this->renderPage('activity', [
            'account' => $this->describe($account, $directory),
            'categories' => $categories,
            'category' => $category,
            'page' => $page,
            ...$activityRepository->paginate($subject, $category, $page, self::PER_PAGE),
        ]);
    }

    /**
     * Whose history it is, as symfony-activity names an entity: its class
     * and its id.
     */
    private function subject(AbstractUser $account): ActivitySubject
    {
        return new ActivitySubject($account::class, (string) $account->getId());
    }

    /**
     * 404 where nobody administers, 403 where this one does not: the first
     * hides a feature the application did not ask for, the second refuses a
     * page that is there.
     */
    private function assertMayAdminister(AccountAdministrationService $administration): void
    {
        if (! $administration->getPageRole()) {
            throw $this->createNotFoundException();
        }

        if (! $administration->canAdminister()) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * What a row and a page show of an account: what the directory already
     * tells of one — its label, its address, its roles —, and the state only
     * an administrator is shown.
     *
     * @return array<string, mixed>
     */
    private function describe(AbstractUser $account, AccountDirectoryService $directory): array
    {
        return $directory->describe($account) + [
            'id' => (string) $account->getId(),
            'initials' => method_exists($account, 'getInitials') ? $account->getInitials() : null,
            'enabled' => $account->isEnabled(),
            'locked' => $account->isLocked(),
            // No password yet: the account is open and its holder has not
            // followed the activation mail.
            'activated' => $account->getPassword() !== null,
            'password_change_required' => $account->isPasswordChangeRequired(),
            'date_last_login' => $account->getDateLastLogin(),
        ];
    }
}

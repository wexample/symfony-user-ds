<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyActivity\Class\ActivitySubject;
use Wexample\SymfonyActivity\Repository\ActivityRepository;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Controller\AccountActionController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Enum\AccountAction;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountAdministrationService;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\AccountCreateFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\AccountEditFormProcessor;
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

    /**
     * The list itself is the account table's, read from `/_api/user/list`.
     */
    #[Route(path: '', name: UserRoute::ACCOUNTS)]
    public function index(AccountAdministrationService $administration): Response
    {
        $this->assertMayAdminister($administration);

        return $this->renderPage('index');
    }

    /**
     * Opening an account, a page of its own — the list opens it in a modal.
     * Declared before `/{id}`, which would read `create` as an id.
     */
    #[Route(path: '/create', name: UserRoute::ACCOUNT_CREATE, priority: 1)]
    public function create(
        AccountAdministrationService $administration,
        AccountCreateFormProcessor $createFormProcessor
    ): Response {
        $this->assertMayAdminister($administration);

        return $this->renderPage('create', [
            'account_create_form' => $createFormProcessor->createForm()->createView(),
        ]);
    }

    // Exposed: the accounts table draws the link to each row's page.
    #[Route(path: '/{id}', name: UserRoute::ACCOUNT, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
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
     * What one account is called — its address, its username, its name —, a
     * page of its own the account's page opens in a modal.
     */
    #[Route(path: '/{id}/edit', name: UserRoute::ACCOUNT_EDIT, methods: [Request::METHOD_GET])]
    public function edit(
        string $id,
        AccountAdministrationService $administration,
        AccountDirectoryService $directory,
        AccountEditFormProcessor $editFormProcessor
    ): Response {
        $this->assertMayAdminister($administration);

        if (! $account = $directory->find($id)) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage('edit', [
            'account' => $this->describe($account, $directory),
            'account_edit_form' => $editFormProcessor->createForm($account)->createView(),
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
            'username' => $account->getUsername(),
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

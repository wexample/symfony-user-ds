<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\EventSubscriber\ImpersonationGuardSubscriber;
use Wexample\SymfonyUser\Form\ImpersonateForm;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\ImpersonateFormProcessor;
use Wexample\SymfonyUser\Service\ImpersonationService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * Choose an account to impersonate: every one when they are few, a search
 * otherwise. Absent where the firewall has no `switch_user`; refused to
 * whoever lacks its role. While impersonating, the next account, or the way
 * back. One account taken from its own page, too.
 */
#[Route(path: '/account/impersonate')]
#[IsGranted('IS_AUTHENTICATED')]
final class ImpersonationController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    // What the account page's action is signed with.
    public const string CSRF_ACCOUNT = 'impersonate_account';

    #[Route(path: '', name: UserRoute::IMPERSONATE)]
    public function index(
        Request $request,
        ImpersonationService $impersonation,
        AccountDirectoryService $directory,
        ImpersonateFormProcessor $formProcessor
    ): Response {
        if (! $config = $impersonation->getSwitchUserConfig()) {
            throw $this->createNotFoundException();
        }

        if (! $impersonation->canImpersonate()) {
            $exception = $this->createAccessDeniedException();
            $exception->setAttributes(ImpersonationGuardSubscriber::ATTRIBUTE);

            throw $exception;
        }

        // While impersonating, the account behind chooses: the next switch
        // leaves the current one first.
        $actor = $impersonation->getActor();
        $current = $this->getUser();
        $query = trim((string) $request->query->get('q'));
        $targets = $impersonation->listTargets($actor);
        $searching = $targets === null;

        if ($searching) {
            $targets = $query !== '' ? $impersonation->searchTargets($actor, $query) : [];
        }

        $choices = $directory->toChoices(array_filter(
            $targets,
            static fn ($target) => $target->getUserIdentifier() !== $current->getUserIdentifier()
        ));

        // Back to the account behind, on a page it may open — not this one,
        // which the way back would open again in place of the account's own.
        $exitUrl = $impersonation->getExitUrl($request);

        return $this->renderPage('index', [
            'exit_url' => $exitUrl,
            'searching' => $searching,
            'query' => $query,
            'search_min_length' => ImpersonationService::SEARCH_MIN_LENGTH,
            'count' => count($choices),
            'impersonate_form' => $choices
                ? $formProcessor->createForm(null, [
                    ImpersonateForm::OPTION_TARGETS => $choices,
                    ImpersonateForm::OPTION_EXIT_URL => $exitUrl,
                ])->createView()
                : null,
        ]);
    }

    /**
     * One account taken at once, from its own page: allowed as the form
     * allows it, the intent kept, the browser sent through the firewall's
     * switch to the page that account lands on.
     */
    #[Route(path: '/{identifier}', name: UserRoute::IMPERSONATE_ACCOUNT, methods: ['POST'])]
    public function account(
        string $identifier,
        Request $request,
        ImpersonationService $impersonation,
    ): Response {
        if (! $impersonation->getSwitchUserConfig()) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid(self::CSRF_ACCOUNT, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $actor = $impersonation->getActor();
        $target = $actor && $impersonation->canImpersonate() ? $impersonation->findTarget($actor, $identifier) : null;

        if (! $target) {
            throw $this->createAccessDeniedException();
        }

        $impersonation->grantIntent($target);

        return $this->redirect($impersonation->getSwitchUrl($request, $target));
    }
}

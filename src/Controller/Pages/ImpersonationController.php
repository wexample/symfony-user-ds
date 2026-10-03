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
use Wexample\SymfonyUser\Service\FormProcessor\ImpersonateFormProcessor;
use Wexample\SymfonyUser\Service\ImpersonationService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * Choose an account to impersonate: every one when they are few, a search
 * otherwise. Absent where the firewall has no `switch_user`; refused to
 * whoever lacks its role. While impersonating, the next account, or the way
 * back.
 */
#[Route(path: '/account/impersonate')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ImpersonationController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '', name: UserRoute::IMPERSONATE)]
    public function index(
        Request $request,
        ImpersonationService $impersonation,
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

        $choices = [];
        foreach ($targets as $target) {
            if ($target->getUserIdentifier() === $current->getUserIdentifier()) {
                continue;
            }

            $description = $impersonation->describe($target);
            $choices[$description['label'] . ($description['roles'] ? ' (' . implode(', ', $description['roles']) . ')' : '')] = $description['identifier'];
        }

        return $this->renderPage('index', [
            'exit_url' => $this->isGranted('IS_IMPERSONATOR')
                ? $this->generateUrl(UserRoute::IMPERSONATE, [$config['parameter'] => '_exit'])
                : null,
            'searching' => $searching,
            'query' => $query,
            'search_min_length' => ImpersonationService::SEARCH_MIN_LENGTH,
            'count' => count($choices),
            'impersonate_form' => $choices
                ? $formProcessor->createForm(null, [ImpersonateForm::OPTION_TARGETS => $choices])->createView()
                : null,
        ]);
    }
}

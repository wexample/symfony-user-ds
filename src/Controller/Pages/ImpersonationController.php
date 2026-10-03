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
 * whoever lacks its role. While impersonating, the way back.
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

        if ($this->isGranted('IS_IMPERSONATOR')) {
            return $this->renderPage('index', [
                'impersonating' => true,
                'exit_url' => $request->getBasePath() . '/?' . http_build_query([$config['parameter'] => '_exit']),
            ]);
        }

        if (! $impersonation->canImpersonate()) {
            $exception = $this->createAccessDeniedException();
            $exception->setAttributes(ImpersonationGuardSubscriber::ATTRIBUTE);

            throw $exception;
        }

        $actor = $this->getUser();
        $query = trim((string) $request->query->get('q'));
        $targets = $impersonation->listTargets($actor);
        $searching = $targets === null;

        if ($searching) {
            $targets = $query !== '' ? $impersonation->searchTargets($actor, $query) : [];
        }

        $choices = [];
        foreach ($targets as $target) {
            $description = $impersonation->describe($target);
            $choices[$description['label'] . ($description['roles'] ? ' (' . implode(', ', $description['roles']) . ')' : '')] = $description['identifier'];
        }

        return $this->renderPage('index', [
            'impersonating' => false,
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

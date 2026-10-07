<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\ActivationProgressService;
use Wexample\SymfonyUser\Service\FormProcessor\PasswordResetRequestFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\SetPasswordFormProcessor;
use Wexample\SymfonyUser\Service\PasswordResetService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

#[Route(path: '/password/')]
final class PasswordController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: 'forgot', name: UserRoute::PASSWORD_FORGOT)]
    public function forgot(
        Request $request,
        PasswordResetRequestFormProcessor $processor
    ): Response {
        return $this->renderPage('forgot', [
            'link_invalid' => $request->query->get(UserRoute::PARAMETER_LINK) === UserRoute::LINK_INVALID,
            'password_reset_request_form' => $processor->createForm()->createView(),
        ]);
    }

    /**
     * Where a dead activation link leads: it expired, or the account is
     * already activated.
     */
    #[Route(path: 'activation-invalid', name: UserRoute::PASSWORD_ACTIVATION_INVALID)]
    public function activationInvalid(): Response
    {
        return $this->renderPage('activation_invalid');
    }

    #[Route(path: 'new', name: UserRoute::PASSWORD_NEW)]
    public function new(
        PasswordResetService $passwordResetService,
        SetPasswordFormProcessor $processor,
        ActivationProgressService $activationProgress
    ): Response {
        if (! $user = $passwordResetService->getProofUser()) {
            return $this->redirectToRoute(UserRoute::PASSWORD_FORGOT);
        }

        return $this->renderPage('new', [
            'user' => $user,
            // No proof in session, yet a user to set a password for: the
            // account owes the change, and has not asked for it.
            'forced' => ! $passwordResetService->hasProof(),
            'activation_steps' => $activationProgress->steps(ActivationProgressService::STEP_PASSWORD),
            'set_password_form' => $processor->createForm()->createView(),
        ]);
    }
}

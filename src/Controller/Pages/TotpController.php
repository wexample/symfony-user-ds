<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\FormProcessor\TotpDisableFormProcessor;
use Wexample\SymfonyUser\Service\TotpService;
use Wexample\SymfonyUser\Service\TwoFactorPolicyService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * The authenticator app of the signed-in user: get backup codes, turn it off.
 * New codes are made by TotpBackupCodesController, in symfony-user.
 * Setting it up is a tunnel of its own (TotpSetupTunnelController).
 */
#[Route(path: '/account/authenticator')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TotpController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '', name: UserRoute::TOTP)]
    public function index(
        TwoFactorPolicyService $policy,
        TotpDisableFormProcessor $disableFormProcessor
    ): Response {
        $user = $this->getAbstractUser();
        $required = $policy->requiresApp($user);

        if ($user->isTotpAuthenticationEnabled()) {
            return $this->renderPage('index', [
                'enabled' => true,
                'required' => $required,
                'backup_codes_count' => $user->countBackupCodes(),
                'totp_disable_form' => $required ? null : $disableFormProcessor->createForm()->createView(),
            ]);
        }

        // Not set up yet: the setup tunnel, step by step.
        return $this->redirectToRoute(UserRoute::TOTP_SETUP);
    }

    /**
     * Shown once: the codes are not kept in clear anywhere.
     */
    #[Route(path: '/backup-codes', name: UserRoute::TOTP_BACKUP_CODES)]
    public function backupCodes(TotpService $totpService): Response
    {
        if (! $codes = $totpService->pullNewBackupCodes()) {
            return $this->redirectToRoute(UserRoute::TOTP);
        }

        return $this->renderPage('backup_codes', ['codes' => $codes]);
    }


    private function getAbstractUser(): AbstractUser
    {
        $user = $this->getUser();

        if (! $user instanceof AbstractUser) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}

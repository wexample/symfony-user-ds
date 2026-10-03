<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Form\TwoFactorCodeForm;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\ActivationProgressService;
use Wexample\SymfonyUser\Service\FormProcessor\TwoFactorCodeFormProcessor;
use Wexample\SymfonyUser\Service\TwoFactorCodeService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * The `auth_form_path` of the firewall: where a login waits for its code.
 */
#[Route(path: '/login/2fa')]
final class TwoFactorController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '', name: UserRoute::TWO_FACTOR)]
    public function form(
        Request $request,
        TokenStorageInterface $tokenStorage,
        TwoFactorCodeFormProcessor $formProcessor,
        TwoFactorCodeService $codeService,
        ActivationProgressService $activationProgress
    ): Response {
        if (! $user = $this->getPendingUser($tokenStorage)) {
            return $this->redirectToRoute(UserRoute::LOGIN);
        }

        $backupCode = $request->query->getBoolean('backup');
        $form = $formProcessor->createForm(null, [TwoFactorCodeForm::OPTION_BACKUP_CODE => $backupCode]);

        if ($request->query->has('failed')) {
            $formProcessor->addFailure($form, $codeService->getLastFailure());
        }

        return $this->renderPage('index', [
            'email' => $user->getEmail(),
            'method' => $backupCode ? 'backup_code' : $tokenStorage->getToken()->getCurrentTwoFactorProvider(),
            'has_backup_codes' => $user->countBackupCodes() > 0,
            'can_resend' => $codeService->canResend(),
            'two_factor_code_form' => $form->createView(),
            'activation_steps' => $activationProgress->steps(ActivationProgressService::STEP_CODE),
        ]);
    }


    private function getPendingUser(TokenStorageInterface $tokenStorage): ?AbstractUser
    {
        $token = $tokenStorage->getToken();
        $user = $token instanceof TwoFactorTokenInterface ? $token->getUser() : null;

        return $user instanceof AbstractUser ? $user : null;
    }
}

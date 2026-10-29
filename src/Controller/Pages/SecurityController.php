<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Form\LoginForm;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\FormProcessor\LoginFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\MagicLinkRequestFormProcessor;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

final class SecurityController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '/login', name: UserRoute::LOGIN)]
    public function login(
        Request $request,
        LoginFormProcessor $loginFormProcessor,
        MagicLinkRequestFormProcessor $magicLinkRequestFormProcessor,
        AuthenticationUtils $authenticationUtils,
        Security $security
    ): Response {
        // Signed in by the remember-me cookie alone, and sent here by a page
        // that asks for a password typed in this session — the account's own
        // pages, the administration: not signed out, asked to confirm.
        $confirming = $security->isGranted('IS_REMEMBERED');

        $form = $loginFormProcessor->createForm([
            LoginForm::FIELD_IDENTIFIER => $authenticationUtils->getLastUsername()
                ?: ($confirming ? $this->getRememberedIdentifier($security) : null),
        ]);

        if ($error = $authenticationUtils->getLastAuthenticationError()) {
            $loginFormProcessor->addAuthenticationError($form, $error);
        }

        return $this->renderPage('login', [
            'session_expired' => $request->query->get(UserRoute::PARAMETER_SESSION) === UserRoute::SESSION_EXPIRED,
            'confirming' => $confirming,
            'login_form' => $form->createView(),
            'magic_link_request_form' => $magicLinkRequestFormProcessor->isEnabled()
                ? $magicLinkRequestFormProcessor->createForm()->createView()
                : null,
        ]);
    }

    /**
     * What the remembered account signs in with: its username when it has
     * one — what its holder types —, its address otherwise.
     */
    private function getRememberedIdentifier(Security $security): ?string
    {
        $user = $security->getUser();

        if (! $user instanceof AbstractUser) {
            return null;
        }

        return $user->getUsername() ?: $user->getEmail();
    }
}

<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
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
        AuthenticationUtils $authenticationUtils
    ): Response {
        $form = $loginFormProcessor->createForm([
            LoginForm::FIELD_IDENTIFIER => $authenticationUtils->getLastUsername(),
        ]);

        if ($error = $authenticationUtils->getLastAuthenticationError()) {
            $loginFormProcessor->addAuthenticationError($form, $error);
        }

        return $this->renderPage('login', [
            'session_expired' => $request->query->get(UserRoute::PARAMETER_SESSION) === UserRoute::SESSION_EXPIRED,
            'login_form' => $form->createView(),
            'magic_link_request_form' => $magicLinkRequestFormProcessor->isEnabled()
                ? $magicLinkRequestFormProcessor->createForm()->createView()
                : null,
        ]);
    }
}

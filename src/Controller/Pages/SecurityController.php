<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Form\LoginForm;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\FormProcessor\LoginFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\MagicLinkRequestFormProcessor;
use Wexample\SymfonyUser\Service\PostLoginTargetService;
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
        Security $security,
        PostLoginTargetService $postLoginTarget
    ): Response {
        $token = $security->getToken();

        // A password waiting for its code goes on with the code.
        if ($token instanceof TwoFactorTokenInterface) {
            return $this->redirectToRoute(UserRoute::TWO_FACTOR);
        }

        // Signed in — by the form or by the cookie alike —, there is nothing
        // to do here: where a sign-in would have led.
        if ($token && $security->isGranted('IS_AUTHENTICATED')) {
            return $this->redirect($postLoginTarget->getTargetUrl(
                $request,
                $token,
                $security->getFirewallConfig($request)?->getName() ?? 'main'
            ));
        }

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

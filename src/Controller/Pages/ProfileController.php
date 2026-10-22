<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Repository\TermsAcceptanceRepository;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\ChangePasswordFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\ProfileFormProcessor;
use Wexample\SymfonyUser\Service\TrustedDeviceService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * What the account holder reads and changes about itself: its name, its
 * language, its password, the devices it trusted, and the terms it accepted.
 * The authenticator app has a page of its own (TotpController), linked from
 * here; the email is shown and not changed, being the identifier it signs in
 * with.
 *
 * The entry leading here is the application's to add, in the `items` of the
 * user menu.
 */
#[Route(path: '/account')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    public const string ROUTE_REVOKE_DEVICES = 'user_profile_revoke_devices';

    private const string CSRF_REVOKE_DEVICES = 'profile-revoke-devices';

    #[Route(path: '', name: UserRoute::PROFILE)]
    public function index(
        AccountDirectoryService $accountDirectory,
        ProfileFormProcessor $profileFormProcessor,
        ChangePasswordFormProcessor $changePasswordFormProcessor,
        TermsAcceptanceRepository $termsAcceptanceRepository
    ): Response {
        $user = $this->getAbstractUser();

        return $this->renderPage('index', [
            'user' => $user,
            // Read here rather than in the template: a user class without
            // UserWithNameTrait has no initials to show.
            'initials' => method_exists($user, 'getInitials') ? $user->getInitials() : null,
            'roles' => $accountDirectory->describe($user)['roles'],
            'profile_form' => $profileFormProcessor->hasFields()
                ? $profileFormProcessor->createForm()->createView()
                : null,
            'change_password_form' => $changePasswordFormProcessor->createForm()->createView(),
            'terms_history' => $termsAcceptanceRepository->findHistory($user),
        ]);
    }

    /**
     * Every device trusted so far asks for a code again at its next sign-in.
     */
    #[Route(path: '/revoke-devices', name: self::ROUTE_REVOKE_DEVICES, methods: [Request::METHOD_POST])]
    public function revokeDevices(
        Request $request,
        TrustedDeviceService $trustedDevices
    ): RedirectResponse {
        if (! $this->isCsrfTokenValid(self::CSRF_REVOKE_DEVICES, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $trustedDevices->revokeAll($this->getAbstractUser());

        return $this->redirectToRoute(UserRoute::PROFILE);
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

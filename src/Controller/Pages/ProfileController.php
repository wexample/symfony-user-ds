<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\ChangePasswordFormProcessor;
use Wexample\SymfonyUser\Service\FormProcessor\ProfileFormProcessor;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * What the account holder reads and changes about itself: its name, its
 * language, its password. The authenticator app and the trusted devices
 * have a page of their own (TotpController), linked from here; the email is
 * shown and not changed, being the identifier it signs in with.
 *
 * The entry leading here is the application's to add, in the `items` of the
 * user menu.
 */
#[Route(path: '/account')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '', name: UserRoute::PROFILE)]
    public function index(
        AccountDirectoryService $accountDirectory,
        ProfileFormProcessor $profileFormProcessor,
        ChangePasswordFormProcessor $changePasswordFormProcessor
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
        ]);
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

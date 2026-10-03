<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Form\ImpersonateForm;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\AccountPickerService;
use Wexample\SymfonyUser\Service\FormProcessor\AccountPickerFormProcessor;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * Sign in by choosing an account: every one when they are few, a search
 * otherwise. Absent unless the firewall lists AccountPickerAuthenticator.
 */
final class AccountPickerController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    #[Route(path: '/login/accounts', name: UserRoute::ACCOUNT_PICKER)]
    public function index(
        Request $request,
        AccountPickerService $accountPicker,
        AccountDirectoryService $directory,
        AccountPickerFormProcessor $formProcessor
    ): Response {
        if (! $accountPicker->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $query = trim((string) $request->query->get('q'));
        $accounts = $accountPicker->listAccounts();
        $searching = $accounts === null;

        if ($searching) {
            $accounts = $query !== '' ? $accountPicker->searchAccounts($query) : [];
        }

        $choices = $directory->toChoices($accounts);

        return $this->renderPage('index', [
            'searching' => $searching,
            'query' => $query,
            'search_min_length' => AccountDirectoryService::SEARCH_MIN_LENGTH,
            'account_picker_form' => $choices
                ? $formProcessor->createForm(null, [ImpersonateForm::OPTION_TARGETS => $choices])->createView()
                : null,
        ]);
    }
}

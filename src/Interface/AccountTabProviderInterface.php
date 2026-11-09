<?php

namespace Wexample\SymfonyUserDs\Interface;

/**
 * A package adding pages of its own to an account's page in the
 * administration — its subscription —: each as a tab after the account's own
 * (`account_tabs(account)`). Its pages extend
 * `pages/accounts/layout/account.html.twig`, with what AccountPageService
 * gives them.
 */
interface AccountTabProviderInterface
{
    public const string TAG = 'wexample_symfony_user_ds.account_tab_provider';

    /**
     * The tabs for $account — as AccountDirectoryService::describeAccount()
     * gives it —, as `page_tabs()` reads them: `label` (translated), `route`,
     * `params`, `icon`.
     *
     * @return list<array{label: string, route: string, params?: array, icon?: string}>
     */
    public function getAccountTabs(array $account): array;
}

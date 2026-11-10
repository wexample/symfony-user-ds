<?php

namespace Wexample\SymfonyUserDs\Interface;

/**
 * A package adding what can be done with an account from its page — write to
 * its holder —: each as an action at the far end of the page's tabs, after the
 * account's own (`account_actions(account)`).
 */
interface AccountActionProviderInterface
{
    public const string TAG = 'wexample_symfony_user_ds.account_action_provider';

    /**
     * The actions for $account — as AccountDirectoryService::describeAccount()
     * gives it —, as `page_tabs()` reads them: `label` (translated), `href`,
     * `icon`, and `method`, `token`, `target`, `confirm` where they apply.
     *
     * @return list<array<string, mixed>>
     */
    public function getAccountActions(array $account): array;
}

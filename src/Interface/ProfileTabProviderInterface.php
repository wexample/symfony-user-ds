<?php

namespace Wexample\SymfonyUserDs\Interface;

/**
 * A package adding pages of its own to the signed-in account's profile — its
 * api tokens, its subscription —: each as a tab after the profile's own
 * (`profile_tabs()`), in the layout its pages extend.
 */
interface ProfileTabProviderInterface
{
    public const string TAG = 'wexample_symfony_user_ds.profile_tab_provider';

    /**
     * The tabs, as `page_tabs()` reads them: `label` (translated), `route`,
     * `icon`, `match` (the route prefixes the tab stays active under).
     * None when the account has nothing to see there.
     *
     * @return list<array{label: string, route: string, icon?: string, match?: list<string>}>
     */
    public function getProfileTabs(): array;
}

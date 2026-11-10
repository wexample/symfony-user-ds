<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUserDs\Interface\AccountActionProviderInterface;
use Wexample\SymfonyUserDs\Interface\AccountTabProviderInterface;
use Wexample\SymfonyUserDs\Interface\ProfileTabProviderInterface;

/**
 * The tabs other packages add to the profile and to an account's page, after
 * their own, and the actions they add to an account's page.
 */
class TabProvidersExtension extends AbstractExtension
{
    /**
     * @param iterable<ProfileTabProviderInterface> $profileProviders
     * @param iterable<AccountTabProviderInterface> $accountProviders
     * @param iterable<AccountActionProviderInterface> $accountActionProviders
     */
    public function __construct(
        #[AutowireIterator(ProfileTabProviderInterface::TAG)]
        private readonly iterable $profileProviders,
        #[AutowireIterator(AccountTabProviderInterface::TAG)]
        private readonly iterable $accountProviders,
        #[AutowireIterator(AccountActionProviderInterface::TAG)]
        private readonly iterable $accountActionProviders,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('profile_tabs', $this->profileTabs(...)),
            new TwigFunction('account_tabs', $this->accountTabs(...)),
            new TwigFunction('account_actions', $this->accountActions(...)),
        ];
    }

    public function profileTabs(): array
    {
        $tabs = [];

        foreach ($this->profileProviders as $provider) {
            array_push($tabs, ...$provider->getProfileTabs());
        }

        return $tabs;
    }

    /**
     * @param array $account as AccountDirectoryService::describeAccount() gives it
     */
    public function accountTabs(array $account): array
    {
        $tabs = [];

        foreach ($this->accountProviders as $provider) {
            array_push($tabs, ...$provider->getAccountTabs($account));
        }

        return $tabs;
    }

    /**
     * @param array $account as AccountDirectoryService::describeAccount() gives it
     */
    public function accountActions(array $account): array
    {
        $actions = [];

        foreach ($this->accountActionProviders as $provider) {
            array_push($actions, ...$provider->getAccountActions($account));
        }

        return $actions;
    }
}

<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUserDs\Interface\AccountTabProviderInterface;
use Wexample\SymfonyUserDs\Interface\ProfileTabProviderInterface;

/**
 * The tabs other packages add to the profile and to an account's page, after
 * their own.
 */
class TabProvidersExtension extends AbstractExtension
{
    /**
     * @param iterable<ProfileTabProviderInterface> $profileProviders
     * @param iterable<AccountTabProviderInterface> $accountProviders
     */
    public function __construct(
        #[AutowireIterator(ProfileTabProviderInterface::TAG)]
        private readonly iterable $profileProviders,
        #[AutowireIterator(AccountTabProviderInterface::TAG)]
        private readonly iterable $accountProviders,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('profile_tabs', $this->profileTabs(...)),
            new TwigFunction('account_tabs', $this->accountTabs(...)),
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
}

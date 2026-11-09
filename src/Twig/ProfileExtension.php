<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUserDs\Interface\ProfileTabProviderInterface;

class ProfileExtension extends AbstractExtension
{
    /**
     * @param iterable<ProfileTabProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator(ProfileTabProviderInterface::TAG)]
        private readonly iterable $providers,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('profile_tabs', $this->profileTabs(...)),
        ];
    }

    /**
     * The tabs other packages add to the profile, after its own.
     */
    public function profileTabs(): array
    {
        $tabs = [];

        foreach ($this->providers as $provider) {
            array_push($tabs, ...$provider->getProfileTabs());
        }

        return $tabs;
    }
}

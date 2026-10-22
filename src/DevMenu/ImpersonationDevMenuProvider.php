<?php

namespace Wexample\SymfonyUserDs\DevMenu;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Wexample\SymfonyDesignSystem\Interface\DevMenuProviderInterface;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Interface\AccountGateInterface;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\ImpersonationService;

/**
 * Switching to another account, where the firewall allows it: walking the
 * application as each role. Shown but unavailable when it cannot be used now
 * — nobody signed in, an account without the role, or one held by a gate
 * (terms, authenticator setup), which would refuse the impersonation page.
 */
class ImpersonationDevMenuProvider implements DevMenuProviderInterface
{
    /**
     * @param iterable<AccountGateInterface> $gates
     */
    public function __construct(
        private readonly ImpersonationService $impersonation,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TokenStorageInterface $tokenStorage,
        #[AutowireIterator(AccountGateInterface::TAG)]
        private readonly iterable $gates,
    ) {
    }

    public function getDevMenuItems(): array
    {
        // No switch_user on the firewall: impersonation does not exist here.
        if (! $this->impersonation->getSwitchUserConfig()) {
            return [];
        }

        return [[
            'icon' => 'ph:bold/user-switch',
            'label' => 'WexampleSymfonyUserDsBundle.common.menu::impersonate',
            'href' => $this->urlGenerator->generate(UserRoute::IMPERSONATE),
            'target' => 'modal',
            'account' => true,
            'disabled' => ! $this->impersonation->canImpersonate() || $this->isHeldByGate(),
        ]];
    }

    private function isHeldByGate(): bool
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (! $user instanceof AbstractUser) {
            return false;
        }

        foreach ($this->gates as $gate) {
            if ($gate->isBlocking($user, $token)) {
                return true;
            }
        }

        return false;
    }
}

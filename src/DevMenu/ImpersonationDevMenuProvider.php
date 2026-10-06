<?php

namespace Wexample\SymfonyUserDs\DevMenu;

use Wexample\SymfonyDesignSystem\Interface\DevMenuProviderInterface;
use Wexample\SymfonyUserDs\Twig\ImpersonationExtension;

/**
 * Switching to another account, where the firewall allows it and the account
 * signed in may: walking the application as each role.
 */
class ImpersonationDevMenuProvider implements DevMenuProviderInterface
{
    public function __construct(
        private readonly ImpersonationExtension $impersonation,
    ) {
    }

    public function getDevMenuItems(): array
    {
        $url = $this->impersonation->impersonationUrl();

        return $url ? [[
            'icon' => 'ph:bold/user-switch',
            'label' => 'WexampleSymfonyUserDsBundle.common.menu::impersonate',
            'href' => $url,
            'target' => 'modal',
        ]] : [];
    }
}

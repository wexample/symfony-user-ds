<?php

namespace Wexample\SymfonyUserDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyUserDs\Interface\AccountTabProviderInterface;
use Wexample\SymfonyUserDs\Interface\ProfileTabProviderInterface;

class WexampleSymfonyUserDsExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $container->registerForAutoconfiguration(ProfileTabProviderInterface::class)
            ->addTag(ProfileTabProviderInterface::TAG);
        $container->registerForAutoconfiguration(AccountTabProviderInterface::class)
            ->addTag(AccountTabProviderInterface::TAG);
    }
}

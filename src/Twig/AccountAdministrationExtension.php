<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\AccountAdministrationService;

class AccountAdministrationExtension extends AbstractExtension
{
    public function __construct(
        private readonly AccountAdministrationService $administration,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'account_administration_url',
                [
                    $this,
                    'accountAdministrationUrl',
                ]
            ),
        ];
    }

    /**
     * The administration screens, for an account allowed to open them.
     *
     * @return string|null null where no `administration.page_role` is
     *                     declared, or for an account without it
     */
    public function accountAdministrationUrl(): ?string
    {
        return $this->administration->canAdminister()
            ? $this->urlGenerator->generate(UserRoute::ACCOUNTS)
            : null;
    }
}

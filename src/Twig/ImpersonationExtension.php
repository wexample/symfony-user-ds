<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\ImpersonationService;

class ImpersonationExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImpersonationService $impersonation,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'impersonation_url',
                [
                    $this,
                    'impersonationUrl',
                ]
            ),
            new TwigFunction(
                'impersonation_exit_url',
                [
                    $this,
                    'impersonationExitUrl',
                ]
            ),
        ];
    }

    /**
     * The impersonation page, for an account allowed to take another one.
     *
     * @return string|null null where the firewall has no switch_user, or for
     *                     an account without its role
     */
    public function impersonationUrl(): ?string
    {
        return $this->impersonation->canImpersonate()
            ? $this->urlGenerator->generate(UserRoute::IMPERSONATE)
            : null;
    }

    /**
     * The way back from an impersonation, on a page the account behind may
     * open; null when nobody is impersonated.
     */
    public function impersonationExitUrl(): ?string
    {
        $request = $this->requestStack->getMainRequest();

        return $request ? $this->impersonation->getExitUrl($request) : null;
    }
}

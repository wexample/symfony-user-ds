<?php

namespace Wexample\SymfonyUserDs\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyUser\Entity\AbstractUser;
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
            new TwigFunction(
                'impersonation_account_url',
                [
                    $this,
                    'impersonationAccountUrl',
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

    /**
     * Where to post to take $account at once — its page's action —; null when
     * it may not be taken: no switch_user, a signed-in account without its
     * role, $account itself or one the rules refuse.
     */
    public function impersonationAccountUrl(AbstractUser $account): ?string
    {
        $actor = $this->impersonation->getActor();

        if (! $actor || ! $this->impersonation->canImpersonate()
            || $account->getUserIdentifier() === $actor->getUserIdentifier()
            || $this->impersonation->getRefusal($actor, $account) !== null) {
            return null;
        }

        return $this->urlGenerator->generate(UserRoute::IMPERSONATE_ACCOUNT, ['identifier' => $account->getUserIdentifier()]);
    }
}

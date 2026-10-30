<?php

namespace Wexample\SymfonyUserDs\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;
use Wexample\SymfonyUser\Interface\MembershipGroupInterface;
use Wexample\SymfonyUserDs\Service\MembershipsScreenService;

/**
 * `{{ memberships(group) }}`: the members screen of a group, drawn in the page
 * calling it — the application's, in its own layout. Nothing for an account
 * that may not read it.
 */
class MembershipsScreenExtension extends AbstractTemplateExtension
{
    public const string COMPONENT = '@WexampleSymfonyUserDsBundle/components/memberships';

    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly MembershipsScreenService $screen,
    ) {
        parent::__construct($componentsExtension);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'memberships',
                fn (Environment $twig, mixed $context, MembershipGroupInterface $group): string => ($view = $this->screen->build($group))
                    ? $this->renderComponent($twig, $context, self::COMPONENT, $view)
                    : '',
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}

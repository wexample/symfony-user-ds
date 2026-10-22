<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyTunnels\Attribute\TunnelRoute;
use Wexample\SymfonyTunnels\Controller\AbstractTunnelController;
use Wexample\SymfonyUser\Service\Tunnel\TotpSetup\TotpSetupTunnelManagerService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * Setting the authenticator app up, below its page: each step at
 * /account/authenticator/setup/<step>. Its routes start like the page's, which
 * is what lets a role held until the app is set up walk them (TotpSetupGate).
 */
#[Route(path: '/account/authenticator/setup/', name: 'user_totp_setup_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TotpSetupTunnelController extends AbstractTunnelController
{
    use SymfonyUserDsBundleClassTrait;

    public static function getTunnelManagerClass(): string
    {
        return TotpSetupTunnelManagerService::class;
    }

    #[TunnelRoute]
    public function index(Request $request): Response
    {
        return $this->handleTunnelRequest($request);
    }
}

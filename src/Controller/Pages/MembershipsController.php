<?php

namespace Wexample\SymfonyUserDs\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyForms\Service\FormProcessor\Notification;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Exception\MembershipAdministrationException;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\MembershipAdministrationService;
use Wexample\SymfonyUserDs\Service\FormProcessor\MembershipGrantsFormProcessor;
use Wexample\SymfonyUserDs\Service\MembershipTargetResolver;
use Wexample\SymfonyUserDs\Service\MembershipsScreenService;
use Wexample\SymfonyUserDs\Traits\SymfonyUserDsBundleClassTrait;

/**
 * The members of a group, for its members to read and its administrators to
 * change. The group is named `{kind}:{id}` — `team:0193…` —, the kind being a
 * MembershipManagerInterface of the application. Whoever is neither gets a
 * 404: the group is not theirs to know of.
 */
#[Route(path: '/memberships')]
#[IsGranted('IS_AUTHENTICATED')]
final class MembershipsController extends AbstractPagesController
{
    use SymfonyUserDsBundleClassTrait;

    /**
     * The id a row's buttons sign their token with: the application's forms'
     * own — `submit`, stateless —, as the account page's buttons do.
     */
    public const string CSRF_REMOVE = 'submit';

    private const string TRANSLATION_DOMAIN = 'WexampleSymfonyUserDsBundle.components.memberships.memberships::';

    /**
     * The members screen, here for a kind of group no page of the application
     * hosts; one that does is sent there (HostsMembershipsInterface).
     */
    #[Route(path: '/{id}', name: UserRoute::MEMBERSHIPS)]
    public function index(
        string $id,
        MembershipTargetResolver $targets,
        MembershipsScreenService $screen,
    ): Response {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $group || $membership || ! $screen->mayRead($group)) {
            throw $this->createNotFoundException();
        }

        if ($targets->isHosted($group)) {
            return $this->redirect($targets->getMembershipsUrl($group));
        }

        return $this->renderPage('index', [
            'group' => $group,
        ]);
    }

    #[Route(path: '/{id}/member', name: UserRoute::MEMBERSHIP)]
    public function show(
        string $id,
        MembershipTargetResolver $targets,
        MembershipAdministrationService $administration,
        MembershipGrantsFormProcessor $grantsFormProcessor,
    ): Response {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $membership || ! $administration->mayAdminister($this->getAbstractUser(), $group)) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage('show', [
            'group' => $group,
            'member_label' => $membership->getMember()->getMembershipLabel(),
            'membership_grants_form' => $grantsFormProcessor->createForm($membership)->createView(),
        ]);
    }

    /**
     * A member taken out of its group, from its row, then back to the page
     * listing the members — the application's when it hosts them —, saying
     * so, or why not.
     */
    #[Route(path: '/{id}/remove', name: UserRoute::MEMBERSHIP_REMOVE, methods: ['POST'])]
    public function remove(
        string $id,
        Request $request,
        MembershipTargetResolver $targets,
        MembershipAdministrationService $administration,
    ): RedirectResponse {
        [$group, $membership] = $targets->resolve($id) ?? [null, null];

        if (! $membership || ! $administration->mayAdminister($this->getAbstractUser(), $group)) {
            throw $this->createNotFoundException();
        }

        if (! $this->isCsrfTokenValid(self::CSRF_REMOVE, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $administration->remove($this->getAbstractUser(), $membership);
            $this->notify(Notification::TYPE_SUCCESS, 'remove.done');
        } catch (MembershipAdministrationException $exception) {
            $this->notify(Notification::TYPE_ERROR, 'remove.refused.' . $exception->refusal->value);
        }

        return $this->redirect($targets->getMembershipsUrl($group));
    }

    /**
     * A toast on the page the redirect reaches, its message one of the
     * members screen's.
     */
    private function notify(string $type, string $key): void
    {
        $this->addFlash($type, (new Notification(self::TRANSLATION_DOMAIN . $key, $type))->toArray());
    }

    private function getAbstractUser(): AbstractUser
    {
        $user = $this->getUser();

        if (! $user instanceof AbstractUser) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}

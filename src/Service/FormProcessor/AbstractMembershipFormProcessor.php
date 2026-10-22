<?php

namespace Wexample\SymfonyUserDs\Service\FormProcessor;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyForms\Controller\FormController;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Exception\MembershipAdministrationException;
use Wexample\SymfonyUser\Interface\MembershipGroupInterface;
use Wexample\SymfonyUser\Interface\MembershipInterface;
use Wexample\SymfonyUser\Routing\UserRoute;
use Wexample\SymfonyUser\Service\GrantHierarchyService;
use Wexample\SymfonyUser\Service\MembershipAdministrationService;
use Wexample\SymfonyUserDs\Service\MembershipTargetResolver;

/**
 * A form about one group, or one membership in it. Which one travels in the
 * submission url (MembershipTargetResolver), never in a field. The change is
 * made in formIsValid(), so a refusal comes back as an error on the form; a
 * change made reloads the members page, which lists them again.
 */
abstract class AbstractMembershipFormProcessor extends AbstractFormProcessor
{
    /** The refusal code is appended: `@form::error.refused.last_administrator`. */
    public const string ERROR_REFUSED = '@form::error.refused.';

    protected ?MembershipGroupInterface $group = null;

    protected ?MembershipInterface $membership = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        protected readonly Security $security,
        protected readonly MembershipAdministrationService $administration,
        protected readonly GrantHierarchyService $grantHierarchy,
        protected readonly MembershipTargetResolver $targets,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * The membership of the submission handled, not of the next one.
     */
    public function reset(): void
    {
        parent::reset();
        $this->group = null;
        $this->membership = null;
    }

    public function getFormActionRoute(): string
    {
        return 'form_' . FormController::ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT;
    }

    public function getFormActionArgs($data): array
    {
        return parent::getFormActionArgs($data) + ['id' => $this->targets->getKey($this->group, $this->membership)];
    }

    /**
     * The group, or the membership, the submission url names.
     */
    public function handleSubmittedRequest(Request $request): MembershipGroupInterface|MembershipInterface
    {
        $target = $this->targets->resolve((string) $request->attributes->get('id'));

        if (! $target) {
            throw new NotFoundHttpException('No group or membership under this id.');
        }

        [$group, $membership] = $target;

        return $membership ?? $group;
    }

    public function formIsValid(FormInterface $form): bool
    {
        if (! $this->administration->mayAdminister($this->getActor(), $this->group)) {
            throw new AccessDeniedHttpException('This group is not administered by this account.');
        }

        if (! parent::formIsValid($form)) {
            return false;
        }

        try {
            $this->apply($form);
        } catch (MembershipAdministrationException $exception) {
            $form->addError(new FormError(self::ERROR_REFUSED . $exception->refusal->value));

            return false;
        }

        return true;
    }

    public function onValid(FormInterface $form)
    {
        $this->setNotification('@form::success.message');
        $this->redirectToRoute(UserRoute::MEMBERSHIPS, ['id' => $this->targets->getKey($this->group)]);
    }

    /**
     * @throws MembershipAdministrationException
     */
    abstract protected function apply(FormInterface $form): void;

    /**
     * $data is the group or the membership: what the form is about.
     */
    protected function setTarget(MembershipGroupInterface|MembershipInterface|null $data): void
    {
        $this->membership = $data instanceof MembershipInterface ? $data : null;
        $this->group = $this->membership?->getGroup() ?? $data;
    }

    protected function getActor(): AbstractUser
    {
        $actor = $this->security->getUser();

        if (! $actor instanceof AbstractUser) {
            throw new AccessDeniedHttpException();
        }

        return $actor;
    }
}

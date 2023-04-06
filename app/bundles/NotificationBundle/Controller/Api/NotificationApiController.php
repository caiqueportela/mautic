<?php

namespace Mautic\NotificationBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Notification>
 */
class NotificationApiController extends CommonApiController
{
    /**
     * @var ContactTracker
     */
    protected $contactTracker;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, ContactTracker $contactTracker, RequestStack $requestStack)
    {
        $this->contactTracker = $contactTracker;

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack);
    }

    public function initialize(ControllerEvent $event)
    {
        $notificationModel = $this->getModel('notification');
        \assert($notificationModel instanceof NotificationModel);

        $this->model           = $notificationModel;
        $this->entityClass     = Notification::class;
        $this->entityNameOne   = 'notification';
        $this->entityNameMulti = 'notifications';

        parent::initialize($event);
    }

    /**
     * Receive Web Push subscription request.
     *
     * @return JsonResponse
     */
    public function subscribeAction(Request $request)
    {
        $osid = $request->get('osid');
        if ($osid) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
            $leadModel = $this->getModel('lead');

            if ($currentLead = $this->contactTracker->getContact()) {
                $currentLead->addPushIDEntry($osid);
                $leadModel->saveEntity($currentLead);
            }

            return new JsonResponse(['success' => true, 'osid' => $osid], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }
}

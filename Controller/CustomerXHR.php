<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerXHR extends Controller
{
    private $userService;
    private $eventDispatcher;
    private $translator;

    public function __construct(UserService $userService, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    public function listCustomersXHR(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $group = $request->query->get('group');
        $organization = $request->query->get('organization');
        $company = $request->query->get('company');
        $request->query->remove('company');
        $request->query->remove('organization');
        $request->query->remove('group');

        $json = array();

        if ($request->isXmlHttpRequest()) {
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreFrameworkBundle:User');
            $json =  $repository->getAllCustomer($request->query, $this->container, $group, $organization, $company);
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function removeCustomerXHR(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();
        if ($request->getMethod() == "DELETE") {
            $em = $this->getDoctrine()->getManager();
            $id = $request->attributes->get('customerId');
            $user = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(['id' => $id]);

            if ($user) {

                $this->userService->removeCustomer($user);
                // Trigger customer created event
                $event = new GenericEvent(CoreWorkflowEvents\Customer\Delete::getId(), [
                    'entity' => $user,
                ]);

                $this->eventDispatcher->dispatch('uvdesk.automation.workflow.execute', $event);

                $json['alertClass'] = 'success';
                $json['alertMessage'] = $this->translator->trans('Success ! Customer removed successfully.');
            } else {
                $json['alertClass'] =  'danger';
                $json['alertMessage'] = $this->translator->trans('Error ! Invalid customer id.');
                $json['statusCode'] = Response::HTTP_NOT_FOUND;
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}

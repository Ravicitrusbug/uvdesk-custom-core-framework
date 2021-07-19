<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UVDeskService;

class Customer extends AbstractController
{
    private $userService;
    private $eventDispatcher;
    private $translator;
    private $fileSystem;
    private $uvdeskService;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        FileSystem $fileSystem,
        UVDeskService $uvdeskService
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->fileSystem = $fileSystem;
        $this->uvdeskService = $uvdeskService;
    }

    public function listCustomers(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User');

        return $this->render('@UVDeskCoreFramework/Customers/listSupportCustomers.html.twig', [
            'supportGroupCollection' => $userRepository->getSupportGroups(),
            'supportTeamCollection' => $userRepository->getSupportTeams(),
            'supportCompanyCollection' => $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportCompany')->findAll(),
        ]);
    }

    public function createCustomer(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->getMethod() == "POST") {
            $entityManager = $this->getDoctrine()->getManager();
            $formDetails = $request->request->get('customer_form');
            //dd($formDetails);
            $uploadedFiles = $request->files->get('customer_form');

            // Profile upload validation
            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
            if (isset($uploadedFiles['profileImage'])) {
                if (!in_array($uploadedFiles['profileImage']->getMimeType(), $validMimeType)) {
                    $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                    return $this->redirect($this->generateUrl('helpdesk_member_create_customer_account'));
                }
            }

            $user = $entityManager->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email' => $formDetails['email']));
            $customerInstance = !empty($user) ? $user->getCustomerInstance() : null;

            if (empty($customerInstance)) {
                if (!empty($formDetails)) {
                    $fullname = trim(implode(' ', [$formDetails['firstName'], $formDetails['lastName']]));
                    $supportRole = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportRole')->findOneByCode('ROLE_CUSTOMER');

                    $user = $this->userService->createUserInstance($formDetails['email'], $fullname, $supportRole, [
                        'contact' => $formDetails['contactNumber'],
                        'source' => 'website',
                        'active' => !empty($formDetails['isActive']) ? true : false,
                        'image' => $uploadedFiles['profileImage'],
                    ]);

                    $cInstance = $user->getCustomerInstance();

                    // Map support team
                    if (!empty($formDetails['userSubGroup'])) {
                        $supportTeamRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportTeam');

                        foreach ($formDetails['userSubGroup'] as $supportTeamId) {
                            $supportTeam = $supportTeamRepository->findOneById($supportTeamId);

                            if (!empty($supportTeam)) {
                                $cInstance->addSupportTeam($supportTeam);
                            }
                        }
                    }
                    // Map support group
                    if (!empty($formDetails['companies'])) {
                        $supportCompanyRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportCompany');

                        foreach ($formDetails['companies'] as $supportCompanyId) {
                            $supportCompany = $supportCompanyRepository->findOneById($supportCompanyId);

                            if (!empty($supportCompany)) {
                                $cInstance->addSupportCompany($supportCompany);
                            }
                        }
                    }

                    if (!empty($formDetails['groups'])) {
                        $supportGroupRepository = $entityManager->getRepository('UVDeskCoreFrameworkBundle:SupportGroup');

                        foreach ($formDetails['groups'] as $supportGroupId) {
                            $supportGroup = $supportGroupRepository->findOneById($supportGroupId);

                            if (!empty($supportGroup)) {
                                $cInstance->addSupportGroup($supportGroup);
                            }
                        }
                    }

                    $entityManager->persist($cInstance);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Customer saved successfully.'));

                    return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                }
            } else {
                $this->addFlash('warning', $this->translator->trans('Error ! User with same email already exist.'));
            }
        }

        return $this->render('@UVDeskCoreFramework/Customers/createSupportCustomer.html.twig', [
            'user' => new User(),
            'errors' => json_encode([])
        ]);
    }

    public function editCustomer(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('UVDeskCoreFrameworkBundle:User');

        if ($userId = $request->attributes->get('customerId')) {
            $user = $repository->findOneBy(['id' =>  $userId]);
            if (!$user)
                $this->noResultFound();
        }
        if ($request->getMethod() == "POST") {

            $contentFile = $request->files->get('customer_form');

            // Customer Profile upload validation
            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
            if (isset($contentFile['profileImage'])) {
                if (!in_array($contentFile['profileImage']->getMimeType(), $validMimeType)) {
                    $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                    return $this->render('@UVDeskCoreFramework/Customers/updateSupportCustomer.html.twig', ['user' => $user, 'errors' => json_encode([])]);
                }
            }
            if ($userId) {
                $data = $request->request->all();
                $data = $data['customer_form'];
                $checkUser = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email' => $data['email']));
                $errorFlag = 0;

                if ($checkUser) {
                    if ($checkUser->getId() != $userId)
                        $errorFlag = 1;
                }

                if (!$errorFlag && 'hello@uvdesk.com' !== $user->getEmail()) {
                    $password = $user->getPassword();
                    $email = $user->getEmail();
                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setIsEnabled(isset($data['isActive']) ? 1 : 0);
                    $em->persist($user);

                    // User Instance
                    $userInstance = $em->getRepository('UVDeskCoreFrameworkBundle:UserInstance')->findOneBy(array('user' => $user->getId()));
                    $oldSupportTeam = ($supportTeamList = $userInstance->getSupportTeams()) ? $supportTeamList->toArray() : [];
                    $oldSupportGroup  = ($supportGroupList = $userInstance->getSupportGroups()) ? $supportGroupList->toArray() : [];
                    $oldSupportCompany  = ($supportCompanyList = $userInstance->getSupportCompanies()) ? $supportCompanyList->toArray() : [];

                    if (isset($data['userSubGroup'])) {
                        foreach ($data['userSubGroup'] as $userSubGroup) {
                            if ($userSubGrp = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportTeam',
                                'findOneBy',
                                [
                                    'id' => $userSubGroup
                                ]
                            ))
                                if (!$oldSupportTeam || !in_array($userSubGrp, $oldSupportTeam)) {
                                    $userInstance->addSupportTeam($userSubGrp);
                                } elseif ($oldSupportTeam && ($key = array_search($userSubGrp, $oldSupportTeam)) !== false)
                                    unset($oldSupportTeam[$key]);
                        }

                        foreach ($oldSupportTeam as $removeteam) {
                            $userInstance->removeSupportTeam($removeteam);
                            $em->persist($userInstance);
                        }
                    }

                    if (isset($data['groups'])) {
                        foreach ($data['groups'] as $userGroup) {
                            if ($userGrp = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportGroup',
                                'findOneBy',
                                [
                                    'id' => $userGroup
                                ]
                            ))

                                if (!$oldSupportGroup || !in_array($userGrp, $oldSupportGroup)) {
                                    $userInstance->addSupportGroup($userGrp);
                                } elseif ($oldSupportGroup && ($key = array_search($userGrp, $oldSupportGroup)) !== false)
                                    unset($oldSupportGroup[$key]);
                        }

                        foreach ($oldSupportGroup as $removeGroup) {
                            $userInstance->removeSupportGroup($removeGroup);
                            $em->persist($userInstance);
                        }
                    }

                    if (isset($data['companies'])) {
                        foreach ($data['companies'] as $userCompany) {
                            if ($usercmpny = $this->uvdeskService->getEntityManagerResult(
                                'UVDeskCoreFrameworkBundle:SupportCompany',
                                'findOneBy',
                                [
                                    'id' => $userCompany
                                ]
                            ))

                                if (!$oldSupportCompany || !in_array($usercmpny, $oldSupportCompany)) {
                                    $userInstance->addSupportCompany($usercmpny);
                                } elseif ($oldSupportCompany && ($key = array_search($usercmpny, $oldSupportCompany)) !== false)
                                    unset($oldSupportCompany[$key]);
                        }

                        foreach ($oldSupportCompany as $removeCompany) {
                            $userInstance->removeSupportCompany($removeCompany);
                            $em->persist($userInstance);
                        }
                    }

                    $userInstance->setUser($user);
                    // $userInstance->setIsActive(isset($data['isActive']) ? 1 : 0);
                    $userInstance->setIsVerified(0);
                    if (isset($data['source']))
                        $userInstance->setSource($data['source']);
                    else
                        $userInstance->setSource('website');
                    if (isset($data['contactNumber'])) {
                        $userInstance->setContactNumber($data['contactNumber']);
                    }
                    if (isset($contentFile['profileImage'])) {
                        $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($contentFile['profileImage'], 'profile');
                        $userInstance->setProfileImagePath($assetDetails['path']);
                    }

                    $em->persist($userInstance);
                    $em->flush();

                    $user->addUserInstance($userInstance);
                    $em->persist($user);
                    $em->flush();

                    // Trigger customer created event
                    $event = new GenericEvent(CoreWorkflowEvents\Customer\Update::getId(), [
                        'entity' => $user,
                    ]);

                    $this->eventDispatcher->dispatch('uvdesk.automation.workflow.execute', $event);

                    $this->addFlash('success', $this->translator->trans('Success ! Customer information updated successfully.'));
                    return $this->redirect($this->generateUrl('helpdesk_member_manage_customer_account_collection'));
                } else {
                    $this->addFlash('warning', $this->translator->trans('Error ! User with same email is already exist.'));
                }
            }
        } elseif ($request->getMethod() == "PUT") {
            $content = json_decode($request->getContent(), true);
            $userId  = $content['id'];
            $user = $repository->findOneBy(['id' =>  $userId]);

            if (!$user)
                $this->noResultFound();

            $checkUser = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(array('email' => $content['email']));
            $errorFlag = 0;

            if ($checkUser) {
                if ($checkUser->getId() != $userId)
                    $errorFlag = 1;
            }

            if (!$errorFlag && 'hello@uvdesk.com' !== $user->getEmail()) {
                $name = explode(' ', $content['name']);
                $lastName = isset($name[1]) ? $name[1] : ' ';
                $user->setFirstName($name[0]);
                $user->setLastName($lastName);
                $user->setEmail($content['email']);
                $em->persist($user);

                //user Instance
                $userInstance = $em->getRepository('UVDeskCoreFrameworkBundle:UserInstance')->findOneBy(array('user' => $user->getId()));
                if (isset($content['contactNumber'])) {
                    $userInstance->setContactNumber($content['contactNumber']);
                }
                $em->persist($userInstance);
                $em->flush();

                $json['alertClass']      = 'success';
                $json['alertMessage']    = $this->translator->trans('Success ! Customer updated successfully.');
            } else {
                $json['alertClass']      = 'error';
                $json['alertMessage']    = $this->translator->trans('Error ! Customer with same email already exist.');
            }

            return new Response(json_encode($json), 200, []);
        }

        return $this->render('@UVDeskCoreFramework/Customers/updateSupportCustomer.html.twig', [
            'user' => $user,
            'errors' => json_encode([])
        ]);
    }

    protected function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user);

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

    public function bookmarkCustomer(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_CUSTOMER')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $json = array();
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);
        $id = $request->attributes->get('id') ?: $data['id'];
        $user = $em->getRepository('UVDeskCoreFrameworkBundle:User')->findOneBy(['id' => $id]);
        if (!$user) {
            $json['error'] = 'resource not found';
            return new JsonResponse($json, Response::HTTP_NOT_FOUND);
        }
        $userInstance = $em->getRepository('UVDeskCoreFrameworkBundle:UserInstance')->findOneBy(
            array(
                'user' => $id,
                'supportRole' => 4
            )
        );

        if ($userInstance->getIsStarred()) {
            $userInstance->setIsStarred(0);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = $this->translator->trans('unstarred Action Completed successfully');
        } else {
            $userInstance->setIsStarred(1);
            $em->persist($userInstance);
            $em->flush();
            $json['alertClass'] = 'success';
            $json['message'] = $this->translator->trans('starred Action Completed successfully');
        }
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}

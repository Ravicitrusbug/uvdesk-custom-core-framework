<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Controller;

use Webkul\UVDesk\CoreFrameworkBundle\Form;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportCompany;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Symfony\Component\Translation\TranslatorInterface;

class Company extends AbstractController
{
    private $userService;
    private $translator;

    public function __construct(UserService $userService, TranslatorInterface $translator)
    {
        $this->userService = $userService;
        $this->translator = $translator;
    }

    public function listCompanies(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskCoreFramework/Companies/listSupportCompany.html.twig');
    }

    public function createCompany(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $supportCompany = new SupportCompany();

        $errors = [];

        if ($request->getMethod() == "POST") {

            $request->request->set('users', explode(',', $request->request->get('tempUsers')));
            $request->request->set('groups', explode(',', $request->request->get('tempGroups')));
            $oldGroups = ($grpList =  $supportCompany->getSupportGroups()) ? $grpList->toArray() : $grpList;

            $allDetails = $request->request->all();

            $em = $this->getDoctrine()->getManager();
            $supportCompany->setName($allDetails['name']);
            $supportCompany->setDescription($allDetails['description']);
            $supportCompany->setIsActive((bool) isset($allDetails['isActive']));
            $em->persist($supportCompany);

            $usersGroup  = (!empty($allDetails['groups'])) ? $allDetails['groups'] : [];

            if (!empty($usersGroup)) {
                $usersGroup = array_map(function ($group) {
                    return 'p.id = ' . $group;
                }, $usersGroup);

                $userGroup = $em->createQueryBuilder('p')->select('p')
                    ->from('UVDeskCoreFrameworkBundle:SupportGroup', 'p')
                    ->where(implode(' OR ', $usersGroup))
                    ->getQuery()->getResult();
            }

            // Add Teams to Group
            foreach ($userGroup as $supportGroup) {
                $supportGroup->addSupportCompany($supportCompany);
                $em->persist($supportGroup);
            }

            $em->persist($supportCompany);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('Success ! Team information saved successfully.'));

            return $this->redirect($this->generateUrl('helpdesk_member_support_company_collection'));
        }

        return $this->render('@UVDeskCoreFramework/Companies/createSupportCompany.html.twig', [
            'company' => $supportCompany,
            'errors' => json_encode($errors)
        ]);
    }

    public function editCompany(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_SUB_GROUP')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->attributes->get('supportCompanyId')) {
            $supportCompany = $this->getDoctrine()->getRepository('UVDeskCoreFrameworkBundle:SupportCompany')
                ->findSubGroupById(['id' => $request->attributes->get('supportCompanyId')]);

            if (!$supportCompany)
                $this->noResultFound();
        }
        // dd($supportCompany);
        $errors = [];
        if ($request->getMethod() == "POST") {

            $request->request->set('users', explode(',', $request->request->get('tempUsers')));
            $request->request->set('groups', explode(',', $request->request->get('tempGroups')));
            //$oldUsers = ($usersList = $supportTeam->getUsers()) ? $usersList->toArray() : $usersList;
            $oldGroups = ($grpList = $supportCompany->getSupportGroups()) ? $grpList->toArray() : $grpList;

            $allDetails = $request->request->all();

            $em = $this->getDoctrine()->getManager();
            $supportCompany->setName($allDetails['name']);
            $supportCompany->setDescription($allDetails['description']);
            $supportCompany->setIsActive((bool) isset($allDetails['isActive']));

            $usersGroup  = (!empty($allDetails['groups'])) ? $allDetails['groups'] : [];

            if (!empty($usersGroup)) {
                $usersGroup = array_map(function ($group) {
                    return 'p.id = ' . $group;
                }, $usersGroup);

                $userGroup = $em->createQueryBuilder('p')->select('p')
                    ->from('UVDeskCoreFrameworkBundle:SupportGroup', 'p')
                    ->where(implode(' OR ', $usersGroup))
                    ->getQuery()->getResult();
            }

            // Add Group to team
            foreach ($userGroup as $supportGroup) {
                if (!$oldGroups || !in_array($supportGroup, $oldGroups)) {
                    $supportGroup->addSupportCompany($supportCompany);
                    $em->persist($supportGroup);
                } elseif ($oldGroups && ($key = array_search($supportGroup, $oldGroups)) !== false)
                    unset($oldGroups[$key]);
            }

            foreach ($oldGroups as $removeGroup) {
                $removeGroup->removeSupportCompany($supportCompany);
                $em->persist($removeGroup);
            }

            $em->persist($supportCompany);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('Success ! Company information updated successfully.'));
            return $this->redirect($this->generateUrl('helpdesk_member_support_company_collection'));
        }
        return $this->render('@UVDeskCoreFramework/Companies/updateSupportCompany.html.twig', [
            'company' => $supportCompany,
            'errors' => json_encode($errors)
        ]);
    }
}

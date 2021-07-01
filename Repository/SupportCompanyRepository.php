<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\Criteria;

/**
 * AgentPrivilegeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SupportCompanyRepository extends \Doctrine\ORM\EntityRepository
{

    public $safeFields = array('page', 'limit', 'sort', 'order', 'direction');
    const LIMIT = 10;

    public function getAllSupportCompanies(\Symfony\Component\HttpFoundation\ParameterBag $obj = null, $container)
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        $data = $obj->all();
        $data = array_reverse($data);
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->safeFields)) {
                if ($key != 'dateUpdated' and $key != 'dateAdded' and $key != 'search') {
                    $qb->Andwhere('a.' . $key . ' = :' . $key);
                    $qb->setParameter($key, $value);
                } else {
                    if ($key == 'search') {
                        $qb->orwhere('a.name' . ' LIKE :name');
                        $qb->setParameter('name', '%' . urldecode($value) . '%');
                        $qb->orwhere('a.description' . ' LIKE :description');
                        $qb->setParameter('description', '%' . urldecode(trim($value)) . '%');
                    }
                }
            }
        }

        if (!isset($data['sort'])) {
            $qb->orderBy('a.id', Criteria::DESC);
        }

        $paginator  = $container->get('knp_paginator');

        $results = $paginator->paginate(
            $qb,
            isset($data['page']) ? $data['page'] : 1,
            self::LIMIT,
            array('distinct' => false)
        );

        $parsedCollection = array_map(function ($team) {
            return [
                'id'          => $team->getId(),
                'name'        => $team->getName(),
                'description' => $team->getDescription(),
                'isActive'    => $team->getIsActive(),
            ];
        }, $results->getItems());

        $paginationData = $results->getPaginationData();
        $queryParameters = $results->getParams();

        $paginationData['url'] = '#' . $container->get('uvdesk.service')->buildPaginationQuery($queryParameters);

        $json['groups'] = $parsedCollection;
        $json['pagination_data'] = $paginationData;

        return $json;
    }


    public function findSubGroupById($filterArray = [])
    {
        $json = array();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')->from($this->getEntityName(), 'a');

        foreach ($filterArray as $key => $value) {
            $qb->Andwhere('a.' . $key . ' = :' . $key);
            $qb->setParameter($key, $value);
        }

        $result = $qb->getQuery()->getOneOrNullResult();
        // $result = $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return ($result);
    }
}

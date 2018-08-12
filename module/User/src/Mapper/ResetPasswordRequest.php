<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManagerInterface;
use ZfcUserDoctrineORM\Options\ModuleOptions;
use Zend\Stdlib\Hydrator\HydratorInterface;

class ResetPasswordRequest
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get Entity Repository
     */
    public function getEntityRepository()
    {
        return $this->em->getRepository('User\\Entity\\ResetPasswordRequest');
    }

    public function findById($uuid)
    {
        return $this->getEntityRepository()->findOneBy(['uuid' => $uuid]);
    }

    public function insert($entity)
    {
        return $this->persist($entity);
    }

    public function update($entity)
    {
        return $this->persist($entity);
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }
}

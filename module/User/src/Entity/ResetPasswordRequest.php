<?php

namespace User\Entity;

use ZfcUserDoctrineORM\Entity\User;

/**
 * ResetPasswordRequest
 */
class ResetPasswordRequest
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var DateTime
     */
    private $expiredAt;

   /**
     * @var DateTime
     */
    private $resetedAt;

   /**
     * @var DateTime
     */
    private $createdAt;

   /**
     * @var DateTime
     */
    private $updatedAt;

   /**
     * @var DateTime
     */
    private $deletedAt;


    /**
     * @return the $uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Get email
     *
     * @return $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set User
     *
     * @param $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return the $expiredAt
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * @param \DateTime $expiredAt
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;
    }

    /**
     * @return the $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return the $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return the $deletedAt
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return the $resetedAt
     */
    public function getResetedAt()
    {
        return $this->resetedAt;
    }

    /**
     * @param \DateTime $resetedAt
     */
    public function setResetedAt($resetedAt)
    {
        $this->resetedAt = $resetedAt;
    }
}

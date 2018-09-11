<?php
namespace User\Service;

use Zend\Mvc\Controller\AbstractActionController;
use User\Mapper\ResetPasswordRequest;
use Zend\Crypt\Password\Bcrypt;

class ResetPassword extends AbstractActionController
{
    protected $resetPasswordRequestMapper;
    protected $resetPasswordEntity;

    function __construct($resetPasswordRequestMapper, $resetPasswordEntity)
    {
        $this->setResetPasswordRequestMapper($resetPasswordRequestMapper);
        $this->setResetPasswordEntity($resetPasswordEntity);
    }

    public function generateResetPasswordKey($email)
    {
        $expiredAt = new \DateTime();
        $generateKeyStatus = false;
        date_add($expiredAt, date_interval_create_from_date_string("14 days"));
        $resetPasswordEntity = $this->getResetPasswordEntity();
        $resetPasswordEntity->setEmail($email);
        $resetPasswordEntity->setExpiredAt($expiredAt);
        $resetPasswordEntity->setCreatedAt(new \DateTime());
        $resetPasswordEntity->setUpdatedAt(new \DateTime());

        $resetPasswordRequestMapper = $this->getResetPasswordRequestMapper();
        try {
            $requestResetPasswordResult = $resetPasswordRequestMapper->insert($resetPasswordEntity);
            $generateKeyStatus = true;
        } catch (\Exception $e) {
        }

        return $generateKeyStatus;
    }

    public function resetPassword($resetPasswordRequestEntity, $pwd)
    {
        $resetPasswordRequestMapper = $this->getResetPasswordRequestMapper();
        $resetPasswordRequestEmail  = $resetPasswordRequestEntity->getEmail();
        $userEntity = $resetPasswordRequestMapper->getUserEntity()->findByEmail($resetPasswordRequestEmail);

        $hashMethod = new Bcrypt();
        $hashMethod->setCost(10);
        $securePass = $hashMethod->create($pwd);

        $userEntity->setPassword($securePass);
        $resetPasswordRequestMapper->getUserEntity()->update($userEntity);
        $resetPasswordRequestEntity->setResetedAt(new \DateTime());
        $resetPasswordRequestEntity->setExpiredAt(new \DateTime());
        $resetPasswordRequestEntity->setUpdatedAt(new \DateTime());
        $requestResetPasswordResult = $resetPasswordRequestMapper->update($resetPasswordRequestEntity);
    }

    /**
     * Get the value of resetPasswordEntity
     */
    public function getResetPasswordEntity()
    {
        return $this->resetPasswordEntity;
    }

    /**
     * Set the value of resetPasswordEntity
     *
     * @return  self
     */
    public function setResetPasswordEntity($resetPasswordEntity)
    {
        $this->resetPasswordEntity = $resetPasswordEntity;

        return $this;
    }

    /**
     * Get the value of resetPasswordRequestMapper
     */
    public function getResetPasswordRequestMapper()
    {
        return $this->resetPasswordRequestMapper;
    }

    /**
     * Set the value of resetPasswordRequestMapper
     *
     * @return  self
     */
    public function setResetPasswordRequestMapper($resetPasswordRequestMapper)
    {
        $this->resetPasswordRequestMapper = $resetPasswordRequestMapper;

        return $this;
    }
}

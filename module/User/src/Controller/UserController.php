<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    protected $resetPasswordReqeuestMapper;

    function __construct($resetPasswordReqeuestMapper)
    {
        $this->setResetPasswordRequestMapper($resetPasswordReqeuestMapper);
    }

    function requestResetPasswordAction()
    {
        return new ViewModel();
    }

    function resetPasswordAction()
    {
        $uuid = $this->params()->fromRoute('uuid', null);
        $resetPasswordRequest = $this->getResetPasswordRequestMapper()->findById($uuid);
        $message = [];
        if (is_null($resetPasswordRequest)) {
            $message['error'] = 'Reset Password Key Not Found';
        }
        return new ViewModel($message);
    }

    public function setResetPasswordRequestMapper($resetPasswordReqeuestMapper)
    {
        $this->resetPasswordReqeuestMapper = $resetPasswordReqeuestMapper;
    }

    public function getResetPasswordRequestMapper()
    {
        return $this->resetPasswordReqeuestMapper;
    }
}

<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    protected $resetPasswordReqeuestMapper;

    function __construct($resetPasswordReqeuestMapper)
    {
        $this->resetPasswordReqeuestMapper = $resetPasswordReqeuestMapper;
    }

    function requestResetPasswordAction()
    {
        return new ViewModel();
    }
}

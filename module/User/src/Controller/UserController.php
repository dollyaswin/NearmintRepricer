<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    protected $resetPasswordReqeuestMapper;

    protected $requestResetPasswordForm;

    function __construct($resetPasswordReqeuestMapper)
    {
        $this->setResetPasswordRequestMapper($resetPasswordReqeuestMapper);
    }

    function requestResetPasswordAction()
    {
        $form = $this->getRequestResetPasswordForm();
        $prg  = $this->prg('request-reset-password', true);
//         $prg  = $this->prg('zfcuser/request_reset_password', true);

        if ($prg instanceof \Zend\Http\PhpEnvironment\Response) {
            // returned a response to redirect us
            return $prg;
        } elseif ($prg === false) {
//             // this wasn't a POST request, but there were no params in the flash messenger
//             // probably this is the first time the form was loaded
            return new ViewModel(['form' => $this->getRequestResetPasswordForm()]);
        }

        $errorMessages = null;
        $form->setData($prg);
        if ($form->isValid()) {
            $data = $form->getData();
//             $generateResetPasswordKey = $service->generateResetPasswordKey();
            $resetPasswordKey = false;
            if ($resetPasswordKey === false) {
                $this->flashMessenger()
                     ->setNamespace('request-reset-password-error')
                     ->addMessage('Request Reset Password Error');
            } else {
                $this->flashMessenger()
                     ->setNamespace('request-reset-password-success')
                     ->addMessage('Request Reset Password Success');
            }

            $this->redirect()->toRoute('zfcuser/request_reset_password');
        } else {
            $errorMessages = $form->getMessages();
        }

        return new ViewModel(['form' => $form, 'errorMessages' => $errorMessages]);
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

    public function getRequestResetPasswordForm()
    {
        return $this->requestResetPasswordForm;
    }

    public function setRequestResetPasswordForm($requestResetPasswordForm)
    {
        $this->requestResetPasswordForm = $requestResetPasswordForm;
        return $this;
    }
}

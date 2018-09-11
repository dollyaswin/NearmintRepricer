<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Crypt\Password\Bcrypt;
use ZfcUserDoctrineORM\Options\ModuleOptions as UserModuleOption;
use ZfcUserDoctrineORM\Mapper\User as UserMapper;

class UserController extends AbstractActionController
{
    protected $resetPasswordRequestMapper;
    protected $userMapper;

    protected $requestResetPasswordForm;
    protected $resetPasswordForm;

    protected $resetPasswordService;

    function __construct($resetPasswordRequestMapper)
    {
        $this->setResetPasswordRequestMapper($resetPasswordRequestMapper);
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
            $resetPasswordKey = false;
            $data  = $form->getData();
            $email = $data['email'];

            $resetPasswordMapper = $this->getResetPasswordRequestMapper();
            $checkEmail = $resetPasswordMapper->getUserEntity()->findByEmail($email);


            $generateResetPasswordKey = false;
            $errorMessage = '';
            if (is_null($checkEmail)) {
                $errorMessage = 'Email address not found';
            } else {
                $service = $this->getResetPasswordService();
                $generateResetPasswordKey = $service->generateResetPasswordKey($email);
            }

            if ($generateResetPasswordKey  === false) {
                $this->flashMessenger()
                     ->setNamespace('request-reset-password-error')
                     ->addMessage('Request Reset Password Error');
                if (!empty($errorMessage)) {
                    $this->flashMessenger()->addMessage($errorMessage);
                }
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
        $form = $this->getResetPasswordForm();
        $resetPasswordRequest = $this->getResetPasswordRequestMapper()->findById($uuid);

        if (is_null($resetPasswordRequest)) {
            $this->flashMessenger()
                 ->setNamespace('reset-password-error')
                 ->addMessage('Link not found or has been expired!');
        }

        $prg  = $this->prg($uuid, true);
//         $prg  = $this->prg('zfcuser/request_reset_password', true);

        if ($prg instanceof \Zend\Http\PhpEnvironment\Response) {
            // returned a response to redirect us
            return $prg;
        } elseif ($prg === false) {
            //             // this wasn't a POST request, but there were no params in the flash messenger
            //             // probably this is the first time the form was loaded
            return new ViewModel(['form' => $this->getResetPasswordForm()]);
        }

        $message = [];
        $form->setData($prg);
        if ($form->isValid()) {
            $resetPassword = false;
            $data  = $form->getData();
            $pwd   = $data['pwd'];
            $repwd = $data['repwd'];

            $validateRePwd = new \Zend\Validator\Identical($repwd);
            if (! $validateRePwd->isValid($pwd)) {
                $this->flashMessenger()
                     ->setNamespace('reset-password-error')
                     ->addMessage('Password does not match!');
            }

            if (! is_null($resetPasswordRequest)) {

                $expiredAtObj = $resetPasswordRequest->getExpiredAt();
                $expiredAt = date_format($expiredAtObj, "Y-m-d H:i:s");
                $now = date_format(new \DateTime(), "Y-m-d H:i:s");

                if ($now > $expiredAt) {
                    $this->flashMessenger()
                        ->setNamespace('reset-password-error')
                        ->addMessage('Link has been expired!');
                } else {
                    $service = $this->getResetPasswordService();
                    $resetPassword = $service->resetPassword($resetPasswordRequest, $pwd);
                    $resetPassword = true;
                }
            }

            if ($resetPassword === false) {
                $this->flashMessenger()
                     ->setNamespace('reset-password-error')
                     ->addMessage('Reset Password Error!');
            } else {
                $this->flashMessenger()
                     ->setNamespace('reset-password-success')
                     ->addMessage('Password Changed Successfully. Please login with new Password.');
            }

            $this->redirect()->toRoute('zfcuser/reset_password', [
                "uuid" => $uuid
            ]);
        } else {
            $message = $form->getMessages();
        }

        return new ViewModel([
            "form" => $form,
            "errorMessages" => $message
        ]);
    }

    public function setResetPasswordRequestMapper($resetPasswordRequestMapper)
    {
        $this->resetPasswordReqeuestMapper = $resetPasswordRequestMapper;
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

    /**
     * Get the value of resetPasswordService
     */
    public function getResetPasswordService()
    {
        return $this->resetPasswordService;
    }

    /**
     * Set the value of resetPasswordService
     *
     * @return  self
     */
    public function setResetPasswordService($resetPasswordService)
    {
        $this->resetPasswordService = $resetPasswordService;

        return $this;
    }

    /**
     * Get the value of userMapper
     */
    public function getUserMapper()
    {
        return $this->userMapper;
    }

    /**
     * Set the value of userMapper
     *
     * @return  self
     */
    public function setUserMapper($userMapper)
    {
        $this->userMapper = $userMapper;

        return $this;
    }

    /**
     * Get the value of resetPasswordForm
     */
    public function getResetPasswordForm()
    {
        return $this->resetPasswordForm;
    }

    /**
     * Set the value of resetPasswordForm
     *
     * @return  self
     */
    public function setResetPasswordForm($resetPasswordForm)
    {
        $this->resetPasswordForm = $resetPasswordForm;

        return $this;
    }
}

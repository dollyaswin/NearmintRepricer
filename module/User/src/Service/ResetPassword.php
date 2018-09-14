<?php
namespace User\Service;

use Zend\Mvc\Controller\AbstractActionController;
use User\Mapper\ResetPasswordRequest;
use Zend\Crypt\Password\Bcrypt;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Zend\View\Model\ViewModel;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Message as MailMessage;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

class ResetPassword extends AbstractActionController
{
    use LoggerAwareTrait;

    protected $container;
    protected $config;
    protected $viewRenderer;
    protected $viewHelper;
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
            print("<pre>".print_r($e->getMessage(), true)."</pre>");
            exit;
        }

        $this->sendMail($email, $requestResetPasswordResult->getUuid());
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

        // $this->sendMail($requestRe   setPasswordResult);
    }

    public function mailTransport($config)
    {
        $transport = new SmtpTransport();
        $options   = new SmtpOptions($config['options']);
        $transport->setOptions($options);

        return $transport;
    }

    public function sendMail($email, $key)
    {
        $config  = $this->getConfig();
        $message = $config['reset_password'];
        $viewRenderer  = $this->getViewRenderer();
        $mailTransport = $this->mailTransport($config);

        if (! isset($message['template'])) {
            return;
        }

        $scheme = $this->getViewHelper()->get('ServerUrl')->getScheme();
        $host   = $this->getViewHelper()->get('ServerUrl')->getHost();
        $data   = [
            "url"  => str_replace([':scheme', ':host', ':key'], [$scheme, $host, $key], $message['url'])
        ];
        $view = new ViewModel($data);
        $view->setTemplate($message['template']);
        $html = $viewRenderer->render($view);
        $htmlMimePart = new MimePart($html);
        $htmlMimePart->setType('text/html');
        $mimeMessage  = new MimeMessage();
        $mimeMessage->addPart($htmlMimePart);

        if (! isset($message['subject'])) {
            return;
        }

        $to = trim($email);
        $mailMessage = new Message();
        $mailMessage->addFrom(
            $config['sender']['from'],
            $config['sender']['name']
        )->setSubject($message['subject']);
        $mailMessage->addTo($to);
        $mailMessage->setBody($mimeMessage);
        // print("<pre>".print_r($mailMessage,true)."</pre>");exit;
        try {
            $mailTransport->send($mailMessage);
        } catch (\Exception $e) {
            print("<pre>".print_r($e->getMessage(), true)."</pre>");
            exit;
        }
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

    /**
     * Get the value of container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the value of container
     *
     * @return  self
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the value of viewRenderer
     */
    public function getViewRenderer()
    {
        return $this->viewRenderer;
    }

    /**
     * Set the value of viewRenderer
     *
     * @return  self
     */
    public function setViewRenderer($viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;

        return $this;
    }

    /**
     * Get the value of viewHelper
     */
    public function getViewHelper()
    {
        return $this->viewHelper;
    }

    /**
     * Set the value of viewHelper
     *
     * @return  self
     */
    public function setViewHelper($viewHelper)
    {
        $this->viewHelper = $viewHelper;
        return $this;
    }

    /**
     * Get the value of config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the value of config
     *
     * @return  self
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }
}

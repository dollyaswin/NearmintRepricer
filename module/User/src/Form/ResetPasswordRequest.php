<?php
/**
 * @copyright Copyright (c) 2018
*/

namespace User\Form;

use Zend\Form\Form;
use Zend\Form\Element\Button;
use Zend\Form\Element\Email;
use Zend\Form\Element\Password;

class ResetPasswordRequest extends Form
{
    public function __construct($name)
    {
        parent::__construct($name);

        $submitBtn = new Button();
        $submitBtn->setOptions(['label' => 'Request Reset Password'])
                  ->setName('submit')
                  ->setAttribute('type', 'submit');

        $email = new Email();
        $email->setName('email')
             ->setOption('label', 'Email')
             ->setAttribute('placeholder', 'Email');

        $this->add($email)
             ->add($submitBtn);
    }
}

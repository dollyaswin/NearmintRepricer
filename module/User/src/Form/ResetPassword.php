<?php
/**
 * @copyright Copyright (c) 2018
*/

namespace User\Form;

use Zend\Form\Form;
use Zend\Form\Element\Button;
use Zend\Form\Element\Email;
use Zend\Form\Element\Password;

class ResetPassword extends Form
{
    public function __construct($name)
    {
        parent::__construct($name);

        $submitBtn = new Button();
        $submitBtn->setOptions(['label' => 'Reset Password'])
                  ->setName('submit')
                  ->setAttribute('type', 'submit');

        $pwd = new Password();
        $pwd->setName('pwd')
            ->setOption('label', 'Password')
            ->setAttribute('placeholder', 'Password');

        $repwd = new Password();
        $repwd->setName('repwd')
            ->setOption('label', 'Re-Type Password')
            ->setAttribute('placeholder', 'Re-Type Password');

        $this->add($pwd)
             ->add($repwd)
             ->add($submitBtn);
    }
}

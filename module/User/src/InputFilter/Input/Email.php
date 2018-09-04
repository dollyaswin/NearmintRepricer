<?php
/**
 * @copyright Copyright (c) 2018
 */

namespace User\InputFilter\Input;

use Zend\InputFilter\Input;
use Zend\Filter\StringTrim;
use Zend\Validator;

/**
 * Class for InputFilter Email
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class Email extends Input
{
    public function __construct($name = 'email')
    {
        parent::__construct($name);

        $filterChain = $this->getFilterChain()
                            ->attach(new StringTrim());
        $emailValidator = new Validator\EmailAddress();
        $emailValidator->setMessage('Email Address is invalid', Validator\EmailAddress::INVALID);
        $validatorChain = $this->getValidatorChain()->attach($emailValidator, true);
        $this->setRequired(true)
            ->setErrorMessage('Email Address is not valid')
            ->setFilterChain($filterChain)
            ->setValidatorChain($validatorChain);
    }
}

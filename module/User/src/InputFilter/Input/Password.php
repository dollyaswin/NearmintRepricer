<?php
/**
 * @copyright Copyright (c) 2018
 */

namespace User\InputFilter\Input;

use Zend\InputFilter\Input;
use Zend\Filter\StringTrim;
use Zend\Validator;

/**
 * Class for InputFilter Password
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class Password extends Input
{
    public function __construct($password = 'pwd')
    {
        parent::__construct($password);

        $filterChain = $this->getFilterChain()
                            ->attach(new StringTrim());
        $validator = new Validator\StringLength();
        $validator->setMin(8);
        $validator->setMessage('Password is invalid', Validator\StringLength::TOO_SHORT);
        $validatorChain = $this->getValidatorChain()->attach($validator, true);
        $this->setRequired(true)
            ->setErrorMessage('Password is not valid')
            ->setFilterChain($filterChain)
            ->setValidatorChain($validatorChain);
        return $validatorChain;
    }
}

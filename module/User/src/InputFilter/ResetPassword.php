<?php
/**
 * @copyright Copyright (c) 2018
*/

namespace User\InputFilter;

use Zend\InputFilter\InputFilter;
use User\InputFilter\Input;

/**
 * Class ResetPasswordRequest InputValidator
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class ResetPassword extends InputFilter
{

    public function __construct()
    {
        $this->add(new Input\Password());
    }
}

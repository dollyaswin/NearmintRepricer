<?php
namespace User\Form;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * ResetPassword Factory
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class ResetPasswordFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new ResetPassword('ResetPassword');
        $inputFilter = new \User\InputFilter\ResetPassword();
        $form->setInputFilter($inputFilter);
        return $form;
    }
}

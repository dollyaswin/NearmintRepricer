<?php
namespace User\Form;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * ResetPasswordRequest Factory
 *
 * @author Dolly Aswin <dolly.aswin@gmail.com>
 */
class ResetPasswordRequestFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new \User\Form\ResetPasswordRequest('ResetPasswordRequest');
        $inputFilter = new \User\InputFilter\ResetPasswordRequest();
        $form->setInputFilter($inputFilter);
        return $form;
    }
}

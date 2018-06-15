<?php

namespace User\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineDataFixtureModule\ContainerAwareInterface;
use DoctrineDataFixtureModule\ContainerAwareTrait;
use Zend\Crypt\Password\Bcrypt;

class LoadUser implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $config = $this->container->get('Config');
        $bcrypt = new Bcrypt();
        if (isset($config['zfcuser']['password_cost'])) {
            $bcrypt->setCost($config['zfcuser']['password_cost']);
        }

        $password = $bcrypt->create('12345678');
        $userData = [
            'username'  => 'andrew',
            'password'  => $password,
            'email'     => 'andrewstokinger@gmail.com',
            'displayName' => 'Andrew Stokinger',
        ];

        $user = new \ZfcUser\Entity\User;
        $user->setUsername($userData['username']);
        $user->setPassword($userData['password']);
        $user->setEmail($userData['email']);
        $user->setDisplayName($userData['displayName']);
        $manager->persist($user);
        $manager->flush();
    }
}

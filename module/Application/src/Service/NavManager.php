<?php

namespace Application\Service;

class NavManager
{
    /**
     * Auth service.
     * @var \Zend\Authentication\AuthenticationService
     */
    private $authService;

    /**
     * Url view helper.
     * @var \Zend\View\Helper\Url
     */
    private $urlHelper;

    /**
     * Constructs the service.
     */
    public function __construct($authService, $urlHelper)
    {
        $this->authService = $authService;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Menu render based on user role
     *
     * @return array
     */
    public function getMenuItems()
    {
        $navItem = array();
        $url = $this->urlHelper;
        $items = [];

        $items[] = [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'link' => $url('home'),
            'route' => ['home'],
        ];
/*
        $items[] = [
            'label' => 'About Us',
            'icon' => 'business',
            'link' => $url('about', ['action' => 'index']),
            'route' => ['about'],
        ];

        $items[] = [
            'label' => 'Service',
            'icon' => 'service',
            'link' => $url('service', ['action' => 'index']),
            'route' => ['service'],
        ];
*/
        return $items;
    }
}
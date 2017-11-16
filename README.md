# Product Repricer

## Introduction

This is a product price matching application written using the Zend Framework MVC layer and module
systems. 
This application is meant to transfer pricing information between two sites which do not have public APIs. 

This project uses a MySQL database which must be version 5.1 or higher. 

## Security
All passwords used in this project are not saved in this code.  They are expected to be set as environment variables in .htaccess. 


## Zend Framework

All relevant code is contained in the /module folder, in the default module 'Application'
In order to run a script using Zend's routing system, 
you would simply load the URL which corresponds to the Controller class and method you want. 
localhost/application/update-crystal-commerce-prices will load the method 
updateCrystalCommercePricesAction() from \module\Application\src\Controller\IndexController.php

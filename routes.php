<?php
/**
 * Created by PhpStorm.
 * User: poweruser
 * Date: 19/03/2018
 * Time: 21:28
 */
namespace Plugin\Login;


// Open http://www.example.com/hello/John
// Plugin/MyPluginName/PublicController::hello($name) will handle that

$routes['login'] = array(
    'controller' => 'SiteController',
    'action' => 'index',
);
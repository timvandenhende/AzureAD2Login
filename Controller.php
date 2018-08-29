<?php
/**
 * Created by PhpStorm.
 * User: poweruser
 * Date: 28/02/2018
 * Time: 11:14
 */
namespace Plugin\Login;

class Controller extends \Ip\WidgetController{
    public function getTitle() {
        return __('Userlogin', 'ipAdmin');
    }
}
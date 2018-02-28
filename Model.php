<?php
/**
 * Date: 27/02/2018
 * Time: 22:27
 */
namespace Plugin\AzureAD2Login;

class Model
{
    public static function Get_code_url(){
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?
                client_id='.ipGetOption('AzureAD2Login.client_id').'
                &response_type=code
                &redirect_uri='.ipConfig()->baseUrl().'Login
                &response_mode=query
                &prompt=login
                &scope='.ipGetOption('AzureAD2Login.scope');
    }
}
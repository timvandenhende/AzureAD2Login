<?php
/**
 * Date: 17/10/2017
 * Time: 16:02
 */
namespace Plugin\Login;

use Plugin\Smartsysteem\BootstrapFormModel;
use Plugin\Smartsysteem\Model;

class SiteController extends \Ip\Controller
{
    public function getTitle()
    {
        return __('Overzicht', 'ipAdmin');
    }

    public static function index(){
        $returndata = \Plugin\Smartsysteem\Verticalnavbar::RenderNavBar();
        if (ipRequest()->getQuery('logout',0)==1){
            ipUser()->logout(ipUser()->userId());
        }
        if (ipUser()->isLoggedIn()) {
            $returndata .= "<p>You are logged in</p>";
            $returndata .= '<p><a href="?logout=1">Logout</a>';
            $returndata .= '</p>';
        } else {
            include_once 'Model.php';
            if ($value = ipRequest()->getQuery('code', '') <>''){
                if (ipRequest()->getQuery('type')=='Google'){
                    include_once 'GoogleModel.php';
                    $returndata .= '<p>' . login(ipRequest()->getQuery('code')) . '</p>';
                }
                else {
                    $returndata .= '<p>' . AzureModel::login(ipRequest()->getQuery('code')) . '</p>';
                }
            }
            else {
                $returndata .= Model::ErrorAlert("Hallo!","Gelieve in te loggen");
                $returndata .= '<div class="col-md-6"><a href="'.AzureModel::Get_code_url().'"><button class="btn btn-primary">Inloggen als student of medewerker</button></a></div>';
                $returndata .= '<div class="col-md-6"><h2>Inloggen voor SMARTscholen</h2>';
                $returndata .= BootstrapFormModel::NewForm('locallogin','locallogin',ipConfig()->baseUrl());
                $returndata .= BootstrapFormModel::RenderFormElementText('Gebruikersnaam','username',"username",true);
                $returndata .= BootstrapFormModel::RenderFormElementPassword('Wachtwoord','password',"password",true);
                $returndata .= BootstrapFormModel::RenderSA('Smartsysteem.checklocallogin');
                $returndata .= BootstrapFormModel::RenderSecurityToken();
                $returndata .= BootstrapFormModel::RenderSubmitbutton('Inloggen');
                $returndata .= BootstrapFormModel::CloseForm();
                $returndata .= '</div>';
            }
        }
        return $returndata;
    }
}
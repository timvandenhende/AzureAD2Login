<?php
/**
 * Date: 17/10/2017
 * Time: 16:02
 */
namespace Plugin\Login;

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
                $returndata .= "Gelieve in te loggen";
                $returndata .= '<p><a href="'.AzureModel::Get_code_url().'">Inloggen via 0365</a></p>';
            }
        }
        return $returndata;
    }
}
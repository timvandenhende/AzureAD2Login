<?php
/**
 * Created by PhpStorm.
 * User: poweruser
 * Date: 28/02/2018
 * Time: 8:26
 */
if (ipUser()->isLoggedIn()) {
    echo "You are logged in";
} else {
    if ($value = ipRequest()->getQuery('code', '') <>''){
        echo "<p>Code ontvangen</p>";
        \Plugin\AzureAD2Login\AzureModel::login(ipRequest()->getQuery('code'));
    }
    else {
        echo "Please log in";
        echo '<a href="'.\Plugin\AzureAD2Login\AzureModel::Get_code_url().'">Inloggen via 0365</a>';
    }
}
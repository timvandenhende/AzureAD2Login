<?php
/**
 * Date: 27/02/2018
 * Time: 22:27
 */
namespace Plugin\AzureAD2Login;

class AzureModel
{
    public static function Get_code_url(){
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?
                client_id='.ipGetOption('AzureAD2Login.client_id').'
                &response_type=code
                &redirect_uri='.ipConfig()->baseUrl().'Login
                &response_mode=query
                &prompt=consent
                &scope='.ipGetOption('AzureAD2Login.scope');
    }

    public static function login($code)
    {
        // is cURL installed yet?
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();
        // Now set some options (most are optional)
        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, "https://login.microsoftonline.com/common/oauth2/v2.0/token");
        //set vars
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "grant_type=authorization_code
        &redirect_uri=".ipConfig()->baseUrl()."Login
        &client_id=".ipGetOption('AzureAD2Login.client_id')."
        &scope=".ipGetOption('AzureAD2Login.scope')."
        &client_secret=".ipGetOption('AzureAD2Login.client_secret')."
        &&code=".$code);;
        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Download the given URL, and return output
        $output = curl_exec($ch);
        //echo "curl zou moeten afgelopen zijn";
        // Close the cURL resource, and free system resources
        curl_close($ch);
        $result = json_decode($output);
        //echo $result->access_token;
        //antwoord omvormen
        $json = json_decode($output, true);
        $mail = AzureModel::GETmail($result->access_token);
        $userId = ipDb()->selectValue('user', 'id', array('email' =>$mail ));
        $validuntil = date("Y-m-d H:i:s", strtotime("+1 hours"))
        if ($userId <> ""){
            ipUser()->login($userId);
            ipDb()->update(
                'user',
                array('id' => ipUser()->userId()),
                array('hash' => $result->access_token, 'resetSecret' => $result->refresh_token, 'resetTime' => $validuntil)
            );
        }
        else{
            ipDb()->insert('user', array('username' => $mail, 'email' => $mail, 'hash' => $result->access_token, 'resetSecret' => $result->refresh_token, 'resetTime' => $validuntil));
            $userId = ipDb()->selectValue('user', 'id', array('email' =>$mail ));
            ipUser()->login($userId);
        }
        return new \Ip\Response\Redirect(ipConfig()->baseUrl().'Login');
    }

    public static function refresh_token(){
        // is cURL installed yet?
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();
        // Now set some options (most are optional)
        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, "https://login.microsoftonline.com/common/oauth2/v2.0/token");
        //set vars
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "grant_type=refresh_token
                &redirect_uri=".ipConfig()->baseUrl()."Login
                &client_id=".ipGetOption('AzureAD2Login.client_id')."
                &scope=".ipGetOption('AzureAD2Login.scope')."
                &client_secret=".ipGetOption('AzureAD2Login.client_secret')."
                &refresh_token=".$_COOKIE['refreshtoken']);

        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Download the given URL, and return output
        $output = curl_exec($ch);
        //echo "curl zou moeten afgelopen zijn";
        // Close the cURL resource, and free system resources
        curl_close($ch);
        $result = json_decode($output);
        //echo $result->access_token;
        //$json = json_decode($output, true);
        //$mail = $json['value'];
        //return $mail;
        ipDb()->update(
            'user',
            array('id' => ipUser()->userId()),
            array('hash' => $result->access_token, 'resetSecret' => $result->refresh_token)
        );
        return new \Ip\Response\Redirect(ipConfig()->baseUrl().'Login');
    }

    static function GETmail($token)
    {
        // is cURL installed yet?
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();
        // Now set some options (most are optional)
        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, "https://graph.microsoft.com/v1.0/me/mail");
        // User agent
        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        //set headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $token)
        );
        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Download the given URL, and return output
        $output = curl_exec($ch);
        // Close the cURL resource, and free system resources
        curl_close($ch);
        //antwoord omvormen
        $json = json_decode($output, true);
        $mail = $json['value'];
        return $mail;
    }
}
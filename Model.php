<?php
/**
 * Date: 27/02/2018
 * Time: 22:27
 */
namespace Plugin\Login;

class AzureModel
{
    public static function Get_code_url(){
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?
                client_id='.ipGetOption('Login.client_id').'
                &response_type=code
                &redirect_uri='.ipConfig()->baseUrl().'login
                &response_mode=query
                &prompt=consent
                &scope='.ipGetOption('Login.scope');
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
        &redirect_uri=".ipConfig()->baseUrl()."login
        &client_id=".ipGetOption('Login.client_id')."
        &scope=".ipGetOption('Login.scope')."
        &client_secret=".ipGetOption('Login.client_secret')."
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
        $username = AzureModel::GETusername($result->access_token);
        $userId = ipDb()->selectValue('user', 'id', array('username' =>$username ));
        echo "userid:".$userId;
        $validuntil = date("Y-m-d H:i:s", strtotime("+1 hours"));
        if (empty($userId)){
            return '<div class="alert alert-danger">
                  <strong>Error!</strong> Je hebt geen toegang tot deze applicatie met deze account. Contacteer <a href="mailto:tim.vandenhende@arteveldehs.be">Tim Vanden Hende</a> indien dit om een fout gaat.
                </div>';
        }
        else{
            ipUser()->login($userId);
            //update profile image
            \Plugin\Login\AzureModel::GETprofilePicture();
            try{
                ipDb()->update(
                    'user',
                    array('hash' => $result->access_token, 'resetSecret' => $result->refresh_token, 'resetTime' => $validuntil),
                    array('id' => ipUser()->userId())
                );
            }catch (\Ip\DbException $e){
                ipLog()->log('Login', 'Error while executing my database query: '.$e);
            }
        }
        return "<p>Welkom in de planningstool voor de SMART-stage</p><p><a href='".ipConfig()->baseUrl()."/dashboard'>Klik hier om naar je dashboard te gaan.</a></p>";
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
                &redirect_uri=".ipConfig()->baseUrl()."login
                &client_id=".ipGetOption('Login.client_id')."
                &scope=".ipGetOption('Login.scope')."
                &client_secret=".ipGetOption('Login.client_secret')."
                &refresh_token=".ipDb()->selectValue('user','resetSecret',array('id' => ipUser()->userId())));

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
        //echo print_r($result);
        //echo $result->access_token;
        //$json = json_decode($output, true);
        //$mail = $json['value'];
        //return $mail;
        $validuntil = date("Y-m-d H:i:s", strtotime("+1 hours"));
        ipDb()->update(
            'user',
            array('hash' => $result->access_token, 'resetSecret' => $result->refresh_token, 'resetTime' => $validuntil),
            array('id' => ipUser()->userId())
        );
        return new \Ip\Response\Redirect(ipConfig()->baseUrl().'login');
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

    static function GETusername($token)
    {
        // is cURL installed yet?
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }
        // OK cool - then let's create a new cURL resource handle
        $ch = curl_init();
        // Now set some options (most are optional)
        // Set URL to download
        curl_setopt($ch, CURLOPT_URL, "https://graph.microsoft.com/v1.0/me/userPrincipalName");
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

    public static function GETprofilePicture(){
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://graph.microsoft.com/v1.0/me/photo/%24value',
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => null,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer '.ipDb()->selectValue('user','hash',array('id' => ipUser()->userId())),
                'content-type: image/jpeg; charset=utf-8'
            ) ,
        ));
        $response = curl_exec($ch);
        // Close the cURL resource, and free     system resources
        curl_close($ch);
        $returndata = 'photo <img src="data:image/jpeg;base64,' . base64_encode($response) . '"/>';
        ipDb()->update(
            'user',
            array('Image' => $response),
            array('id' => ipUser()->userId())
        );
        return $returndata;
    }
}
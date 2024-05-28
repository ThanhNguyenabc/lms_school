<?php
die('<h1><center>Thanks for your support! The system will stop you from access for now.</h1>');
//define("BASE_URL","https://".$_SERVER["HTTP_HOST"].pathinfo($_SERVER["PHP_SELF"])["dirname"]);
define("BASE_URL","https://lms.ila.edu.vn/school/");

//Trung Tran Ngoc Nguyen - 19-06-2023
## Begin - Check login with Google OAuth 2 SSO Function

function open_http($url, $method = false, $params = null)
{

    if (!function_exists('curl_init')) {
        die('ERROR: CURL library not found!');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, $method);
    if ($method == true && isset($params)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }
    curl_setopt($ch,  CURLOPT_HTTPHEADER, array(
        'Content-Length: '.strlen($params),
        'Cache-Control: no-store, no-cache, must-revalidate',
        "Expires: " . date("r")
    ));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

// 		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function get_url_oauth2()
{
	//echo('<pre>');print_r($_SERVER);print_r(BASE_URL);die;
	//echo(BASE_URL);die;
    $redirect = BASE_URL.'ssologin.php?goauth=y&crossUser=check';

    $scope = urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email');
	
	$myfile      = fopen(__DIR__."/../services/google/client_secret.json", "r");
	$access_ids = json_decode(fgets($myfile),true);
	fclose($myfile);
	//echo('<pre>');print_r($access_ids);die;

    $params = array(
        'response_type=code',
        'redirect_uri=' . urlencode($redirect),
        'client_id=' . $access_ids['web']['client_id'],
        'scope=' . $scope
        //,'access_type=offline'
        //,'approval_prompt=force'
    );
    $params = implode('&', $params);

    $url = 'https://accounts.google.com/o/oauth2/auth?'.$params;
    //die($url);
    return $url;
}

function get_google_oauth2_token()
{
    
    $url = get_url_oauth2();

    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Expires: " . date("r"));
    header('Location:' . $url);
}

function check_google_oauth2_code()
{
    $code = $_REQUEST['code'];
    //die($code);

    if ($code) {

        // get access_token for google API
        $redirect = urlencode(BASE_URL.'ssologin.php?goauth=y&crossUser=check');
        $scope = urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email');

        $myfile      = fopen(__DIR__."/../services/google/client_secret.json", "r");
		$access_ids = json_decode(fgets($myfile),true);
		fclose($myfile);

        $params = array(
            'client_id=' . $access_ids['web']['client_id'],
            'client_secret='.$access_ids['web']['client_secret'],
            'grant_type=authorization_code',
            'code=' . $code,
            'redirect_uri=' . $redirect,
            'scope=' . $scope

        );
        $params = implode('&', $params);
        //echo "<pre>";print_r($params);die;
        $url = 'https://accounts.google.com/o/oauth2/token';
        $request = json_decode(open_http($url, true, $params));
        //echo "<pre>".$params;print_r($request);die;
        if(empty($request)){
            echo 'Error - empty access tocken';
            exit;
        }

        //Get user info
        //
        // id 				The value of this field is an immutable identifier for the logged-in user
        // email 			The email address of the logged in user
        // verified_email 	A flag that indicates whether or not Google has been able to verify the email address.
        // name 			The full name of the logged in user
        // given_name 		The first name of the logged in user
        // family_name 		The last name of the logged in user
        // picture 			The URL to the user's profile picture. If the user has no public profile, this field is not included.
        // locale 			The user's registered locale. If the user has no public profile, this field is not included.
        // timezone 		the default timezone of the logged in user
        // gender 			the gender of the logged in user (other|female|male)

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$request->access_token;
        $request = json_decode(open_http($url));
		//echo "<pre>".$url;print_r($request);die;
		//echo("<pre>"); print_R($url); exit;
        if(empty($request)){
            echo 'Error - empty user data';
            exit;
        }
        else if(!empty($request->error))
		{
            echo '<pre>';
			var_dump($request->error);
			echo '</pre>';
            exit;
        }
        $returnRequest = new stdClass();
        $returnRequest->first_name  = $request->given_name;
        $returnRequest->last_name   = $request->family_name;
        $returnRequest->email       = $request->email;
        $returnRequest->id          = $request->id;
        $returnRequest->real_name   = $request->given_name.' '.$request->family_name;
        $returnRequest->display_name = $request->name;
        $returnRequest->all_request  = $request;
        return $returnRequest;
    }
    else{
        echo 'Error - empty code';
        exit;
    }
}
## End - Check login with Google OAuth 2 SSO

// Get token via Google
if(isset($_REQUEST['crossUser']) && $_REQUEST['crossUser']=="google")
	get_google_oauth2_token();
// Verify user via Google
if(isset($_REQUEST['crossUser']) && $_REQUEST['crossUser']=="check"){
	$challenge = check_google_oauth2_code();
	if(is_object($challenge)){
		//include_once "application/mysql-sessions.php";
		session_start();
		require_once ("./application/data/backend.php");
		Core_SSOLogin($challenge);
	}

	header('Location:' . BASE_URL);
}
?>
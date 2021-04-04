<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING );
/*
//application login setup
$Discord = array();
$Discord['C_ID'] = '??????'; //OAUTH2 CLIENT ID
$Discord['C_SE'] = '???????'; //OAUTH2 CLIENT SECRET
$Discord['server_id'] = '?????????'; //Server ID. if state is 1, this set the state to 2 if is join, or 3 if not to the server
//$Discord['header'] = "NO";//Remove the Default HTML <header>

//use ?action=login to login
//use ?action=logout to logout and delete all cookies
*/
//define some keys
define('OAUTH2_CLIENT_ID', $Discord['C_ID']);
define('OAUTH2_CLIENT_SECRET', $Discord['C_SE']);
define('P_KEY', hex2bin(OAUTH2_CLIENT_SECRET. sha1(OAUTH2_CLIENT_SECRET)));//private key, generated using the client secret
define('C_USER', substr(sha1(OAUTH2_CLIENT_ID),0,10));//name of the encrypted cookie
define('access_token', C_USER."_A");
define('refresh_token', C_USER."_R");

$reload = '<!DOCTYPE html><meta http-equiv="refresh" content="0; " />Loading...';
$authorizeURL = 'https://discordapp.com/api/oauth2/authorize';
$tokenURL = 'https://discordapp.com/api/oauth2/token';
$userURL = 'https://discordapp.com/api/users/@me';
$guildURL = 'https://discordapp.com/api/users/@me/guilds';

// Start the login process by sending the user to Discord's authorization page
if(get('action') == 'login') {
  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    'redirect_uri' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],
    'response_type' => 'code',
    'scope' => 'identify guilds'
  );

  // Redirect the user to Discord's authorization page
  die(header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params)));
}

// Logout from discord
if(get('action') == 'logout') {
  // This must to logout you, but it didn't worked(
	setcookie(access_token, "", time()-10,"/");
	setcookie(refresh_token, "", time()-10,"/");
	setcookie(C_USER, "", time()-10,"/");
	die(header('Location: ' . $_SERVER['PHP_SELF']));
}

// Get access_token and refresh_token with code
if(get('code')) {
  // Exchange the auth code for a token
  $token = apiRequest($tokenURL, array(
    "grant_type" => "authorization_code",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],
    'code' => get('code')
  ));

setcookie(access_token, $token['access_token'], time()+$token['expires_in'],"/");
setcookie(refresh_token, $token['refresh_token'], time()+$token['expires_in']+$token['expires_in'],"/");
header('Location: ' . $_SERVER['PHP_SELF']);
}

// Get access_token with refresh_token
if(get('action') == 'refresh') {
  $token = apiRequest($tokenURL, array(
    "grant_type" => "refresh_token",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],
	'refresh_token' => cookie(refresh_token)
	));
	setcookie(access_token, $token['access_token'], time()+$token['expires_in'],"/");
	setcookie(refresh_token, $token['refresh_token'], time()+$token['expires_in']+$token['expires_in'],"/");
header('Location: ' . $_SERVER['PHP_SELF']);
}

//login check
if(cookie(access_token)) {
	$Discord['state'] = 1;
	if (cookie(C_USER)){
		//get encripted cookie
		$Discord['user'] = json_decode(Cyper::decrypt(cookie(C_USER),P_KEY),true);
		//check expire time
		if (time() > $Discord['user']['expire']){
			setcookie(C_USER, "", time()-10,"/");
			die($reload);
		}
	}else{
		//get ifo from api an encrypt cookie
		$Discord['user'] = apiRequest($userURL);
		$Discord['user']['expire'] = time()+(3600*48);
		setcookie(C_USER, Cyper::encrypt(json_encode($Discord['user']),P_KEY), time()+(3600*48),"/");
	}

	//If tokens fail try re login
	if (isset($Discord['user']['message'])){
		setcookie(C_USER, '', time()-10,"/");
		if($Discord['user']['message'] == '401: Unauthorized'){
			setcookie(access_token, '', time()-10,"/");
			header('Location: ' . $_SERVER['PHP_SELF'].'?action=login');
		}
	}

	if(isset($Discord['user']['message'])){
		setcookie(C_USER, "", time()-10,"/");
	}
 
	if (strlen($Discord['user']['avatar']) != 0){
		if(substr($Discord['user']['avatar'],0,4) != "http"){
			$avatar = 'https://cdn.discordapp.com/avatars/'.$Discord['user']['id'].'/'.$Discord['user']['avatar'].'.png?size=2048';
			$Discord['user']['avatar'] = $avatar;
		} else {
			$avatar = $Discord['user']['avatar'];
		}
	}
	else
		$avatar = 'https://cdn.discordapp.com/embed/avatars/0.png';
	
	$headerH =  '<header class=header-login><br><img class=rounded-circle src="'.$avatar.'" />
	<h4 style="color: #ffffff; display: inline; position:relative;top:-30;">' . $Discord['user']['username']. '</h4>
	<button class=butt-logout onclick="window.location.href=\'?action=logout\'">Logout</button></header>';
	
	if (!isset($Discord['user']['isJoin'])){
		//check if is join
		if (isset($Discord['server_id'])){
			if (strlen($Discord['server_id'])>0){
				$Discord['guild'] = apiRequest($guildURL);
				if(isset($guild['retry_after'])||isset($Discord['user']['retry_after'])){
					die($reload);
				}

				if (guild_join($Discord['server_id'],$Discord['guild'])){
					$Discord['state'] = 2;
					$Discord['user']['isJoin'] = true;
				}else{
					$Discord['state'] = 3;
					$Discord['user']['isJoin'] = false;
				}
				setcookie(C_USER, Cyper::encrypt(json_encode($Discord['user']),P_KEY), time()+(3600*48),"/");
			}
		}
	} else {
		if ($Discord['user']['isJoin']){
			$Discord['state'] = 2;
		}else{
			$Discord['state'] = 3;
		}
	}
	//echo '<pre>'; die(print_r($Discord['user']));//use this to debug

} else {
	if (cookie(refresh_token)) header('Location: '.$_SERVER['PHP_SELF'].'?action=refresh');
	$Discord['state'] = 0;
	$avatar = 'https://cdn.discordapp.com/embed/avatars/0.png';
	$headerH = '
	<header class=header-login><br><img class=rounded-circle src="'.$avatar.'"/>
	<button class=butt-login onclick="window.location.href=\'?action=login\'">Login</button>
	<h4 class=text-login >Not logged in</h4></header>';
}

//set the default HTML Header
if ($Discord['header'] != "NO"){
echo '
<style>
.rounded-circle{border-radius:50%;width: 50x; height: 50px;}
.butt-login{background-color: #3e2ca3; border: 2px solid white; color: white; position:absolute;top:55;left:65;width:auto;max-width:1000px;}
.text-login{color: #ffffff; display: inline; position:relative;top:-45;}
.butt-logout{background-color: #ed3737; border: 2px solid white; color: white; position:absolute;top:55;left:65;width:auto;max-width:1000px}
.header-login{border-bottom:#000;border-width:0 0 3px 0;border-style:solid;border-color:#fff;background-color:#000;}
</style>
'.$headerH;
}

function guild_join($G_ID,$G_Arr)
{global $Discord;
	try{
		for ($i=0; $i<count($G_Arr); $i++)
		{
			if ($G_Arr[$i]['id'] == $G_ID){return true;}
		}
	}catch(Exception $ex)
	{ // If critical functions fail, we catch the reason why
		echo '<pre>';
		print_r($G_Arr);
		echo '</pre>';
	}
	return false;
}

function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);


  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers[] = 'Accept: application/json';

  if(cookie(access_token))
    $headers[] = 'Authorization: Bearer ' . cookie(access_token);

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  return json_decode($response,true);
}

function get($key, $default=NULL) {
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function session($key, $default=NULL) {
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}

function cookie($key, $default=NULL) {
  return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : $default;
}
class Cyper {
    const METHOD = 'aes-256-ctr';
    public static function encrypt($message, $key, $encode = true)
    {
		$message = gzcompress($message,9);
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }

    public static function decrypt($message, $key, $encoded = true)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );
		
		$plaintext = gzuncompress($plaintext);
        return $plaintext;
    }

}
?>
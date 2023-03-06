<?php
//application login setup
$Discord['C_ID'] = ???????????;//OAUTH2 CLIENT ID
$Discord['C_SE'] = '¿¿¿¿¿¿¿¿¿';//OAUTH2 CLIENT SECRET

/*
$Discord = array();
$Discord['server_id'] = '?????????'; //Server ID. if state is 1, this set the state to 2 if is join, or 3 if not to the server
//$Discord['header'] = "NO";//Remove the Default HTML <header>

//use ?action=login to login
//use ?action=logout to logout and delete all cookies
ini_set('display_startup_errors', 1);
ini_set('max_execution_time', 300); //300 seconds = 5 minutes. In case if your CURL is slow and is loading too much (Can be IPv6 problem)
*/


/*
    DISCORD AUTH 
*/
//Error handle
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', 1);
//HTTPS ONLY
if ($_SERVER['REQUEST_SCHEME'] == 'http') exit(header('Location: ' .mainfile()));
    
//define some keys
define('OAUTH2_CLIENT_ID', $Discord['C_ID']);
define('OAUTH2_CLIENT_SECRET', $Discord['C_SE']);
define('P_KEY', hex2bin(OAUTH2_CLIENT_SECRET. sha1(OAUTH2_CLIENT_SECRET)));//private key, generated using the client secret
define('C_USER', N(OAUTH2_CLIENT_ID));//name of the encrypted cookie
define('access_token', N(C_USER."_A"));
define('refresh_token', N(C_USER."_R"));
define('expire_token', N(C_USER."_E"));
define('expire_time', 604000);//1 week
define('URI', N(C_USER."_U"));

//Var Static
$reload = '<!DOCTYPE html><meta http-equiv="refresh" content="0; " />Loading...';
$authorizeURL = 'https://discordapp.com/api/oauth2/authorize';
$tokenURL = 'https://discordapp.com/api/oauth2/token';
$userURL = 'https://discordapp.com/api/users/@me';
$guildURL = 'https://discordapp.com/api/users/@me/guilds';


// Start the login process by sending the user to Discord's authorization page
if(get('action') == 'login') {
  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    'redirect_uri' => realfile(),
    'response_type' => 'code',
//      'scope' => 'identify guilds guilds.join'
    'scope' => 'identify email'
  );
    if (isset($Discord['server_id'])){
        if (strlen($Discord['server_id'])>0){
            $params['scope'].=' guilds';
        }
    }
  
    //Redirect Cookie
    $lock = Cyper::encrypt(mainfile());
    addcookie(URI, $lock ,3650);
  // Redirect the user to Discord's authorization page
  die(header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params)));
}

// Logout from discord
if(get('action') == 'logout') {
  // This must to logout you
    delcookie(access_token);
    delcookie(refresh_token);
    delcookie(C_USER);
    //delcookie(expire_token);
    delcookie(URI);
    GoLoc();
}

//Get error code
if(get('error')) {
    if(ismain()){
        $bodyH ='<h2>'.get('error').'</h2><p>'.get('error_description').'<p>';
        if(cookie(URI)){
            $urk = Cyper::decrypt(cookie(URI));
            if (substr($urk,-9,9) == 'login.php'){
                $bodyH .= 'Redirect Error';
                delcookie(URI);
            } else {
                $bodyH .=  delayurl($urk.'?'.$_SERVER["QUERY_STRING"],5);
            }
        } else {
            $bodyH .= delayurl("/",15).'Redirect Error';
        }
    }
}

// Get access_token and refresh_token with code
if(get('code')) {
  // Exchange the auth code for a token
  $token = apiRequest($tokenURL, array(
    "grant_type" => "authorization_code",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => realfile(),
    'code' => get('code')
  ));

    if (isset($token['error'])){
        //do any
        GoLoc('?error='.$token['error'].'&error_description='.$token['error_description']);
    }
    //addcookie(expire_token, Cyper::encrypt($token['expires_in']), $token['expires_in']);
    addcookie(access_token, Cyper::encrypt($token['access_token']), expire_time);
    addcookie(refresh_token, Cyper::encrypt($token['refresh_token']), expire_time*2);
    if(cookie(URI)){
        $urk = Cyper::decrypt(cookie(URI));
        //delcookie(URI);
        redirect($urk);
    }
echo '<pre>';print_r($token);
die('Redirect Error');
}

// Get access_token with refresh_token
if(get('action') == 'refresh' && !cookie(access_token)) {
  $token = apiRequest($tokenURL, array(
    "grant_type" => "refresh_token",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => realfile(),
    'refresh_token' => Cyper::decrypt(cookie(refresh_token))
    ));
    
    //addcookie(expire_token, Cyper::encrypt($token['expires_in']), $token['expires_in']);
    addcookie(access_token, Cyper::encrypt($token['access_token']), expire_time);
    addcookie(refresh_token, Cyper::encrypt($token['refresh_token']), expire_time*2);
    if (!isset($token['access_token'])) {GoLoc('?action=login');}
GoLoc();
}

//login check
if(cookie(access_token)||cookie(C_USER)) {
    $Discord['state'] = 1;
    //echo '<pre>';print_r(session_get_cookie_params ($_COOKIE[ C_USER ]) );die();
    if (cookie(C_USER)){
        //get encripted cookie
        $Discord['user'] = json_decode(Cyper::decrypt(cookie(C_USER)),true);
        //check expire time
        if (time() > $Discord['user']['expire']){
            
            if(!cookie(access_token))
            addcookie(access_token, $Discord['user']['access_token'], expire_time);
            
            if(!cookie(refresh_token))
            addcookie(refresh_token, $Discord['user']['refresh_token'], expire_time);
            delcookie(C_USER);
            die($reload);
        }
    }else{
        //get ifo from api an encrypt cookie
        //echo Cyper::decrypt(cookie(expire_token)).'<br>';
        $Discord['user'] = apiRequest($userURL);
        $Discord['user']['expire'] = time()+expire_time;
        $Discord['user']['access_token'] = cookie(access_token);
        $Discord['user']['refresh_token'] = cookie(refresh_token);
        FormatAvatar($Discord);
        addcookie(C_USER, Cyper::encrypt(json_encode($Discord['user'])), expire_time);
        //delcookie(expire_token);
    }

    //If tokens fail try re login
    if (isset($Discord['user']['message'])){
        delcookie(C_USER);
        if($Discord['user']['message'] == '401: Unauthorized'){
            delcookie(access_token);
            GoLoc('?action=refresh');
        }
    }

    if(isset($Discord['user']['message'])){
        delcookie(C_USER);
    }
 
    if (strlen($Discord['user']['avatar']) != 0){
        $avatar = $Discord['user']['avatar'];
    } else
        $avatar = 'https://cdn.discordapp.com/embed/avatars/0.png';
        
        
    
    $headerH =  '<header class=header-login><span id=SideH></span><br><img class=rounded-circle src="'.$avatar.'" />
    <h4 class=text-login>' . $Discord['user']['username']. '</h4>
    <button class=butt-logout onclick="window.location.href=\'?action=logout\'">Logout</button></header>';
    
    if (!isset($Discord['user']['isJoin'])){
        //check if is join
        if (isset($Discord['server_id'])){
            if (strlen($Discord['server_id'])>0){
                $Discord['guild'] = apiRequest($guildURL);
                if(isset($guild['retry_after'])||isset($Discord['user']['retry_after'])){
                    die($reload);
                }

                if (isJoin($Discord['server_id'],$Discord['guild'])){
                    $Discord['state'] = 2;
                    $Discord['user']['isJoin'] = true;
                }else{
                    $Discord['state'] = 3;
                    //$Discord['user']['isJoin'] = false;
                }
                addcookie(C_USER, Cyper::encrypt(json_encode($Discord['user'])), expire_time);
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
    if(ismain() && !get('error')){$bodyH .= delayurl("/",15).'How the hell did you get here???';}
} else {
    if (cookie(refresh_token)) GoLoc('?action=refresh');
    $Discord['state'] = 0;
    $avatar = 'https://cdn.discordapp.com/embed/avatars/0.png';
    $headerH = '
    <header class=header-login><span id=SideH></span><br>
    <img class=rounded-circle src="'.$avatar.'"/>
    <button class=butt-login onclick="window.location.href=\'?action=login\'">Login</button>
    <h4 class=text-login >Not logged in</h4></header>';
}

//set the default HTML Header
$headerH = '<style>
.rounded-circle{border-radius:50%;width: 50x; height: 50px;}
.text-login{color: #333333; display: inline; position:relative;top:-45;}
#SideH {color: #333333; display: inline; position:absolute;left:160;}
.butt-login{background-color: #3e2ca3; border: 2px solid #eeeeee; color: white; position:absolute;top:55;left:65;width:auto;max-width:1000px;}
.butt-logout{background-color: #ed3737; border: 2px solid #eeeeee; color: white; position:absolute;top:55;left:65;width:auto;max-width:1000px}
.header-login{border-bottom:#eeeeee;border-width:0 0 3px 0;border-style:solid;border-color:#eeeeee;background-color:#ffffff;}
</style>
'.$headerH;
if ($Discord['header'] != "NO"){
    echo $headerH;
}
if (strlen($bodyH) > 0) echo $bodyH;

//main functions
function isJoin($G_ID,$G_Arr){
    //return false;
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

  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers[] = 'Accept: application/json';
  if(cookie(access_token))
    $headers[] = 'Authorization: Bearer ' . Cyper::decrypt(cookie(access_token));
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

function delcookie($key) {
    $config = array (
        'expires' => time()-10,
        'path' => '/',
        'domain' => $_SERVER["HTTP_HOST"], // leading dot for compatibility or use subdomain
        'secure' => false,     // or false
        'httponly' => true,    // or false
        'samesite' => 'Lax' // None || Lax  || Strict
    );
    setcookie($key, '', $config);   
}

function addcookie($key, $val,$time) {
    $config = array (
        'expires' => time()+$time,
        'path' => '/',
        'domain' => $_SERVER["HTTP_HOST"], // leading dot for compatibility or use subdomain
        'secure' => false,     // or false
        'httponly' => true,    // or false
        'samesite' => 'Lax' // None || Lax  || Strict
    );
    setcookie($key, $val, $config);   
}

function cookie($key, $default=NULL) {
  return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : $default;
}
function GoLoc($extra = ''){
    
    if (strlen($extra)>0){
        die(header('Location: '.$_SERVER['PHP_SELF'].$extra));
    } else {
        die(header('Location: '.mainfile()));
    }
}
function delayurl($url,$delay=0){
    return '<!DOCTYPE html><meta http-equiv="refresh" content="'.$delay.'; url='.$url.'" />';

}
function redirect($url){
    die(header('Location: '.$url));
}

function N($data){
    return substr(sha1($data),0,10);
}

function realfile(){
    $url = __FILE__;
    $url = str_replace("\\","/",$url);
    $root = $_SERVER['DOCUMENT_ROOT'];
    $url = str_replace($root,"",$url);
    return 'https://' . $_SERVER["HTTP_HOST"] .$url;
}
function ismain(){
    if(strpos('-'.mainfile(),realfile())){
        return true;
    }
return false;
}
function mainfile(){
    $uri = $_SERVER["REQUEST_URI"];
    if (get('action')){
        $uri = str_replace("?".$_SERVER["QUERY_STRING"],"",$uri);
    }
    return 'https://' . $_SERVER["HTTP_HOST"] . $uri;
}
function FormatAvatar(&$Discord){
    if (strlen($Discord['user']['avatar']) != 0){
        if(substr($Discord['user']['avatar'],0,4) != "http"){
           $Discord['user']['avatar'] = 'https://cdn.discordapp.com/avatars/'.$Discord['user']['id'].'/'.$Discord['user']['avatar'].'.png?size=2048';
        }
    }else
        $Discord['user']['avatar'] = 'https://cdn.discordapp.com/embed/avatars/3.png';
}


class Cyper {
    const METHOD = 'aes-256-ctr';
    public static function encrypt($message, $key = P_KEY, $encode = true)
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

    public static function decrypt($message, $key = P_KEY, $encoded = true)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                //throw new Exception('Encryption failure');
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
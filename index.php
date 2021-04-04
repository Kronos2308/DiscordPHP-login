<?php
$Discord = array();
$Discord['C_ID'] = '??????'; //OAUTH2 CLIENT ID
$Discord['C_SE'] = '???????'; //OAUTH2 CLIENT SECRET
$Discord['server_id'] = '?????????'; //Server ID. if state is 1, this set the state to 2 if is join, or 3 if not to the server
//$Discord['header'] = "NO";//Remove the Default HTML <header>

require "login.php";




print_r($Discord);
//Examples
switch ($Discord['state']){
	case 0:
	//not login code
	break;

	case 1:
	// login code
	case 2:
	// login and join server
	break;

	case 3:
	//login but not join to server code
	break;

}
if ($Discord['state'] == 2){
	//login and join server
	print_r($Discord['user']);
}
if ($Discord['state'] == 0){
	//not login code
}

?>
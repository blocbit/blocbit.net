<?php
require_once "../.config/config.php";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');


if (isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], $site) === false and strpos(strtolower($_SERVER['HTTP_REFERER']), $site_domain) === false) {
	header('HTTP/1.0 403 Forbidden');
	logdie("Referer error.");
}

logtext("Solution: Parsing parameters...");
logtext($_POST);

$_POST["work_id"] = 0+$_POST["work_id"];

$auth_address = $_POST["auth_address"];
$user_name = trim($_POST["user_name"], " ");
$public_key = $_POST["public_key"];
$btc_address = $_POST["btc_address"];
$pin = $_POST["pin"];
$ip = get_ip();

if (!preg_match("#^[a-z0-9]{1,17}$#", $user_name)) {
	header("HTTP/1.0 400 Bad Request");
	logdie("Only english letters and numbers allowed in username.");
}

if (!preg_match("#^[A-Za-z0-9]{1,40}$#", $auth_address)) {
	header("HTTP/1.0 400 Bad Request");
	logdie("Bad address.");
}

/*logtext("Checking advocates...");
if (file_exists($ausers_json)){
	unlink($users_json);
	rename ($ausers_json,$users_json);	
}*/

logtext("Loading archive users...");
$users = array();
foreach (glob(dirname($users_json) . "/*json") as $data_file_path) {
	$json_root = json_decode(file_get_contents($data_file_path), true);
	if (isset($json_root["users"]))
		$data = $json_root["users"];
	else
		$data = $json_root["certs"];
	foreach ($data as $data_user_name => $data_cert) {
		if (!isset($users[$data_user_name]) or $users[$data_user_name]{0} == "@")
			$users[$data_user_name] = $data_cert;
	}
}

foreach ($users as $data_user_name => $data_cert) {
	if (strtolower($data_user_name) == strtolower($user_name)) {
		header("HTTP/1.0 400 Bad Request");
		logdie("Username $user_name already exits.");
	}
	if (strpos($data_cert, ",".$auth_address.",") !== false) {
		header("HTTP/1.0 400 Bad Request");
		logdie("Address $auth_address already exits.");
	}
}

/* Hash pin*/
$options = ['cost' => 12,];
$hash = password_hash($pin, PASSWORD_BCRYPT, $options);

logtext($public_key."-pk");
logtext($btc_address."-btc");
logtext($hash."-hash");

logtext("Verify work...");
$res = verifyWork($_POST["work_id"], $_POST["work_solution"]);
if (!$res or !$_POST["work_solution"]) {
	header("HTTP/1.0 400 Bad Request");
	logdie("Bad solution :(");
}

logtext("Good solution, signing...");
chdir($zeronet_dir);
$out = array();
exec("python zeronet.py --debug cryptSign $auth_address#web/$user_name $privatekey 2>&1", $out);
$sign = $out[sizeof($out)-1];
$back = implode("\n", $out);
logtext($back);
logtext($sign);
logtext($ip);

if ($sign{strlen($sign)-1} != "=") logdie("User sign error, please contact site owner!");


$auser_json = $ausers_dir.$user_name.".json";
$data = json_decode(file_get_contents($users_json), true);
$wdata = json_decode(file_get_contents($wl_json), true);


//$wdata =array();

$sdata =array();
$sdata['name']  = $user_name ;
$sdata['id']  = "" ;
$sdata['webauthnkeys']  = "" ;
$sdata['title']  = "adv" ;
$sdata['add']  = $auth_address ;
$sdata['sign']  = $sign ;
$sdata['blockey']  = $public_key ;
$sdata['btckey']  = $btc_address ;
$sdata['pinhash']  = $hash ;


$data["users"][$user_name] = "web,$auth_address,$sign";
ksort($data["users"]);
$json_out = json_encode($data, JSON_PRETTY_PRINT);

logtext("Adding advocate bit...");	
$u = fopen($auser_json, "w");
fwrite($u, json_encode($sdata, JSON_PRETTY_PRINT));
fclose($u);

logtext("Adding to whitelist...");
$wdata[$public_key] = $ip;
$w = fopen($wl_json, "w");
fwrite($w, json_encode($wdata, JSON_PRETTY_PRINT));
fclose($w);

logtext("Adding to advocates...");
$a = fopen($ausers_json, "w");
fwrite($a, $json_out);
fclose($a);
//chmod($ausers_json, 0666);

logtext("Signing...");
$out = array();
exec("python zeronet.py --debug siteSign $site $privatekey --publish 2>&1", $out);
$out = implode("\n", $out);
logtext($out);
if (strpos($out, "content.json signed!") === false) {
	header("HTTP/1.0 500 Internal Server Error");
	logdie("Site sign error, please contact site owner!");
}else{
	header("HTTP/1.0 200 Ok");
	echo "Success, your account will be active in time for the next bloc cycle";		
}

/*
logtext("Publishing...");
$server_ip = $_SERVER['SERVER_ADDR'];
$out = array();
exec("python zeronet.py --debug --ip_external $server_ip sitePublish $site 2>&1", $out);
$out = implode("\n", $out);
logtext($out);
if (strpos($out, "Successfuly published") === false) {
	header("HTTP/1.0 500 Internal Server Error");
	logdie("Publish error, please contact site owner!");
}
*/

?>
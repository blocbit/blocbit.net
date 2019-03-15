<?php
require_once "../.config/config.php";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] != "POST") {
	logdie("Not allowed");
}
/*
if (isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], $site) === false and strpos(strtolower($_SERVER['HTTP_REFERER']), $site_domain) === false) {
	header('HTTP/1.0 403 Forbidden');
	logdie("Referer error.");
}
/* will not work if fopen off
function getBalance($address) {
    return file_get_contents('https://blockchain.info/q/addressbalance/'. $address);
}

echo getBalance('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa');

$url = 'https://blockchain.info/q/addressbalance/1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa?format=json';

*/

$public_key = htmlspecialchars($_GET["publickey"]);
$ip = get_ip();

logtext($pk.":".$ip);

$wdata = json_decode(file_get_contents($wl_json), true);

logtext("Adding to whitelist...");

$wdata[$public_key] = $ip;
$w = fopen($wl_json, "w");
fwrite($w, json_encode($wdata, JSON_PRETTY_PRINT));
fclose($w);


//echo '  From: ' . $ip;
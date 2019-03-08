<?
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: *');


if (isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], $site) === false and strpos(strtolower($_SERVER['HTTP_REFERER']), $site_domain) === false) {
	header('HTTP/1.0 403 Forbidden');
	logdie("Referer error.");
}


logtext("NodeIP: Parsing parameters...");

function get_ip() {
	$mainIp = '';
	if (getenv('HTTP_CLIENT_IP'))
		$mainIp = getenv('HTTP_CLIENT_IP');
	else if(getenv('HTTP_X_FORWARDED_FOR'))
		$mainIp = getenv('HTTP_X_FORWARDED_FOR');
	else if(getenv('HTTP_X_FORWARDED'))
		$mainIp = getenv('HTTP_X_FORWARDED');
	else if(getenv('HTTP_FORWARDED_FOR'))
		$mainIp = getenv('HTTP_FORWARDED_FOR');
	else if(getenv('HTTP_FORWARDED'))
		$mainIp = getenv('HTTP_FORWARDED');
	else if(getenv('REMOTE_ADDR'))
		$mainIp = getenv('REMOTE_ADDR');
	else
		$mainIp = 'UNKNOWN';
	return $mainIp;
}

$ip = get_ip()

logtext($ip);

echo $ip;
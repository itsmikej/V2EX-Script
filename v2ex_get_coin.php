<?php
define("LOGIN_URL", "http://v2ex.com/signin");
define("COIN_URL", "http://v2ex.com/mission/daily");
define("GET_COIN_URL", "http://v2ex.com/mission/daily/redeem?once=");
define("USER_AGENT", "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0");
define("COOKIE_FILE", dirname(__file__)."/v2ex.cookie");
define("LOG_FILE", dirname(__file__)."/v2ex.log");
define("U", "username");
define("P", "password");

$ip = array(
	'CLIENT-IP: '.randIp(),
	'X-FORWARDED-FOR: '.randIp()
);
$error = 0;

function randIp(){
	return rand(60, 255).'.'.rand(60, 255).'.'.rand(60, 255).'.'.rand(60, 255);
}

function getLoginCode($data){
	if(preg_match("/value=\"(\d{5})\"\sname=\"once\"/", $data, $matches)){
		return $matches[1];
	}else{
		v2exLog("login_code error");
	}
}

function getCoinCode($data){
	if(preg_match("/\'\/mission\/daily\/redeem\?once=(\d{5})\'\;/", $data, $matches)){
		return $matches[1];
	}else{
		v2exLog("coin_code error");
	}
}

function v2exLog($data){
	$data = date("Y-m-d")."\t".$data."\r\n";
	file_put_contents(LOG_FILE, $data, FILE_APPEND);
	exit;
}

function send($url, $post_data = false, $referer = ""){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $GLOBALS["ip"]);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
	if(!empty($post_data) && !empty($referer)){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_REFERER, $referer);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
	curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
	$data = curl_exec($ch);
	curl_close($ch);
	$GLOBALS['error']++;
	if($data === false){
		v2exLog("error{$GLOBALS['error']}:".curl_error($ch));
	}else{
		return $data;
	}
}

$login_html = send(LOGIN_URL);
$login_code = getLoginCode($login_html);

$post_data = "u=".urlencode(U)."&p=".urlencode(P)."&once=".$login_code."&next=".urlencode("/");
send(LOGIN_URL, $post_data, LOGIN_URL);

$coin_html = send(COIN_URL);
$coin_code = getCoinCode($coin_html);

$url = GET_COIN_URL.$coin_code;
$info_html = send($url);
if(preg_match("/每日登录奖励已领取/", $info_html)){
	v2exLog("success!");
}else{
	v2exLog("false!");
}
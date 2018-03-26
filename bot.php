<?php
$token = "";

function go_curl($url, $type, $data = false, &$err_msg = null, $timeout = 20, $cert_info = array(),$proxy = "",$cookie = "recookie.txt"){
	$type = strtoupper($type);
	if ($type == 'GET' && is_array($data)) {
		$data = http_build_query($data);
	}
	$option = array();
	if ( $type == 'POST' ) {
		$option[CURLOPT_POST] = 1;
	}
	if ($data) {
		if ($type == 'POST') {
			$option[CURLOPT_POSTFIELDS] = $data;
		} elseif ($type == 'GET') {
			$url = strpos($url, '?') !== false ? $url.'&'.$data :  $url.'?'.$data;
		}
	}
	$option[CURLOPT_URL]            = $url;
	$option[CURLOPT_FOLLOWLOCATION] = TRUE;
	$option[CURLOPT_MAXREDIRS]      = 4;
	$option[CURLOPT_RETURNTRANSFER] = TRUE;
	$option[CURLOPT_TIMEOUT]        = $timeout;
	//设置证书信息
	if(!empty($cert_info) && !empty($cert_info['cert_file'])) {
		$option[CURLOPT_SSLCERT]       = $cert_info['cert_file'];
		$option[CURLOPT_SSLCERTPASSWD] = $cert_info['cert_pass'];
		$option[CURLOPT_SSLCERTTYPE]   = $cert_info['cert_type'];
	}
	//设置CA
	if(!empty($cert_info['ca_file'])) {
		// 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
		$option[CURLOPT_SSL_VERIFYPEER] = 1;
		$option[CURLOPT_CAINFO] = $cert_info['ca_file'];
	} else {
		// 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
		$option[CURLOPT_SSL_VERIFYPEER] = 0;
	}

	if(!empty($proxy)){
		$proxy_arr = explode(":",$proxy);
		$option[CURLOPT_PROXYAUTH]   = CURLAUTH_BASIC;
		$option[CURLOPT_PROXYTYPE]   = CURLPROXY_HTTP;
		$option[CURLOPT_PROXY]   = $proxy_arr[0];
		$option[CURLOPT_PROXYPORT]   = $proxy_arr[1];
		//$option[CURLOPT_PROXYUSERPWD]   = "user:pass";
	}

	if(!empty($cookie)){
		if (is_file($cookie)){
			$option[CURLOPT_COOKIEFILE]   = $cookie;
		}else{
			$option[CURLOPT_COOKIE]   = $cookie;
		}
		$option[CURLOPT_COOKIEJAR]   = "recookie.txt";
	}

	$ch = curl_init();
	curl_setopt_array($ch, $option);
	$response = curl_exec($ch);
	$curl_no  = curl_errno($ch);
	$curl_err = curl_error($ch);
	curl_close($ch);
	// error_log
	if($curl_no > 0) {
		if($err_msg !== null) {
			$err_msg = '('.$curl_no.')'.$curl_err;
		}
	}
	return $response;
}
function authcode($string,$operation='DECODE',$key='',$expiry=0){
	$ckey_length=4;
	$key=md5($key ? $key:"hcaiyue.top");
	$keya=md5(substr($key,0,16));
	$keyb=md5(substr($key,16,16));
	$keyc=$ckey_length ? ($operation=='DECODE' ? substr($string,0,$ckey_length):substr(md5(microtime()),-$ckey_length)):'';
	$cryptkey=$keya.md5($keya.$keyc);
	$key_length=strlen($cryptkey);
	$string=$operation=='DECODE' ? base64_decode(substr($string,$ckey_length)):sprintf('%010d',$expiry ? $expiry+time():0).substr(md5($string.$keyb),0,16).$string;
	$string_length=strlen($string);
	$result='';
	$box=range(0,255);
	$rndkey=array();
	for($i=0;$i<=255;$i++){
		$rndkey[$i]=ord($cryptkey[$i%$key_length]);
	}
	for($j=$i=0;$i<256;$i++){
		$j=($j+$box[$i]+$rndkey[$i])%256;
		$tmp=$box[$i];
		$box[$i]=$box[$j];
		$box[$j]=$tmp;
	}
	for($a=$j=$i=0;$i<$string_length;$i++){
		$a=($a+1)%256;
		$j=($j+$box[$a])%256;
		$tmp=$box[$a];
		$box[$a]=$box[$j];
		$box[$j]=$tmp;
		$result.=chr(ord($string[$i]) ^ ($box[($box[$a]+$box[$j])%256]));
	}
	if($operation=='DECODE'){
		if((substr($result,0,10)==0||substr($result,0,10)-time()>0)&&substr($result,10,16)==substr(md5(substr($result,26).$keyb),0,16)){
			return substr($result,26);
		}else{
			return'';
		}
	}else{
		return $keyc.str_replace('=','',base64_encode($result));
	}
}
function sendmessage($data){
	go_curl("https://api.telegram.org/bot$token/sendMessage","POST",$data);
}
//file_put_contents("text.txt",file_get_contents("php://input")."\n\n\n",FILE_APPEND); //debug output
if (isset($_POST["method"]) && $_POST["method"] == "send"){
	$data = [];
	$data["chat_id"] = authcode($_POST["sckey"]);
	$data["text"] = $_POST["content"];
	sendmessage($data);
	return;
}
$a = json_decode(file_get_contents("php://input"),true);
if ($a["message"]["text"] == "/start"){
    $data = [];
    $data["chat_id"] = $a["message"]["chat"]["id"];
    $data["text"] = authcode($a["message"]["chat"]["id"],"ENCODE");
    sendmessage($data);
}
if ($a["channel_post"]["text"] == "/start"){
    $data = [];
    $data["chat_id"] = $a["channel_post"]["chat"]["id"];
    $data["text"] = authcode($a["channel_post"]["chat"]["id"],"ENCODE");
    sendmessage($data);
}
?>
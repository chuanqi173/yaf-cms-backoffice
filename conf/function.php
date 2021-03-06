<?php
use Illuminate\Database\Capsule\Manager as DB;
define('_RBACCookieKey_', 'RBACUser');
define('_EXPIRETIME_', 86000);
 
define('_COOKIE_KEY_', 'f6j5r@ziqpei&vkjapip19lo6kl8t4');
define('_COOKIE_IV_', 'x6j5r@ziqpei&vkjapip19lo6kl8t3');

define('APP_KEY', '');
define('APP_SECRET', '');

define('LOG_DIR', APP_PATH . '/log/');
define('CERT_DIR',APP_PATH . '/cert/');

#捕获Warring错误
set_error_handler('displayErrorHandler');
function displayErrorHandler($errno, $errstr, $filename, $line)
{
	$error_no_arr = array(
			1=>'ERROR', 
			2=>'WARNING', 
			4=>'PARSE', 
			8=>'NOTICE', 
			16=>'CORE_ERROR',
			32=>'CORE_WARNING', 
			64=>'COMPILE_ERROR', 
			128=>'COMPILE_WARNING', 
			256=>'USER_ERROR', 
			512=>'USER_WARNING', 
			1024=>'USER_NOTICE', 
			2047=>'ALL', 
			2048=>'STRICT'
	);

	if(in_array($errno,[1,2,4])){
			Log::out('sysError', 'I', "File:{$filename} on Line:{$line} \n" . $error_no_arr[$errno] . ":". $errstr."\n");
			#throw new \Exception($error_no_arr[$errno] . ":". $errstr, $errno);
	}
}
/**
 * 输出变量的内容，通常用于调试
 *
 * @package Core
 *
 * @param mixed $vars 要输出的变量
 * @param string $label
 * @param boolean $return
 */
function dump($vars, $label = '', $return = false)
{
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
        $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
        $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) { return $content; }
    echo $content;
    return null;
}
function json($vars, $format='json', $callback='callback')
{
	header("Access-Control-Allow-Origin:*");
	header("Access-Control-Allow-Methods", "PUT,POST,GET,OPTIONS,DELETE");
	header("Access-Control-Allow-Headders", "content-type");
	if($format=='json'){
		header("Content-type: application/json;charset=utf-8");		
		$data = updateNull($vars);	
		die(json_encode($data, JSON_UNESCAPED_UNICODE));
	}else{
		header("Content-type: text/javascript;charset=utf-8");		
		$data = updateNull($vars);	
		die("{$callback}(".json_encode($data, JSON_UNESCAPED_UNICODE).")");
	}
}
function ret($ret=0, $msg='ok', $data=[])
{	
	$ret  = array(
		'ret'	=>	$ret,
		'msg'	=>	$msg,		
	);
	if(!empty($data)){ $ret['data']	=$data; }
	json($ret);	
}
function updateNull(& $onearr){
	if(!empty($onearr)&&is_array($onearr)){
	foreach($onearr as $k=>$v){
		if(is_array($v)){
			$onearr[$k]	=	updateNull($v);
		}else{
			if($v===NULL){
				$onearr[$k] = '';
			}
		}
	}}
	return $onearr;
}

/***保存SQL记录到redis***/
function remember($key, $ttl, callable $func){
		$cache_enable =	Yaf_Registry::get('config')->cache->redis->enable;
		if( $cache_enable && Cache::exists($key) ){		
				return Cache::get($key);
		}
		$rows	= call_user_func($func);			
		if( $cache_enable ){
				Cache::set($key, $rows, $ttl);
		}
		return $rows;	
}

/***PHP上传文件到七牛cdn***/
function uploadToCDN($filePath, $cdnfileName){
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = Yaf_Registry::get('config')->application->cdn->accessKey;
		$secretKey = Yaf_Registry::get('config')->application->cdn->secretKey;

		// 构建鉴权对象
		$auth = new \Qiniu\Auth($accessKey, $secretKey);
		// 要上传的空间
		$bucket = Yaf_Registry::get('config')->application->cdn->bucket;
		
		// 生成上传 Token
		$token = $auth->uploadToken($bucket);

		// 上传到七牛后保存的文件名
		$key = $cdnfileName;

		// 初始化 UploadManager 对象并进行文件的上传
		$uploadMgr = new \Qiniu\Storage\UploadManager;

		// 调用 UploadManager 的 putFile 方法进行文件的上传
		list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
		if ($err !== null) {
			return false;
		} else {
			return Yaf_Registry::get('config')->application->cdn->url . $ret['key'];
		}
}

function getIp(){
    if (@$_SERVER["HTTP_X_FORWARDED_FOR"])
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    else if (@$_SERVER["HTTP_CLIENT_IP"])
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    else if (@$_SERVER["REMOTE_ADDR"])
        $ip = $_SERVER["REMOTE_ADDR"];
    else if (@getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (@getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (@getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else
        $ip = "Unknown";
    return $ip;
}

function getLang($code){
	return DB::table('scsj_language')->where('code','=',$code)->first()['string'];
}

function page_url($controller='index', $action='index', $args=array()){
	$router = Yaf_Dispatcher::getInstance()->getRouter();
	$urls	= array(
						':c'=>$controller,
						':a'=>$action
				);

	if( !empty($args) && is_array($args) ){
		foreach($args	as $k=>$v){
			$args[$k]	=	strval($v);
		}
	}
		
	$url	= $router->getRoute($router->getCurrentRoute())->assemble($urls, $args);
	$suffix = Yaf_Registry::get('config')->application->suffix;
	if(!empty($suffix)) {
		if( substr($url, -1, 1)=='/' ){
			$url= substr($url,0,strlen($url)-1).'.'.$suffix;
		}else{
			if(preg_match('#(\/?\?)#', $url)){
				$url = preg_replace('#(\/?\?)#', '.'.$suffix.'?', $url);
			}else{
				$url.= '.'.$suffix;
			}
		}
	}	
	return $url;
}
/**
	* PHP生成随机字符串
	* @param	Int		$length			字符串长度
	* @weburl	url						学习地址：http://www.ijquery.cn/?p=1027
*/
function randStr($length = 10){
	$characters = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
	$randomString = ''; 
	for ($i = 0; $i < $length; $i++) { 
		$randomString .= $characters[rand(0, strlen($characters) - 1)]; 
	} 
	return $randomString; 
}	
#发消息
function send($uid, $content, $type=1){
	switch($type){
		case 1:
			#发站内信
			DB::table('notice')->insert(['user_id'=>$uid, 'content'=>$content, 'created_at'=>date('Y-m-d H:i:s')]);
			break;
	}
}
#按钮菜单权限验证函数
function checkAuth($title){
	$auth	= new Auth(_RBACCookieKey_);
	if(!$auth->isLogin())	return FALSE;		
	$auths = explode(',', DB::table('roles')->find($auth->role)['auth_names']);			
	return in_array($title, $auths);				
}
/**
 * 跳转
 *
 * @param      $url
 * @param null $headers
 */
function redirect($url) {
	echo "<script>top.location.href='{$url}';</script>";
	exit;
	/* if (!empty($url))
	{
		if ($headers)
		{
			if (!is_array($headers))
				$headers = array($headers);

			foreach ($headers as $header)
				header($header);
		}

		header('Location: ' . $url);
		exit;
	} */
}

function pick($url, $postData='')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = isset($row['port']) ? $row['port']:80;
	$file = $row['path'];	
	if(is_array($postData)){
		$postData = http_build_query($postData);
	}
	$len = strlen($postData);
	$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $postData;		
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}

/**
 *采集函数
 *
 */
function curl_data($url,$postdata='',$pre_url='http://www.baidu.com',$proxyip=false,$compression='gzip, deflate'){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT,5);
		
		$client_ip	= rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
		$x_ip		= rand(1,254).'.'.rand(1,254).'.'.rand(1,254).'.'.rand(1,254);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$x_ip,'CLIENT-IP:'.$client_ip));//构造IP				
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if($postdata!=''){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}
		$pre_url = $pre_url ? $pre_url : "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		curl_setopt($ch, CURLOPT_REFERER, $pre_url);
		if($proxyip){
			curl_setopt($ch, CURLOPT_PROXY, $proxyip);
		}		
		if($compression!='') {	
			curl_setopt($ch, CURLOPT_ENCODING, $compression);	
		}
		
		//Mozilla/5.0 (Linux; U; Android 2.3.7; zh-cn; c8650 Build/GWK74) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1s		
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11'); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
}

function http_post_json($url, $jsonStr)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT,5);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charset=utf-8',
		'Content-Length: ' . strlen($jsonStr)
	)
	);
	$response = curl_exec($ch);
	#$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	return $response;
}

/**
 * 加密/解密字符串
 *
 * @param  string     $string    原始字符串
 * @param  string     $operation 操作选项: DECODE：解密；其它为加密
 * @param  string     $key       混淆码
 * @return string     $result    处理后的字符串
 */
function authcode($string, $operation, $key = '') {
		$authorization='changpei0628x9385dbc36c077a2e8bec942dd38';
		$key = md5($key ? $key : $authorization);
		$key_length = strlen($key);
	
		$string = $operation == 'DECODE' ? base64_decode($string) : substr(md5($string.$key), 0, 8).$string;
		$string_length = strlen($string);
	
		$rndkey = $box = array();
		$result = '';
	
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($key[$i % $key_length]);
			$box[$i] = $i;
		}
	
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
	
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
	
		if($operation == 'DECODE') {
			if(substr($result, 0, 8) == substr(md5(substr($result, 8).$key), 0, 8)) {
				return substr($result, 8);
			} else {
				return '';
			}
		} else {
			return str_replace('=', '', base64_encode($result));
		}	
}
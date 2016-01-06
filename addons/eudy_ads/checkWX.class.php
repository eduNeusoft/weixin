<?php
defined('IN_IA') or exit('Access Denied');
class checkWX{
	private $appid="";
	private $secret="";

	public function __construct() {
		$filename=dirname(__FILE__)."/checkWX.config";
		if(file_exists($filename)){
		$cfg= unserialize( file_get_contents($filename) );
			$this->appid=$cfg['appid'];
			$this->secret=$cfg['secret'];
		}
    }

	public function checkIsWxClient(){
		global $_W;
		$useragent=strtolower($_SERVER['HTTP_USER_AGENT']);
		if ( strpos($useragent, 'micromessenger') === false ) {exit("请在微信客户端登陆");}

		if(!empty($_GET['code'])&&!empty($_GET['state'])){
			$ok=$this->setCODE();
			if($ok){
				return true;
			}
			else if($ok===false){
				exit("请在微信客户端登陆");
			} 
		}

		$url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];

		$token=$_W['token'];
		
		$location="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_base&state=".$token."#wechat_redirect";
		header("Location:".$location);

	}
	public function setCODE(){
		global $_W;
		$token=$_W['token'];
		
		$u=$this->curl_file_get_contents($_GET['code']);
		if(!empty($u['openid'])){
			return true;
		}else if( !empty($u['errcode']) ){
			return $u['errcode'];
		}
		else{
			return false;
		}
	}
	private function curl_file_get_contents($code){
		$durl="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->secret."&code=".$code."&grant_type=authorization_code";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $durl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
		curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$r = json_decode( curl_exec($ch) ,true );
		$i=1;
		while(empty($r)&&$i<5){
			$r = json_decode( curl_exec($ch) ,true );
			$i++;
		}
		curl_close($ch);
	    return $r;
	} 
}

?>
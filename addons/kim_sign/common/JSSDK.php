<?php
/**
 * Kim答到管理模块定义
 *
 */
defined('IN_IA') or exit('Access Denied');
load()->func("communication");
load()->model("cache");
class JSSDK {
    private $appId='你的appid';
    private $appSecret='你的appSecret';
    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }
    private function getJsApiTicket() {
        $ticket = cache_read("js_api_ticket");
        if(empty($ticket) || $ticket["time"] <= time()) {
            $token = $this->getWxAccessToken();
            if(!empty($token)) {
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$token&type=jsapi";
                $js_token = ihttp_request($url);
                if(!empty($js_token)) {
                    $content = @json_decode($js_token['content'], true);
                    $ticket = array(
                        "ticket" => $content["ticket"],
                        "time" => TIMESTAMP + $content["expires_in"]
                    );
                    cache_write("js_api_ticket", $ticket);
                }
            }
        }
        return $ticket["ticket"];
    }
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    public function getSignPackage() {
        if(empty($this->appId) || empty($this->appSecret)) return array();
        $jsapiTicket = $this->getJsApiTicket();
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = TIMESTAMP;
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }
    private function getWxAccessToken(){
        $rep = pdo_fetch("SELECT `acid`,`access_token` FROM ".tablename("account_wechats")." WHERE `key`=:key AND `secret`=:secret",array(":key" => $this->appId,":secret"=>$this->appSecret));
        $access_token = iunserializer($rep["access_token"]);
        if(empty($access_token) || $access_token["expire"] <= time()) {
            $appId = $this->appId;
            $secret = $this->appSecret;
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secret";
            $content = ihttp_get($url);
            if(!empty($content)) {
                $token = @json_decode($content['content'], true);
                if(!empty($token) && is_array($token) && !empty($token['access_token']) && !empty($token['expires_in'])) {
                    $record = array(
                        "token" => $token['access_token'],
                        "expire" => TIMESTAMP + $token['expires_in']
                    );
                    pdo_update('account_wechats', array("access_token"=>iserializer($record)), array('acid' => $rep['acid']));
                    return $token['access_token'];
                }
            }
        }
        return $access_token;
    }
} 
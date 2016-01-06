<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

define('ACCOUNT_PLATFORM_API_ACCESSTOKEN', 'https://api.weixin.qq.com/cgi-bin/component/api_component_token');
define('ACCOUNT_PLATFORM_API_PREAUTHCODE', 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=');
define('ACCOUNT_PLATFORM_API_LOGIN', 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s');
define('ACCOUNT_PLATFORM_API_QUERY_AUTH_INFO', 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=');
define('ACCOUNT_PLATFORM_API_ACCOUNT_INFO', 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=');
define('ACCOUNT_PLATFORM_API_REFRESH_AUTH_ACCESSTOKEN', 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=');

load()->classs('weixin.account');
load()->func('communication');

class WeiXinPlatform extends WeiXinAccount {
	public $appid;
	public $appsecret;
	public $encodingaeskey;
	public $token;
	public $refreshtoken;
	public $account;
	
	function __construct($account = array()) {
		$setting = setting_load('platform');
		$this->appid = $setting['platform']['appid'];
		$this->appsecret = $setting['platform']['appsecret'];
		$this->token = $setting['platform']['token'];
		$this->encodingaeskey = $setting['platform']['encodingaeskey'];
		$this->account = $account;
		if ($this->account['key'] == 'wx570bc396a51b8ff8') {
			$this->account['key'] = $this->appid;
			$this->openPlatformTestCase();
		}
		$this->account['key'] = $this->appid;
	}
	
	function getComponentAccesstoken() {
		$accesstoken = cache_load('account:component:assesstoken');
		if (empty($accesstoken) || empty($accesstoken['value']) || $accesstoken['expire'] < TIMESTAMP) {
			$ticket = cache_load('account:ticket');
			if (empty($ticket)) {
				return error(1, '缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试');
			}
			$data = array(
				'component_appid' => $this->appid,
				'component_appsecret' => $this->appsecret,
				'component_verify_ticket' => $ticket,
			);
			$response = $this->request(ACCOUNT_PLATFORM_API_ACCESSTOKEN, $data);
			if (is_error($response)) {
				return $response;
			}
			$accesstoken = array(
				'value' => $response['component_access_token'],
				'expire' => TIMESTAMP + intval($response['expires_in']),
			);
			cache_write('account:component:assesstoken', $accesstoken);
		}
		return $accesstoken['value'];
	}
	
	function getPreauthCode() {
				$preauthcode = cache_load('account:preauthcode');
		if (true || empty($preauthcode) || empty($preauthcode['value']) || $preauthcode['expire'] < TIMESTAMP) {
			$component_accesstoken = $this->getComponentAccesstoken();
			if (is_error($component_accesstoken)) {
				return $component_accesstoken;
			}
			$data = array(
				'component_appid' => $this->appid
			);
			$response = $this->request(ACCOUNT_PLATFORM_API_PREAUTHCODE . $component_accesstoken, $data);
			if (is_error($response)) {
				return $response;
			}
			$preauthcode = array(
				'value' => $response['pre_auth_code'],
				'expire' => TIMESTAMP + intval($response['expires_in']),
			);
			cache_write('account:preauthcode', $preauthcode);
		}
		return $preauthcode['value'];
	}
	
	public function getAuthInfo($code) {
		$component_accesstoken = $this->getComponentAccesstoken();
		if (is_error($component_accesstoken)) {
			return $component_accesstoken;
		}
		$post = array(
			'component_appid' => $this->appid,
			'authorization_code' => $code,
		);
		$response = $this->request(ACCOUNT_PLATFORM_API_QUERY_AUTH_INFO . $component_accesstoken, $post);
		if (is_error($response)) {
			return $response;
		}
		$this->setAuthRefreshToken($response['authorization_info']['authorizer_refresh_token']);
		return $response;
	}
	
	public function getAccountInfo($appid = '') {
		$component_accesstoken = $this->getComponentAccesstoken();
		if (is_error($component_accesstoken)) {
			return $component_accesstoken;
		}
		$appid = !empty($appid) ? $appid : $this->account['key'];
		$post = array(
			'component_appid' => $this->appid,
			'authorizer_appid' => $appid,
		);
		$response = $this->request(ACCOUNT_PLATFORM_API_ACCOUNT_INFO . $component_accesstoken, $post);
		if (is_error($response)) {
			return $response;
		}
		return $response;
	}
	
	public function getAccessToken() {
		$cachename = 'account:auth:accesstoken:'.$this->account['key'];
		$auth_accesstoken = cache_load($cachename);
		if (empty($auth_accesstoken) || empty($auth_accesstoken['value']) || $auth_accesstoken['expire'] < TIMESTAMP) {
			$component_accesstoken = $this->getComponentAccesstoken();
			if (is_error($component_accesstoken)) {
				return $component_accesstoken;
			}
			$this->refreshtoken = $this->getAuthRefreshToken();
			$data = array(
				'component_appid' => $this->appid,
				'authorizer_appid' => $this->account['key'],
				'authorizer_refresh_token' => $this->refreshtoken,
			);
			$response = $this->request(ACCOUNT_PLATFORM_API_REFRESH_AUTH_ACCESSTOKEN . $component_accesstoken, $data);
			if (is_error($response)) {
				return $response;
			}
			if ($response['authorizer_refresh_token'] != $this->refreshtoken) {
				$this->setAuthRefreshToken($response['authorizer_refresh_token']);
			}
			$auth_accesstoken = array(
				'value' => $response['authorizer_access_token'],
				'expire' => TIMESTAMP + intval($response['expires_in']),
			);
			cache_write($cachename, $auth_accesstoken);
		}
		return $auth_accesstoken['value'];
	}
	
	public function fetch_token() {
		return $this->getAccessToken();
	}
	
	public function getAuthLoginUrl() {
		$preauthcode = $this->getPreauthCode();
		if (is_error($preauthcode)) {
			$authurl = "javascript:alert('缺少接入平台关键数据，等待微信开放平台推送数据，请十分钟后再试');";
		} else {
			$authurl = sprintf(ACCOUNT_PLATFORM_API_LOGIN, $this->appid, $preauthcode, urlencode($GLOBALS['_W']['siteroot'] . 'index.php?c=account&a=auth&do=forward'));
		}
		return $authurl;
	}
	
	public function openPlatformTestCase() {
		global $_GPC;
		$post = file_get_contents('php://input');
		WeUtility::logging('platform-test-message', $post);
		$encode_message = $this->xmlExtract($post);
		$message = aes_decode($encode_message['encrypt'], $this->encodingaeskey);
		$message = $this->parse($message);
		$response = array(
				'ToUserName' => $message['from'],
				'FromUserName' => $message['to'],
				'CreateTime' => TIMESTAMP,
				'MsgId' => TIMESTAMP,
				'MsgType' => 'text',
		);
		if ($message['content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
			$response['Content'] = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
		}
		if ($message['msgtype'] == 'event') {
			$response['Content'] = $message['event'] . 'from_callback';
		}
		if (strexists($message['content'], 'QUERY_AUTH_CODE')) {
			list($sufixx, $authcode) = explode(':', $message['content']);
			$auth_info = $this->getAuthInfo($authcode);
			WeUtility::logging('platform-test-send-message', var_export($auth_info, true));
			$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=". $auth_info['authorization_info']['authorizer_access_token'];
			$data = array(
				'touser' => $message['from'],
				'msgtype' => 'text',
				'text' => array('content' => $authcode.'_from_api'),
			);
			$response = ihttp_request($url, urldecode(json_encode($data)));
			exit('');
		}
		$xml = array(
			'Nonce' => $_GPC['nonce'],
			'TimeStamp' => $_GPC['timestamp'],
			'Encrypt' => aes_encode(array2xml($response), $this->encodingaeskey, $this->appid),
		);
		$signature = array($xml['Encrypt'], $this->token, $_GPC['timestamp'], $_GPC['nonce']);
		sort($signature, SORT_STRING);
		$signature = implode($signature);
		$xml['MsgSignature'] = sha1($signature);
		exit(array2xml($xml));
	}
	
	private function request($url, $post = array()) {
		$response = ihttp_request($url, json_encode($post));
		$response = json_decode($response['content'], true);
		if (empty($response) || !empty($response['errcode'])) {
			return error($response['errcode'], $response['errmsg']);
		}
		return $response;
	}
	
	private function getAuthRefreshToken() {
		$auth_refresh_token = cache_load('account:auth:refreshtoken:'.$this->account['key']);
		if (empty($auth_refresh_token)) {
			$auth_refresh_token = $this->account['auth_refresh_token'];
			cache_write('account:auth:refreshtoken:'.$this->account['key'], $auth_refresh_token);
		}
		return $auth_refresh_token;
	}
	
	private function setAuthRefreshToken($token) {
		pdo_update('account_wechats', array('auth_refresh_token' => $token), array('acid' => $this->account['acid']));
		cache_write('account:auth:refreshtoken:'.$this->account['key'], $token);
	}
}
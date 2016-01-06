<?php
/**
 * 天天签到模块订阅器
 *
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');

class zmcn_signModuleReceiver extends WeModuleReceiver {
	public function receive() {
		$type = $this->message['type'];
	}
}
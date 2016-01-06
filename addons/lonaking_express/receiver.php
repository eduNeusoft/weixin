<?php
/**
 * 快递专家模块订阅器
 *
 * @author lonaking
 * @url http://bbs.52jscn.com/thread-7940-1-1.html
 */
defined('IN_IA') or exit('Access Denied');

class Lonaking_expressModuleReceiver extends WeModuleReceiver {
	public function receive() {
		$type = $this->message['type'];
		//这里定义此模块进行消息订阅时的, 消息到达以后的具体处理过程, 请查看微赞文档来编写你的代码
	}
}
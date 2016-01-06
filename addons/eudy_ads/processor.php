<?php
/**
* 奔跑吧阿达西模块定义
 *
 * @author 蜂窝科技
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class eudy_adsModuleProcessor extends WeModuleProcessor {

	public function respond() {
		$content = $this->message['content'];
		//这里定义此模块进行消息处理时的具体过程, 请查看微擎文档来编写你的代码
	}
}
<?php
/**
 * 幸运大抽奖模块处理程序
 *
 * @author 华轩科技
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');

class Hx_cardsModuleProcessor extends WeModuleProcessor {
	public $table_reply = 'hx_cards_reply';
	public function respond() {
		$content = $this->message['content'];
		//这里定义此模块进行消息处理时的具体过程, 请查看微赞文档来编写你的代码
		$rid = $this->rule;
		$fromuser = $this->message['from'];
		if($rid) {
			$reply = pdo_fetch("SELECT * FROM " . tablename($this->table_reply) . " WHERE rid = :rid", array(':rid' => $rid));
			if($reply) {
				$news = array();
				$news[] = array(
					'title' => $reply['title'],
					'description' =>$reply['description'],
					'picurl' =>$reply['thumb'],
					'url' => $this->createMobileUrl('detail', array('id' => $reply['id'])),
				);
				return $this->respNews($news);
			}
		}
		return null;
	}
}
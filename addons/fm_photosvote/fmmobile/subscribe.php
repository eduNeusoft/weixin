<?php
/**
 * 女神来了模块定义
 *
 * @author 微赞科技
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');
	if($_W['isajax']) {
			//$uniacid = $_W['acid'];//当前公众号ID	
			
			$rid = $_GPC['rid'];
						
					
			if (!empty($rid)) {
				$reply = pdo_fetch("SELECT * FROM ".tablename($this->table_reply)." WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			}
			
			
	
			include $this->template('subscribe');
			exit();
		}
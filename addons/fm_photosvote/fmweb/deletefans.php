<?php
/**
 * 女神来了模块定义
 *
 * @author 微赞科技
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');
$reply = pdo_fetch("select * from ".tablename($this->table_reply)." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $uniacid));
        if (empty($reply)) {
            $this->webmessage('抱歉，要修改的活动不存在或是已经被删除！');
        }	
        foreach ($_GPC['idArr'] as $k => $id) {
            $id = intval($id);
            if ($id == 0)
                continue;
			$fans = pdo_fetch("select from_user from ".tablename($this->table_users)." where id = :id", array(':id' => $id));
			if (empty($fans)) {
                $this->webmessage('抱歉，选中的粉丝数据不存在！');
            }
			//删除粉丝参与记录
			pdo_delete($this->table_users, array('id' => $id));
			
        }
        $this->webmessage('粉丝记录删除成功！', '', 0);
    
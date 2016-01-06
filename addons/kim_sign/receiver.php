<?php
/**
 * 小区业主管理模块订阅器
 *
 * @author Kim
 * @url http://wx.358cn.com
 */
defined('IN_IA') or exit('Access Denied');

class Kim_SignModuleReceiver extends WeModuleReceiver {
    public function receive() {
        global $_W;
        //这里定义此模块进行消息订阅时的, 消息到达以后的具体处理过程, 请查看微赞文档来编写你的代码
        $stat_setting = uni_setting($_W['uniacid'], 'stat');
        $stat_setting = $stat_setting['stat'];
        if(!is_array($stat_setting) || empty($stat_setting)) {
            $stat_setting = array();
            $stat_setting['msg_maxday'] = 0;
            $stat_setting['msg_history'] = 1;
            $stat_setting['use_ratio'] = 1;
        }


        if (!empty($stat_setting['msg_maxday']) && $stat_setting['msg_maxday'] > 0) {
            pdo_delete('stat_msg_history', " createtime < ".TIMESTAMP.' - '. $stat_setting['msg_maxday'] * 86400);
        }
        if ($stat_setting['msg_history']) {
            switch ($this->message['type']) {
                case 'text':
                    $content = iserializer(array('content' => $this->message['content'], 'redirection' => $this->message['redirection'], 'source' => $this->message['source']));
                    break;
                default:
                    $content = $this->message['content'];
            }
            pdo_insert('stat_msg_history', array(
                'uniacid' => $_W['uniacid'],
                'module' => $this->params['module'],
                'from_user' => $this->message['from'],
                'rid' => intval($this->params['rule']),
                'kid' => $this->keyword['id'],
                'message' => $content,
                'type' => $this->message['type'],
                'createtime' => $this->message['time'],
            ));
        }
        if (!empty($stat_setting['use_ratio'])) {
            $updateid = pdo_query("UPDATE ".tablename('stat_rule')." SET hit = hit + 1, lastupdate = '".TIMESTAMP."' WHERE rid = :rid AND createtime = :createtime", array(':rid' => $this->params['rule'], ':createtime' => strtotime(date('Y-m-d'))));
            if (empty($updateid)) {
                pdo_insert('stat_rule', array(
                    'uniacid' => $_W['uniacid'],
                    'rid' => $this->params['rule'],
                    'createtime' => strtotime(date('Y-m-d')),
                    'hit' => 1,
                    'lastupdate' => $this->message['time'],
                ));
            }
            if (!empty($this->keyword['id'])) {
                $updateid = pdo_query("UPDATE ".tablename('stat_keyword')." SET hit = hit + 1, lastupdate = '".TIMESTAMP."' WHERE kid = :kid AND createtime = :createtime", array(':kid' => $this->keyword['id'], ':createtime' => strtotime(date('Y-m-d'))));
                if (empty($updateid)) {
                    pdo_insert('stat_keyword', array(
                        'uniacid' => $_W['uniacid'],
                        'rid' => $this->params['rule'],
                        'kid' => $this->keyword['id'],
                        'createtime' => strtotime(date('Y-m-d')),
                        'hit' => 1,
                        'lastupdate' => $this->message['time'],
                    ));
                }
            }
        }
    }
}
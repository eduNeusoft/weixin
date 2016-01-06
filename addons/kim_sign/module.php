<?php
/**
 * Kim签到管理模块定义
 *
 * @author Kim
 * @url http://wx.358cn.com
 */
defined('IN_IA') or exit('Access Denied');
include_once 'common/common.inc.php';
load()->func('tpl');
class Kim_SignModule extends WeModule {

    public $tableName = 'kim_sign_reply';

    public function fieldsFormDisplay($rid = 0) {
        global $_W;
        if (!empty($rid)) {
            $reply = pdo_fetch("SELECT * FROM ".tablename($this->tableName)." WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
        }
        $reply['start_time'] = empty($reply['start_time']) ? strtotime(date('Y-m-d')) : $reply['start_time'];
        $reply['end_time'] = empty($reply['end_time']) ? TIMESTAMP : $reply['end_time'] + 86399;
        include $this->template('form');
    }

    public function fieldsFormValidate($rid = 0) {
        return true;
    }

    public function fieldsFormSubmit($rid = 0) {
        global $_GPC, $_W;
        $id = intval($_GPC['reply_id']);
        $reply = $_GPC['reply'];
        $reply['rid'] = $rid;
        if (empty($id)) {
            pdo_insert($this->tableName, $reply);
        } else {
            pdo_update($this->tableName, $reply, array('id' => $id));
        }
    }

    public function ruleDeleted($rid = 0) {
        pdo_delete($this->tableName, array('rid' => $rid));
    }

    public function settingsDisplay($settings) {

        global $_GPC, $_W;
        $credits = uni_setting($_W['uniacid'],'creditnames');
        $cls = new Prize;
        list($code,$groups) = $cls->groups();
        if(checksubmit()) {
            $cfg = array(
                'times' => $_GPC['times'],
                'start_time' => $_GPC['start_time'],
                'end_time' => $_GPC['end_time'],
                'prize_group'=>$_GPC['prize_group'],
                'credit_type'=> $_GPC['credit_type'],
                'credit' => intval($_GPC['credit']),
                'rank' => intval($_GPC['rank'])
            );
            $start_time = $cfg['start_time'];
            $start_time = strtotime($start_time);
            $end_time = $cfg['end_time'];
            $end_time = strtotime($end_time);
            if($start_time >= $end_time){
                message('开始时间不得晚于结束时间', 'refresh', 'error');
            }elseif($this->saveSettings($cfg)) {
                message('保存成功', 'refresh');
            }
        }
        if(!isset($settings) || empty($settings)) {
            $UnIaCid = $_W['uniacid'];
            $par = array(':module' => $this->modulename, ':uniacid' => $UnIaCid);
            $sql = "SELECT COUNT(*) FROM ".tablename("uni_account_modules")." WHERE module=:module AND uniacid=:uniacid";
            $row = pdo_fetchcolumn($sql,$par);
            if(empty($row) || intval($row) <= 0){
                $cfg = array(
                    'module' => $this->modulename,
                    'uniacid' => $UnIaCid,
                    'enabled' => 1
                );
                pdo_insert("uni_account_modules",$cfg);
            }
        }
        if(!isset($settings['times'])) $settings['times'] = '1';
        if(!isset($settings['start_time'])) $settings['start_time'] = '08:30';
        if(!isset($settings['end_time'])) $settings['end_time'] = '22:00';
        if(!isset($settings['credit'])) $settings['credit'] = '1';
        if(!isset($settings['rank'])) $settings['rank'] = '10';
        include $this->template('setting');
    }
}

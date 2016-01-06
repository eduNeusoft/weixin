<?php
/**
 * Kim签到模块 公共方法
 * User: kim
 * Date: 15-2-6
 * Time: 上午10:51
 */

include_once "prize.class.php";
include_once "JSSDK.php";
/**
 * 根据OpenId获取用户ID
 * @param $openId
 * @return $uid
 */
function getUserIdByOpenId($openId) {
    global $_W;
    unset($params,$sql);
    $params = array(':uniacid'=>$_W['uniacid'],':openid'=>$openId);
    $sql = 'SELECT uid FROM '.tablename('mc_mapping_fans').' WHERE uniacid=:uniacid AND openid=:openid';
    $uid = pdo_fetchcolumn($sql, $params);
    unset($params,$sql);
    return $uid;
}

/**
 * 获取用户数据
 * @param $uid
 * @param string $fields
 */
function getUserFieldsByUid($uid, $fields='*') {
    global $_W;
    if($fields <> '*') {
        if(!is_array($fields)) {
            $fields = array($fields);
        }
        $fs = '';
        foreach($fields as $f) {
            $fs .= "`{$f}`,";
        }
        $fields = rtrim($fs, ',');
    }
    unset($params,$sql);
    $sql = "SELECT {$fields} FROM ".tablename('mc_members').' WHERE uid=:uid';
    $params = array(':uid'=> $uid);
    $resp = pdo_fetch($sql, $params);
    unset($params,$sql,$fields,$fs);
    return $resp;
}
/**
 * 获取用户当天签到数据 返回 array() 表示未签到
 * @param $uid
 * @return data
 */
function getUserTodaySign($uid, $times=1) {
    global $_W;
    if(intval($times) <= 0) $times = 1;
    unset($params,$sql,$resp,$date);
    $date = strtotime(date('Y-m-d'));
    $hour = round(24/intval($times));//签到时间间隔
    if($times > 1) $date = TIMESTAMP-$hour*60*60;
    $params = array(':uid' => $uid, ':time' => $date,':uniacid'=>$_W['uniacid']);
    $sql = 'SELECT id FROM '.tablename('kim_sign_record').' WHERE uid=:uid AND time>=:time AND uniacid=:uniacid';
    $resp = pdo_fetch($sql, $params);
    unset($params,$sql,$date);
    return $resp;
}

/**
 * 获取回复参数
 * @param $ruleId
 * @return data 回复
 */
function getReplyRule($ruleId) {
    unset($params,$sql,$resp);
    $params = array(':rid' => $ruleId);
    $sql = 'SELECT * FROM ' . tablename('kim_sign_reply') . ' WHERE rid=:rid LIMIT 1';
    $resp = pdo_fetch($sql, $params);
    unset($params,$sql);
    return $resp;
}

/**
 * 获取模块配置
 * @param $moduleName
 * @return bool
 */
function getModulesSettings($moduleName) {
    global $_W;
    unset($params,$sql,$resp);
    $sql = "SELECT settings FROM ".tablename("uni_account_modules")." WHERE module=:module AND uniacid=:uniacid";
    $resp = pdo_fetchcolumn($sql,array(":module"=>$moduleName,":uniacid"=>$_W['uniacid']));
    unset($params,$sql);
    return unserialize($resp);
}
function getTodaySignTotal () {
    global $_W;
    unset($params,$sql,$resp);
    $date = strtotime(date('Y-m-d'));
    $sql = "SELECT COUNT(*) FROM ".tablename('kim_sign_record')." WHERE time >= :time AND uniacid=:uniacid";
    $resp = pdo_fetchcolumn($sql,array(':time'=>$date,':uniacid'=>$_W['uniacid']));
    unset($params,$sql);
    return $resp;
}

/**
 * 获取用户签到总次数
 */
function getUserSignTotal($uid) {
    global $_W;
    unset($params,$sql,$resp);
    $sql = "SELECT COUNT(*) FROM ".tablename('kim_sign_record')." WHERE uid=:uid AND uniacid=:uniacid";
    $params = array(':uid' => $uid, ':uniacid' => $_W['uniacid']);
    $resp = pdo_fetchcolumn($sql, $params);
    unset($params,$sql);
    return $resp;
}

/**
 * 获得用户签到奖品
 */
function getUserTodayPrize($uid) {
    global $_W;
    unset($params,$sql,$resp);
    $date = strtotime(date('Y-m-d'));
    $sql = "SELECT * FROM ".tablename('kim_sign_prizes_record')." WHERE uid=:uid AND winning_time>=:winning_time ORDER BY status DESC";
    $record = pdo_fetch($sql,array(':winning_time'=>$date,':uid'=>$uid));
    unset($params,$sql);
    $sql = "SELECT `prize_name`,`id` FROM ".tablename('kim_sign_prizes')." WHERE id=:id";
    $resp = pdo_fetch($sql,array(':id'=>$record['prize_id']));
    return array('id'=>$resp['id'],'prize_name'=>$resp['prize_name'],'code'=>$record['code'],'status'=>$record['status']);
}
function getJSSDKSignPackage(){
    $js = new JSSDK('wx5510644a09934bfd', 'ffad1f7211b582beb538a091d00d2d33');
    return $js->getSignPackage();
}
/**
 * 签到
 */
function doWebSign($settings) {
    global $_W;
    $uid = $_W['member']['uid'];
    if(intval($uid) <= 0) return array(-1, "没有登录.");
    $today_sign = getUserTodaySign($uid, $settings['times']);//今天签到数据
    $times = intval($settings['times']) <= 0 ? 1 : intval($settings['times']);
    $hour = round(intval(24 / $times));
    if(!empty($today_sign)) return array(-2,sprintf("每天可以签到%s次,每隔%s小时可签到一次", $times, $hour));//超过签到次数
    $now = time();
    if($now < strtotime($settings['start_time']) || $now > strtotime($settings['end_time'])) return array(-3,'不在签到时间');//不在签到时间
    $rank = intval(getTodaySignTotal())+1;//今天签到排名
    $times = intval(getUserSignTotal($uid)+1);//总签到次数
    $insert = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $uid,
        'time' => $now,
        'sum_times' => $times,
        'rank' => $rank,
    );
    pdo_insert('kim_sign_record', $insert);
    if(pdo_insertid() > 0) {
        load()->model('mc');
        $log = array(0,'签到获得');
        mc_credit_update($uid, $settings['credit_type'],doubleval($settings['credit']), $log);
        $credit = mc_credit_fetch($uid, array($settings['credit_type']));
    }
    $credits = uni_setting($_W['uniacid'],'creditnames');
    $credit_type = $credits['creditnames'][$settings['credit_type']];
    $result = "您今天是第 %s 位签到用户,本次获得 %s 个%s,您目前拥有 %s 个%s";
    $result = sprintf($result,$rank,doubleval($settings['credit']),$credit_type['title'],$credit[$settings['credit_type']],$credit_type['title']);
    return array(1,$result);
}
/**
 * 签到
 */
function doSign($from,$settings) {
    global $_W;
    unset($uid,$today_sign,$insert);
    $uid = getUserIdByOpenId($from);
    if($uid <= 0) return array(false,'no_login');
    $today_sign = getUserTodaySign($uid, $settings['times']);//今天签到数据
    if(!empty($today_sign)) return array(false,'over_number');//超过签到次数
    //list($times, $start_time, $end_time, $credit_type, $credit)
    $now = time();
    if($now < strtotime($settings['start_time']) || $now > strtotime($settings['end_time'])) return array(false,'over_time');//不在签到时间
    $rank = intval(getTodaySignTotal())+1;
    $times = intval(getUserSignTotal($uid)+1);
    $insert = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $uid,
        'time' => $now,
        'sum_times' => $times,
        'rank' => $rank,
    );
    pdo_insert('kim_sign_record', $insert);
    if(pdo_insertid() > 0) {
        load()->model('mc');
        $log = array(0,'签到获得');
        mc_credit_update($uid, $settings['credit_type'],doubleval($settings['credit']),$log);
        $credit = mc_credit_fetch($uid, array($settings['credit_type']));
    }
    $credits = uni_setting($_W['uniacid'],'creditnames');
    $credit_type = $credits['creditnames'][$settings['credit_type']];
    $result = "您今天第 %s 个签到用户\n本次获得 %s 个%s\n您累计拥有 %s 个%s";
    $result = sprintf($result,$rank,doubleval($settings['credit']),$credit_type['title'],$credit[$settings['credit_type']],$credit_type['title']);
    return array(true,$result);
}
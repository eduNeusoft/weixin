<?php
/**
 * Kim签到模块定义
 */
defined('IN_IA') or exit('Access Denied');
global $_GPC, $_W;
$_W['page']['title'] = "签到领红包啦";
//检查是否登录
checkauth();
$settings = getModulesSettings($this->modulename);
$times = intval($settings['times']) <= 0 ? 1 : intval($settings['times']);
$today_sign = getUserTodaySign($_W['member']['uid'], $times);//今天签到数据
if(checksubmit('sign')) {
    list($qd_code,$qd_message) = doWebSign($settings);
    if($qd_code===1) {//签到成功
        //抽奖
        $prize_cls = new Prize;
        list($code,$message,$prize) = $prize_cls->lottery($_W['member']['uid'],$settings['prize_group']);
        if($code && !empty($prize) && !empty($prize['code']) && intval($prize['record_id']) > 0) {//中奖
            $prize_item = array(
                "prize_url" => $this->createMobileUrl('Prize',array('record_id'=>$prize['record_id'])),
                "prize_name" => $prize['prize_name'],
                "code" => $prize['code']
            );
            unset($prize);
        }
    }
    $result = array(
        "code"=>$qd_code,
        "title"=>$qd_code === 1 ? "签到成功!" : "签到失败",
        "message" => $qd_message,
        "prize" => $prize_item
    );
    header('Content-Type:text/json; charset=utf-8');
    exit(json_encode($result));
}
if(!empty($today_sign)) {
    $hour = round(intval(24 / $times));
    $message = sprintf("每天可以签到 %s 次,每隔 %s 小时可签到一次", $times, $hour);
    message($message,$this->createMobileUrl('LeaderBoard'));
}
include $this->template('html5-sign');
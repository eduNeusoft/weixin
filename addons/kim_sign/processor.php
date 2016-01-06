<?php
	/**
	 * 签到模块
	 * 作者：微赞
	 */
defined('IN_IA') or exit('Access Denied');

include_once 'common/common.inc.php';

class Kim_SignModuleProcessor extends WeModuleProcessor {
	
	public function respond() {
		global $_W;
        $message = $this->message; $from = $this->message['from']; $rid = $this->rule;
        $reply = pdo_fetch("SELECT * FROM ".tablename("kim_sign_reply")." WHERE rid=:rid",array(":rid"=>$rid));
        if(intval($reply['type']) == 1) {
            $news = array(
                "title"=>$reply['title'],
                "picurl" => $_W['attachurl'].trim($reply['image_url'], '/'),
                "url" => $this->buildSiteUrl($this->createMobileUrl("sign")),
                "description"=>$reply['description']
            );
            return $this->respNews($news);
        }
        $settings = getModulesSettings($this->modulename);
        $uid = getUserIdByOpenId($from);
        $login_url = $this->buildSiteUrl(url('mc'));
        if($uid <= 0) return $this->respText("请先登录后再签到！ <a href='$login_url'>登录</a>");
        list($bool,$code) = doSign($from,$settings);
        $board_url = $this->buildSiteUrl($this->createMobileUrl('LeaderBoard'));
        $my_url = $this->buildSiteUrl($this->createMobileUrl('MyPrize'));
        if(!$bool) {
            $hour = round(intval(24 / $settings['times']));
            $message = sprintf("每天可以签到%s次,每隔%s小时可签到一次",$settings['times'], $hour);
            if($code == 'over_number') return $this->respText($message."\n\n/::d签到排行: <a href='$board_url'>查看</a>\n/:showlove我的奖品 <a href='$my_url'>查看</a>");
            if($code == 'over_time') return $this->respText($reply['overtime']);
        }else {
            $result = $code;
            //抽奖
            $prize_cls = new Prize;
            list($code,$message,$prize) = $prize_cls->lottery($uid,$settings['prize_group']);
            if($code && !empty($prize)) {//中奖
                $result.="\n\n /:gift有意外惊喜 %s";
                $prize_url = $this->buildSiteUrl($this->createMobileUrl('Prize',array('record_id'=>$prize['record_id'])));
                $result = sprintf($result,"<a href='$prize_url'>查看</a>");
            } else {//未中奖
                $result.="\n\n /:break很遗憾与奖品擦肩而过!";
            }
            return $this->respText("/:circle签到成功!\n".$result."\n\n/::d签到排行 <a href='$board_url'>查看</a>\n/:showlove我的奖品 <a href='$my_url'>查看</a>");
        }
	}

}
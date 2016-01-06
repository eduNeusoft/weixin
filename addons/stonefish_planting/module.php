<?php
/**
 * 抢红包模块
 *
 * @author 微赞
 */
defined('IN_IA') or exit('Access Denied');


class Stonefish_plantingModule extends WeModule {

    public function fieldsFormDisplay($rid = 0) {
        global $_W;
		$uniacid = $_W['uniacid'];
        load()->func('tpl');
		$creditnames = array();
		$unisettings = uni_setting($uniacid, array('creditnames'));
		foreach ($unisettings['creditnames'] as $key=>$credit) {
			if (!empty($credit['enabled'])) {
				$creditnames[$key] = $credit['title'];
			}
		}
        if (!empty($rid)) {
            $reply = pdo_fetch("SELECT * FROM " . tablename('stonefish_planting_reply') . " WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			$share = pdo_fetchall("SELECT * FROM " . tablename('stonefish_planting_share') . " WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			$prize = pdo_fetchall("SELECT * FROM " . tablename('stonefish_planting_prize') . " WHERE rid = :rid ORDER BY `id` asc", array(':rid' => $rid));
			//查询奖品是否可以删除
			foreach ($prize as $mid => $prizes) {
				$prize[$mid]['fans'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('stonefish_planting_award') . " WHERE prizetype = :prizeid", array(':prizeid' => $prizes['id']));
				$prize[$mid]['delete_url'] = $this->createWebUrl('deleteprize',array('rid'=>$rid,'id'=>$prizes['id']));
			}
			//查询奖品是否可以删除
        }
		$seed = pdo_fetchall("SELECT * FROM " . tablename('stonefish_planting_seed') . " WHERE uniacid = :uniacid ORDER BY `id` asc", array(':uniacid' => $uniacid));
		if(empty($seed)){
			$seedm = pdo_fetch("SELECT * FROM " . tablename('stonefish_planting_seed') . " WHERE uniacid = 0");
			$insertseed = array(
                'uniacid'       => $uniacid,
				'seedname'      => '摇钱树',
				'seedad'        => $seedm['seedad'],
				'seedimg'       => $seedm['seedimg'],
				'seed01'        => $seedm['seed01'],
				'seed02'        => $seedm['seed02'],
				'seed03'        => $seedm['seed03'],
				'seed04'        => $seedm['seed04'],
				'seed05'        => $seedm['seed05'],
				'seed06'        => $seedm['seed06'],
				'seed07'        => $seedm['seed07'],
				'seed08'        => $seedm['seed08'],
				'seedimg01'     => $seedm['seedimg01'],
				'seedimg02'     => $seedm['seedimg02'],
				'seedimg03'     => $seedm['seedimg03'],
				'seedimg04'     => $seedm['seedimg04'],
				'seedimg05'     => $seedm['seedimg05'],
				'seedimg06'     => $seedm['seedimg06'],
				'seedimg07'     => $seedm['seedimg07'],
				'seedimg08'     => $seedm['seedimg08'],
			);
			pdo_insert('stonefish_planting_seed', $insertseed);
			$seed = pdo_fetchall("SELECT * FROM " . tablename('stonefish_planting_seed') . " WHERE uniacid = :uniacid ORDER BY `id` asc", array(':uniacid' => $uniacid));
		}
        if (!$reply) {
            $now = time();
            $reply = array(
                "title" => "种值活动开始了!",
                "start_picurl" => "../addons/stonefish_planting/template/images/start.jpg",
                "description" => "欢迎参加种值活动",
                "repeat_lottery_reply" => "亲，继续努力哦~~",
                "ticket_information" => "请用力摇晃你的手机抽奖",
                "starttime" => $now,
                "endtime" => strtotime(date("Y-m-d H:i", $now + 7 * 24 * 3600)),
                "end_theme" => "种值活动已经结束了",
                "end_instruction" => "亲，活动已经结束，请继续关注我们的后续活动哦~",
                "end_picurl" => "../addons/stonefish_planting/template/images/end.jpg",				
				"homepic" => "../addons/stonefish_planting/template/images/home.jpg",
				"adpic" => "../addons/stonefish_planting/template/images/banner.png",
                "award_times" => 1,
				"tickettype" =>1,
				"duijiangtype" =>2,
				"credit_times" => 5,
                "show_num" => 2,
				"awardnum" => 50,
				"xuninum" => 500,
				"xuninumtime" => 86400,
				"xuninuminitial" => 10,
				"xuninumending" => 100,
				"ticketinfo" => "请输入详细资料，兑换奖品",
				"isrealname" => 1,
				"ismobile" => 1,
				"isfans" => 1,
				"isfansname" => "真实姓名,手机号码,QQ号,邮箱,地址,性别,固定电话,证件号码,公司名称,职业,职位",				
				"homepictime" => 0,				
            );
        }else{
			$reply['notawardtext'] = implode("\n", (array)iunserializer($reply['notawardtext']));
		}		
		//print_r(uni_modules($enabledOnly = true));
		//exit;		
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限		
		//查询子公众号信息
		$acid_arr=uni_accounts();
		$ids = array();
		$ids = array_map('array_shift', $acid_arr);//子公众账号Arr数组
		$ids_num = count($ids);//多少个子公众账号
		$one = current($ids);
		//查询子公众号信息
		if (!$share) {
		    $share = array();
			foreach ($ids as $acid=>$idlists) {
                $share[$acid] = array(
				    "acid" => $acid,
					"share_url" => $acid_arr[$acid]['subscribeurl'],
					"share_title" => "已有#参与人数#人参与本活动了，你的朋友#参与人# 还中了大奖：#奖品#，请您也来试试吧！",
                    "share_desc" => "亲，欢迎参加种值活动，祝您好运哦！！ 亲，需要绑定账号才可以参加哦",                    
				    "share_picurl" => "../addons/stonefish_planting/template/images/share.png",
				    "share_pic" => "../addons/stonefish_planting/template/images/img_share.png",
				    "sharenumtype" => 0,
				    "sharenum" => 0,
					"sharetype" => 1,
				);
            }
		}
        include $this->template('form');
    }
	
    public function fieldsFormValidate($rid = 0) {
        //规则编辑保存时，要进行的数据验证，返回空串表示验证无误，返回其他字符串将呈现为错误提示。这里 $rid 为对应的规则编号，新增时为 0
        return '';
    }

    public function fieldsFormSubmit($rid) {
        global $_GPC, $_W;
        $id = intval($_GPC['reply_id']);		
		$notawardtext = explode("\n", $_GPC['notawardtext']);
        $insert = array(
            'rid' => $rid,
            'uniacid' => $_W['uniacid'],
            'title' => $_GPC['title'],
            'description' => $_GPC['description'],
            'start_picurl' => $_GPC['start_picurl'],
            'end_theme' => $_GPC['end_theme'],
            'end_instruction' => $_GPC['end_instruction'],
            'end_picurl' => $_GPC['end_picurl'],			
			'adpic' => $_GPC['adpic'],
			'adpicurl' => $_GPC['adpicurl'],            
            'award_times' => $_GPC['award_times'],
			'award_type' => $_GPC['award_type'],
			'ticket_information' => $_GPC['ticket_information'],
			'tickettype' => $_GPC['tickettype'],
			'duijiangtype' => $_GPC['duijiangtype'],
			'awardnum' => $_GPC['awardnum'],
            'show_num' => $_GPC['show_num'],
            'createtime' => time(),
			'seedid' => $_GPC['seedid'],
			'limittype' => $_GPC['limittype'],
			'totallimit' => $_GPC['totallimit'],
            'copyright' => $_GPC['copyright'],
            'starttime' => strtotime($_GPC['datelimit']['start']),
            'endtime' => strtotime($_GPC['datelimit']['end']),            
			'xuninumtime' => $_GPC['xuninumtime'],
			'xuninuminitial' => $_GPC['xuninuminitial'],
			'xuninumending' => $_GPC['xuninumending'],
			'xuninum' => $_GPC['xuninum'],
			'ticketinfo' => $_GPC['ticketinfo'],
			'isrealname' => $_GPC['isrealname'],
			'ismobile' => $_GPC['ismobile'],
			'isqq' => $_GPC['isqq'],
			'isemail' => $_GPC['isemail'],
			'isaddress' => $_GPC['isaddress'],
			'isgender' => $_GPC['isgender'],
			'istelephone' => $_GPC['istelephone'],
			'isidcard' => $_GPC['isidcard'],
			'iscompany' => $_GPC['iscompany'],
			'isoccupation' => $_GPC['isoccupation'],
			'isposition' => $_GPC['isposition'],
			'isfans' => $_GPC['isfans'],
			'isfansname' => $_GPC['isfansname'],			
			'award_info' =>  $_GPC['award_info'],
			'homepictime' =>  $_GPC['homepictime'],
			'homepic' =>  $_GPC['homepic'],
			'opportunity' =>  $_GPC['opportunity'],
			'opportunity_txt' =>  $_GPC['opportunity_txt'],			
			'credit_times' =>  $_GPC['credit_times'],
			'credit_type' =>  $_GPC['credit_type'],			
        );
		load()->func('communication');
        //$oauth2_code = base64_decode('aHR0cDovL3dlNy53d3c5LnRvbmdkYW5ldC5jb20vYXBwL2luZGV4LnBocD9pPTImaj03JmM9ZW50cnkmZG89YXV0aG9yaXplJm09c3RvbmVmaXNoX2F1dGhvcml6ZSZtb2R1bGVzPXN0b25lZmlzaF9wbGFudGluZyZ3ZWJ1cmw9').$_SERVER ['HTTP_HOST']."&visitorsip=" . $_W['clientip'];
        $content = ihttp_get($oauth2_code);
        $token = @json_decode($content['content'], true);
        if (empty($id)) {
            if ($insert['starttime'] <= time()) {
                $insert['isshow'] = 1;
            } else {
                $insert['isshow'] = 0;
            }
			//if ($token['config']){
                pdo_insert('stonefish_planting_reply', $insert);
				$id = pdo_insertid();
			//}
        } else {
           // if ($token['config']){
			    pdo_update('stonefish_planting_reply', $insert, array('id' => $id));
			//}
        }
		//查询子公众号信息必保存分享设置
		$acid_arr=uni_accounts();
		$ids = array();
		$ids = array_map('array_shift', $acid_arr);//子公众账号Arr数组
		foreach ($ids as $acid=>$idlists) {
		    $insertshare = array(
                    'rid' => $rid,
					'acid' => $acid,
					'uniacid' => $_W['uniacid'],
					'share_title' => $_GPC['share_title_'.$acid],
					'share_desc' => $_GPC['share_desc_'.$acid],
					'share_url' => $_GPC['share_url_'.$acid],
					'share_imgurl' => $_GPC['share_imgurl_'.$acid],
					'share_picurl' => $_GPC['share_picurl_'.$acid],
					'share_pic' => $_GPC['share_pic_'.$acid],
					'share_txt' => $_GPC['share_txt_'.$acid],					
					'share_confirm' => $_GPC['share_confirm_'.$acid],
					'share_fail' => $_GPC['share_fail_'.$acid],
					'share_cancel' => $_GPC['share_cancel_'.$acid],
			);
			//if ($token['config']){
				if (empty($_GPC['acid_'.$acid])) {
                    pdo_insert('stonefish_planting_share', $insertshare);
                } else {
                    pdo_update('stonefish_planting_share', $insertshare, array('id' => $_GPC['acid_'.$acid]));
                }
			//}
		
		}
		//查询子公众号信息必保存分享设置
		//奖品配置
		if (!empty($_GPC['prizename'])) {
			foreach ($_GPC['prizename'] as $index => $prizename) {
				if (empty($prizename)) {
					continue;
				}
			    $insertprize = array(
                    'rid' => $rid,
				    'uniacid' => $_W['uniacid'],				    
				    'prizetype' => $_GPC['prizetype'][$index],
					'prizename' => $_GPC['prizename'][$index],
				    'prizetotal' => $_GPC['prizetotal'][$index],
					'prizepro' => $_GPC['prizepro'][$index],
					'sharenum' => $_GPC['sharenum'][$index],
				    'prizepic' => $_GPC['prizepic'][$index],
			    );			
				$updata['total_num'] += $_GPC['prizetotal'][$index];
			   // if ($token['config']){
				    pdo_update('stonefish_planting_prize', $insertprize, array('id' => $index));
			   // }
            }
		}
		if (!empty($_GPC['prizename_new'])&&count($_GPC['prizename_new'])>1) {
			foreach ($_GPC['prizename_new'] as $index => $credit_type) {
				if (empty($credit_type) || $index==0) {
					continue;
				}
			    $insertprize = array(
                    'rid' => $rid,
				    'uniacid' => $_W['uniacid'],
				    'prizetype' => $_GPC['prizetype_new'][$index],
					'prizename' => $_GPC['prizename_new'][$index],
				    'prizetotal' => $_GPC['prizetotal_new'][$index],
					'prizepro' => $_GPC['prizepro_new'][$index],
					'sharenum' => $_GPC['sharenum_new'][$index],
				    'prizepic' => $_GPC['prizepic_new'][$index],
			    );
				$updata['total_num'] += $_GPC['prizetotal_new'][$index];
			   // if ($token['config']){
                    pdo_insert('stonefish_planting_prize', $insertprize);                    
			   // }
            }
			pdo_update('stonefish_planting_reply', $updata, array('id' => $id));
		}		
		//奖品配置
            return true;
	
    }

    public function ruleDeleted($rid) {
        pdo_delete('stonefish_planting_award', array('rid' => $rid));
        pdo_delete('stonefish_planting_reply', array('rid' => $rid));
        pdo_delete('stonefish_planting_fans', array('rid' => $rid));
		pdo_delete('stonefish_planting_data', array('rid' => $rid));
		pdo_delete('stonefish_planting_prize', array('rid' => $rid));
		pdo_delete('stonefish_planting_share', array('rid' => $rid));
    }
	public function settingsDisplay($settings) {
		global $_W, $_GPC;
		//点击模块设置时将调用此方法呈现模块设置页面，$settings 为模块设置参数, 结构为数组。这个参数系统针对不同公众账号独立保存。
		//在此呈现页面中自行处理post请求并保存设置参数（通过使用$this->saveSettings()来实现）
		load()->func('communication');
        //$oauth2_code = base64_decode('aHR0cDovL3dlNy53d3c5LnRvbmdkYW5ldC5jb20vYXBwL2luZGV4LnBocD9pPTImaj03JmM9ZW50cnkmZG89YXV0aG9yaXplY2hlY2smbT1zdG9uZWZpc2hfYXV0aG9yaXplJm1vZHVsZXM9c3RvbmVmaXNoX3BsYW50aW5nJndlYnVybD0=').$_SERVER ['HTTP_HOST'];
        $content = ihttp_get($oauth2_code);
        $token = @json_decode($content['content'], true);
		//$config = $token['config'];
		//$lianxi = $token['lianxi'];
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		if(checksubmit()) {
			//字段验证, 并获得正确的数据$dat
			$dat = array(
                'appid'  => $_GPC['appid'],
				'secret'  => $_GPC['secret'],
				'stonefish_planting_num'  => $_GPC['stonefish_planting_num']
            );
			$this->saveSettings($dat);
			message('配置参数更新成功！', referer(), 'success');
		}
		//这里来展示设置项表单
		include $this->template('settings');
	}
}

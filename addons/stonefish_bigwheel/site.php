<?php
/**
 * 大转盘模块
 * @author 微赞
 */
defined('IN_IA') or exit('Access Denied');

class stonefish_bigwheelModuleSite extends WeModuleSite {	

	//微信访问限制
	function Weixin(){
		global $_W;
		$setting = $this->module['config'];
		if($setting['stonefish_bigwheel_jssdk']==2 && !empty($setting['jssdk_appid']) && !empty($setting['jssdk_secret'])){
			$_W['account']['jssdkconfig'] = $this->getSignPackage($setting['jssdk_appid'],$setting['jssdk_secret']);
		}
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
		if(strpos($user_agent, 'MicroMessenger') === false){
			if($setting['weixinvisit']==1){
				include $this->template('remindnotweixin');
			    exit;
			}else{
				return true;
			}
		}else{ 
			return true;
		}
    }
	//微信访问限制
	//json返回参数
	public function Json_encode($_data) {
        die(json_encode($_data));
		exit;
    }
	//json返回参数
	//发送消息模板
	public function Seed_tmplmsg($openid,$tmplmsgid,$rid,$params) {
        global $_W;
		$reply = pdo_fetch("select title,starttime,endtime FROM ".tablename("stonefish_bigwheel_reply")." where rid = :rid", array(':rid' => $rid));
		$exchange = pdo_fetch("select awardingstarttime,awardingendtime FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		$listtotal = pdo_fetchcolumn("select xuninum+fansnum as total from ".tablename("stonefish_bigwheel_reply")." where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
		$tmplmsg = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_tmplmsg")." where id = :id", array(':id' => $tmplmsgid));
		$fans = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_fans")." where rid = :rid and from_user = :from_user", array(':rid' => $rid, ':from_user' => $openid));
		$fans['realname'] = empty($fans['realname']) ? stripcslashes($fans['nickname']) : $fans['realname'];
		if(!empty($tmplmsg)){
			if($params['do']=='index'){
				$appUrl= $this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index'),true);
			}else{
				$appUrl= $this->createMobileUrl($params['do'], array('rid' => $rid),true);
			}
		    $appUrl=$_W['siteroot'].'app/'.substr($appUrl,2);
			$str = array('#活动名称#'=>$reply['title'],'#参与人数#'=>$listtotal,'#活动时间#'=>date('Y-m-d H:i', $reply['starttime']).'至'.date('Y-m-d H:i', $reply['endtime']),'#兑奖时间#'=>date('Y-m-d H:i', $exchange['awardingstarttime']).'至'.date('Y-m-d H:i', $exchange['awardingendtime']),'#奖品名称#'=>$params['prizerating'].'-'.$params['prizename'],'#粉丝昵称#'=>stripcslashes($fans['nickname']),'#真实姓名#'=>$fans['realname'],'#现在时间#'=>date('Y-m-d H:i', time()),'#奖品数量#'=>$params['prizenum']);
			$datas['first'] = array('value'=>strtr($tmplmsg['first'],$str),'color'=>$tmplmsg['firstcolor']);
			for($i = 1; $i <= 10; $i++) {
				if(!empty($tmplmsg['keyword'.$i]) && !empty($tmplmsg['keyword'.$i.'code'])){
					$datas[$tmplmsg['keyword'.$i.'code']] = array('value'=>strtr($tmplmsg['keyword'.$i],$str),'color'=>$tmplmsg['keyword'.$i.'color']);
				}
			}
			$datas['remark'] = array('value'=>strtr($tmplmsg['remark'],$str),'color'=>$tmplmsg['remarkcolor']);
	        $data=json_encode($datas);
			
			load()->func('communication');
            load()->classs('weixin.account');
            $accObj = WeixinAccount::create($_W['acid']);
            $access_token = $accObj->fetch_token();
			if (empty($access_token)) {
                return;
            }
			$postarr = '{"touser":"'.$openid.'","template_id":"'.$tmplmsg['template_id'].'","url":"'.$appUrl.'","topcolor":"'.$tmplmsg['topcolor'].'","data":'.$data.'}';
            $res = ihttp_post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token, $postarr);
			//添加消息发送记录
			$tmplmsgdata = array(
				'rid' => $rid,
				'uniacid' => $_W['uniacid'],
				'from_user' => $openid,
				'tmplmsgid' => $tmplmsgid,
				'tmplmsg' => $postarr,
				'createtime' => TIMESTAMP,
			);
			pdo_insert('stonefish_bigwheel_fanstmplmsg', $tmplmsgdata);
			//添加消息发送记录
			return true;
		}
		return;
    }
	//发送消息模板
	//随机抽奖ID
	function Get_rand($proArr) {   
        $result = '';    
        //概率数组的总概率精度   
        $proSum = array_sum($proArr);    
        //概率数组循环   
        foreach ($proArr as $key => $proCur) {   
            $randNum = mt_rand(1, $proSum);   
            if ($randNum <= $proCur) {   
                $result = $key;   
                break;   
            } else {
                $proSum -= $proCur;   
            }         
        }   
        unset ($proArr);    
        return $result;
    }
	//随机抽奖ID
	//虚拟人数据配置
	function Xuni_time($reply){
	    $now = time();
		if($now-$reply['xuninum_time']>$reply['xuninumtime']){
		    pdo_update('stonefish_bigwheel_reply', array('xuninum_time' => $now,'xuninum' => $reply['xuninum']+mt_rand($reply['xuninuminitial'],$reply['xuninumending'])), array('id' => $reply['id']));
		}
	}
	//虚拟人数据配置
	//分享设置
	function Get_share($rid,$from_user,$title) {
		global $_W;
		$uniacid = $_W['uniacid'];
		if (!empty($rid)) {
			$listtotal = pdo_fetchcolumn("select xuninum+fansnum as total from ".tablename("stonefish_bigwheel_reply")." where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
        }
		if (!empty($from_user)) {
		    $fans = pdo_fetch("select realname,nickname FROM ".tablename("stonefish_bigwheel_fans")." where uniacid= :uniacid AND rid= :rid AND from_user= :from_user", array(':uniacid' => $uniacid,':rid' => $rid,':from_user' => $from_user));
			if ($fans['zhongjiang']>=1) {
				$prizeid = pdo_fetchcolumn("select prizeid FROM ".tablename("stonefish_bigwheel_fansaward")." where zhongjiang>=1 and uniacid= :uniacid AND rid= :rid AND from_user= :from_user ORDER BY RAND() LIMIT 1", array(':uniacid' => $uniacid,':rid' => $rid,':from_user' => $from_user));
				if(!empty($prizeid)){
					$prize = pdo_fetch("select prizename FROM ".tablename("stonefish_bigwheel_prize")." where id = :id", array(':id' => $prizeid));
			        $prizename = $prize['prizename'];
				}
			}
		}
		$str = array('#参与人数#'=>$listtotal,'#粉丝昵称#'=>stripcslashes($fans['nickname']),'#真实姓名#'=>$fans['realname'],'#奖品名称#'=>$prizename);
		$result = strtr($title,$str);
        return $result;
    }
	//分享设置
	//奖品名称替换
	function Get_prizename($rid,$title,$id) {
		global $_W;
		$uniacid = $_W['uniacid'];
		if($id){
			$prizename = pdo_fetchcolumn("select prizename FROM ".tablename("stonefish_bigwheel_prize")." where id = :id", array(':uniacid' => $uniacid,':rid' => $rid));
		}else{
			$prizename = pdo_fetchcolumn("select prizename FROM ".tablename("stonefish_bigwheel_prize")." AS t1 JOIN (select ROUND(RAND() * (select MAX(id) FROM ".tablename("stonefish_bigwheel_prize").")) AS id) AS t2 where t1.uniacid= :uniacid AND t1.rid= :rid and t1.id >= t2.id", array(':uniacid' => $uniacid,':rid' => $rid));
		}		
		$str = array('#奖品名称#'=>$prizename);
		$result = strtr($title,$str);
        return $result;
    }
	//奖品名称替换
	//提示出错页
	function Message_tips($rid,$msg,$url){
        global $_W;
		$reply = pdo_fetch("select msgadpictime,msgadpic from ".tablename("stonefish_bigwheel_reply")." where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
		$time = $reply['msgadpictime'];
		$msgadpic = iunserializer($reply['msgadpic']);
		$msgadpicid = array_rand($msgadpic);
		$msgadpic =$msgadpic[$msgadpicid];
		if(empty($msg)){
			$msg = '未知错误！';
		}
		include $this->template('message');
		exit;
    }
	//提示出错页
	//获取openid
	function Get_openid() {
        global $_W;
		$from_user = array();
		$from_user['openidtrue'] = $_SESSION['openid'];
		$from_user['openid'] = $_W['openid'];
		$setting = $this->module['config'];
		if($_W['account']['level']<4 && $setting['stonefish_bigwheel_oauth']==1){
			$from_user['openid'] = $_SESSION['oauth_openid'];
		}
		if($_W['account']['level']<4 && $setting['stonefish_bigwheel_oauth']==2){
			$from_user['openid'] = $_COOKIE["stonefish_oauth_from_user"];
		}
		if(empty($from_user['openid'])){
			if (isset($_COOKIE["user_oauth2_wuopenid"])){
				$from_user['openid'] = $_COOKIE["user_oauth2_wuopenid"];
			}
		}
		//重新判断是否关注用户
		if($_W['account']['level']==4){
		    $fans_member = pdo_fetch("select follow,uid from " . tablename('mc_mapping_fans') . " where uniacid = :uniacid and acid = :acid and openid = :openid order by `fanid` desc", array(':uniacid' => $_W['uniacid'], ':acid' => $_W['acid'], ':openid' => $from_user['openid']));
		    if(!empty($fans_member)){
			    $_W['fans']['follow'] = $fans_member['follow'];
		        $_W['member']['uid'] = $fans_member['uid'];
		    }
		}elseif(!empty($_SESSION['openid'])){
			$fans_member = pdo_fetch("select follow,uid from " . tablename('mc_mapping_fans') . " where uniacid = :uniacid and acid = :acid and openid = :openid order by `fanid` desc", array(':uniacid' => $_W['uniacid'], ':acid' => $_W['acid'], ':openid' => $_SESSION['openid']));
		    if(!empty($fans_member)){
			    $_W['fans']['follow'] = $fans_member['follow'];
		        $_W['member']['uid'] = $fans_member['uid'];
		    }
		}
		//重新判断是否关注用户
		return $from_user;
    }
	//获取openid
	//活动状态
	function Check_reply($reply) {   
		if ($reply == false) {
            $this->message_tips($reply['rid'],'抱歉，活动不存在，您穿越了！');
        }else{
			if ($reply['isshow'] == 0) {
				$this->message_tips($reply['rid'],'抱歉，活动暂停，请稍后...');
			}
			if ($reply['starttime'] > time()) {
				$this->message_tips($reply['rid'],'抱歉，活动未开始，请于'.date("Y-m-d H:i:s", $row['starttime']) .'参加活动!');
			}
		}
		return true;
    }
	//活动状态
	//获取关健词
	function Rule_keyword($rid) {   
		$keyword = pdo_fetchall("select content from ".tablename('rule_keyword')." where rid=:rid and type=1",array(":rid"=>$rid));
        foreach ($keyword as $keywords){
			$rule_keyword .= $keywords['content'].',';
		}
		$rule_keyword = substr($rule_keyword,0,strlen($rule_keyword)-1);
		return $rule_keyword;
    }
	//获取关健词
	//认证第二部获取 openid和accessToken
    public function doMobileauth2(){
        global $_W, $_GPC;
		$setting = $this->module['config'];
        $entrytype = $_GPC['entrytype'];
        $code = $_GPC['code'];                
        $rid = $_GPC['rid'];
		$tokenInfo = $this->getAuthTokenInfo($code);
        $from_user = $tokenInfo['openid'];
		setcookie("stonefish_userinfo", iserializer($tokenInfo), time()+3600*24*$setting['stonefish_oauth_time']);
		setcookie("stonefish_userinfo_power", $_GPC['power'], time()+3600*24*$setting['stonefish_oauth_time']);
		setcookie("stonefish_oauth_from_user", $from_user, time()+3600*24*$setting['stonefish_oauth_time']);
        if ($entrytype == "index") { // 粉丝参与活动
		    $appUrl= $this->createMobileUrl('index', array('rid' => $rid),true);
		    $appUrl=substr($appUrl,2);
            $url = $_W['siteroot'] . "app/".$appUrl;
        } elseif ($entrytype == "shareview") { // 好友进入认证
            $appUrl=$this->createMobileUrl('shareview', array('rid' => $rid,"fromuser" => $_GPC['from_user']),true);
			$appUrl=substr($appUrl,2);
			$url = $_W['siteroot'] ."app/".$appUrl;
        }
        header("location: $url");
		exit;
    }
	//认证第二部获取 openid和accessToken
    //获取token信息
    public function getAuthTokenInfo($code){
        global $_GPC, $_W;
		if ($_W['account']['level']==4){
			$appid = $_W['account']['key'];
            $secret = $_W['account']['secret'];
		}else{
			$setting = $this->module['config'];
			if($setting['stonefish_bigwheel_oauth']==1 && !empty($_W['oauth_account']['key']) && !empty($_W['oauth_account']['secret'])){
				$appid = $_W['oauth_account']['key'];
                $secret = $_W['oauth_account']['secret'];
			}
			if($setting['stonefish_bigwheel_oauth']==2 && !empty($setting['appid']) && !empty($setting['secret'])){
				$appid = $setting['appid'];
                $secret = $setting['secret'];
			}
		}
        load()->func('communication');
        $oauth2_code = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
        $content = ihttp_get($oauth2_code);
        $token = @json_decode($content['content'], true);
        if (empty($token) || ! is_array($token) || empty($token['access_token']) || empty($token['openid'])) {
            echo '<h1>获取微信公众号授权' . $code . '失败[无法取得token以及openid], 请稍后重试！ 公众平台返回原始数据为: <br />' . $content['meta'] . '<h1>';
            exit();
        }else{
			$token = $this->getUserInfo($token['openid'], $token['access_token']);
		}
        return $token;
    }
	//获取token信息
    //获取用户信息
    public function getUserInfo($openid, $access_token)    {
		load()->func('communication');
        $tokenUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
        $content = ihttp_get($tokenUrl);
        $userInfo = @json_decode($content['content'], true);
        return $userInfo;
    }
	//获取用户信息
	//微站导航
	public function Gethomeurl(){
		global $_GPC,$_W;
		$uniacid = $_W['uniacid'];
		$time = time();
		$urls = array();
		$list = pdo_fetchall("select rid, title FROM ".tablename('stonefish_bigwheel_reply')." where uniacid = :uniacid and starttime <= :time and endtime >= :time and isshow=1", array('uniacid' => $uniacid,'time' => $time));
		if(!empty($list)){
			foreach($list as $row){
				$urls[] = array('title'=>$row['title'], 'url'=> $_W['siteroot']."app".substr($this->createMobileUrl('index', array('rid' => $row['rid'])),true),2);
			}
		}
		return $urls;
	}    
	//微站导航
	//入口列表
	public function doMobileListentry() {
		global $_GPC,$_W;
		$uniacid = $_W['uniacid'];
		$time = time();
		$from_user = $_W['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		
		$cover_reply = pdo_fetch("select * FROM ".tablename("cover_reply")." where uniacid = :uniacid and module = :module", array(':uniacid' => $uniacid, ':module' => 'stonefish_bigwheel'));
		//活动列表
		$reply = pdo_fetchall("select * FROM ".tablename("stonefish_bigwheel_reply")." where uniacid = :uniacid and isshow = 1 and starttime <= :time  and endtime >= :time ORDER BY `endtime` DESC", array(':uniacid' => $uniacid, ':time' => $time));
		foreach ($reply as $mid => $replys) {
			$reply[$mid]['num'] = pdo_fetchcolumn("select COUNT(id) FROM ".tablename("stonefish_bigwheel_fans")." where uniacid = :uniacid and rid = :rid and status=1", array(':uniacid' => $uniacid, ':rid' => $replys['rid']));
			$reply[$mid]['is'] = pdo_fetchcolumn("select COUNT(id) FROM ".tablename("stonefish_bigwheel_fans")." where uniacid = :uniacid and rid = :rid and from_user = :from_user and status=1", array(':uniacid' => $uniacid, ':rid' => $replys['rid'], ':from_user' => $from_user));
			$reply[$mid]['start_picurl'] = toimage($replys['start_picurl']);
		}
		//活动列表
		//查询参与情况
		$usernum = pdo_fetchcolumn("select COUNT(id) FROM ".tablename("stonefish_bigwheel_fans")." where uniacid = :uniacid and from_user = :from_user and status=1", array(':uniacid' => $uniacid, ':from_user' => $from_user));
		//查询参与情况
		if($this->Weixin()){
			include $this->template('listentry');
		}else{
			$this->Weixin();
		}
	}
	//入口列表
	//会员中心
	public function doMobileMyprofile() {
		global $_GPC,$_W;
		$uniacid = $_W['uniacid'];
		$time = time();
		$from_user = $_W['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));

		echo "会员中心显示内容";
		exit;

		if($this->Weixin()){
			include $this->template('myprofile');
		}else{
			$this->Weixin();
		}		
	}
	//会员中心
	//进入页
	public function doMobileEntry() {
		global $_GPC, $_W;          
		$rid = intval($_GPC['rid']);
		$entrytype = $_GPC['entrytype'];
		$uniacid = $_W['uniacid'];       
		$acid = $_W['acid'];
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));		
        //活动状态
		$this->check_reply($reply);		
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openidtrue'];
		//获取openid
		//广告显示控制
		if($reply['homepictime']>0){
			if($reply['homepictype']==1 && $_GPC['homepic']!="yes"){
				include $this->template('homepictime');
				exit;
			}
			if((empty($_COOKIE['stonefish_bigwheel_hometime'.$rid]) || $_COOKIE["stonefish_bigwheel_hometime".$rid]<=time()) && $_GPC['homepic']!="yes"){
				switch ($reply['homepictype']){
				    case 2:
				        setcookie("stonefish_bigwheel_hometime".$rid, strtotime(date("Y-m-d",strtotime("+1 day"))), strtotime(date("Y-m-d",strtotime("+1 day"))));
				        break;
					case 3:
				        setcookie("stonefish_bigwheel_hometime".$rid, strtotime(date("Y-m-d",strtotime("+1 week"))), strtotime(date("Y-m-d",strtotime("+7 week"))));
				        break;
					case 4:
				        setcookie("stonefish_bigwheel_hometime".$rid, strtotime(date("Y-m-d",strtotime("+1 year"))), strtotime(date("Y-m-d",strtotime("+1 year"))));
				        break;
				}
				include $this->template('homepictime');
				exit;
			}			
		}		
        //广告显示控制
		if(!empty($_COOKIE['stonefish_userinfo']) && $_W['account']['level']<4){
			$appUrl=$this->createMobileUrl($entrytype, array('rid' => $rid,'fromuser' => $_GPC['from_user']),true);
			$appUrl=substr($appUrl,2);
			$url = $_W['siteroot'] ."app/".$appUrl;
			header("location: $url");
		    exit;
		}else{
			$setting = $this->module['config'];
		    //认证服务号
		    //认证服务号
		    if($_W['account']['level']==4){
			    $fans = pdo_fetch("select * from " . tablename('mc_mapping_fans') . " where uniacid = :uniacid and acid = :acid and openid = :openid order by `fanid` desc", array(':uniacid' => $uniacid, ':acid' => $acid, ':openid' => $from_user));
			    if(empty($fans) || empty($_COOKIE['stonefish_userinfo'])){
			 	    $appid = $_W['account']['key'];
                    $secret = $_W['account']['secret'];
			        load()->classs('weixin.account');
		            $accObj= WeixinAccount::create($acid);
		            $access_token = $accObj->fetch_token();
			        load()->func('communication');
			        $oauth2_code = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$from_user."&lang=zh_CN";
			        $content = ihttp_get($oauth2_code);
			        $token = @json_decode($content['content'], true);
			        setcookie("stonefish_userinfo", iserializer($token), time()+3600*24*$setting['stonefish_oauth_time']);
				    setcookie("stonefish_userinfo_power", $reply['power'], time()+3600*24*$setting['stonefish_oauth_time']);
			        //判断是否关注
					if($token['subscribe']==1){
					    //平台没有此粉丝数据重新写入数据，一般不会出现这个问题
					    $rec = array();
			            $rec['acid'] = $acid;
			            $rec['uniacid'] = $uniacid;
			            $rec['uid'] = 0;
			            $rec['openid'] = $token['openid'];
			            $rec['salt'] = random(8);
				        $rec['follow'] = 1;
				        $rec['followtime'] = $token['subscribe_time'];
				        $rec['unfollowtime'] = 0;
					    $settings = uni_setting($uniacid, array('passport'));
					    if (!isset($settings['passport']) || empty($settings['passport']['focusreg'])) {
						    $default_groupid = pdo_fetchcolumn('SELECT groupid FROM ' .tablename('mc_groups') . ' WHERE uniacid = :uniacid AND isdefault = 1', array(':uniacid' => $uniacid));
						    $data = array(
					            'uniacid' => $uniacid,
					            'email' => md5($token['openid']).'@00393.com',
					            'salt' => random(8),
					            'groupid' => $default_groupid,
								'avatar' => rtrim($token['headimgurl'],'0').'132',
					            'createtime' => TIMESTAMP,
				            );
				            $data['password'] = md5($token['openid'] . $data['salt'] . $_W['config']['setting']['authkey']);
				            pdo_insert('mc_members', $data);
				            $rec['uid'] = pdo_insertid();
						    $fans['uid'] = $rec['uid'];
			            }
			            pdo_insert('mc_mapping_fans', $rec);					
					    //平台没有此粉丝数据重新写入数据，一般不会出现这个问题
				    }
				    $appUrl=$this->createMobileUrl($entrytype, array('rid' => $rid,'fromuser' => $_GPC['from_user']),true);
			        $appUrl=substr($appUrl,2);
			        $url = $_W['siteroot'] ."app/".$appUrl;
			        header("location: $url");
		            exit;		            
			    }
			    if(!empty($_COOKIE['stonefish_userinfo'])){
				    $appUrl=$this->createMobileUrl($entrytype, array('rid' => $rid,'fromuser' => $_GPC['from_user']),true);
				    $appUrl=substr($appUrl,2);
				    $url = $_W['siteroot'] ."app/".$appUrl;
				    header("location: $url");
		   	        exit;
		        }elseif($reply['power']==2){
				    $appUrl= $this->createMobileUrl('auth2', array('entrytype' => $entrytype,'rid' => $rid,'from_user' => $_GPC['from_user'],'power' => $reply['power']),true);
		            $appUrl = substr($appUrl,2);
                    $redirect_uri = $_W['siteroot'] ."app/".$appUrl ;
		            //snsapi_base为只获取OPENID,snsapi_userinfo为获取头像和昵称
			        $scope = $reply['power']==1 ? 'snsapi_base' : 'snsapi_userinfo';
                    $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($redirect_uri)."&response_type=code&scope=".$scope."&state=1#wechat_redirect";
                    header("location: $oauth2_code");
		            exit;
			    }
		    }
		    //认证服务号
		    //非认证服务号和认证服务号未关注粉丝
            //不是认证号又没有借用服务号获取头像昵称可认证服务号未关注用户
		    if($setting['stonefish_bigwheel_oauth']==0){
				if(!isset($_COOKIE["user_oauth2_wuopenid"]) && $_W['account']['level']!=4){
				   	//设置cookie信息
			    	setcookie("user_oauth2_wuopenid", time(), time()+3600*24*$setting['stonefish_oauth_time']);
			   	}				
			    $appUrl=$this->createMobileUrl($entrytype, array('rid' => $rid,'fromuser' => $_GPC['from_user']),true);
			   	$appUrl=substr($appUrl,2);
			   	$url = $_W['siteroot'] ."app/".$appUrl;
			    header("location: $url");
		        exit;
			}
		    //不是认证号又没有借用服务号获取头像昵称可认证服务号未关注用户			
		    //不是认证号 借用服务号获取头像昵称
            if ($setting['stonefish_bigwheel_oauth']==1 && !empty($_W['oauth_account']['key']) && !empty($_W['oauth_account']['secret'])) { // 判断是否是借用设置
                $appid = $_W['oauth_account']['key'];
                $secret = $_W['oauth_account']['secret'];
            }
			if ($setting['stonefish_bigwheel_oauth']==2 && !empty($setting['appid']) && ! empty($setting['secret'])) { // 判断是否是借用设置
                $appid = $setting['appid'];
                $secret = $setting['secret'];
            }
		    $appUrl= $this->createMobileUrl('auth2', array('entrytype' => $entrytype,'rid' => $rid,'from_user' => $_GPC['from_user'],'power' => $reply['power']),true);
		    $appUrl = substr($appUrl,2);
            $redirect_uri = $_W['siteroot'] ."app/".$appUrl ;
		    //snsapi_base为只获取OPENID,snsapi_userinfo为获取头像和昵称
			$scope = $reply['power']==1 ? 'snsapi_base' : 'snsapi_userinfo';
            $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($redirect_uri)."&response_type=code&scope=".$scope."&state=1#wechat_redirect";
            header("location: $oauth2_code");
		    exit;
		    //不是认证号 借用服务号获取头像昵称
		    //非认证服务号和认证服务号未关注粉丝
		}
	}
	//进入页
	//帮助页
	public function doMobileShareview() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
		$uid = $_GPC['uid'];
		$uniacid = $_W['uniacid'];       
		$fromuser = authcode(base64_decode($_GPC['fromuser']), 'DECODE');
		$page_fromuser = $_GPC['fromuser'];		
		$acid = $_W['acid'];
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));
		//活动状态
		$this->check_reply($reply);		
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数
		//增加人数，和浏览次数
		pdo_update('stonefish_bigwheel_reply', array('viewnum' => $reply['viewnum'] + 1), array('id' => $reply['id']));
		//增加人数，和浏览次数
		if(!empty($fromuser)) {
			//参与分享人信息
		    $fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and uniacid = :uniacid and from_user= :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $fromuser));
		    if(!empty($fans)){
			    $realname = empty($fans['realname']) ? stripcslashes($fans['nickname']) : $fans['realname'];
				if($fans['status']==0){
				    $this->message_tips($rid,'抱歉，活动中您的朋友可能有作弊行为已被管理员暂停屏蔽！请告之你的朋友〖'.$realname.'〗，Ta将不胜感激！by【'.$_W['account']['name'].'】');
			    }
		    }else{
			    $this->message_tips($rid,'抱歉，您的朋友没有参与本活动！请告之你的朋友，3秒后自动进入活动页！',url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)));
		    }
			$setting = $this->module['config'];
			if(!empty($_COOKIE['stonefish_userinfo'])){
				$firend = iunserializer($_COOKIE["stonefish_userinfo"]);
				if($_COOKIE["stonefish_userinfo_power"]!=$reply['power'] || empty($firend['openid']) || !empty($firend['access_token'])){
					setcookie("stonefish_userinfo", '', time()-7200);
				    setcookie("stonefish_userinfo_power", '', time()-7200);
					$appUrl=$this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'shareview','from_user' => $page_fromuser),true);
				    $appUrl=substr($appUrl,2);
				    $url = $_W['siteroot'] ."app/".$appUrl;
				    header("location: $url");
				    exit;
				}
			}elseif($setting['stonefish_bigwheel_oauth']>=1 || $_W['account']['level']==4){
				$appUrl=$this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'shareview','from_user' => $page_fromuser),true);
				$appUrl=substr($appUrl,2);
				$url = $_W['siteroot'] ."app/".$appUrl;
				header("location: $url");
				exit;
			}else{
				$firend = array('headimgurl' => MODULE_URL.'template/images/avatar.jpg','nickname' => '匿名');
			}
		}
		if($from_user!=$fromuser){
			//是否开启互助模式
			if($reply['helptype'] == 1){
				$helpcount = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user", array(':rid' => $rid,":fromuser" => $from_user,":from_user" => $fromuser));
				if($helpcount){
					header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index'))."");
                    exit();
				}
			}
			//是否开启互助模式
			$firendhelpcount = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user));
			$firendfelp = pdo_fetch("select id,viewnum FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user order by visitorstime desc", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user));
			if ($reply['limittype'] == 1) { // 每天限制                
                $dayfirendhelp = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user and visitorstime >= :starttime", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user,":starttime" => strtotime(date('Y-m-d'))));
            }
            if ($reply['limittype'] == 0 && $firendhelpcount) { // 限制一次
                $res["msg"] = "亲每人只能一次机会哟!";
                $res["code"] = 502;
            }
            if ($reply['limittype'] == 1 && $firendhelpcount>=$reply['totallimit']) {//限制最多多少次
                $res["msg"] = "亲你的助力次数已用完!";
                $res["code"] = 503;
            }
			if ($reply['limittype'] == 1 && $dayfirendhelp) {//限制最多多少次
                $res["msg"] = "今天助力次数已用完，明天再来吧!";
                $res["code"] = 504;
            }
            if ($reply['powertype']==0 && $res["code"] == '') {
                $score = 0;
                $addp = $reply['addp'];
                $op = $this->get_rand(array(
                    "+" => $addp,
                    "-" => (100 - $addp)
                ));
				$score = mt_rand($reply['randompointstart']*100,$reply['randompointend']*100)/100;
				$score = sprintf("%.2f",$score);
				if ($op == "+") {
                    $point = + $score;
                } elseif ($op == "-") {
                    $point = - $score;
                }
				$insert = array(
                    'rid' => $rid,
                    'uniacid' => $uniacid,
                    'from_user' => $from_user,
				    'fromuser' => $fromuser,
				    'avatar' => $firend['headimgurl'],
				    'nickname' => $firend['nickname'],
				    'visitorsip'=> CLIENT_IP,
                    'visitorstime' => TIMESTAMP,
					'point' => $point,
				    'viewnum' => 1
                );
				pdo_insert('stonefish_bigwheel_sharedata', $insert); // 记录助力人
            }else{
				pdo_update('stonefish_bigwheel_sharedata', array('viewnum' => $firendfelp['viewnum'] + 1), array('id' => $firendfelp['id']));
			}
			//记录分享
			//增加分享人分享量
			$sharenum = pdo_fetchcolumn("select count(id) from ".tablename('stonefish_bigwheel_sharedata')." where uniacid= :uniacid and fromuser= :fromuser and rid= :rid", array(':uniacid' => $uniacid,':rid' => $rid,':fromuser' => $fromuser));
			$sharepoint = pdo_fetchcolumn("select sum(point) from ".tablename('stonefish_bigwheel_sharedata')." where uniacid= :uniacid and fromuser= :fromuser and rid= :rid", array(':uniacid' => $uniacid,':rid' => $rid,':fromuser' => $fromuser));
			pdo_update('stonefish_bigwheel_fans', array('sharenum' => $sharenum,'sharepoint' => $sharepoint), array('uniacid' => $uniacid,'from_user' => $fromuser,'rid' => $rid));
			//增加分享人分享量
			if($reply['powertype']==0){
				header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index')) . "");
                exit();
			}
		}else{
			header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index')) . "");
            exit();
		}
		if($this->Weixin()){
			include $this->template('share');
		}else{
			$this->Weixin();
		}
	}
	//帮助页
	//助力
	public function doMobileFirendhelp(){
        global $_W, $_GPC;
		$uniacid = $_W['uniacid'];
        $rid = $_GPC['rid'];
        $from_user = $_GPC['fopenid'];
        $fromuser = $_GPC['fromuser'];       
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		//活动状态
		$this->check_reply($reply);		
		//活动状态
        $res = array();
        if(!empty($reply)){
            $firendhelpcount = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user));
			$firendfelp = pdo_fetch("select id,viewnum FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user order by visitorstime desc", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user));
			//查询是否互助
			if($reply['helptype']){
				$firendtohelp = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user", array(':rid' => $rid,":fromuser" => $from_user,":from_user" => $fromuser));
				if($firendtohelp){
					$res["msg"] = "禁止互助!";
                    $res["code"] = 505;
				}
			}
			//查询是否互助
			if ($reply['limittype'] == 1) { // 每天限制                
                $dayfirendhelp = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid=:rid and fromuser=:fromuser and from_user=:from_user and visitorstime >= :starttime", array(':rid' => $rid,":fromuser" => $fromuser,":from_user" => $from_user,":starttime" => strtotime(date('Y-m-d'))));
            }
            if ($reply['limittype'] == 0 && $firendhelpcount) { // 限制一次
                $res["msg"] = "亲每人只能一次机会哟!";
                $res["code"] = 502;
            }
            if ($reply['limittype'] == 1 && $firendhelpcount>=$reply['totallimit']) {//限制最多多少次
                $res["msg"] = "亲你的助力次数已用完!";
                $res["code"] = 503;
            }
			if ($reply['limittype'] == 1 && $dayfirendhelp >= 1) {//限制最多多少次
                $res["msg"] = "今天助力次数已用完，明天再来吧!";
                $res["code"] = 504;
            }
            if ($res["code"] == '') {
                $score = 0;
                $addp = $reply['addp'];
                $op = $this->get_rand(array(
                    "+" => $addp,
                    "-" => (100 - $addp)
                ));
				$score = mt_rand($reply['randompointstart']*100,$reply['randompointend']*100)/100;
				$score = sprintf("%.2f",$score);
				if ($op == "+") {
                    $point = + $score;
                } elseif ($op == "-") {
                    $point = - $score;
                }
				$insert = array(
                    'rid' => $rid,
                    'uniacid' => $_W['uniacid'],
                    'from_user' => $from_user,
				    'fromuser' => $fromuser,
				    'avatar' => $_GPC['fheadimgurl'],
				    'nickname' => $_GPC['fnickname'],
				    'visitorsip'=> CLIENT_IP,
                    'visitorstime' => TIMESTAMP,
					'point' => $point,
				    'viewnum' => 1
                );
				pdo_insert('stonefish_bigwheel_sharedata', $insert); // 记录助力人
                $res["msg"] = "助力成功!";
				$res["code"] = 200;
            }else{
				pdo_update('stonefish_bigwheel_sharedata', array('viewnum' => $firendfelp['viewnum'] + 1), array('id' => $firendfelp['id']));
			}
			//记录分享
			//增加分享人分享量
			$sharenum = pdo_fetchcolumn("select count(id) from ".tablename('stonefish_bigwheel_sharedata')." where uniacid= :uniacid and fromuser= :fromuser and rid= :rid", array(':uniacid' => $uniacid,':rid' => $rid,':fromuser' => $fromuser));
			$sharepoint = pdo_fetchcolumn("select sum(point) from ".tablename('stonefish_bigwheel_sharedata')." where uniacid= :uniacid and fromuser= :fromuser and rid= :rid", array(':uniacid' => $uniacid,':rid' => $rid,':fromuser' => $fromuser));
			pdo_update('stonefish_bigwheel_fans', array('sharenum' => $sharenum,'sharepoint' => $sharepoint), array('uniacid' => $uniacid,'from_user' => $fromuser,'rid' => $rid));
			//增加分享人分享量
        }
        echo json_encode($res);
    }
	//助力
	//活动首页
	public function doMobileindex() {
        global $_GPC, $_W;
		$rid = intval($_GPC['rid']);        
		$uid = intval($_GPC['uid']);
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];
        if (empty($rid)) {
            $this->message_tips($rid,'抱歉，参数错误！');
        }		
        $reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));
        //活动状态
		$this->check_reply($reply);
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数		
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
        //获得关键词
        $reply['keyword']=  $this->rule_keyword($rid);
        //获得关键词		
		//获取openid以及头像昵称
		if(empty($from_user)) {
		    //没有获取openid跳转至引导页
            if (!empty($share['share_url'])) {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $share['share_url'] . "");
                exit();
            }else{
				$this->message_tips($rid,'请关注公众号再参与活动');
			}
			//没有获取openid跳转至引导页			           
		}else{
			//查询是否为关注用户并查询是否需要关注粉丝参与活动否则跳转至引导页
			if($reply['issubscribe']>=1 && intval($_W['fans']['follow'])!=1){
			    //没有关注粉丝跳转至引导页
				if (!empty($share['share_url'])) {
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $share['share_url'] . "");
                    exit();
                }else{
				    $this->message_tips($rid,'请关注公众号再参与活动');
			    }
				//没有关注粉丝跳转至引导页
			}
			//查询是否为关注用户并查询是否需要关注粉丝参与活动否则跳转至引导页
			//验证是否为会员
			if($reply['issubscribe']>=2){
				$members = pdo_fetch("select `status`,`groupid`,`districtid` FROM ".tablename('stonefish_member')." where `uniacid`=:uniacid AND `uid` = :uid",array(':uniacid' => $_W['uniacid'],':uid' => $_W['member']['uid']));
				$profile = mc_fetch($_W['member']['uid'], array('mobile'));
				if(!empty($members) && $members['status']==0) {
				    $this->message_tips($rid,'会员已被锁定，请联系管理员');
				}
				if($reply['issubscribe']==3 && empty($profile['mobile'])) {
				    $this->message_tips($rid,'请先验证成为会员才能参与活动！',url('entry//member',array('m'=>'stonefish_member','fstatus'=>'mobile','url'=>url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)))));
				}
				if($reply['issubscribe']==4 && empty($members['groupid'])) {
				    $this->message_tips($rid,'请先验证成为会员才能参与活动！',url('entry//member',array('m'=>'stonefish_member','fstatus'=>'groupid','url'=>url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)))));
				}
				if($reply['issubscribe']==5 && empty($members['districtid'])) {
				    $this->message_tips($rid,'请先验证成为会员才能参与活动！',url('entry//member',array('m'=>'stonefish_member','fstatus'=>'districtid','url'=>url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)))));
				}
				if(empty($members)) {
				    $this->message_tips($rid,'请先验证成为会员才能参与活动！',url('entry//member',array('m'=>'stonefish_member','url'=>url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)))));
				}
			}
			//验证是否为会员 
			$setting = $this->module['config'];
			if(!empty($_COOKIE['stonefish_userinfo'])){
				$firend = iunserializer($_COOKIE["stonefish_userinfo"]);
				if($_COOKIE["stonefish_userinfo_power"]!=$reply['power'] || empty($firend['openid']) || !empty($firend['access_token'])){
					setcookie("stonefish_userinfo", '', time()-7200);
				    setcookie("stonefish_userinfo_power", '', time()-7200);
					$appUrl=$this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index'),true);
				    $appUrl=substr($appUrl,2);
				    $url = $_W['siteroot'] ."app/".$appUrl;
				    header("location: $url");
				    exit;
				}
			}elseif($setting['stonefish_bigwheel_oauth']>=1 || $_W['account']['level']==4){
			    $appUrl=$this->createMobileUrl('entry', array('rid' => $rid,'entrytype' => 'index'),true);
				$appUrl=substr($appUrl,2);
				$url = $_W['siteroot'] ."app/".$appUrl;
				header("location: $url");
				exit;
			}else{
				$firend = array('headimgurl' => MODULE_URL.'template/images/avatar.jpg','nickname' => '匿名');
			}
		}
		//获取openid以及头像昵称
        //获得用户资料
		if($_W['member']['uid']){
			$profile = mc_fetch($_W['member']['uid'], array('avatar','nickname','realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position'));
		}
		//获得用户资料
		//查询是否参与活动并更新头像和昵称
		$fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and uniacid = :uniacid and from_user= :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
		if(!empty($fans)){
			if($fans['status']==0){
				$real_name = empty($fans['realname']) ? stripcslashes($fans['nickname']) : $fans['realname'];
				$this->message_tips($rid,'抱歉，活动中您〖'.$real_name.'〗可能有作弊行为已被管理员暂停屏蔽！请联系【'.$_W['account']['name'].'】管理员');
			}
			//更新分享量
			$fans['sharenum'] = pdo_fetchcolumn("select count(id) FROM ".tablename('stonefish_bigwheel_sharedata')." where uniacid = :uniacid and rid = :rid and fromuser = :from_user", array(':uniacid' => $uniacid,':rid' => $rid,':from_user' => $from_user));
			pdo_update('stonefish_bigwheel_fans', array('sharenum' => $fans['sharenum']), array('id' => $fans['id']));
			//更新分享量
			//更新头像和昵称
			if($reply['power']==2 && ($setting['stonefish_bigwheel_oauth']>=1 || $_W['account']['level']==4)){
				pdo_update('stonefish_bigwheel_fans', array('avatar' => $firend['headimgurl'], 'nickname' => $firend['nickname']), array('id' => $fans['id']));
			}
			//更新头像和昵称
			//查询是否需要弹出填写兑奖资料
			if($fans['awardnum'] || $exchange['before']==1){
				//自动读取会员信息存入FANS表中
				$ziduan = array('realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position');
				foreach ($ziduan as $ziduans) {
					if($exchange['is'.$ziduans]){
			            if(!empty($profile[$ziduans]) && empty($fans[$ziduans])){
				            pdo_update('stonefish_bigwheel_fans', array($ziduans => $profile[$ziduans]), array('id' => $fans['id']));
				        }else{
					        if(empty($fans[$ziduans])){
						        $$ziduans = true;
						    }
					    }
			        }
				}
				if($realname || $mobile || $qq || $email || $address || $gender || $telephone || $idcard || $company || $occupation || $position){
			       $isfans = true;
			    }
				//自动读取会员信息存入FANS表中
			}
			//查询是否需要弹出填写兑奖资料
		}else{
			if($exchange['before']==1){
			    $isfans = true;
			}
		}
		//查询是否参与活动并更新头像和昵称
		//增加人数，和浏览次数
        pdo_update('stonefish_bigwheel_reply', array('viewnum' => $reply['viewnum'] + 1), array('id' => $reply['id']));
		//增加人数，和浏览次数
		//查询是活动定义的次数还是商户赠送次数
		if($reply['opportunity']==3){
            //其他模块
		    $doings = pdo_fetch("select * from " . tablename($reply['othermodule'].'_prizeslist') . " where urlrid = '" . $rid . "' and prizetype='stonefish_bigwheel' and uniacid='".$uniacid."' and from_user='".$from_user."'");
			if(!empty($doings)){
				if($doings['usecount'] >= $doings['prizesnum']){
					$this->message_tips($rid,'抱歉，您的抽奖次数已用完了或没有获得抽奖次数', url('entry//myaward',array('m'=>'stonefish_bigwheel','rid'=>$rid)));
				}else{
					$reply['number_times'] = $doings['prizesnum']-$doings['usecount'];
				}
			}else{
				$this->message_tips($rid,'抱歉，您还未获得抽奖资格!');
			}
		}elseif($reply['opportunity']==1){
            //商家赠送机会
			if(empty($profile['mobile'])){
				$this->message_tips($rid,'还没有注册成为会员，无法进入抽奖', url('entry//member',array('m'=>'stonefish_member','url'=>url('entry//index',array('m'=>'stonefish_bigwheel','rid'=>$rid)))));
			}
		    $doings = pdo_fetch("select awardcount,districtid,status from " . tablename('stonefish_branch_doings') . " where rid = " . $rid . " and mobile='" . $profile['mobile'] . "' and uniacid='".$uniacid."'");
			if(!empty($doings)){
				if ($doings['status']<2) {
					$this->message_tips($rid,'抱歉，您的抽奖资格正在审核中');
                }else{
					if($doings['awardcount'] == 0){
					    $this->message_tips($rid,'抱歉，您的抽奖次数已用完了或没有获得抽奖次数');
					}else{
						$reply['number_times'] = $doings['awardcount'];
					}						
				}
				//查询网点资料
				$business = pdo_fetch("select * from " . tablename('stonefish_branch_business') . " where id=" . $doings['districtid'] . "");
				//更新网点记录到会员中心表
				pdo_update('stonefish_members', array('districtid' => $doings['districtid']), array('uid' => $_W['member']['uid']));
			}else{
				$this->message_tips($rid,'抱歉，您的还未获得抽奖资格');
			}
		}elseif($reply['opportunity']==2){
			$creditnames = array();
		    $unisettings = uni_setting($uniacid, array('creditnames'));
		    foreach ($unisettings['creditnames'] as $key=>$credit) {
		        if ($reply['credit_type']==$key) {
			        $creditnames = $credit['title'];
				    break;
			    }
		    }
			//积分购买机会
			$credit = mc_credit_fetch($_W['member']['uid'], array($reply['credit_type']));
			$credit_times = intval($credit[$reply['credit_type']]/$reply['credit_times']);
			if($credit_times<1){
				$msg = '没有足够的!'.$creditnames.'兑换次数了！';
			}
			if($reply['number_times']){
				if($reply['number_times']>=$credit_times){
					$reply['number_times'] = $credit_times;
				}
			}else{
				$reply['number_times'] = $credit_times;
			}			
		}
		//查询分享赠送次数
		$Scount = 0;
		if($share['sharenum']>0){
			switch ($share['sharetimes']){
				case 1://赠送当天
				    if($share['sharetype']){//分享成功打开链接后
					    $sharenum = pdo_fetchcolumn("select count(id) as dd from ".tablename('stonefish_bigwheel_sharedata')." where uniacid='".$uniacid."' and rid= '".$rid."' and fromuser='".$from_user."'");
				        if($share['sharenumtype']==2&&$reply['day_number_times']>0&&$sharenum>0){
					        $reply['day_number_times'] = $reply['day_number_times']+ ceil($share['sharenum']);
							$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
							$Scount = ceil($share['sharenum']);
				        }elseif($reply['day_number_times']>0){
				            $reply['day_number_times'] = $reply['day_number_times']+floor($share['sharenum'] * $sharenum);
							$reply['number_times'] =$reply['number_times']+floor($share['sharenum'] * $sharenum);
							$Scount = floor($share['sharenum'] * $sharenum);
				        }
					}elseif($fans['share_num']>0&&$reply['day_number_times']>0){//分享赠送
						$reply['day_number_times'] =$reply['day_number_times']+ceil($share['sharenum']);
						$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
						$Scount = ceil($share['sharenum']);
					}
				    break;
				case 2://赠送总数
				    if($share['sharetype']){//分享成功打开链接后
					    $sharenum = pdo_fetchcolumn("select count(id) as dd from ".tablename('stonefish_bigwheel_sharedata')." where uniacid='".$uniacid."' and rid= '".$rid."' and fromuser='".$from_user."'");
				        if($share['sharenumtype']==2&&$reply['number_times']>0&&$sharenum>0){
					        $reply['number_times'] = $reply['number_times']+ ceil($share['sharenum']);
							$Scount = ceil($share['sharenum']);
				        }elseif($reply['number_times']>0){
				            $reply['number_times'] = $reply['number_times']+floor($share['sharenum'] * $sharenum);
							$Scount = floor($share['sharenum'] * $sharenum);
				        }
					}elseif($fans['share_num']>0&&$reply['number_times']>0){//分享赠送-$fans['tosharenum']
						$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
						$Scount = ceil($share['sharenum']);
					}
				    break;
			}
		}
		//查询分享赠送次数
		//查询再来一次中奖机会
		$mydayagainprize = pdo_fetchcolumn("select count(a.id) from ".tablename('stonefish_bigwheel_fansaward')." as a,".tablename('stonefish_bigwheel_prize')." as b where a.rid = :rid and a.uniacid = :uniacid and a.zhongjiang>=1 and a.from_user =:from_user and b.prizetype = :prizetype and a.prizeid=b.id and zhongjiangtime>=:time", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizetype' => 'againprize', ':time' => strtotime(date('Y-m-d'))));
		$myagainprize = pdo_fetchcolumn("select count(a.id) from ".tablename('stonefish_bigwheel_fansaward')." as a,".tablename('stonefish_bigwheel_prize')." as b where a.rid = :rid and a.uniacid = :uniacid and a.zhongjiang>=1 and a.from_user =:from_user and b.prizetype = :prizetype and a.prizeid=b.id", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizetype' => 'againprize'));
		$reply['number_times'] = $reply['number_times'] + $myagainprize;
		$reply['day_number_times'] = $reply['day_number_times'] + $mydayagainprize;
		//查询再来一次中奖机会
		//更新当日次数
        $nowtime = strtotime(date('Y-m-d'));
        if ($fans['lasttime'] < $nowtime) {
            $fans['todaynum'] = 0;
        }
		//更新当日次数
		//查询次数并替换变量
		$replystr = pdo_fetch("select number_times,day_number_times from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		if(empty($fans['totalnum'])){
			$fans['totalnum'] = 0;
		}
		if($replystr['number_times']==0){
			$number_times = '无限';
			$Lcount = '无限';
		}else{
			$number_times = $replystr['number_times'];
			$Lcount = $reply['number_times'] - $fans['totalnum'];
		}
		if($replystr['day_number_times']==0){
			if($replystr['number_times']==0){
			    $day_number_times = '无限';
			    $Tcount = '无限';
		    }else{
			    $day_number_times = $number_times;
			    $Tcount = $Lcount;
		    }
		}else{
			$day_number_times = $replystr['day_number_times'];
			$Tcount = $reply['day_number_times'] - $fans['todaynum'];
		}
		if($Tcount != '无限' && $Lcount != '无限'){
			if($Lcount<$Tcount){
			    $Tcount = $Lcount;
		    }			
		}		
		$str = array('#最多次数#'=>$number_times,'#每天次数#'=>$day_number_times,'#参与次数#'=>'<span id="totalcount">'.$fans['totalnum'].'</span>','#今日次数#'=>'<span id="count">'.$fans['todaynum'].'</span>','#剩余次数#'=>'<span id="lcount">'.$Lcount.'</span>','#今日剩余#'=>'<span id="tcount">'.$Tcount.'</span>','#赠送次数#'=>$Scount,'#再来一次#'=>$myagainprize);
		$reply['tips'] = strtr($reply['tips'],$str);
		//查询次数并替换变量
		//判断总次数超过限制,一般情况不会到这里的，考虑特殊情况,回复提示文字msg，便于测试
        if ($fans['totalnum'] >= $reply['number_times'] && $number_times > 0) {
           $msg = $reply['number_times_tips'];
        }
        //判断当日是否超过限制,一般情况不会到这里的，考虑特殊情况,回复提示文字msg，便于测试
        if ($fans['todaynum'] >= $reply['day_number_times'] && $day_number_times > 0) {
            $msg = $reply['day_number_times_tips'];
        }
		if($fans['awardnum']>=$reply['award_num']&&$reply['award_num']!=0){				
			$msg = $reply['award_num_tips'];
		}
		//中奖名单
		if($reply['viewawardnum']){
			$fansaward = pdo_fetchall("select * from ".tablename('stonefish_bigwheel_fansaward')." where rid = :rid and uniacid = :uniacid and zhongjiang>=1 group by from_user,prizeid order by createtime desc limit ".$reply['viewawardnum'], array(':rid' => $rid, ':uniacid' => $uniacid));
			foreach ($fansaward as $fansawards){
				$prizex = pdo_fetchcolumn("select count(id) from ".tablename('stonefish_bigwheel_fansaward')." where rid = :rid and uniacid = :uniacid and zhongjiang>=1 and from_user =:from_user and prizeid = :prizeid", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $fansawards['from_user'], ':prizeid' => $fansawards['prizeid']));
				$prizename = pdo_fetchcolumn("select prizename from " . tablename('stonefish_bigwheel_prize') . " where id='" . $fansawards['prizeid'] . "'");
				$awardx = '';
				if($prizex==1){
					$awardx .= $prizename.'&nbsp;&nbsp;';
				}else{
					$awardx .= $prizename.' X'.$prizex.'&nbsp;&nbsp;';
				}
				$fansinfo = pdo_fetch("select realname,nickname from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and uniacid = :uniacid and from_user =:from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $fansawards['from_user']));
				$zjrealname = empty($fansinfo['realname']) ? stripcslashes($fansinfo['nickname']) : $fansinfo['realname'];
				$zjrealname = empty($zjrealname) ? '匿名' : $zjrealname;
				$award_list .= $zjrealname.'抽中了：'.$awardx.'&nbsp;&nbsp;';
				$zjrealname = '';				
			}
		}
		//中奖名单		
		//兑奖参数重命名
		$isfansname = explode(',',$exchange['isfansname']);
		//兑奖参数重命名
		//九宫格
		if($reply['turntable']){
		    $prize0_3 = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` asc  limit 4", array(':rid' => $rid));
			$prize6_9 = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` desc  limit 2,4", array(':rid' => $rid));
			$prize4 = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` asc  limit 4,1", array(':rid' => $rid));
			$prize5 = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` asc  limit 5,1", array(':rid' => $rid));
			$prize10 = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` asc  limit 10,1", array(':rid' => $rid));
			$prize11 = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . " where rid = :rid order by `id` asc  limit 11,1", array(':rid' => $rid));
		}
		//九宫格
		//查询中奖总数以及可中奖总数 是否还有奖品
		$prizenum = pdo_fetchcolumn("select count(id) from ".tablename('stonefish_bigwheel_fansaward')." where uniacid='".$uniacid."' and rid= '".$rid."' and zhongjiang>=1");
		if($prizenum>=$reply['prize_num']){
		    //已没有奖品可发放了
			$running = false;
            $msg = '所有奖品都发放完了，无法抽奖了，下次早点来哟!';
		}		
		//查询中奖总数以及可中奖总e数 是否还有奖品
        //分享信息
        $sharelink = $_W['siteroot'] .'app/'.substr($this->createMobileUrl('entry', array('rid' => $rid,'from_user' => $page_from_user,'entrytype' => 'shareview')),2);
        $sharetitle = empty($share['share_title']) ? '欢迎参加活动' : $share['share_title'];
        $sharedesc = empty($share['share_desc']) ? '亲，欢迎参加活动，祝您好运哦！！' : str_replace("\r\n"," ", $share['share_desc']);
		$sharetitle = $this->get_share($rid,$from_user,$sharetitle);
		$sharedesc = $this->get_share($rid,$from_user,$sharedesc);
		if(!empty($share['share_img'])){
		    $shareimg = toimage($share['share_img']);
		}else{
		    $shareimg = toimage($reply['start_picurl']);
		}
		//分享信息
		if($this->Weixin()){
			if(!$reply['turntable']){
                include $this->template('index');
		    }else{
		        include $this->template('squares');
		    }
		}else{
			$this->Weixin();
		}
    }
	//活动首页
	//抽奖
	public function doMobileget_award() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];		
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
        $reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		//$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));
        if ($reply == false) {
            $this->Json_encode(array("success"=>2, "msg"=>'规则出错！...'));
        }
        if($reply['isshow'] == 0){
            $this->Json_encode(array("success"=>2, "msg"=>'活动暂停，请稍后...'));
        }
        if ($reply['starttime'] > time()) {
            $this->Json_encode(array("success"=>2, "msg"=>'活动还没有开始呢，请等待...'));
        }
        if ($reply['endtime'] < time()) {
            $this->Json_encode(array("success"=>2, "msg"=>'活动已经结束了，下次再来吧！'));
        }
		if (empty($from_user)) {
			$this->Json_encode(array("success"=>2, "msg"=>'粉丝数据出错！'));
		}else{
			if($reply['issubscribe']>=1 && intval($_W['fans']['follow'])!=1){
				$this->Json_encode(array("success"=>2, "msg"=>'请先关注公众号【'.$_W['account']['name'].'】再来参与活动！'));
			}
			if($reply['issubscribe']>=2){
				$members = pdo_fetch("select `status`,`groupid`,`districtid` FROM ".tablename('stonefish_member')." where `uniacid`=:uniacid AND `uid` = :uid",array(':uniacid' => $_W['uniacid'],':uid' => $_W['member']['uid']));
				$profile = mc_fetch($_W['member']['uid'], array('mobile'));
				if(!empty($members) && $members['status']==0) {
				    $this->message_tips($rid,'会员已被锁定，请联系管理员');
				}
				if($reply['issubscribe']==3 && empty($profile['mobile'])) {
				    $this->Json_encode(array("success"=>2, "msg"=>'请先验证成为会员才能参与活动！'));
				}
				if($reply['issubscribe']==4 && empty($members['groupid'])) {
				    $this->Json_encode(array("success"=>2, "msg"=>'请先验证成为会员才能参与活动！'));
				}
				if($reply['issubscribe']==5 && empty($members['districtid'])) {
				    $this->Json_encode(array("success"=>2, "msg"=>'请先验证成为会员才能参与活动！'));
				}
				if(empty($members)) {
				    $this->Json_encode(array("success"=>2, "msg"=>'请先验证成为会员才能参与活动！'));
				}
			}			
		}
        //是否已关联用户，如果中奖一次，判断是否已中奖
        $fans = pdo_fetch("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and from_user = :from_user", array(':rid' => $rid, ':from_user' => $from_user));
        if($fans == false){
             $fans = array(
                'rid' => $rid,
				'uniacid' => $uniacid,
                'from_user' => $from_user,
				'avatar' => $_GPC['avatar'],
				'nickname' => $_GPC['nickname'],
                'todaynum' => 0,
                'totalnum' => 0,
                'awardnum' => 0,
				'tickettype' => $exchange['tickettype'],
                'createtime' => time(),
            );
            pdo_insert('stonefish_bigwheel_fans', $fans);
            $fans['id'] = pdo_insertid();
			//发送消息模板之参与模板
			if($exchange['tmplmsg_participate']){
				$this->seed_tmplmsg($from_user,$exchange['tmplmsg_participate'],$rid,array('do' =>'index', 'nickname' =>$_GPC['nickname']));
			}
			//发送消息模板之参与模板
			//增加人数，和浏览次数
            pdo_update('stonefish_bigwheel_reply', array('fansnum' => $reply['fansnum'] + 1), array('id' => $reply['id']));
        }else{
			if($fans['awardnum']>=$reply['award_num']&&$reply['award_num']!=0){				
				$this->Json_encode(array("success"=>2, "msg"=>'您已中过大奖了，本活动仅限中奖'.$reply['award_num'].'次，谢谢！'));
			}
			if($fans['status']==0){
				$this->Json_encode(array("success"=>2, "msg"=>'会员已被锁定，请联系管理员'));
			}
			//增加浏览次数            
		}
		//是否已关联用户，如果中奖一次，判断是否已中奖
		//获得用户资料
		if($_W['member']['uid']){
			$profile = mc_fetch($_W['member']['uid'], array('avatar','nickname','realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position'));
		}
		//获得用户资料		
		//查询是活动定义的次数还是商户赠送次数
		if($reply['opportunity']==3){
			//其他模块
		    $doings = pdo_fetch("select * from " . tablename($reply['othermodule'].'_prizeslist') . " where urlrid = " . $rid . " and prizetype='stonefish_bigwheel' and uniacid='".$uniacid."' and from_user='".$from_user."'");
			if(!empty($doings)){
				if($doings['usecount'] >= $doings['prizesnum']){
					$this->Json_encode(array("success"=>2, "msg"=>'抱歉，您的抽奖次数已用完了或没有获得抽奖次数'));
				}else{
					$reply['number_times'] = $doings['prizesnum']-$doings['usecount'];
				}
			}else{
				$this->Json_encode(array("success"=>2, "msg"=>'抱歉，您的还未获得抽奖资格'));
			}
		}elseif($reply['opportunity']==1){
			if(empty($profile['mobile'])){
				$this->Json_encode(array("success"=>2, "msg"=>'您没有注册成为会员，不能参与!'));
			}
			$doings = pdo_fetch("select * from " . tablename('stonefish_branch_doings') . " where rid = " . $rid . " and mobile='" . $profile['mobile'] . "' and uniacid='".$uniacid."'");
			if(!empty($doings)){
			    if ($doings['status']<2) {
                    $this->Json_encode(array("success"=>2, "msg"=>'抱歉，您的参与资格正在审核中!'));
                 }else{
				    if ($doings['awardcount'] == 0) {
				        $this->Json_encode(array("success"=>2, "msg"=>'抱歉，您的参与次数已用完了或没有获得参与次数!'));
                    }else{
					    $reply['number_times'] = $doings['awardcount'];
				    }
			    }
			}else{
				$this->Json_encode(array("success"=>2, "msg"=>'抱歉，您还没有获取参与资格，不能抽奖!'));
			}
		}elseif($reply['opportunity']==2){			
		    $unisettings = uni_setting($uniacid, array('creditnames'));
		    foreach ($unisettings['creditnames'] as $key=>$credits) {
		    	if ($reply['credit_type']==$key) {
			    	$creditnames = $credits['title'];
					break;
			    }
		    }
		    $credit = mc_credit_fetch($_W['member']['uid'], array($reply['credit_type']));
			$credit_value = intval($credit[$reply['credit_type']]/$reply['credit_value']);
			if($credit_value<1){
			    $this->Json_encode(array("success"=>2, "msg"=>'抱歉，您没有'.$creditnames.'兑换参与次数了，不能再参与!'));
			}
			if($reply['number_times']){
				if($reply['number_times']>=$credit_value){
					$reply['number_times'] = $credit_value;
				}
			}else{
				$reply['number_times'] = $credit_value;
			}
			$reply['number_times'] += $fans['totalnum'];
		}
        //查询是活动定义的次数还是商户赠送次数
		//查询分享赠送次数
		$Scount = 0;
		if($share['sharenum']>0){
			switch ($share['sharetimes']){
				case 1://赠送当天
				    if($share['sharetype']){//分享成功打开链接后
					    $sharenum = pdo_fetchcolumn("select count(id) as dd from ".tablename('stonefish_bigwheel_sharedata')." where uniacid='".$uniacid."' and rid= '".$rid."' and fromuser='".$from_user."'");
				        if($share['sharenumtype']==2&&$reply['day_number_times']>0&&$sharenum>0){
					        $reply['day_number_times'] = $reply['day_number_times']+ ceil($share['sharenum']);
							$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
				        }elseif($reply['day_number_times']>0){
				            $reply['day_number_times'] = $reply['day_number_times']+floor($share['sharenum'] * $sharenum);
							$reply['number_times'] =$reply['number_times']+floor($share['sharenum'] * $sharenum);
				        }
					}elseif($fans['share_num']>0&&$reply['day_number_times']>0){//分享赠送
						$reply['day_number_times'] =$reply['day_number_times']+ceil($share['sharenum']);
						$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
					}
				    break;
				case 2://赠送总数
				    if($share['sharetype']){//分享成功打开链接后
					    $sharenum = pdo_fetchcolumn("select count(id) as dd from ".tablename('stonefish_bigwheel_sharedata')." where uniacid='".$uniacid."' and rid= '".$rid."' and fromuser='".$from_user."'");
				        if($share['sharenumtype']==2&&$reply['number_times']>0&&$sharenum>0){
					        $reply['number_times'] = $reply['number_times']+ ceil($share['sharenum']);
				        }elseif($reply['number_times']>0){
				            $reply['number_times'] = $reply['number_times']+floor($share['sharenum'] * $sharenum);
				        }
					}elseif($fans['share_num']>0&&$reply['number_times']>0){//分享赠送-$fans['tosharenum']
						$reply['number_times'] =$reply['number_times']+ceil($share['sharenum']);
					}
				    break;
			}
		}
		//查询分享赠送次数
		//查询再来一次中奖机会
		$mydayagainprize = pdo_fetchcolumn("select count(a.id) from ".tablename('stonefish_bigwheel_fansaward')." as a,".tablename('stonefish_bigwheel_prize')." as b where a.rid = :rid and a.uniacid = :uniacid and a.zhongjiang>=1 and a.from_user =:from_user and b.prizetype = :prizetype and a.prizeid=b.id and zhongjiangtime>=:time", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizetype' => 'againprize', ':time' => strtotime(date('Y-m-d'))));
		$myagainprize = pdo_fetchcolumn("select count(a.id) from ".tablename('stonefish_bigwheel_fansaward')." as a,".tablename('stonefish_bigwheel_prize')." as b where a.rid = :rid and a.uniacid = :uniacid and a.zhongjiang>=1 and a.from_user =:from_user and b.prizetype = :prizetype and a.prizeid=b.id", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizetype' => 'againprize'));
		$reply['number_times'] = $reply['number_times'] + $myagainprize;
		$reply['day_number_times'] = $reply['day_number_times'] + $mydayagainprize;
		//查询再来一次中奖机会
        //更新当日次数
        $nowtime = strtotime(date('Y-m-d'));
        if ($fans['lasttime'] < $nowtime) {
            $fans['todaynum'] = 0;
        }
		$replystr = pdo_fetch("select number_times,day_number_times from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
        //判断总次数超过限制,一般情况不会到这里的，考虑特殊情况,回复提示文字msg，便于测试
        if ($fans['totalnum'] >= $reply['number_times'] && $replystr['number_times'] > 0) {
           $this->Json_encode(array("success"=>2, "msg"=>'您超过参与总次数了，不能再参与了!'));
        }
        //判断当日是否超过限制,一般情况不会到这里的，考虑特殊情况,回复提示文字msg，便于测试
        if ($fans['todaynum'] >= $reply['day_number_times'] && $replystr['day_number_times'] > 0) {
            $this->Json_encode(array("success"=>2, "msg"=>'您超过当日参与次数了，不能再参与了!'));
        }
        //所有奖品
		$gift = pdo_fetchall("select * from ".tablename('stonefish_bigwheel_prize')." where rid = :rid and uniacid=:uniacid order by Rand()", array(':rid' => $rid,':uniacid' => $uniacid));
		//计算礼物中的最小概率
		$rate = 1;
		foreach ($gift as $giftxiao) {
			if ($giftxiao['probalilty']< 1&&$giftxiao['probalilty']> 0 && $giftxiao['prizetotal']-$giftxiao['prizedraw']>=1) {
                $temp = explode('.', $giftxiao['probalilty']);
                $temp = pow(10, strlen($temp[1]));
                $rate = $temp < $rate ? $rate : $temp;
            }
		}
		//计算礼物中的最小概率
        $prize_arr = array();
		foreach ($gift as $row) {
			$prize_ren = 0;
			$prize_day = 0;
			$prizeday = 0;
			$prizeren = 0;
			//查询此奖品今日中奖数量
			$prizeday = pdo_fetchcolumn("select count(id) FROM ".tablename('stonefish_bigwheel_fansaward') ." where rid = :rid and uniacid=:uniacid and prizeid = :prizeid and createtime >= :daytime",array(":rid"=>$rid,":uniacid"=>$uniacid,":prizeid"=>$row['id'],":daytime"=>$nowtime));
			if($row['prizeday']==0 || $row['prizeday']>$prizeday){
				$prize_day = 1;
			}
			//查询此奖品今日中奖数量
			//查询此奖品每人中奖数量
			$prizeren = pdo_fetchcolumn("select count(id) FROM ".tablename('stonefish_bigwheel_fansaward') ." where from_user=:from_user and rid = :rid and uniacid=:uniacid and prizeid = :prizeid",array(":from_user"=>$from_user,":rid"=>$rid,":uniacid"=>$uniacid,":prizeid"=>$row['id']));
			if($row['prizeren']==0 || $row['prizeren']>$prizeren){
				$prize_ren = 1;
			}
			//查询此奖品每人中奖数量
			if($row['prizetotal']-$row['prizedraw']>=1 && floatval($row['probalilty'])>0 && $prize_day==1 && $prize_ren==1){
			    $item = array(
			    	'id'      => $row['id'],
				    'prize'   => $row['prizetype'],
				    'v'       => $row['probalilty'] * $rate,
			    );
				$prize_arr[] = $item;
				$isgift = true;
			}
			$probalilty += $row['probalilty'];
		}
		//所有奖品
		if($isgift && ($fans['awardnum']<$reply['award_num'] || $reply['award_num']==0)){
            //参与次数
            pdo_update('stonefish_bigwheel_fans', array('totalnum' => $fans['totalnum'] + 1,'todaynum' => $fans['todaynum'] + 1,'lasttime'=>$nowtime), array('id' => $fans['id']));
			//查询是否为分享赠送次数
			$sharereply = pdo_fetch("select number_times,day_number_times from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid", array(':rid' => $rid));
			if(($sharereply['day_number_times']>0 && $sharereply['day_number_times']-$fans['todaynum']<=0) || ($sharereply['number_times']>0 && $sharereply['number_times']-$fans['totalnum']<=0)){
				pdo_update('stonefish_bigwheel_fans', array('tosharenum' => $fans['tosharenum'] + 1), array('id' => $fans['id']));
			}
			//查询是否为分享赠送次数
			//商家赠送增加使用次数
		    if($reply['opportunity']==3){
			    pdo_update($reply['othermodule'].'_prizeslist', array('usecount' => $doings['usecount']+1), array('id' => $doings['id']));
		    }elseif($reply['opportunity']==1){
			    pdo_update('stonefish_branch_doings', array('usecount' => $doings['usecount']+1,'usetime' => time()), array('id' => $doings['id']));
		    }elseif($reply['opportunity']==2){
			    mc_credit_update($_W['member']['uid'], $reply['credit_type'], -$reply['credit_value'], array($_W['member']['uid'], '兑换活动消耗：'.$reply['credit_value'].'个'.$creditnames));
			    $credit_now = $credit[$reply['credit_type']]-$reply['credit_value'];
		    }
			//增加未中奖奖项
			if(100-$probalilty>0 && $reply['turntable']==0){
				$item = array(
			    	'id'      => 0,
				    'prize'   => '好可惜！没有中奖',
				    'v'       => (100-$probalilty)*$rate,
			    );
			    $prize_arr[] = $item;
			}
			//开始抽奖咯
		    foreach ($prize_arr as $key => $val) {
   		        $arr[$val['id']] = $val['v'];
		    }
		    $prizeid = $this->get_rand($arr); //根据概率获取奖项id
            if($prizeid>0){
                $codesn = date("YmdHis").mt_rand(1000,9999);
				$status = 1;
				$consumetime = '';
				$awardinfo = pdo_fetch("select * from ".tablename('stonefish_bigwheel_prize')." where id = :id", array(':id' =>$prizeid));
				//查询奖品名称和类型
				$k = pdo_fetchcolumn("select count(id) from ".tablename('stonefish_bigwheel_prize')." where uniacid='".$uniacid."' and rid= '".$rid."' and id<='".$prizeid."' order by `id` asc");
				if(!empty($awardinfo['prizepic'])){
				    $awardinfo['prizepic'] = toimage($awardinfo['prizepic']);
				}
				//减少库存
				if($exchange['inventory']==1){
					pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $awardinfo['prizedraw'] + 1), array('id' => $prizeid));
				}
				//减少库存
				if($awardinfo['prizetype']!='physical' && $awardinfo['prizetype']!='virtual' && $awardinfo['prizetype']!='spaceprize' && $awardinfo['prizetype']!='againprize'){
					$unisetting_s = uni_setting($uniacid, array('creditnames'));
		            foreach ($unisetting_s['creditnames'] as $key=>$credit) {
		    	        if ($awardinfo['prizetype']==$key) {
			    	        $credit_names = $credit['title'];
					        break;
			            }
		            }			        
				    if($reply['credit_type']==$awardinfo['prizetype']){
					    $credit_now += $awardinfo['prizevalue'];
				    }
				    $status = 2;
				    $consumetime = time();
					$fans['tickettype'] = 1;
					$fans['ticketid'] = 0;
					$fans['ticketname'] = '系统';
				    //减少库存
				    if($exchange['inventory']==2){
					    pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $awardinfo['prizedraw'] + 1), array('id' => $prizeid));
				    }
				    //减少库存
					//添加积分到粉丝数据库
			        mc_credit_update($_W['member']['uid'], $awardinfo['prizetype'], $awardinfo['prizevalue'], array($_W['member']['uid'], '抽中'.$awardinfo['prizevalue'].'个'.$credit_names));
			        //添加积分到粉丝数据库
				}
				if($awardinfo['prizetype']=='againprize'){
				    //参与次数减少一次
                    pdo_update('stonefish_bigwheel_fans', array('totalnum' => $fans['totalnum'],'todaynum' => $fans['todaynum'],'lasttime'=>$nowtime), array('id' => $fans['id']));
					$status = 2;
				    $consumetime = time();
					$fans['tickettype'] = 1;
					$fans['ticketid'] = 0;
					$fans['ticketname'] = '系统';
				}
				if($awardinfo['prizetype']!='spaceprize'){
					//中奖记录保存
			        $insert = array(
                        'uniacid' => $uniacid,
                        'rid' => $rid,                   
                        'from_user' => $from_user,
                        'prizeid' => $prizeid,
                        'codesn' => $codesn,
                        'createtime' => time(),
					    'zhongjiangtime' => time(),
                        'consumetime' => $consumetime,
                        'zhongjiang' => $status,
					    'tickettype' => $fans['tickettype'],
						'ticketid' => $fans['ticketid'],
						'ticketname' => $fans['ticketname'],
                    );
				    $temp = pdo_insert('stonefish_bigwheel_fansaward', $insert);
				    $fansawardid = pdo_insertid();//取id商家赠送时保存
				    //中奖记录保存
				    //发送消息模板之中奖记录
			        if($exchange['tmplmsg_winning']){
				        $this->seed_tmplmsg($from_user,$exchange['tmplmsg_winning'],$rid,array('do' =>'myaward','nickname' =>$_GPC['nickname'],'prizerating' =>$awardinfo['prizerating'],'prizename' =>$awardinfo['prizename']));
			        }
			        //发送消息模板之中奖记录
                    //保存中奖人信息到fans中
                    pdo_update('stonefish_bigwheel_fans', array('awardnum' => $fans['awardnum'] + 1,'zhongjiang' => 1), array('id' => $fans['id']));
				    //保存中奖人信息到fans中
				    //自动读取会员信息存入FANS表中
				    $ziduan = array('realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position');
				    foreach ($ziduan as $ziduans) {
					    if($exchange['is'.$ziduans]){
			                if(!empty($profile[$ziduans]) && empty($fans[$ziduans])){
				               pdo_update('stonefish_bigwheel_fans', array($ziduans => $profile[$ziduans]), array('id' => $fans['id']));
				            }else{
					            if(empty($fans[$ziduans])){
						            $$ziduans = true;
						        }
					        }
			            }
				    }
				    if($realname || $mobile || $qq || $email || $address || $gender || $telephone || $idcard || $company || $occupation || $position){
			           $isfans = true;
			        }
				    //自动读取会员信息存入FANS表中
				    //商家赠送添加使用记录
				    if($reply['opportunity']==1){
			            $content = '中奖SN:'.$codesn.';'.$awardinfo['prizerating'].'['.$awardinfo['prizename'].']';
				        $insert = array(
                		    'uniacid' => $uniacid,
                		    'rid' => $rid,
                		    'module' => 'stonefish_bigwheel',
                		    'mobile' => $doings['mobile'],
                		    'content' =>$content,
						    'prizeid' =>$fansawardid,
						    'createtime' => time(),
            	        );
				        pdo_insert('stonefish_branch_doingslist', $insert);
				    }
					//商家赠送添加使用记录
				}
				if($status == 2){
					//发送消息模板之兑奖记录
					if($exchange['tmplmsg_exchange']){
				       	$this->seed_tmplmsg($from_user,$exchange['tmplmsg_exchange'],$rid,array('do' =>'myaward', 'prizerating' =>$awardinfo['prizerating'], 'prizename' =>$awardinfo['prizename'], 'prizenum' =>1));
			       	}
					//发送消息模板之兑奖记录
				}
				//中奖信息推送
				$data = array(
                    'award' => $awardinfo,
					'msg' => '中奖了',
                    'success' => 1,
					'prizetype' => $k,
					'isfans' =>$isfans,
					'credit_now' => $credit_now,
                );
				//中奖信息推送
            }else{
				$nojiang = iunserializer($reply['notawardtext']);
				$nojiangid = array_rand($nojiang);
			    $awardname =$nojiang[$nojiangid];
				$data = array(
                    'msg' => $awardname,
                    'success' => 0,
					'isfans' =>0,
					'credit_now' => $credit_now,
                );			    		
			}
        }else{
		    //没有奖品或没有资格的提示
			$noprize = iunserializer($reply['notprizetext']);
			$noprizeid = array_rand($noprize);
			$awardname =$noprize[$noprizeid];
			$data = array(
                'msg' => $awardname,
                'success' => 2,
				'isfans' =>0,
            );
		}
		//查询此用户是否还有中奖记录并更新状态
		$yes = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_fansaward') ." where from_user=:from_user and uniacid=:uniacid and rid=:rid and zhongjiang>=1",array(":from_user"=>$from_user,":uniacid"=>$_W['uniacid'],":rid"=>$rid));
		if(empty($yes)){
			pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => 0), array('rid' => $rid,'uniacid' => $_W['uniacid'],'from_user' => $from_user));
		}else{
			$yes = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_fansaward') ." where from_user=:from_user and uniacid=:uniacid and rid=:rid and zhongjiang>=2",array(":from_user"=>$from_user,":uniacid"=>$_W['uniacid'],":rid"=>$rid));
			if(empty($yes)){
				pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => 1), array('rid' => $rid,'uniacid' => $_W['uniacid'],'from_user' => $from_user));
			}else{
				pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => 2), array('rid' => $rid,'uniacid' => $_W['uniacid'],'from_user' => $from_user));
			}
		}
		//查询此用户是否还有中奖记录并更新状态
		$this->Json_encode($data);
    }
	//抽奖
	//用户注册
	public function doMobileRegfans() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
        $from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		$page_from_user = $_GPC['from_user'];
		$uniacid = $_W['uniacid'];
		//规则判断
        $reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
        if ($reply == false) {
            $this->json_encode(array("success"=>2, "msg"=>'规则出错！...'));
        }
        if($reply['isshow'] != 1){
            $this->json_encode(array("success"=>2, "msg"=>'活动暂停，请稍后...'));
        }
        if ($reply['starttime'] > time()) {
            $this->json_encode(array("success"=>2, "msg"=>'活动还没有开始呢，请等待...'));
        }
        if ($reply['endtime'] < time()) {
            $this->json_encode(array("success"=>2, "msg"=>'活动已经结束了，下次再来吧！'));
        }
        if ($reply['power']==2&&intval($_W['fans']['follow'])!=0) {
            $this->json_encode(array("success"=>2, "msg"=>'请先关注公共账号再来参与活动！详情请查看规则！'));
        }
		//规则判断		
		//查询是活动定义还是商户赠送
		$uid = pdo_fetchcolumn("select uid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$from_user,":uniacid"=>$uniacid));
		$profile = mc_fetch($uid, array('avatar','nickname','realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position'));
		if($reply['opportunity']==3){
			$doings = pdo_fetch("select * from " . tablename($reply['othermodule'].'_prizeslist') . " where urlrid = " . $rid . " and prizetype='stonefish_bigwheel' and uniacid='".$uniacid."' and from_user='".$from_user."'");
			if(!empty($doings)){
				if($doings['usecount'] >= $doings['prizesnum']){
					$this->json_encode(array("success"=>2, "msg"=>'抱歉，您的抽奖次数已用完了或没有获得抽奖次数!'));
				}
			}else{
				$this->json_encode(array("success"=>2, "msg"=>'抱歉，您的还未获得抽奖资格!'));
			}
		}elseif($reply['opportunity']==1){
			if(empty($profile['mobile'])){
				$this->json_encode(array("success"=>2, "msg"=>'您没有注册成为会员，不能参与活动!'));
			}
			$doings = pdo_fetch("select * FROM " . tablename('stonefish_branch_doings') . " where rid = " . $rid . " and mobile='" . $profile['mobile'] . "' and uniacid='".$uniacid."'");
			if(!empty($doings)){
			    if ($doings['status']<2) {
                    $this->json_encode(array("success"=>2, "msg"=>'抱歉，您的资格正在审核中!'));
                 }else{
				    if ($doings['awardcount'] == 0) {
				        $this->json_encode(array("success"=>2, "msg"=>'抱歉，您的资格已用完了!'));
                    }
			    }
			}else{
				$this->json_encode(array("success"=>2, "msg"=>'抱歉，您还没有获取资格，不能参与!'));
			}			
		}elseif($reply['opportunity']==2){
		    $unisettings = uni_setting($uniacid, array('creditnames'));
		    foreach ($unisettings['creditnames'] as $key=>$credits) {
		    	if ($reply['credit_type']==$key) {
			    	$creditnames = $credits['title'];
					break;
			    }
		    }
		    $credit = mc_credit_fetch($uid, array($reply['credit_type']));
			$credit_value = intval($credit[$reply['credit_type']]/$reply['credit_value']);
			if($credit_value<1){
			    $this->json_encode(array("success"=>2, "msg"=>'抱歉，您没有'.$creditnames.'兑换参与资格了!'));
			}						
		}
        //查询是活动定义还是商户赠送
        //判断是否参与过
		$fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and uniacid = :uniacid and from_user= :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
		if(!empty($fans)){
			$this->json_encode(array("success"=>2, "msg"=>'已参与过本活动，请勿重复参与！'));
		}else{
			$fansdata = array(
                'rid' => $rid,
				'uniacid' => $uniacid,
                'from_user' => $from_user,					
				'avatar' => $_GPC['avatar'],
				'nickname' => $_GPC['nickname'],
				'todaynum' => 1,
                'totalnum' => 1,
                'awardnum' => 0,
                'createtime' => time(),
            );
            pdo_insert('stonefish_bigwheel_fans', $fansdata);
            $fans['id'] = pdo_insertid();
			//自动读取会员信息存入FANS表中
			$ziduan = array('realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position');
			foreach ($ziduan as $ziduans){
				if($reply['is'.$ziduans]){
					if(!empty($_GPC[$ziduans])){
				        pdo_update('stonefish_bigwheel_fans', array($ziduans => $_GPC[$ziduans]), array('id' => $fans['id']));
				        if($reply['isfans']){				            
                            if($ziduans=='email'){
								mc_update($_W['member']['uid'], array('email' => $_GPC['email']));
							}else{
								mc_update($_W['member']['uid'], array($ziduans => $_GPC[$ziduans],'email' => $profile['email']));
							}
				        }
					}
			    }
		    }
		    //自动读取会员信息存入FANS表中
			//增加人数，和浏览次数
            pdo_update('stonefish_bigwheel_reply', array('fansnum' => $reply['fansnum'] + 1), array('id' => $reply['id']));
			//商家赠送增加使用次数
		    if($reply['opportunity']==3){
			    //pdo_update('stonefish_branch_doings', array('usecount' =>0,'usetime' => time()), array('id' => $doings['id']));
				pdo_update($reply['othermodule'].'_prizeslist', array('usecount' => 0), array('id' => $doings['id']));
		    }elseif($reply['opportunity']==1){
			    pdo_update('stonefish_branch_doings', array('usecount' =>0,'usetime' => time()), array('id' => $doings['id']));
				$content = '参与活动成功';
				$insert = array(
                	'uniacid' => $uniacid,
                	'rid' => $rid,
                	'module' => 'stonefish_bigwheel',
                	'mobile' => $doings['mobile'],
                	'content' =>$content,
					'prizeid' =>0,
					'createtime' => time()
            	);
				pdo_insert('stonefish_branch_doingslist', $insert);
		    }elseif($reply['opportunity']==2){
			    mc_credit_update($uid, $reply['credit_type'], -$reply['credit_value'], array($uid, '兑换活动资格 消耗：'.$reply['credit_value'].'个'.$creditnames));
			    $credit_now = $credit[$reply['credit_type']]-$reply['credit_value'];
		    }
			$data = array(
                'success' => 1,
				'msg' => '成功参与活动,请邀请好友帮你吧!',
				'credit_now' => $credit_now,
            );
		}
		//判断是否参与过
		$this->json_encode($data);
    }
	//用户注册
	//用户注册资料修改
	public function doMobileEditfans() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
        $from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		$uniacid = $_W['uniacid'];
		$reply = pdo_fetch("select id,fansnum FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
		$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
        //判断是否参与过
		$fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and uniacid = :uniacid and from_user= :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
		$uid = pdo_fetchcolumn("select uid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$from_user,":uniacid"=>$uniacid));
		if(empty($fans)){
			$fansdata = array(
                'rid' => $rid,
				'uniacid' => $uniacid,
                'from_user' => $from_user,					
				'avatar' => $_GPC['avatar'],
				'nickname' => $_GPC['nickname'],
				'todaynum' => 0,
                'totalnum' => 0,
                'awardnum' => 0,
                'createtime' => time(),
            );
            pdo_insert('stonefish_bigwheel_fans', $fansdata);
            $fans['id'] = pdo_insertid();
			//增加人数，和浏览次数
            pdo_update('stonefish_bigwheel_reply', array('fansnum' => $reply['fansnum'] + 1), array('id' => $reply['id']));
		}
		//读取保存提交的资料
		if($fans['id']){
			$ziduan = array('realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position');
			foreach ($ziduan as $ziduans) {
				if($exchange['is'.$ziduans]){
					pdo_update('stonefish_bigwheel_fans', array($ziduans => $_GPC[$ziduans]), array('id' => $fans['id']));
					//是否同步保存FANS表
					if($exchange['isfans'] && $uid){				            
                        if($ziduans=='email'){
							mc_update($uid, array('email' => $_GPC['email']));
						}else{
							mc_update($uid, array($ziduans => $_GPC[$ziduans],'email' => $profile['email']));
						}
				    }
					//是否同步保存FANS表
			    }
			}
		}
		//读取保存提交的资料
		$data = array(
            'success' => 1,
			'msg' => '资料保存成功！',
        );
		//判断是否参与过
		$this->json_encode($data);
    }
	//用户注册资料修改
	//分享成功
	public function doMobileShare_confirm() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
		$uniacid = $_W['uniacid'];
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		$fans = pdo_fetch("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid = :uniacid and from_user = :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
		if ($fans == true) {
			//保存分享次数
			pdo_update('stonefish_bigwheel_fans', array('share_num' => $fans['share_num']+1,'sharetime' => time()), array('id' => $fans['id']));
			$data = array(
                'msg' => '分享次数保存成功！',
                'success' => 1,
            );
		}else{
			$data = array(
                'msg' => '还没有参与活动呀!',
                'success' => 0,
            );
		}
        $this->Json_encode($data);
    }
	//分享成功
	//活动规则
	public function doMobileRule() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);		
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];
        if (empty($rid)) {
            $this->message_tips($rid,'抱歉，参数错误！');
        }		
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));		
        //活动状态
		$this->check_reply($reply);		
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数
		//查询奖品设置
		$prize = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_prize') . " where rid = :rid ORDER BY `break` asc", array(':rid' => $rid));
		if($this->Weixin()){
			include $this->template('rule');
		}else{
			$this->Weixin();
		}
    }
	//活动规则
	//朋友助力
	public function doMobileFirend() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);		
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];
        if (empty($rid)) {
            $this->message_tips($rid,'抱歉，参数错误！');
        }		
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));		
        //活动状态
		$this->check_reply($reply);		
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数	
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
		//助力
		$firend = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_sharedata') . " where rid = :rid and uniacid = :uniacid and fromuser = :from_user order by `id` desc", array(':rid' => $rid,':uniacid' => $uniacid,':from_user' => $from_user));
		foreach ($firend as $mid => $firends) {			    
			if(empty($firends['nickname'])){
				$firend[$mid]['nickname'] = '匿名好友';
			}
			if(empty($firends['avatar'])){
				$firend[$mid]['avatar'] = '../addons/stonefish_bigwheel/template/images/avatar.jpg';
			}else{
				$firend[$mid]['avatar'] = $firends['avatar'];
			}			
		}
		//助力
		//分享信息
        $sharelink = $_W['siteroot'] .'app/'.substr($this->createMobileUrl('entry', array('rid' => $rid,'from_user' => $page_from_user,'entrytype' => 'shareview')),2);
        $sharetitle = empty($share['share_title']) ? '欢迎参加活动' : $share['share_title'];
        $sharedesc = empty($share['share_desc']) ? '亲，欢迎参加活动，祝您好运哦！！' : str_replace("\r\n"," ", $share['share_desc']);
		$sharetitle = $this->get_share($rid,$from_user,$sharetitle);
		$sharedesc = $this->get_share($rid,$from_user,$sharedesc);
		if(!empty($share['share_img'])){
		    $shareimg = toimage($share['share_img']);
		}else{
		    $shareimg = toimage($reply['start_picurl']);
		}
		//分享信息
		if($this->Weixin()){
			include $this->template('firend');
		}else{
			$this->Weixin();
		}
    }
	//朋友助力
	//排行榜
	public function doMobileRank() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);		
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];
        if (empty($rid)) {
            $this->message_tips($rid,'抱歉，参数错误！');
        }
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));		
        //活动状态
		$this->check_reply($reply);		
		//活动状态
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
		//排行榜
		$rankorder = 'awardnum';//awardnum奖品数量sharenum分享量sharepoint分享助力
		$rank = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid = :uniacid order by ".$rankorder." desc,`id` asc limit ".$reply['viewranknum'], array(':rid' => $rid,':uniacid' => $uniacid));		
		//排行榜
		if($this->Weixin()){
			include $this->template('rank');
		}else{
			$this->Weixin();
		}
    }
	//排行榜
	//我的奖品
	public function doMobileMyaward() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);		
		$uniacid = $_W['uniacid'];
		$acid = $_W['acid'];
        if (empty($rid)) {
            $this->message_tips($rid,'抱歉，参数错误！');
        }		
		$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
		$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		$template = pdo_fetch("select * from " . tablename('stonefish_bigwheel_template') . " where id = :id", array(':id' => $reply['templateid']));
		$share = pdo_fetch("select * from " . tablename('stonefish_bigwheel_share') . " where rid = :rid and acid = :acid", array(':rid' => $rid,':acid' => $acid));
        //活动状态
		$this->check_reply($reply);
		$nojiang = iunserializer($reply['notawardtext']);
		$nojiangid = array_rand($nojiang);
		$awardname =$this->get_prizename($rid,$nojiang[$nojiangid]);		
		//活动状态
		//兑奖参数重命名
		$isfansname = explode(',',$exchange['isfansname']);
		//兑奖参数重命名
		//虚拟人数
		$this->xuni_time($reply);
		//虚拟人数
		//获取openid
		$openid = $this->get_openid();
		$from_user = $openid['openid'];
		$page_from_user = base64_encode(authcode($from_user, 'ENCODE'));
		//获取openid
		//我的礼盒奖品
		$fans = pdo_fetch("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid = :uniacid and from_user = :from_user", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
		//查询是否需要弹出填写兑奖资料
		if($fans['awardnum']){
			//自动读取会员信息存入FANS表中
			$uid = pdo_fetchcolumn("select uid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$from_user,":uniacid"=>$uniacid));
			$profile = mc_fetch($uid, array('avatar','nickname','realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position'));
			$ziduan = array('realname','mobile','qq','email','address','gender','telephone','idcard','company','occupation','position');
			foreach ($ziduan as $ziduans) {
				if($exchange['is'.$ziduans]){
			        if(!empty($profile[$ziduans]) && empty($fans[$ziduans])){
				        pdo_update('stonefish_bigwheel_fans', array($ziduans => $profile[$ziduans]), array('id' => $fans['id']));
				    }else{
					    if(empty($fans[$ziduans])){
						    $$ziduans = true;
						}
					}
			    }
			}
			if($realname || $mobile || $qq || $email || $address || $gender || $telephone || $idcard || $company || $occupation || $position){
			    $isfans = true;
			}
			//自动读取会员信息存入FANS表中
		}
		//查询是否需要弹出填写兑奖资料
		$mylihe = pdo_fetchall("select tt.* from(
select * from ".tablename('stonefish_bigwheel_fansaward')." order by zhongjiang asc) as tt  where rid = :rid and uniacid = :uniacid and from_user = :from_user and zhongjiang>=1 GROUP BY prizeid order by `id` desc", array(':rid' => $rid,':uniacid' => $uniacid,':from_user' => $from_user));
		foreach ($mylihe as $mid => $mylihes) {
			$mylihe[$mid]['num'] = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid = :uniacid and from_user = :from_user and zhongjiang=1 and prizeid='".$mylihes['prizeid']."'", array(':rid' => $rid,':uniacid' => $uniacid,':from_user' => $from_user));
			$mylihe[$mid]['numd'] = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid = :uniacid and from_user = :from_user and zhongjiang=2 and prizeid='".$mylihes['prizeid']."'", array(':rid' => $rid,':uniacid' => $uniacid,':from_user' => $from_user));
			$prize = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . " where id='".$mylihes['prizeid']."'");
			$mylihe[$mid]['prizepic'] = $prize['prizepic'];
			$mylihe[$mid]['prizerating'] = $prize['prizerating'];
			$mylihe[$mid]['prizename'] = $prize['prizename'];
			$mylihe[$mid]['prizetype'] = $prize['prizetype'];
			
			if(empty($mylihes['ticketname'])&&!empty($mylihes['ticketid'])){
				if($exchange['tickettype']==2){
				    $mylihe[$mid]['ticketname'] = pdo_fetchcolumn("select name FROM " . tablename('activity_coupon_password') . " where uniacid = :uniacid and id = :id", array(':uniacid' => $_W['uniacid'],':id' => $mylihes['ticketid']));
			    }
			    if($exchange['tickettype']==3){
				    $mylihe[$mid]['ticketname'] = pdo_fetchcolumn("select title FROM " . tablename('stonefish_branch_business') . " where uniacid = :uniacid and id = :id", array(':uniacid' => $_W['uniacid'],':id' => $mylihes['ticketid']));
			    }
			}
			$mylihe[$mid]['ticketid'] = empty($mylihe[$mid]['ticketid']) ? "0" : $mylihe[$mid]['ticketid'];
			$mylihe[$mid]['ticketname'] = empty($mylihe[$mid]['ticketname']) ? "没有选择" : $mylihe[$mid]['ticketname'];
		}
		//我的礼盒奖品
		//店员
		if($exchange['tickettype']==2){
			$shangjia = pdo_fetchall("select name as shangjianame,id FROM " . tablename('activity_coupon_password') . " where uniacid = :uniacid ORDER BY `id` asc", array(':uniacid' => $uniacid));
		}
		//商家网点
		if($exchange['tickettype']==3){
			$shangjia = pdo_fetchall("select title as shangjianame,id FROM " . tablename('stonefish_branch_business') . " where uniacid = :uniacid ORDER BY `id` DESC", array(':uniacid' => $uniacid));
		}
		if($this->Weixin()){
			include $this->template('myaward');
		}else{
			$this->Weixin();
		}
    }
	//我的奖品
	//兑奖商家
	public function doMobileExchange_shangjia() {
        global $_GPC, $_W;
		$uniacid = $_W['uniacid'];
		$rid = intval($_GPC['rid']);
		//获取openid
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		//获取openid
		$shangjiaid = $_GPC['shangjiaid'];
		if(empty($from_user)){
			$data = array(                    
			    'msg' => '系统出错，兑奖人出错，请联系管理员！',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if(!empty($rid)){
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		}else{
			$data = array(                    
			    'msg' => '系统出错，活动规则！请联系管理员',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if(empty($shangjiaid)){
			$data = array(                    
			    'msg' => '请选择商家或门店',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		$fansaward = pdo_fetch("select * from " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid = :uniacid and from_user = :from_user and zhongjiang=1", array(':rid' => $rid,':uniacid' => $uniacid,':from_user' => $from_user));
		if(!empty($fansaward)){
		    if($exchange['tickettype']==2){
				$ticketname = pdo_fetchcolumn("select name FROM " . tablename('activity_coupon_password') . " where uniacid = :uniacid and id = :id", array(':uniacid' => $uniacid,':id' => $shangjiaid));
			}
			if($exchange['tickettype']==3){
				$ticketname = pdo_fetchcolumn("select title FROM " . tablename('stonefish_branch_business') . " where uniacid = :uniacid and id = :id", array(':uniacid' => $uniacid,':id' => $shangjiaid));
			}
			if($_GPC['award_id']=='all'){
			    pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $ticketname), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'zhongjiang' => 1));
			    pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $ticketname), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
			}else{
				pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $ticketname), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'zhongjiang' => 1, 'prizeid' =>$_GPC['award_id']));
			    pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $ticketname), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
			}
			$data = array(                    
			    'msg' => '数据保存成功！请返回兑奖！',
                'success' => 1,
            );
			$this->Json_encode($data);
		}else{
			$data = array(                    
			    'msg' => '穿越了，没有中奖呀，亲！',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
	}
	//兑奖商家
	//兑奖
	public function doMobileExchange() {
        global $_GPC, $_W;
		$uniacid = $_W['uniacid'];
        $awardid = $_GPC['awardid'];
		$rid = intval($_GPC['rid']);
		$shangjiaid = $_GPC['dianmian'];
		$password = $_GPC['mima'];
		//获取openid
		$from_user = authcode(base64_decode($_GPC['from_user']), 'DECODE');
		//获取openid
		if(empty($from_user)){
			$data = array(                    
			    'msg' => '系统出错，兑奖人出错，请联系管理员！',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if(empty($password)){
			$data = array(                    
			    'msg' => '请输入密码',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if(empty($awardid)){
			$data = array(                    
			    'msg' => '奖品ID出错！请联系管理员',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if(!empty($rid)){
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		}else{
			$data = array(                    
			    'msg' => '系统出错，活动规则！请联系管理员',
                'success' => 2,
            );
			$this->Json_encode($data);
		}
		if($exchange['tickettype']==4){
			if($exchange['awardingpas']!=$password){
				$data = array(                    
			        'msg' => '系统出错，兑奖密码或账号不匹配！',
                    'success' => 2,
                );
			    $this->Json_encode($data);
			}else{
				if($awardid=='all'){
					$prizenum = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
					pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'zhongjiang' => 2, 'consumetime' => time()), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'zhongjiang' => 1));
					pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'zhongjiang' => 2), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
					//减少库存
					if($exchange['inventory']==1){
					    $prize = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
						foreach ($prize as $prizes) {
							pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prizes['prizedraw'] + 1), array('id' => $prizes['id']));
						}
				    }
					//减少库存
					//发送消息模板之奖记录
					if($exchange['tmplmsg_exchange']){
				        $this->seed_tmplmsg($from_user,$exchange['tmplmsg_exchange'],$rid,array('do' =>'myaward', 'prizerating' =>'所有', 'prizename' =>'奖品', 'prizenum' =>$prizenum));
			        }
					//发送消息模板之奖记录
				}else{
					$prizenum = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and prizeid =:prizeid and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizeid' => $awardid));
					$prizeid = pdo_fetchcolumn("select prizeid FROM " . tablename('stonefish_bigwheel_fansaward') . " where id='" . $awardid . "'");
					pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'zhongjiang' => 2, 'consumetime' => time()), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'prizeid' => $awardid, 'zhongjiang' => 1));
					pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'zhongjiang' => 2), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
					//减少库存
					if($exchange['inventory']==2){
					    pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw'] + $prizenum), array('id' => $awardid));
				    }
					//减少库存
					//发送消息模板之奖记录
					if($exchange['tmplmsg_exchange']){
				        $this->seed_tmplmsg($from_user,$exchange['tmplmsg_exchange'],$rid,array('do' =>'myaward', 'prizerating' =>$prize['prizerating'], 'prizename' =>$prize['prizename'], 'prizenum' =>$prizenum));
			        }
					//发送消息模板之奖记录
				}
				$data = array(                    
			        'msg' => '恭喜兑奖成功！',
                    'success' => 1,
                );
			    $this->Json_encode($data);
			}
		}else{
			if(empty($shangjiaid)){
			    $data = array(                    
			        'msg' => '请选择店名或商家网点',
                    'success' => 2,
                );
			    $this->Json_encode($data);
		    }
			if($exchange['tickettype']==2){
			    //店员
			    $shangjia = pdo_fetch("select name as shangjianame,id FROM " . tablename('activity_coupon_password') . " where uniacid = :uniacid and id = :id and password = :password", array(':uniacid' => $uniacid,':id' => $shangjiaid,':password' => $password));
			    if(!empty($shangjia)){
				    $duijiangmima = 1;
			    }
		    }elseif($exchange['tickettype']==3){
			    //商家网点
			    $shangjia = pdo_fetch("select title as shangjianame,id FROM " . tablename('stonefish_branch_business') . " where uniacid = :uniacid and id = :id and password = :password", array(':uniacid' => $uniacid,':id' => $shangjiaid,':password' => $password));
			    if(!empty($shangjia)){
				    $duijiangmima = 1;
			    }
		    }
			if($duijiangmima==1){
				if($awardid=='all'){
					$prizenum = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
					pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $shangjia['shangjianame'],'zhongjiang' => 2, 'consumetime' => time()), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'zhongjiang' => 1));
					pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $shangjia['shangjianame'],'zhongjiang' => 2), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
					//减少库存
					if($exchange['inventory']==1){
					    $prize = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user));
						foreach ($prize as $prizes) {
							pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prizes['prizedraw'] + 1), array('id' => $prizes['id']));
						}
				    }
					//减少库存
					//发送消息模板之奖记录
					if($exchange['tmplmsg_exchange']){
				        $this->seed_tmplmsg($from_user,$exchange['tmplmsg_exchange'],$rid,array('do' =>'myaward', 'prizerating' =>'所有', 'prizename' =>'奖品', 'prizenum' =>$prizenum));
			        }
					//发送消息模板之奖记录
				}else{
					$prizenum = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid =:uniacid and from_user =:from_user and prizeid =:prizeid and zhongjiang=1", array(':rid' => $rid, ':uniacid' => $uniacid, ':from_user' => $from_user, ':prizeid' => $awardid));
					$prize = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_prize') . " where id='" . $awardid . "'");
					pdo_update('stonefish_bigwheel_fansaward', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $shangjia['shangjianame'],'zhongjiang' => 2, 'consumetime' => time()), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user, 'prizeid' => $awardid, 'zhongjiang' => 1));
					pdo_update('stonefish_bigwheel_fans', array('tickettype' => $exchange['tickettype'],'ticketid' => $shangjiaid,'ticketname' => $shangjia['shangjianame'],'zhongjiang' => 2), array('rid' => $rid, 'uniacid' => $uniacid, 'from_user' => $from_user));
					//减少库存
					if($exchange['inventory']==2){
					    pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw'] + $prizenum), array('id' => $awardid));
				    }
					//减少库存
					//发送消息模板之奖记录
					if($exchange['tmplmsg_exchange']){
				        $this->seed_tmplmsg($from_user,$exchange['tmplmsg_exchange'],$rid,array('do' =>'myaward', 'prizerating' =>$prize['prizerating'], 'prizename' =>$prize['prizename'], 'prizenum' =>$prizenum));
			        }
					//发送消息模板之奖记录
				}
			    //添加兑奖记录到商家网点
			    if($exchange['tickettype']==3){			
			        $content = '兑奖成功';
			        $insert = array(
                        'uniacid' => $uniacid,
                        'rid' => $rid,
                        'module' => 'stonefish_bigwheel',
                        'fansID' => $_W['member']['uid'],
				        'mobile' => $fans['mobile'],
				        'bid' => $shangjiaid,
                        'content' =>$content,
				        'createtime' => time()
                    );
			        pdo_insert('stonefish_branch_duijiang', $insert);
			    }
			    //添加兑奖记录到商家网点
		        $data = array(                    
			        'msg' => '恭喜兑奖成功！',
                    'success' => 1,
                );
			    $this->Json_encode($data);
		    }else{
			    $data = array(                    
			        'msg' => '系统出错，兑奖密码或账号不匹配！',
                    'success' => 2,
                );
			    $this->Json_encode($data);
		    }
		}	
	}
	//兑奖
	//活动管理
	public function doWebManage() {
        global $_GPC, $_W;
        //查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		//查询是否填写系统参数
		$setting = $this->module['config'];
		if(empty($setting)){
			message('抱歉，系统参数没有填写，请先填写系统参数！', url('profile/module/setting',array('m' => 'stonefish_bigwheel')), 'error');
		}
		//查询是否填写系统参数
		$params = array(':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['keyword'])) {
            $where = ' AND `title` LIKE :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_reply') . "  where uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_reply') . " where uniacid=:uniacid " . $where . " order by id desc " . $limit, $params);

        if (!empty($list)) {
            foreach ($list as &$item) {
                $item['start_time'] = date('Y-m-d H:i', $item['starttime']);
                $item['end_time'] = date('Y-m-d H:i', $item['endtime']);
                $nowtime = time();
                if ($item['starttime'] > $nowtime) {
                    $item['status'] = '<span class="label label-warning">未开始</span>';
                    $item['show'] = 1;
                } elseif ($item['endtime'] < $nowtime) {
                    $item['status'] = '<span class="label label-default ">已结束</span>';
                    $item['show'] = 0;
                } else {
                    if ($item['isshow'] == 1) {
                        $item['status'] = '<span class="label label-success">已开始</span>';
                        $item['show'] = 2;
                    } else {
                        $item['status'] = '<span class="label label-default ">已暂停</span>';
                        $item['show'] = 1;
                    }
                }
            }
        }
        include $this->template('manage');
    }
	//活动管理
	//活动分析表
	public function doWebTrend() {
        global $_GPC, $_W;
		load()->func('tpl');
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		//查询是否填写系统参数
		$setting = $this->module['config'];
		if(empty($setting)){
			message('抱歉，系统参数没有填写，请先填写系统参数！', url('profile/module/setting',array('m' => 'stonefish_bigwheel')), 'error');
		}
		//查询是否填写系统参数
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
        $rid = intval($_GPC['rid']);
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
		//所有奖品类别
		$award = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_prize') . " where rid = :rid and uniacid=:uniacid ORDER BY `id` asc", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//中奖数量
		$reply['zhongjiangnum'] = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid=:uniacid and zhongjiang>=1", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//领取数量
		$reply['lingqunum'] = pdo_fetchcolumn("select sum(totalnum) FROM " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//领取数量
		$reply['helpnum'] = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_sharedata') . " where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//所有奖品类别
		//今日昨天关键指标
		$fansnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_fans') . ' WHERE rid = :rid AND uniacid = :uniacid AND createtime >= :starttime AND createtime <= :endtime', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')) - 86400, ':endtime' => strtotime(date('Y-m-d'))));
		$helpnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_sharedata') . ' WHERE rid = :rid AND uniacid = :uniacid AND visitorstime >= :starttime AND visitorstime <= :endtime', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')) - 86400, ':endtime' => strtotime(date('Y-m-d'))));
		$zhongjiangnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_fansaward') . ' WHERE rid = :rid AND uniacid = :uniacid AND createtime >= :starttime AND createtime <= :endtime and zhongjiang>=1', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')) - 86400, ':endtime' => strtotime(date('Y-m-d'))));
		
		$today_fansnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_fans') . ' WHERE rid = :rid AND uniacid = :uniacid AND createtime >= :starttime AND createtime <= :endtime', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')), ':endtime' => TIMESTAMP));
		$today_helpnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_sharedata') . ' WHERE rid = :rid AND uniacid = :uniacid AND visitorstime >= :starttime AND visitorstime <= :endtime', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')), ':endtime' => TIMESTAMP));
		$today_zhongjiangnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('stonefish_bigwheel_fansaward') . ' WHERE rid = :rid AND uniacid = :uniacid AND createtime >= :starttime AND createtime <= :endtime and zhongjiang>=1', array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':starttime' => strtotime(date('Y-m-d')), ':endtime' => TIMESTAMP));
		//今日昨天关键指标
		$scroll = intval($_GPC['scroll']);
		$st = $_GPC['datelimit']['start'] ? strtotime($_GPC['datelimit']['start']) : strtotime('-30day');
	    $et = $_GPC['datelimit']['end'] ? strtotime($_GPC['datelimit']['end']) : strtotime(date('Y-m-d'));
		if(empty($_GPC['datelimit']['start']) && $st!=$reply['starttime']){
			$st=$reply['starttime'];
		}
	    $starttime = min($st, $et);
	    $endtime = max($st, $et);
		$day_num = ($endtime - $starttime) / 86400 + 1;
	    $endtime += 86399;
		if($_W['isajax'] && $_W['ispost']) {
		    $days = array();
		    $datasets = array();
		    for($i = 0; $i < $day_num; $i++){
			    $key = date('m-d', $starttime + 86400 * $i);
			    $days[$key] = 0;
			    $datasets['flow1'][$key] = 0;
			    $datasets['flow3'][$key] = 0;
			    $datasets['flow4'][$key] = 0;
		    }

			$data = pdo_fetchall('SELECT createtime FROM ' . tablename('stonefish_bigwheel_fans') . ' WHERE uniacid = :uniacid AND rid = :rid AND createtime >= :starttime AND createtime <= :endtime', array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':starttime' => $starttime, ':endtime' => $endtime));
		    foreach($data as $da) {
			    $key = date('m-d', $da['createtime']);
			    if(in_array($key, array_keys($days))) {
				    $datasets['flow1'][$key]++;
			    }
		    }
			
			$data = pdo_fetchall('SELECT visitorstime FROM ' . tablename('stonefish_bigwheel_sharedata') . ' WHERE uniacid = :uniacid AND rid = :rid AND visitorstime >= :starttime AND visitorstime <= :endtime', array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':starttime' => $starttime, ':endtime' => $endtime));
		    foreach($data as $da) {
			    $key = date('m-d', $da['visitorstime']);
			    if(in_array($key, array_keys($days))) {
				    $datasets['flow3'][$key]++;
			    }
		    }
			
			$data = pdo_fetchall('SELECT createtime FROM ' . tablename('stonefish_bigwheel_fansaward') . ' WHERE uniacid = :uniacid AND rid = :rid AND createtime >= :starttime AND createtime <= :endtime and zhongjiang>=1', array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':starttime' => $starttime, ':endtime' => $endtime));
		    foreach($data as $da) {
			    $key = date('m-d', $da['createtime']);
			    if(in_array($key, array_keys($days))) {
				    $datasets['flow4'][$key]++;
			    }
		    }

		    $shuju['label'] = array_keys($days);
		    $shuju['datasets'] = $datasets;
		
		    if ($day_num == 1) {
			    $day_num = 2;
			    $shuju['label'][] = $shuju['label'][0];
			
			    foreach ($shuju['datasets']['flow1'] as $ky => $va) {
				    $k = $ky;
				    $v = $va;
			    }
			    $shuju['datasets']['flow1']['-'] = $v;
			
			    foreach ($shuju['datasets']['flow2'] as $ky => $va) {
				    $k = $ky;
				    $v = $va;
			    }
			    $shuju['datasets']['flow2']['-'] = $v;
			
			    foreach ($shuju['datasets']['flow3'] as $ky => $va) {
				    $k = $ky;
				    $v = $va;
			    }
			    $shuju['datasets']['flow3']['-'] = $v;
			
			    foreach ($shuju['datasets']['flow4'] as $ky => $va) {
				    $k = $ky;
				    $v = $va;
			    }
			    $shuju['datasets']['flow4']['-'] = $v;
		    }

		    $shuju['datasets']['flow1'] = array_values($shuju['datasets']['flow1']);
		    $shuju['datasets']['flow2'] = array_values($shuju['datasets']['flow2']);
		    $shuju['datasets']['flow3'] = array_values($shuju['datasets']['flow3']);
		    $shuju['datasets']['flow4'] = array_values($shuju['datasets']['flow4']);
		    exit(json_encode($shuju));		
	    }
		
        include $this->template('trend');
    }
	//活动分析表
	//模板管理
	public function doWebTemplate() {
        global $_GPC, $_W;
		//查询是否填写系统参数
		$setting = $this->module['config'];
		if(empty($setting)){
			message('抱歉，系统参数没有填写，请先填写系统参数！', url('profile/module/setting',array('m' => 'stonefish_bigwheel')), 'error');
		}
		//查询是否填写系统参数
		//活动模板
		$template = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_template') . " where uniacid = :uniacid ORDER BY `id` asc", array(':uniacid' => $_W['uniacid']));
		if(empty($template)){			
			$inserttemplate = array(
                'uniacid'          => $_W['uniacid'],
				'title'            => '默认',
				'thumb'            => '../addons/stonefish_bigwheel/template/images/template.jpg',
				'fontsize'         => '12',
				'bgimg'            => '',
				'bgcolor'          => '#0cbb84',
				'textcolor'        => '#ffffff',
				'textcolorlink'    => '#f3f3f3',
				'buttoncolor'      => '#38c89a',
				'buttontextcolor'  => '#ffffff',
				'rulecolor'        => '#5dd1ac',
				'ruletextcolor'    => '#f3f3f3',
				'navcolor'         => '#fcfcfc',
				'navtextcolor'     => '#9a9a9a',
				'navactioncolor'   => '#45c018',
				'watchcolor'       => '#f5f0eb',
				'watchtextcolor'   => '#717171',
				'awardcolor'       => '#8571fe',
				'awardtextcolor'   => '#ffffff',
				'awardscolor'      => '#b7b7b7',
				'awardstextcolor'  => '#434343',
			);
			pdo_insert('stonefish_bigwheel_template', $inserttemplate);			
		}
		//活动模板
		$params = array(':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['keyword'])) {
            $where = ' AND `title` LIKE :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_template') . "  where uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_template') . " where uniacid=:uniacid " . $where . " order by id desc " . $limit, $params);
        include $this->template('template');
    }
	//模板管理
	//模板修改
	public function doWebTemplatepost() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
		load()->func('tpl');
		if(!empty($id)) {
			$item = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_template')." where id = :id", array(':id' => $id));				
		}else{
			$item['uniacid'] = $_W['uniacid'];
		}
		if(checksubmit('submit')) {
			if(empty($_GPC['title'])){
				message('模板名称必需输入', referer(), 'error');
			}
			if(!isset($_GPC['thumb'])){
				message('模板缩略图必需上传', referer(), 'error');
			}
			$data = array(
				'uniacid'          => $_GPC['uniacid'],
				'title'            => $_GPC['title'],
				'thumb'            => $_GPC['thumb'],
				'fontsize'         => $_GPC['fontsize'],
				'bgimg'            => $_GPC['bgimg'],
				'bgcolor'          => $_GPC['bgcolor'],
				'textcolor'        => $_GPC['textcolor'],
				'textcolorlink'    => $_GPC['textcolorlink'],
				'buttoncolor'      => $_GPC['buttoncolor'],
				'buttontextcolor'  => $_GPC['buttontextcolor'],
				'rulecolor'        => $_GPC['rulecolor'],
				'ruletextcolor'    => $_GPC['ruletextcolor'],
				'navcolor'         => $_GPC['navcolor'],
				'navtextcolor'     => $_GPC['navtextcolor'],
				'navactioncolor'   => $_GPC['navactioncolor'],
				'watchcolor'       => $_GPC['watchcolor'],
				'watchtextcolor'   => $_GPC['watchtextcolor'],
				'awardcolor'       => $_GPC['awardcolor'],
				'awardtextcolor'   => $_GPC['awardtextcolor'],
				'awardscolor'      => $_GPC['awardscolor'],
				'awardstextcolor'  => $_GPC['awardstextcolor'],
		    );
			if(!empty($id)) {
				pdo_update('stonefish_bigwheel_template', $data, array('id' => $id));
				message('模板修改成功！', url('site/entry/template', array('m' => 'stonefish_bigwheel')), 'success');
			}else{
				pdo_insert('stonefish_bigwheel_template', $data);
				message('模板添加成功！', url('site/entry/template', array('m' => 'stonefish_bigwheel')), 'success');
			}			
		}
        include $this->template('templatepost');
    }
	//模板修改
	//模板删除
	public function doWebTemplatedel() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
		load()->func('tpl');
		if(!empty($id)) {
			$item = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_template')." where id = :id", array(':id' => $id));
			if(!empty($item)){
				pdo_delete('stonefish_bigwheel_template', array('id' => $id));
				message('模板删除成功', referer(), 'success');
			}else{
				message('活动不存在或已删除', referer(), 'error');
			}
		}else{
			message('系统出错', referer(), 'error');
		}
    }
	//模板删除
	//消息模板管理
	public function doWebTmplmsg() {
        global $_GPC, $_W;
		//查询是否填写系统参数
		$setting = $this->module['config'];
		if(empty($setting)){
			message('抱歉，系统参数没有填写，请先填写系统参数！', url('profile/module/setting',array('m' => 'stonefish_bigwheel')), 'error');
		}
		//查询是否填写系统参数
		$params = array(':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['keyword'])) {
            $where = ' AND template_name LIKE :keyword';
            $params[':keyword'] = "%{$_GPC['keyword']}%";
        }
        $total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_tmplmsg') . "  where uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_tmplmsg') . " where uniacid=:uniacid " . $where . " order by id desc " . $limit, $params);
        include $this->template('tmplmsg');
    }
	//消息模板管理
	//消息模板修改
	public function doWebTmplmsgpost() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
		load()->func('tpl');
		if(!empty($id)) {
			$item = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_tmplmsg')." where id = :id", array(':id' => $id));				
		}else{
			$item['uniacid'] = $_W['uniacid'];
		}
		if(checksubmit('submit')) {
			if(empty($_GPC['template_name'])){
				message('消息模板名称必需输入', referer(), 'error');
			}
			if(empty($_GPC['template_id'])){
				message('消息模板ID必需输入', referer(), 'error');
			}
			if(empty($_GPC['first'])){
				message('消息模板标题必需输入', referer(), 'error');
			}
			if(empty($_GPC['keyword1'])){
				message('消息模板必需输入一个参数', referer(), 'error');
			}
			if(empty($_GPC['remark'])){
				message('消息模板必需输入备注', referer(), 'error');
			}
			$data = array(
				'uniacid'          => $_GPC['uniacid'],
				'template_name'    => $_GPC['template_name'],
				'template_id'      => $_GPC['template_id'],
				'topcolor'         => $_GPC['topcolor'],
				'first'            => $_GPC['first'],
				'firstcolor'       => $_GPC['firstcolor'],
				'keyword1'         => $_GPC['keyword1'],
				'keyword2'         => $_GPC['keyword2'],
				'keyword3'         => $_GPC['keyword3'],
				'keyword4'         => $_GPC['keyword4'],
				'keyword5'         => $_GPC['keyword5'],
				'keyword6'         => $_GPC['keyword6'],
				'keyword7'         => $_GPC['keyword7'],
				'keyword8'         => $_GPC['keyword8'],
				'keyword9'         => $_GPC['keyword9'],
				'keyword10'        => $_GPC['keyword10'],
				'keyword1color'    => $_GPC['keyword1color'],
				'keyword2color'    => $_GPC['keyword2color'],
				'keyword3color'    => $_GPC['keyword3color'],
				'keyword4color'    => $_GPC['keyword4color'],
				'keyword5color'    => $_GPC['keyword5color'],
				'keyword6color'    => $_GPC['keyword6color'],
				'keyword7color'    => $_GPC['keyword7color'],
				'keyword8color'    => $_GPC['keyword8color'],
				'keyword9color'    => $_GPC['keyword9color'],
				'keyword10color'   => $_GPC['keyword10color'],
				'keyword1code'     => $_GPC['keyword1code'],
				'keyword2code'     => $_GPC['keyword2code'],
				'keyword3code'     => $_GPC['keyword3code'],
				'keyword4code'     => $_GPC['keyword4code'],
				'keyword5code'     => $_GPC['keyword5code'],
				'keyword6code'     => $_GPC['keyword6code'],
				'keyword7code'     => $_GPC['keyword7code'],
				'keyword8code'     => $_GPC['keyword8code'],
				'keyword9code'     => $_GPC['keyword9code'],
				'keyword10code'    => $_GPC['keyword10code'],
				'remark'           => $_GPC['remark'],
				'remarkcolor'      => $_GPC['remarkcolor'],
		    );
			if(!empty($id)) {
				pdo_update('stonefish_bigwheel_tmplmsg', $data, array('id' => $id));
				message('消息模板修改成功！', url('site/entry/tmplmsg', array('m' => 'stonefish_bigwheel')), 'success');
			}else{
				pdo_insert('stonefish_bigwheel_tmplmsg', $data);
				message('消息模板添加成功！', url('site/entry/tmplmsg', array('m' => 'stonefish_bigwheel')), 'success');
			}			
		}
        include $this->template('tmplmsgpost');
    }
	//消息模板修改
	//消息模板删除
	public function doWebTmplmsgdel() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
		load()->func('tpl');
		if(!empty($id)) {
			$item = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_tmplmsg')." where id = :id", array(':id' => $id));
			if(!empty($item)){
				pdo_delete('stonefish_bigwheel_tmplmsg', array('id' => $id));
				message('消息模板删除成功', referer(), 'success');
			}else{
				message('消息模板不存在或已删除', referer(), 'error');
			}
		}else{
			message('系统出错', referer(), 'error');
		}
    }
	//消息模板删除
	//活动状态设置
    public function doWebSetshow() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
        $isshow = intval($_GPC['isshow']);

        if (empty($rid)) {
            message('抱歉，传递的参数错误！', '', 'error');
        }
        $temp = pdo_update('stonefish_bigwheel_reply', array('isshow' => $isshow), array('rid' => $rid));
		if($isshow){
			message('状态设置成功！活动已开启！', referer(), 'success');
		}else{
			message('状态设置成功！活动已关闭！', referer(), 'success');
		}
       
    }
	//活动状态设置
	//删除活动
	public function doWebDelete() {
        global $_GPC, $_W;
        $rid = intval($_GPC['rid']);
        $rule = pdo_fetch("select id, module from " . tablename('rule') . " where id = :id and uniacid=:uniacid", array(':id' => $rid, ':uniacid' => $_W['uniacid']));
        if (empty($rule)) {
            message('抱歉，要修改的规则不存在或是已经被删除！');
        }
        if (pdo_delete('rule', array('id' => $rid))) {
            pdo_delete('rule_keyword', array('rid' => $rid));
            //删除统计相关数据
            pdo_delete('stat_rule', array('rid' => $rid));
            pdo_delete('stat_keyword', array('rid' => $rid));
            //调用模块中的删除
            $module = WeUtility::createModule($rule['module']);
            if (method_exists($module, 'ruleDeleted')) {
                $module->ruleDeleted($rid);
            }
        }
        message('活动删除成功！', referer(), 'success');
    }
	//删除活动
	//批理删除活动
	public function doWebDeleteAll() {
        global $_GPC, $_W;
        foreach ($_GPC['idArr'] as $k => $rid) {
            $rid = intval($rid);
            if ($rid == 0)
                continue;
            $rule = pdo_fetch("select id, module from " . tablename('rule') . " where id = :id and uniacid=:uniacid", array(':id' => $rid, ':uniacid' => $_W['uniacid']));
            if (empty($rule)) {
				echo json_encode(array('errno' => 1,'error' => '抱歉，要修改的规则不存在或是已经被删除！'));
				exit;
            }
            if (pdo_delete('rule', array('id' => $rid))) {
                pdo_delete('rule_keyword', array('rid' => $rid));
                //删除统计相关数据
                pdo_delete('stat_rule', array('rid' => $rid));
                pdo_delete('stat_keyword', array('rid' => $rid));
                //调用模块中的删除
                $module = WeUtility::createModule($rule['module']);
                if (method_exists($module, 'ruleDeleted')) {
                    $module->ruleDeleted($rid);
                }
            }
        }
        //message('选择中的活动删除成功！', referer(), 'success');
		echo json_encode(array('errno' => 0,'error' => '选择中的活动删除成功！'));
		exit;
    }
	//批理删除活动
	//消息通知记录
	public function doWebPosttmplmsg() {
        global $_GPC, $_W;
		$rid = $_GPC['rid'];
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select poweravatar from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		$params = array(':rid' => $rid, ':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['nickname'])) {
            $where.=' and b.nickname LIKE :nickname';
            $params[':nickname'] = "%{$_GPC['nickname']}%";
        }
		if (!empty($_GPC['realname'])) {     
            $where.=' and b.realname LIKE :realname';
            $params[':realname'] = "%{$_GPC['realname']}%";
        }
		if (!empty($_GPC['mobile'])) {     
            $where.=' and b.mobile LIKE :mobile';
            $params[':mobile'] = "%{$_GPC['mobile']}%";
        }
		if (!empty($_GPC['zhongjiang'])) {     
            $where.=' and b.zhongjiang =:zhongjiang';
            $params[':zhongjiang'] = "{$_GPC['zhongjiang']}";
        }
		$total = pdo_fetchcolumn("select count(a.id) from " . tablename('stonefish_bigwheel_fanstmplmsg') . " as a," . tablename('stonefish_bigwheel_fans') . " as b where a.rid = :rid and a.uniacid=:uniacid and a.from_user=b.from_user" . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select a.tmplmsg,a.createtime,b.avatar,b.realname,b.nickname,b.mobile,c.template_name from " . tablename('stonefish_bigwheel_fanstmplmsg') . " as a," . tablename('stonefish_bigwheel_fans') . " as b," . tablename('stonefish_bigwheel_tmplmsg') . " as c where a.rid = :rid and a.uniacid=:uniacid and a.from_user=b.from_user and c.id=a.tmplmsgid" . $where . " order by a.id desc " . $limit, $params);
		
        include $this->template('posttmplmsg');
    }
	//消息通知记录
	//参与活动粉丝
	public function doWebFansdata() {
        global $_GPC, $_W;
		$rid = $_GPC['rid'];
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select poweravatar from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		$params = array(':rid' => $rid, ':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['nickname'])) {
            $where.=' and nickname LIKE :nickname';
            $params[':nickname'] = "%{$_GPC['nickname']}%";
        }
		if (!empty($_GPC['realname'])) {     
            $where.=' and realname LIKE :realname';
            $params[':realname'] = "%{$_GPC['realname']}%";
        }
		if (!empty($_GPC['mobile'])) {     
            $where.=' and mobile LIKE :mobile';
            $params[':mobile'] = "%{$_GPC['mobile']}%";
        }
		//导出标题以及参数设置
		if($_GPC['zhongjiang']==''){
		    $statustitle = '全部';
		}
		if($_GPC['zhongjiang']==1){
		    $statustitle = '未中奖';
			$where.=' and zhongjiang=0';
		}
		if($_GPC['zhongjiang']==2){
		     $statustitle = '已中奖';
			$where.=' and zhongjiang>=1';
		}
		if($_GPC['zhongjiang']==3){
		     $statustitle = '虚拟奖';
			 $where.='and zhongjiang>=1 and xuni=1';
		}
		//导出标题以及参数设置				
		$total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid=:uniacid " . $where . " order by id desc " . $limit, $params);
		//中奖情况以及是否为关注会员并发送消息
		foreach ($list as &$lists) {
			$lists['awardinfo'] = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . "  where rid = :rid and from_user=:from_user", array(':rid' => $rid,':from_user' => $lists['from_user']));
			$lists['share_num'] = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_sharedata') . "  where rid = :rid and fromuser=:from_user", array(':rid' => $rid,':from_user' => $lists['from_user']));
			$lists['fanid'] = pdo_fetchcolumn("select fanid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$lists['from_user'],":uniacid"=>$_W['uniacid']));
		}
		//中奖情况以及是否为关注会员并发送消息
		//一些参数的显示
        $num1 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and uniacid=:uniacid and zhongjiang=0", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $num2 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $num3 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1 and xuni=1", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//一些参数的显示
        include $this->template('fansdata');
    }
	//参与活动粉丝
	//参与活动粉丝状态
	public function doWebSetfansstatus() {
		global $_GPC, $_W;
		$id = intval($_GPC['id']);
		$data = intval($_GPC['data']);
		if ($id) {
			$data = ($data==1?'0':'1');
			pdo_update("stonefish_bigwheel_fans", array('status' => $data), array("id" => $id));
			die(json_encode(array("result" => 1, "data" => $data)));
		}
		die(json_encode(array("result" => 0)));
	}
	//参与活动粉丝状态
	//删除参与活动粉丝
	public function doWebDeletefans() {
        global $_GPC, $_W;
		$rid = intval($_GPC['rid']);
		$reply = pdo_fetch("select * from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $exchange = pdo_fetch("select inventory FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		if(empty($reply)){
			echo json_encode(array('errno' => 1,'error' => '抱歉，传递的参数错误！'));
			exit;
        }
        foreach ($_GPC['idArr'] as $k => $id) {
            $id = intval($id);
            if($id == 0)
                continue;
			$fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where id = :id", array(':id' => $id));
            if(empty($fans)){
				echo json_encode(array('errno' => 1,'error' => '抱歉，选中的粉丝数据不存在！'));
				exit;
            }
            //删除粉丝中奖记录
			load()->model('mc');
			$fansaward = pdo_fetchall("select id,prizeid,zhongjiang from " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid=:uniacid and from_user=:from_user", array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':from_user' => $fans['from_user']));
			foreach ($fansaward as $fansawards) {
				$prize = pdo_fetch("select id,prizedraw from " . tablename('stonefish_bigwheel_prize') . " where id = :id", array(':id' => $fansawards['prizeid']));
				if($exchange['inventory']==1){
					pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $fansawards['prizeid']));
				}else{
					if($fansawards['zhongjiang']==2){
						pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $fansawards['prizeid']));
					}
				}
				//查询奖品是否为虚拟积分，如果是则扣除相应的积分
				if($prize['prizetype']!='physical' && $prize['prizetype']!='virtual' && $prize['prizetype']!='againprize'){
					$uid = pdo_fetchcolumn("select uid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$fans['from_user'],":uniacid"=>$_W['uniacid']));
					$unisetting_s = uni_setting($_W['uniacid'], array('creditnames'));
		            foreach ($unisetting_s['creditnames'] as $key=>$credit) {
		    	        if ($prize['prizetype']==$key) {
			    	        $credit_names = $credit['title'];
					        break;
			            }
		            }
					//扣除积分到粉丝数据库					
			        mc_credit_update($uid, $prize['prizetype'], -$prize['prizevalue'], array($_W['uid'], '抽抽卡删除奖品扣除'.$prize['prizevalue'].'个'.$credit_names));
			        //扣除积分到粉丝数据库
				}
			    //查询奖品是否为虚拟积分，如果是则扣除相应的积分				
			}
			//删除粉丝中奖记录			
			//删除粉丝中奖详细记录
			pdo_delete('stonefish_bigwheel_fansaward', array('from_user' => $fans['from_user'],'rid' => $rid,'uniacid' => $_W['uniacid']));
			//删除粉丝中奖详细记录
			//删除粉丝中奖详细记录
			pdo_delete('stonefish_bigwheel_sharedata', array('fromuser' => $fans['from_user'],'rid' => $rid,'uniacid' => $_W['uniacid']));
			//删除粉丝中奖详细记录
			//删除粉丝参与记录
			pdo_delete('stonefish_bigwheel_fans', array('id' => $id));
			//删除粉丝参与记录
			$i = $i + 1;
        }
		//减少参与记录
		pdo_update('stonefish_bigwheel_reply', array('fansnum' => $reply['fansnum']-$i), array('id' => $reply['id']));
		//减少参与记录
		echo json_encode(array('errno' => 0,'error' => '选中的粉丝删除成功！'));
		exit;
    }
	//删除参与活动粉丝
	//参与粉丝信息
	public function doWebUserinfo() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//兑奖资料
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			$exchange = pdo_fetch("select * FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
			$isfansname = explode(',',$exchange['isfansname']);
			//粉丝数据
			if($uid){
				$data = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_fans')." where id = :id", array(':id' => $uid));
			}else{
				echo '未找到指定粉丝资料';
				exit;
			}
			include $this->template('userinfo');
			exit();
		}
    }
	//参与粉丝信息
	//参与粉丝中奖记录信息
	public function doWebPrizeinfo() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//中奖记录
			if($uid){
				$data = pdo_fetch("select id, from_user from " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
				$list = pdo_fetchall("select a.*,b.* from " . tablename('stonefish_bigwheel_fansaward') . " a," . tablename('stonefish_bigwheel_prize') . " AS b where a.prizeid = b.id and a.rid = :rid and a.uniacid=:uniacid and a.from_user=:from_user order by a.id desc ", array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':from_user' => $data['from_user']));
			}else{
				echo '未找到指定粉丝中奖记录';
				exit;
			}
			include $this->template('prizeinfo');
			exit();
		}
    }
	//参与粉丝中奖记录信息
	//助力详细情况
	public function doWebSharelist() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//规则
			$reply = pdo_fetch("select poweravatar FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			//粉丝数据
			$data = pdo_fetch("select id, from_user  FROM " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
			$share = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_sharedata') . "  where rid = :rid and uniacid=:uniacid and fromuser=:fromuser ORDER BY id DESC ", array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':fromuser' => $data['from_user']));
			include $this->template('sharelist');
			exit();
		}
    }
	//助力详细情况
	//虚拟助力
	public function doWebAddxunishare() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			load()->func('tpl');
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//规则
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			//粉丝数据
			$data = pdo_fetch("select *  FROM " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
			include $this->template('addxunishare');
			exit();
		}
    }
	public function doWebSavexunishare() {
        global $_GPC, $_W;
		$uid = intval($_GPC['uid']);
		$rid = intval($_GPC['rid']);
		$viewnum = intval($_GPC['viewnum']);
		$point = intval($_GPC['point']);
		if(!$point){
		    message('助力额必需填写', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if(!$rid){
		    message('系统出错', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if($uid) {
		    //规则
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			//粉丝数据
			$data = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
			//添加中奖记录
            $insert = array(
                'uniacid' => $_W['uniacid'],
                'rid' => $rid,
                'from_user' => '系统虚拟者',
                'fromuser' => $data['from_user'],
                'avatar' => $_GPC['avatar'],
                'nickname' => $_GPC['nickname'],
				'visitorsip' => CLIENT_IP,
                'viewnum' => $viewnum,
				'point' => $point,
                'visitorstime' => time()
            );
            pdo_insert('stonefish_bigwheel_sharedata', $insert);
			//添加中奖记录
            //设置此粉丝为虚拟中奖者
            pdo_update('stonefish_bigwheel_fans', array('sharepoint' => $data['sharepoint'] + $point,'xuni' => 1), array('id' => $data['id']));
			//设置此粉丝为虚拟中奖者
			message('添加虚拟助力量成功', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')));
		}else{
			message('未找到指定用户', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}       
    }
	//虚拟助力
	//虚拟奖品
	public function doWebAddxuniaward() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//规则
			$reply = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_reply') . " where rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
			//粉丝数据
			$data = pdo_fetch("select *  FROM " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
			//奖品数据
			$awardlist = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_prize') . ' where rid = :rid and uniacid = :uniacid order by id ASC', array(':uniacid' => $_W['uniacid'], ':rid' => $rid));
			include $this->template('addxuniaward');
			exit();
		}
    }
	public function doWebSavexuniaward() {
        global $_GPC, $_W;
		$uid = intval($_GPC['uid']);
		$rid = intval($_GPC['rid']);
		$awardid = intval($_GPC['awardid']);
		if(!$awardid){
		    message('必需选择奖品才能生效', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if(!$rid){
		    message('系统出错', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if($uid) {
		    //规则
			$reply = pdo_fetch("select * from " . tablename('stonefish_bigwheel_reply') . " where rid = :rid order by `id` desc", array(':rid' => $rid));
			//粉丝数据
			$data = pdo_fetch("select id, from_user, awardnum  from " . tablename('stonefish_bigwheel_fans') . ' where id = :id', array(':id' => $uid));
			//添加中奖记录
			$prize = pdo_fetch("select * from " . tablename('stonefish_bigwheel_prize') . "  where id=:id", array(':id' => $awardid));
			pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw'] + 1), array('id' => $awardid));
            //保存award中
            $codesn = date("YmdHis").mt_rand(100000,999999);
			$insert = array(
                'uniacid' => $_W['uniacid'],
                'rid' => $rid,
                'from_user' => $data['from_user'],                
                'prizeid' => $awardid,
                'codesn' => $codesn,
                'createtime' => time(),
				'zhongjiangtime' => time(),
				'consumetime' => time(),
                'zhongjiang' => 2,
				'xuni' => 1
            );			
            $temp = pdo_insert('stonefish_bigwheel_fansaward', $insert);
            //保存中奖人信息到fans中
            pdo_update('stonefish_bigwheel_fans', array('awardnum' => $data['awardnum'] + 1,'zhongjiang' => 1,'xuni' => 1), array('id' => $data['id']));
			message('添加虚拟奖品成功', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')));
		} else {
			message('未找到指定用户', url('site/entry/fansdata',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}      
    }
	//虚拟奖品
	//参与活动粉丝分享数据
	public function doWebSharedata() {
        global $_GPC, $_W;
		$rid = $_GPC['rid'];
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select poweravatar from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		$params = array(':rid' => $rid, ':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['nickname'])) {
            $where.=' and nickname LIKE :nickname';
            $params[':nickname'] = "%{$_GPC['nickname']}%";
        }		
		if (!empty($_GPC['fromuser'])) {     
            $where.=' and fromuser=:fromuser';
            $params[':fromuser'] = $_GPC['fromuser'];
        }
		$total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_sharedata') . "  where rid = :rid and uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_sharedata') . " where rid = :rid and uniacid=:uniacid " . $where . " order by id desc " . $limit, $params);
		//分享人
		foreach ($list as &$lists) {
			$fans = pdo_fetch("select avatar,nickname,realname from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and from_user=:from_user", array(':rid' => $rid,':from_user' => $lists['fromuser']));
			$lists['favatar'] =$fans['avatar'];
			$lists['fnickname'] =stripcslashes($fans['nickname']);
			$lists['frealname'] =$fans['realname'];
		}
		//分享人
        include $this->template('sharedata');
    }
	//参与活动粉丝分享数据
	//删除参与活动粉丝分享数据
	public function doWebDeletesharedata() {
        global $_GPC, $_W;
		$rid = intval($_GPC['rid']);
		$reply = pdo_fetch("select * from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        if(empty($reply)){
			echo json_encode(array('errno' => 1,'error' => '抱歉，传递的参数错误！'));
			exit;
        }
        foreach ($_GPC['idArr'] as $k => $id) {
            $id = intval($id);
            if($id == 0)
                continue;
			$sharedata = pdo_fetch("select id,point,fromuser from ".tablename('stonefish_bigwheel_sharedata')." where id = :id", array(':id' => $id));
            if(empty($sharedata)){
				echo json_encode(array('errno' => 1,'error' => '抱歉，选中的数据不存在！'));
				exit;
            }
			$fans = pdo_fetch("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid=:uniacid and from_user=:from_user", array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':from_user' => $sharedata['fromuser']));
			//减少参与粉丝分享助力
			pdo_update('stonefish_bigwheel_fans', array('sharenum' => $fans['sharenum']-1,'sharepoint' => $fans['sharepoint']-$sharedata['point']), array('id' => $fans['id']));
			//减少参与粉丝分享助力			
			//删除粉丝分享记录
			pdo_delete('stonefish_bigwheel_sharedata', array('id' => $sharedata['id']));
			//删除粉丝分享记录
        }
		echo json_encode(array('errno' => 0,'error' => '选中的分享数据删除成功！'));
		exit;
    }
	//删除参与活动粉丝分享数据
	//参与活动粉丝奖品数据
	public function doWebPrizedata() {
        global $_GPC, $_W;
		$rid = $_GPC['rid'];
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select poweravatar from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		//所有奖品类别
		$award = pdo_fetchall("select * FROM " . tablename('stonefish_bigwheel_prize') . " where rid = :rid and uniacid=:uniacid ORDER BY `id` asc", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		foreach ($award as $k =>$awards) {
			$award[$k]['num'] = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid=:uniacid and prizeid=:prizeid", array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':prizeid' => $awards['id']));
		}
		//所有奖品类别
		$params = array(':rid' => $rid, ':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['nickname'])) {
            $where.=' and b.nickname LIKE :nickname';
            $params[':nickname'] = "%{$_GPC['nickname']}%";
        }
		if (!empty($_GPC['realname'])) {     
            $where.=' and b.realname LIKE :realname';
            $params[':realname'] = "%{$_GPC['realname']}%";
        }
		if (!empty($_GPC['mobile'])) {     
            $where.=' and b.mobile LIKE :mobile';
            $params[':mobile'] = "%{$_GPC['mobile']}%";
        }
		//导出标题以及参数设置
		if($_GPC['zhongjiang']==''){
		    $statustitle = '全部';
			$where.=' and a.zhongjiang>=1';
		}
		if($_GPC['zhongjiang']==1){
		    $statustitle = '未兑换';
			$where.=' and a.zhongjiang=1';
		}
		if($_GPC['zhongjiang']==2){
		    $statustitle = '已兑换';
			$where.=' and a.zhongjiang>=2';
		}		
		if($_GPC['xuni']==1){
		    $statustitle .= '虚拟';
			$where.=' and a.xuni=1';
		}
		if($_GPC['xuni']=='2'){
		    $statustitle .= '真实';
			$where.=' and a.xuni=0';
		}
		if($_GPC['tickettype']==1){
		    $statustitle .= '后台兑奖';
			$where.=' and a.tickettype=1';
		}
		if($_GPC['tickettype']==2){
		    $statustitle .= '店员兑奖';
			$where.=' and a.tickettype=2';
		}
		if($_GPC['tickettype']==3){
		    $statustitle .= '商家网点兑奖';
			$where.=' and a.tickettype=3';
		}
		if($_GPC['tickettype']==4){
		    $statustitle .= '密码兑奖';
			$where.=' and a.tickettype=4';
		}
		if (!empty($_GPC['prizeid'])) {
            $statustitle .= pdo_fetchcolumn("select prizerating FROM ".tablename('stonefish_bigwheel_prize')." where id=:prizeid", array(':prizeid' => $_GPC['prizeid']));;
			$where.=' and a.prizeid=:prizeid';
            $params[':prizeid'] = $_GPC['prizeid'];
        }
		//导出标题以及参数设置				
		$total = pdo_fetchcolumn("select count(a.id) from " . tablename('stonefish_bigwheel_fansaward') . " as a," . tablename('stonefish_bigwheel_fans') . " as b where a.from_user = b.from_user and a.rid = b.rid and a.uniacid = b.uniacid and a.rid = :rid and a.uniacid=:uniacid" . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select a.*,b.id as fid,b.avatar,b.nickname,b.realname,b.mobile from " . tablename('stonefish_bigwheel_fansaward') . " as a," . tablename('stonefish_bigwheel_fans') . " as b where a.from_user = b.from_user and a.rid = b.rid and a.uniacid = b.uniacid and a.rid = :rid and a.uniacid=:uniacid" . $where . " order by a.id desc " . $limit, $params);
		//奖品名称
		foreach ($list as &$lists) {
			$prize = pdo_fetch("select prizerating,prizename from " . tablename('stonefish_bigwheel_prize') . "  where id = :id", array(':id' =>$lists['prizeid']));
			$lists['prizerating'] =$prize['prizerating'];
			$lists['prizename'] =$prize['prizename'];
			$lists['fanid'] = pdo_fetchcolumn("select fanid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$lists['from_user'],":uniacid"=>$_W['uniacid']));
		}
		//奖品名称
		//一些参数的显示
        $num1 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1 and tickettype=1", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $num2 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1 and tickettype=2", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $num3 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1 and tickettype=3", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		$num4 = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fansaward') . "  where rid = :rid and uniacid=:uniacid and zhongjiang>=1 and tickettype=4", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//一些参数的显示
        include $this->template('prizedata');
    }
	//参与活动粉丝奖品数据
	//设置奖品兑换状态
	public function doWebSetprizestatus() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
		$rid = intval($_GPC['rid']);
		$pid = intval($_GPC['pid']);
        $zhongjiang = intval($_GPC['zhongjiang']);
		$exchange = pdo_fetch("select inventory,tickettype FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		if (empty($id)) {
            message('抱歉，传递的参数错误！', '', 'warning');
        }
		//查询奖品数量
		$prize = pdo_fetch("select * FROM " . tablename('stonefish_bigwheel_prize') . " where id = :id ORDER BY `id` DESC", array(':id' => $pid));
		if($zhongjiang == 2 && $prize['prizetotal']<=$prize['prizedraw']){
			message('抱歉，没有足够的奖品发放了！', '', 'warning');
		}
		//查询奖品数量
        $p = array('zhongjiang' => $zhongjiang);
        if ($zhongjiang == 2) {
            $p['consumetime'] = TIMESTAMP;
			$p['tickettype'] = 1;
			$p['ticketname'] = $_W['username'];
        }
        if ($zhongjiang == 1) {
            $p['consumetime'] = '0';
			$p['zhongjiang'] = 1;
			$p['tickettype'] = $exchange['tickettype'];
			$p['ticketid'] = 0;
			$p['ticketname'] = '';
        }
        $temp = pdo_update('stonefish_bigwheel_fansaward', $p, array('id' => $id));
		if($exchange['inventory']==2 && $zhongjiang == 2){
			pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']+1), array('id' => $pid));
		}
		if($exchange['inventory']==2 && $zhongjiang == 1){
			pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $pid));
		}
        if ($temp == false) {
            message('抱歉，刚才操作数据失败！', '', 'warning');
        } else {
		    //修改用户状态
			$from_user = pdo_fetchcolumn("select from_user FROM " . tablename('stonefish_bigwheel_fansaward') . " where id = :id ORDER BY `id` DESC", array(':id' => $id));
			pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => $zhongjiang), array('rid' => $rid,'uniacid' => $_W['uniacid'],'from_user' => $from_user));
			message('奖品兑换状态设置成功！', $this->createWebUrl('prizedata',array('rid'=>$_GPC['rid'])), 'success');
        }
    }
	//设置奖品兑换状态
	//删除中奖记录数据
	public function doWebDeleteprizedata() {
        global $_GPC, $_W;
		$rid = intval($_GPC['rid']);
		$reply = pdo_fetch("select * from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
        $exchange = pdo_fetch("select inventory,tickettype FROM ".tablename("stonefish_bigwheel_exchange")." where rid = :rid", array(':rid' => $rid));
		if(empty($reply)){
			echo json_encode(array('errno' => 1,'error' => '抱歉，传递的参数错误！'));
			exit;
        }
        foreach ($_GPC['idArr'] as $k => $id) {
            $id = intval($id);
            if($id == 0)
                continue;
			$fansaward = pdo_fetch("select prizeid,from_user,zhongjiang from ".tablename('stonefish_bigwheel_fansaward')." where id = :id", array(':id' => $id));
			$from_user = $fansaward['from_user'];
			$fans = pdo_fetch("select * from ".tablename('stonefish_bigwheel_fans')." where rid = :rid and  uniacid = :uniacid and  from_user = :from_user", array(':rid' => $rid,':uniacid'=>$_W['uniacid'],':from_user'=>$from_user));
            if(empty($fansaward)){
				echo json_encode(array('errno' => 1,'error' => '抱歉，选中的中奖数据不存在！'));
				exit;
            }
			$prize = pdo_fetch("select prizetype,prizevalue,prizedraw FROM " . tablename('stonefish_bigwheel_prize') . " where id = :id ORDER BY `id` DESC", array(':id' => $fansaward['prizeid']));
			//修改积分到粉丝数据库
			if($prize['prizetype']!='physical' && $prize['prizetype']!='virtual' && $prize['prizetype']!='againprize' && $prize['prizetype']!='spaceprize'){
				load()->model('mc');
			    $uid = pdo_fetchcolumn("select uid FROM ".tablename('mc_mapping_fans') ." where openid=:openid and uniacid=:uniacid",array(":openid"=>$from_user,":uniacid"=>$_W['uniacid']));
			    $unisetting_s = uni_setting($uniacid, array('creditnames'));
		        foreach ($unisetting_s['creditnames'] as $key=>$credit) {
		    	    if ($prize['prizetype']==$key) {
			    	    $credit_names = $credit['title'];
					    break;
			        }
		        }			
			    mc_credit_update($uid, $prize['prizetype'], -$prize['prizevalue'], array($_W['uid'], '抽抽卡取消中奖扣除'.$prize['prizevalue'].'个'.$credit_names));
			}
			//修改积分到粉丝数据库
			if($exchange['inventory']==1){
				pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $fansaward['prizeid']));
			}else{
				if($fansaward['zhongjiang']==2){
					pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $fansaward['prizeid']));
				}
			}
			//删除粉丝中奖记录
			pdo_update('stonefish_bigwheel_fansaward', array('zhongjiang' => 0, 'zhongjiangtime' => ''), array('id' => $id));
			//再来一次删除记录并减少次数
			if($prize['prizetype']=='againprize'){
				pdo_delete('stonefish_bigwheel_fansaward', array('id' => $id));
				pdo_update('stonefish_bigwheel_fans', array('totalnum' => $fans['totalnum']-1,'todaynum' => $fans['todaynum']-1), array('id' => $fans['id']));
			}
			//再来一次删除记录并减少次数
			//删除粉丝中奖记录
			//查询此用户是否还有中奖记录并更新状态
			$yes = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_fansaward') ." where from_user=:from_user and uniacid=:uniacid and rid=:rid and zhongjiang>=1",array(":from_user"=>$from_user,":uniacid"=>$_W['uniacid'],":rid"=>$rid));
			if(empty($yes)){
				pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => 0), array('rid' => $rid,'uniacid' => $_W['uniacid'],'from_user' => $from_user));
			}
			//查询此用户是否还有中奖记录并更新状态
        }
		echo json_encode(array('errno' => 0,'error' => '选中的中奖数据删除成功！'));
		exit;
    }
	//删除中奖记录数据
	//参与活动粉丝排行榜
	public function doWebRankdata() {
        global $_GPC, $_W;
		$rid = $_GPC['rid'];
		$rid = empty($rid) ? $_GPC['id'] : $rid;
		$reply = pdo_fetch("select poweravatar from ".tablename('stonefish_bigwheel_reply')." where rid = :rid and uniacid=:uniacid", array(':rid' => $rid, ':uniacid' => $_W['uniacid']));
		//查询do参数
		if(empty($_GPC['do'])){
			$_GPC['do'] = pdo_fetchcolumn("select do from " . tablename('modules_bindings') . "  where eid = :eid and module=:module", array(':eid' => $_GPC['eid'], ':module' => 'stonefish_bigwheel'));
		}
		//查询do参数
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		if(empty($_GPC['page'])){
			$_GPC['page']=1;
		}
		//导出标题以及参数设置
		if($_GPC['rank']=='sharenum' || $_GPC['rank']==''){
		    $statustitle = '分享值';
			$order = 'sharenum';
		}
		if($_GPC['rank']=='sharepoint'){
		    $statustitle = '分享额';
			$order = 'sharepoint';
		}
		if($_GPC['rank']=='award'){
		    $statustitle = '中奖量';
			$order = 'awardnum';
		}
		$params = array(':rid' => $rid, ':uniacid' => $_W['uniacid']);
		$total = pdo_fetchcolumn("select count(id) from " . tablename('stonefish_bigwheel_fans') . "  where rid = :rid and uniacid=:uniacid " . $where . "", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * from " . tablename('stonefish_bigwheel_fans') . " where rid = :rid and uniacid=:uniacid " . $where . " order by ".$order." desc,id asc " . $limit, $params);
        include $this->template('rankdata');
    }
	//参与活动粉丝排行榜
	//商家网点增送项
	public function doWebBranch() {
        global $_GPC, $_W;
		//查询是否有商户网点权限
		$modules = uni_modules($enabledOnly = true);
		$modules_arr = array();
		$modules_arr = array_reduce($modules, create_function('$v,$w', '$v[$w["mid"]]=$w["name"];return $v;'));
		if(in_array('stonefish_branch',$modules_arr)){
		    $stonefish_branch = true;
		}
		//查询是否有商户网点权限
		$rid = $_GPC['rid'];
		//选择商家
		$district = pdo_fetchall("select * FROM " . tablename('stonefish_branch_district') . " where uniacid = '{$_W['uniacid']}' ORDER BY orderid desc, id DESC", array(), 'id');
		$items = pdo_fetchall("select id,title,districtid FROM " . tablename('stonefish_branch_business') . " where uniacid = '{$_W['uniacid']}' ORDER BY id DESC", array(), 'id');
        if (!empty($items)) {
            $business = '';
            foreach ($items as $cid => $cate) {
                $business[$cate['districtid']][$cate['id']] = array($cate['id'], $cate['title']);
            }
        }
		//选择商家
		$params = array(':module' => 'stonefish_bigwheel', ':rid' => $rid, ':uniacid' => $_W['uniacid']);
		if (!empty($_GPC['mobile'])) {     
            $where.=' and mobile=:mobile';
            $params[':mobile'] = $_GPC['mobile'];
        }
		if (!empty($_GPC['districtid'])) {     
            $where.=' and districtid=:districtid';
            $params[':districtid'] = $_GPC['districtid'];
        }elseif(!empty($_GPC['pcate'])){
		    $districts = pdo_fetchall("select id FROM " . tablename('stonefish_branch_business') . "  where districtid=:districtid and  uniacid=:uniacid ORDER BY id DESC", array('districtid' =>$_GPC['pcate'],'uniacid' =>$_W['uniacid']), 'districtid');
			$districtid = '';
            foreach ($districts as $districtss) {
                $districtid .= $districtss['id'].',';
            }
			$districtid = substr($districtid,0,strlen($districtid)-1);
			$where.=' and districtid in(:districtid)';
            $params[':districtid'] = $districtid;
		}
		$total = pdo_fetchcolumn("select count(id) FROM " . tablename('stonefish_branch_doings') . "  where module=:module and rid = :rid and uniacid=:uniacid ".$where."", $params);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 12;
        $pager = pagination($total, $pindex, $psize);
        $start = ($pindex - 1) * $psize;
        $limit .= " LIMIT {$start},{$psize}";
        $list = pdo_fetchall("select * FROM " . tablename('stonefish_branch_doings') . " where module=:module and rid = :rid and uniacid=:uniacid ".$where." ORDER BY id DESC " . $limit, $params);
		//查询商家
		foreach ($list as &$lists) {
			$lists['shangjia'] = pdo_fetchcolumn("select title FROM " . tablename('stonefish_branch_business') . "  where id = :id", array(':id' => $lists['districtid']));
		}
		//查询商家
        include $this->template('branch');
    }
	//商家网点增送项
	//导入商家网点增送记录
	public function doWebImporting() {
        global $_GPC, $_W;
		if($_W['isajax']) {
		    $rid = intval($_GPC['rid']);
		    //选择商家
		    $district = pdo_fetchall("select * FROM " . tablename('stonefish_branch_district') . " where uniacid = '{$_W['uniacid']}' ORDER BY orderid desc, id DESC", array(), 'id');
		    $items = pdo_fetchall("select id,title,districtid FROM " . tablename('stonefish_branch_business') . " where uniacid = '{$_W['uniacid']}' ORDER BY id DESC", array(), 'id');
            if (!empty($items)) {
                $business = '';
                foreach ($items as $cid => $cate) {
                    $business[$cate['districtid']][$cate['id']] = array($cate['id'], $cate['title']);
                }
            }
		    //选择商家
			include $this->template('importing');
			exit();
		}       
    }
	public function doWebImportingsave() {
        global $_GPC, $_W;		
		$rid = intval($_GPC['rid']);
		$districtid = intval($_GPC['districtid']);
		if(!$rid){
		    message('系统出错', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
			exit;
		}
		if(empty($_FILES["inputExcel"]["tmp_name"])){
			message('系统出错', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
			exit;
		}
		$inputFileName = '../addons/stonefish_bigwheel/template/moban/excel/'.$_FILES["inputExcel"]["name"];
		if (file_exists($inputFileName)){
            unlink($inputFileName);    //如果服务器上存在同名文件，则删除
		}
		move_uploaded_file($_FILES["inputExcel"]["tmp_name"],$inputFileName);
        require_once '../framework/library/phpexcel/PHPExcel.php';
        require_once '../framework/library/phpexcel/PHPExcel/IOFactory.php';
        require_once '../framework/library/phpexcel/PHPExcel/Reader/Excel5.php';			
		//设置php服务器可用内存，上传较大文件时可能会用到
		ini_set('memory_limit', '1024M');
		$objReader = PHPExcel_IOFactory::createReader('Excel5');//use excel2007 for 2007 format 
		$objPHPExcel = $objReader->load($inputFileName); 
		$sheet = $objPHPExcel->getSheet(0); 
		$highestRow = $sheet->getHighestRow();           //取得总行数 
		$highestColumn = $sheet->getHighestColumn(); //取得总列数
			
		$objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow(); 

        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
            
        $headtitle=array(); 
        for ($row = 2;$row <= $highestRow;$row++){
            $strs=array();
            //注意highestColumnIndex的列数索引从0开始
            for ($col = 0;$col < $highestColumnIndex;$col++){
                $strs[$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
			//查询是否规定了区域商点
			if(!empty($districtid)){
				$strs[2] = $districtid;
			}
			//查询是否规定了区域商点
            //插入数据
			$chongfu = pdo_fetch("select id FROM ".tablename('stonefish_branch_doings')." where mobile =:mobile and uniacid=:uniacid and districtid=:districtid", array(':mobile' => $strs[0],':uniacid' => $_W['uniacid'],':districtid' => $strs[2]));
			$data = array(
					'uniacid' => $_W['uniacid'],
					'rid' => $rid,
					'module' => 'stonefish_bigwheel',
					'mobile' => $strs[0],
					'awardcount' => $strs[1],
					'districtid' => $strs[2],
					'status' => 2,
					'createtime' => time()
			);
			if (!empty($chongfu)){
				pdo_update('stonefish_branch_doings', $data, array('id' => $chongfu['id']));
			}else{
				pdo_insert('stonefish_branch_doings', $data);
			}				
        }
        unlink($inputFileName); //删除上传的excel文件
        message('导入增送次数成功', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')));
		exit;    
    }
	//导入商家网点增送记录
	//修改商家网店增送记录
	public function doWebEditbranch() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			$data = pdo_fetch("select * FROM " . tablename('stonefish_branch_doings') . ' where id = :id AND uniacid = :uniacid', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
			include $this->template('editbranch');
			exit();
		}       
    }
	public function doWebEditbranchsave() {
        global $_GPC, $_W;
		$uid = intval($_GPC['uid']);
		$rid = intval($_GPC['rid']);
		$usecount = intval($_GPC['usecount']);
		$awardcount = intval($_GPC['awardcount']);
		$status = intval($_GPC['status']);
		if($usecount>$awardcount){
		    message('修改后的次数少于已使用的次数', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if(!$rid){
		    message('系统出错', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
		if($uid) {
		    //次数
            pdo_update('stonefish_branch_doings', array('awardcount' => $awardcount,'status' => $status), array('id' => $uid));
			message('修改增送次数成功', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')));
		} else {
			message('未找到指定用户', url('site/entry/branch',array('rid' => $rid, 'm' => 'stonefish_bigwheel')), 'error');
		}
    }
	//修改商家网店增送记录
	//增送使用记录
	public function doWebUseinfo() {
        global $_GPC, $_W;
		if($_W['isajax']) {
			$uid = intval($_GPC['uid']);
			$rid = intval($_GPC['rid']);
			//粉丝数据
			$data = pdo_fetch("select id, districtid, mobile, awardcount, usecount  FROM " . tablename('stonefish_branch_doings') . ' where id = :id AND uniacid = :uniacid', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
			//商家信息
			$data['shangjiang'] = pdo_fetchcolumn("select title FROM " . tablename('stonefish_branch_business') . "  where id = :id", array(':id' => $data['districtid']));
			$list = pdo_fetchall("select * FROM " . tablename('stonefish_branch_doingslist') . "  where rid = :rid and uniacid=:uniacid and mobile=:mobile ORDER BY id DESC ", array(':uniacid' => $_W['uniacid'], ':rid' => $rid, ':mobile' => $data['mobile']));
			include $this->template('useinfo');
			exit();
		}       
    }
	//增送使用记录
	//增送记录状态
	public function doWebSetcheck() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
        $type = $_GPC['type'];
        $data = intval($_GPC['data']);
        if (in_array($type, array('status'))) {
            $data = ($data==2?'1':'2');
            pdo_update("stonefish_branch_doings", array("status" => $data), array("id" => $id, "uniacid" => $_W['uniacid']));
            die(json_encode(array("result" => 1, "data" => $data)));
        }        
        die(json_encode(array("result" => 0)));
    }
	//增送记录状态
	//删除增送记录
	public function doWebDeletebranch() {
        global $_GPC, $_W;
		$rid = intval($_GPC['rid']);
		$reply = pdo_fetch("select * FROM ".tablename('stonefish_bigwheel_reply')." where rid = :rid", array(':rid' => $rid));
        if (empty($reply)) {
			echo json_encode(array('errno' => 1,'error' => '抱歉，要修改的活动不存在或是已经被删除！'));
			exit;
        }
        foreach ($_GPC['idArr'] as $k => $id) {
            $id = intval($id);
            if ($id == 0)
                continue;			
            //删除使用记录
			$doings = pdo_fetch("select * FROM " . tablename('stonefish_branch_doings') . " where id = :id", array(':id' => $id));
			$doingslist = pdo_fetchall("select * FROM " . tablename('stonefish_branch_doingslist') . " where rid = :rid and uniacid=:uniacid and module=:module and mobile=:mobile", array(':rid' => $rid, ':uniacid' => $_W['uniacid'], ':module' => $doings['module'], ':mobile' => $doings['mobile']));
			foreach ($doingslist as $doingslists) {
				//删除中奖记录
				//删除奖品第一步先恢复到奖池中
				$award = pdo_fetch("select id,prizeid,from_user FROM " . tablename('stonefish_bigwheel_fansaward') . " where id = :id", array(':id' => $doingslists['prizeid']));
				$prize = pdo_fetch("select id,prizedraw FROM " . tablename('stonefish_bigwheel_prize') . " where id = :id", array(':id' => $award['prizeid']));
				pdo_update('stonefish_bigwheel_prize', array('prizedraw' => $prize['prizedraw']-1), array('id' => $award['prizeid']));			
				//删除奖品第一步先恢复到奖池中
				//查询粉丝是否还有中奖记录，没有则需要改变粉丝状态
				$fansaward = pdo_fetch("select id FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid=:uniacid and from_user=:from_user and id!=:id", array(':rid' => $rid, ':from_user' => $award['from_user'], ':uniacid' => $_W['uniacid'], ':id' => $doingslists['prizeid']));
				if(empty($fansaward)){
					pdo_update('stonefish_bigwheel_fans', array('zhongjiang' => 0), array('from_user' => $award['from_user'],'uniacid' => $_W['uniacid'],'rid' => $rid));
				}else{
					$awardnum = pdo_fetchcolumn("select count(*) FROM " . tablename('stonefish_bigwheel_fansaward') . " where rid = :rid and uniacid=:uniacid and from_user=:from_user and id!=:id", array(':rid' => $rid,':uniacid' => $_W['uniacid'],':from_user' => $award['from_user'],':id' => $doingslists['prizeid']));
					pdo_update('stonefish_bigwheel_fans', array('awardnum' => $awardnum,'zhongjiang' => 1), array('from_user' => $award['from_user'],'uniacid' => $_W['uniacid'],'rid' => $rid));
				}
				//查询粉丝是否还有中奖记录，没有则需要改变粉丝状态
				//查询奖品是否为虚拟积分，如果是则扣除相应的积分			
				//查询奖品是否为虚拟积分，如果是则扣除相应的积分
				//删除粉丝中奖记录
			    pdo_delete('stonefish_bigwheel_award', array('id' => $doingslists['prizeid']));
			    //删除粉丝中奖记录
				//删除中奖记录
				pdo_delete('stonefish_branch_doingslist', array('id' => $doingslists['id']));
			}
			//删除使用记录
			//删除赠送记录
			pdo_delete('stonefish_branch_doings', array('id' => $id));
			//删除赠送记录
        }
		echo json_encode(array('errno' => 0,'error' => '商家赠送记录删除成功！'));
		exit;
    }
	//删除增送记录
	//导出数据
	public function doWebDownload() {
        require_once 'download.php';
    }
	//导出数据
	//借用ＪＳ分享
	function getSignPackage($appId,$appSecret) {
		global $_W;
        $jsapiTicket = $this->getJsApiTicket($_W['uniacid'],$appId,$appSecret);
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string1 = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string1);
		$signPackage = array(
			"appId"		=> $appId,
			"nonceStr"	=> $nonceStr,
			"timestamp" => "$timestamp",
			"signature" => $signature,
		);
		
		if(DEVELOPMENT) {
			$signPackage['url'] = $url;
			$signPackage['string1'] = $string1;
			$signPackage['name'] = $_W['account']['name'];
		}        
        return $signPackage;
    }

    function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    function getJsApiTicket($uniacid,$appId,$appSecret) {
        load()->func('cache');
        $api = cache_load("stonefish_bigwheel.api_share.json::".$uniacid, true);
        $new = false;
        if(empty($api['appid']) || $api['appid']!==$appId){
            $new = true;
        }
        if(empty($api['appsecret']) || $api['appsecret']!==$appSecret){
            $new = true;
        }      
        $data = cache_load("stonefish_bigwheel.jsapi_ticket.json::".$uniacid, true);
        if (empty($data['expire_time']) || $data['expire_time'] < time() || $new) {
            $accessToken = $this->getAccessToken($uniacid,$appId,$appSecret);       
            $url = "http://api.weixin.qq.com/cgi-bin/ticket/getticket?type=1&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data['expire_time'] = time() + 7000;
                $data['jsapi_ticket'] = $ticket;
                cache_write("stonefish_bigwheel.jsapi_ticket.json::".$uniacid, iserializer($data));
                cache_write("stonefish_bigwheel.api_share.json::".$uniacid, iserializer(array("appid"=>$appId,"appsecret"=>$appSecret)));
            }
        } else {
            $ticket = $data['jsapi_ticket'];
        }
        return $ticket;
    }

    function getAccessToken($uniacid,$appId,$appSecret) {
        load()->func('cache');
        $api = cache_load("stonefish_bigwheel.api_share.json::".$uniacid, true);
        $new = false;
        if(empty($api['appid']) || $api['appid']!==$appId){
            $new = true;
        }
        if(empty($api['appsecret']) || $api['appsecret']!==$appSecret){
            $new = true;
        }
        $data = cache_load("stonefish_bigwheel.access_token.json::".$uniacid, true);     
        if (empty($data['expire_time']) || $data['expire_time'] < time() || $new) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data['expire_time'] = time() + 7000;
                $data['access_token'] = $access_token;
                cache_write("stonefish_bigwheel.access_token.json::".$uniacid, iserializer($data));
                cache_write("stonefish_bigwheel.api_share.json::".$uniacid, iserializer(array("appid"=>$appId,"appsecret"=>$appSecret)));
            }
        } else {
            $access_token = $data['access_token'];
        }
        return $access_token;
    }
	function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
	//借用ＪＳ分享
}

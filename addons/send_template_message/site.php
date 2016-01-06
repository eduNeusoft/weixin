<?php
/**
 * 红包模块数据库管理
 *
 * @author Gorden
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');

class Send_template_messageModuleSite extends WeModuleSite {
	private $account_wechats = 'account_wechats';

	public function doWebTemplate() {
		global $_W, $_GPC;
	
		
		$ops = array('display', 'edit', 'delete'); // 只支持此 3 种操作.
		$op = in_array($_GPC['op'], $ops) ? $_GPC['op'] : 'display';
		load()->classs('weixin.account');
		
		$appid=$this->module['config']['appid'];
		$appsecrect=$this->module['config']['appsecrect'];
		$uniacid=$this->uniacid;

		
		$sql = "SELECT * FROM ".tablename($this->account_wechats)." WHERE `uniacid`=:uniacid AND `key`=:key AND `secret`=:secret LIMIT 1";
		$params = array(':uniacid'=>$uniacid,':key'=>$appid,':secret'=>$appsecrect);
		$result = pdo_fetch($sql, $params);	
		if($result==null){
			message('请将微赞公众号设置里的appid和appsecrect填写完整！','','error');
		}
		$account=WeiXinAccount::create($result['acid']);
		
		
		if($op == 'display'){
			$data = $this->module['config'];
			
			$r=$account->fetchFansGroups();
			$group=$r['groups'];
			load()->func('tpl');
			include $this->template('template_send');
		}
		
		if ($op == 'edit') {
			
			if (checksubmit()) {
				$data = $_GPC['data']['key']; // 获取打包值
				$gro=$_GPC['data']['group'];
				
				$keys=$this->module['config']['key'];
				$colors=$this->module['config']['color'];
				$postdata=array();
				for($i=0;$i<count($data);$i++){
					$postdata[$keys[$i]]=array('value'=>$data[$i],'color'=>$colors[$i]);
				}
				
		
				$tpl_id_short=$this->module['config']['tpl_id_short'];
				$url=$this->module['config']['url'];
				$topcolor = $this->module['config']['topcolor'];

				$result=$account->fansAll();
				$fans=$result['fans'];
				
				
				if($gro==-1){
					$t=0;
					for($i=0;$i<$result['total'];$i++){
						$t++;
						$touser=$fans[$i];
						$account->sendTplNotice($touser, $tpl_id_short, $postdata , $url , $topcolor);
					}
					
				}else{
					$t=0;
					for($i=0;$i<$result['total'];$i++){
						$touser=$fans[$i];
						$r=$account->fetchFansGroupid($touser);
						if($r['groupid']==$gro){
							$t++;
							$account->sendTplNotice($touser, $tpl_id_short, $postdata , $url , $topcolor);
						}
					}
				}

			
				message("发送成功！共发送至".$t."人！",'','success'); // 保存成功
			}
			$r=$account->fetchFansGroups();
			$group=$r['groups'];
			load()->func('tpl');
			include $this->template('template_send');
		}
		
		
	}
}
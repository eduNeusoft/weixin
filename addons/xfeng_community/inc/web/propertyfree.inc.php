<?php
/**
 * 微小区模块
 *
 * [晓锋] Copyright (c) 2013 qfinfo.cn
 */
/**
 * 后台小区物业费
 */
defined('IN_IA') or exit('Access Denied');
	
	global $_GPC,$_W;
	$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
		if ($op == 'display') {
			//显示当前公众号下面的数据
			$list = pdo_fetchall("SELECT * FROM".tablename('xcommunity_protime')."WHERE weid='{$_W['weid']}'");
			//查当前月份对应的数据
			$condition = '';
			if($_GPC['protime']){
				$condition = "AND protime='{$_GPC['protime']}'";
			}
			if($_GPC['searchword']){
				$condition = "AND mobile='{$_GPC['searchword']}' OR username='{$_GPC['searchword']}'";
			}
			$pindex = max(1, intval($_GPC['page']));
			$psize  = 10;
			$sql = "SELECT * FROM".tablename('xcommunity_propertyfree')."as p left join".tablename('xcommunity_protime')."as t on p.protimeid=t.id where t.weid='{$_W['weid']}' $condition LIMIT ".($pindex - 1) * $psize.','.$psize;
			$rows   = pdo_fetchall($sql);
			$sql1 = "SELECT COUNT(*) FROM".tablename('xcommunity_propertyfree')."as p left join".tablename('xcommunity_protime')."as t on p.protimeid=t.id where t.weid='{$_W['weid']}' $condition";

			$total  = pdo_fetchcolumn($sql1);
			$pager  = pagination($total, $pindex, $psize);
			//物业费短信通知提醒
			if(checksubmit('sms')){
    			$pids=$_GPC['pid'];
    			if(!empty($pids)){
		    		foreach ($pids  as $pid) {
		    			if(!empty($pid)){
		    				$cost = pdo_fetch("SELECT * FROM".tablename('xcommunity_propertyfree')."WHERE id='{$pid}'");
		    				if ($this->module['config']['property_status']) {
								load()->func('communication');
								$tpl_id    = $this->module['config']['property_id'];
								$price 		= $cost['profree'];
								$tpl_value = urlencode("#price#=$price");
								$appkey    = $this->module['config']['sms_account'];
								$params    = "mobile=".$cost['mobile']."&tpl_id=".$tpl_id."&tpl_value=".$tpl_value."&key=".$appkey;
								$url       = 'http://v.juhe.cn/sms/send';
								$content   = ihttp_post($url,$params);
							}
		    			}
		    		}   
	    			message('发送成功！',referer(), 'success');    
	    		}else{
	    			message('请选择要发送的用户！');
	    		}		    			
	    	}
	    	//物业费微信通知提醒
	    	if (checksubmit('wechat')) {
	    		$pids=$_GPC['pid'];
	    		if (!empty($pids)) {
	    			foreach ($pids as $pid) {
	    				$cost = pdo_fetch("SELECT * FROM".tablename('xcommunity_propertyfree')."WHERE id='{$pid}'");
	    				$member = pdo_fetch("SELECT * FROM".tablename('xcommunity_member')."WHERE mobile='{$cost['mobile']}'");
	    				if ($this->module['config']['wechat_status']) {
								$openid = $member['openid'];
								$url = '';
								$template_id = $this->module['config']['property_tplid'];
								$createtime = date('Y-m-d H:i:s', $_W['timestamp']);
								$content = array(
										'first' => array(
												'value' => '您好，您本月物业费已出。',
											),
										'userName' => array(
												'value' => $member['realname'],
											),
										'address' => array(
												'value' => $member['address'],
											),
										'pay'    => array(
												'value' => $cost['profree'],
											),
										'remark'    => array(
											'value' => '请尽快缴纳，如有疑问，请咨询'.$this->module['config']['tel'].'。',
										),	
									);
								$this->sendtpl($openid,$url,$template_id,$content);
						}
	    			}
	    		}
	    	}
			include $this->template('propertyfree');
		}elseif ($op == 'add') {
			if ($_W['ispost']) {
				if (!empty($_FILES['uploadExcel']['name'])) {
						$tmp_file   = $_FILES['uploadExcel']['tmp_name'];
						$file_types = explode(".",$_FILES['uploadExcel']['name']);
						$file_type  = $file_types[count($file_types)-1];
					  /*设置上传路径*/
					   $savePath = IA_ROOT.'/addons/xfeng_community/template/upFile/';
					  /*以时间来命名上传的文件*/
					   $str = date('Ymdhis'); 
					   $file_name = $str.".".$file_type;
					   /*是否上传成功*/
					   if (!copy($tmp_file,$savePath.$file_name)) {
					   		message('上传失败');
					     
					   }
					  $insert['protime']    = $_GPC['protime']['year'].'-'.$_GPC['protime']['month'];
					  $res = $this->read($savePath.$file_name);
					  $result = pdo_fetch("SELECT * FROM".tablename('xcommunity_protime')."WHERE protime='{$insert['protime']}' AND weid='{$_W['weid']}'");
				  	  if ($result) {
				  	  	message('该月份中已存在数据',referer(),'success');exit();
				  	  }
					  $insert['weid']		= $_W['weid'];
					  $insert['createtime'] = TIMESTAMP;
					  pdo_insert('xcommunity_protime',$insert);
					  $protimeid = pdo_insertid();
					  // print_r($insert['protime']);exit;
					  /*对生成的数组进行数据库的写入*/
					  foreach ( $res as $k => $v ) {
						    if ($k != 0) {
								$data['mobile']     = $v[0];
								$data['username']   = $v[1];
								$data['homenumber'] = $v[2];
								$data['profree']    = $v[3];
								$data['tcf']        = $v[4];
								$data['gtsf']       = $v[5];
								$data['gtdf']       = $v[6];
								$data['weid']		= $_W['weid'];
								$data['protimeid']  = $protimeid;
								$data['status']     = $v[7];
								$data['createtime'] = TIMESTAMP;
								$result = pdo_insert('xcommunity_propertyfree',$data);
						    }
					  }

					  if($result){
				       		message('导入成功',referer(),'success');
				     	}
					}
			}
			
			include $this->template('propertyfree_add');
		}elseif ($op == 'delete') {
			$protime = $_GPC['month'];
			// if ($_W['ispost']) {
				$protimeid = pdo_fetchcolumn('SELECT id FROM'.tablename('xcommunity_protime')."WHERE protime='{$protime}' AND weid='{$_W['weid']}'");
				$res       = pdo_delete('xcommunity_propertyfree',array('protimeid' => $protimeid));
				$result    = pdo_delete('xcommunity_protime',array('protime' => $protime,'weid' => $_W['weid']));
				
				if ($result && $res) {
					$r = array(
							'data'   => 1,
							'info'   => '删除成功',
							'status' => 1,
						);
					echo json_encode($r);
				}
			// }
		}
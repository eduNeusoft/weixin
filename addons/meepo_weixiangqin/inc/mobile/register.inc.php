<?php
        global $_W,$_GPC;
        $weid = $_W['uniacid'];
		$settings = pdo_fetch("SELECT * FROM ".tablename('meepo_hongniangset')." WHERE weid=:weid",array(':weid'=>$_W['weid']));
        $hnages = $settings['hnages'];
		if(!empty($hnages)){
			$ages = explode(",",$hnages);
			$morenage = $ages[0];
		}
        $openid = $_W['openid']; 
		if(empty($openid)){
		  message('请重新从微信进入！');
		}
        $res = $this->getusers($weid,$openid);
		$cfg = $this->module['config'];
		
		 $heightLs = array('140','141','142','143','144','145','146','147','148','149','150','151','152','153','154','155','156','157','158','159','160','161','162','163','164','165','166','167','168','169','170','171','172','173','174','175','176','177','178','179','180');
		  $heightHs = array('160','161','162','163','164','165','166','167','168','169','170','171','172','173','174','175','176','177','178','179','180','181','182','183','184','185','186','187','188','189','190','191','192','193','194','195','196','197','198','199','200','201','202','203','204','205','206','207','208','209','210','211','212','213','214','215','216','217','218','219','220');
		  $day = array('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31');
		  $myages = array('18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50');
		   $myheights = array('140','141','142','143','144','145','146','147','148','149','150','151','152','153','154','155','156','157','158','159','160','161','162','163','164','165','166','167','168','169','170','171','172','173','174','175','176','177','178','179','180','181','182','183','184','185','186','187','188','189','190','191','192','193','194','195','196','197','198','199','200','201','202','203','204','205','206','207','208','209','210','211','212','213','214','215','216','217','218','219','220');
			if (!empty($_POST)) {
					$_POST['height'] =intval($_POST['height']); 
					$_POST['uheightL']=intval($_POST['uheightL']);
					$_POST['uheightH']=intval($_POST['uheightH']);
					$_POST['uweight']=intval($_POST['uweight']);
					$_POST['uage']=intval($_POST['uage']);
					$_POST['gender'] =intval($_POST['gender']);
					//2015 07-11 新增完善资料奖励积分
					if(empty($res['Descrip']) && !empty($_POST['Descrip'])){
						
						if(!empty($cfg['awardjifen']) && $cfg['awardjifen'] != '0'){
							 $award = intval($cfg['awardjifen']);
                             pdo_query("UPDATE ".tablename('mc_members')." SET credit1 = credit1 + '{$award}' WHERE uid = '{$_W['member']['uid']}' AND uniacid='{$weid}'");
						}
					}
					load()->model('mc');
					$data = array(
					   'realname'=>$_POST['realname'],
						'telephone'=>$_POST['telephone'],
						'gender'=>$_POST['gender'],
						'constellation'=>$_POST['constellation'],
						'height'=>$_POST['height'],
						'weight'=>$_POST['weight'],
						'education'=>$_POST['education'],
						'revenue'=>$_POST['revenue'],
						'affectivestatus'=>$_POST['affectivestatus'],
						'occupation'=>$_POST['occupation'],
						'lookingfor'=>$_POST['lookingfor'],
					
					);
					
					mc_update($_W['member']['uid'],$data);
					$RES = pdo_update("hnfans", $_POST,array('from_user'=>$openid,'weid'=>$weid));
                    if ($RES) {
                        message("亲，恭喜您，保存成功", $this->createMobileUrl('alllist') , 'sucess');
                    } else {
                        message("你未做任何改变，保存失败！", '','referer');
                    }
            }
           include $this->template('register');
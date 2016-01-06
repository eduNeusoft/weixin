<?php
/**
 * 奔跑吧阿达西模块定义
 *
 * @author 蜂窝科技
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class eudy_adsModuleSite extends WeModuleSite {
 public $activitytalbe = "eudy_ads";
 
 	public function doWebactivity() {
        global $_W, $_GPC;
		
        $operation = ! empty($_GPC['op']) ? $_GPC['op'] : 'display';
        
        if ($operation == 'post') { // 添加
            $id = intval($_GPC['id']);
            
            if (! empty($id)) {
                $item = pdo_fetch("SELECT * FROM " . tablename($this->activitytalbe) . " WHERE id = :id", array(
                    ':id' => $id
                ));
                if (empty($item)) {
                    message('抱歉，游戏删除或不存在！', '', 'error');
                }
				$item['begintime'] = date("Y-m-d  H:i", $item['begintime']);
                $item['endtime'] = date("Y-m-d  H:i", $item['endtime']);
            }
            if (checksubmit('submit')) {
                if (empty($_GPC['name'])) {
                    message('请输入游戏名称!');
                }                
                if (empty($_GPC['link'])) {
                    message('请输入引导关注素材地址！');
                }
                
                $data = array(
                    'weid' => $_W['uniacid'],
                    'name' => $_GPC['name'],
					'icon' => $_GPC['icon'],
                    'link' => $_GPC['link'],
					'qrpic' => $_GPC['qrpic'],
					'bgpic' => $_GPC['bgpic'],
					'music' => $_GPC['music'],
					'fxname' => $_GPC['fxname'],
                );
                if (! empty($id)) {
                    pdo_update($this->activitytalbe, $data, array(
                        'id' => $id
                    ));
                } else {
                    pdo_insert($this->activitytalbe, $data);
                }
                message('更新奔跑吧阿达西模块成功！', $this->createWebUrl("activity", array(
                    "op" => "display"
                )), 'success');
            }
        } elseif ($operation == 'display') {
            $pindex = max(1, intval($_GPC['page']));
            
            $psize = 20;
            
            $list = pdo_fetchall("SELECT * FROM " . tablename($this->activitytalbe) . " WHERE weid = '{$_W['uniacid']}'  ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->activitytalbe) . " WHERE weid = '{$_W['uniacid']}'");
            $pager = pagination($total, $pindex, $psize);
        } elseif ($operation == 'delete') {
            $id = intval($_GPC['id']);
            // 删除活动
            pdo_delete($this->activitytalbe, array(
                'id' => $id
            ));
            
            message('删除成功！', referer(), 'success');
        }
        
        load()->func('tpl');
        include $this->template('activity');
	}
	
	public function  doMobileIndex(){
		global $_W,$_GPC;
		$weid = $_W['uniacid'];
		$fid=$_GPC['fid'];
		//判断是否微信客户端
		require_once MODULE_ROOT.'/checkWX.class.php';
		$cwx=new checkWX();
		$cwx->checkIsWxClient($url);
		unset($cwx);
//判断结束
        $slides = pdo_fetch("select * from ".tablename($this->activitytalbe)." where weid = ".$weid." order by id desc");
		$data_url= $this->createMobileUrl("GetData");
		include $this->template('index');
	}

	public function doMobileGetData(){
		include $this->template('data');
	}
	public function doMobilePostScroe(){
		global $_W,$_GPC;
		checkauth();
		if(!empty($_GPC['fen'])){

			$user=array('realname'=>$_GPC['name'],'mobile'=>$_GPC['tel']);
			$registUser=pdo_fetch("select id,score from ".tablename("eudy_ads_user")." where weid=:weid and fid=:fid ",array(':weid'=>$_W['uniacid'],':fid'=>$_W['member']['uid']));

			if(empty($registUser)){
				$data=array(
					'weid'=>$_W['uniacid'],
					'fid'=>$_W['member']['uid'],
					'uname'=>$user['realname'],
					'tel'=>$user['mobile'],
					'score'=>$_GPC['fen'],
					'createtime'=>TIMESTAMP
				);
				pdo_insert('eudy_ads_user',$data);
				echo "1";
			}else{
				$score=intval($_GPC['fen']);
				if(intval($registUser['score'])<$score){
					$data=array(
						'uname'=>$user['realname'],
						'tel'=>$user['mobile'],
						'score'=>$_GPC['fen'],
						'createtime'=>TIMESTAMP
					);
					pdo_update('eudy_ads_user',$data,array('id'=>$registUser['id']) );
				}
				echo "2";
			}
			
			die();
		}
	}
	
	public function doMobileList(){
		global $_W,$_GPC;
		$weid = $_W['uniacid'];
		$sql="select * from ".tablename('eudy_ads_user')." where weid = ".$weid."  order by score desc LIMIT 30";
		$list = pdo_fetchall($sql);
		include $this->template('datatables');
	}
	public function doWebPicture() {
		global $_W,$_GPC;
		$weid = $_W['uniacid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = "weid = '{$_W['uniacid']}'";
		if (!empty($_GPC['keywords'])) {
			$condition .= " AND uname = '{$_GPC['keywords']}'";
		}
		$sql="select * from ".tablename('eudy_ads_user')." where weid = ".$weid."  order by score desc LIMIT ".(($pindex-1)*$psize).",".$psize;
		$list = pdo_fetchall($sql);
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(eudy_ads_user)." WHERE $condition");
		$pager = pagination($total, $pindex, $psize);		

		include $this->template('picture');
	}	
	public function doWebpost(){
		global $_W,$_GPC;
		$id = (int) $_GPC['id'];
		// 删除
		if($_GPC['op']=='delete'){
			$id = intval($_GPC['id']);
			$row = pdo_fetch("SELECT id FROM ".tablename(eudy_ads_user)." WHERE id = :id", array(':id' => $id));
			if (empty($row)) {
				message('抱歉，信息不存在或是已经被删除！');
			}
			pdo_delete(eudy_ads, array('id' => $id));

			pdo_delete(eudy_ads_user, array('fid' => $id));
			message('删除成功！', referer(), 'success');
		}
		include $this->template('post');
	}
	
	
	






}

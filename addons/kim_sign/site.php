<?php
	/**
	 * 签到模块
	 * 作者：微赞
	 */
defined('IN_IA') or exit('Access Denied');

include_once 'common/common.inc.php';
load()->func('tpl');
class Kim_SignModuleSite extends WeModuleSite {
    private $tableName='kim_sign_record';
    protected $prizesRecordTable = "kim_sign_prizes_record";
    protected $prizesTable = "kim_sign_prizes";
    private function table($table='kim_sign_record'){
        return tablename($table);
    }
    public function doMobileLeaderBoard() {
        global $_GPC, $_W;
        $_W['page']['title']='签到排行榜';
        $settings = getModulesSettings($this->modulename);
        $rank = intval($settings['rank']);
        //当前签到
        $sql = "SELECT * FROM {$this->table()} WHERE uniacid=:uniacid AND time >=:date ORDER BY rank ASC LIMIT 0,$rank";
        $params = array(':uniacid' => $_W['uniacid'],":date"=>strtotime(date('Y-m-d')));
        $list = pdo_fetchall($sql, $params);
        //总次数排行
        $sql = "SELECT * FROM (SELECT * FROM {$this->table()} WHERE uniacid=:uniacid ORDER BY sum_times DESC) A GROUP BY uid ORDER BY sum_times DESC LIMIT 0,$rank";
        $params = array(':uniacid' => $_W['uniacid']);
        $sum_times_list = pdo_fetchall($sql, $params);
        //积份排行
        $sql = "SELECT nickname,email,credit1 FROM ".tablename('mc_members')." WHERE uniacid=:uniacid ORDER BY credit1 DESC LIMIT 0,$rank";
        $credit_list = pdo_fetchall($sql, $params);
        include $this->template('leader_board');
    }
	public function doWebList(){
		global $_GPC, $_W;
		checklogin();
        unset($id,$ids,$index,$size,$page,$sql,$params,$total,$list);
		$id = intval($_GPC['id']);
		if (checksubmit('delete')) {
            $ids = implode("','", $_GPC['select']);
			if(pdo_delete($this->tableName, " id IN ('{$ids}')")) {
                message('删除成功！', $this->createWebUrl('list', array('id' => $id, 'page' => $_GPC['page'])));
            }
        }

		$index = max(1, intval($_GPC['page']));
        $size = 15;
        $page = ($index - 1) * $size;
        $sql = "SELECT * FROM {$this->table()} WHERE uniacid=:uniacid AND time >=:date ORDER BY rank ASC LIMIT {$page},$size";
        $params = array(':uniacid' => $_W['uniacid'],":date"=>strtotime(date('Y-m-d')));
        $list = pdo_fetchall($sql, $params);
        $sql = "SELECT COUNT(*) FROM {$this->table()} WHERE uniacid=:uniacid AND time >=:date ORDER BY rank ASC LIMIT {$page},$size";
		$total = pdo_fetchcolumn($sql, $params);
		$pager = pagination($total, $index, $size);
		include $this->template('list');
	}
    public function doWebPrizesGroup(){
        global $_GPC, $_W,$ptr_title;
        $ptr_title = '奖品所属组';
        $op = $_GPC['op'];
        $ops = array('display','edit','delete','add','record');
        $op = in_array($op, $ops) ? $op : 'display';
        $cls = new Prize;
        if($op == 'add' || $op == 'edit') {
            list($code,$message) = $cls->editGroup();
            if(!empty($message)) {
                if($code) message($message, $this->createWebUrl('PrizesGroup'));
                message($message);
            }
            list($code,$group) = $cls->findGroup();
        }
        list($list,$total,$pager) = $cls->findGroups();
        include $this->template('prizes_group');
    }
    public function doWebPrizes(){
        global $_GPC, $_W,$ptr_title;
        checklogin();
        load()->func('tpl');
        $op = $_GPC['op'];
        $group_id = intval($_GPC['group_id']);
        $settings = getModulesSettings($this->modulename);
        if($group_id <= 0) $group_id = $settings['prize_group'];
        $_GPC['group_id'] = $group_id;
        $ops = array('display','edit','delete','test','record');
        $op = in_array($op, $ops) ? $op : 'display';
        $cls = new Prize;
        $ptr_title = '奖品例表';
        //ajax 按钮
        if($_W['ispost'] && $_W['isajax']) {
            if(!empty($_GPC['fieldmap']) && $_GPC['fieldmap'] == 'status' && $cls->change()) {
                exit('success');
            }elseif(!empty($_GPC['fieldmap']) && $_GPC['fieldmap'] == 'release' && $cls->release()){
                exit('success');
            }
            exit('操作失败');
        }
        if ($op=='delete') {
            if($cls->delete()) message('删除成功！', $this->createWebUrl('Prizes'));
        }elseif($op == 'edit') {
            list($code,$groups) = $cls->groups();
            $id = $_GPC['id'];
            $_W['page']['title'] = intval($id) > 0 ? '奖品编辑 - 主要业务模块 - 签到' : '奖品添加 - 主要业务模块 - 签到';
            $ptr_title = intval($id) > 0 ? '奖品编辑' : '奖品添加';
            list($code,$message,$prize) = $cls->edit();
            if(!$code) message($message);
            if(intval($id) > 0 && empty($prize)) message('奖品不存在.',$this->createWebUrl('Prizes',array('group_id'=>$group_id)));
            if($code && !empty($message)) message($message,$this->createWebUrl('Prizes',array('group_id'=>$group_id)));
        }elseif($op == 'test') {
            $cls->test_lottery(1,$settings['prize_group']);
        }elseif($op == 'record') {
            $prize = $cls->findPrize();
        }
        if(checksubmit('import')) $cls->import();//导入奖品
        list($list,$total,$pager) = $cls->_list();//未领
        include $this->template('prizes');
    }
    public function doMobilePrize() {
        checkauth();
        global $_GPC, $_W;
        $record_id = $_GPC['record_id'];
        $sql = "SELECT * FROM ".tablename('kim_sign_prizes_record')." WHERE uid=:uid AND id=:id";
        $record = pdo_fetch($sql,array(':id'=>$record_id,':uid'=>$_W['member']['uid']));
        if(empty($record)) message('奖品不属于您,或已过期.',url('mc'));
        $sql = "SELECT * FROM ".tablename('kim_sign_prizes')." WHERE id=:id";
        $prize = pdo_fetch($sql,array(':id'=>$record['prize_id']));
        $prize['record'] = $record;
        include $this->template('prize');
    }
    public function doMobileMyPrize() {
        checkauth();
        global $_GPC, $_W;
        $_W['page']['title']='我的礼包';
        $sql = "SELECT r.*,p.prize_name FROM ".tablename('kim_sign_prizes_record')." AS r JOIN ".tablename('kim_sign_prizes')." AS p ON r.prize_id=p.id  WHERE r.uid=:uid";
        $records = pdo_fetchall($sql,array(':uid'=>$_W['member']['uid']));
        include $this->template('my_prize');
    }
	public function doMobileSuccess() {
		include $this->template('success');
	}

}

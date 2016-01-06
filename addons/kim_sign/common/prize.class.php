<?php
/**
 * 抽奖奖品
 * User: xujm
 * Date: 15-2-28
 * Time: 下午1:05
 */
class Prize{
    //获取所有奖品 加入过滤已中奖品不会再抽中
    private function getLotteryPrizes($uid,$prize_group) {
        global $_W;
        $sql='SELECT id,prize_possibility FROM '.tablename('kim_sign_prizes').' WHERE `issued_number`<`number` AND uniacid=:uniacid AND `group_id`=:group_id AND prize_validity > :now AND id NOT IN(SELECT prize_id FROM '.tablename('kim_sign_prizes_record').' WHERE uid=:uid) ORDER BY `prize_possibility` DESC';
        $items = pdo_fetchall($sql, array(':uniacid'=>$_W['uniacid'],':group_id'=>$prize_group,':now'=>TIMESTAMP,':uid'=>$uid));
        return $items;
    }
    public function test_lottery($uid,$prize_group) {
        global $_GPC, $_W;
        //获取所有奖品 加入过滤已中奖品不会再抽中
        $items = $this->getLotteryPrizes($uid,$prize_group);
        if(empty($items)) exit(json_encode(array('code'=>0,'message'=>'没有奖品.','item'=>null)));
        foreach($items as $k=>$v) {
            $prizes[$v['id']] = $v['prize_possibility'];
        }
        $p_id = $this->get_rand($prizes);//获得中奖物品
        if($p_id <= 0) exit(json_encode(array('code'=>0,'message'=>'未抽中.','item'=>null)));
        //计算奖品概率
        $sql = 'SELECT * FROM '.tablename('kim_sign_prizes').' WHERE id=:id';
        $prize = pdo_fetch($sql,array(':id'=>$p_id));//抽出来的奖品
        unset($prizes,$p_id);
        $prizes[$prize['id']] = $prize['prize_possibility'];
        $p_id = $this->get_rand($prizes);
        if($p_id <= 0) exit(json_encode(array('code'=>0,'message'=>'未抽中..','item'=>$prize)));
        //获得兑换码
        $sql = 'SELECT * FROM '.tablename('kim_sign_prizes_record').' WHERE `prize_id`=:prize_id AND uid=0';
        $prize_code= pdo_fetch($sql,array(':prize_id'=>$p_id));
        if(empty($prize_code)) exit(json_encode(array('code'=>0,'message'=>'很抱歉,奖品已放完...','item'=>null)));
        $prize['code'] = $prize_code['code'];
        $result = array('code'=>0,'message'=>'恭喜中奖啦！','item'=>$prize);
        exit(json_encode($result));
    }
    public function lottery($uid,$prize_group) {
        global $_GPC, $_W;
        //获取所有奖品
        $items = $this->getLotteryPrizes($uid,$prize_group);;
        if(empty($items)) return null;
        foreach($items as $k=>$v) {
            $prizes[$v['id']] = $v['prize_possibility'];
        }
        $p_id = $this->get_rand($prizes);//获得中奖物品
        if($p_id <= 0) return array(false,'未抽中..');
        //计算奖品概率
        $sql = 'SELECT * FROM '.tablename('kim_sign_prizes').' WHERE id=:id';
        $prize = pdo_fetch($sql,array(':id'=>$p_id));//抽出来的奖品
        unset($prizes,$p_id);
        $prizes[$prize['id']] = $prize['prize_possibility'];
        $p_id = $this->get_rand($prizes);
        if($p_id <= 0) return array(false,'未抽中...');
        //获得兑换码
        $sql = 'SELECT * FROM '.tablename('kim_sign_prizes_record').' WHERE `prize_id`=:prize_id AND uid=0';
        $prize_code= pdo_fetch($sql,array(':prize_id'=>$p_id));
        if(empty($prize_code)) return array(false,'很抱歉,奖品已放完...');
        $times = $prize['issued_number']+1;
        $update = array('issued_number'=>$times,'update_time'=>TIMESTAMP);
        if($times >= $prize['number']) $update['status'] = -1;
        if(pdo_update('kim_sign_prizes',$update,array('id'=>$prize['id'])) > 0){//更新奖品发放次数
            //记录奖品发放
            $record = array('uid'=>$uid,'winning_time'=>TIMESTAMP,'status'=>1);
            if(pdo_update('kim_sign_prizes_record',$record,array('id'=>$prize_code['id']))) {
                $prize['code'] = $prize_code['code'];
                $prize['record_id'] = $prize_code['id'];
                return array(true,'成功',$prize);
            }
        }
        return array(false,'很抱歉,奖品已放完...');
    }

    public function findGroup() {
        global $_GPC, $_W;
        $op = $_GPC['op'];
        $id = intval($_GPC['id']);
        if($op == 'edit' && $id > 0) {
            $sql = 'SELECT * FROM '.tablename('kim_sign_prizes_group').' WHERE `uniacid`=:uniacid AND `id`=:id';
            return array(true,pdo_fetch($sql,array(':uniacid' => $_W['uniacid'],':id'=>$id)));
        }
    }

    public function findGroups() {
        global $_GPC, $_W;
        $page = max(1, intval($_GPC['page']));
        $page_size = 9;
        $now_page = ($page - 1) * $page_size;
        $list = pdo_fetchall('SELECT * FROM '.tablename('kim_sign_prizes_group').' WHERE `uniacid`=:uniacid ORDER BY `id` DESC LIMIT ' . $now_page . ',' . $page_size, array(':uniacid' => $_W['uniacid']) );
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('kim_sign_prizes_group').' WHERE `uniacid`=:uniacid ORDER BY `id` DESC ', array(':uniacid' => $_W['uniacid']) );
        $pager = pagination($total, $page, $page_size);
        return array($list,$total,$pager);
    }

    /**
     * 获得未删除的组
     */
    public function groups() {
        global $_W;
        $list = pdo_fetchall('SELECT * FROM '.tablename('kim_sign_prizes_group').' WHERE `uniacid`=:uniacid AND `is_del` = 0 ORDER BY `id`', array(':uniacid' => $_W['uniacid']) );
        return array(true,$list);
    }
    /**
     * 编辑奖品组
     * @return array
     */
    public function editGroup() {
        global $_GPC, $_W;
        if (checksubmit('edit')) {
            $group = $_GPC['group'];
            if(empty($group['name'])) return array(false,'组名称不能为空.');
            if(empty($group['create_time'])) $group['create_time'] = TIMESTAMP;
            if(empty($group['is_del'])) $group['is_del'] = 0;
            if(intval($group['id']) > 0) {//修改
                pdo_update('kim_sign_prizes_group',$group,array('id'=>$group['id']));
                return array(true,'修改成功.');
            }else{//添加
                unset($group['id']);
                $group['uniacid'] = $_W['uniacid'];
                pdo_insert('kim_sign_prizes_group',$group);
                if(pdo_insertid() > 0) return array(true,'添加成功.');
                return array(false,'添加失败.');
            }
        }
    }
    public function edit() {
        global $_GPC, $_W;
        $id = $_GPC['id'];
        if (checksubmit('edit')) {
            $prize = $_GPC['prize'];
            $prize_validity = $_GPC['prize_validity'];
            if(empty($prize['prize_name'])) return array(false,'奖品名称为空.',$prize);
            if(intval($prize['prize_possibility']) < 0) return array(false,'奖品概率为空.',$prize);
            if(empty($prize['group_id']) || intval($prize['group_id']) <= 0) return array(false,'所属组为空.',$prize);
            if(empty($prize['description'])) return array(false,'使用详情为空.',$prize);
            if(empty($prize['prize_validity'])) return array(false,'有效期不能为这.',$prize);
            $prize['prize_validity'] = strtotime($prize['prize_validity'].' 23:59:59');
            if(intval($prize['id']) > 0) {//修改
                $prize['update_time'] = TIMESTAMP;
                unset($prize['prize_code']);
                $prize['update_time'] = TIMESTAMP;
                pdo_update('kim_sign_prizes',$prize,array('id'=>$id));
                return array(true,'修改成功.',$prize);
            }else{
                if(empty($prize['prize_code'])) return array(false,'领奖口令为空.',$prize);
                $prize['create_time'] = TIMESTAMP;
                unset($prize['id']);
                $prize['uniacid'] = $_W['uniacid'];
                $codes = explode("\r\n",$prize['prize_code']);
                if(empty($codes)) return array(false,'领奖口令为空.',$prize);
                $prize['number'] = count($codes);
                pdo_begin();
                pdo_insert('kim_sign_prizes',$prize);
                $p_id = pdo_insertid();
                if($p_id > 0) {
                    foreach($codes as $code) {
                        if(empty($code)) continue;
                        $record = array('uid'=>0,'prize_id'=>$p_id,'code'=>$code,'create_time'=>TIMESTAMP,'status'=>0);
                        pdo_insert('kim_sign_prizes_record',$record);
                        if(pdo_insertid() <= 0) {
                            pdo_rollback();
                            return array(false,'添加失败.');
                        }
                    }
                    pdo_commit();
                    return array(true,'添加成功.',$prize);
                }
                pdo_rollback();
                return array(false,'添加失败.');
            }
        }
        if(intval($id) > 0) {
            $sql = 'SELECT * FROM '.tablename('kim_sign_prizes').' WHERE `uniacid`=:uniacid AND `id`=:id';
            $prize = pdo_fetch($sql,array(':uniacid' => $_W['uniacid'],':id'=>$id));
            $prize['record'] = pdo_fetchall('SELECT * FROM '.tablename('kim_sign_prizes_record').' WHERE `prize_id`=:prize_id',array(':prize_id'=>$prize['id']));
            return array(true,'',$prize);
        }else{
            $prize['group_id'] = intval($_GPC['group_id']);
            $prize['prize_validity'] = TIMESTAMP;
        }
        return array(true,'',$prize);
    }
    public function change() {//修改状态
        global $_W,$_GPC;
        $ret = intval($_GPC['ret']) == '1' ? -1 : 1;
        $set = @json_decode(base64_decode($_GPC['dat']), true);
        if(is_array($set)) {
            $field = $_GPC['fieldmap'];
            $record = array($field => $ret,'update_time'=>TIMESTAMP);
            if(pdo_update('kim_sign_prizes_record', $record, array('id' => $set['id'],'status'=>1)) > 0) {
                return true;
            }
        }
        return false;
    }
    public function release() {
        global $_W,$_GPC;
        $set = @json_decode(base64_decode($_GPC['dat']), true);
        if(is_array($set)) {
            $sql = 'SELECT p.id,p.issued_number FROM '.tablename('kim_sign_prizes_record').' AS r JOIN '.tablename('kim_sign_prizes').' AS p ON r.prize_id=p.id WHERE r.id=:id';
            $data = pdo_fetch($sql,array(':id'=>$set['id']));
            if(!empty($data) && intval($data['id'])  > 0) {
                $number = intval($data['issued_number'])-1;
                $number = $number >=0 ? $number : 0;
                if(pdo_update('kim_sign_prizes',array('issued_number'=>$number,'status'=>0),array('id'=>intval($data['id']))) >0){
                    $db_record = pdo_fetch("SELECT * FROM ".tablename('kim_sign_prizes_record')." WHERE id=:id",array(':id' => $set['id']));
                    $db_record['winning_time'] = 0;
                    $db_record['uid'] = 0;
                    $db_record['update_time'] = TIMESTAMP;
                    $db_record['status'] = 0;
                    unset($db_record['id']);
                    pdo_insert('kim_sign_prizes_record',$db_record);
                    if(pdo_insertid() > 0) {
                        //-2表示放弃
                        $record = array('status'=>-2,'update_time'=>TIMESTAMP);
                        if(pdo_update('kim_sign_prizes_record', $record, array('id' => $set['id'])) > 0) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }
        return false;
    }
    public function delete() {
        global $_GPC, $_W;
        pdo_delete('kim_sign_prizes_record', array('prize_id'=>$_GPC['id']));
        pdo_delete('kim_sign_prizes', array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
    }
    public function import() {
        global $_GPC, $_W;
        $data = explode("\r\n",$_GPC['prize_content']);
        $index = 0;
        $items = array();
        foreach($data as $temp) {
            list($name,$possibility,$code) = explode(",",$temp);
            if(empty($name) || intval($possibility) <= 0 || empty($code)) {
                unset($items);
                message("第".($index+1)."行数据出错,导入失败!");
            }
            $prize = array(
                'uniacid' => $_W['uniacid'],
                'prize_name' => $name,
                'prize_possibility' => $possibility,
                'prize_code'=>$code,
                'create_time'=>TIMESTAMP,
                'status'=>0
            );
            $items[$index] = $prize;
            $index++;
        }
        $index = 0;
        foreach($items as $prize) {
            pdo_insert('kim_sign_prizes',$prize);
            if(pdo_insertid() > 0) $index++;
        }
        if(count($items) <> $index) message('导入过程中出现错误，没有完全导入!','refresh');
        message('导入成功!','refresh');
    }
    public function findPrize() {
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
        $sql = 'SELECT * FROM '.tablename('kim_sign_prizes').' WHERE `uniacid`=:uniacid AND `id`=:id';
        $prize = pdo_fetch($sql,array(':uniacid' => $_W['uniacid'],':id'=>$id));
        $prize['record'] = pdo_fetchall('SELECT * FROM '.tablename('kim_sign_prizes_record').' WHERE `prize_id`=:prize_id',array(':prize_id'=>$prize['id']));
        return $prize;
    }
    public function _list() {
        global $_GPC, $_W;
        $group_id = intval($_GPC['group_id']);
        if($group_id <= 0) array(array(),0,null);
        $page = max(1, intval($_GPC['page']));
        $page_size = 9;
        $now_page = ($page - 1) * $page_size;
        $list = pdo_fetchall('SELECT * FROM '.tablename('kim_sign_prizes').' WHERE `uniacid`=:uniacid AND `group_id`=:group_id ORDER BY `prize_possibility` DESC LIMIT ' . $now_page . ',' . $page_size, array(':uniacid' => $_W['uniacid'],':group_id'=>$group_id) );
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('kim_sign_prizes').' WHERE `uniacid`=:uniacid AND `group_id`=:group_id ORDER BY `id` DESC ', array(':uniacid' => $_W['uniacid'],':group_id'=>$group_id) );
        $pager = pagination($total, $page, $page_size);
        return array($list,$total,$pager);
    }
    /*
     * 经典的概率算法，
     * $proArr是一个预先设置的数组，
     * 假设数组为：array(100,200,300，400)，
     * 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，
     * 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间，
     * 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。
     * 这样 筛选到最终，总会有一个数满足要求。
     * 就相当于去一个箱子里摸东西，
     * 第一个不是，第二个不是，第三个还不是，那最后一个一定是。
     * 这个算法简单，而且效率非常 高，
     * 关键是这个算法已在我们以前的项目中有应用，尤其是大数据量的项目中效率非常棒。
     */
    private function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        if($proSum <> 100) {//如果中奖概率没有100% 除于中奖概率之外的为不中奖
            $proArr[0] = 100-$proSum.'';
            $proSum = array_sum($proArr);
        }
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
}
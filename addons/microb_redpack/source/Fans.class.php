<?php

class Fans {

    /**
     * 保存一条用户记录至用户表中, 如果OpenID存在, 则更新记录
     * @param array $entity     用户数据
     * @return int|error        成功返回用户编号, 失败返回错误信息
     */
    public function save($entity) {
        global $_W;
        $rec = array_elements(array('openid', 'nickname', 'gender', 'state', 'city', 'country', 'avatar'), $entity);
        $rec['uniacid'] = $_W['uniacid'];
        $sql = 'SELECT * FROM ' . tablename('mbrp_fans') . ' WHERE `uniacid`=:uniacid AND `openid`=:openid';
        $pars = array();
        $pars[':uniacid'] = $rec['uniacid'];
        $pars[':openid'] = $rec['openid'];
        $exists = pdo_fetch($sql, $pars);
        if(!empty($exists)) {
            $filter = array();
            $filter['uniacid'] = $_W['uniacid'];
            $filter['uid'] = $exists['uid'];
            $ret = pdo_update('mbrp_fans', $rec, $filter);
            if($ret !== false) {
                return $exists['uid'];
            }else{
                return error(-2, '数据更新失败, 请稍后重试');
            }
        }
        $ret = pdo_insert('mbrp_fans', $rec);
        if(!empty($ret)) {
            return pdo_insertid();
        } else {
            return error(-1, '数据保存失败, 请稍后重试');
        }
    }
    
    public function remove($uid, $isOpenid = false) {
        global $_W;
        $pars = array();
        $pars['uniacid'] = $_W['uniacid'];
        if($isOpenid){
            $pars['openid'] = $uid;
        }else{
            $pars['uid'] = intval($uid);
        }
        $del = pdo_delete('mbrp_fans', $pars);
        if($del !== false) {
            return true;
        }else{
            return error(-1, '数据删除失败, 请稍后重试');
        }

    }
    
    public function modify($uid, $entity, $isOpenid = false) {
        global $_W;
        $rec = array_elements(array('unionid', 'nickname', 'gender', 'state', 'city', 'country', 'avatar'), $entity);
        $rec['uniacid'] = $_W['uniacid'];
        $filter = array();
        if($isOpenid) {
            $filter['openid'] = $uid;
        } else {
            $filter['uid'] = intval($uid);
        }
        $ret = pdo_update('mbrp_fans', $rec, $filter);
        if($ret !== false) {
            return true;
        }else{
            return error(-1, '数据更新失败, 请稍后重试');
        }

    }

    public function getOne($uid, $isOpenid = false) {
        global $_W;
        $pars = array();
        $pars[':uniacid'] = $_W['uniacid'];
        if($isOpenid){
            $pars[':openid'] = $uid;
            $sql = 'SELECT * FROM ' . tablename('mbrp_fans') . ' WHERE `uniacid`=:uniacid AND `openid` =:openid';
        }else{
            $pars[':uid'] = intval($uid);
            $sql = 'SELECT * FROM ' . tablename('mbrp_fans') . ' WHERE `uniacid`=:uniacid AND `uid` =:uid';
        }
        $ret= pdo_fetch($sql, $pars);
        if(!empty($ret)) {
            return $ret;
        }else{
            return array();
        }
    }

    public function getAll($filters = array(), $pindex = 0, $psize = 15, &$total = 0) {
        global $_W;
        $condition = '`f`.`uniacid`=:uniacid';
        $pars = array();
        $pars[':uniacid'] = $_W['uniacid'];
        if(!empty($filters['nickname'])) {
            $condition .= ' AND  `f`.`nickname` LIKE :nickname';
            $pars[':nickname'] = "%{$filters['nickname']}%";
        }
        if(!empty($filters['status'])) {
            if($filters['status'] == 'success') {
                $condition .= " AND `r`.`status`='success'";
            } else {
                $condition .= " AND (`r`.`status`!='success' OR `r`.`status` IS NULL)";
            }
        }
        $sql = 'FROM ' . tablename('mbrp_fans') . ' AS `f` LEFT JOIN ' . tablename('mbrp_records') . ' AS `r` ON (`f`.`uid`=`r`.`uid`) WHERE ';
        $sql .= $condition;
        if($pindex > 0){
            $total = pdo_fetchcolumn("SELECT COUNT(*) {$sql}", $pars);
            $start = ($pindex - 1) * $psize;
            $sql .= " ORDER BY `f`.`uid` DESC LIMIT {$start},{$psize}";
            $ds = pdo_fetchall("SELECT `f`.*,`r`.`status`,`r`.`id`,`r`.`type`,`r`.`fee`,`r`.`status` AS `send` {$sql}", $pars);
        } else {
            $sql .= " ORDER BY `f`.`uid` DESC";
            $ds = pdo_fetchall("SELECT `f`.*,`r`.`id`,`r`.`type`,`r`.`fee`,`r`.`status` AS `send` {$sql}", $pars);
        }
        if(!empty($ds)) {
            foreach($ds as &$row) {
                $row['helps'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('mbrp_helps') . ' WHERE `uniacid`=:uniacid AND `from`=:uid', array(':uniacid' => $_W['uniacid'], ':uid' => $row['uid']));
            }
            unset($row);
        }
        return $ds;
    }
    
    public function getProfile($uid) {
        global $_W;
        static $cache = array();
        if(!empty($cache[$uid])) {
            return $cache[$uid];
        } else {
            $sql = "SELECT * FROM " . tablename('mbrp_profiles') . " WHERE `uid`=:uid AND `uniacid`=:uniacid";
            $pars = array();
            $pars[':uid'] = $uid;
            $pars[':uniacid'] = $_W['uniacid'];
            $profiles = pdo_fetchall($sql, $pars);
            $ret = array();
            if(!empty($profiles)) {
                foreach($profiles as $profile) {
                    $ret[$profile['name']] = array(
                        'name' => $profile['name'],
                        'title' => $profile['title'],
                        'value' => $profile['value'],
                    );
                }
            }
            $cache[$uid] = $ret;
            return $ret;
        }
    }
}
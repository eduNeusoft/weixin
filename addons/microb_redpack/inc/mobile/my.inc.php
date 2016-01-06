<?php
global $_W, $_GPC;

$user = $this->auth();

$foo = $_GPC['foo'];
$foos = array('profile', 'history');
$foo = in_array($foo, $foos) ? $foo : 'list';

require_once MB_ROOT . '/source/Fans.class.php';
$f = new Fans();

if($foo == 'profile') {
    exit('success');
    if($_W['ispost']) {
        if($_GPC['code'] != $_SESSION['code']) {
            exit('手机验证码不正确');
        }
        $input = array();
        $input['name'] = trim($_GPC['name']);
        $input['phone'] = trim($_GPC['phone']);
        $titles = array();
        $titles['name'] = '姓名';
        $titles['phone'] = '手机号';

        foreach($input as $k => $v) {
            $r = array();
            $r['uniacid'] = $_W['uniacid'];
            $r['uid'] = $user['uid'];
            $r['name'] = $k;
            $r['title'] = $titles[$k];
            $r['value'] = $v;
            $sql = 'SELECT * FROM ' . tablename('mbrl_profiles') . ' WHERE `uniacid`=:uniacid AND `uid`=:uid AND `name`=:name';
            $pars = array();
            $pars[':uniacid'] = $_W['uniacid'];
            $pars[':uid'] = $user['uid'];
            $pars[':name'] = $k;
            $exists = pdo_fetch($sql, $pars);
            if(empty($exists)) {
                pdo_insert('mbrl_profiles', $r);
            } else {
                pdo_update('mbrl_profiles', $r, array('id' => $exists['id']));
            }
        }
        exit('success');
    }
    $profile = $f->getProfile($user['uid']);
    if(!empty($profile)) {
        exit('success');
    }
}

if($foo == 'history') {
    require_once MB_ROOT . '/source/Activity.class.php';
    $id = $_GPC['actid'];
    $id = intval($id);
    $a = new Activity();
    $activity = $a->getOne($id);
    if($activity['type'] == 'game') {
        $filters = array();
        $filters['owner'] = $user['uid'];
        $filters['activity'] = $activity['actid'];
        $records = $a->getRecords($filters);
        include $this->template('my-game');
    }
    
    if($activity['type'] == 'shared') {
        require_once MB_ROOT . '/source/Shared.class.php';
        $s = new Shared($activity);
        $filters = array();
        $filters['owner'] = $user['uid'];
        $ds = $s->getAllHelps($filters);
        if(!empty($ds)) {
            foreach($ds as &$r) {
                $r['user'] = $f->getOne($r['helper']);
            }
        }
        include $this->template('my-shared');
    }
}

<?php
global $_W, $_GPC;

$this->checkLicense();
$modulePublic = '../addons/microb_redpack/static/';
$foo = $_GPC['foo'];
$foos = array('list', 'create', 'modify', 'delete', 'search');
$foo = in_array($foo, $foos) ? $foo : 'list';
require_once MB_ROOT . '/source/Gift.class.php';

if($foo == 'search') {
    $fuzzy = $_GPC['fuzzy'];

    $filters = array();
    $filters['title'] = $fuzzy;
    $g = new Gift();
    $ds = $g->getAll($filters, 1, 10);
    $rs = array();
    if(!empty($ds)) {
        foreach($ds as $row) {
            $rs[] = array_elements(array('id', 'type', 'title'), $row);
        }
    }
    exit(json_encode($rs));
}

if($foo == 'create') {
    if($_W['ispost']) {
        $input = array_elements(array('title', 'type', 'remark'), $_GPC);
        $input['remark'] = htmlspecialchars_decode($input['remark']);
        if($input['type'] == 'cash') {
            $input['tag'] = serialize($_GPC['cash']);
        } else {
            $input['tag'] = '';
        }
        $g = new Gift();
        $ret = $g->create($input);
        if(is_error($ret)) {
            message($ret['message']);
        } else {
            message('成功保存礼品信息', $this->createWebUrl('gifts'));
        }
    }
    $entity = array();
    $entity['type'] = 'cash';
    $entity['tag'] = array();
    load()->func('tpl');
    include $this->template('gifts-form');
}

if($foo == 'modify') {
    $id = $_GPC['id'];
    $g = new Gift();
    $entity = $g->getOne($id);
    if(empty($entity)) {
        message('访问错误');
    }
    if($_W['ispost']) {
        $input = array_elements(array('title', 'type', 'remark'), $_GPC);
        $input['remark'] = htmlspecialchars_decode($input['remark']);
        if($input['type'] == 'cash') {
            $input['tag'] = serialize($_GPC['cash']);
        } else {
            $input['tag'] = '';
        }
        $g = new Gift();
        $ret = $g->modify($id, $input);
        if(is_error($ret)) {
            message($ret['message']);
        } else {
            message('成功保存礼品信息', $this->createWebUrl('gifts'));
        }
    }
    load()->func('tpl');
    include $this->template('gifts-form');
}

if($foo == 'delete') {
    $id = $_GPC['id'];
    $g = new Gift();
    $g->remove($id);
    message('成功删除礼品信息', $this->createWebUrl('gifts'));
}

if($foo == 'list') {
    $a = new Gift();
    $ds = $a->getAll(array());
    include $this->template('gifts-list');
}
?>

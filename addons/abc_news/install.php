<?php

if(!pdo_tableexists('abc_replace')){
	$sql = "
	CREATE TABLE `ims_abc_replace` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `replace` varchar(32) DEFAULT NULL,
	  `name` varchar(32) DEFAULT NULL,
	  `uniacid` int(11) unsigned NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `uniacid` (`uniacid`) USING BTREE,
	  KEY `id` (`id`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	";
	pdo_query($sql);
	$data = array();
	$data[] = array('replace'=>'#用户ID#','name'=>'uid','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#用户名#','name'=>'username','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#会员组#','name'=>'groupid','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#积分#','name'=>'credit1','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#余额#','name'=>'credit2','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#真实姓名#','name'=>'realname','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#昵称#','name'=>'nickname','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#头像#','name'=>'avatar','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#qq#','name'=>'qq','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#推荐人#','name'=>'father','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#二级推荐人#','name'=>'grandpa','uniacid'=>$_W['uniacid']);
	$data[] = array('replace'=>'#三级推荐人#','name'=>'grandfather','uniacid'=>$_W['uniacid']);
	foreach ($data as $da){
		pdo_insert('abc_replace',$da);
	}
	
}





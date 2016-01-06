<?php

/**
 *助力活动
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_orderform') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`rid` int(10) NOT NULL,
 `weid` int(11) NOT NULL,
 `oname` varchar(200) ,
 `pname` varchar(200) ,
 `odesc` text,
 `address` varchar(200),
 `p_tel` varchar(50),
 `p_desc` text,
 `lng` decimal(18,10) NOT NULL DEFAULT '0.0000000000',
`lat` decimal(18,10) NOT NULL DEFAULT '0.0000000000',
`location_p` varchar(100) NOT NULL,
`location_c` varchar(100) NOT NULL,
`location_a` varchar(100) NOT NULL,
 `p_title_pg` varchar(500),
 `p_titile_url` varchar(500),
 `copyright` varchar(50),
 `follow_url` varchar(200),
`new_title` varchar(200),
`new_icon` varchar(200),
`new_content` varchar(200),
 `share_title` varchar(200),
`share_icon` varchar(200),
`share_content` varchar(200),
`createtime` int(10),
`updatetime` int(10),
`email` varchar(200),
`emailenable` int(1),
PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * 订单项类型
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_orderform_item') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`fid` int(10) ,
`iname` varchar(200) ,
`ititle` varchar(500) ,
`ititle_pg` varchar(500) ,
`ititle_url` varchar(500) ,
`y_price` float(6,2) ,
`x_price` float(6,2) ,
`i_desc` text ,
`i_summary` varchar(50),
`o_tel` varchar(50),
`pay_type` int(1),
`o_num` int(3),
`displayorder` int(3),

`createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * order
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_orderform_order') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`orderno` varchar(100),
`outno` varchar(300),
`acid` varchar(100),
`fid` int(10) ,
`iid` int(10) ,
`openid` varchar(200) NOT NULL,
`nickname` varchar(300),
`headimgurl` varchar(300),
`uname` varchar(300),
`utel` varchar(50),
`ordertime` int(10),
`paytime` int(10),
`ordernum` int (3),
`o_yprice` int (3),
`o_xprice` int (3),
`zf_price` int(3),
`js_price` int(3),
`pay_type` int(3),
`remark` varchar(2000),
`status` int(1),
`createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);
/**
 * 设置
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_orderform_setting') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`weid` int(10) NOT NULL,
`appid` varchar(200) ,
`appsecret` varchar(200),
`mchid` varchar(100),
`shkey` varchar(100),
 `createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * 模板消息设置
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_orderform_template') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`weid` int(10) NOT NULL,
`ordertid` varchar(500) ,
`orderenable` int(1),
`payenable` int(1),
`paytid` varchar(500),
 `createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

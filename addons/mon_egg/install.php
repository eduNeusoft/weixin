<?php


/**
 *砍价活动
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_egg') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`rid` int(10) NOT NULL,
 `weid` int(11) NOT NULL  ,
 `title` varchar(200) NOT NULL,
 `starttime` int(10),
 `endtime` int(10),
 `intro` text,
 `music` varchar(500),
 `banner_bg` varchar(1000),
 `bg_img` varchar(1000),
 `share_bg` varchar(1000),
 `day_count` int(10),
 `prize_limit` int(10),
 `dpassword` varchar(20),
  `follow_url` varchar(1000),
  `copyright` varchar(100) NOT NULL,
 `follow_dlg_tip` varchar(500),
`follow_btn_name` varchar(20),
`share_enable` int(1) default 0,
`share_times` int(10) default 0,
`share_award_count` int(10) default 0,
`new_icon` varchar(200),
`new_content` varchar(200),
 `new_title` varchar(200),
 `share_title` varchar(200),
`share_icon` varchar(200),
`share_content` varchar(200),
`createtime` int(10) DEFAULT 0,
`updatetime` int(10),
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);


/**
 * 参加用户
 */

$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_egg_user') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`egid` int(10) NOT NULL,
 `openid` varchar(200) NOT NULL,
 `nickname` varchar(100) NOT NULL,
 `headimgurl` varchar(200) NOT NULL,
 `createtime` int(10) DEFAULT 0,
 `uname` varchar(100),
 `tel` varchar(50),
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * 扎金蛋奖品
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_egg_prize') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`sn` varchar(100),
`egid` int(10) ,
`plevel` varchar(50) ,
`pname` varchar(50) ,
`pimg` varchar(500),
`ptype` int(1),
`pb` int(10) default 0,
`jf` int(10) default 0,
`pcount` int(10),
`display_order` int(3),
`createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * zjd记录
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_egg_record') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`egid` int(10) NOT NULL,
`pid` int(10) ,
`pname` varchar(200),
`uid` int(10),
`openid` varchar(200) NOT NULL,
`status` int(1),
`createtime` int(10) DEFAULT 0,
`dhtime` int(10),
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);

/**
 * 金蛋share
 */
$sql = "
CREATE TABLE IF NOT EXISTS " . tablename('mon_egg_share') . " (
`id` int(10) unsigned  AUTO_INCREMENT,
`egid` int(10) NOT NULL,
`uid` int(10) ,
`openid` varchar(300),
`createtime` int(10) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
pdo_query($sql);








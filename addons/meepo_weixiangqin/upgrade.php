<?php
 if(!pdo_fieldexists('hnfans', 'yingcang')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD `yingcang` int(2) NOT NULL DEFAULT '1' COMMENT '隐藏显示';");
}
pdo_query("CREATE TABLE IF NOT EXISTS `ims_hnresearch` (
  `reid` int(11) NOT NULL AUTO_INCREMENT,
  `weid` int(11) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(1000) NOT NULL,
  `content` text NOT NULL,
  `information` varchar(500) NOT NULL DEFAULT '',
  `thumb` varchar(200) NOT NULL DEFAULT '',
  `inhome` tinyint(4) NOT NULL DEFAULT '0',
  `createtime` int(10) NOT NULL DEFAULT '0',
  `starttime` int(10) NOT NULL DEFAULT '0',
  `endtime` int(10) unsigned NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `pretotal` int(10) unsigned NOT NULL DEFAULT '1',
  `noticeemail` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`reid`),
  KEY `weid` (`weid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_hnresearch_data` (
  `redid` bigint(20) NOT NULL AUTO_INCREMENT,
  `reid` int(11) NOT NULL,
  `rerid` int(11) NOT NULL,
  `refid` int(11) NOT NULL,
  `data` varchar(800) NOT NULL,
  PRIMARY KEY (`redid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_hnresearch_fields` (  
  `refid` int(11) NOT NULL AUTO_INCREMENT,
  `reid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT '',
  `essential` tinyint(4) NOT NULL DEFAULT '0',
  `bind` varchar(30) NOT NULL DEFAULT '',
  `value` varchar(300) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  `displayorder` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS `ims_hnresearch_rows` (
  `rerid` int(11) NOT NULL AUTO_INCREMENT,
  `reid` int(11) NOT NULL,
  `openid` varchar(50) NOT NULL,
  `createtime` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rerid`),
  KEY `reid` (`reid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("	
CREATE TABLE IF NOT EXISTS `ims_hnfans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weid` int(10) unsigned NOT NULL COMMENT '公众号ID',
  `from_user` varchar(50) NOT NULL COMMENT '用户的唯一身份ID',
  `createtime` int(10) unsigned NOT NULL COMMENT '加入时间',
  `realname` varchar(10) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `nickname` varchar(20) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(200) NOT NULL DEFAULT '' COMMENT '头像',
  `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号码',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别(0:保密 1:男 2:女)',
  `constellation` varchar(10) NOT NULL DEFAULT '' COMMENT '星座',
  `telephone` varchar(15) NOT NULL DEFAULT '' COMMENT '固定电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '邮寄地址',
  `nationality` varchar(30) NOT NULL DEFAULT '' COMMENT '国籍',
  `resideprovincecity` varchar(30) NOT NULL DEFAULT '' COMMENT '居住省市',
  `residedist` varchar(30) NOT NULL DEFAULT '' COMMENT '居住行政区/县',
  `education` varchar(10) NOT NULL DEFAULT '' COMMENT '学历',
  `occupation` varchar(30) NOT NULL DEFAULT '' COMMENT '职业',
  `position` varchar(30) NOT NULL DEFAULT '' COMMENT '职位',
  `revenue` varchar(10) NOT NULL DEFAULT '' COMMENT '年收入',
  `affectivestatus` varchar(30) NOT NULL DEFAULT '' COMMENT '情感状态',
  `lookingfor` varchar(255) NOT NULL DEFAULT '' COMMENT ' 交友目的',
  `height` varchar(5) NOT NULL DEFAULT '' COMMENT '身高',
  `weight` varchar(5) NOT NULL DEFAULT '' COMMENT '体重',
  `interest` text NOT NULL COMMENT '兴趣爱好',
  `lxxingzuo` varchar(200) NOT NULL DEFAULT '' COMMENT '理想星座',
  `housestatus` varchar(20) NOT NULL DEFAULT '' COMMENT '是否有房',
  `carstatus` varchar(20) NOT NULL DEFAULT '' COMMENT '是否有车',
  `lat` varchar(20) NOT NULL DEFAULT '' COMMENT '经度',
  `lng` varchar(20) NOT NULL DEFAULT '' COMMENT '纬度',
  `ueducation` varchar(30) NOT NULL DEFAULT '' COMMENT 'TA的学历',
  `urevenue` varchar(30) NOT NULL DEFAULT '' COMMENT '他的月薪',
  `love` int(10) NOT NULL DEFAULT '0' COMMENT '被喜欢次数',
  `mails` int(10) NOT NULL DEFAULT '0' COMMENT '收信次数',
  `uheightL` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta的最小身高',
  `uheightH` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta的最大身高',
  `uweight` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta的体重',
  `uage` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta的年龄',
  `Descrip` varchar(200) NOT NULL DEFAULT '' COMMENT '一句话描述',
  `uitsCharacter` varchar(300) NOT NULL DEFAULT '' COMMENT 'Ta的性格',
  `uitsOthers` varchar(300) NOT NULL DEFAULT '' COMMENT 'Ta的其他要求',
  `age` int(10) NOT NULL DEFAULT '0' COMMENT '自己的年龄',
  `isshow` int(2) NOT NULL DEFAULT '0' COMMENT '注册审核机制',
  `time` int(12) NOT NULL DEFAULT '0' COMMENT '进入平台获取资料时间',
  `yingcang` int(2) NOT NULL DEFAULT '1' COMMENT '隐藏显示',
  `qq` varchar(20) NOT NULL COMMENT '会员QQ',
  `wechat` varchar(25) NOT NULL COMMENT '会员微信',
  `telephoneconfirm` int(2) NOT NULL DEFAULT '0' COMMENT '是否手机验证',
  `tuijian` int(2) NOT NULL DEFAULT '1' COMMENT '推荐',
  `tjtype` int(2) NOT NULL DEFAULT '0' COMMENT '推荐类型',
  PRIMARY KEY (`id`),
  KEY `weid` (`weid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

 if(!pdo_fieldexists('meepo_hongniangset', 'header_adsurl')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD `header_adsurl` varchar(200) NOT NULL  COMMENT '首页图片链接';");
}

//0803
 if(!pdo_fieldexists('hnmessage', 'msgtype')) {
    pdo_query("ALTER TABLE ".tablename('hnmessage')." ADD   `msgtype` varchar(20) NOT NULL DEFAULT 'text' COMMENT 'leixing';");
}
 if(!pdo_fieldexists('hnmessage', 'thumburl')) {
    pdo_query("ALTER TABLE ".tablename('hnmessage')." ADD   `thumburl` varchar(100) NOT NULL DEFAULT '0' COMMENT 'thumb';");
}


//增加幻灯片
pdo_query("CREATE TABLE IF NOT EXISTS `ims_meepoweixiangqin_slide` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `weid` int(10) unsigned NOT NULL,
            `title` varchar(100) NOT NULL DEFAULT '',
            `url` varchar(200) NOT NULL DEFAULT '',
            `attachment` varchar(100) NOT NULL DEFAULT '',
            `displayorder` int(10) unsigned NOT NULL DEFAULT '0',
            `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示',
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

 if(!pdo_fieldexists('meepo_hongniangset', 'pay_qq')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD   `pay_qq` varchar(10) NOT NULL DEFAULT '0' COMMENT '查看qq';");
}
 if(!pdo_fieldexists('meepo_hongniangset', 'pay_wechat')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD   `pay_wechat` varchar(10) NOT NULL DEFAULT '0' COMMENT '查看微信';");
}
 if(!pdo_fieldexists('hnfans', 'qq')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `qq` varchar(20) NOT NULL COMMENT '会员QQ';");
}
 if(!pdo_fieldexists('hnfans', 'wechat')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `wechat` varchar(25) NOT NULL COMMENT '会员微信';");
}
 if(!pdo_fieldexists('hnfans', 'telephoneconfirm')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `telephoneconfirm` int(2) NOT NULL DEFAULT '0' COMMENT '是否手机验证';");
}
 if(!pdo_fieldexists('hnfans', 'tuijian')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD     `tuijian` int(2) NOT NULL DEFAULT '1' COMMENT '推荐';");
}
//0914

 if(!pdo_fieldexists('hnfans', 'tjtype')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD     `tjtype` int(2) NOT NULL DEFAULT '0' COMMENT '推荐类型';");
}
pdo_query("	
CREATE TABLE IF NOT EXISTS  `ims_meepohn_baoyue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weid` int(11) NOT NULL,
  `tid` varchar(20) NOT NULL DEFAULT '0' COMMENT '包月ID',
  `openid` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `fee` int(10) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `paytype` varchar(20) NOT NULL,
  `transid` varchar(30) NOT NULL DEFAULT '0',
  `time` int(12) NOT NULL,
  `starttime` int(10) NOT NULL DEFAULT '0',
  `endtime` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS  `ims_meepohn_tuijian` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weid` int(10) NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '0',
  `openid` varchar(200) NOT NULL DEFAULT '',
  `payment` int(10) NOT NULL DEFAULT '0',
  `createtime` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;");

pdo_query("CREATE TABLE IF NOT EXISTS  `ims_meepo_sms_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weid` int(10) NOT NULL,
  `news` varchar(10) NOT NULL DEFAULT '',
  `openid` varchar(200) NOT NULL DEFAULT '',
  `createtime` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
?>
<?php
 if(!pdo_fieldexists('hnfans', 'yingcang')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD `yingcang` int(2) NOT NULL DEFAULT '1' COMMENT '������ʾ';");
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
  `weid` int(10) unsigned NOT NULL COMMENT '���ں�ID',
  `from_user` varchar(50) NOT NULL COMMENT '�û���Ψһ���ID',
  `createtime` int(10) unsigned NOT NULL COMMENT '����ʱ��',
  `realname` varchar(10) NOT NULL DEFAULT '' COMMENT '��ʵ����',
  `nickname` varchar(20) NOT NULL DEFAULT '' COMMENT '�ǳ�',
  `avatar` varchar(200) NOT NULL DEFAULT '' COMMENT 'ͷ��',
  `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '�ֻ�����',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '�Ա�(0:���� 1:�� 2:Ů)',
  `constellation` varchar(10) NOT NULL DEFAULT '' COMMENT '����',
  `telephone` varchar(15) NOT NULL DEFAULT '' COMMENT '�̶��绰',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '�ʼĵ�ַ',
  `nationality` varchar(30) NOT NULL DEFAULT '' COMMENT '����',
  `resideprovincecity` varchar(30) NOT NULL DEFAULT '' COMMENT '��סʡ��',
  `residedist` varchar(30) NOT NULL DEFAULT '' COMMENT '��ס������/��',
  `education` varchar(10) NOT NULL DEFAULT '' COMMENT 'ѧ��',
  `occupation` varchar(30) NOT NULL DEFAULT '' COMMENT 'ְҵ',
  `position` varchar(30) NOT NULL DEFAULT '' COMMENT 'ְλ',
  `revenue` varchar(10) NOT NULL DEFAULT '' COMMENT '������',
  `affectivestatus` varchar(30) NOT NULL DEFAULT '' COMMENT '���״̬',
  `lookingfor` varchar(255) NOT NULL DEFAULT '' COMMENT ' ����Ŀ��',
  `height` varchar(5) NOT NULL DEFAULT '' COMMENT '���',
  `weight` varchar(5) NOT NULL DEFAULT '' COMMENT '����',
  `interest` text NOT NULL COMMENT '��Ȥ����',
  `lxxingzuo` varchar(200) NOT NULL DEFAULT '' COMMENT '��������',
  `housestatus` varchar(20) NOT NULL DEFAULT '' COMMENT '�Ƿ��з�',
  `carstatus` varchar(20) NOT NULL DEFAULT '' COMMENT '�Ƿ��г�',
  `lat` varchar(20) NOT NULL DEFAULT '' COMMENT '����',
  `lng` varchar(20) NOT NULL DEFAULT '' COMMENT 'γ��',
  `ueducation` varchar(30) NOT NULL DEFAULT '' COMMENT 'TA��ѧ��',
  `urevenue` varchar(30) NOT NULL DEFAULT '' COMMENT '������н',
  `love` int(10) NOT NULL DEFAULT '0' COMMENT '��ϲ������',
  `mails` int(10) NOT NULL DEFAULT '0' COMMENT '���Ŵ���',
  `uheightL` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta����С���',
  `uheightH` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta��������',
  `uweight` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta������',
  `uage` int(10) NOT NULL DEFAULT '0' COMMENT 'Ta������',
  `Descrip` varchar(200) NOT NULL DEFAULT '' COMMENT 'һ�仰����',
  `uitsCharacter` varchar(300) NOT NULL DEFAULT '' COMMENT 'Ta���Ը�',
  `uitsOthers` varchar(300) NOT NULL DEFAULT '' COMMENT 'Ta������Ҫ��',
  `age` int(10) NOT NULL DEFAULT '0' COMMENT '�Լ�������',
  `isshow` int(2) NOT NULL DEFAULT '0' COMMENT 'ע����˻���',
  `time` int(12) NOT NULL DEFAULT '0' COMMENT '����ƽ̨��ȡ����ʱ��',
  `yingcang` int(2) NOT NULL DEFAULT '1' COMMENT '������ʾ',
  `qq` varchar(20) NOT NULL COMMENT '��ԱQQ',
  `wechat` varchar(25) NOT NULL COMMENT '��Ա΢��',
  `telephoneconfirm` int(2) NOT NULL DEFAULT '0' COMMENT '�Ƿ��ֻ���֤',
  `tuijian` int(2) NOT NULL DEFAULT '1' COMMENT '�Ƽ�',
  `tjtype` int(2) NOT NULL DEFAULT '0' COMMENT '�Ƽ�����',
  PRIMARY KEY (`id`),
  KEY `weid` (`weid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

 if(!pdo_fieldexists('meepo_hongniangset', 'header_adsurl')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD `header_adsurl` varchar(200) NOT NULL  COMMENT '��ҳͼƬ����';");
}

//0803
 if(!pdo_fieldexists('hnmessage', 'msgtype')) {
    pdo_query("ALTER TABLE ".tablename('hnmessage')." ADD   `msgtype` varchar(20) NOT NULL DEFAULT 'text' COMMENT 'leixing';");
}
 if(!pdo_fieldexists('hnmessage', 'thumburl')) {
    pdo_query("ALTER TABLE ".tablename('hnmessage')." ADD   `thumburl` varchar(100) NOT NULL DEFAULT '0' COMMENT 'thumb';");
}


//���ӻõ�Ƭ
pdo_query("CREATE TABLE IF NOT EXISTS `ims_meepoweixiangqin_slide` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `weid` int(10) unsigned NOT NULL,
            `title` varchar(100) NOT NULL DEFAULT '',
            `url` varchar(200) NOT NULL DEFAULT '',
            `attachment` varchar(100) NOT NULL DEFAULT '',
            `displayorder` int(10) unsigned NOT NULL DEFAULT '0',
            `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '�Ƿ���ʾ',
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

 if(!pdo_fieldexists('meepo_hongniangset', 'pay_qq')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD   `pay_qq` varchar(10) NOT NULL DEFAULT '0' COMMENT '�鿴qq';");
}
 if(!pdo_fieldexists('meepo_hongniangset', 'pay_wechat')) {
    pdo_query("ALTER TABLE ".tablename('meepo_hongniangset')." ADD   `pay_wechat` varchar(10) NOT NULL DEFAULT '0' COMMENT '�鿴΢��';");
}
 if(!pdo_fieldexists('hnfans', 'qq')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `qq` varchar(20) NOT NULL COMMENT '��ԱQQ';");
}
 if(!pdo_fieldexists('hnfans', 'wechat')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `wechat` varchar(25) NOT NULL COMMENT '��Ա΢��';");
}
 if(!pdo_fieldexists('hnfans', 'telephoneconfirm')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD    `telephoneconfirm` int(2) NOT NULL DEFAULT '0' COMMENT '�Ƿ��ֻ���֤';");
}
 if(!pdo_fieldexists('hnfans', 'tuijian')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD     `tuijian` int(2) NOT NULL DEFAULT '1' COMMENT '�Ƽ�';");
}
//0914

 if(!pdo_fieldexists('hnfans', 'tjtype')) {
    pdo_query("ALTER TABLE ".tablename('hnfans')." ADD     `tjtype` int(2) NOT NULL DEFAULT '0' COMMENT '�Ƽ�����';");
}
pdo_query("	
CREATE TABLE IF NOT EXISTS  `ims_meepohn_baoyue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `weid` int(11) NOT NULL,
  `tid` varchar(20) NOT NULL DEFAULT '0' COMMENT '����ID',
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
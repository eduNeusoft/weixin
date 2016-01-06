<?php
if(!pdo_fieldexists('egg_reply', 'periodlottery')) {
	pdo_query("ALTER TABLE ".tablename('egg_reply')."  ADD `periodlottery` SMALLINT( 10 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT '0Ϊ������';");
}

if(pdo_fieldexists('egg_award', 'activation_code')) {
	pdo_query("ALTER TABLE ".tablename('egg_award')." CHANGE `activation_code` `activation_code` text;");
}

if(!pdo_fieldexists('egg_reply', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('egg_reply')."  ADD   `uniacid` int(10) unsigned NOT NULL DEFAULT '0';");
}

if(!pdo_fieldexists('egg_reply', 'title')) {
	pdo_query("ALTER TABLE  ".tablename('egg_reply')."  ADD   `title` varchar(255) NOT NULL DEFAULT '' COMMENT '活动标题';");
}

if(!pdo_fieldexists('egg_reply', 'starttime')) {
	pdo_query("ALTER TABLE ".tablename('egg_reply')."  ADD   `starttime` int(10) unsigned NOT NULL COMMENT '开始时间';");
}

if(!pdo_fieldexists('egg_reply', 'endtime')) {
	pdo_query("ALTER TABLE ".tablename('egg_reply')."  ADD   `endtime` int(10) unsigned NOT NULL COMMENT '结束时间';");
}

if(pdo_fieldexists('egg_reply', 'rule')) {
	pdo_query("ALTER TABLE ".tablename('egg_reply')."  CHANGE   `rule`    `rule` text NOT NULL COMMENT '规则';");
}

if(!pdo_fieldexists('egg_winner', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('egg_winner')." ADD   `uniacid` int(10) unsigned NOT NULL DEFAULT '0';");
}
if(!pdo_fieldexists('egg_winner', 'uid')) {
	pdo_query("ALTER TABLE ".tablename('egg_winner')." ADD     `uid` int(10) unsigned NOT NULL DEFAULT '0';");
}
if(!pdo_fieldexists('egg_winner', 'isaward')) {
	pdo_query("ALTER TABLE ".tablename('egg_winner')." ADD    `isaward` tinyint(3) unsigned NOT NULL DEFAULT '0';");
}
if(!pdo_fieldexists('egg_winner', 'credit')) {
	pdo_query("ALTER TABLE ".tablename('egg_winner')." ADD     `credit` int(10) unsigned NOT NULL DEFAULT '0';");
}
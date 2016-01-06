<?php
if(!pdo_fieldexists('shake_reply', 'logo')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `logo` VARCHAR(255) NOT NULL DEFAULT '' AFTER `background`; ");
}
if(!pdo_fieldexists('shake_reply', 'speedandroid')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `speedandroid` INT(10) UNSIGNED NOT NULL DEFAULT '8000' AFTER `speed`;");
}
if(!pdo_fieldexists('shake_reply', 'qrcode')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `qrcode` varchar(255) NOT NULL AFTER `cover`;");
}
if(!pdo_fieldexists('shake_reply', 'maxjoin')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `maxjoin` int(10) unsigned NOT NULL AFTER `maxwinner`;");
}
if(!pdo_fieldexists('shake_reply', 'joinprobability')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `joinprobability` int(10) unsigned NOT NULL AFTER `maxjoin`;");
}
if(!pdo_fieldexists('shake_member', 'status')) {
	pdo_query("ALTER TABLE ".tablename('shake_member')." ADD `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0为不可摇奖，1为可摇奖' AFTER `lastupdate`;");
}
if(!pdo_indexexists('shake_member', 'createtime')) {
	pdo_query("ALTER TABLE ".tablename('shake_member')." ADD KEY `createtime` (`createtime`);");
}
if(!pdo_fieldexists('shake_reply', 'speedandroid')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD `speedandroid` int(10) unsigned NOT NULL DEFAULT '5000' AFTER `speed`;");
}

if(!pdo_fieldexists('shake_reply', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." ADD   `uniacid` int(10) unsigned NOT NULL;");
}
if(pdo_fieldexists('shake_reply', 'qrcode')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." change   `qrcode` `qrcode` varchar(255) NOT NULL;");
}
if(pdo_fieldexists('shake_reply', 'speedandroid')) {
	pdo_query("ALTER TABLE ".tablename('shake_reply')." change   `speedandroid`  `speedandroid` int(10) unsigned NOT NULL DEFAULT '8000';");
}
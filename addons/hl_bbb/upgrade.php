<?php
 $sql = "
CREATE TABLE IF NOT EXISTS `ims_bbb_share` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rid` int(10) unsigned NOT NULL COMMENT '规则ID',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `share_uid` int(10) unsigned NOT NULL COMMENT '分享者ID',
  `createtime` char(8) NOT NULL DEFAULT '' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 ";
pdo_query($sql);
if(!pdo_fieldexists('bbb_reply', 'start_time')) {
	pdo_query("ALTER TABLE ".tablename('bbb_reply')." ADD `start_time` int(10) NOT NULL DEFAULT '0';");
}
if(!pdo_fieldexists('bbb_reply', 'end_time')) {
	pdo_query("ALTER TABLE ".tablename('bbb_reply')." ADD `end_time` int(10) NOT NULL DEFAULT '1600000000';");
}
if(!pdo_fieldexists('bbb_reply', 'uniacid')) {
	pdo_query("ALTER TABLE ".tablename('bbb_reply')." ADD   `uniacid` int(10) unsigned NOT NULL;");
}
pdo_query("update  ".tablename('modules')." set `title`='摇骰子' where `name`='bbb' ;");

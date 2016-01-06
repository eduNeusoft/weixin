<?php
if(!pdo_fieldexists('str_order', 'print_nums')) {
	pdo_query("ALTER TABLE `ims_str_order` ADD `print_nums` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `comment`;");
}
if(!pdo_fieldexists('str_order', 'notice_acid')) {
	pdo_query("ALTER TABLE `ims_str_store` ADD `notice_acid` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `displayorder`;");
}
if(!pdo_fieldexists('str_order', 'groupid')) {
	pdo_query("ALTER TABLE `ims_str_store` ADD `groupid` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `notice_acid`;");
}
if(!pdo_tableexists('str_order_print')) {
	pdo_query("CREATE TABLE IF NOT EXISTS `ims_str_order_print` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `uniacid` int(10) unsigned NOT NULL DEFAULT '0',
		  `sid` int(10) unsigned NOT NULL DEFAULT '0',
		  `pid` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `oid` int(10) unsigned NOT NULL DEFAULT '0',
		  `foid` varchar(50) NOT NULL,
		  `status` tinyint(3) unsigned NOT NULL DEFAULT '2',
		  `addtime` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `addtime` (`addtime`),
		  KEY `foid` (`foid`),
		  KEY `uniacid` (`uniacid`),
		  KEY `pid` (`pid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
	");
}

if(!pdo_tableexists('str_print')) {
	pdo_query("CREATE TABLE IF NOT EXISTS `ims_str_print` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `uniacid` int(10) unsigned NOT NULL DEFAULT '0',
		  `sid` int(10) unsigned NOT NULL,
		  `name` varchar(20) NOT NULL,
		  `print_no` varchar(30) NOT NULL,
		  `key` varchar(30) NOT NULL,
		  `print_nums` tinyint(3) unsigned NOT NULL DEFAULT '1',
		  `qrcode_link` varchar(100) NOT NULL,
		  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
		  PRIMARY KEY (`id`),
		  KEY `uniacid` (`uniacid`),
		  KEY `sid` (`sid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;
	");
}




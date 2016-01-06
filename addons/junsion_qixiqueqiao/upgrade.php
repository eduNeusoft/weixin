<?php
if (!pdo_fieldexists('junsion_qixiqueqiao_rule', 'describe_limit2')) {
	pdo_query("ALTER TABLE " . tablename('junsion_qixiqueqiao_rule') . " ADD  `describe_limit2` int(1) DEFAULT '0';");
}
if (pdo_fieldexists('junsion_qixiqueqiao_rule', 'bg')) {
	pdo_query("ALTER TABLE " . tablename('junsion_qixiqueqiao_rule') . " CHANGE `bg` `bg` VARCHAR(200) ;");
}
$table = "
		CREATE TABLE IF NOT EXISTS `ims_junsion_qixiqueqiao_record` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `openid` varchar(50) NOT NULL,
		  `pid` int(11) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;		
";
pdo_query($table);

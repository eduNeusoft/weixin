<?php
if(!pdo_fieldexists('lxy_marry_list', 'sendtitle')) {
	pdo_query("ALTER TABLE ".tablename('lxy_marry_list')." ADD `sendtitle` VARCHAR(255) NOT NULL DEFAULT '' , ADD `senddescription` VARCHAR(500) NOT NULL DEFAULT '' ;");
}
if(!pdo_fieldexists('lxy_marry_list', 'bg_pic')) {
	pdo_query("ALTER TABLE ".tablename('lxy_marry_list')." ADD   `bg_pic` varchar(255) NOT NULL COMMENT '±³¾°Í¼Æ¬';");
}
if(pdo_fieldexists('lxy_marry_list', 'copyright')) {
	pdo_query("ALTER TABLE ".tablename('lxy_marry_list')." CHANGE  `copyright`  `copyright` varchar(512) DEFAULT NULL COMMENT '°æÈ¨';");
}
<?php
if(!pdo_fieldexists('research_fields', 'displayorder')) {
	pdo_query("ALTER TABLE ".tablename('research_fields')." ADD `displayorder` INT(11) UNSIGNED NOT NULL DEFAULT '0';");
}

if(!pdo_fieldexists('research', 'alltotal')) {
	pdo_query("ALTER TABLE ".tablename('research')." ADD   `alltotal` int(10) unsigned NOT NULL DEFAULT '100' COMMENT '预约总人数';");
}
if(!pdo_fieldexists('research', 'uyan')) {
	pdo_query("ALTER TABLE ".tablename('research')." ADD   `uyan` int(20) NOT NULL;");
}
if(!pdo_fieldexists('research', 'mobile')) {
	pdo_query("ALTER TABLE ".tablename('research')." ADD   `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '通知手机号码';");
}
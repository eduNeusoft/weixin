<?php
defined('IN_IA') or exit('Access Denied');

if(!pdo_fieldexists('weisrc_dragonboat_reply', 'cover')) {
    pdo_query("ALTER TABLE ".tablename('weisrc_dragonboat_reply')." ADD `cover` varchar(500) DEFAULT '';");
}

if(!pdo_fieldexists('weisrc_dragonboat_reply', 'showusernum')) {
    pdo_query("ALTER TABLE ".tablename('weisrc_dragonboat_reply')." ADD `showusernum` int(11) DEFAULT '20';");
}

if(!pdo_fieldexists('weisrc_dragonboat_reply', 'isneedfollow')) {
    pdo_query("ALTER TABLE ".tablename('weisrc_dragonboat_reply')." ADD `isneedfollow` tinyint(1) DEFAULT '0';");
}
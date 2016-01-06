<?php
/**
 */
if(!pdo_fieldexists('qiyue_canvas', 'uid')) {
    pdo_query("ALTER TABLE ".tablename('qiyue_canvas')." ADD  `uid` int(10) unsigned NOT NULL DEFAULT '0';");
}

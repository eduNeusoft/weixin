<?php
/**
 * 种植乐园模块
 *
 * @author 微赞
 * @url http://www.00393.com/
 */
if(!pdo_fieldexists('stonefish_planting_reply', 'award_type')) {
	pdo_query("ALTER TABLE ".tablename('stonefish_planting_reply')." ADD `award_type` tinyint(1) DEFAULT '0' COMMENT '抽奖炻1为保留0为越级' AFTER `award_times`;");
}
if(!pdo_fieldexists('stonefish_planting_reply', 'duijiangtype')) {
	pdo_query("ALTER TABLE ".tablename('stonefish_planting_reply')." ADD `duijiangtype` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '库存类型1抽中减少2兑奖减少' AFTER `tickettype`;");
}
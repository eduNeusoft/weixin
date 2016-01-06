<?php
/**
 * Kim签到管理模块定义
 * 修改表结构时必须把需要更新的表结构按要求放下面
 * 每提升以个版本需要更新把所有更新的表结构需放在 upgrade ，如未添加表、未修改表结构就省略
 */
if(!pdo_fieldexists('kim_sign_reply', 'type')) {
   pdo_query("ALTER TABLE ".tablename('kim_sign_reply')." ADD   `type` int(11) NOT NULL COMMENT '类型 0-普通模式 1-摇一摇';");
}
if(!pdo_fieldexists('kim_sign_reply', 'title')) {
   pdo_query("ALTER TABLE ".tablename('kim_sign_reply')." ADD    `title` varchar(50) DEFAULT NULL COMMENT '回复标题';");
}

if(!pdo_fieldexists('kim_sign_reply', 'image_url')) {
   pdo_query("ALTER TABLE ".tablename('kim_sign_reply')." ADD     `image_url` varchar(255) DEFAULT NULL COMMENT '回复图片';");
}

if(!pdo_fieldexists('kim_sign_reply', 'description')) {
   pdo_query("ALTER TABLE ".tablename('kim_sign_reply')." ADD     `description` text COMMENT '描述';");
}





<?php
/**
 * Kim签到模块定义
 * 下面建表SQL必须完整，把所需的表全部列出;
 * 每提升以个版本需要更新 install版本对应的表结构，如未添加表、未修改表结构就省略
 * @author Kim
 * @url http://wx.358cn.com
 */
//1.0 版本 表结构
$sql =<<<EOF
CREATE TABLE IF NOT EXISTS `ims_kim_sign_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `uid` int(11) NOT NULL COMMENT '用户ID',
  `time` int(11) NOT NULL COMMENT '签到时间',
  `sum_times` int(11) NOT NULL COMMENT '总次数',
  `rank` int(11) NOT NULL COMMENT '排名',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='签到记录';
CREATE TABLE IF NOT EXISTS `ims_kim_sign_reply` (
  `id` int(123) NOT NULL AUTO_INCREMENT,
  `rid` int(123) NOT NULL COMMENT '规则ID',
  `overtime` text NOT NULL COMMENT '超出时间段提示',
  `overnum` text NOT NULL COMMENT '超出次数提示',
  `type` int(11) NOT NULL COMMENT '类型 0-普通模式 1-摇一摇',
  `title` varchar(50) DEFAULT NULL COMMENT '回复标题',
  `image_url` varchar(255) DEFAULT NULL COMMENT '回复图片',
  `description` text COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='回复规则';
CREATE TABLE IF NOT EXISTS `ims_kim_sign_prizes` (
  `id`  int NOT NULL AUTO_INCREMENT,
  `uniacid` int(11) NOT NULL,
  `group_id`  int NULL COMMENT '所属组',
  `prize_name`  varchar(50) NULL COMMENT '奖品名称' ,
  `prize_possibility`  int(11) NULL COMMENT '奖品概率百分比' ,
  `prize_code`  varchar(255) NULL COMMENT '领奖口令' ,
  `prize_validity`  int(255) NULL COMMENT '有效期',
  `prize_code_image`  varchar(255) NULL COMMENT '二维码',
  `description`  text NULL COMMENT '使用详情',
  `prize_url`  varchar(255) NULL COMMENT '兑奖URL',
  `create_time`  int(11) NULL COMMENT '添加时间' ,
  `update_time`  int(11) NULL COMMENT '最后修改时间' ,
  `number`  int NULL DEFAULT 1 COMMENT '总份数' ,
  `issued_number` int NULL DEFAULT 0 COMMENT '已发份数' ,
  `status`  int(1) NULL DEFAULT 0 COMMENT '0-未领取，-1-已发放 -2-已使用' ,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='签到奖品';
CREATE TABLE IF NOT EXISTS `ims_kim_sign_prizes_record` (
  `id`  int NOT NULL AUTO_INCREMENT ,
  `uid`  int NULL COMMENT '领奖用户ID' ,
  `code`  varchar(255) NULL COMMENT '兑奖口令',
  `prize_id`  int NULL COMMENT '奖品ID' ,
  `create_time`  int NULL COMMENT '创建时间' ,
  `update_time`  int NULL COMMENT '领取时间',
  `winning_time`  int NULL DEFAULT 0 COMMENT '中奖时间',
  `status`  int NULL COMMENT '状态 0-正常 1-中奖 -1-已发放',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='奖品发放记录';
CREATE TABLE IF NOT EXISTS `ims_kim_sign_prizes_group` (
    `id`  int NOT NULL AUTO_INCREMENT ,
    `uniacid` int(11) NOT NULL,
    `name`  varchar(50) NULL COMMENT '组名' ,
    `create_time`  int NULL COMMENT '创建时间',
    `is_del`  int(1) NULL DEFAULT 0 COMMENT '删除状态 0 -未删除 -1-已删除',
    PRIMARY KEY (`id`)
)ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='奖品分组';
EOF;
pdo_run($sql);
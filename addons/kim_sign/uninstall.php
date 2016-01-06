<?php
/**
 * Kim答到管理模块定义
 *
 * @author Kim
 * @url http://wx.358cn.com
 */
$sql =<<<EOF
DROP TABLE IF EXISTS `ims_kim_sign_record`;
DROP TABLE IF EXISTS `ims_kim_sign_reply`;
DROP TABLE IF EXISTS `ims_kim_sign_prizes`;
DROP TABLE IF EXISTS `ims_kim_sign_prizes_record`;
DROP TABLE IF EXISTS `ims_kim_sign_prizes_group`;
EOF;
pdo_run($sql);
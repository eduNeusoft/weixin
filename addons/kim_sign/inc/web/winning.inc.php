<?php
/**
 * Kim签到模块定义
 */
defined('IN_IA') or exit('Access Denied');
global $_GPC, $_W;
$_W['page']['title'] = "奖品发放";
//检查是否登录
checklogin();
$sql = tablename($this->prizesRecordTable)." AS R LEFT JOIN ".tablename($this->prizesTable)." AS P ON R.prize_id=P.id WHERE R.uid > 0 AND R.status=1 AND P.uniacid=:uniacid";
$index = max(1, intval($_GPC['page']));
$size = 15;
$page = ($index - 1) * $size;
$list = pdo_fetchall("SELECT R.*,P.*,P.id AS p_id,R.id AS r_id FROM $sql ORDER BY winning_time LIMIT {$page},$size", array(":uniacid"=>$_W['uniacid']));
$total = pdo_fetchcolumn("SELECT COUNT(*) FROM $sql ORDER BY winning_time", array(":uniacid"=>$_W['uniacid']));
$pager = pagination($total, $index, $size);
include $this->template('winning-list');
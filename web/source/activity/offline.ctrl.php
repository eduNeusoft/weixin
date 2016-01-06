<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */

defined('IN_IA') or exit('Access Denied');

$dos = array('introduce', 'clerk');
$do = in_array($do, $dos) ? $do : 'introduce';

if($do == 'introduce') {
	$_W['page']['title'] = '功能说明 - 门店营销参数 - 会员营销';
	template('activity/offline');
}

if($do == 'clerk') {
	$_W['page']['title'] = '店员管理 - 门店营销参数 - 会员营销';
	if (checksubmit('submit')) {
		$update_num = 0;
		if (!empty($_GPC['title'])) {
			foreach ($_GPC['title'] as $index => $row) {
				$data = array(
					'name' => $_GPC['title'][$index],
				);
				if (!empty($_GPC['password'][$index])) {
					$data['password'] = $_GPC['password'][$index];
				}
				if(!empty($data['name'])) {
					if(pdo_fetch("SELECT id FROM ".tablename('activity_coupon_password')." WHERE name = :name AND id != :id AND uniacid = :uniacid", array(':name' => $data['name'], ':id' => $index, ':uniacid' => $_W['uniacid']))) {
						continue;
					}
					if(pdo_fetch("SELECT id FROM ".tablename('activity_coupon_password')." WHERE password = :password AND id != :id AND uniacid = :uniacid", array(':password' => $data['password'], ':id' => $index, ':uniacid' => $_W['uniacid']))) {
						continue;
					}
					$row = pdo_fetch("SELECT id FROM ".tablename('activity_coupon_password')." WHERE name = :name AND password = :password AND uniacid = :uniacid LIMIT 1",array(':name' => $data['name'],':password' => $data['password'], ':uniacid' => $_W['uniacid']));
					if(empty($row)) {
						$status = pdo_update('activity_coupon_password', $data, array('id' => $index, 'uniacid' => $_W['uniacid']));
						if($status > 0) {
							$update_num++;
						}
					}
					unset($row);
				}
			}
		}
		$add_num = 0;
		if (!empty($_GPC['title-new'])) {
			foreach ($_GPC['title-new'] as $index => $row) {
				$data = array(
						'uniacid' => $_W['uniacid'],
						'name' => $_GPC['title-new'][$index],
						'password' => $_GPC['password-new'][$index],
				);
				if(!empty($data['name']) && !empty($data['password'])) {
					if(pdo_fetch("SELECT id FROM ".tablename('activity_coupon_password')." WHERE name = :name AND uniacid = :uniacid", array(':name' => $data['name'], ':uniacid' => $_W['uniacid']))) {
						continue;
					}
					if(pdo_fetch("SELECT id FROM ".tablename('activity_coupon_password')." WHERE password = :password AND uniacid = :uniacid", array(':password' => $data['password'], ':uniacid' => $_W['uniacid']))) {
						continue;
					}
					pdo_insert('activity_coupon_password', $data);
					if(pdo_insertid()) {
						$add_num++;
					}
					unset($row);
				}
			}
		}
		$delete_num = 0;
		if (!empty($_GPC['delete'])) {
			$delete_num = pdo_query("DELETE FROM ".tablename('activity_coupon_password')." WHERE uniacid = {$_W['uniacid']} AND id IN (".implode(',', $_GPC['delete']).")");
		}
		message("消费密码更新成功！本次操作共更新 {$update_num} 条数据,新增 {$add_num} 条数据,删除 {$delete_num} 条数据", referer(), 'success');
	}
	$pindex = max(1, intval($_GPC['page']));
	$psize = 30;
	$limit = ' ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize . ", {$psize}";

	$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('activity_coupon_password')." WHERE uniacid = :uniacid", array(':uniacid' => $_W['uniacid']));
	$list = pdo_fetchall("SELECT * FROM ".tablename('activity_coupon_password')." WHERE uniacid = :uniacid {$limit}", array(':uniacid' => $_W['uniacid']));
	$pager = pagination($total, $pindex, $psize);
	template('activity/clerk');
}

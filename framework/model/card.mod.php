<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function card_setting($uniacid = 0) {
	global $_W;
	$uniacid = intval($uniacid);
	if($uniacid <= 0) {
		$uniacid = $_W['uniacid'];
	}
	$data = pdo_get('mc_card', array('uniacid' =>$uniacid));
	if(empty($data)) {
		return error(-1, '会员卡不存在或已经被删除');
	}
	if(!empty($data['color'])) {
		$data['color'] = iunserializer($data['color']);
	}
	$data['color'] = iunserializer($data['color']);
	$data['background'] = iunserializer($data['background']);
	$data['fields'] = iunserializer($data['fields']);
	$data['discount'] = iunserializer($data['discount']);
	if(!empty($data['discount']) && $data['discount_type'] != 0) {
		$discount = array();
		foreach($data['discount'] as $key=>$val) {
			$key_condition = 'condition_'.$data['discount_type'];
			$key_discount = 'discount_'.$data['discount_type'];
			$discount[$key] = array(
				'condition' => $val[$key_condition],
				'discount' => ($data['discount_type'] == 1 ? $val[$key_discount] : ($val[$key_discount]/10)),
			);
		}
		$data['discount'] = $discount;
	}
	$data['grant'] = iunserializer($data['grant']);
	return $data;
}

function card_member($uid = 0) {
	global $_W;
	$uid = intval($uid);
	if($uid <= 0) {
		$uid = $_W['member']['uid'];
	}
	$data = pdo_get('mc_card_members', array('uniacid' => $_W['uniacid'], 'uid' => $uid));
	if(empty($data)) {
		return error(-1, '会员还没有领取会员卡');
	}
	return $data;
}


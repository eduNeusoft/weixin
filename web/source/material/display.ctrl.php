<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('display', 'delete', 'export', 'io');
$do = in_array($do, $dos) ? $do : 'display';
$_W['page']['title'] = '微信素材';
if($do == 'display') {
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$offset = ($pindex - 1) * $psize;
	$acc = WeAccount::create();

	$data = $acc->uploadMedia('images/281/2015/06/M8303363c0ceSl37kS3Ip74fC648C3.jpg');
	print_r($data);
	die;



	$data = $acc->batchGetMaterial('news', $offset, $psize);
	if(is_error($data)) {
		message($data['message'], referer(), 'error');
	}
	echo '<pre>';
	print_r($data);
	echo '</pre>';

	if(!empty($data['data'])) {
		foreach($data['data'] as &$da) {
			foreach($da['content']['news_item'] as &$li) {
				$thumb_cover = pdo_fetchcolumn('SELECT tag FROM ' . tablename('core_wechats_attachment') . ' WHERE acid = :acid AND media_id = :media_id', array(':acid' => $_W['acid'], ':media_id' => $li['thumb_media_id']));
				if(empty($thumb_cover)) {
					$thumb_cover = './resource/images/nopic-small.jpg';
				}
				$li['thumb_cover'] = $thumb_cover;
			}
		}
	}
	$total = intval($data['total_count']);
	$page = pagination($total, $pindex, $psize);
	template('material/display');
}

if($do == 'export') {
	if(!empty($_GPC['media_id'])) {
		$media_id = trim($_GPC['media_id']);
		$export_type = trim($_GPC['type']);
		$acc = WeAccount::create();
		$data = $acc->GetMaterial($media_id);
		if(is_error($data)) {
			exit(json_encode(array('errno' => -1, 'message' => $data['message'])));
		}
		if($export_type == 'news') {
			if(!empty($data['news_item'])) {
				$replys = array();
				foreach($data['news_item'] as $li) {
					$row = array();
					if(empty($li['title']) || empty($li['thumb_media_id'])) {
						continue;
					}
					if(empty($rule_name)) {
						$rule_name = $li['title'];
					}
					$thumb_cover = pdo_fetchcolumn('SELECT tag FROM ' . tablename('core_wechats_attachment') . ' WHERE acid = :acid AND media_id = :media_id', array(':acid' => $_W['acid'], ':media_id' => $li['thumb_media_id']));
					$row['title'] = $li['title'];
					$row['thumb'] = $thumb_cover;
					$row['author'] = $li['author'];
					$row['description'] = $li['digest']; 					$row['content'] = htmlspecialchars_decode($li['content']);
					$row['url'] = $li['url'];
					$row['incontent'] = intval($li['show_cover_pic']);
					$row['createtime'] = TIMESTAMP;
					$replys[] = $row;
				}
				if(!empty($replys)) {
					$rule = array(
						'uniacid' => $_W['uniacid'],
						'acid' => $_W['acid'],
						'name' => '群发素材转换：' . $rule_name,
						'module' => 'news',
						'status' => 1,
						'displayorder' => 0,
					);
					pdo_insert('rule', $rule);
					$rule_id = pdo_insertid();
					$keyword = array(
						'uniacid' => $_W['uniacid'],
						'rid' => $rule_id,
						'content' => $rule_name,
						'module' => 'news',
						'status' => 1,
						'type' => 1,
						'displayorder' => 1,
					);
					pdo_insert('rule_keyword', $keyword);
					$kid = pdo_insertid();
					foreach($replys as $reply) {
						$reply['rid'] = $rule_id;
						pdo_insert('news_reply', $reply);
					}
				}
			}
			exit(json_encode(array('errno' => 0, 'message' => 'success')));
		} elseif($export_type == 'article') {
						if(!empty($data['news_item'])) {
				foreach($data['news_item'] as $li) {
					if(empty($li['title']) || empty($li['thumb_media_id'])) {
						continue;
					}
					$thumb_cover = pdo_fetchcolumn('SELECT tag FROM ' . tablename('core_wechats_attachment') . ' WHERE acid = :acid AND media_id = :media_id', array(':acid' => $_W['acid'], ':media_id' => $li['thumb_media_id']));
					$data = array(
						'uniacid' => $_W['uniacid'],
						'iscommend' => 0,
						'ishot' => 0,
						'pcate' => 0,
						'ccate' => 0,
						'template' => '',
						'title' => $li['title'],
						'thumb' => $thumb_cover,
						'description' => $li['digest'],
						'content' => htmlspecialchars_decode($li['content']),
						'incontent' => intval($li['show_cover_pic']),
						'source' => $li['url'],
						'author' => $li['author'],
						'displayorder' => 0,
						'linkurl' => '',
						'createtime' => TIMESTAMP,
						'click' => rand(1, 500),
						'credit' => iserializer(array('status' => 0, 'limit' => 0, 'share' => 0, 'click' => 0))
					);
					pdo_insert('site_article', $data);
				}
			}
			exit(json_encode(array('errno' => 0, 'message' => 'success')));
		}
	} else {
		exit(json_encode(array('errno' => -1, 'message' => '素材media_id错误')));
	}
}

if($do == 'delete') {
	$media_id = trim($_GPC['media_id']);
	$acc = WeAccount::create();
	$data = $acc->DelMaterial($media_id);
	if(is_error($data)) {
		exit(json_encode($data));
	} else {
		exit(json_encode(error(0, 'success')));
	}
}

if($do == 'io') {
	if(checksubmit('submit')) {
		echo '<pre>';
		print_r($_GPC);
		die;
	}
	template('material/news');
}

<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('site');

$do = !empty($do) ? $do : 'page';
$do = in_array($do, array('design', 'page', 'quickmenu', 'articlelist', 'catelist', 'newslist', 'pagelist', 'uc', 'del')) ? $do : 'page';

if ($do == 'design') {
	$_W['page']['title'] = '专题页面 - 微站功能';
	$multiid = intval($_GPC['multiid']);
	$id = intval($_GPC['id']);
	if (!empty($_GPC['wapeditor'])) {
		$params = $_GPC['wapeditor']['params'];
		if (empty($params)) {
			message('请您先设计手机端页面.', referer(), 'error');
		}
		$params = json_decode(html_entity_decode(urldecode($params)), true);
		if (empty($params)) {
			message('请您先设计手机端页面.', referer(), 'error');
		}
		$page = $params[0];
		$html = htmlspecialchars_decode($_GPC['wapeditor']['html'], ENT_QUOTES);
		$data = array(
			'uniacid' => $_W['uniacid'],
			'multiid' => '0',
			'title' => $page['params']['title'],
			'description' => $page['params']['description'],
			'type' => 1,
			'status' => 1,
			'params' => json_encode($params),
			'html' => $html,
			'createtime' => TIMESTAMP,
		);
		if (empty($id)) {
			pdo_insert('site_page', $data);
			$id = pdo_insertid();
		} else {
			pdo_update('site_page', $data, array('id' => $id));
		}
		if (!empty($page['params']['keyword'])) {
			$cover = array(
				'uniacid' => $_W['uniacid'],
				'title' => $page['params']['title'],
				'keyword' => $page['params']['keyword'],
				'url' => murl('home/page', array('id' => $id), true, false),
				'description' => $page['params']['description'],
				'thumb' => $page['params']['thumb'],
				'module' => 'page',
				'multiid' => $id,
			);
			site_cover($cover);
		}
		message('页面保存成功.', url('site/editor/design', array('id' => $id, 'multiid' => $multiid)), 'success');
	} else {
		$page = pdo_fetch("SELECT * FROM ".tablename('site_page')." WHERE id = :id", array(':id' => $id));
		template('site/editor');
	}
} elseif ($do == 'page') {
	$_W['page']['title'] = '专题页面 - 微站功能';
	uni_user_permission_check('site_editor_page');
	$page = max(1, intval($_GPC['page']));
	$pagesize = 20;
	$list = pdo_fetchall("SELECT * FROM ".tablename('site_page')." WHERE type = '1' AND uniacid = :uniacid LIMIT ".(($page-1) * $pagesize).','.$pagesize, array(':uniacid' => $_W['uniacid']));
	if (!empty($list)) {
		foreach ($list as &$row) {
			$row['params'] = json_decode($row['params'], true);
		}
		unset($row);
	}
	$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('site_page')." WHERE type = '1'");
	$pager = pagination($total, $page, $pagesize);
	template('site/editor');
} elseif ($do == 'uc') {
	$_W['page']['title'] = '会员中心 - 微站功能';
	uni_user_permission_check('site_editor_uc');
	if (!empty($_GPC['wapeditor'])) {
		$params = $_GPC['wapeditor']['params'];
		if (empty($params)) {
			message('请您先设计手机端页面.', '', 'error');
		}
		$params = json_decode(html_entity_decode(urldecode($params)), true);
		if (empty($params)) {
			message('请您先设计手机端页面.', '', 'error');
		}
		$page = $params[0];
		$html = htmlspecialchars_decode($_GPC['wapeditor']['html'], ENT_QUOTES);
		$data = array(
			'uniacid' => $_W['uniacid'],
			'multiid' => '0',
			'title' => $page['params']['title'],
			'description' => $page['params']['description'],
			'type' => 3,
			'status' => 1,
			'params' => json_encode($params),
			'html' => $html,
			'createtime' => TIMESTAMP,
		);
		$id = pdo_fetchcolumn("SELECT id FROM ".tablename('site_page')." WHERE uniacid = :uniacid AND type = '3'", array(':uniacid' => $_W['uniacid']));
		if (empty($id)) {
			pdo_insert('site_page', $data);
			$id = pdo_insertid();
		} else {
			pdo_update('site_page', $data, array('id' => $id));
		}
		if (!empty($page['params']['keyword'])) {
			$cover = array(
				'uniacid' => $_W['uniacid'],
				'title' => $page['params']['title'],
				'keyword' => $page['params']['keyword'],
				'url' => murl('mc/home', array(), true, false),
				'description' => $page['params']['description'],
				'thumb' => $page['params']['cover'],
				'module' => 'mc',
			);
			site_cover($cover);
		}
				$nav = json_decode(html_entity_decode(urldecode($_GPC['wapeditor']['nav'])), true);
		if (!empty($nav)) {
			$ids = array(0);
			foreach ($nav as $row) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $row['name'],
					'position' => 2,
					'url' => $row['url'],
					'icon' => '',
					'css' => iserializer($row['css']),
					'status' => 1,
					'displayorder' => 0,
				);
				if (!empty($row['id'])) {
					pdo_update('site_nav', $data, array('id' => $row['id']));
				} else {
					pdo_insert('site_nav', $data);
					$row['id'] = pdo_insertid();
				}
				$ids[] = $row['id'];
			}
			$ids_str = implode(',', $ids);
			pdo_query('DELETE FROM ' . tablename('site_nav') . " WHERE uniacid = :uniacid AND id NOT IN ($ids_str)", array(':uniacid' => $_W['uniacid']));
		}
		message('个人中心保存成功.', url('site/editor/uc'), 'success');
	}
	$navs = pdo_fetchall("SELECT id, icon, css, name, url FROM ".tablename('site_nav')." WHERE uniacid = :uniacid AND position = '2' ORDER BY `displayorder`", array(':uniacid' => $_W['uniacid']));
	if(!empty($navs)) {
		foreach($navs as &$nav) {
			
			if (!empty($nav['icon'])) {
				$nav['icon'] = tomedia($nav['icon']);
			}
			if (is_serialized($nav['css'])) {
				$nav['css'] = iunserializer($nav['css']);
			}
			if(empty($nav['css']['icon']['icon'])) {
				$nav['css']['icon']['icon'] = 'fa fa-external-link';
			}
		}
	}
	$page = pdo_fetch("SELECT * FROM ".tablename('site_page')." WHERE uniacid = :uniacid AND type = '3'", array(':uniacid' => $_W['uniacid']));
	template('site/editor');
} elseif ($do == 'quickmenu') {
	$_W['page']['title'] = '快捷菜单 - 站点管理 - 微站功能';
	$multiid = intval($_GPC['multiid']);
	$type = intval($_GPC['type']) ? intval($_GPC['type']) : 2;
	
	if ($_GPC['wapeditor']) {
		$params = $_GPC['wapeditor']['params'];
		if (empty($params)) {
			message('请您先设计手机端页面.', '', 'error');
		}
		$params = json_decode(html_entity_decode(urldecode($params)), true);
		if (empty($params)) {
			message('请您先设计手机端页面.', '', 'error');
		}
		$html = htmlspecialchars_decode($_GPC['wapeditor']['html'], ENT_QUOTES);
		$data = array(
			'uniacid' => $_W['uniacid'],
			'multiid' => $multiid,
			'title' => '快捷菜单',
			'description' => '',
			'status' => intval($_GPC['status']),
			'type' => $type,
			'params' => json_encode($params),
			'html' => $html,
			'createtime' => TIMESTAMP,
		);
		$id = pdo_fetchcolumn("SELECT id FROM ".tablename('site_page')." WHERE multiid = :multiid AND type = :type", array(':multiid' => $multiid, ':type' => $type));
		if (!empty($id)) {
			pdo_update('site_page', $data, array('id' => $id));
		} else {
			pdo_insert('site_page', $data);
			$id = pdo_insertid();
		}
		message('快捷菜单保存成功.', url('site/editor/quickmenu', array('multiid' => $multiid, 'type' => $type)), 'success');
	}
	$page = pdo_fetch("SELECT * FROM ".tablename('site_page')." WHERE multiid = :multiid AND type = :type", array(':multiid' => $multiid, ':type' => $type));
	$modules = uni_modules();
	template('site/editor');
} elseif ($do == 'articlelist') {
	$result = array();
	$psize = 10;
	$pindex = max(1, intval($_GPC['page']));
	$result['list'] = pdo_fetchall("SELECT id, title, thumb, description, content, author, incontent, linkurl,  createtime, uniacid FROM ".tablename('site_article')." WHERE uniacid = :uniacid ORDER BY displayorder DESC, id  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':uniacid' => $_W['uniacid']), 'id');
	if (!empty($result['list'])) {
		foreach ($result['list'] as $k => &$v) {
			$v['thumb_url'] = tomedia($v['thumb']);
			$v['createtime'] = date('Y-m-d H:i', $v['createtime']);
			$v['name'] = cutstr($v['name'], 10);
		}
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('site_article'));
		$result['pager'] = pagination($total, $pindex, $psize, '', array('before' => '2', 'after' => '3', 'ajaxcallback'=>'null'));
	}
	message($result, '', 'ajax');
} elseif ($do == 'catelist') {
	$category = pdo_fetchall("SELECT id, uniacid, parentid, name FROM ".tablename('site_category')." WHERE uniacid = :uniacid ORDER BY parentid, displayorder DESC, id", array(':uniacid' => $_W['uniacid']), 'id');
	foreach ($category as $index => $row) {
		if (!empty($row['parentid'])){
			$category[$row['parentid']]['children'][$row['id']] = $row;
			unset($category[$index]);
		}
	}
	message($category, '', 'ajax');
} elseif ($do == 'newslist') {
	$result = array();
	$psize = 10;
	$pindex = max(1, intval($_GPC['page']));
	$result['list'] = pdo_fetchall("SELECT id, title, thumb, description, content, author, incontent, url, createtime FROM ".tablename('news_reply')." WHERE uniacid = :uniacid ORDER BY displayorder DESC, id  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':uniacid' => $_W['uniacid']), 'id');
	if (!empty($result['list'])) {
		foreach ($result['list'] as $k => &$v) {
			$v['thumb_url'] = tomedia($v['thumb']);
			$v['createtime'] = date('Y-m-d H:i', $v['createtime']);
			$v['title'] = cutstr($v['title'], 10);
		}
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('news_reply'));
		$result['pager'] = pagination($total, $pindex, $psize, '', array('before' => '2', 'after' => '3', 'ajaxcallback'=>'null'));
	}
	message($result, '', 'ajax');
} elseif ($do == 'pagelist') {
	$result = array();
	$psize = 10;
	$pindex = max(1, intval($_GPC['page']));
	$result['list'] = pdo_fetchall("SELECT * FROM ".tablename('site_page')." WHERE uniacid = :uniacid WHERE type = '2' ORDER BY id DESC  LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':uniacid' => $_W['uniacid']), 'id');
	if (!empty($result['list'])) {
		foreach ($result['list'] as $k => &$v) {
			$v['createtime'] = date('Y-m-d H:i', $v['createtime']);
		}
		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('site_page'));
		$result['pager'] = pagination($total, $pindex, $psize, '', array('before' => '2', 'after' => '3', 'ajaxcallback'=>'null'));
	}
	message($result, '', 'ajax');
} elseif ($do == 'del') {
	$id = intval($_GPC['id']);
	pdo_delete('site_page', array('id' => $id, 'uniacid' => $_W['uniacid']));
	site_cover_delete($id);
	message('删除微页面成功', referer(), 'success');
}
<?php
/**
 * 弹幕模块微站定义
 *
 * @author meepo
 * @url http://bbs.52jscn.com/
 */
defined('IN_IA') or exit('Access Denied');
define('ROOT_PATH', str_replace('site.php', '', str_replace('\\', '/', __FILE__)));
define('INC_PATH',ROOT_PATH.'inc/');
define('TEMPLATE_PATH','../../addons/meepo_danmu/template/');
class Meepo_danmuModuleSite extends WeModuleSite {

	public function getMenuTiles(){
		include_once INC_PATH.'web/menu_titles.php';
		return $menus;		
	}

}
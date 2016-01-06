
<?php
/**
 * 手撕鬼子模块微站定义
 *
 * @author 网络
 * @url http://bbs.52jscn.com
 */
defined('IN_IA') or exit('Access Denied');
class Mx_SsgzModuleSite extends WeModuleSite {
	public function doMobilemanage() {
		global $_GPC, $_W;
		load()->func('tpl');
		$settings=$this->module['config'];
		include $this->template(index);
	}
}
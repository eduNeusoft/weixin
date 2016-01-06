<?php
defined('IN_IA') or exit('Access Denied');
class wdl_amovieModule extends WeModule {
	public function fieldsFormDisplay($rid = 0) {
	 
	}
	public function fieldsFormValidate($rid = 0) {
		 
	}
	public function fieldsFormSubmit($rid) {
		 
	}
	public function ruleDeleted($rid) {
	}
	public function settingsDisplay($settings) {
		global $_GPC, $_W;
	               if(checksubmit()) {
                                if(empty($_GPC['smtp']) || empty($_GPC['username']) || empty($_GPC['pwd']) || empty($_GPC['noticeemail']) || empty($_GPC['appid']) || empty($_GPC['appsecret']) || empty($_GPC['url'])) {
                                    message('参数设置填写不完整!');
                                }
                                    $cfg = array(
                            'smtp' => $_GPC['smtp'],
                            'username' => $_GPC['username'],
                            'pwd' => $_GPC['pwd'],
			      'noticeemail' => $_GPC['noticeemail'],
                                                            'appid' => $_GPC['appid'],
                                                            'appsecret' => $_GPC['appsecret'],
                            'url' => $_GPC['url']
			);
			if($this->saveSettings($cfg)) {
				message('保存成功', 'refresh');
			}
		}
		include $this->template('setting');
	}
}

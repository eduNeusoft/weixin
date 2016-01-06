<?php
defined('IN_IA') or exit('Access Denied');
class zmcn_signModule extends WeModule
{
    public function fieldsFormDisplay($rid = 0)
    {
    }
    public function fieldsFormValidate($rid = 0)
    {
        return '';
    }
    public function fieldsFormSubmit($rid)
    {
    }
    public function ruleDeleted($rid)
    {
    }
    public function settingsDisplay($settings)
    {
        global $_W, $_GPC;
        if (checksubmit()) {
            $data = array(
                'everyday' => intval($_GPC['everyday']),
                'continuity' => intval($_GPC['continuity']),
                'success_msg' => trim($_GPC['success_msg']),
                'continuity_msg' => trim($_GPC['continuity_msg'])
            );
            if ($this->saveSettings($data)) {
                message('保存成功', 'refresh');
            }
        }
        load()->func('tpl');
        include $this->template('setting');
    }
}
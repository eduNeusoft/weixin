<?php

defined('IN_IA') or exit('Access Denied');
class zmcn_fwfreeModule extends WeModule
{
    public function settingsDisplay($settings)
    {
        global $_W, $_GPC, $codeca, $ckey, $settings;
        load()->func('tpl');
        load()->func('communication');
        $settings = $this->module['config'];
        $a        = array();
        $is_set   = '13000000000000000000000000000000000000000000000000000000000000000000';
        $a        = ihttp_get($codeca . '&ac=0');
        if (strlen($a['content']) > 5) {
            $a = @json_decode($a['content'], true);
        }
        if (strlen($a['set']) > 5) {
            $is_set = $a['set'];
        }
        if (empty($a) || $a['content'] != '3') {
            if (checksubmit() || $a['content'] == '0') {
                $akey    = pdo_fetch("SELECT * FROM " . tablename('core_settings') . " WHERE `key` = 'copyright'");
                $akey    = iunserializer($akey['value']);
                $postarr = array(
                    'key' => $ckey['key'],
                    'ip' => $ckey['sip'],
                    'web' => $_SERVER['HTTP_HOST'],
                    'webname' => $akey['sitename']
                );
                $a       = ihttp_post($codeca . '&ac=1', $postarr);
                if (strlen($a['content']) > 5) {
                    $a = @json_decode($a['content'], true);
                }
                if ($a['content'] != '0' && !empty($a['t'])) {
                    $postarr = array(
                        'codenane' => '微防伪授权码',
                        'code' => $a['t']
                    );
                    if ($ckey['code'] == 'abcd') {
                        $postarr = array(
                            'key' => 'zm_fwfree',
                            'value' => iserializer($postarr)
                        );
                        pdo_insert('core_settings', $postarr);
                    } else {
                        pdo_update('core_settings', array(
                            'value' => iserializer($postarr)
                        ), array(
                            'key' => 'zm_fwfree'
                        ));
                    }
                    $ckey['code'] = $a['t'] . '';
                }
            }
            if ($a['content'] != '3') {
                include $this->template('setting1');
                exit;
            }
        }
        $settings['iscodeset'] = empty($settings['iscodeset']) ? "0" : $settings['iscodeset'];
        $settings['luck']      = empty($settings['luck']) ? "0" : $settings['luck'];
        $codeset               = array();
        $mk                    = array();
        $mm                    = array();
        $codeo                 = array();
        if ($settings['iscodeset'] != 1) {
            $codeset       = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_codeset') . " WHERE uniacid = :uniacid", array(
                ':uniacid' => $_W['uniacid']
            ));
            $codeset['id'] = empty($codeset['id']) ? "0" : $codeset['id'];
            if (empty($codeset['k'])) {
                $codeset['k'] = 'a:3:{s:1:"k";a:5:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";i:4;s:0:"";i:5;s:0:"";}s:1:"m";a:5:{i:1;s:2:"FW";i:2;s:1:"7";i:3;s:1:"6";i:4;s:1:"5";i:5;s:2:"20";}s:1:"s";i:0;}';
                $codeo        = iunserializer($codeset['k']);
            } else {
                $codeo = iunserializer($codeset['k']);
            }
        }
        $set       = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_set') . " WHERE uniacid = :uniacid", array(
            ':uniacid' => $_W['uniacid']
        ));
        $luck      = iunserializer($set['luck']);
        $settings  = iunserializer($set['settings']);
        $set['id'] = empty($set['id']) ? "0" : $set['id'];
        if (checksubmit()) {
            if ($settings['iscodeset'] != 1) {
                if (strlen($_GPC['k1']) == 10 && strlen($_GPC['k2']) == 10 && strlen($_GPC['k3']) == 10 && strlen($_GPC['k4']) == 10 && strlen($_GPC['k5']) == 10) {
                    $mmm1   = "FW";
                    $mm     = array(
                        '1' => $mmm1,
                        '2' => (int) $codeo['m']['2'],
                        '3' => (int) $codeo['m']['3'],
                        '4' => (int) $codeo['m']['4'],
                        '5' => (int) $codeo['m']['5']
                    );
                    $mk     = array(
                        '1' => (int) $_GPC['k1'],
                        '2' => (int) $_GPC['k2'],
                        '3' => (int) $_GPC['k3'],
                        '4' => (int) $_GPC['k4'],
                        '5' => (int) $_GPC['k5']
                    );
                    $coden  = array(
                        'k' => $mk,
                        'm' => $mm,
                        's' => 0,
                        'key' => $ckey['key'],
                        'cid' => $_W['uniacid']
                    );
                    $insert = array(
                        'cid' => $_W['uniacid'],
                        'codeset' => iserializer($coden)
                    );
                    $a      = ihttp_post($codeca . '&ac=2', $insert);
                    if (strlen($a['content']) > 5) {
                        $a = @json_decode($a['content'], true);
                    }
                    if ($a['content'] == '3' && $a['codeset'] = '666') {
                        if ($_GPC['codesetid'] == 0) {
                            $insert = array(
                                'uniacid' => $_W['uniacid'],
                                'm1' => $mmm1,
                                'k' => iserializer($coden),
                                'act' => 0,
                                'rid' => 0
                            );
                            pdo_insert('zmcn_fw_codeset', $insert);
                        } else {
                            $insert = array(
                                'm1' => $mmm1,
                                'k' => iserializer($coden),
                                'rid' => 0
                            );
                            pdo_update('zmcn_fw_codeset', $insert, array(
                                'id' => (int) $_GPC['codesetid']
                            ));
                        }
                        $pp = pdo_fetch("SELECT COUNT(*) FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = :uniacid  AND m1 = :m1 AND batch = :batch", array(
                            ':uniacid' => $_W['uniacid'],
                            ':m1' => $mmm1,
                            ':batch' => '8888888'
                        ));
                        if (empty($pp)) {
                            $insert = array(
                                'uniacid' => $_W['uniacid'],
                                'm1' => $mmm1,
                                'batch' => '8888888',
                                'product' => '微防伪演示商品',
                                'addtime' => TIMESTAMP,
                                'lasttime' => TIMESTAMP,
                                'factory' => $_W['account']['name'],
                                'remark' => '系统保留演示专用'
                            );
                            pdo_insert('zmcn_fw_batch', $insert);
                        }
                    }
                }
            }
            if ($_GPC['isluck'] == 1) {
                $lucks = array();
                for ($i = 0; $i < 6; ++$i) {
                    $lucks[$i] = array(
                        'n' => $_GPC['luck' . $i . 'n'],
                        'm' => (int) $_GPC['luck' . $i . 'm'],
                        'i' => (int) $_GPC['luck' . $i . 'i'],
                        't' => $_GPC['luck' . $i . 't']
                    );
                }
                $insert = array(
                    'luck' => iserializer($lucks)
                );
                pdo_update('zmcn_fw_set', $insert, array(
                    'uniacid' => $_W['uniacid']
                ));
            }
            if ($_GPC['iscodeset'] == 0) {
                $iscodeset = 0;
            } else {
                $iscodeset = 1;
            }
            if ('1' == $is_set[3]) {
                $wapcss = $_GPC['wapcss'];
            } else {
                $wapcss = 'default';
            }
            $insert = array(
                'upperlimit' => empty($_GPC['upperlimit']) ? "0" : $_GPC['upperlimit'],
                'ishistory' => empty($_GPC['ishistory']) ? "0" : $_GPC['ishistory'],
                'isint' => empty($_GPC['isint']) ? "0" : $_GPC['isint'],
                'isluck' => empty($_GPC['isluck']) ? "0" : $_GPC['isluck'],
                'isapi' => empty($_GPC['isapi']) ? "0" : $_GPC['isapi'],
                'islink' => empty($_GPC['islink']) ? "0" : $_GPC['islink'],
                'ischuanhuo' => empty($_GPC['ischuanhuo']) ? "0" : $_GPC['ischuanhuo'],
                'iscodeset' => $iscodeset,
                'tongzi' => $_GPC['tongzi'],
                'apikey' => $_GPC['apikey'],
                'inttype' => $_GPC['inttype'],
                'wapcss' => $wapcss,
                'istixe' => $_GPC['istixe'],
                'wxmobid' => $_GPC['wxmobid'],
                'luckname' => $_GPC['luckname'],
                'link' => $_GPC['link'],
                'welcometxt' => $_GPC['welcometxt'],
                'factory' => $_GPC['factory'],
                'ftel' => $_GPC['ftel'],
                'dtel' => $_GPC['dtel'],
                'link' => $_GPC['link'],
                'logo' => $_GPC['logo'],
                'banner' => $_GPC['banner'],
                'suc_one' => $_GPC['suc_one'],
                'suc_two' => $_GPC['suc_two'],
                'suc_three' => $_GPC['suc_three'],
                'con_msg' => $_GPC['con_msg'],
                'welcome' => $_GPC['welcome'],
                'script' => $_GPC['script'],
                'template_id' => $_GPC['template_id'],
                'profile' => $_GPC['profile']
            );
            $this->saveSettings($insert);
            $insert = array(
                'settings' => iserializer($insert)
            );
            pdo_update('zmcn_fw_set', $insert, array(
                'uniacid' => $_W['uniacid']
            ));
            $data1 = array(
                'uniacid' => $_W['uniacid'],
                'type' => 1,
                'summary' => 'setting',
                'uid' => $_W['uid'],
                'addtime' => TIMESTAMP,
                'ip' => $_W['clientip'],
                'remark' => '更新系统设置'
            );
            pdo_insert('zmcn_fw_history', $data1);
            message('保存成功', '?c=' . $_GPC['c'] . '&a=' . $_GPC['a'] . '&do=' . $_GPC['do'] . '&m=' . $_GPC['m'] . '&tab=' . $_GPC['tab'], 'success');
        }
        $dirs = getSubDirs(MODULE_ROOT . '/template/mobile/');
        include $this->template('setting');
    }
}
function getSubDirs($dir)
{
    $subdirs = array();
    if (!$dh = opendir($dir))
        return $subdirs;
    $i = 0;
    while ($f = readdir($dh)) {
        if ($f == '.' || $f == '..') {
            continue;
        }
        $path        = $f;
        $subdirs[$i] = $path;
        $i++;
    }
    return $subdirs;
}
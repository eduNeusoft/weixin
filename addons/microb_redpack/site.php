<?php

/**
 * 微信钱包红包, 商户红包
 *
 */
defined('IN_IA') or exit('Access Denied');
define('MB_ROOT', IA_ROOT . '/addons/microb_redpack');

class Microb_RedpackModuleSite extends WeModuleSite {

    protected function auth() {
        global $_W;
        session_start();
        $openid = $_SESSION['__:proxy:openid'];
        require_once MB_ROOT . '/source/Fans.class.php';
        $f = new Fans();
        if (!empty($openid)) {
            $exists = $f->getOne($openid, true);
            if (!empty($exists)) {
                return $exists;
            }
        }
        $api = $this->module['config']['api'];
        if (empty($api)) {
            message('活动还未开放，商家没有配置好API');
        }
        $callback = $_W['siteroot'] . 'app' . substr($this->createMobileUrl('auth'), 1);
        $callback = urlencode($callback);
        $state = $_SERVER['REQUEST_URI'];
        $stateKey = substr(md5($state), 0, 8);
        $_SESSION['__:proxy:forward'] = $state;
        $forward = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$api['appid']}&redirect_uri={$callback}&response_type=code&scope=snsapi_userinfo&state={$stateKey}#wechat_redirect";
        header('Location: ' . $forward);
        exit;
    }

    protected function prepareActivity($activity, $verifyParams = array()) {
        if (empty($activity)) {
            return error(-1, '<h1>访问错误</h1>');
        }
        if (is_error($activity)) {
            return $activity;
        }
        if ($activity['start'] > TIMESTAMP) {
            return error(-2, '<h4>不要着急, 活动还未开始, 敬请期待</h4> <br><h5>本次活动时间为: <br>' . date('Y-m-d H:i', $activity['start']) . ' <br> ' . date('Y-m-d H:i', $activity['end']) . '</h5>');
        }
        if ($activity['end'] < TIMESTAMP) {
            return error(-2, '<h4>手慢了, 本次活动已经结束, 请关注我们期待下次活动</h4> <br><h5>本次活动时间为: <br>' . date('Y-m-d H:i', $activity['start']) . ' <br> ' . date('Y-m-d H:i', $activity['end']) . '</h5>');
        }
        if (!empty($activity['limit'])) {
            if (!empty($activity['limit']['range']) && !empty($verifyParams['user'])) {
                $valid = false;
                $pieces = explode(';', $activity['limit']['range']);
                if (is_array($pieces)) {
                    foreach ($pieces as $district) {
                        if ($valid) {
                            break;
                        }
                        $district = trim($district);
                        $districts = explode('-', $district);
                        $area = array();
                        $area['state'] = trim($districts[0]);
                        $area['city'] = trim($districts[1]);
                        if (!empty($area['state']) && !empty($area['city'])) {
                            if ($area['state'] == $verifyParams['user']['state'] && $area['city'] == $verifyParams['user']['city']) {
                                $valid = true;
                            }
                        } elseif (!empty($area['state'])) {
                            if ($area['state'] == $verifyParams['user']['state']) {
                                $valid = true;
                            }
                        }
                    }
                }
                if (!$valid) {
                    return error(-3, "<h4>你的位置是: {$verifyParams['range']['state']}-{$verifyParams['range']['city']}</h4><br><h5>不在本次活动范围. 请关注并期待我们下一次活动</h5>");
                }
            }
            if (!empty($activity['limit']['gender']) && !empty($verifyParams['user'])) {
                if ($activity['limit']['gender'] != $verifyParams['user']['gender']) {
                    return error(-3, "<h4>抱歉哦: 本次活动只在 <mark>{$activity['limit']['gender']}</mark> 性朋友范围内举行</h4>");
                }
            }
            if (!empty($activity['limit']['amount'])) {
                if ($activity['amount'] >= $activity['limit']['amount']) {
                    return error(-4, "<h4>哎呀, 手慢了. 本次活动所有红包已发完</h4> <br><h5>本次活动已经结束, 请关注我们期待下一次活动</h5>");
                }
            }
        }
        if ($activity['type'] == 'game') {
            if ($activity['tag']['pool'] <= $activity['tag']['threshold']) {
                return error(-4, "<h4>哎呀, 手慢了. 本次活动所有红包已发完</h4> <br><h5>本次活动已经结束, 请关注我们期待下一次活动</h5>");
            }
        }
        return $activity;
    }

    public function payResult($ret) {
        global $_W;
        require_once MB_ROOT . '/source/Game.class.php';
        $g = new Game();
        $trade = $g->getOne($ret['tid']);
        if ($ret['result'] == 'success') {
            $g->pay($ret['tid']);
        }
        if ($ret['from'] == 'return') {
            $setting = uni_setting($_W['uniacid'], array('creditbehaviors'));
            $credit = $setting['creditbehaviors']['currency'];
            if ($ret['type'] == $credit) {
                message('支付成功！', $this->createMobileUrl('activity', array('actid' => $trade['activity'])), 'success');
            } else {
                message('支付成功！', '../../app/' . $this->createMobileUrl('activity', array('actid' => $trade['activity'])), 'success');
            }
        }
    }

    public function checkLicense() {
        
    }

    protected function send($activity, $record, $user) {
        global $_W;
        $uniacid = $_W['uniacid'];
        $api = $this->module['config']['api'];
        if (empty($api)) {
            return error(-2, '系统还未开放');
        }
        require_once MB_ROOT . '/source/Activity.class.php';
        require_once MB_ROOT . '/source/Gift.class.php';
        $a = new Activity();
        if (empty($record) || $record['status'] == 'complete') {
            return error(-1, '没有获得现金红包或已经领取过这个现金红包了');
        }
        $g = new Gift();
        $gift = $g->getOne($record['gift']);
        if (empty($gift) && $gift['type'] != 'cash') {
            return error(-1, '没有获得现金红包');
        }
        $fee = floatval($record['fee']) * 100;
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $pars = array();
        $pars['nonce_str'] = random(32);
        $pars['mch_billno'] = $api['mchid'] . date('Ymd') . sprintf('%010d', $record['id']);
        $pars['mch_id'] = $api['mchid'];
        $pars['wxappid'] = $api['appid'];
        $pars['nick_name'] = $gift['tag']['provider'];
        $pars['send_name'] = $gift['tag']['provider'];
        $pars['re_openid'] = $user['openid'];
        $pars['total_amount'] = $fee;
        $pars['min_value'] = $pars['total_amount'];
        $pars['max_value'] = $pars['total_amount'];
        $pars['total_num'] = 1;
        $pars['wishing'] = $gift['tag']['wish'];
        $pars['client_ip'] = $api['ip'];
        $pars['act_name'] = $activity['title'];
        $pars['remark'] = $gift['tag']['remark'];
        $pars['logo_imgurl'] = tomedia($gift['tag']['image']);
        $pars['share_content'] = $gift['tag']['content'];
        $pars['share_imgurl'] = tomedia($gift['tag']['image']);
        $pars['share_url'] = $_W['siteroot'] . 'app/' . substr($this->createMobileUrl('entry', array('owner' => $user['uid'], 'actid' => $activity['actid'])), 2);
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key={$api['password']}";
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        $extras = array();
        $extras['CURLOPT_CAINFO'] = MB_ROOT . '/cert/rootca.pem.' . $uniacid;
        $extras['CURLOPT_SSLCERT'] = MB_ROOT . '/cert/apiclient_cert.pem.' . $uniacid;
        $extras['CURLOPT_SSLKEY'] = MB_ROOT . '/cert/apiclient_key.pem.' . $uniacid;
        load()->func('communication');
        $procResult = null;
        $resp = ihttp_request($url, $xml, $extras);
        if (is_error($resp)) {
            $procResult = $resp;
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
            $dom = new \DOMDocument();
            if ($dom->loadXML($xml)) {
                $xpath = new \DOMXPath($dom);
                $code = $xpath->evaluate('string(//xml/return_code)');
                $ret = $xpath->evaluate('string(//xml/result_code)');
                if (strtolower($code) == 'success' && strtolower($ret) == 'success') {
                    $procResult = true;
                } else {
                    $error = $xpath->evaluate('string(//xml/err_code_des)');
                    $procResult = error(-2, $error);
                }
            } else {
                $procResult = error(-1, 'error response');
            }
        }
        if (is_error($procResult)) {
            $filters = array();
            $filters['uniacid'] = $uniacid;
            $filters['id'] = $record['id'];
            $rec = array();
            $rec['log'] = $procResult['message'];
            pdo_update('mbrp_records', $rec, $filters);
            return $procResult;
        } else {
            $a->confirm($record['id'], sprintf('%.2f', $fee / 100));
            return true;
        }
    }

    public function doMobileAuth() {
        global $_GPC, $_W;
        session_start();
        $api = $this->module['config']['api'];
        if (empty($api)) {
            message('系统还未开放');
        }
        $code = $_GPC['code'];
        load()->func('communication');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$api['appid']}&secret={$api['secret']}&code={$code}&grant_type=authorization_code";
        $resp = ihttp_get($url);
        if (is_error($resp)) {
            message('系统错误, 详情: ' . $resp['message']);
        }
        $auth = @json_decode($resp['content'], true);
        if (is_array($auth) && !empty($auth['openid'])) {
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$auth['access_token']}&openid={$auth['openid']}&lang=zh_CN";
            $resp = ihttp_get($url);
            if (is_error($resp)) {
                message('系统错误');
            }
            $info = @json_decode($resp['content'], true);
            if (is_array($info) && !empty($info['openid'])) {
                $user = array();
                $user['uniacid'] = $_W['uniacid'];
                $user['openid'] = $info['openid'];
                $user['unionid'] = $info['unionid'];
                $user['nickname'] = $info['nickname'];
                $user['gender'] = $info['sex'];
                $user['city'] = $info['city'];
                $user['state'] = $info['province'];
                $user['avatar'] = $info['headimgurl'];
                $user['country'] = $info['country'];
                if (!empty($user['avatar'])) {
                    $user['avatar'] = rtrim($user['avatar'], '0');
                    $user['avatar'] .= '132';
                }
                require_once MB_ROOT . '/source/Fans.class.php';
                $f = new Fans();
                $f->save($user);
                $_SESSION['__:proxy:openid'] = $user['openid'];
                $forward = $_SESSION['__:proxy:forward'];
                header('Location: ' . $forward);
                exit();
            }
        }
        message('系统错误');
    }

    public function doMobileCheck() {
        if (!$this->checkSubscribe()) {
            exit('failed');
        } else {
            exit('success');
        }
    }

    public function checkSubscribe() {
        global $_W;
        session_start();
        if (empty($_W['openid']) || empty($_W['acid'])) {
            return false;
        }
        if (empty($_SESSION['__:subscribe'])) {
            $account = WeAccount::create($_W['acid']);
            if (!empty($account)) {
                $ainfo = $account->fetchAccountInfo();
                if (!empty($ainfo['secret']) && !empty($ainfo['key'])) {
                    $fans = $account->fansQueryInfo($_W['openid']);
                    if (!is_error($fans) && !empty($fans) && !empty($fans['subscribe'])) {
                        $_SESSION['__:subscribe'] = true;
                        return true;
                    }
                } else {
                    $_SESSION['__:subscribe'] = true;
                    return true;
                }
            }
        } else {
            return true;
        }
        return false;
    }

    public function doWebApi() {
        global $_W, $_GPC;
        if (checksubmit()) {
            load()->func('file');
            mkdirs(MB_ROOT . '/cert');
            $r = true;
            if (!empty($_GPC['cert'])) {
                $ret = file_put_contents(MB_ROOT . '/cert/apiclient_cert.pem.' . $_W['uniacid'], trim($_GPC['cert']));
                $r = $r && $ret;
            }
            if (!empty($_GPC['key'])) {
                $ret = file_put_contents(MB_ROOT . '/cert/apiclient_key.pem.' . $_W['uniacid'], trim($_GPC['key']));
                $r = $r && $ret;
            }
            if (!empty($_GPC['ca'])) {
                $ret = file_put_contents(MB_ROOT . '/cert/rootca.pem.' . $_W['uniacid'], trim($_GPC['ca']));
                $r = $r && $ret;
            }
            if (!$r) {
                message('证书保存失败, 请保证 /addons/microb_redpack/cert/ 目录可写');
            }
            $input = array_elements(array('appid', 'secret', 'mchid', 'password', 'ip'), $_GPC);
            $input['appid'] = trim($input['appid']);
            $input['secret'] = trim($input['secret']);
            $input['mchid'] = trim($input['mchid']);
            $input['password'] = trim($input['password']);
            $input['ip'] = trim($input['ip']);
            $setting = $this->module['config'];
            $setting['api'] = $input;
            if ($this->saveSettings($setting)) {
                message('保存参数成功', 'refresh');
            }
        }
        $config = $this->module['config']['api'];
        if (empty($config['ip'])) {
            $config['ip'] = $_SERVER['SERVER_ADDR'];
        }
        include $this->template('setting');
    }
    
    public function doWebRecords() {
        global $_W, $_GPC;
        $filters = array();
        if(!empty($_GPC['nickname'])) {
            $filters['nickname'] = $_GPC['nickname'];
        }
        if(!empty($_GPC['status'])) {
            $filters['status'] = $_GPC['status'];
        }
        
        $pindex = intval($_GPC['page']);
        $pindex = max($pindex, 1);
        $psize = 15;
        $total = 0;
        
        require_once MB_ROOT . '/source/Fans.class.php';
        $f = new Fans();
        $ds = $f->getAll($filters, $pindex, $psize, $total);
        $pager = pagination($total, $pindex, $psize);
        include $this->template('records');
    }
    
    public function doWebSend() {
        global $_W, $_GPC;
        require_once MB_ROOT . '/source/Fans.class.php';
        $f = new Fans();

        $uid = intval($_GPC['uid']);
        $user = $f->getOne($uid);
        if(empty($user)) {
            exit('错误的访问');
        }
        $activity = $this->getActivity(true, array('user' => $user));
        if(is_error($activity)) {
            message($activity['message']);
        }
        $ret = $this->send($user);
        if(is_error($ret)) {
            exit($ret['message']);
        } else {
            exit('success');
        }
    }
    
    public function doWebEntry() {
        global $_W;
        $url = $_W['siteroot'] . 'app/' . substr($this->createMobileUrl('get'), 2);
        include $this->template('entry');
    }

    public function doWebQr() {
        global $_GPC;
        $raw = @base64_decode($_GPC['raw']);
        if (!empty($raw)) {
            include MB_ROOT . '/source/phpqrcode.php';
            QRcode::png($raw, false, QR_ECLEVEL_Q, 4);
        }
    }

}

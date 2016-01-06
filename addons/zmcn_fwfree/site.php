<?php

defined('IN_IA') or exit('Access Denied');
class zmcn_fwfreeModuleSite extends WeModuleSite
{
    public function doMobilet()
    {
        global $_W, $_GPC, $codeca, $codeset, $luckset, $ckey, $settings, $webset;
        $settings = $this->module['config'];
        $batch    = array();
        load()->classs('weixin.account');
        $access_token = WeAccount::token();
        load()->func('communication');
        if (strlen($settings['wapcss']) < 1) {
            $settings['wapcss'] = 'default';
        }
        if (empty($_W['openid'])) {
            $appid       = $_W['account']['key'];
            $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . urlencode($_W['siteurl']) . "&response_type=code&scope=snsapi_userinfo&state=0#wechat_redirect";
            header("location:$oauth2_code");
            exit;
        }
        if (empty($_W['fans']['follow'])) {
            $oauth2_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $_W['openid'] . "&lang=zh_CN";
            $content    = ihttp_get($oauth2_url);
            $info       = @json_decode($content['content'], true);
            if (empty($info) || !is_array($info) || empty($info['openid'])) {
                $_W['fans']['follow'] = 0;
            } else {
                $_W['fans']['follow']   = $info['subscribe'];
                $_W['fans']['nickname'] = $info['nickname'];
            }
        }
        if (empty($_W['fans']['follow'])) {
            if (empty($settings['link'])) {
                $settings['link'] = $_W['account']['qrcode'];
            } else {
                $settings['link'] = $_W['attachurl'] . $settings['link'];
            }
            include $this->template($settings['wapcss'] . '/comeon');
            exit;
        }
        $content = $_GPC['co'];
        $openid  = $_W['openid'];
        if ($_GPC['u'] == 'i') {
            $casel = "微站";
            $ctapy = 6;
        } else {
            $casel = "扫一扫";
            $ctapy = 3;
        }
        $fans   = pdo_fetch("SELECT * FROM " . tablename('mc_mapping_fans') . " WHERE uniacid=:uniacid AND openid=:openid", array(
            ':uniacid' => $_W['uniacid'],
            ':openid' => $openid
        ));
        $member = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uniacid=:uniacid AND uid=:uid", array(
            ':uniacid' => $_W['uniacid'],
            ':uid' => $fans['uid']
        ));
        if (empty($member['resideprovince']) && !empty($info['province'])) {
            $member['resideprovince'] = $info['province'];
        }
        if (empty($member['residecity']) && !empty($info['city'])) {
            $member['residecity'] = $info['city'];
        }
        if (empty($member['gender']) && !empty($info['sex'])) {
            $member['gender'] = $info['sex'];
        }
        if ((int) $settings['ischuanhuo'] > 0 && !empty($_W['clientip'])) {
        }
        $fgltxt = "查询失败，你输入的不是有效的防伪码！";
        $mm     = strtoupper($content);
        $mm     = str_replace("－", "", str_replace("　", "", str_replace("-", "", str_replace(" ", "", str_replace(".", "", $mm)))));
        $fglact = 0;
        $fglnum = 0;
        $fglttt = 0;
        $a      = substr($mm, 2, $codeset['m'][5] - 2);
        if (empty($settings['welcome'])) {
            $settings['welcome'] = '欢迎您进入本公司商品防伪查询!';
        }
        if (empty($settings['con_msg'])) {
            $settings['con_msg'] = '查询失败,查不到该防伪码!请仔细核对!';
        }
        if (empty($settings['luckname'])) {
            $settings['luckname'] = "积分";
        }
        if (empty($settings['welcometxt'])) {
            $settings['welcometxt'] = "现在请刮开防伪标上的涂层，\n直接在微信回复" . $codeset['m'][5] . "位防伪密码\n（英文字母要区分大小写，不要输入空格），\n您将可以参加" . $settings['luckname'] . "活动！\n";
        }
        if ($mm == "微信防伪") {
            $fgltle = $settings['welcome'];
            $fgltxt = $settings['welcometxt'];
            $fglurl = "";
            $fglttt = 1;
        } elseif ($mm == $codeset['m'][1] . "000") {
            $fgltle = $settings['welcome'];
            $fgltxt = $settings['welcometxt'];
            $fglurl = "";
            $fglttt = 1;
        } elseif (strlen($mm) != (int) $codeset['m'][5] && strlen($mm) != 18) {
            $fgltxt = $settings['con_msg'] . "\n";
            $fglttt = 1;
        } elseif (!is_numeric($a)) {
            $fgltxt = $settings['con_msg'] . "\n";
            $fglttt = 1;
        }
        if ($fglttt == 0) {
            $insert = array(
                'cid' => $_W['uniacid'],
                'cod' => $mm
            );
            $a      = ihttp_post($codeca . '&ac=5', $insert);
            if (strlen($a['content']) > 5) {
                $a = @json_decode($a['content'], true);
            }
            if ($a['content'] == '3') {
                if ($a['isok'] == '8') {
                    if (strlen($a['mm']) == (int) $codeset['m'][5]) {
                        $mm = $a['mm'];
                    }
                    $batchid = substr($mm, strlen($codeset['m'][1]), (int) $codeset['m'][2]);
                    $batch   = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid=:uniacid AND batch=:batch", array(
                        ':uniacid' => $_W['uniacid'],
                        ':batch' => $batchid
                    ));
                    if (!empty($batch['id'])) {
                        $codeset['m'][1] = substr($mm, 0, 2);
                        $fgltxt          = "1密码正确！";
                        $record          = pdo_fetch("SELECT COUNT(*) AS number ,id , num , addtime , userid ，isvalid FROM " . tablename('zmcn_fw_chai') . " WHERE uniacid=:uniacid AND code=:code", array(
                            ':uniacid' => $_W['uniacid'],
                            ':code' => $mm
                        ));
                        $fglact          = 1;
                        $fglcong         = 0;
                        if (empty($record['number'])) {
                            $fglnum  = 1;
                            $fglcong = 1;
                            $fglpp   = 2;
                            $data    = array(
                                'uniacid' => $_W['uniacid'],
                                'code' => $mm,
                                'type' => $ctapy,
                                'num' => 1,
                                'addtime' => $_W['timestamp'],
                                'userna' => $openid,
                                'userid' => $fans['uid'],
                                'ip' => $_W['clientip'],
                                'gender' => $member['gender'],
                                'province' => $member['resideprovince'],
                                'city' => $member['residecity'],
                                'os' => $_W['os'],
                                'container' => $_W['container']
                            );
                            pdo_insert('zmcn_fw_chai', $data);
                        } else {
                            $fglnum = $record['num'] + 1;
                        }
                        $history = pdo_fetch("SELECT COUNT(*) AS number,id FROM " . tablename('zmcn_fw_history') . " WHERE uniacid=:uniacid AND summary=:summary AND type=0 AND uid=:uid AND addtime>:addtime", array(
                            ':uniacid' => $_W['uniacid'],
                            ':summary' => $mm,
                            ':uid' => $fans['uid'],
                            ':addtime' => $_W['timestamp'] - 21
                        ));
                        if (empty($history['number'])) {
                            $data1 = array(
                                'uniacid' => $_W['uniacid'],
                                'type' => 0,
                                'summary' => $mm,
                                'uid' => $fans['uid'],
                                'addtime' => $_W['timestamp'],
                                'ip' => $_W['clientip'],
                                'remark' => "第" . $fglnum . "次" . $casel . "：" . $member['resideprovince'] . "·" . $member['residecity']
                            );
                            pdo_insert('zmcn_fw_history', $data1);
                            $data = array(
                                'num' => $fglnum
                            );
                            pdo_update('zmcn_fw_chai', $data, array(
                                'id' => $record['id']
                            ));
                            $fglpp = 1;
                        } elseif ($fglnum == 2) {
                            $fglcong = 1;
                        }
                    } else {
                        $fgltxt = "查询失败，查不到些商品，这可能商家还未录入相关商品资料！\n";
                        $fglttt = 1;
                    }
                } else {
                    $fgltxt = $settings['con_msg'] . "\n";
                    $fglttt = 1;
                }
            } else {
                $fgltxt = "查询中断，防伪码数据库连接失败(" . $a . ")，这是网通信故障，与商品真伪无关，请稍候再查询！";
                $fglttt = 1;
            }
        }
        if ($fglact == 1) {
            if ($fglcong == 1) {
                if (empty($settings['suc_one'])) {
                    $settings['suc_one'] = '查询成功，该码为首次查询，请放心使用！';
                }
                $fgltxt = "查询结果：" . $settings['suc_one'] . "\n";
                $fgltle = $settings['suc_one'];
            } elseif ((int) $_W['timestamp'] > (int) $batch['validity'] || $record['isvalid'] == 1) {
                if (empty($settings['suc_three'])) {
                    $settings['suc_three'] = $settings['con_msg'] . "\n";
                }
                $fgltxt = "查询结果：" . $settings['suc_three'] . "\n";
                $fgltle = $settings['suc_three'];
                $fglact = 0;
            } else {
                if ((int) $settings['upperlimit'] > 0 && (int) $record['num'] > (int) $settings['upperlimit']) {
                    if (empty($settings['suc_three'])) {
                        $settings['suc_three'] = '查询失败，该码因查询次数过多，已经失效！';
                    }
                    $fgltxt = "查询结果：" . $settings['suc_three'] . "\n";
                    $fgltle = $settings['suc_three'];
                    $fglact = 0;
                } else {
                    if (empty($settings['suc_two'])) {
                        $settings['suc_two'] = '查询成功，该码为非首次查询，谨防假冒！';
                    }
                    $fgltxt = "查询结果：该码为多次查询,\n首次时间：" . date('Y-m-d H:i:s', $record['addtime']) . "\n";
                    $fgltle = $settings['suc_two'];
                }
            }
        }
        if ($fglact == 1) {
            $params = iunserializer($batch['param']);
            if ($batch['product'] != "") {
                $fgltxt .= "产品名称：" . $batch['product'] . "\n";
            }
            if ($batch['factory'] != "") {
                $fgltxt .= "企业名称：" . $batch['factory'] . "\n";
            }
            if ($batch['brand'] != "") {
                $fgltxt .= "产品品牌：" . $batch['brand'] . "\n";
            }
            if ($settings['ischuanhuo'] > 0) {
                if ($batch['ischuanhuo'] == 0) {
                }
            }
            foreach ($params AS $param) {
                $fgltxt .= $param['title'] . "：" . $param['value'] . "\n";
            }
            if ($settings['ftel'] != "") {
                $fgltxt .= "客服电话：" . $settings['ftel'] . "\n";
            }
            if ($settings['dtel'] != "") {
                $fgltxt .= "打假电话：" . $settings['dtel'] . "\n";
            }
            if ($settings['script'] != "") {
                $fgltxt .= $settings['script'] . "\n";
            }
            if ($batch['buylink'] != "") {
                $fglurl = $batch['buylink'];
            } else {
                $fglurl = $_W['siteroot'] . "app/index.php?i=" . $_W['uniacid'] . "&c=mc";
            }
        }
        if ($fglnum == 1) {
            pdo_query("update " . tablename('zmcn_fw_batch') . " set rcon = rcon + 1 where id=" . $batch['id']);
            if ($batch['inttype'] == 0 && $settings['isluck'] == 1) {
                $luckno  = mt_rand(10000, 99999);
                $list    = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_set') . " WHERE uniacid=" . $_W['uniacid']);
                $luckset = iunserializer($list['luck']);
                $jj      = 0;
                $tt      = "";
                for ($i = 1; $i < 6; ++$i) {
                    $tmparray = explode($luckset[$i]['m'], $luckno);
                    if (count($tmparray) > 1) {
                        $jj = $luckset[$i]['i'];
                        $tt = $luckset[$i]['t'];
                        break;
                    }
                }
                if (empty($settings['luckname'])) {
                    $settings['luckname'] = '码上抽奖';
                }
                if ($jj == 0) {
                    $jj = $luckset[0]['i'];
                    $tt = $luckset[0]['t'];
                }
                $fgltxt .= $settings['luckname'] . "活动：抽奖码(" . $luckno . ")奖励：" . zmcn_get_int($settings['inttype'], $jj) . "\n";
                mc_credit_update($fans['uid'], 'credit' . $settings['inttype'], $jj, array(
                    0 => $fans['uid'],
                    1 => '微防伪：' . $settings['luckname'] . '活动'
                ));
                $fgltxt .= $tt . "\n";
            } else {
                if ($batch['integral'] > 0) {
                    if ($batch['inttype'] == 0) {
                        $batch['inttype'] = 1;
                    }
                    mc_credit_update($fans['uid'], 'credit' . $batch['inttype'], $batch['integral'], array(
                        0 => $fans['uid'],
                        1 => '微防伪：查询' . $mm . '奖励'
                    ));
                    $fgltxt .= "防伪码查询奖励：" . zmcn_get_int($batch['inttype'], $batch['integral']) . "\n";
                }
            }
        } elseif ($fglcong == 1) {
            $record = pdo_fetch("SELECT num,remark,credittype,createtime FROM " . tablename('mc_credits_record') . " WHERE uniacid=:uniacid AND uid=:uid AND operator=:operator AND remark LIKE '微防伪：%' ORDER BY id DESC", array(
                ':uniacid' => $_W['uniacid'],
                ':uid' => $fans['uid'],
                ':operator' => $fans['uid']
            ));
            $fgltxt .= $record['remark'] . zmcn_get_int(substr($record['credittype'], -1), $record['num']) . "\n";
        } elseif ($fglact == 1) {
            if ($settings['ishistory'] >= 1) {
                $fgltxt .= "近期查询记录：\n";
                $history = pdo_fetchall("SELECT * FROM " . tablename('zmcn_fw_history') . " WHERE uniacid=:uniacid AND type = 0 AND summary=:summary  ORDER BY id DESC LIMIT " . $settings['ishistory'], array(
                    ':uniacid' => $_W['uniacid'],
                    ':summary' => $mm
                ));
                for ($i = 0; $i < count($history); ++$i) {
                    $fgltxt .= $history[$i]['remark'] . "(" . date('y-m-d h:i:s', $history[$i]['addtime']) . ")\n";
                }
            }
        }
        if (strlen($batch['banner']) < 5) {
            $batch['banner'] = $settings['banner'];
        }
        if (strlen($batch['logo']) < 5) {
            $batch['logo'] = $settings['logo'];
        }
        if ($fglact == 1) {
            if ($settings['islink'] == 1) {
                $toshop = iunserializer($batch['toshop']);
                if (strlen($batch['buylink']) > 20) {
                    $fglurl = $batch['buylink'];
                } elseif ($toshop['shioip'] == 0) {
                    $fglurl = $_W['siteroot'] . "app/index.php?i=" . $_W['uniacid'] . "&c=mc&a=bond&do=credits&credittype=credit1&wxref=mp.weixin.qq.com#wechat_redirect";
                } elseif ($toshop['shioip'] == 1) {
                    $fglurl = $_W['siteroot'] . "app/index.php?i=" . $_W['uniacid'] . "&c=entry&do=detail&m=ewei_shopping&id=" . $toshop['proid'];
                } elseif ($toshop['shioip'] == 2) {
                    $fglurl = $_W['siteroot'] . "app/index.php?i=" . $_W['uniacid'] . "&c=mc&a=bond&do=credits&credittype=credit1&wxref=mp.weixin.qq.com#wechat_redirect";
                }
                $abcd     = array(
                    'title' => $fgltle,
                    'description' => $fgltxt,
                    'picUrl' => $fglpic,
                    'url' => $fglurl
                );
                $articles = array(
                    $abcd
                );
                $kfxx     = array(
                    'touser' => $_W['openid'],
                    'msgtype' => "news",
                    'news' => array(
                        'articles' => $articles
                    )
                );
                $postarr  = encode_json($kfxx);
                if ($fglpp == 1 && !empty($access_token)) {
                    $res = ihttp_post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token, $postarr);
                    $res = @json_decode($res['content'], true);
                    if ((int) $res['errcode'] > 0) {
                        $abcd    = array(
                            'first' => array(
                                'value' => $settings['welcome'],
                                'color' => '#173177'
                            ),
                            'keyword1' => array(
                                'value' => '商品防伪鉴别',
                                'color' => '#173177'
                            ),
                            'keyword2' => array(
                                'value' => $fgltle,
                                'color' => '#000000'
                            ),
                            'remark' => array(
                                'value' => '亲！可能是因为你太长时间没来活动，所以系统发不出更详细的资料，请点菜单或回复几个字后再查！',
                                'color' => '#173177'
                            )
                        );
                        $kfxx    = array(
                            'touser' => $_W['openid'],
                            'template_id' => $settings['template_id'],
                            'url' => $fglurl,
                            'topcolor' => '#FF0000',
                            'data' => $abcd
                        );
                        $postarr = encode_json($kfxx);
                        $res     = ihttp_post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token, $postarr);
                    }
                }
                header('location:' . $fglurl);
                exit;
            } else {
                $fgltxt = str_replace(chr(10), '<br>', $fgltxt);
                $fgltxt = str_replace(chr(13), '<br>', $fgltxt);
                include $this->template($settings['wapcss'] . '/authentic');
            }
        } else {
            include $this->template($settings['wapcss'] . '/err');
        }
    }
    public function doWebZmfwcaa()
    {
        global $_W, $_GPC, $codeset, $settings, $webset;
        $foo = !empty($_GPC['foo']) ? $_GPC['foo'] : 'display';
        load()->func('tpl');
        $settings = $this->module['config'];
        if ($foo == 'display') {
            $pindex    = max(1, intval($_GPC['page']));
            $psize     = 10;
            $condition = '';
            $params    = array();
            $list      = pdo_fetchall("SELECT * FROM " . tablename('zmcn_fw_chai') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
            $total     = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('zmcn_fw_chai') . " WHERE uniacid = '{$_W['uniacid']}' $condition", $params);
            $pager     = pagination($total, $pindex, $psize);
        } elseif ($foo == 'post') {
            $pindex = max(1, intval($_GPC['page']));
            $psize  = 20;
            if ((int) $_GPC['id'] > 0) {
                $list   = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_chai') . " WHERE id=" . (int) $_GPC['id']);
                $params = array(
                    ':uniacid' => $_W['uniacid'],
                    ':summary' => $list['code'],
                    ':type' => 0
                );
            } elseif ((int) $_GPC['cid'] > 0) {
                $list   = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_batch') . " WHERE id=" . (int) $_GPC['cid']);
                $params = array(
                    ':uniacid' => $_W['uniacid'],
                    ':summary' => $list['m1'] . $list['batch'],
                    ':type' => 1
                );
            }
            $list  = pdo_fetchall("SELECT * FROM " . tablename('zmcn_fw_history') . " WHERE uniacid=:uniacid AND type = :type AND summary=:summary  ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('zmcn_fw_history') . ' WHERE uniacid=:uniacid AND type = :type AND summary=:summary', $params);
            $pager = pagination($total, $pindex, $psize);
        } elseif ($foo == 'close') {
            $list = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_chai') . " WHERE id=" . (int) $_GPC['id']);
            pdo_update('zmcn_fw_chai', array(
                'isvalid' => 1
            ), array(
                'uniacid' => $_W['uniacid'],
                'id' => (int) $_GPC['id']
            ));
            $data1 = array(
                'uniacid' => $_W['uniacid'],
                'type' => 1,
                'summary' => substr($list['code'], 0, strlen($codeset['m'][1]) + (int) $codeset['m'][2]),
                'uid' => $_W['uid'],
                'addtime' => TIMESTAMP,
                'ip' => $_W['clientip'],
                'remark' => '锁定查询记录：' . $list['code']
            );
            pdo_insert('zmcn_fw_history', $data1);
            message('该条记录已经进入黑名单！', referer(), 'success');
        } elseif ($foo == 'delete') {
            $list  = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_chai') . " WHERE id=" . (int) $_GPC['id']);
            $data1 = array(
                'uniacid' => $_W['uniacid'],
                'type' => 1,
                'summary' => substr($list['code'], 0, strlen($codeset['m'][1]) + (int) $codeset['m'][2]),
                'uid' => $_W['uid'],
                'addtime' => TIMESTAMP,
                'ip' => $_W['clientip'],
                'remark' => '删除查询记录：' . $list['code']
            );
            pdo_insert('zmcn_fw_history', $data1);
            $params = array(
                'uniacid' => $_W['uniacid'],
                'summary' => $list['code']
            );
            pdo_delete('zmcn_fw_history', $params);
            pdo_delete('zmcn_fw_chai', array(
                'uniacid' => $_W['uniacid'],
                'id' => (int) $_GPC['id']
            ));
            message('该条记录已经删除！', referer(), 'success');
        }
        include $this->template('history');
    }
    public function doWebZmfwpee()
    {
        global $_W, $_GPC, $codeca, $codeset, $luckset, $settings, $webset;
        $settings = $this->module['config'];
        $foo      = !empty($_GPC['foo']) ? $_GPC['foo'] : 'display';
        load()->func('tpl');
        load()->func('communication');
        if (empty($codeset['k']['1'])) {
            message('请先进行部署好密码，再使用！', 'index.php?c=profile&a=module&do=setting&m=' . $webset['moid'], 'error');
            exit;
        }
        $iii = 1;
        if ($foo == 'display') {
            $pindex    = max(1, intval($_GPC['page']));
            $psize     = 10;
            $condition = '';
            $params    = array();
            if (!empty($_GPC['keyword'])) {
                $condition .= " AND product LIKE :keyword";
                $params[':keyword'] = "%{$_GPC['keyword']}%";
            }
            $list  = pdo_fetchall("SELECT * FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
            $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('zmcn_fw_batch') . " WHERE uniacid = '{$_W['uniacid']}' $condition", $params);
            $pager = pagination($total, $pindex, $psize);
        } elseif ($foo == 'post') {
            $parent_cate = array();
            $sub_cate    = array();
            if ($settings['isint'] == "1" && pdo_tableexists('shopping_category')) {
                $parent_cate = pdo_fetchall("SELECT id as a, name as b FROM " . tablename('shopping_category') . " WHERE weid = :weid AND parentid > 0  ORDER BY parentid ASC", array(
                    ":weid" => $_W['uniacid']
                ));
                $tmp         = array();
                foreach ($parent_cate AS $parent) {
                    array_push($tmp, $parent['a']);
                }
                $tmp = implode(",", $tmp);
                if (strlen($tmp) >= 1) {
                    $sub_cate = pdo_fetchall("SELECT id as a , title as b , ccate as c FROM " . tablename('shopping_goods') . " WHERE weid = :weid AND ccate IN (" . $tmp . ") AND deleted = 0 ORDER BY displayorder ASC", array(
                        ":weid" => $_W['uniacid']
                    ));
                }
            } elseif ($settings['isint'] == "2" && pdo_tableexists('ewei_shop_category')) {
            }
            $batch = array();
            if ($_GPC['id'] > 0) {
                $batch       = pdo_fetch("SELECT * FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = :uniacid and id = :id", array(
                    ':uniacid' => $_W['uniacid'],
                    ':id' => (int) $_GPC['id']
                ));
                $params      = iunserializer($batch['param']);
                $batch['id'] = $batch['id'] == "" ? "0" : $batch['id'];
                $toshop      = iunserializer($batch['toshop']);
            } else {
                $batch['id'] = 0;
            }
            if (checksubmit('submit')) {
                $insert = array();
                for ($i = 0; $i < count($_GPC['titles']); $i++) {
                    $insert[$i] = array(
                        'title' => $_GPC['titles'][$i],
                        'value' => $_GPC['values'][$i]
                    );
                }
                $toshop  = array(
                    'shioip' => $settings['isint'],
                    'proid' => $_GPC['shopproid']
                );
                $inbatch = array(
                    'remark' => $_GPC['remark'],
                    'product' => $_GPC['product'],
                    'factory' => $_GPC['factory'],
                    'brand' => $_GPC['brand'],
                    'ischuanhuo' => $_GPC['ischuanhuo'],
                    'province' => $_GPC['chuanhuo']['province'],
                    'city' => $_GPC['chuanhuo']['city'],
                    'video' => $_GPC['video'],
                    'buylink' => $_GPC['buylink'],
                    'validity' => empty($_GPC['validity']) ? strtotime('+30 month') : strtotime($_GPC['validity']),
                    'inttype' => $_GPC['inttype'],
                    'integral' => $_GPC['integral'],
                    'logo' => $_GPC['logo'],
                    'banner' => $_GPC['banner'],
                    'param' => iserializer($insert),
                    'lasttime' => TIMESTAMP,
                    'toshop' => iserializer($toshop)
                );
                if ($batch['id'] == "0") {
                    if (strlen($_GPC['batch']) != (int) $codeset['m'][2]) {
                        $_GPC['batch'] = substr("0000000000" . (int) $_GPC['batch'], 0 - $codeset['m'][2]);
                    }
                    $bl = array(
                        '1111111',
                        '2222222',
                        '3333333',
                        '4444444',
                        '5555555',
                        '6666666',
                        '7777777',
                        '0000000'
                    );
                    if (in_array($_GPC['batch'], $bl)) {
                        message('这个批号已经被系统保留，请更换批号', 'refresh', 'error ');
                        exit;
                    }
                    $batch = pdo_fetch("SELECT COUNT(*) as a FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = :uniacid and batch=:batch", array(
                        ':uniacid' => $_W['uniacid'],
                        ':batch' => $_GPC['batch']
                    ));
                    if (!empty($batch['a'])) {
                        message('这个批号已经有记录，请更换批号', 'refresh', 'error ');
                        exit;
                    }
                    $inbatch1 = array(
                        'uniacid' => $_W['uniacid'],
                        'm1' => $_GPC['m1'],
                        'batch' => $_GPC['batch'],
                        'num' => 0,
                        'addtime' => TIMESTAMP,
                        'rcon' => 0
                    );
                    $inbatchs = array_merge($inbatch1, $inbatch);
                    pdo_insert('zmcn_fw_batch', $inbatchs);
                    $data1 = array(
                        'uniacid' => $_W['uniacid'],
                        'type' => 1,
                        'summary' => $_GPC['m1'] . $_GPC['batch'],
                        'uid' => $_W['uid'],
                        'addtime' => TIMESTAMP,
                        'ip' => $_W['clientip'],
                        'remark' => '新增批次：' . $_GPC['m1'] . $_GPC['batch']
                    );
                    pdo_insert('zmcn_fw_history', $data1);
                    message('新增批次信息成功！', referer(), 'success');
                } else {
                    pdo_update('zmcn_fw_batch', $inbatch, array(
                        'uniacid' => $_W['uniacid'],
                        'id' => (int) $_GPC['id']
                    ));
                    $data1 = array(
                        'uniacid' => $_W['uniacid'],
                        'type' => 1,
                        'summary' => $batch['m1'] . $batch['batch'],
                        'uid' => $_W['uid'],
                        'addtime' => TIMESTAMP,
                        'ip' => $_W['clientip'],
                        'remark' => '更新批次：' . $batch['m1'] . $batch['batch']
                    );
                    pdo_insert('zmcn_fw_history', $data1);
                    message('更新批次信息成功！', referer(), 'success');
                }
            }
        } elseif ($foo == 'daoru') {
            $pp = pdo_fetch("SELECT COUNT(*) FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = :uniacid  AND m1 = :m1 AND batch = :batch", array(
                ':uniacid' => $_W['uniacid'],
                ':m1' => 'FW',
                ':batch' => '8888888'
            ));
            if (empty($pp)) {
                $insert = array(
                    'uniacid' => $_W['uniacid'],
                    'm1' => 'FW',
                    'batch' => '8888888',
                    'product' => '微防伪演示商品',
                    'addtime' => TIMESTAMP,
                    'lasttime' => TIMESTAMP,
                    'factory' => $_W['account']['name'],
                    'remark' => '系统保留演示专用'
                );
                pdo_insert('zmcn_fw_batch', $insert);
            }
            if (checksubmit('submit')) {
                $insert = array(
                    'cid' => $_W['uniacid'],
                    'm1' => 'FW'
                );
                $a      = ihttp_post($codeca . '&ac=4', $insert);
                if (strlen($a['content']) > 5) {
                    $a = @json_decode($a['content'], true);
                }
                if ($a['content'] == '3' && $a['isok'] == '666') {
                    if ($_GPC['codetype'] == '3') {
                        for ($i = 0; $i < $a['coden']; ++$i) {
                            $mima .= wordwrap($a['codes'][$i], 4, " ", TRUE) . "\n";
                        }
                    } elseif ($_GPC['codetype'] == '4') {
                        $html = "";
                        for ($i = 0; $i < $a['coden']; ++$i) {
                            $aaa = $_W['siteroot'] . "app/index.php?c=entry&do=t&m=zmcn_fwfree&co=" . $a['codes'][$i] . "&i=" . $_W['uniacid'];
                            $html .= "<img src='./index.php?c=platform&a=url2qr&do=qr&url=" . urlencode($aaa) . "' style='width:200px; height:200px;  margin: 10px;' />";
                        }
                    } elseif ($_GPC['codetype'] == '6') {
                        for ($i = 0; $i < $a['coden']; ++$i) {
                            $mima .= $a['codes'][$i] . "\n";
                        }
                    }
                    pdo_query('update ' . tablename('zmcn_fw_batch') . ' set num = ' . (int) $a['codeg'] . " , lasttime = '" . TIMESTAMP . "'  where batch='8888888'");
                    $data1 = array(
                        'uniacid' => $_W['uniacid'],
                        'type' => 1,
                        'summary' => 'FW8888888',
                        'uid' => $_W['uid'],
                        'addtime' => TIMESTAMP,
                        'ip' => $_W['clientip'],
                        'remark' => '生成演示防伪码：(' . $_GPC['codetype'] . ')' . $a['codef'] . '-' . $a['codeg'] . '共' . $a['coden'] . '条'
                    );
                    pdo_insert('zmcn_fw_history', $data1);
                } elseif ($a['content'] == '3' && $a['isok'] == '234') {
                    $html = "今天演示防伪码生成个数已经够多了，请明天再来！演示防伪码总数有限，请节约使用！";
                } elseif ($a['content'] == '3' && $a['isok'] == '599') {
                    $html = "请重新提交一下密码部署！";
                }
            }
        } elseif ($foo == 'chong') {
            if (strlen($_GPC['b']) != (int) $codeset['m'][2]) {
                $_GPC['b'] = substr('0000000000' . (int) $_GPC['b'], 0 - $codeset['m'][2]);
            }
            $bl = array(
                '1111111',
                '2222222',
                '3333333',
                '4444444',
                '5555555',
                '6666666',
                '7777777',
                '0000000'
            );
            if (in_array($_GPC['batch'], $bl)) {
                echo "1";
                exit;
            }
            $batch = pdo_fetch("SELECT COUNT(*) as a FROM " . tablename('zmcn_fw_batch') . " WHERE uniacid = :uniacid and batch=:batch", array(
                ':uniacid' => $_W['uniacid'],
                ':batch' => $_GPC['b']
            ));
            if (!empty($batch['a'])) {
                echo "1";
            } else {
                echo "0";
            }
            exit;
        }
        include $this->template('batch');
    }
    public function doWebhelp()
    {
        global $_W, $_GPC, $codeset, $settings, $webset;
        include $this->template('help');
    }
    public function doMobileindex()
    {
        global $_W, $_GPC, $codeset, $luckset, $settings, $webset;
        $settings            = $this->module['config'];
        $settings['profile'] = str_replace(chr(10), '<br>', $settings['profile']);
        $settings['profile'] = str_replace(chr(13), '<br>', $settings['profile']);
        if (empty($settings['wapcss'])) {
            $settings['wapcss'] = 'default';
        }
        include $this->template($settings['wapcss'] . '/index');
    }
}
function zm_get_username($idd)
{
    $memb = pdo_fetch("SELECT * FROM " . tablename('mc_members') . " WHERE uid=:uid", array(
        ':uid' => $idd
    ));
    if (!empty($memb['uid'])) {
        $aa = "<img src='" . $memb['avatar'] . "'  width='32' height='32'  onerror=\"this.src='resource/images/gw-wx.gif'\" /> <a href='./index.php?c=mc&a=member&do=post&uid=" . $memb['uid'] . "' title='查看详细信息' target='_blank'>" . $memb['nickname'] . "</a>";
    } else {
        $aa = "UID:" . $idd;
    }
    return $aa;
}
function zm_get_webusername($idd)
{
    $memb = pdo_fetch("SELECT * FROM " . tablename('users_profile') . " WHERE uid=:uid", array(
        ':uid' => $idd
    ));
    if (!empty($memb['uid'])) {
        $aa = "<a href='./index.php?c=user&a=edit&uid=" . $memb['uid'] . "' title='查看详细信息' target='_blank'>" . $memb['realname'] . "</a>";
    } else {
        $aa = "UID:" . $idd;
    }
    return $aa;
}
function zm_get_caitype($ty)
{
    switch ($ty) {
        case 0:
            $a = "<span class='label label-danger'>网页API</span>";
            break;
        case 1:
            $a = "<span class='label label-warning'>手动输入</span>";
            break;
        case 2:
            $a = "<span class='label label-success'>扫码回复(大码)</span>";
            break;
        case 3:
            $a = "<span class='label label-success'>扫一扫</span>";
            break;
        case 4:
            $a = "<span class='label label-primary'>扫码回复(小码)</span>";
            break;
        case 5:
            $a = "<span class='label label-info'>扫条码</span>";
            break;
        case 6:
            $a = "<span class='label label-default'>微站界面</span>";
            break;
        default:
            $a = "<span class='label label-default'>未知</span>";
    }
    return $a;
}
function encode_json($str)
{
    return urldecode(json_encode(url_encode($str)));
}
function url_encode($str)
{
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[urlencode($key)] = url_encode($value);
        }
    } else {
        $str = urlencode($str);
    }
    return $str;
}
function zmcn_get_int($j, $f)
{
    $wi = array(
        "",
        "积分",
        "余额",
        "金豆",
        "海贝",
        "微币"
    );
    $wu = array(
        "",
        "分",
        "元",
        "颗",
        "片",
        "枚"
    );
    $e  = $wi[$j] . $f . $wu[$j];
    return $e;
}
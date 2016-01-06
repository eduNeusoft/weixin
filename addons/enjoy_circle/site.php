<?php
defined('IN_IA') or exit('Access Denied');
class Enjoy_circleModuleSite extends WeModuleSite
{
    public function shareth($str, $n)
    {
        $qian = array(
            "#user#"
        );
        $hou  = array(
            $n
        );
        return str_replace($qian, $hou, $str);
    }
    public function auth($uniacid, $openid)
    {
        $userlist = pdo_fetch("select * from " . tablename('enjoy_circle_fans') . " where uniacid=" . $uniacid . " and openid='" . $openid . "'");
        if (empty($userlist)) {
            $userinfo = mc_oauth_userinfo();
            $data     = array(
                'uniacid' => $uniacid,
                'subscribe' => $userinfo['subscribe'],
                'openid' => $userinfo['openid'],
                'nickname' => $userinfo['nickname'],
                'gender' => $userinfo['sex'],
                'city' => $userinfo['city'],
                'state' => $userinfo['province'],
                'country' => $userinfo['country'],
                'subscribe_time' => $userinfo['subscribe_time'],
                'avatar' => $userinfo['avatar'],
                'ip' => CLIENT_IP
            );
            pdo_insert('enjoy_circle_fans', $data);
            $userlist = pdo_fetch("select * from " . tablename('enjoy_circle_fans') . " where uniacid=" . $uniacid . " and openid='" . $openid . "'");
        }
        return $userlist;
    }
    public function doMobilecomment()
    {
        global $_W, $_GPC;
        $tid         = $_GPC['id'];
        $comment     = $_GPC['cont'];
        $uniacid     = $_W['uniacid'];
        $openid      = $_W['openid'];
        $ulist       = $this->auth($uniacid, $openid);
        $data        = array(
            'uniacid' => $uniacid,
            'tid' => $tid,
            'comment' => $comment,
            'nickname' => $ulist['nickname'],
            'cuid' => $ulist['uid'],
            'createtime' => TIMESTAMP
        );
        $res         = pdo_insert('enjoy_circle_comment', $data);
        $data['cid'] = pdo_insertid();
        $data['fuc'] = "todelpop('p_" . $data['cid'] . "')";
        if ($res > 0) {
            echo json_encode($data);
        }
    }
    public function doMobiledelcomment()
    {
        global $_W, $_GPC;
        $cid     = $_GPC['cid'];
        $uniacid = $_W['uniacid'];
        pdo_delete('enjoy_circle_comment', array(
            'uniacid' => $uniacid,
            'cid' => $cid
        ));
    }
    public function formatDate($time)
    {
        $rtime = date("m-d H:i", $time);
        $htime = date("H:i", $time);
        $time  = time() - $time;
        if ($time < 60) {
            $str = '刚刚';
        } elseif ($time < 60 * 60) {
            $min = floor($time / 60);
            $str = $min . '分钟前';
        } elseif ($time < 60 * 60 * 24) {
            $h   = floor($time / (60 * 60));
            $str = $h . '小时前 ';
        } elseif ($time < 60 * 60 * 24 * 3) {
            $d = floor($time / (60 * 60 * 24));
            if ($d == 1)
                $str = '昨天 ';
            else
                $str = '前天 ';
        } else {
            $str = $rtime;
        }
        return $str;
    }
}
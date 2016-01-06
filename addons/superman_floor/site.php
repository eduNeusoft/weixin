<?php
/**
 * 【超人】抢楼活动模块微站定义
 *
 * @author 超人
 * @url
 */
defined('IN_IA') or exit('Access Denied');
load()->model('mc');
include 'common.inc.php';
class Superman_floorModuleSite extends WeModuleSite {
    public function doWebDelete()
    {
        checklogin();
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $rid = intval($_GPC['rid']);
        $sql = "SELECT id FROM " . tablename('superman_floor_award') . " WHERE `id`=:id";
        $row = pdo_fetch($sql, array(':id'=>$id));
        if (empty($row)) {
            message('抱歉，奖品不存在或是已经被删除！', '', 'error');
        }
        if (pdo_delete('superman_floor_award', array('id' => $id))) {
            message('删除成功', murl('platform/reply/post', array(
                'm' => 'superman_floor',
                'rid' => $rid,
            )), 'success');
        }
    }

	public function doWebAwardlist()
    {
        checklogin();
		global $_GPC, $_W;
        $eid = intval($_GPC['eid']);
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        if ($operation == 'display') {
            $award = pdo_fetchall("SELECT * FROM ".tablename('rule')." WHERE uniacid='{$_W['uniacid']}' AND module = 'superman_floor' ");
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            $start = ($pindex - 1) * $psize;
            $where = ' WHERE 1=1';
            $params = array();
            if ($_GPC['rid'] != '') {
                $where .= ' AND rid=:rid';
                $params[':rid'] = $_GPC['rid'];
            }
            if ($_GPC['realname'] != '') {
                $where .= " AND realname LIKE '%{$_GPC['realname']}%'";
            }
            if ($_GPC['mobile'] != '') {
                $where .= " AND mobile LIKE '%{$_GPC['mobile']}%'";
            }
            $sql = "SELECT COUNT(*) FROM ".tablename('superman_floor_winner')." {$where}";
            $total = pdo_fetchcolumn($sql, $params);
            if ($total > 0) {
                $sql = "SELECT * FROM ".tablename('superman_floor_winner')." {$where} ORDER BY id DESC LIMIT $start,$psize";
                $list = pdo_fetchall($sql, $params);
                if ($list) {
                    $members = array();
                    foreach ($list as &$val) {
                        $val['award'] = pdo_fetch("SELECT * FROM ".tablename('superman_floor_award')." WHERE id={$val['award_id']}");
                        $val['rule'] = pdo_fetch("SELECT * FROM ".tablename('rule')." WHERE id={$val['rid']}");
                        if (!isset($members[$val['openid']])) {
                            $row = mc_fetch($val['openid'], array('avatar','nickname'));
                            $members[$val['openid']] = $row;
                        } else {
                            $row = $members[$val['openid']];
                        }
                        $val['avatar'] = $row['avatar'];
                        $val['nickname'] = $row['nickname'];
                        //$val['member'] = pdo_fetch("SELECT * FROM ".tablename('superman_floor_member')." WHERE id={$val['member_id']}");
                    }
                }
                $pager = pagination($total, $pindex, $psize);
            }
            include $this->template('awardlist');
        }if ($operation == 'toexcel'){
            $id = intval($_GPC['rid']);
            if($id) {
                $sql = "SELECT * FROM " . tablename('superman_floor_winner').' WHERE `rid` = '.$id;
                $list = pdo_fetchall($sql);
                if($list) {
                    $members = array();
                    foreach ($list as &$val) {
                        $val['floorname'] = pdo_fetchcolumn("SELECT name FROM " . tablename('rule') . " WHERE id=$val[rid]");
                        $val['prize'] = pdo_fetchcolumn("SELECT title FROM " . tablename('superman_floor_award') . " WHERE id=$val[award_id]");
                        if (!isset($members[$val['openid']])) {
                            $row = mc_fetch($val['openid'], array('avatar','nickname'));
                            $members[$val['openid']] = $row;
                        } else {
                            $row = $members[$val['openid']];
                        }
                        $val['avatar'] = $row['avatar'];
                        $val['nickname'] = $row['nickname'];
                    }
                }
            }else{
                $sql = "SELECT * FROM " . tablename('superman_floor_winner');
                $list = pdo_fetchall($sql);
                if($list) {
                    $members = array();
                    foreach ($list as &$val) {
                        $val['floorname'] = pdo_fetchcolumn("SELECT name FROM " . tablename('rule') . " WHERE id=$val[rid]");
                        $val['prize'] = pdo_fetchcolumn("SELECT title FROM " . tablename('superman_floor_award') . " WHERE id=$val[award_id]");
                        if (!isset($members[$val['openid']])) {
                            $row = mc_fetch($val['openid'], array('avatar','nickname'));
                            $members[$val['openid']] = $row;
                        } else {
                            $row = $members[$val['openid']];
                        }
                        $val['avatar'] = $row['avatar'];
                        $val['nickname'] = $row['nickname'];
                    }
                }
            }

            // $profiles = mc_fetch($uids, array('nickname', 'avatar'));
            foreach($list as &$or){
                // $or['nickname'] = $profiles[$or['uid']]['nickname'];
                $or['dateline'] = date('Y-m-d H:i:s', $or['dateline']);
                switch($or['status']){
                    case '0':
                        $or['status'] = '未领取';
                        break;
                    case '1':
                        $or['status'] = '已领取';
                        break;
                }
            }
            require_once IA_ROOT.'/framework/library/phpexcel/PHPExcel.php';
            require_once IA_ROOT.'/framework/library/phpexcel/PHPExcel/IOFactory.php';
            require_once IA_ROOT.'/framework/library/phpexcel/PHPExcel/Writer/Excel5.php';

            $resultPHPExcel = new PHPExcel();
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                        'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                        //'color' => array('argb' => 'FFFF0000'),
                    ),
                ),
            );
            $style_fill = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('argb' => '0xFFFF00')
                ),
            );

            $resultPHPExcel->getActiveSheet()->getStyle('A1:H1')->applyFromArray(($styleArray+$style_fill));
            $resultPHPExcel->getActiveSheet()->setCellValue('A1', '活动名称');
            $resultPHPExcel->getActiveSheet()->setCellValue('B1', '真实姓名');
            $resultPHPExcel->getActiveSheet()->setCellValue('C1', '电话');
            $resultPHPExcel->getActiveSheet()->setCellValue('D1', 'QQ');
            $resultPHPExcel->getActiveSheet()->setCellValue('E1', '中奖楼层');
            $resultPHPExcel->getActiveSheet()->setCellValue('F1', '中奖时间');
            $resultPHPExcel->getActiveSheet()->setCellValue('G1', '奖品');
            $resultPHPExcel->getActiveSheet()->setCellValue('H1', '是否领奖');
            $resultPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $resultPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
            $i = 2;
            foreach($list as $item){
                $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, $item['floorname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('B' . $i, $item['nickname']);
                $resultPHPExcel->getActiveSheet()->setCellValue('C' . $i, $item['mobile']);
                $resultPHPExcel->getActiveSheet()->setCellValue('D' . $i, $item['qq']);
                $resultPHPExcel->getActiveSheet()->setCellValue('E' . $i, $item['floor']);
                $resultPHPExcel->getActiveSheet()->setCellValue('F' . $i, $item['dateline']);
                $resultPHPExcel->getActiveSheet()->setCellValue('G' . $i, $item['prize']);
                $resultPHPExcel->getActiveSheet()->setCellValue('H' . $i, $item['status']);
                $resultPHPExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->applyFromArray($styleArray);
                $i++;
            }
            $resultPHPExcel->getActiveSheet()->setCellValue('A' . $i, '总报名人数：'.count($list).'人');
            $resultPHPExcel->getActiveSheet()->getStyle('A'.$i)->applyFromArray(array('font' => array('bold' => true)));


            $outputFileName = 'total.xls';
            $xlsWriter = new PHPExcel_Writer_Excel5($resultPHPExcel);
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition:inline;filename="'.$outputFileName.'"');
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            $xlsWriter->save( "php://output" );
        }elseif ($operation == 'mark') {
            if (!empty($_GPC['wid'])) {
                $wid = intval($_GPC['wid']);
                pdo_update('superman_floor_winner', array('status' => intval($_GPC['status'])), array('id' => $wid));
                message('操作成功！', $this->createWebUrl('awardlist', array('name' => 'superman_floor', 'page' => $_GPC['page'])));
            }
        }
	}
    public function doWebStatistics() {
        checklogin();
        global $_GPC, $_W;
        $eid = intval($_GPC['eid']);
        $operation = !empty($_GPC['op']) ? $_GPC['op'] : 'statistics';
        if ($operation == 'statistics'){
            $pindex = max(1, intval($_GPC['page']));
            $psize = 20;
            $start = ($pindex - 1) * $psize;
            $where = " WHERE `module`= 'superman_floor'";
            $sql = "SELECT COUNT(*) FROM ".tablename('rule')." {$where}";
            $total = pdo_fetchcolumn($sql);
            if ($total > 0) {
                $award = pdo_fetchall("SELECT * FROM ".tablename('rule')." WHERE uniacid='{$_W['uniacid']}' AND module = 'superman_floor' LIMIT {$start},{$psize}");
                if($award) {
                    foreach ($award as &$val) {
                        $val['winningman'] = pdo_fetchcolumn("SELECT COUNT(DISTINCT openid) FROM ".tablename('superman_floor_winner')."WHERE rid = $val[id]");
                        $val['winningnum'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('superman_floor_winner')."WHERE rid = $val[id]");
                        $val['winningreceive'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('superman_floor_winner')."WHERE rid = $val[id] AND status = 1");
                        $val['participateman'] = pdo_fetchcolumn("SELECT COUNT(DISTINCT openid) FROM ".tablename('superman_floor_'.$val[id]));
                        $val['participatenum'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('superman_floor_'.$val[id]));
                    }
                }
                $pager = pagination($total, $pindex, $psize);
            }
            include $this->template('awardlist');
        }


    }

	public function doMobileInfosubmit() {
		global $_W, $_GPC;
        $title = '提交领奖信息';

        list($rid, $winner_id, $openid) = explode('|', superman_authcode(trim($_GPC['_x']), 'DECODE'));
        if (empty($rid) || empty($winner_id) || empty($openid)) {
            message('非法请求！');
        }
        $active = pdo_fetch("SELECT * FROM ".tablename('rule')." WHERE id='$rid'");
        if (!$active) {
            message('活动已结束！');
        }

        $success = false;
        if (checksubmit()) {
            $realname = trim($_GPC['realname']);
            if (empty($realname)) {
                message('请填写姓名！');
            }
            $mobile = trim($_GPC['mobile']);
            if (empty($mobile)) {
                message('请填写电话号码！');
            }
            $qq = trim($_GPC['qq']);
            if (empty($qq)) {
                message('请填写QQ号码！');
            }
            $winner = $update_data = array(
                'realname' => $realname,
                'mobile' => $mobile,
                'qq' => $qq,
            );
            pdo_update('superman_floor_winner', $update_data, array('id' => $winner_id));
            $success = true;
        } else {
            $winner = pdo_fetch("SELECT * FROM ".tablename('superman_floor_winner')." WHERE id='$winner_id'");
        }
        include $this->template('infosubmit');
	}
    //中奖名单
    public function doMobileWinninginfo()
    {
        global $_W, $_GPC;
        $title = '中奖名单';
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('superman_floor_winner')."WHERE rid = {$_GPC['rid']}");
        if ($total > 0) {
            $list = pdo_fetchall("SELECT * FROM ".tablename('superman_floor_winner')."WHERE rid = {$_GPC['rid']} "." ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
            if ($list) {
                $members = array();
                foreach ($list as &$val) {
                    $val['award'] = pdo_fetch("SELECT * FROM ".tablename('superman_floor_award')." WHERE id={$val['award_id']}");
                    if (!isset($members[$val['openid']])) {
                        $row = mc_fetch($val['openid'], array('avatar','nickname'));
                        $members[$val['openid']] = $row;
                    } else {
                        $row = $members[$val['openid']];
                    }
                    $val['avatar'] = $row['avatar'];
                    $val['nickname'] = $row['nickname'];
                }
            }
            $pager = pagination($total, $pindex, $psize, '', array('before' => 1, 'after' => 0));
        }

        include $this->template('winninginfo');
    }
}

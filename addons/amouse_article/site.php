<?php
/**
 * 微官网模块微站定义
 *
 * @author 史中营 qq:800083075
 * @url http://w.mamani.cn
 */
defined('IN_IA') or exit('Access Denied');
include_once IA_ROOT . '/addons/amouse_article/model.php';
define("AMOUSE_ARTICLE", "amouse_article");
define("RES", "../addons/".AMOUSE_ARTICLE."/style/");
class Amouse_articleModuleSite extends WeModuleSite {

    public function __app($f_name){
        global $_W, $_GPC;
        $weid=$_W['uniacid'];
        $set=  pdo_fetch("SELECT * FROM ".tablename('fineness_sysset')." WHERE weid=:weid limit 1", array(':weid' => $weid));
        include_once 'inc/app/'.strtolower(substr($f_name, 8)).'.inc.php';
    }


    public function doMobileIndex() {
        global $_GPC, $_W;
        $weid=$_W['uniacid'];
        $this->__app(__FUNCTION__);
    }

    //默认设置
    public function doMobileWap() {
        global $_GPC, $_W;
        $weid=$_W['uniacid'];
        $set=  pdo_fetch("SELECT * FROM ".tablename('fineness_sysset')." WHERE weid=:weid limit 1", array(':weid' => $weid));
        $cid = intval($_GPC['cid']);
        $advlist= pdo_fetchall('SELECT * FROM '.tablename('fineness_adv')." WHERE weid=$weid and pid =$cid ");
        //独立选择分类模板
        $title = $category['name'];
        $result = pdo_fetchall("SELECT * FROM ".tablename('fineness_article_category')." WHERE uniacid =$weid AND parentid = $cid ORDER BY displayorder ASC, id ASC ");
        if($cid>0){
            $sql="SELECT id FROM " . tablename('fineness_article_category') . " WHERE uniacid =$weid AND parentid = $cid ORDER BY createtime ASC limit 1";
            $defaultid = pdo_fetchcolumn($sql);
            if($defaultid){
                $list = pdo_fetchall("SELECT * FROM ".tablename('fineness_article')." WHERE weid={$weid} AND pcate={$cid} AND ccate=$defaultid ORDER BY displayorder ASC ") ;
            }else{
                $list = pdo_fetchall("SELECT * FROM ".tablename('fineness_article')." WHERE weid={$weid} AND pcate={$cid}  ORDER BY displayorder ASC ") ;
            }
        }else{
            $list = pdo_fetchall("SELECT * FROM ".tablename('fineness_article')." WHERE weid=:weid ORDER BY displayorder ASC ", array(':weid' => $weid)) ;
        }
        
        include $this->template('themes/list14');
    }


    //List
    public function doMobileList() {
        global $_GPC, $_W;
        $weid=$_W['uniacid'];
        $set=  pdo_fetch("SELECT * FROM ".tablename('fineness_sysset')." WHERE weid=:weid limit 1", array(':weid' => $weid));
        $cid = intval($_GPC['cid']);
        $category = pdo_fetch("SELECT * FROM " . tablename('fineness_article_category') . " WHERE id = '{$cid}'");

        $advlist= pdo_fetchall('SELECT * FROM '.tablename('fineness_adv')." WHERE weid=:weid and pid ='{$cid}' ", array(':weid' =>$weid));
        $result = pdo_fetchall("SELECT * FROM " . tablename('fineness_article_category') . " WHERE uniacid =$weid AND parentid = $cid ORDER BY displayorder ASC, id ASC ");
        //独立选择分类模板
        $title = $category['name'];
        $op=$_GPC['op'];
        if(!empty($category['thumb'])) {
            $shareimg = toimage($category['thumb']);
        }else{
            $shareimg=IA_ROOT.'/addons/amouse_article/icon.jpg';
        }

        $childid=$_GPC['childid'];
        $list = pdo_fetchall("SELECT * FROM ".tablename('fineness_article')." WHERE weid={$weid} AND pcate={$cid} AND ccate={$childid} ORDER BY displayorder ASC ") ;
        $url=$_W['siteroot']."app/".substr($this->createMobileUrl('Index',array('cid'=>$cid,'uniacid'=>$weid),true),2);

        include $this->template('themes/list14');
    }


    public function doMobileDetail() {
        $this->__app(__FUNCTION__);
       // include $this->template('detail');
    }

    public function doMobileJubao() {
        include $this->template('jubao');
    }

    public  function doMobileLike(){
        global $_W, $_GPC;
        $weid=$_W['uniacid'];
        $record_id=$_GPC['articleid'];
        $record=pdo_fetch("SELECT * FROM ".tablename('fineness_article')." WHERE id= $record_id ");
        if(empty($record)){
            $res['ret']=501;
            return json_encode($res);
        }
        if(pdo_update('fineness_article',array('zanNum'=>$record['zanNum']+1), array('id'=>$record_id))){
            $res['ret']=0;
            return json_encode($res);
        }
    }

    //一键关注
    public function doMobileTuijian() {
        global $_GPC, $_W;
        $weid=$_W['uniacid'];
        $cfg = $this->module['config'];
        $list = pdo_fetchall("SELECT * FROM ".tablename('wx_tuijian')." WHERE weid=:weid ORDER BY createtime DESC ", array(':weid' => $weid)) ;
        include $this->template('tuijian');
    }

    //后台程序 inc/web文件夹下
    public function __web($f_name){
        global $_W, $_GPC;
        $weid=$_W['uniacid'];
        include_once 'inc/web/'.strtolower(substr($f_name, 5)).'.inc.php';
    }

    //分类关联
    public function doWebCategory() {
        $this->__web(__FUNCTION__);
    }

    //文章关联
    public function doWebPaper() {
        $this->__web(__FUNCTION__);
    }

    //系统设置
    public function doWebSysset() {
        $this->__web(__FUNCTION__);
    }

    //一键关注设置
    public function doWebHutui() {
        $this->__web(__FUNCTION__);
    }

    //幻灯片管理
    public function doWebSlide(){
        $this->__web(__FUNCTION__);
    }


    //广告管理
    public function doWebAdv() {
        $this->__web(__FUNCTION__);
    }

    public function doWebjiaocheng() {
        include $this->template('help');
    }


}

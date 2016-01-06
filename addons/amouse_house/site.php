<?php

/**
 * @url
 */
defined('IN_IA') or exit('Access Denied');
define("AMOUSE_HOUSE_RES", "../addons/amouse_house/style");
class Amouse_HouseModuleSite extends WeModuleSite {

    //首页
    public function doMobileIndex(){
        global $_W,$_GPC;
        $pindex= max(1, intval($_GPC['page']));
        $psize= 20;
        $weid=$_W['uniacid'];
        $openid=$_W['fans']['from_user'] ;
        $setting= $this->get_sysset($weid);
        $followed = !empty($_W['openid']);
        if ($followed) {
            $mf = pdo_fetch("select follow from " . tablename('mc_mapping_fans') . " where openid=:openid limit 1", array(":openid" => $_W['openid']));
            $followed = $mf['follow'] == 1;
        }
        if(!$followed){
            $followurl = $setting['guanzhuUrl'];
            header("location:$followurl");
        }
        $location_c= trim($setting['defcity']) ? trim($setting['defcity']) :'中国';
        $condition= " WHERE weid='{$weid}' AND status='1' ";
        $type = $_GPC['type'];
        if ($type!='') {
            $condition .= " AND type = '".$type."'";
        }
        if(!empty($setting['defcity'])){
            $condition .= " AND location_c LIKE '%".$setting['defcity']."%'";
        }
        $list= pdo_fetchall('SELECT * FROM '.tablename('amouse_house')." $condition ORDER BY createtime DESC LIMIT ".($pindex -1) * $psize.','.$psize); //分页
        $total= pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename('amouse_house').$condition);
        $slides = pdo_fetchall("select * from ".tablename('amouse_house_slide')." where weid = ".$weid." and isshow = 1 order by listorder desc");
        $pager = pagination($total, $pindex, $psize);

        $pageend=ceil($total/$psize);
        if($total/$psize!=0 && $total>=$psize){
            $pageend++;
        }
        $url=$_W['siteroot']."app/".substr($this->createMobileUrl('index'),2);
        include $this->template('house/index');
    }


    public function doMobileNew(){
        global $_W,$_GPC;
        $a= !empty($_GPC['a']) ? $_GPC['a'] : 'rent';
        load()->func('file');
        load()->func('tpl');

        $wxid= !empty($_GPC['wxid']) ? $_GPC['wxid'] : $_W['fans']['from_user'];
        $data = array(
            'weid'=> $_W['uniacid'],
            'title'=> trim($_GPC['title']),
            'price'=> trim($_GPC['price']),
            'square_price'=> trim($_GPC['square_price']),
            'area'=>trim($_GPC['area']),
            'house_type'=> $_GPC['house_type'],
            'floor'=> trim($_GPC['floor']),
            'orientation'=> $_GPC['orientation'],
            'createtime'=> TIMESTAMP,
            'type'=> trim($_GPC['type']),
            'status'=> 0,
            'recommed'=>0,
            'contacts'=> trim($_GPC['contacts']),
            'phone'=> trim($_GPC['phone']),
            'introduction'=> trim($_GPC['introduction']),
            'openid'=> $wxid,
            'thumb1' => $_GPC['thumb1'],
            'thumb2' => $_GPC['thumb2'],
            'thumb3' => $_GPC['thumb3'],
            'thumb4' => $_GPC['thumb4'],
            'location_p' => trim($_GPC['location_p']),
            'location_c' => trim($_GPC['location_c']),
            'location_a' => trim($_GPC['location_a']),
            'place' => trim($_GPC['place']),
            'lng' => trim($_GPC['lng']),
            'lat' => trim($_GPC['lat']),
        );
        if($a == 'rent') {
            if ($_W['ispost']) {
                pdo_insert('amouse_house',$data);
                message('提交房产信息成功，请等待审核!',$this->createMobileUrl('index',array('type'=>$_GPC['type'])),'success');
            }
            include $this->template('house/rent_new');
        }elseif($a='house'){
            if ($_W['ispost']) {
                pdo_insert('amouse_house',$data);
                message('提交房产信息成功，请等待审核!',$this->createMobileUrl('index',array('type'=>$_GPC['type'])),'success');
            }
            include $this->template('house/house_new');
        }
    }

    public function doMobileDetail(){
        global $_W,$_GPC;
        load()->func('tpl'); 
        $weid= $_W['uniacid'];
		$setting= $this->get_sysset($weid);
        $id= intval($_GPC['id']);
        $a= !empty($_GPC['a']) ? $_GPC['a'] : 'rent';
        $type =!empty($_GPC['type']) ? $_GPC['type'] : '0';
        if($a == 'rent') {
            $item= pdo_fetch("SELECT * FROM ".tablename('amouse_house')." WHERE weid=:weid AND id = :id", array(':weid'=>$weid,':id' => $id));
            if(empty($item)) {
                message("抱歉，信息不存在!", referer(), "error");
            }
            if($type==0||$type==1){
                $recommeds=pdo_fetchall("SELECT * FROM ".tablename('amouse_house')." WHERE weid=:weid AND (type='0' OR type='1') AND recommed=1 ORDER BY createtime ", array(':weid'=>$weid));
                $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('a'=>'rent','type'=>$type,'id'=>$item[id]),true),2);
            }elseif($type==2||$type==3){
                $recommeds=pdo_fetchall("SELECT * FROM ".tablename('amouse_house')." WHERE weid=:weid AND (type='2' OR type='3') AND recommed=1 ORDER BY createtime ", array(':weid'=>$weid));
                $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('a'=>'rent','type'=>$type,'id'=>$item[id]),true),2);
            }

            include $this->template('house/rent_detail');
        }elseif($a='house'){
            $item= pdo_fetch("SELECT * FROM ".tablename('amouse_newflats')." WHERE weid=:weid AND id = :id", array(':weid'=>$weid,':id' => $id));
            if(empty($item)) {
                message("抱歉，信息不存在!", referer(), "error");
            }
            $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('a'=>'house','id'=>$item[id]),true),2);
            include $this->template('house/new_house_detail');
        }
    }

    //新楼盘
    public function doMobileList(){
        global $_W,$_GPC;
        $pindex= max(1, intval($_GPC['page']));
        $psize= 20;
        $weid=$_W['uniacid'];
		 $setting= $this->get_sysset($weid);
        $condition= " WHERE weid='{$weid}' ";
        $list= pdo_fetchall('SELECT * FROM '.tablename('amouse_newflats')." $condition ORDER BY createtime DESC LIMIT ".($pindex -1) * $psize.','.$psize); //分页
        $total= pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename('amouse_newflats').$condition);
        $slides = pdo_fetchall("select * from ".tablename('amouse_house_slide')." where weid = ".$weid." and isshow = 1 order by listorder desc");
        $pager = pagination($total, $pindex, $psize);
        $pageend=ceil($total/$psize);
        if($total/$psize!=0 && $total>=$psize){
            $pageend++;
        }
        $url=$_W['siteroot']."app/".substr($this->createMobileUrl('list'),2);
        include $this->template('house/new_house_list');
    }

    public function doMobilePosition(){
        global $_W,$_GPC;
		$weid=$_W['uniacid'];
		$setting= $this->get_sysset($weid);
        $op= !empty($_GPC['op']) ? $_GPC['op'] : 'display';
        if($op=='display'){
            $location_ps = pdo_fetchall("select distinct location_p from ".tablename('amouse_house')." where  status=1 and weid = ".$weid);
            $cities = pdo_fetchall("select distinct location_p,location_c from ".tablename('amouse_house')." where status = 1 and weid = ".$weid);
        }
        if($op=='positionsort'){
            $op = 'sort';
            $location_c = trim($_GPC['location_c']);
            $slides = pdo_fetchall("select * from ".tablename('amouse_house_slide')." where weid = ".$weid." and isshow = 1 order by listorder desc");
            $pindex= max(1, intval($_GPC['page']));
            $psize= 20;
            $type = $_GPC['type'];
            $list = pdo_fetchall("SELECT * FROM ".tablename('amouse_house')." WHERE weid ='{$weid}' AND location_c = '".$location_c."' AND status=1 ORDER BY createtime DESC LIMIT ".($pindex -1) * $psize.','.$psize); //分页
            $total= pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename('amouse_house')." WHERE weid ='{$weid}' AND location_c = '".$location_c."' AND status=1 ORDER BY createtime DESC");
            $pager = pagination($total, $pindex, $psize);
            include $this->template('house/index');
            exit;
        }

        include $this->template('house/position');
    }


    private function fileUpload($file, $type) {
        global $_W;
        set_time_limit(0);
        $_W['uploadsetting'] = array();
        $_W['uploadsetting']['images']['folder'] = 'image';
        $_W['uploadsetting']['images']['extentions'] = array('jpg', 'png', 'gif');
        $_W['uploadsetting']['images']['limit'] = 50000;
        $result = array();
        $upload = file_upload($file, 'image');
        if (is_error($upload)) {
            message($upload['message'], '', 'ajax');
        }
        $result['url'] = $upload['url'];
        $result['error'] = 0;
        $result['filename'] = $upload['path'];
        return $result;
    }

    public function doMobileUploadImage() {
        global $_W;
        load()->func('file');
        if (empty($_FILES['file']['name'])) {
            $result['message'] = '请选择要上传的文件！';
            exit(json_encode($result));
        }

        if ($file = $this->fileUpload($_FILES['file'], 'image')) {
            if ($file['error']) {
                exit('0');
                //exit(json_encode($file));
            }
            $result['url'] = $_W['config']['upload']['attachdir'] . $file['filename'];
            $result['error'] = 0;
            $result['filename'] = $file['filename'];
            exit(json_encode($result));
        }
    }


    public function doMobileUpdateCount(){
        global $_GPC, $_W;
        $id = intval($_GPC['id']);
        $op = !empty($_GPC['op']) ? $_GPC['op'] : 'read';
        $result['state'] = 0 ;
        $result['msg'] = ' ';
        $detail = pdo_fetch("SELECT * FROM " . tablename('amouse_newflats') . " WHERE `id`=:id", array(':id'=>$id));
        if($detail){
            if($op=="read"){
                $data=array('readcount'=>$detail['readcount']+1);
                pdo_update('amouse_newflats', $data, array('id' => $id));
            }elseif($op=="like"){
                $data2=array('like'=>$detail['like']+1);
                pdo_update('amouse_newflats',$data2, array('id' => $id));
            }
            $result['msg'] = ' ';
            $result['state'] = 1 ;
        }
        message($result, '', 'ajax');
    }

    private function checkIsWeixin(){
        $user_agent= $_SERVER['HTTP_USER_AGENT'];
        if(strpos($user_agent, 'MicroMessenger') === false) {
            echo "本页面仅支持微信访问!非微信浏览器禁止浏览!";
            exit;
        }
    }

    private function checkCookie() {
        global $_W,$_GPC;
        $weid=$_W['uniacid'];
        $setting= $this->get_sysset($weid);
        $oauth_openid= "amouse_house_zombie_".$weid;
        if(empty($_COOKIE[$oauth_openid])) {
            if(!empty($setting) && $setting['isoauth'] == '0') {
                if(!empty($setting) && !empty($setting['appid']) && !empty($setting['appsecret'])) { // 判断是否是借用设置
                    $appid= $setting['appid'];
                    $secret= $setting['appsecret'];
                }
            }
            $url =  $_W['siteroot']."app/".substr($this->createMobileUrl('userinfo',array(),true),2);
            $oauth2_code = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=0#wechat_redirect";
            header("location:$oauth2_code");
            exit;
        }
    }


    public function doMobileUserinfo() {
        global $_GPC, $_W;
        $weid= $_W['uniacid']; //当前公众号ID
        load()->func('communication');
        //用户不授权返回提示说明
        if($_GPC['code'] == "authdeny") {
            $url = $this->createMobileUrl('index', array(),true);
            $url2 =  $_W['siteroot']."app/".substr($url,2);
            header("location:$url2");
            exit('authdeny');
        }
        //高级接口取未关注用户Openid
        if(isset($_GPC['code'])) {
            //第二步：获得到了OpenID
            $serverapp= $_W['account']['level'];
            $setting= $this->get_sysset($weid);
            if(!empty($setting) && !empty($setting['appid']) && !empty($setting['appsecret'])) { // 判断是否是借用设置
                $appid= $setting['appid'];
                $secret= $setting['appsecret'];
            }
            $state= $_GPC['state'];
            //1为关注用户, 0为未关注用户
            $rid= $_GPC['id'];
            //查询活动时间
            $code= $_GPC['code'];
            $oauth2_code= "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
            $content= ihttp_get($oauth2_code);
            $token= @ json_decode($content['content'], true);
            if(empty($token) || !is_array($token)
                || empty($token['access_token']) || empty($token['openid'])) {
                echo '<h1>获取微信公众号授权'.$code.'失败[无法取得token以及openid], 请稍后重试！ 公众平台返回原始数据为: <br />'.$content['meta'].'<h1>';
                exit;
            }
            $from_user= $token['openid'];
            //未关注用户和关注用户取全局access_token值的方式不一样
            if($state == 1) {
                $oauth2_url= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
                $content= ihttp_get($oauth2_url);
                $token_all= @ json_decode($content['content'], true);
                if(empty($token_all) || !is_array($token_all) || empty($token_all['access_token'])) {
                    echo '<h1>获取微信公众号授权失败[无法取得access_token], 请稍后重试！ 公众平台返回原始数据为: <br />'.$content['meta'].'<h1>';
                    exit;
                }
                $access_token= $token_all['access_token'];
                $oauth2_url= "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$from_user."&lang=zh_CN";
            } else {
                $access_token= $token['access_token'];
                $oauth2_url= "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$from_user."&lang=zh_CN";
            }

            //使用全局ACCESS_TOKEN获取OpenID的详细信息
            $content= ihttp_get($oauth2_url);
            $info= @ json_decode($content['content'], true);
            if(empty($info) || !is_array($info) || empty($info['openid']) || empty($info['nickname'])) {
                echo '<h1>获取微信公众号授权失败[无法取得info], 请稍后重试！<h1>';
                exit;
            }

            $row= array('nickname' => $info["nickname"], 'realname' => $info["nickname"], 'gender' => $info['sex']);
            if(!empty($info["country"])) {
                $row['nationality']= $info["country"];
            }
            if(!empty($info["province"])) {
                $row['resideprovince']= $info["province"];
            }
            if(!empty($info["city"])) {
                $row['residecity']= $info["city"];
            }
            if(!empty($info["headimgurl"])) {
                $row['avatar']= $info["headimgurl"];
            }
            fans_update($info['openid'], $row);
            $oauth_openid= "amouse_house_zombie_".$_W['uniacid'];
            setcookie($oauth_openid, $info['openid'], time() + 3600 * 240);
            $url =   $_W['siteroot']."app/".substr($this->createMobileUrl('index',array()),2);
            header("location:$url");
            exit;
        } else {
            echo '<h1>网页授权域名设置出错!</h1>';
            exit;
        }
    }

    //房产管理 //shizhongying qq:214983937
    public function doWebHouse() {
        global $_GPC, $_W;
        load()->func('tpl');
        $op= $_GPC['op'] ? $_GPC['op'] : 'display';
        $weid= $_W['uniacid'];

        if($op == 'display') {
            $pindex= max(1, intval($_GPC['page']));
            $psize= 20; //每页显示
            $starttime= empty($_GPC['date']['start']) ? strtotime('-1 month') : strtotime($_GPC['date']['start']);
            $endtime= empty($_GPC['date']['end']) ? TIMESTAMP : strtotime($_GPC['date']['end']) + 86399*2;
            $pindex= max(1, intval($_GPC['page']));
            $psize= 20;
            $condition=" WHERE weid=:weid AND createtime>=:starttime AND createtime<=:endtime";
            $paras= array(':weid'=>$weid,':starttime' => $starttime, ':endtime' => $endtime);
            $status=$_GPC['status'];
            $type = $_GPC['type'];  //类型 出租 ---
            if ($status!='') {
                $condition .= " AND status='".$status. "'";
            }
            if (!empty($type)) {
                $condition .= " AND type = '" . $type. "'";
            }
            if(!empty($_GPC['title'])) {
                $condition .= " AND title LIKE '%".$_GPC['title']."%'";
            }
            $list= pdo_fetchall('SELECT * FROM '.tablename('amouse_house')." $condition ORDER BY createtime desc  LIMIT ".($pindex -1) * $psize.','.$psize, $paras);
            $total= pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('amouse_house').$condition, $paras);
            $pager= pagination($total, $pindex, $psize);

        }elseif($op == 'verify') {  //审核
            $id= intval($_GPC['id']);
            if($id > 0) {
                if(pdo_update('amouse_house',array('status' => 1), array('id' => $id)) === false) {
                    message('审核失败, 请稍后重试.', 'error');
                }
                message('审核成功！', $this->createWebUrl('house', array('op' => 'display', 'name' => 'amouse_house')), 'success');
            }
        }elseif($op == 'recommed'){//推荐
            $id= intval($_GPC['id']);
            $recommed= intval($_GPC['recommed']);
            if($recommed==1){
                $msg='推荐';
            }elseif($recommed==0){
                $msg='取消推荐';
            }
            if($id > 0) {
                pdo_update('amouse_house',array('recommed' =>$recommed), array('id' => $id)) ;
                message($msg.'成功！', $this->createWebUrl('house', array('op' => 'display', 'name' => 'amouse_house')), 'success');
            }
        }elseif($op == 'del') { //删除
            if(isset($_GPC['delete'])) {
                $ids= implode(",", $_GPC['delete']);
                $sqls= "delete from  ".tablename('amouse_house')."  where id in(".$ids.")";
                pdo_query($sqls);
                message('删除成功！', referer(), 'success');
            }
            $id= intval($_GPC['id']);
            $temp= pdo_delete("amouse_house", array('id' => $id));
            message('删除数据成功！', $this->createWebUrl('house', array('op' => 'display', 'name' => 'amouse_house')), 'success');
        }elseif($op == 'post') {
            $id= intval($_GPC['id']);
            load()->func('tpl');
            if($id > 0) {
                $item= pdo_fetch('SELECT * FROM '.tablename('amouse_house')." WHERE id=:id", array(':id' => $id));
            }

            if(checksubmit('submit')) {
                $data = array(
                    'title'=> trim($_GPC['title']),
                    'price'=> trim($_GPC['price']),
                    'square_price'=> trim($_GPC['square_price']),
                    'area'=>trim($_GPC['area']),
                    'house_type'=> $_GPC['house_type'],
                    'floor'=> trim($_GPC['floor']),
                    'orientation'=> $_GPC['orientation'],
                    'contacts'=> trim($_GPC['contacts']),
                    'location_p' => $_GPC['district']['province'],
                    'location_c' => $_GPC['district']['city'],
                    'location_a' => $_GPC['district']['district'],
                    'phone'=> trim($_GPC['phone']),
                    'lng' => $_GPC['baidumap']['lng'],
                    'lat' => $_GPC['baidumap']['lat'],
                    'thumb1'=>trim($_GPC['thumb1']),
                    'thumb3'=>trim($_GPC['thumb3']),
                    'thumb2'=>trim($_GPC['thumb2']),
                    'introduction'=> trim($_GPC['introduction'])  );

                if(empty($id)) {
                    pdo_insert('amouse_house', $data);
                } else {
                    pdo_update('amouse_house', $data, array('id' => $id));
                }
                message('更新新楼盘数据成功！', $this->createWebUrl('house', array('op' => 'display', 'name' => 'amouse_house')), 'success');
            }
        }
        include $this->template('web/house');
    }

    //新楼盘管理
    public function doWebPremises(){
        global $_GPC, $_W;
        $op = $_GPC['op'] ? $_GPC['op'] : 'display';
        $weid = $_W['uniacid'];
        if ($op == 'display') {
            $pindex= max(1, intval($_GPC['page']));
            $psize= 20; //每页显示
            //$condition= "WHERE 1=1";
            if(!empty($_GPC['keyword'])) {
                $condition .= " WHERE name LIKE '%".$_GPC['keyword']."%'";
            }
            $list= pdo_fetchall('SELECT * FROM '.tablename('amouse_newflats')." $condition  ORDER BY createtime ASC LIMIT ".($pindex -1) * $psize.','.$psize);
            $total= pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('amouse_newflats').$condition);
            $pager= pagination($total, $pindex, $psize);

        }elseif($op == 'post') {
            $id= intval($_GPC['id']);
            load()->func('tpl');
            if($id > 0) {
                $item= pdo_fetch('SELECT * FROM '.tablename('amouse_newflats')." WHERE id=:id", array(':id' => $id));
            }

            if(checksubmit('submit')) {
                $title= trim($_GPC['name']) ? trim($_GPC['name']) : message('请填写楼盘名称！');
                $logo= $_GPC['thumb'] ?  $_GPC['thumb']  : message('请上传楼盘图片！');
                $prefix= trim($_GPC['type']);
                $insert= array('name' => $title,
                    'thumb' => $logo,
                    'price' => $_GPC['price'],
                    'type' => $prefix,
                    'years'=>$_GPC['years'],
                    'wytype'=>$_GPC['wytype'],
                    'cqtype'=>$_GPC['cqtype'],
                    'jzarea'=>$_GPC['jzarea'],
                    'ratio'=>$_GPC['ratio'],
                    'floor_area'=>$_GPC['floor_area'],
                    'afforestation'=>$_GPC['afforestation'],
                    'total'=>$_GPC['total'],
                    'door_area'=>$_GPC['door_area'],
                    'road_transport'=>$_GPC['road_transport'],
                    'investors'=>$_GPC['investors'],
                    'developers'=>$_GPC['developers'],
                    'property_compay'=>$_GPC['property_compay'],
                    'propertypay'=>$_GPC['propertypay'],
                    'features'=>$_GPC['features'],
                    'sales_addres'=>$_GPC['sales_addres'],
                    'checkin_time'=>$_GPC['checkin_time'],
                    'sales_status'=>$_GPC['sales_status'],
                    'average_price'=>$_GPC['average_price'],
                    'discounted_costs'=>$_GPC['discounted_costs'],'payment'=>$_GPC['payment'],
                    'business'=>$_GPC['business'],'banks'=>$_GPC['banks'],'trading_area'=>$_GPC['trading_area'],
                    'park'=>$_GPC['park'],'hotel'=>$_GPC['hotel'],'supermarket'=>$_GPC['supermarket'],
                    'humanities'=>$_GPC['humanities'],'supporting'=>$_GPC['supporting'],'internal'=>$_GPC['internal'],
                    'parking_number'=>$_GPC['parking_number'],'base'=>$_GPC['base'],'equally'=>$_GPC['equally'],
                    'surrounding'=>$_GPC['surrounding'],'landscape'=>$_GPC['landscape'],'hospitals'=>$_GPC['hospitals'],
                    'school'=>$_GPC['school'],'traffic'=>$_GPC['traffic'],
                    'construction'=>$_GPC['construction'],
                    'design'=>$_GPC['design'],'salecom'=>$_GPC['salecom'],
                    'address'=>$_GPC['address'],
                    'like'=>0,
                    'introduction'=>htmlspecialchars_decode($_GPC['introduction']),
                    'weid' => $_W['uniacid'], 'createtime' => TIMESTAMP);

                if(empty($id)) {
                    pdo_insert('amouse_newflats', $insert);
                } else {
                    pdo_update('amouse_newflats', $insert, array('id' => $id));
                }
                message('更新新楼盘数据成功！', $this->createWebUrl('premises', array('op' => 'display', 'name' => 'amouse_house')), 'success');
            }
        }elseif($op == 'del') { //删除
            if(isset($_GPC['delete'])) {
                $ids= implode(",", $_GPC['delete']);
                $sqls= "delete from  ".tablename('amouse_newflats')."  where id in(".$ids.")";
                pdo_query($sqls);
                message('删除成功！', referer(), 'success');
            }
            $id= intval($_GPC['id']);
            $temp= pdo_delete("amouse_newflats", array('id' => $id));
            message('删除数据成功！', $this->createWebUrl('premises', array('op' => 'display', 'name' => 'amouse_house')), 'success');
        }


        include $this->template('web/flats');
    }

    //幻灯片管理
    public function doWebSlide() {
        global $_W, $_GPC;
        $weid= $_W['uniacid'];
        load()->func('tpl');
        if (checksubmit('submit')) {
            if (!empty($_GPC['slide-new'])) {
                foreach ($_GPC['slide-new'] as $index => $row) {
                    if (empty($row)) {
                        continue;
                    }
                    $data = array(
                        'weid' => $weid,
                        'slide' => $_GPC['slide-new'][$index],
                        'url' => $_GPC['url-new'][$index],
                        'listorder' => $_GPC['listorder-new'][$index],
                    );
                    pdo_insert('amouse_house_slide', $data);
                }
            }
            if (!empty($_GPC['attachment'])) {
                foreach ($_GPC['attachment'] as $index => $row) {
                    if (empty($row)) {
                        continue;
                    }
                    $data = array(
                        'weid' => $weid,
                        'slide' => $_GPC['attachment'][$index],
                        'url' => $_GPC['url'][$index],
                        'listorder' => $_GPC['listorder'][$index],
                        'isshow' => $_GPC['isshow'][$index],
                    );
                    pdo_update('amouse_house_slide', $data, array('id' => $index));
                }
            }
            message('幻灯片更新成功！', $this->createWebUrl('slide'));
        }

        if ($op == 'delete') {
            $id = intval($_GPC['id']);
            if (!empty($id)) {
                $item = pdo_fetch("SELECT * FROM " . tablename('amouse_house_slide') . " WHERE id = :id", array(':id' => $id));
                if (empty($item)) {
                    message('图片不存在或是已经被删除！');
                }
                pdo_delete('amouse_house_slide', array('id' => $item['id']));
            } else {
                $item['attachment'] = $_GPC['attachment'];
            }
            //file_delete($item['attachment']);
            message('删除成功！', referer(), 'success');
        }
        $photos = pdo_fetchall("SELECT * FROM " . tablename('amouse_house_slide') . " WHERE weid = :weid ORDER BY listorder DESC", array(':weid' => $weid));
        include $this->template('web/slide');
    }


    public function get_sysset($weid=0) {
        global $_GPC, $_W;
        return pdo_fetch("SELECT * FROM ".tablename('amouse_sysset')." WHERE weid=:weid limit 1", array(':weid' => $weid));
    }

    //参数设置
    public function doWebSysset() {
        global $_W, $_GPC;
        $weid= $_W['uniacid'];
        $set= $this->get_sysset($weid);
        load()->func('tpl');
        if(checksubmit('submit')) {
            $data= array(
            'weid' => $weid,
            'guanzhuUrl'=>trim($_GPC['guanzhuUrl']),
            'copyright'=>trim($_GPC['copyright']),
            'broker'=>trim($_GPC['broker']),
            'jjrmobile'=>trim($_GPC['jjrmobile']),
            'newflat_images'=>trim($_GPC['newflat_images']),
            'appid_share'=>trim($_GPC['appid_share']),
            'appsecret_share'=>trim($_GPC['appsecret_share']),
            'isoauth' => trim($_GPC['isoauth']),
            'defcity' => trim($_GPC['defcity']),
            'isshow' => trim($_GPC['isshow'])
            );
            if($_GPC['isoauth']==0){
                $data['appid']=trim($_GPC['appid']) ;
                $data['appsecret']=trim($_GPC['appsecret']);
            }else{
                $data['appid']='' ;
                $data['appsecret']='';
            }
            if(!empty($set)) {
                pdo_update('amouse_sysset', $data, array('id' => $set['id']));
            } else {
                pdo_insert('amouse_sysset', $data);
            }
            message('更新参数设置成功！', 'refresh');
        }
        if(!isset($set['isoauth'])) {
            $set['isoauth']= 1;
            $set['isshow']= 1;
        }
        include $this->template('web/sysset');
    }
}
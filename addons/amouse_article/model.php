<?php
function amouse_article_site_article($params = array()){
    global $_GPC, $_W;
    extract($params);
    $result = array();
    $condition = " WHERE weid = '{$_W['uniacid']}'";
    if (!empty($cid)) {
        $category = pdo_fetch("SELECT parentid FROM " . tablename('fineness_article_category') . " WHERE id = '{$cid}'");
        if (!empty($category['parentid'])) {
            $condition .= " AND ccate = '{$cid}'";
        } else {
            $condition .= " AND pcate = '{$cid}'";
        }
    }
    $sql = "SELECT * FROM " . tablename('fineness_article') . $condition . ' ORDER BY displayorder ASC';
    $result['list'] = pdo_fetchall($sql);
    return $result;
}


function amouse_article_site_category($params = array()){
    global $_GPC, $_W;
    extract($params);
    if (!isset($parentid)) {
        $condition = "";
    } else {
        $condition = " AND parentid = '$parentid'";
    }
    $category = array();
    $result = pdo_fetchall("SELECT * FROM " . tablename('fineness_article_category') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY parentid ASC, displayorder ASC, id ASC ");
    if (!isset($parentid)) {
        if (!empty($result)) {
            foreach ($result as $row) {
                $row['thumb'] = (strpos($row['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $row['thumb'] : $row['thumb'];
                if (empty($row['parentid'])) {
                    $category[$row['id']] = $row;
                } else {
                    $category[$row['parentid']]['children'][$row['id']] = $row;
                }
            }
        }
    } else {
        $category = $result;
    }
    return $category;
}

function amouse_article_site_pcategory($cid){
    global $_GPC, $_W;
    if($cid>0){
        $condition = " AND parentid = $cid ";
    }
    $category = array();
    $result = pdo_fetchall("SELECT * FROM " . tablename('fineness_article_category') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY parentid ASC, displayorder ASC, id ASC ");

    return $result;
}

function amouse_article_site_ccategory($params = array()){
    global $_GPC, $_W;
    extract($params);

    $condition = " AND parentid = $cid ";
    $category = array();
    $result = pdo_fetchall("SELECT * FROM " . tablename('fineness_article_category') . " WHERE uniacid = '{$_W['uniacid']}' $condition ORDER BY parentid ASC, displayorder ASC, id ASC ");

    if (!isset($parentid)) {
        if (!empty($result)) {
            foreach ($result as $row) {
                $row['thumb'] = (strpos($row['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $row['thumb'] : $row['thumb'];
                if (empty($row['parentid'])) {
                    $category[$row['id']] = $row;
                } else {
                    $category[$row['parentid']]['children'][$row['id']] = $row;
                }
            }
        }
    } else {
        $category = $result;
    }
    return $category;
}


function amouse_article_site_list($cid=0){
    global $_GPC, $_W;
    $weid=$_W['uniacid'];
    if($cid>0){
        $list = pdo_fetchall("SELECT * FROM ".tablename('fineness_article')." WHERE weid={$weid} AND ccate={$cid}  ORDER BY displayorder ASC ") ;
    }
    return $list;
}


function amouse_article_site_get_prev($params = array()) {
    global $_GPC, $_W;
    extract($params);
    $condition=" WHERE weid={$_W['uniacid']}";
    if (isset($pcate)) {
        $condition .= " AND pcate=$pcate";
    }
    if (isset($id)) {
        $condition .= " AND id<$id ";
    }
    $result = pdo_fetch("SELECT * FROM ".tablename('fineness_article')." $condition ORDER BY displayorder DESC LIMIT 1");
    if (!empty($result)) {
       //$result['url']=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('id'=>$result['id'],'uniacid'=>$_W['uniacid'], 'track_id' => $_W['fans']['from_user'], 'track_type' => 'click'),true),2);
        $result['thumb'] = (strpos($result['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $result['thumb'] : $result['thumb'];
    }
    return $result;
}

function amouse_article_site_get_last($params = array()) {
    global $_GPC, $_W;
    extract($params);
    $condition = "";
    if (isset($pcate)) {
        $condition .= " AND pcate = $pcate";
    }
    $result = pdo_fetch("SELECT * FROM ".tablename('fineness_article')." WHERE  weid='{$_W['uniacid']}' $condition ORDER BY id DESC LIMIT 1");
    if (!empty($result)) {
        $result['url'] = url('mobile/module/detail', array('m'=>'amouse_article', 'id' => $result['id'], 'i' => $_W['uniacid'], 'track_id' => $_W['fans']['from_user'], 'track_type' => 'click'));
        $result['thumb'] = (strpos($result['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $result['thumb'] : $result['thumb'];
    }
    return $result;
}

function amouse_article_site_get_next($params = array()) {
    global $_GPC, $_W;
    extract($params);
    $condition = "";
    if (isset($pcate)) {
        $condition .= " AND pcate = $pcate";
    }
    if (isset($id)) {
        $condition .= " AND id > $id ";
    }
    $result = pdo_fetch("SELECT * FROM ".tablename('fineness_article')." WHERE weid = '{$_W['uniacid']}' $condition ORDER BY id ASC LIMIT 1");

    if (!empty($result)) {
        $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('id'=>$result['id'],'uniacid'=>$_W['uniacid']),true),2);
        $result['url'] = $url;
        $result['thumb'] = (strpos($result['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $result['thumb'] : $result['thumb'];
    }
    return $result;
}


function amouse_article_site_get_first($params = array()) {
    global $_GPC, $_W;
    extract($params);
    $condition = "";
    if (isset($pcate)) {
        $condition .= " AND pcate = $pcate";
    }
    $result = pdo_fetch("SELECT * FROM ".tablename('fineness_article')." WHERE weid = '{$_W['uniacid']}' $condition ORDER BY id ASC LIMIT 1");
    if (!empty($result)) {
        $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('id'=>$result['id'],'uniacid'=>$_W['uniacid']),true),2);
       $result['url'] =$url;
        $result['thumb'] = (strpos($result['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $result['thumb'] : $result['thumb'];
    }
    return $result;
}

function quickshare3_site_article_hot($params = array()) {
    global $_GPC, $_W;
    $result = array();
    $condition = " WHERE weid = '{$_W['uniacid']}'";
    $sql = "SELECT * FROM ".tablename('fineness_article'). $condition. ' ORDER BY clickNum DESC';
    $result = pdo_fetchall($sql . " LIMIT 10");
    if (!empty($result)) {
        foreach ($result as &$row) {
            $url=$_W['siteroot']."app/".substr($this->createMobileUrl('detail',array('id'=>$result['id'],'uniacid'=>$_W['uniacid']),true),2);
            $result['url'] =$url;
            $row['thumb'] = (strpos($row['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $row['thumb'] : $row['thumb'];
        }
    }
    return $result;
}

function amouse_article_weixin_hot($params = array()){
    global $_GPC, $_W;
    extract($params);
    $result = array();
    $condition = " WHERE weid = '{$_W['uniacid']}' and hot=1 ";
    //$condition = " WHERE weid = '{$_W['uniacid']}'";
    $sql = "SELECT * FROM " . tablename('wx_tuijian') . $condition . ' ORDER BY hot DESC';
    $result['list'] = pdo_fetchall($sql);
    return $result;
}

function amouse_article_advertising_random($params = array()) {
    global $_GPC, $_W;
    $result = array();
    $sql = "SELECT * FROM `ims_fineness_adv_er` AS t1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `ims_fineness_adv_er`)-(SELECT MIN(id) FROM `fineness_adv_er`))+(SELECT MIN(id) FROM `ims_fineness_adv_er`)) AS id) AS t2 WHERE t1.id >= t2.id ORDER BY t1.id LIMIT 1";
    $result = pdo_fetch($sql);
    if(!empty($result)) {
        $result['url']=$result['link'];
        $row['thumb'] = (strpos($row['thumb'], 'http://') === FALSE) ? $_W['attachurl'] . $row['thumb'] : $row['thumb'];
    }
    return $result;
}
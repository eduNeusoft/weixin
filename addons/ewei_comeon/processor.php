<?php

/**
 * 来吧来吧
 *
 * @author ewei
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Ewei_comeonModuleProcessor extends WeModuleProcessor {

    public function respond() {
        global $_W;
        $rid = $this->rule;
        $sql = "SELECT title,description,thumb,isshow,starttime,endtime FROM " . tablename('ewei_comeon_reply') . " WHERE `rid`=:rid LIMIT 1";
        $row = pdo_fetch($sql, array(':rid' => $rid));

        if ($row == false) {
            return $this->respText("活动已取消...");
        }
        return $this->respNews(array(
                    'Title' => $row['title'],
                    'Description' => $row['description'],
                    'PicUrl' => tomedia($row['thumb']),
                    'Url' => $this->createMobileUrl('index', array('id' => $rid)) ,
        ));
    }

}

<?php
global $_GPC, $_W;
        $weid = $_W['uniacid'];
		$openid = $_W['openid']; 
		$res = $this->getusers($weid,$openid);
        
		if(empty($res) || empty($res['uheightL']) || empty($res['uheightH'])){
		   echo json_encode(2);
		   exit;
		}
		if($res['gender']=='0'){
		   echo json_encode(3);
		   exit;
		}
		$page = intval($_GPC['truepage']);//页码
		
        $pindex = max(1, intval($_GPC['truepage']));
        $psize =5;
        $condition = '';
		$isshow=1;
		$tablename = tablename("hnfans");
        $stores = pdo_fetchall("SELECT * FROM " . $tablename . " WHERE  yingcang=1 AND weid=:weid  AND isshow=:isshow AND gender <>:gender  AND gender <>'0'  AND lxxingzuo=:lxxingzuo AND height>{$res['uheightL']} AND height<{$res['uheightH']} {$condition} ORDER BY time DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, array(':weid' =>$weid,':isshow'=>$isshow,':gender'=>$res['gender'],':lxxingzuo'=>$res['lxxingzuo']));
		//先取得当事人的位置  
		$julires = $this->getusers($weid,$openid);
        if(!empty($stores)){
					foreach($stores as $row){
						if(!empty($row['lat']) && !empty($row['lng'])){
							if(!empty($julires['lat']) && !empty($julires['lng'])){
							   $juli[$row['id']]= "相距: ".$this->getDistance($julires['lat'],$julires['lng'],$row['lat'],$row['lng'])."km";
							}else{
							    $juli[$row['id']]= ""; 
							}
						}else{
							 $juli[$row['id']]= ""; 
						}
					}
			}
        $result_str = '';
		foreach($stores as $row) {
            $onclick2 = "'".$row['from_user']."'";
          $result_str .= '<li class="indexItem"><span  class="linka" onclick="checkself('.$onclick2.')">';
          if(preg_match('/http:(.*)/',$row['avatar'])){
			  $result_str .='<img src="'.$row['avatar'].'" alt="用户头像">';
		  }elseif(preg_match('/images(.*)/',$row['avatar'])){
			  $result_str .='<img src="'.$_W['attachurl'].$row['avatar'].'" alt="用户头像">';
		  }else{
		     $result_str .='<img src="./addons/meepo_weixiangqin/template/mobile/tpl/static/friend/images/cdhn80.jpg" alt="用户头像">'; 
		  }
           $result_str .='<div class="itemc"><p class="hcolor" style="font-size:13px;">'.cutstr($row['realname'],5,true);
		  if($row['gender']=='1'){
			  $result_str .="&nbsp;&nbsp;男";
		  }elseif( $row['gender']=='2'){
		      $result_str .="&nbsp;&nbsp;女";
		  }else{
		     $result_str .="&nbsp;&nbsp;保密";
		  }
		  $onclick = "'".$row['id']."','".$row['from_user']."'";
		   $result_str .='<font id="shopspostion" style="color:red;font-size:12px;">&nbsp;&nbsp;'.$juli[$row['id']].'</font></p>
          <p class="lcolor" style="font-size:13px;">微信:'.cutstr($row['nickname'],5,true).'&nbsp;&nbsp;'.$row['resideprovincecity'].'</p>
		  
        </div>
        <i class="arr"></i>
         </span>    
    	<div class="likebox">
    		<div class="likeit  fleft "><span class="hitlike" onclick="hitlikeone('.$onclick.');" id="'.$row['from_user'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$row['love'].'</span></div>
    		<div class="likeit letterit fright"><a class="hitmail"   href="'.$this->createMobileUrl('hitmail',array('toname'=>$row['nickname'],'toopenid'=>$row['from_user'])).'" target="__blank" style="color:#fff">聊一聊</a></div></div>
      
      </li><li class="dottedLine"></li>';
        }
        if ($result_str == '' || empty($stores)) {
            echo json_encode(0);
        } else {
            echo json_encode($result_str);
        }
	
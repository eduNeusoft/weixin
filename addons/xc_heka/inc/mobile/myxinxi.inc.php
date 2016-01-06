<?php



     	global $_GPC, $_W;

			load()->model('mc');

			load()->func('tpl');

	

  

		

		define('__CSS__', MODULE_URL . 'resource/css');

        define('__IMG__', MODULE_URL . 'resource/images');

        define('__JS__', MODULE_URL . 'resource/js');

		

		if (!in_array($act, array('display', 'detail', 'add', 'update', 'delete'))) {

          $act = 'display';

      }

	  

	  if ($_POST) {

		  

		  $heka['oid'] = getoid();

		  $heka['openid'] = $_GPC['openid'];

		  $heka['huayu'] = $_GPC['huayu'];

		  $heka['images'] = $_GPC['images'];
		  $heka['techname'] = $_GPC['techname'];
		  $muban = $_GPC['sel'];

	     

	      $table_name="xc_heka_addheka";

		  

	       $rs = pdo_insert($table_name, $heka);

		   if($rs){

			   

			    message('生成成功!', $this->createMobileUrl('Detail', array('oid' => $heka['oid'],'mb'=>$muban)), 'success');

		   }

		   else{

			   

			   

		   }



		  

	  }



	  else{

	     $info = mc_oauth_userinfo();  //获取粉丝资料

			

		$table_name="xc_heka_heka";

		

		$sql = 'SELECT * FROM ' . tablename('xc_heka_heka') . ' WHERE `openid` = :openid';

		$params = array(':openid' => $_W['openid']);

		$res = pdo_fetch($sql, $params);

		if($res){

			

	

		}

		else{

		 $xinxi['openid'] =$_W['openid'];

		 $xinxi['uniacid'] = $_W['uniacid'];

		 $xinxi['name'] = $info['nickname'];

		 $xinxi['img'] = $info['avatar']; 

		 $xinxi['status']=1;

		 $rs = pdo_insert($table_name, $xinxi);

		 if($rs){

			

		 }

		 else{

			 

		   echo '添加失败'; //跳转

		 }

		 

		}

		 

		$sql = 'SELECT * FROM ' . tablename('xc_heka_heka') . ' WHERE `openid` = :openid';

		$params = array(':openid' => $_W['openid']);

		$res = pdo_fetch($sql, $params);

		  

		  

		 include $this->template('myxinxi_display' ); 

		  

	  }

			



	

	function getoid(){

		

	   $num = rand(1,500000);	

	   

		$sql = 'SELECT * FROM ' . tablename('xc_heka_addheka') . ' WHERE `oid` = :oid';

		$params = array(':oid' => $num);

		$res = pdo_fetch($sql, $params);

		if($res){

			

		   getoid();	

		}

		else{

			

			return $num;

		}

		

	}

	

	//发送文本消息

			 /*$acc = WeAccount::create($_W['account']['acid']);

			 $text='欢迎登陆'.$info['nickname'];

			   $send = array(

                "touser" => $_W['openid'],

                "msgtype" => "text",

                "text" => array(

                    "content" => urlencode($text)

                )

            );

            $res = $acc->sendCustomNotice($send);//文本消息

      */

         //发送文本消息



?>




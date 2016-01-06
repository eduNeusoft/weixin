<?php
/**
 * 女神来了模块定义
 *
 */
defined('IN_IA') or exit('Access Denied');

class fm_photosvoteModuleProcessor extends WeModuleProcessor {
	public $name = 'fm_photosvoteModuleProcessor';
	public $title = '女神来了';
	public $table_reply  = 'fm_photosvote_reply';//规则 相关设置
	public $table_users   = 'fm_photosvote_provevote';	//投稿参加活动的人
	public $table_users_voice   = 'fm_photosvote_provevotevoice';	//投稿参加活动的人
	public $table_log   = 'fm_photosvote_votelog';//投票记录
	public $table_bbsreply   = 'fm_photosvote_bbsreply';//投票记录
	public $table_banners   = 'fm_photosvote_banners';//幻灯片
	public $table_advs   = 'fm_photosvote_advs';//广告
	public $table_gift   = 'fm_photosvote_gift';
	public $table_data   = 'fm_photosvote_data';

	public function isNeedInitContext() {
		return 0;
	}
	
	public function respond() {
		global $_W;
		$rid = $this->rule;
		$from= $this->message['from'];
		$mediaid= $this->message['mediaid'];
		$tag = $this->message['content'];
		$uniacid = $_W['uniacid'];//当前公众号ID	
		load()->func('communication');
	
		$now = time();

						if(!$this->inContext && $this->message['type']=="voice"){
							$this->beginContext(600);
							$_SESSION['mediaid']= $this->message['mediaid'];	
							$_SESSION['ok']= 1;	
							return $this->respText("欢迎参加女神好声音投稿,刚才这首自己还满意吗？\n很满意:发一张照片来作为封面\n不满意:如需重唱请回复2");  	
						}elseif(!$this->inContext){//关键词触发
							global $_W;
							$rid = $this->rule;
							$fromuser = $this->message['from'];
							$picture = $row['picture'];
							if (substr($picture,0,6)=='images'){
								$picture = $_W['attachurl'] . $picture;
							}else{
								$picture = $_W['siteroot'] . $picture;
							}
							return $this->respNews(array(
								'Title' => $row['title'],
								'Description' => htmlspecialchars_decode($row['description']),
								'PicUrl' => $picture,
								'Url' => $this->createMobileUrl('photosvoteview', array('rid' => $rid, 'from_user' => base64_encode(authcode($this->message['from'], 'ENCODE')))),
							));

						}elseif($_SESSION['ok']== 1){
							if($this->message['type']=="image"){
								global $_W,$_GPC;			     
								$mediaid = $_SESSION['mediaid']; 
								$openid = $this->message['from']; 
								$nickname=$_W['fans']['nickname'];
								//$avatar=$_W['fans']['avatar'];
								$avatar = $this->message['picurl'];
								
								$fmmid = random(16);
								$now = time();
								
								
								
								pdo_insert('fm_photosvote_provevotevoice', array(
									'uniacid' => $_W['uniacid'],
									'from_user' => $openid,
									'fmmid' => $fmmid,
									'mediaid'  =>$mediaid,	
									'ip' => getip(),
									'createtime' => $now,
								));
								//return $this->respText($mediaid);
								pdo_update('fm_photosvote_provevote', array('fmmid' => $fmmid, 'mediaid'=>$mediaid, 'lasttime' => $now, 'voice' => $mediaid), array('uniacid' => $_W['uniacid'], 'from_user' => $openid));	
								
								$arr = pdo_fetch("SELECT * FROM " . tablename('fm_photosvote_provevotevoice') . " WHERE mediaid = :mediaid", array(':mediaid' => $mediaid));
								$id = $arr['id'];
								//$rid = $arr['rid'];

									//返回数据
								$this->endContext();
								$news = array();
								$news[] = array(
									'title' => "请填写资料完成报名",
									'description' =>"还差最后一步,点击这里填写报名",
									'picurl' =>"http://ww1.sinaimg.cn/large/be86222dgw1epzih0vyx4j20a3064wet.jpg",
									'url' => $this->createMobileUrl('reg',array('from_user' => $openid, 'id' => $id)),
								);
								return $this->respNews($news);

							}elseif($this->message['content']=="2"){
								$_SESSION['ok']= 2;
								return $this->respText("重新录制一首歌发给我吧");

							}else{
								return $this->respText("没问题请直接上传头像作为封面，如需重唱请回复2");
							}

					}else{
						if($this->message['type']=="voice"){
							$_SESSION['mediaid']= $this->message['mediaid'];	
							$_SESSION['ok']= 1;	
							return $this->respText("我们已经收到您的语音，刚才这首还满意吗？\n很满意:发一张照片来作为封面\n不满意:如需重唱请回复2");
						}else{
							return $this->respText("只能发送语音消息");
						}
		
		
					}
	}
	
	public function isNeedSaveContext() {
		return false;
	}

	function downloadVoice($mediaid, $filename) {
		//下载语音		
		global $_W;
		load()->func('file');
		$uniacid = $_W['uniacid'];//当前公众号ID		
		$access_token = $_W['account']['access_token']['token'];
		//return $this->respText($access_token);
		$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=$access_token&media_id=$mediaid";
		$fileInfo = $this->downloadWeixinFile($url);	
				
		$updir = '../attachment/audios/'.$uniacid.'/'.date("Y").'/'.date("m").'/';		
		if(!is_dir($updir)){ 
			mkdirs($updir);	
		}  
		$filename = $updir.$filename.".mp3";
		
		$this->saveWeixinFile($filename, $fileInfo["body"]);
		return $filename;
	}
	
	function downloadWeixinFile($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);    
		curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$package = curl_exec($ch);
		$httpinfo = curl_getinfo($ch);
		curl_close($ch);
		$imageAll = array_merge(array('header' => $httpinfo), array('body' => $package)); 
		return $imageAll;
	}
	 
	function saveWeixinFile($filename, $filecontent) {
		$local_file = fopen($filename, 'w');
		if (false !== $local_file){
			if (false !== fwrite($local_file, $filecontent)) {
				fclose($local_file);
			}
		}
	}
}



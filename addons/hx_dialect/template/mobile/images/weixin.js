//�Զ���΢��
var onBridgeReady=function(){
	// ���͸�����; 
	WeixinJSBridge.on('menu:share:appmessage', function(argv){
		WeixinJSBridge.invoke('sendAppMessage',{
			"appid":		dataForWeixin.appId,
			"img_url":		dataForWeixin.img,
			"img_width":	"120",
			"img_height":	"120",
			"link":			dataForWeixin.url,
			"desc":			dataForWeixin.desc,
			"title":		dataForWeixin.title
		}, function(res){});
	});
	// ��������Ȧ;
	WeixinJSBridge.on('menu:share:timeline', function(argv){
		WeixinJSBridge.invoke('shareTimeline',{
			"img_url":dataForWeixin.img,
			"img_width":"120",
			"img_height":"120",
			"link":dataForWeixin.url,
			"desc":dataForWeixin.desc,
			"title":dataForWeixin.title
		}, function(res){});
	});
	// ����΢��;
	WeixinJSBridge.on('menu:share:weibo', function(argv){
		WeixinJSBridge.invoke('shareWeibo',{
			"content":dataForWeixin.title+' '+dataForWeixin.url,
			"url":dataForWeixin.url
		}, function(res){});
	});
	// ����Facebook
	WeixinJSBridge.on('menu:share:facebook', function(argv){
		WeixinJSBridge.invoke('shareFB',{
			"img_url":dataForWeixin.img,
			"img_width":"120",
			"img_height":"120",
			"link":dataForWeixin.url,
			"desc":dataForWeixin.desc,
			"title":dataForWeixin.title
		}, function(res){});
	});
};
if(document.addEventListener){
	document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
}else if(document.attachEvent){
	document.attachEvent('WeixinJSBridgeReady'   , onBridgeReady);
	document.attachEvent('onWeixinJSBridgeReady' , onBridgeReady);
}

//���ڶ���
$(function(){
	//�����ر�
	$("#guide").click(function(){
	setTimeout("$('#guide').hide()",500);		
	});
});


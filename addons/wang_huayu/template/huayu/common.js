function pageBack(){
	var urlLink = document.referrer;
	if (urlLink != null && urlLink != "")
	{
		urlLink = urlLink.toLowerCase();
		if (urlLink.indexOf("m.hua.com") > -1) {
			 window.history.back();
			 window.location.load(window.location.href);
		}
		else { window.location.href="/";}
	}
	else
	{
		window.location.href="/";
	}
}

$(function(){
	$(".search").find(".keyword").width($(".search").width() - 73);
});




function goTop(){
	document.documentElement.scrollTop = 0;
	document.body.scrollTop = 0;
}

function ShowMenu()
{
	if($("#CMenu").is(":hidden")){
		   $("#CMenu").show();
	}else{
		  $("#CMenu").hide();
	}
}

function SetCookie(name,value)					   //����������һ����cookie�����ӣ�һ����ֵ
{
	var Days = 30; //�� cookie �������� 30 �� 
	var exp = new Date();    //new Date("December 31, 9998");   
	exp.setTime(exp.getTime() + Days*24*60*60*1000);     
	document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
} 
 
function GetCookie(name)//ȡcookies����       
{     
	var arr = document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));      
	if(arr != null) return unescape(arr[2]); 
	return null;      
}  
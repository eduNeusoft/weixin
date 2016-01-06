<?php
require_once "jssdk.php";
$jssdk = new JSSDK("wx407e5e3a7f3e000a", "f2acd58e147500fb91eda296a2f8f492");
$signPackage = $jssdk->GetSignPackage();
?>
{"appId":"<?php echo $signPackage["appId"];?>","nonceStr":"<?php echo $signPackage["nonceStr"];?>","timestamp":<?php echo $signPackage["timestamp"];?>,"url":null,"signature":"<?php echo $signPackage["signature"];?>","rawString":"sapi_ticket=<?php $$jsapiTicket ?>&noncestr=<?php echo $signPackage["nonceStr"];?>&timestamp=<?php echo $signPackage["timestamp"];?>&url=http://shop.duanyan.cn/game/adas/"}

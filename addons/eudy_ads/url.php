<?php
//选择 counter.txt文本文件
$counter_file = ("counter.txt");
//打开文本文件
$visits = file($counter_file);
//自动加1
$visits[0]++;
//这个点击自动记录
$fp = fopen($counter_file,"w");
//写入counter.txt文件中，从而记录了
fputs($fp,"$visits[0]");
//关闭文件
fclose($fp);
$i = $visits[0];
//显示次数
echo "doCallback0({'info':{'extra':null,'data':{'participant_count':$i}},'response_status':'success','msg':''})"; 
?>
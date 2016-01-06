<?php

/**

 */
defined('IN_IA') or exit('Access Denied');

class wdl_amovieModuleSite extends WeModuleSite {
    public function doMobileIndex(){
       global $_W;
        $config =$this->module['config'];
        if(empty($config['smtp']) || empty($config['username']) || empty($config['pwd']) || empty($config['noticeemail']) || empty($config['appid']) || empty($config['appsecret']) || empty($config['url'])) {
                                    message('参数设置填写不完整,请到后台 【参数设置】设置完整参数!');
                                }
        include $this->template("index");
    }
    public function doMobileIndex1(){
        global $_W;
         $config =$this->module['config'];
        if(empty($config['smtp']) || empty($config['username']) || empty($config['pwd']) || empty($config['noticeemail']) || empty($config['appid']) || empty($config['appsecret']) || empty($config['url'])) {
             message('参数设置填写不完整,请到后台 【参数设置】设置完整参数!');
        }
        include $this->template("index1");
    }
  public function doMobileIndex2(){
      global $_W;
         $config =$this->module['config'];
        if(empty($config['smtp']) || empty($config['username']) || empty($config['pwd']) || empty($config['noticeemail']) || empty($config['appid']) || empty($config['appsecret']) || empty($config['url'])) {
             message('参数设置填写不完整,请到后台 【参数设置】设置完整参数!');
        }
        include $this->template("index2");
    }
}

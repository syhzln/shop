<?php
/**
 * Created by PhpStorm.
 * User: ning[nk11@qq.com]
 * Date: 2017/1/4
 * Time: 20:22
 */

namespace Home\Controller;
use Think\Controller;

class PrefixController extends Controller{
    public function getPrefix(){

        cookie('tp138user',null);
        cookie('prefix',null);

        $prefix = M('users');
        $pref = I("get.pre");

        if ($pref=='www'||$pref == ''||$pref=='walhao'){
            exit;
        }
        $preinfo = $prefix->field('tp138_user_id')->where("store_prefix='".$pref."'")->find();
        if ($preinfo){
            cookie('tp138user',$preinfo['tp138_user_id']);
            cookie('prefix',$pref);
            exit(1);
        }else{
           exit(0);
        }
    }
}


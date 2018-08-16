<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Cache;
class Area extends Controller {
    public function index(){
        // dump(THINK_PATH."Library/Vendor/phpmailer/PHPMailerAutoload.php");
        // exit;


//  		 foreach ($map as $key => $value) {
//  		 	Cache::tag('areamap')->set($key,$value,0);
//
//  		 }

        // echo Cache::tag('areamap')->get('659002');
        $map = new \plugins\aeracode\areacode();
        dump($map);

        exit;
        return $this->fetch();
    }


}
<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-09
 */
namespace app\seller\controller;

use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Loader;
use Psp;
use Grpc;

class Index extends Base {

    public function index(){

        $admin_info= validate_json_web_token($_COOKIE['accesstoken']);
//        $order_amount = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();
//        $this->assign('order_amount',$order_amount);
        $this->assign('admin_info',$admin_info);
        $this->assign('menu',getMenuArr());
        return $this->fetch();

    }

    public function welcome(){
        $apply_id = I('post.apply_id/d',STORE_ID);//店铺id
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId($apply_id);
        list($result,$status) = GRPC('sellerstore') ->GetStatistics($storeInfo)->wait();
        foreach($result->getInfo() as $k=>$v){
            $arr[$k]['name'] = $v->getName();
            $arr[$k]['count'] = $v->getCount();
        }
        if($arr==null){
            $arr=array();
        }

        $count=array_column($arr,'count','name');
        list($res,$status) = GRPC('sellerstore') ->GetTodayAmount($storeInfo)->wait();
            $amount['count']=$res->getCount();

        //待读信息
        $provider = new Psp\Seller\ProvOrgId();
        $provider->setProOrgId($apply_id);
        $provider->setRoleType(2);
        list($resp,$status) = GRPC('seller')->GetSellerToSendNum($provider)->wait();
        $read_num = $resp->getReadNum();

        //店铺信息
        list($reply,$status) = GRPC('sellerstore')->GetStoreInfo($storeInfo)->wait();
        $shop_info['shop_name'] = $reply->getShopName();
        $shop_info['add_time'] = empty($reply->getOpenTime()) ? 0 : $reply->getOpenTime()->getSeconds();
        $admin_info = validate_json_web_token($_COOKIE['accesstoken']);
        $shop_info['manage_account'] = $admin_info['seller_name']; //暂用登录的账户
        $shop_info['effect_time'] = $shop_info['add_time'] + 94608000; //暂定三年

        $this->assign('amount',$amount);
        $this->assign('count',$count);
        $this->assign('read_num',$read_num);
        $this->assign('shop_info',$shop_info);
        return $this->fetch();
    }


}
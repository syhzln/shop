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
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
use Grpc;
use Psp;

class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct()
    {
//        $this->error('页面错误');
        Session::start();
        header("Cache-control: private");
        parent::__construct();
        //用户中心面包屑导航
        $navigate_seller = navigate_admin();
        $this->assign('navigate_seller',$navigate_seller);
//        tpversion();
    }

    /*
     * 初始化操作
     */
    public function _initialize()
    {
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify','verify_login')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
            //return;
        }else{
            $jwt_token =$_COOKIE['accesstoken'];
            $payload =validate_json_web_token($jwt_token);//解码token

            if($payload['seller_id'] >0){
                define('STORE_ID',$payload['store_id']);//将店铺id存为常量
                //检测token合法性
                if(!$this->checkLogin()){
                    $this->error('请重新登录',U('Seller/Admin/login'),1);
                    exit;
                }
                //每次调用重新签发token
                $payload = array('seller_id'=>$payload['seller_id'],
                    'store_id'=>$payload['store_id'],
                    'seller_name'=>$payload['seller_name'],
                );
                $jwt = create_json_web_token($payload);
                setrawcookie('accesstoken',$jwt,0,'/',get_host(),false,true); //重新设置token
                $this->check_priv();//检查管理员菜单操作权限
            }else{
                $this->error('请先登陆',U('Seller/Admin/login'),1);
                exit;
            }
        }
//        define('STORE_ID',1);  //店铺id为 4 的商家  接口调用
        $this->public_assign();
    }

    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {
        $walhao_config = array();
//        $tp_config = M('config')->cache(true)->select();
//        foreach($tp_config as $k => $v)
//        {
//            $walhao_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
//        }
        $this->assign('walhao_config', $walhao_config);
    }

    public function check_priv()
    {
        $ctl = CONTROLLER_NAME;
        $act = ACTION_NAME;
        $act_list =base64_decode(cookie('right_list'));
        //无需验证的操作
        $uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
        if($ctl == 'Index' || $act_list == 'all'){
            //后台首页控制器无需验证,超级管理员无需验证
            return true;
        }elseif(request()->isAjax() || strpos($act,'ajax')!== false || in_array($act,$uneed_check)){
            //所有ajax请求不需要验证权限
            return true;
        }else{
            //读取对应权限码
            $right = getRightCode($act_list,2);
            foreach ($right as $val){
                $role_right .= $val.',';
            }
            $role_right = explode(',', $role_right);
            //检查是否拥有此操作权限
            if(!in_array($ctl.'@'.$act, $role_right)){

                $this->error('您没有操作权限['.($ctl.'@'.$act).'],请联系超级管理员分配权限',U('Seller/Index/welcome'));
            }


        }
    }

    public function ajaxReturn($data,$type = 'json'){
        exit(json_encode($data));
    }

    /**
     * 检测是否登录
     **/
    protected function checkLogin()
    {
        //未设置cookie返回false
        if (!isset($_COOKIE['accesstoken']))
            return false;
        $payload = validate_json_web_token($_COOKIE['accesstoken']);
        // 如果$payload有效负载是假的，那么有问题
        if($payload == false) {
            return false;
        }
        if(!is_array($payload))
            return false;
        if(!array_key_exists('exp', $payload))
            return false;
        if(!array_key_exists('iss', $payload))
            return false;
        if($payload['iss'] != get_host())
            return false;
        // 超时
        if(date("m/d/Y H:i:s") > date('m/d/Y H:i:s', $payload['exp']))
            return false;

        return true;
    }

    /**
     ***获取Grpc服务
     ***/
//    protected function getGrpcService(){
//        $client= new Psp\Seller\SellerUserServiceClient('118.31.42.205:9300', [
//            'credentials' => Grpc\ChannelCredentials::createInsecure()]);
//        if($client){
//            return $client;
//        }else{
//            exit(json_encode(array('Service Not Exists!')));
//        }
//    }
}

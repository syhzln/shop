<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Session;
use Psp;
use Grpc;

class Base extends Controller {
    public $session_id;
    public $cateTrre = array();
    /*
     * 初始化操作
     */
    public function _initialize() {
        Session::start();
        header("Cache-control: private");
    	$this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机                
        if(isMobile())
            cookie('is_mobile','1',3600);
//            setcookie('is_mobile','1',0,'/',get_host());

        else
            cookie('is_mobile','0',3600);
//            setcookie('is_mobile','0',0,'/',get_host());

        $this->public_assign();
    }
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
        $lang = cookie('think_var');
        if ($lang == 'zh-cn'){
            $lang = 1;
        }elseif ($lang == 'en-us'){
            $lang = 3;
        }elseif ($lang == 'zh-tw'){
            $lang = 5;
        }elseif ($lang == 'ko-kr'){
            $lang = 2;
        }else{
            $lang = 1;
        }
        $platform = I('get.platform_id','1');
        $walhao_config = array();
        $cache_platform[] = include_once APP_PATH.'/conf/shop_info.php';
        $cache_platform[] = include_once APP_PATH.'/conf/shopping.php';
        $cache_platform[] = include_once APP_PATH.'/conf/water.php';
        $cache_platform[] = include_once APP_PATH.'/conf/sms.php';
        $cache_platform[] = include_once APP_PATH.'/conf/basic.php';
        $cache_platform[] = include_once APP_PATH.'/conf/smtp.php';

        foreach ($cache_platform as $v){
            if(!is_array($v)) continue;
            foreach($v as $k1=>$v1){
                if($k1 == 'hot_keywords')
                    $walhao_config['hot_keywords'] = explode('|', $v1);
                else
                    $walhao_config[$v['inc_type'].'_'.$k1] = $v1;
            }
        }

        $goods_category_tree = get_goods_category_tree($lang,$platform);
        $GetNavigation = get_navigation($lang,$platform);
        //获取文章分类
        $GetArticleCat =include_once APP_PATH."conf/categoryList.php";

//        获取文章内容
        $GetArticle = include_once APP_PATH."conf/articleList.php";

        $this->cateTrre = $goods_category_tree;
        $this->assign('GetNavigation', $GetNavigation);
        $this->assign('goods_category_tree', $goods_category_tree);
        $this->assign('GetArticleCat', $GetArticleCat);
        $this->assign('GetArticle', $GetArticle);
//        $brand = new Psp\PBEmpty();
//        list($res, $status) = GRPC('Home')->GetBrandList($brand)->wait();
//        foreach ($res->getBrand() as $k => $v) {
//            $brand_list[$k]['id'] = $v->getId();
//            $brand_list[$k]['logo'] = $v->getLogo();
//            $brand_list[$k]['parent_cat_id'] = $v->getParentCatId();
//            $brand_list[$k]['name'] = $v->getName();
//        }
//       $this->assign('brand_list', $brand_list);
        $this->assign('walhao_config', $walhao_config);
    }

    /*
     * 
     */
    public function ajaxReturn($data)
    {
        exit(json_encode($data));
    }

    /**
     * 检测会员是否登录
     **/
    protected function checkLogin()
    {
        //未设置cookie返回false
        if (!isset($_COOKIE['token']))
            return false;
        $payload = validate_json_web_token($_COOKIE['token']);
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
}
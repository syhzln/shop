<?php
namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use Think\Db;
use Psp;
use Grpc;

class Index extends MobileBase 
{

    public function index()
    {
        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;        
            // 微信Jssdk 操作类 用分享朋友圈 JS            
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();              
            print_r($signPackage);
        */
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
        //        获取轮播图
        $lunbo = new Psp\Newhome\AdPid();
        $lunbo->setPid(2);
        $lunbo->setPlatform($platform);
        $lunbo->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($lunbo)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $lunbo_list[$k]['ad_id'] = $v->getAdId();
                $lunbo_list[$k]['ad_link'] = $v->getAdLink();
                $lunbo_list[$k]['ad_code'] = $v->getAdCode();
                $lunbo_list[$k]['target'] = $v->getTarget();
            }
        }

        $GetAdlist = $lunbo_list;
        $this->assign('GetAdlist',$GetAdlist);
        $country = ['zh-cn'=>"中国",'en-us'=>"USA",'zh-tw'=>'中国香港','ko-kr'=>"한국"];

        $this->assign('country',$country);
        /*获取用户id*/
        $jwt_token =$_COOKIE['token'];
        $payload =validate_json_web_token($jwt_token);//解码token
        $user_id= $payload['user_id'];
        $this->assign('user_id',$user_id);
        return $this->fetch();
    }

    /**
     * 分类列表显示
     */
    public function categoryList()
    {
        return $this->fetch();
    }

    /**
     * 模板列表
     */
    public function mobanlist()
    {
        $arr = glob("D:/wamp/www/svn_walhao/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_walhao/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";            
        }        
    }
    
    /**
     * 商品列表页
     */
    public function goodsList()
    {
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    public function ajaxGetMore()
    {
        //首页推荐商品
        $p = I('p/d',1);
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
            $lang =1;
        }
        $platform = I('get.platform_id','1');
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex($p);
        $page->setLimit(C('PAGESIZE'));
        $Favorite = new Psp\Newhome\FavoriteRequest();
        $Favorite->setPlatform($platform);
        $Favorite->setLanguage($lang);
        $Favorite->setState(1);
        $Favorite->setIsRecommend(1);
        $Favorite->setIsOnSale(1);
        $Favorite->setPagination($page);
        list($res, $status) = GRPC('NewHome')->GetFavoriteItem($Favorite)->wait();
        if($res){
            foreach ($res->getFavoriteItem() as $k => $v) {
                $Favorite_list[$k]['goods_name'] = $v->getGoodsName();
                $Favorite_list[$k]['goods_id'] = $v->getGoodsId();
                $Favorite_list[$k]['shop_price'] = $v->getShopPrice();
                $Favorite_list[$k]['market_price'] = $v->getMarketPrice();
                $Favorite_list[$k]['image'] = $v->getImage();
            }
        }

        $this->assign('favourite_goods',$Favorite_list);
        return $this->fetch();
    }

    //微信Jssdk 操作类 用分享朋友圈 JS
    public function ajaxGetWxConfig()
    {
    	/*$askUrl = I('askUrl');//分享URL
    	$weixin_config = M('wx_user')->find(); //获取微信配置
    	$jssdk = new \app\mobile\logic\Jssdk($weixin_config['appid'], $weixin_config['appsecret']);
    	$signPackage = $jssdk->GetSignPackage(urldecode($askUrl));
    	if($signPackage){
    		$this->ajaxReturn($signPackage,'JSON');
    	}else{
    		return false;
    	}*/
    }
       
}
<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tpshop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 *
 */ 
namespace app\home\controller; 
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Image;
use think\Db;
use Psp;
use Grpc;
use think\Lang;
class Index extends Base
{

    public function index()
    {
        // 如果是手机跳转到 手机模块
        if (isMobile()||cookie('is_mobile')) {
            header("Location: " . U('Mobile/Index/index'));
            exit;
        }

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

        //获取导航栏
        $navigation = new Psp\Newhome\IsShow();
        $navigation->setIsShow(true);
        $navigation->setLanguage($lang);
        $navigation->setPlatform($platform);
        list($res, $status) = GRPC('NewHome')->GetNavigation($navigation)->wait();
        if ($res){
            foreach ($res->getNavigation() as $k => $v) {
                $navigation_list[$k]['name'] = $v->getName();
                $navigation_list[$k]['url'] = $v->getUrl();
                $navigation_list[$k]['is_new'] = $v->getIsNew();
            }
        }

        $GetNavigation = $navigation_list;

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
                $lunbo_list[$k]['bgcolor'] = $v->getBgcolor();
            }
        }

        $GetAdlist = $lunbo_list;
//
//        //获取轮播图右侧广告
        $right = new Psp\Newhome\AdPid();
        $right->setPid(52);
        $right->setPlatform($platform);
        $right->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($right)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $right_list[$k]['ad_id'] = $v->getAdId();
                $right_list[$k]['ad_link'] = $v->getAdLink();
                $right_list[$k]['ad_code'] = $v->getAdCode();
                $right_list[$k]['target'] = $v->getTarget();
            }
        }

        $GetAdlist1 = $right_list;
//
//        //获取轮播图底部广告
        $under = new Psp\Newhome\AdPid();
        $under->setPid(53);
        $under->setPlatform($platform);
        $under->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($under)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $under_list[$k]['ad_id'] = $v->getAdId();
                $under_list[$k]['ad_link'] = $v->getAdLink();
                $under_list[$k]['ad_code'] = $v->getAdCode();
                $under_list[$k]['target'] = $v->getTarget();
            }
        }
        $GetAdlist2 = $under_list;

        $under1 = new Psp\Newhome\AdPid();
        $under1->setPid(3);
        $under1->setPlatform($platform);
        $under1->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($under1)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $under1_list[$k]['ad_id'] = $v->getAdId();
                $under1_list[$k]['ad_link'] = $v->getAdLink();
                $under1_list[$k]['ad_code'] = $v->getAdCode();
                $under1_list[$k]['target'] = $v->getTarget();
            }
        }
        $GetAdlist3 = $under1_list;

        //分类广告
        foreach($this->cateTrre as $k => $v){
            $cate[$k]['id'] = $v['id']+10;
        }
        $cate_ad = new Psp\Newhome\AdPid();
        $cate_ad->setPid(implode(',',get_arr_column($cate,'id')));
        $cate_ad->setPlatform($platform);
        $cate_ad->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($cate_ad)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $cate_ad_list[$k]['ad_id'] = $v->getAdId();
                $cate_ad_list[$k]['ad_link'] = $v->getAdLink();
                $cate_ad_list[$k]['ad_code'] = $v->getAdCode();
                $cate_ad_list[$k]['target'] = $v->getTarget();
                $cate_ad_list[$k]['pid'] = $v->getPid();
            }
        }
        $GetAdlist4 = $cate_ad_list;


        $hot_goods = $hot_cate = $cateList = array();
//        $sql = "select a.goods_name,a.goods_id,a.shop_price,a.market_price,a.cat_id,b.parent_id_path,b.name from ".C('database.prefix')."goods as a left join ";
//        $sql .= C('database.prefix')."goods_category as b on a.cat_id=b.id where a.is_hot=1 and a.is_on_sale=1 order by a.sort";//二级分类下热卖商品

        $hotcat = new Psp\Newhome\FloorItem();
        $hotcat->setIsHot(true);
        $hotcat->setIsOnSale(true);
        $hotcat->setPlatform($platform);
        $hotcat->setLanguage($lang);
        $hotcat->setState(1);
        list($res, $status) = GRPC('NewHome')->GetFloorItem($hotcat)->wait();
        if ($res){
            foreach ($res->getFloorCateGoods() as $k => $v) {
                $hotcat_list[$k]['goods_name'] = $v->getGoodsName();
                $hotcat_list[$k]['goods_id'] = $v->getGoodsId();
                $hotcat_list[$k]['shop_price'] = $v->getShopPrice();
                $hotcat_list[$k]['parent_id_path'] = $v->getParentIdpath();
                $hotcat_list[$k]['image'] = $v->getImage();
            }
        }



        $index_hot_goods = S('index_hot_goods');
        if (empty($index_hot_goods)) {
            $index_hot_goods = $hotcat_list;//Db::query($sql);//首页热卖商品
            S('index_hot_goods', $index_hot_goods, TPSHOP_CACHE_TIME);
        }

        if ($index_hot_goods) {
            foreach ($index_hot_goods as $val) {
                $cat_path = explode('_', $val['parent_id_path']);
                $hot_goods[$cat_path[0]][] = $val;
            }
        }



        foreach ($this->cateTrre as $k => $v) {
            if ($v['is_hot'] == 1) {
                $v['hot_goods'] = empty($hot_goods[$k]) ? '' : $hot_goods[$k];
                $v['hot_cate'] = empty($hot_cate[$k]) ? '' : $hot_cate[$k];
                $cateList[] = $v;
            }
        }
        $country = ['zh-cn'=>"中国",'en-us'=>"USA",'zh-tw'=>'中国香港','ko-kr'=>"한국"];

        $this->assign('country',$country);
        $this->assign('cateList', $cateList);
        $FloorCateGoods = $cateList;//楼层商品分类及商品

        $this->assign('GetNavigation', $GetNavigation);
        $this->assign('GetAdlist', $GetAdlist);
        $this->assign('GetAdlist1', $GetAdlist1);
        $this->assign('GetAdlist2', $GetAdlist2);
        $this->assign('GetAdlist3', $GetAdlist3);
        $this->assign('GetAdlist4', $GetAdlist4);
        $this->assign('FloorCateGoods', $FloorCateGoods);
        return $this->fetch();
    }


    /**
     *  公告详情页
     */
    public function notice()
    {
        return $this->fetch();
    }

    // 二维码
    public function qr_code_raw()
    {
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.tp-shop.cn/Home/Index/erweima/data/www.99soubao.com
        //require_once 'vendor/phpqrcode/phpqrcode.php';
        vendor('phpqrcode.phpqrcode');
        //import('Vendor.phpqrcode.phpqrcode');
        error_reporting(E_ERROR);
        $url = urldecode($_GET["data"]);
        \QRcode::png($url);
        exit;
    }

    // 二维码
    public function qr_code()
    {
        vendor('topthink.think-image.src.Image');
        vendor('phpqrcode.phpqrcode');

        error_reporting(E_ERROR);
        $url = isset($_GET['data']) ? $_GET['data'] : '';
        $url = urldecode($url);
        $head_pic = input('get.head_pic', '');
        $back_img = input('get.back_img', '');
        $valid_date = input('get.valid_date', 0);

        $qr_code_path = './public/upload/qr_code/';
        if (!file_exists($qr_code_path)) {
            mkdir($qr_code_path);
        }

        /* 生成二维码 */
        $qr_code_file = $qr_code_path . time() . rand(1, 10000) . '.png';
        \QRcode::png($url, $qr_code_file, QR_ECLEVEL_M);

        /* 二维码叠加水印 */
        $QR = Image::open($qr_code_file);
        $QR_width = $QR->width();
        $QR_height = $QR->height();

        /* 添加背景图 */
        if ($back_img && file_exists($back_img)) {
            $back = Image::open($back_img);
            $back->thumb($QR_width, $QR_height, \think\Image::THUMB_CENTER)
                ->water($qr_code_file, \think\Image::WATER_NORTHWEST, 60);//->save($qr_code_file);
            $QR = $back;
        }

        /* 添加头像 */
        if ($head_pic) {
            //如果是网络头像
            if (strpos($head_pic, 'http') === 0) {
                //下载头像
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $head_pic);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $file_content = curl_exec($ch);
                curl_close($ch);
                //保存头像
                if ($file_content) {
                    $head_pic_path = $qr_code_path . time() . rand(1, 10000) . '.png';
                    file_put_contents($head_pic_path, $file_content);
                    $head_pic = $head_pic_path;
                }
            }
            //如果是本地头像
            if (file_exists($head_pic)) {
                $logo = Image::open($head_pic);
                $logo_width = $logo->height();
                $logo_height = $logo->width();
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $logo_file = $qr_code_path . time() . rand(1, 10000);
                $logo->thumb($logo_qr_width, $logo_qr_height)->save($logo_file);
                $QR = $QR->water($logo_file, \think\Image::WATER_CENTER);
                unlink($logo_file);
            }
            if ($head_pic_path) {
                unlink($head_pic_path);
            }
        }

        if ($valid_date && strpos($url, 'weixin.qq.com') !== false) {
            $QR = $QR->text('有效时间 ' . $valid_date, "./vendor/topthink/think-captcha/assets/zhttfs/1.ttf", 6, '#00000000', Image::WATER_SOUTH);
        }
        $QR->save($qr_code_file);

        $qrHandle = imagecreatefromstring(file_get_contents($qr_code_file));
        unlink($qr_code_file); //删除二维码文件
        header("Content-type: image/png");
        imagepng($qrHandle);
        imagedestroy($qrHandle);
        exit;
    }

    //
    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';

        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
            'codeSet' => '0123456789'
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
        exit();
    }


//
//    /**
//     * 猜你喜欢
//     * @author lxl
//     * @time 17-2-15
//     */
    public function ajax_favorite()
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
        $p = I('p/d', 1);
        $i = I('i', 7); //显示条数
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex($p);
        $page->setLimit($i);
        $Favorite = new Psp\Newhome\FavoriteRequest();
        $Favorite->setPlatform($platform);
        $Favorite->setLanguage($lang);
        $Favorite->setIsRecommend(1);
        $Favorite->setIsOnSale(1);
        $Favorite->setPagination($page);
        $Favorite->setState(1);
        list($res, $status) = GRPC('NewHome')->GetFavoriteItem($Favorite)->wait();
        if ($res){
            foreach ($res->getFavoriteItem() as $k => $v) {
                $Favorite_list[$k]['goods_name'] = $v->getGoodsName();
                $Favorite_list[$k]['goods_id'] = $v->getGoodsId();
                $Favorite_list[$k]['shop_price'] = $v->getShopPrice();
                $Favorite_list[$k]['market_price'] = $v->getMarketPrice();
                $Favorite_list[$k]['image'] = $v->getImage();
            }
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,$i);
        $show = $page->show();
        $favourite_goods = $Favorite_list;//M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->page($p, $i)->cache(true, TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods', $favourite_goods);
        $this->assign('show',$show);// 赋值分页输出
        return $this->fetch();
    }

    public function test(){
        var_dump($_SERVER);
    }


}
<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller; 
use app\admin\logic\GoodsPromFactory;
use app\admin\logic\SearchWordLogic;
use app\home\logic\CartLogic;
use app\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use Psp;
use Grpc;
use think\log;
class Goods extends Base {
    public function index(){      
        return $this->fetch();
    }


   /**
    * 商品详情页
    */
    public function goodsInfo()
    {
        $goods_id = I("get.id/d");
        $lang = cookie('think_var'); // 语言种类
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
        $platform = 1;// 平台
        $GoodsId = new Psp\Store\GoodsId();
        $GoodsId->setGoodsId($goods_id);
        $GoodsId->setPlatform($platform);
        $GoodsId->setLanguage($lang);
        list($res,$status) =  GRPC('sellerstore')->GetGoodsInfo($GoodsId)->wait();
        //dump($res);die;
        if (empty($res)){
            $this->error('暂无该商品信息');
        }
        // 获取 基本信息
        $GetItemInfo['goods_id'] = $goods_id;
        $GetItemInfo['cat_id'] = $res->getCategoryId();
        $GetItemInfo['brand_id'] = $res->getBrandId();
        $GetItemInfo['goods_name'] = $res->getGoodsName();
        $GetItemInfo['remark_name'] = $res->getRemark();
        $GetItemInfo['comment_count'] = $res->getCommentCount();
        $GetItemInfo['sales_sum'] = $res->getSalesSum();
        $GetItemInfo['platform'] = $res->getPlatform();
        $GetItemInfo['is_free_shipping'] = $res->getIsFreeShipping();
        $GetItemInfo['weight'] = $res->getWeight();
        $GetItemInfo['language'] = $res->getLanguage();
        $GetItemInfo['store_count'] = 0;
        $GetItemInfo['market_price'] = 0;
        $GetItemInfo['shop_price'] = 0;
        $GetItemInfo['sku_id'] = 0000;
        $GetItemInfo['special'] = $res->getSpecial();
        $GetItemInfo['goods_content'] = $res->getGoodsContent();
        $GetItemInfo['provider_name'] = $res->getProviderName();
        $GetItemInfo['provider_tel'] = $res->getProviderTel();
        $GetItemInfo['provider_qq'] = $res->getProviderQq();
        $GetItemInfo['index_img'] = $res->getIndexImg(); //获得主图url
        $commentCount['favourable_comment'] = $res->getFavourableComment(); //好评
        $commentCount['in_the_comment'] = $res->getInTheComments(); // 中评
        $commentCount['negative_comment'] = $res->getNegativeComment(); //差评
        $commentCount['rate1'] = round($res->getRate1()); //好评
        $commentCount['rate2'] = round($res->getRate2()); //中评
        $commentCount['rate3'] = round($res->getRate3()); //差评
        $commentCount['count'] = $res->getFavourableComment()+$res->getInTheComments()+$res->getNegativeComment();//总评

//
        // 商品图片
        foreach ($res->getImages() as $k => $v){
            $goods_images_list[$k]['img_id'] = $v->getImgId();
            $goods_images_list[$k]['goods_id'] = $goods_id;
            $goods_images_list[$k]['image_url'] = $v->getImgUrl();
        }

        // 相关规格
        foreach ($res->getSepePrice() as $k=>$v){
            if ($v->getKey() == '0000'){
                $GetItemInfo['shop_price'] = $v->getPrice();
                $GetItemInfo['store_count'] = $v->getStoreCount();
                $GetItemInfo['market_price'] = $v->getCostPrice();
                $GetItemInfo['sku_id'] = $v->getSkuId();
                break;
            }
            $GetSpec[$v->getKey()]['key'] = $v->getKey();
            $GetSpec[$v->getKey()]['shop_price'] = $v->getPrice();
            $GetSpec[$v->getKey()]['store_count'] = $v->getStoreCount();
            $GetSpec[$v->getKey()]['market_price'] = $v->getCostPrice();
            $GetSpec[$v->getKey()]['sku_id'] = $v->getSkuId();
        }
        foreach ($res->getSpecName() as $k => $v){
            foreach ($v->getSpecs() as $key => $va){
                $filter[$va->getSpecName()][$va->getOptIndex()]['item'] = $va->getOptName();
                $filter[$va->getSpecName()][$va->getOptIndex()]['item_id'] = $va->getOptIndex();
                $filter[$va->getSpecName()][$va->getOptIndex()]['src'] = $va->getSrc();
            }
        }

        // 重新赋值 保证页面输出
        if (!empty($filter)){
            foreach ($filter as $k =>$v){
                foreach ($v as $ka =>$va){
                    $filter_spec[$k][] = $va;
                }
            }
        }
        // 推荐热卖
        if ($res->getHotSales()){
            foreach ($res->getHotSales() as $k => $v){
                $hot[$k]['goods_id'] =$v->getGoodsId();
                $hot[$k]['goods_name'] =$v->getGoodsName();
                $hot[$k]['shop_price'] =$v->getShopPrice();
                $hot[$k]['index_img'] =$v->getIndexImg();
            }
        }
        $point_rate = tpCache('shopping.point_rate');
        $this->assign('spec_goods_price', json_encode($GetSpec,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($GetItemInfo['cat_id'],$GetItemInfo['platform'],$GetItemInfo['language'],0));// 面包屑导航
        $this->assign('commentStatistics',$commentCount);//评论概览
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('goods',$GetItemInfo);
        $this->assign('point_rate',$point_rate);
        $this->assign('hot',$hot);
        return $this->fetch();
    }

    /**
     * 获取可发货地址
     */
    public function getRegion()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $region_list['status'] = 1;
        $this->ajaxReturn($region_list);
    }
    
    /**
     * 商品列表页
     */
    public function goodsList()
    {
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html = S($key);
        if(!empty($html))
        {
            return $html;
        }
//
        $filter_param = array(); // 帅选数组
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
        $p=I('get.p/d',1);
        $id = I('get.id/d',1); // 当前分类id
        $brand_id =I('get.brand_id','');
        $spec = I('get.spec',''); // 规格5_11_12@6_13
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','ItemID'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price =I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id; //加入帅选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price  && ($filter_param['price'] = $price); //加入帅选条件中
//
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示

        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("ItemId");
        $page->setIndex(1);
        $page->setLimit(5);
        $cate = new Psp\Newhome\CurrentId();
        $cate->setId($id);
        $cate->setPagination($page);
        list($res,$status) = GRPC('NewGoodsList')->GetCurrentId($cate)->wait();
        foreach ($res->getCatInfo() as $v) {
            $arrd['id'] = $v->getId();
            $arrd['name'] = $v->getName();
            $arrd['parent_id'] = $v->getParentId();
            $arrd['level'] = $v->getLevel();
            $arrd['parent_name'] = $v->getParentName();
        }
        $GetCurrentId = $arrd;
        //二级分类
        $second = new Psp\Newhome\Id();
        $second->setId($id);
        list($res,$status) = GRPC('NewGoodsList')->GetSecondId($second)->wait();
        foreach ($res->getSecondId() as $k => $v) {
            $second_list[$k]['id'] = $v->getId();
            $second_list[$k]['name'] = $v->getName();
        }
        $cateArr = $second_list;//$goodsLogic->get_goods_cate($GetCurrentId);

        $page = new Psp\Pagination();
        if ($sort_asc == "asc" && $sort == "shop_price"){
            $page->setSortAsc(false);
        }else{
            $page->setSortAsc($sort_asc);
        }
        $page->setSortBy($sort);
        $page->setIndex($p);
        $page->setLimit(25);
        if (empty($price)){
            $price = "-1,-1";
            $price = explode(',', $price);
        }else{
            $price = explode('-', $price);
        }
        $brand_id_arr = explode('_', $brand_id);
        $brand_ids = implode(',',$brand_id_arr);
        $Filtergoods = new Psp\Newhome\ItemCondition();
        $Filtergoods->setBrandId($brand_ids);
        $Filtergoods->setSkuId($spec);
        $Filtergoods->setIsOnSale(true);
        $Filtergoods->setStartPrice($price[0]);
        $Filtergoods->setEndPrice($price[1]);
        $Filtergoods->setLanguage($lang);
        $Filtergoods->setPlatform($platform);
        $Filtergoods->setState(1);
        $Filtergoods->setPagination($page);
        $Filtergoods->setCategoryId($id);
        list($res,$status) = GRPC('NewGoodsList')->GetAllCondition($Filtergoods)->wait();
        foreach ($res->getIteminfo() as $k=>$v) {
            $Filter_list[$k]['goods_id'] = $v->getGoodsId();
            $Filter_list[$k]['goods_name'] = $v->getGoodsName();
            $Filter_list[$k]['image'] = $v->getImage();
            $Filter_list[$k]['shop_price'] = $v->getShopPrice();
            $Filter_list[$k]['market_price'] = $v->getMarketPrice();
        }

        $goods_id = promote_goods_id();
        $goods_id =explode(',',$goods_id);
//过滤促销商品，不出现在商品列表，只出现在促销区
        if ($Filter_list){
            foreach ($Filter_list as $key =>$value){
                if (!in_array($value['goods_id'],$goods_id)){
                    $Filter[] = $value;
                }
            }
        }



        foreach ($res->getPriceRange() as $k=>$v) {
            $price_range[$k]['price'] = $v->getPrice();
        }
        foreach ($res->getItemBrandId() as $k=>$v) {
            $brand_list[$k]['brand_id'] = $v->getBrandId();
            $brand_list[$k]['name'] = $v->getName();
            $brand_list[$k]['logo'] = $v->getLogo();
        }
        foreach ($res->getSpec() as $k=>$v) {
            $k = $v->getSpecId();
            $spec_list[$k]['spec_id'] = $v->getSpecId();
            $spec_list[$k]['name'] = $v->getName();
            foreach($v->getItem() as $k1=>$v1){
                $spec_list[$k]['item'][$k1]['val']= $v1->getVal();
                $spec_list[$k]['item'][$k1]['key'] = $v1->getKey();
                $spec_list[$k]['item'][$k1]['item'] = $v1->getItem();
            }
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,25);
        $show = $page->show();
        $goods_list= $Filter;

        // 帅选 品牌 规格 属性 价格
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($price_range,$filter_param,'goodsList'); // 帅选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($brand_list,$filter_param,'goodsList'); // 获取指定分类下的帅选品牌
        $filter_spec  = $goodsLogic->get_filter_spec($spec_list,$filter_param,'goodsList',1); // 获取指定分类下的帅选规格

        // 获取全部分类
        $category = new Psp\Newhome\ItemCatAllRequest();
        $category->setIsShow(true);
        $category->setPlatform($platform);
        $category->setLanguage($lang);
        list($res,$status) = GRPC('NewGoodsList')->GetItemCatAll($category)->wait();
        foreach ($res->getCatInfo2() as $k=>$v) {
            $k = $v->getId();
            $arr[$k]['id'] = $v->getId();
            $arr[$k]['name'] = $v->getName();
            $arr[$k]['parent_id'] = $v->getParentId();
            $arr[$k]['level'] = $v->getLevel();
        }
        $GetItemCatAll =$arr;
//        dump($GetItemCatAll);
        //热卖
        $hot = new Psp\Newhome\ItemHot();
        $hot->setIsOnSale(true);
        $hot->setIsRecommend(true);
        $hot->setLanguage($lang);
        $hot->setPlatform($platform);
        $hot->setState(1);
        list($res,$status) = GRPC('NewGoodsList')->GetItemHot($hot)->wait();
        foreach ($res->getIteminfo() as $k=>$v) {
            $hot_goods[$k]['goods_id'] = $v->getGoodsId();
            $hot_goods[$k]['goods_name'] = $v->getGoodsName();
            $hot_goods[$k]['image'] = $v->getImage();
            $hot_goods[$k]['shop_price'] = $v->getShopPrice();
            $hot_goods[$k]['market_price'] = $v->getMarketPrice();
        }


        $navigate_cat = navigate_goods($id,$platform,$lang); // 面包屑导航
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('goodsCate', $GetCurrentId);
        $this->assign('goods_category',$GetItemCatAll);
        $this->assign('filter_brand',$filter_brand);
        $this->assign('filter_spec',$filter_spec);
        $this->assign('hot_goods', $hot_goods);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('goods_list',$goods_list);
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('page',$page);
        $this->assign('cat_id',$id);
        S($key,$html);
        $html = $this->fetch();

        return $html;
    }

    /**
     * 新上架列表页
     */
    public function newOnsaleList()
    {
        $p=I('get.p/d',1);
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

        $page = new Psp\Pagination();
//        $page->setSortAsc(1);
//        $page->setSortBy(1);
        $page->setIndex($p);
        $page->setLimit(25);
        $newgoods = new Psp\Newhome\Page();
        $newgoods->setIsOnSale(1);
        $newgoods->setState(1);
        $newgoods->setLanguage($lang);
        $newgoods->setPlatform($platform);
        $newgoods->setPagination($page);
        list($res,$status) = GRPC('NewHome')->GetNewOnSale($newgoods)->wait();
        foreach ($res->getNewGoods() as $k=>$v) {
            $new_list[$k]['goods_id'] = $v->getGoodsId();
            $new_list[$k]['goods_name'] = $v->getGoodsName();
            $new_list[$k]['image'] = $v->getImage();
            $new_list[$k]['shop_price'] = $v->getShopPrice();
            $new_list[$k]['market_price'] = $v->getMarketPrice();
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,25);
        $show = $page->show();

        //热卖
        $hot = new Psp\Newhome\ItemHot();
        $hot->setIsOnSale(true);
        $hot->setIsRecommend(true);
        $hot->setLanguage($lang);
        $hot->setPlatform($platform);
        $hot->setState(1);
        list($res,$status) = GRPC('NewGoodsList')->GetItemHot($hot)->wait();
        if ($res){
        foreach ($res->getIteminfo() as $k=>$v) {
            $hot_goods[$k]['goods_id'] = $v->getGoodsId();
            $hot_goods[$k]['goods_name'] = $v->getGoodsName();
            $hot_goods[$k]['image'] = $v->getImage();
            $hot_goods[$k]['shop_price'] = $v->getShopPrice();
            $hot_goods[$k]['market_price'] = $v->getMarketPrice();
         }
        }
        $this->assign('hot_goods', $hot_goods);
        $this->assign('newList',$new_list);
        $this->assign('page',$show);
        return $this->fetch();
    }

    /**
     * 促销列表页
     */
    public function promotion(){
        //获取轮播图
        $p=I('get.p/d',1);
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
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("ItemId");
        $page->setIndex($p);
        $page->setLimit(5);
        $lunbo = new Psp\Newhome\AdPid();
        $lunbo->setPid(1136);
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

        //热卖
        $hot = new Psp\Newhome\ItemHot();
        $hot->setIsOnSale(true);
        $hot->setIsRecommend(true);
        $hot->setLanguage($lang);
        $hot->setPlatform($platform);
        $hot->setState(1);
        list($res,$status) = GRPC('NewGoodsList')->GetItemHot($hot)->wait();
        foreach ($res->getIteminfo() as $k=>$v) {
            $hot_goods[$k]['goods_id'] = $v->getGoodsId();
            $hot_goods[$k]['goods_name'] = $v->getGoodsName();
            $hot_goods[$k]['image'] = $v->getImage();
            $hot_goods[$k]['shop_price'] = $v->getShopPrice();
            $hot_goods[$k]['market_price'] = $v->getMarketPrice();
        }


        $goods_id = promote_goods_id();
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy('ItemId');
        $page->setIndex($p);
        $page->setLimit(25);
        $cuxiao = new Psp\Newhome\GGoodsId();
        $cuxiao->setIsOnSale(1);
        $cuxiao->setLanguage($lang);
        $cuxiao->setPlatform($platform);
        $cuxiao->setState(1);
        $cuxiao->setGoodsId($goods_id);
        $cuxiao->setPagination($page);
        list($res,$status) = GRPC('NewGoodsList')->GetGoodsIndo($cuxiao)->wait();
        if ($res){
            foreach ($res->getIteminfo() as $k=>$v) {
                $cuxiao_goods[$k]['goods_id'] = $v->getGoodsId();
                $cuxiao_goods[$k]['goods_name'] = $v->getGoodsName();
                $cuxiao_goods[$k]['image'] = $v->getImage();
                $cuxiao_goods[$k]['shop_price'] = $v->getShopPrice();
                $cuxiao_goods[$k]['market_price'] = $v->getMarketPrice();
            }
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,25);
        $show = $page->show();
        $this->assign('hot_goods', $hot_goods);
        $this->assign('newList',$cuxiao_goods);
        $this->assign('GetAdlist',$GetAdlist);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    /**
     * 专区商品列表页
     */
    public function specialList()
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
        $goods_list = array();
        $platform = I('get.platform_id','1');
        $p=I('get.p/d',1);

        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy('ItemID');
        $page->setIndex($p);
        $page->setLimit(25);

        $specialGoods = new Psp\Newhome\Flags();
        $specialGoods->setIsOnSale(true);
        $specialGoods->setLanguage($lang);
        $specialGoods->setPlatform($platform);
        $specialGoods->setState(1);
        $specialGoods->setPagination($page);
        $specialGoods->setFlags(I('get.flags'));
        list($res,$status) = GRPC('NewGoodsList')->GetSpecialList($specialGoods)->wait();
        if (empty($res)){
            $this->error('该专区暂无商品');
        }
        foreach ($res->getIteminfo() as $k=>$v) {
            $goods_list[$k]['goods_id'] = $v->getGoodsId();
            $goods_list[$k]['goods_name'] = $v->getGoodsName();
            $goods_list[$k]['image'] = $v->getImage();
            $goods_list[$k]['shop_price'] = $v->getShopPrice();
            $goods_list[$k]['market_price'] = $v->getMarketPrice();
        }

        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,25);
        $show = $page->show();
        $hot_goods =array_slice($goods_list,0,6) ;
        $this->assign('goods_list',$goods_list);
        $this->assign('hot_goods', $hot_goods);
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('page',$page);
        $this->assign('flags','1');//
        return $this->fetch();
    }

    /**
     *  查询配送地址,并执行回调函数
     */
    public function region()
    {
        $fid = I('fid/d');
        $callback = I('callback');
        $areamap= new \area\area();
        $reg = $areamap->getRegion($fid);
        foreach ($reg as $k => $v){
            $reglist[$k]['id'] = $k;
            $reglist[$k]['name'] = $v;
        }

        $parent_region = array();
        foreach ($reglist as $k => $v){
            $parent_region[]=$v;
        }
        echo $callback.'('.json_encode($parent_region).')';
        exit;
    }

    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {
        $goods_id = I('goods_id/d');//143
        $is_free_shipping = I('is_free_shipping/d');//143
        $weight = I('weight/d');//143
        $region_id = I('region_id/d');//28242
        $goods_logic = new GoodsLogic();
        $dispatching_data = $goods_logic->getGoodsDispatching($goods_id,$region_id,$weight,$is_free_shipping);
        $this->ajaxReturn($dispatching_data);
    }

    /**
     * 商品搜索列表页
     */
    public function search()
    {
        //C('URL_MODEL',0);
        $p=I('get.p/d',1);
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
        $filter_param = array(); // 帅选数组
        $id = I('get.id/d', 0); // 当前分类id
        $brand_id = I('brand_id', '');
        $sort = I('sort', 'ItemID'); // 排序
        $sort_asc = I('sort_asc', 'asc'); // 排序
        $price = I('price', ''); // 价钱
        $start_price = trim(I('start_price', '0')); // 输入框价钱
        $end_price = trim(I('end_price', '0')); // 输入框价钱
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱
        $q = urldecode(trim(I('q', ''))); // 关键字搜索
        empty($q) && $this->error('请输入搜索词');
        $id && ($filter_param['id'] = $id); //加入帅选条件中
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $price && ($filter_param['price'] = $price); //加入帅选条件中
        $q && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类

        // 过滤帅选的结果集里面找商品
        $page = new Psp\Pagination();
        if ($sort_asc == "asc" && $sort == "shop_price"){
            $page->setSortAsc(false);
        }else{
            $page->setSortAsc($sort_asc);
        }
        $page->setSortBy($sort);
        $page->setIndex($p);
        $page->setLimit(25);
        if (empty($price)){
            $price = "0-10000000";
        }
        $price = explode('-', $price);
        $brand_id_arr = explode('_', $brand_id);
        $brand_ids = implode(',',$brand_id_arr);
        $filter = new Psp\Newhome\Search();
        $filter->setLanguage($lang);
        $filter->setPlatform($platform);
        $filter->setKeywords($q);
        $filter->setBrandId($brand_ids);
        $filter->setIsOnSale(true);
        $filter->setStartPrice($price[0]);
        $filter->setEndPrice($price[1]);
        $filter->setPagination($page);
        list($res,$status) = GRPC('NewGoodsList')->GetSearchList($filter)->wait();
        foreach ($res->getIteminfo() as $k=>$v) {
            $filter_list[$k]['goods_id'] = $v->getGoodsId();
            $filter_list[$k]['goods_name'] = $v->getGoodsName();
            $filter_list[$k]['image'] = $v->getImage();
            $filter_list[$k]['shop_price'] = $v->getShopPrice();
            $filter_list[$k]['market_price'] = $v->getMarketPrice();
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $page  = new Page($count,25);
        $show = $page->show();
        $goods_list= $filter_list;

        foreach ($res->getPriceRange() as $k=>$v) {
            $price_range[$k]['price'] = $v->getPrice();
        }

        foreach ($res->getItemBrandId() as $k=>$v) {
            $brand_list[$k]['brand_id'] = $v->getBrandId();
            $brand_list[$k]['name'] = $v->getName();
            $brand_list[$k]['logo'] = $v->getLogo();
        }

        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'search'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_search_price($price_range,$filter_param, 'search'); // 帅选的价格期间
        $filter_brand = $goodsLogic->get_search_brand($brand_list, $filter_param, 'search'); // 获取指定分类下的帅选品牌

        //热卖
        $hot = new Psp\Newhome\ItemHot();
        $hot->setIsOnSale(true);
        $hot->setIsRecommend(true);
        $hot->setLanguage($lang);
        $hot->setPlatform($platform);
        $hot->setState(1);
        list($res,$status) = GRPC('NewGoodsList')->GetItemHot($hot)->wait();
        foreach ($res->getIteminfo() as $k=>$v) {
            $hot_goods[$k]['goods_id'] = $v->getGoodsId();
            $hot_goods[$k]['goods_name'] = $v->getGoodsName();
            $hot_goods[$k]['image'] = $v->getImage();
            $hot_goods[$k]['shop_price'] = $v->getShopPrice();
            $hot_goods[$k]['market_price'] = $v->getMarketPrice();
        }


        $this->assign('hot_goods', $hot_goods);
        $this->assign('goods_list', $goods_list);
        $this->assign('filter_menu', $filter_menu);  // 帅选菜单
        $this->assign('filter_brand', $filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price', $filter_price);// 帅选的价格期间
        $this->assign('filter_param', $filter_param); // 帅选条件
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('page',$page);
        $this->assign('q', I('q'));
        C('TOKEN_ON', false);
        return $this->fetch();
    }
    
    /**
     * 商品咨询ajax分页
     */
    public function ajax_consult(){        
//        $goods_id = I("goods_id/d", 0);
//        $consult_type = I('consult_type','0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后
//
//        $where  = ['is_show'=>1,'parent_id'=>0,'goods_id'=>$goods_id];
//        if($consult_type > 0){
//            $where['consult_type'] = $consult_type;
//        }
//        $count = M('GoodsConsult')->where($where)->count();
//        $page = new AjaxPage($count,5);
//        $show = $page->show();
//        $list = M('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select();
//        $replyList = M('GoodsConsult')->where("parent_id > 0")->order("id desc")->select();
//
//        $this->assign('consultCount',$count);// 商品咨询数量
//        $this->assign('consultList',$list);// 商品咨询
//        $this->assign('replyList',$replyList); // 管理员回复
//        $this->assign('page',$show);// 赋值分页输出
//        return $this->fetch();
    }
    
    /**
     * 商品评论ajax分页
     */
    public function ajaxComment()
    {
        $goods_id = I("goods_id/d");
        $commentType = I('commentType','1'); // 1 全部 2好评 3 中评 4差评
        $limit = 3;
        if(I('p/d')){
            $p = I('p/d');
        } else{
            $p = 1;
        }
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("comment_date");
        $page->setIndex($p);
        $page->setLimit($limit);

        $comment = new Psp\Store\CommentCondition;
        $comment->setGoodsId($goods_id);
        $comment->setPagination($page);
        $comment->setType($commentType);
        list($res,$status) = GRPC('sellerstore')->GetCommentList($comment)->wait();
        foreach ($res->getComment() as $k=>$v) {
            $commentList[$k]['is_anonymous'] = $v->getIsAnonymous();
            $commentList[$k]['username'] = $v->getMemberName();
            $commentList[$k]['goods_rank'] = $v->getStar(); // 服务总评
            $commentList[$k]['desc_star'] = $v->getDescStar();
            $commentList[$k]['add_time'] = $v->getCommentDate()->getSecondS();
            $commentList[$k]['service_star'] = $v->getServiceStar();
            $commentList[$k]['deliver_star'] = $v->getDeliverStar();
            $commentList[$k]['content'] = $v->getComment();
            $commentList[$k]['zan_num'] = $v->getZanNum();
            $commentList[$k]['comment_id'] = $v->getCommentId();
        }
//        dump($commentList);
        $total_count = $res->getPaginationResult()->getTotalRecords();
        // 每页条数
//        $limit_page = $res->getPaginationResult()->getPageSize();

        $Page = new AjaxPage($total_count,$limit) ;
        $show = $Page->show();
//        dump($show);

        $this->assign('commentlist',$commentList);// 商品评论
//        $this->assign('commentCount',$commentCount); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }    
    
    /**
     *  商品咨询
     */
    public function goodsConsult(){
        //  form表单提交
//        C('TOKEN_ON',true);
//        $goods_id = I("goods_id/d",'0'); // 商品id
//        $consult_type = I("consult_type",'1'); // 商品咨询类型
//        $username = I("username",'TPshop用户'); // 网友咨询
//        $content = I("content"); // 咨询内容
//
//        $verify = new Verify();
//        if (!$verify->check(I('post.verify_code'),'consult')) {
//            $this->error("验证码错误");
//        }
//
//
//        $result = $this->validate(input('post.'),['__token__'=>'require|token'],['__token__'=>'你已经提交过了']);
//        if (true !== $result) {
//            $this->error($result, U('/Home/Goods/goodsInfo',array('id'=>$goods_id)));
//            exit;
//        }
//
//        $goodsConsult = M('goodsConsult');
//        $data = array(
//            'goods_id'=>$goods_id,
//            'consult_type'=>$consult_type,
//            'username'=>$username,
//            'content'=>$content,
//            'add_time'=>time(),
//        );
//        $goodsConsult->add($data);
//        $this->success('咨询已提交!', U('/Home/Goods/goodsInfo',array('id'=>$goods_id)));
    }
    
    /**
     * 用户收藏某一件商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $goodsLogic = new GoodsLogic();
//        $result = $goodsLogic->collect_goods(cookie('user_id'),$goods_id);
        $result = $goodsLogic->collect_goods($goods_id);
        exit(json_encode($result));
    }
    
    /**
     * 加入购物车弹出
     */
    public function open_add_cart()
    {
        $flag = I('flag');
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
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex(1);
        $page->setLimit(4);
        $Favorite = new Psp\Newhome\FavoriteRequest();
        $Favorite->setPlatform($platform);
        $Favorite->setLanguage($lang);
        $Favorite->setState(1);
        $Favorite->setIsRecommend(1);
        $Favorite->setIsOnSale(1);
        $Favorite->setPagination($page);
        list($res, $status) = GRPC('NewHome')->GetFavoriteItem($Favorite)->wait();
        foreach ($res->getFavoriteItem() as $k => $v) {
            $Favorite_list[$k]['goods_name'] = $v->getGoodsName();
            $Favorite_list[$k]['goods_id'] = $v->getGoodsId();
            $Favorite_list[$k]['shop_price'] = $v->getShopPrice();
            $Favorite_list[$k]['market_price'] = $v->getMarketPrice();
            $Favorite_list[$k]['image'] = $v->getImage();
        }
        $recommend_goods = $Favorite_list;
        $this->assign('recommend_goods', $recommend_goods);
        $this->assign('flag',$flag);
        return $this->fetch();
    }

    /**
     * 积分商城
     */
    public function integralMall()
    {
//        $cat_id = I('get.id/d');
//        $minValue = I('get.minValue');
//        $maxValue = I('get.maxValue');
//        $brandType = I('get.brandType');
//        $point_rate = tpCache('shopping.point_rate');
//        $is_new = I('get.is_new',0);
//        $exchange = I('get.exchange',0);
//        $goods_where = array(
//            'is_on_sale' => 1,  //是否上架
//        );
//        //积分兑换筛选
//        $exchange_integral_where_array = array(array('gt',0));
//        // 分类id
//        if (!empty($cat_id)) {
//            $goods_where['cat_id'] = array('in', getCatGrandson($cat_id));
//        }
//        //积分截止范围
//        if (!empty($maxValue)) {
//            array_push($exchange_integral_where_array, array('elt', $maxValue));
//        }
//        //积分起始范围
//        if (!empty($minValue)) {
//            array_push($exchange_integral_where_array, array('egt', $minValue));
//        }
//        //积分+金额
//        if ($brandType == 1) {
//            array_push($exchange_integral_where_array, array('exp', ' < shop_price* ' . $point_rate));
//        }
//        //全部积分
//        if ($brandType == 2) {
//            array_push($exchange_integral_where_array, array('exp', ' = shop_price* ' . $point_rate));
//        }
//        //新品
//        if($is_new == 1){
//            $goods_where['is_new'] = $is_new;
//        }
//        //我能兑换
//        $user_id = cookie('user_id');
//        if ($exchange == 1 && !empty($user_id)) {
//            $user_pay_points = intval(M('users')->where(array('user_id' => $user_id))->getField('pay_points'));
//            if ($user_pay_points !== false) {
//                array_push($exchange_integral_where_array, array('lt', $user_pay_points));
//            }
//        }
//
//        $goods_where['exchange_integral'] =  $exchange_integral_where_array;
//        $goods_list_count = M('goods')->where($goods_where)->count();   //总页数
//        $page = new Page($goods_list_count, 25);
//        $goods_list = M('goods')->where($goods_where)->limit($page->firstRow . ',' . $page->listRows)->select();
//        $goods_category = M('goods_category')->where(array('level' => 1))->select();
//
//        $this->assign('goods_list', $goods_list);
//        $this->assign('page', $page->show());
//        $this->assign('goods_list_count',$goods_list_count);
//        $this->assign('goods_category', $goods_category);//商品1级分类
//        $this->assign('point_rate', $point_rate);//兑换率
//        $this->assign('nowPage',$page->nowPage);// 当前页
//        $this->assign('totalPages',$page->totalPages);//总页数
//        return $this->fetch();
    }

    /**
     * 全部商品分类
     * @author lxl
     * @time17-4-18
     */
    public function all_category(){
        return $this->fetch();
    }

    /**
     * 全部品牌列表
     * @author lxl
     * @time17-4-18
     */
    public function all_brand(){
        return $this->fetch();
    }




    
}
<?php
namespace app\mobile\controller;

use app\admin\logic\GoodsPromFactory;
use think\AjaxPage;
use think\Page;
use think\Db;
use Psp;
use Grpc;
use app\home\logic\GoodsLogic;

class Goods extends MobileBase 
{
    public function index()
    {
       // $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
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
     * 商品列表页
     */
    public function goodsList()
    {
        $p=I('get.p/d',1);
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
        }
        $platform = I('get.platform_id','1');
        $id = I('id/d',1); // 当前分类id
        $brand_id = I('brand_id/d','');
        $spec = I('spec',''); // 规格
        $attr = I('attr',''); // 属性
        $sort = I('sort','ItemID'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $filter_param['id'] = $id; //加入帅选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price  && ($filter_param['price'] = $price); //加入帅选条件中

        $goodsLogic = new \app\home\logic\GoodsLogic(); // 前台商品操作逻辑类
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
        $goods_list= $Filter_list;
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
        $this->assign('goods_list',$goods_list);
        $this->assign('goods_category',$GetItemCatAll);
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_spec',$filter_spec);  // 帅选规格
        $this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('goodsCate',$GetCurrentId);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('cat_id',$id);
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('page',$page);
        C('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }

    /**
     * 商品列表页 ajax 翻页请求 搜索
     */
    public function ajaxGoodsList()
    {
        $where ='';

        $cat_id  = I("id/d",0); // 所选择的商品分类id
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " WHERE cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }

        //$result = DB::query("select count(1) as count from __PREFIX__goods $where ");
        //$count = $result[0]['count'];
        //$page = new AjaxPage($count,10);

        $order = " order by goods_id desc"; // 排序
        //$limit = " limit ".$page->firstRow.','.$page->listRows;
        //$list = DB::query("select *  from __PREFIX__goods $where $order $limit");

        //$this->assign('lists',$list);
        $html = $this->fetch('ajaxGoodsList'); //return $this->fetch('ajax_goods_list');
       exit($html);
    }

    /**
     * 专区商品列表页
     */
    public function specialList()
    {

        cookie('flag',I('get.flags'));
        $lang = cookie('think_var');
        if ($lang == 'zh-cn'){
            $lang = 1;
        }elseif ($lang == 'en-us'){
            $lang = 3;
        }elseif ($lang == 'zh-tw'){
            $lang = 5;
        }elseif ($lang == 'ko-kr'){
            $lang = 2;
        }
        $platform = I('get.platform_id','1');
        $p=I('get.p/d',1);

        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy('ItemID');
        $page->setIndex($p);
        $page->setLimit(10);

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

        $this->assign('goods_list',$goods_list);
        $this->assign('page',$page);
        C('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxSpecialList');
        else
            return $this->fetch();
    }

    /**
     * 商品详情页
     */
    public function goodsInfo()
    {
        C('TOKEN_ON',true);
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
        }
        $platform = 1;// 平台
        $GoodsId = new Psp\Store\GoodsId();
        $GoodsId->setGoodsId($goods_id);
        $GoodsId->setLanguage($lang);
        $GoodsId->setPlatform($platform);

        list($res,$status) =  GRPC('sellerstore')->GetGoodsInfo($GoodsId)->wait();

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
        $GetItemInfo['provider_logo_url'] = $res->getProviderImgUrl();
        $commentCount['favourable_comment'] = $res->getFavourableComment(); //好评
        $commentCount['in_the_comment'] = $res->getInTheComments(); // 中评
        $commentCount['negative_comment'] = $res->getNegativeComment(); //差评
        $commentCount['rate1'] = round($res->getRate1()); //好评
        $commentCount['rate2'] = round($res->getRate2()); //中评
        $commentCount['rate3'] = round($res->getRate3()); //差评
        $commentCount['count'] = $res->getFavourableComment()+$res->getInTheComments()+$res->getNegativeComment();//总评

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
            $GetItemInfo['store_count'] += $v->getStoreCount();
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
        $this->assign('commentStatistics',$commentCount);//评论概览
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('spec_goods_price', json_encode($GetSpec,true)); // 规格 对应 价格 库存表
        $this->assign('goods',$GetItemInfo);
        $this->assign('hot',$hot);//热卖
        return $this->fetch();
    }

    /**
     * 商品详情页
     */
    public function detail()
    {
        //  form表单提交
       // C('TOKEN_ON',true);
        //$goods_id = I("get.id/d");
        //$goods = M('Goods')->where("goods_id", $goods_id)->find();
        //$this->assign('goods',$goods);
        //return $this->fetch();
    }

    /*
     * 商品评论
     */
    public function comment()
    {
        $goods_id = I("goods_id/d",0);
        $this->assign('goods_id',$goods_id);
        return $this->fetch();
    }

    /*
     * ajax获取商品评论
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

        $total_count = $res->getPaginationResult()->getTotalRecords();
        $Page = new AjaxPage($total_count,$limit) ;
        $show = $Page->show();
        $this->assign('commentlist',$commentList);// 商品评论
        $this->assign('count',$total_count);// 商品评论总条数
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('p', $p);//页数
        $this->assign('goods_id', $goods_id);//商品id
        $this->assign('commentType', $commentType);// 1 全部 2好评 3 中评 4差评 5晒图
        $this->assign('page_count', $limit);//一页显示的条数
        $this->assign('current_count', $limit * I('p'));//当前条数
        return $this->fetch();
    }

    /*
     * 获取商品规格
     */
    public function goodsAttr()
    {
        /*$goods_id = I("get.goods_id/d",0);
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表
        $this->assign('goods_attr_list',$goods_attr_list);
        $this->assign('goods_attribute',$goods_attribute);
        return $this->fetch();*/
    }

    /**
     * 积分商城
     */
    public function integralMall()
    {
        $rank= I('get.rank');
        //以兑换量（购买量）排序
        if($rank == 'num'){
            $ranktype = 'sales_sum';
            $order = 'desc';
        }
        //以需要积分排序
        if($rank == 'integral'){
            $ranktype = 'exchange_integral';
            $order = 'desc';
        }
        $point_rate = tpCache('shopping.point_rate');
        $goods_where = array(
            'is_on_sale' => 1,  //是否上架
        );
        //积分兑换筛选
        $exchange_integral_where_array = array(array('gt',0));

        // 分类id
        if (!empty($cat_id)) {
            $goods_where['cat_id'] = array('in', getCatGrandson($cat_id));
        }
        //我能兑换
        $user_id = cookie('user_id');
        if ($rank == 'exchange' && !empty($user_id)) {
            //获取用户积分
            //$user_pay_points = intval(M('users')->where(array('user_id' => $user_id))->getField('pay_points'));
           /* if ($user_pay_points !== false) {
                array_push($exchange_integral_where_array, array('lt', $user_pay_points));
            }*/
        }
        $goods_where['exchange_integral'] =  $exchange_integral_where_array;  //拼装条件
        //$goods_list_count = M('goods')->where($goods_where)->count();   //总页数
        //$page = new Page($goods_list_count, 15);
        //$goods_list = M('goods')->where($goods_where)->order($ranktype ,$order)->limit($page->firstRow . ',' . $page->listRows)->select();
        //$goods_category = M('goods_category')->where(array('level' => 1))->select();

        /*$this->assign('goods_list', $goods_list);
        $this->assign('page', $page->show());
        $this->assign('goods_list_count',$goods_list_count);
        $this->assign('goods_category', $goods_category);//商品1级分类*/
        $this->assign('point_rate', $point_rate);//兑换率
        //$this->assign('totalPages',$page->totalPages);//总页数
        if(IS_AJAX){
            return $this->fetch('ajaxIntegralMall'); //获取更多
        }
        return $this->fetch();
    }

     /**
     * 商品搜索列表页
     */
    public function search()
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
        }
        $platform = I('get.platform_id','1');
        $filter_param = array(); // 帅选数组
        $id = I('get.id/d',1); // 当前分类id
        $brand_id = I('brand_id/d','');
        $sort = I('sort','ItemID'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $filter_param['id'] = $id; //加入帅选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $price  && ($filter_param['price'] = $price); //加入帅选条件中
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中

        $goodsLogic = new \app\home\logic\GoodsLogic(); // 前台商品操作逻辑类

        // 过滤帅选的结果集里面找商品
        $page = new Psp\Pagination();
        if ($sort_asc == "asc" && $sort == "shop_price"){
            $page->setSortAsc(false);
        }else{
            $page->setSortAsc($sort_asc);
        }
        $page->setSortBy($sort);
        $page->setIndex($p);
        $page->setLimit(200);
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
        $page  = new Page($count,200);
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

        $this->assign('goods_list',$goods_list);
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('show',$show);// 赋值分页输出
        C('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }

    /**
     * 商品搜索列表页
     */
    public function ajaxSearch()
    {
        return $this->fetch();
    }

    /**
     * 品牌街
     */
    public function brandstreet()
    {
        //$getnum = 9;   //取出数量
        /*$goods=M('goods')->where(array('is_recommend'=>1,'is_on_sale'=>1))->page(1,$getnum)->cache(true,WALHAO_CACHE_TIME)->select(); //推荐商品
        for($i=0;$i<($getnum/3);$i++){
            //3条记录为一组
            $recommend_goods[] = array_slice($goods,$i*3,3);
        }*/
        /*$where = array(
            'is_hot' => 1,  //1为推荐品牌
        );*/
        //$count = M('brand')->where($where)->count(); // 查询满足要求的总记录数
        //$Page = new Page($count,20);
       // $brand_list = M('brand')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('sort desc')->select();
        //$this->assign('recommend_goods',$recommend_goods);  //品牌列表
        //$this->assign('brand_list',$brand_list);            //推荐商品
        //$this->assign('listRows',$Page->listRows);
        /*if(I('is_ajax')){
            return $this->fetch('ajaxBrandstreet');
        }
        return $this->fetch();*/
    }
    
    /**
     * 用户收藏某一件商品
     * @param type $goods_id
     */
    public function collect_goods($goods_id)
    {
        $goodsLogic = new GoodsLogic();
        $result = $goodsLogic->collect_goods($goods_id);
        exit(json_encode($result));
    }
}
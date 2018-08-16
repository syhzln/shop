<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller;

use app\admin\controller\Comment;
use app\home\logic\CartLogic;
use app\home\logic\CartLogic2;
use app\admin\logic\AccountCenterLogic;
use app\home\logic\OrderLogic;
use app\home\logic\UsersLogic;
use app\home\model\Pickup;
use app\home\model\UserAddress;
use app\home\logic\GoodsLogic;
use think\Controller;
use think\Db;
use Grpc;
use Psp;
use think\log;

class Cart extends Base
{
    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $user = array();
    /**
     * 初始化函数
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->cartLogic = new CartLogic();
        //$this->user_id = 1;  //测试
        if($this->checkLogin())
        {
            $jwt_token =$_COOKIE['token'];
            $payload =validate_json_web_token($jwt_token);//解码token
            //每次调用重新签发token
            $token = create_json_web_token($payload);
            setrawcookie('token',$token,0,'/',get_host(),false,true); //覆盖原token
            $this->user_id = (int)$payload['user_id'];

        }else{
            setcookie("curLogin", null, time() - 3600, "/", get_host());//token过期 清除登录态
            setcookie("uname", null, time() - 3600, "/", get_host());
            /*cookie('curLogin',null);
            cookie('uname',null);*/
            if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){
                // ajax 请求的处理方式
                exit($this->fetch('header_cart_list_noLogin'));
            }else{
                // 正常请求的处理方式
                $this->error('请登陆后操作',U("user/login"));
                exit;
            };


        }
    }

    public function cart(){
        $flag = I('flag'); //标记专区商品
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
        $page->setLimit(10);
        $Favorite = new Psp\Newhome\FavoriteRequest();
        $Favorite->setPlatform($platform);
        $Favorite->setLanguage($lang);
        $Favorite->setIsRecommend(1);
        $Favorite->setIsOnSale(1);
        $Favorite->setState(1);
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
        $this->assign('flag',$flag); //输出到页面用于ajax获取购物车列表时候 区分专区商品
        return $this->fetch();
    }

    public function index(){
        return $this->fetch('cart');
    }

    function ajaxAddCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $amount = I("goods_num/d");// 商品数量
        $spec_name = I("goods_spec/a",array()); // 商品规格*/
        $sku_id=implode("_",$spec_name);
//        $member_id =$this->user_id;
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($amount)){
            $this->ajaxReturn(['status'=>0,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        $spec_key = array_values($spec_name);
        if($spec_key){
            sort($spec_key);
            $goods_spec_key = implode('_', $spec_key);
        }else{
            $goods_spec_key = '';
        }
        $cartLogic = new CartLogic();
        $AddShoppingItem = $cartLogic->addGoodsToCart($amount,$goods_spec_key,$this->user_id,$goods_id,$sku_id);
        $this->ajaxReturn($AddShoppingItem);
    }

    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {

        $client=GRPC('Trade');
        $cartItems = new Psp\Trade\CartId();
        $cartItems->setCartId(I("cart_id"));
        list($res,$status) = $client->DeleteShoppingItem($cartItems)->wait();
        if($res->getValue()){
            $return_arr = array('status'=>1,'msg'=>'删除成功','CartItem'=>''); // 返回结果状态
            exit(json_encode($return_arr));
        }
    }

    public function ajaxDelAllCart()
    {
        $client=GRPC('Trade');
        $cartids=I("cart_ids/a");
        $cart_ids=new Psp\Trade\CartIds();
        $cart_ids->setCartId($cartids);
        list($res,$status) = $client->ClearShoppingCart($cart_ids)->wait();
        if($res->getValue()){
            $return_arr = array('status'=>1,'msg'=>'删除成功','CartItem'=>''); // 返回结果状态
            exit(json_encode($return_arr));
        }
    }

    /*
     * ajax 请求获取购物车列表
     */
    public function ajaxCartList()
    {
        $flag = I('flag');
        $amount = I("amount"); // amount 购物车商品数量
        $post_cart_select = I("cart_select/a", array()); // 购物车选中状态
        $item_id = I("item_id");//加减 商品的id
        $sku_id = I("sku_id");//加减 商品的sku_id
        $goodsLogic = new GoodsLogic();
        $cartLogic = new CartLogic();


        if ($_POST['num'] == 1) {
            // 修改购物车数量 和勾选状态
            $client = GRPC('Trade');
            $cartitems = new Psp\Trade\CartItems();
            $cartitems->setMemberId($this->user_id);
            $cartitems->setItemId($item_id);
            $cartitems->setSkuId($sku_id);
            $cartitems->setAmount($amount);
            list($res, $status) = $client->IncreaseShoppingItemAmount($cartitems)->wait();
        }
        if ($_POST['num'] == -1) {
            if($amount > 1) {
                $client = GRPC('Trade');
                $cartitems = new Psp\Trade\CartItems();
                $cartitems->setMemberId($this->user_id);
                $cartitems->setItemId($item_id);
                $cartitems->setSkuId($sku_id);
                $cartitems->setAmount($amount);
                list($res, $status) = $client->DecreaseShoppingItemAmount($cartitems)->wait();
            }
        }
        /*foreach ($amount as $key => $val) {
            $data['amount'] = $val < 1 ? 1 : $val;
            $data['selected'] = $post_cart_select[$key] ? 1 : 0;*/
        //普通商品
        /*if($cartList[$key]['prom_type'] == 0 && (empty($cartList[$key]['spec_key']))){
            $goods = Db::name('goods')->where('goods_id', $cartList[$key]['goods_id'])->find();
            // 如果有阶梯价格
            if (!empty($goods['price_ladder'])) {
                $price_ladder = unserialize($goods['price_ladder']);
                $data['member_goods_price'] = $data['goods_price'] = $goodsLogic->getGoodsPriceByLadder($data['goods_num'], $goods['shop_price'], $price_ladder);
            }
        }
        //限时抢购 不能超过购买数量
        if ($cartList[$key]['prom_type'] == 1) {
            $FlashSaleLogic = new \app\admin\logic\FlashSaleLogic($cartList[$key]['prom_id']);
            $data['goods_num'] = $FlashSaleLogic->getUserFlashResidueGoodsNum($this->user_id,$data['goods_num']); //获取用户剩余抢购商品数量
        }
        //团购 不能超过购买数量
        if($cartList[$key]['prom_type'] == 2){
            $groupBuyLogic =  new \app\admin\logic\GroupBuyLogic($cartList[$key]['prom_id']);
            $groupBuySurplus = $groupBuyLogic->getPromotionSurplus();//团购剩余库存
            if($data['goods_num'] > $groupBuySurplus){
                $data['goods_num'] = $groupBuySurplus;
            }
        }
        if ($cartList[$key]['goods_num'] != $data['goods_num'] || $cartList[$key]['selected'] != $data['selected']) {
            M('Cart')->where("id", $key)->save($data);
        }*/
        /* }
         $this->assign('select_all', input('post.select_all')); // 全选框
     }*/
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->getUserCartList($flag); // 选中的商品
        if (empty($result['total_price'])) {
            $result['total_price'] = array('total_fee' => 0, 'save_money' => 0, 'num' => 0);
        }
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        return $this->fetch('ajax_cart_list');
    }
    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {
        $item =I('item_id/a');
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        if($cartLogic->getUserCartOrderCount() == 0 ){
            $this->error ('你的购物车没有选中商品','Cart/cart');
        }
        $client=GRPC('Trade');
        $cartinfo = new Psp\Trade\CartInfo();

        $cartinfo->setMemberId($this->user_id);

        $iteminfo = new Psp\Trade\ItemInfo();
        foreach ($item as $k=>$v) {
            $items[$k] = Trade(ItemInfo);
            $items[$k]->setItemId(explode(',',$v)[0]);
            $items[$k]->setSkuId(explode(',',$v)[1]);
        }
        $cartinfo->setItemInfo($items);
        list($res,$status) = $client->GetCartInfo($cartinfo)->wait();
        if(!$res){
            $this->error ('没有选中商品','Cart/cart');
        }
        foreach ($res->getCartOrderList()as $k=>$v) {
            $cartList[$k]['sku_id']=$v->getSkuId();
            $cartList[$k]['amount']=$v->getCount();
            $cartList[$k]['spec_name']=$v->getSpecName();
            $cartList[$k]['title']=$v->getTitle();
            $cartList[$k]['price']=$v->getPrice();
            $cartList[$k]['cost']=$v->getCost();
            $cartList[$k]['item_id']=$v->getItemId();
            $cartList[$k]['thumb_img_url']=$v->getThunbImageUrl();
            $cartList[$k]['provider_id']=$v->getProviderId();
            $cartList[$k]['cart_id']=$v->getCartId();
            $cartList[$k]['store_name']=$v->getStoreName();
            $cartList[$k]['qq']=$v->getQq();
            foreach ($v->getLogostic() as $kk=>$vv){
                $cartList[$k]['delivery'][$kk]['temp_id']=$vv->getTempId();
                $cartList[$k]['delivery'][$kk]['logistics_name']=$vv->getLogisticsName();
                $cartList[$k]['delivery'][$kk]['code']=$vv->getDeliveryCode();
            }

        }
        //将商品信息按照供应商分类
        $delivery = array();
        foreach ($cartList as $k=>$v){
            $cart[$v['provider_id']][] = $v;
            $store_name[$v['provider_id']]['store_name'] = $v['store_name'];
            $store_name[$v['provider_id']]['qq'] = $v['qq'];
            $delivery[$v['provider_id']] = array_merge($delivery[$v['provider_id']]?$delivery[$v['provider_id']]:array(),$v['delivery']?$v['delivery']:array());
        }
        if($delivery){
            foreach ($delivery as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if(!in_array($vv,$deli[$k]?$deli[$k]:[])){
                        $deli[$k][] = $vv;
                    }
                }
            }
        }
        //$CartInfo = new
        /*if($this->user_id == 0){
            $this->error('请先登陆',U('Home/User/login'));
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        if($cartLogic->getUserCartOrderCount() == 0 ){
            $this->error ('你的购物车没有选中商品','Cart/cart');
        }
        $result =  $cartLogic->getUserCartList(1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->cache(true,WALHAO_CACHE_TIME)->select();// 物流公司
        // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $couponWhere = [
            'c2.uid' => $this->user_id,
            'c1.use_end_time' => ['gt', time()],
            'c1.use_start_time' => ['lt', time()],
            'c1.condition' => ['elt', $result['total_price']['total_fee']]
        ];
        $couponList = Db::name('coupon')->alias('c1')
            ->field('c1.name,c1.money,c1.condition,c2.*')
            ->join('__COUPON_LIST__ c2', ' c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0', 'inner')
            ->where($couponWhere)
            ->select();
        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        return $this->fetch();*/
        /*if($this->user_id == 0){
            $this->error('请先登陆',U('Home/User/login'));
        }*/
        //$result =  $cartLogic->getUserCartList(1); // 获取购物车商品
        //$shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->cache(true,WALHAO_CACHE_TIME)->select();// 物流公司
        $shippingList = array(
            array(
                "delivery_name" => "申通物流",
                "delivery_amount" => "0.00",
                "delivery_id" => "23234",
                "status" => 1
            ),
            array(
                "delivery_name" => "顺丰物流",
                "delivery_amount" => "0.00",
                "delivery_id" => "23124",
                "status" => 1
            )
        );
        $this->assign('shippingList', $deli); // 物流公司
        $this->assign('cartList',  $cart); // 购物车的商品
        $this->assign('storeName',  $store_name); // 购物车的商品
        return $this->fetch();
    }
    public function array_unique_fb($array2D)
    {
        foreach ($array2D as $v)
        {
            $v = join(",",$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[] = $v;
        }

        $temp = array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组
        foreach ($temp as $k => $v)
        {
            $temp[$k] = explode(",",$v);   //再将拆开的数组重新组装
        }
        return $temp;
    }

    /*
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress()
    {
        /*$address_list = M('UserAddress')->where(['user_id'=>$this->user_id,'is_pickup'=>0])->select();
        if($address_list){
        	$area_id = array();
        	foreach ($address_list as $val){
        		$area_id[] = $val['province'];
                        $area_id[] = $val['city'];
                        $area_id[] = $val['district'];
                        $area_id[] = $val['twon'];
        	}
                $area_id = array_filter($area_id);
        	$area_id = implode(',', $area_id);
        	$regionList = M('region')->where("id", "in", $area_id)->getField('id,name');
        	$this->assign('regionList', $regionList);
        }
        $address_where['is_default'] = 1;
        $c = M('UserAddress')->where(['user_id'=>$this->user_id,'is_default'=>1,'is_pickup'=>0])->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_address');*/
        //$address_list = M('UserAddress')->where(['user_id'=>$this->user_id,'is_pickup'=>0])->select();
        //$client = new Psp\Member\UserServiceClient('118.31.42.205:9300', [
        //    'credentials' => Grpc\ChannelCredentials::createInsecure()
        //]);
        $uid = $this->user_id;
        $address = new Psp\Member\Uid();
        $address->setUid($uid);
        list($res,$status) = GRPC('member')->GetUserAddress($address)->wait();
        $areamap= new \area\area();
        foreach($res->getAddressList() as $k=>$v){

            $arr[$k]['address_id'] = $v->getAddressId();
            $arr[$k]['uid'] = $v->getUid();
            $arr[$k]['location_code'] = $v->getLocationCode();
            $arr[$k]['address_info'] =$areamap->getAddrstr($arr[$k]['location_code']);
            $address =explode(',',$arr[$k]['address_info']);
            $arr[$k]['province'] = $address[0];
            $arr[$k]['city'] = $address[1];
            $arr[$k]['area'] = $address[2];
            $arr[$k]['address'] = $v->getAddress();
            $arr[$k]['name'] = $v->getName();
            $arr[$k]['post_code'] = $v->getPostCode();
            $arr[$k]['phone'] = $v->getPhone();
            $arr[$k]['is_default'] = $v->getIsDefault();

        }
        $this->assign('address_list',$arr);
        return $this->fetch('ajax_address');
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 获取自提点信息
     */
    public function ajaxPickup()
    {
        $province_id = I('province_id/d');
        $city_id = I('city_id/d');
        $district_id = I('district_id/d');
        /*if (empty($province_id) || empty($city_id) || empty($district_id)) {
            exit("<script>alert('参数错误');</script>");
        }*/
        $user_address = new UserAddress();
        $address_list = $user_address->getUserPickup($this->user_id);
        $pickup = new Pickup();
        $pickup_list = $pickup->getPickupItemByPCD($province_id, $city_id, $district_id);
        $this->assign('pickup_list', $pickup_list);
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_pickup');
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 更换自提点
     */
    public function replace_pickup()
    {
        $province_id = I('get.province_id/d');
        $city_id = I('get.city_id/d');
        $district_id = I('get.district_id/d');
//        $region_model = M('region');
        $call_back = I('get.call_back');
        if (IS_POST) {
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功
        }
        $address = array('province' => $province_id, 'city' => $city_id, 'district' => $district_id);
//        $p = $region_model->where(array('parent_id' => 0, 'level' => 1))->select();
//        $c = $region_model->where(array('parent_id' => $province_id, 'level' => 2))->select();
//        $d = $region_model->where(array('parent_id' => $city_id, 'level' => 3))->select();
//        $this->assign('province', $p);
//        $this->assign('city', $c);
//        $this->assign('district', $d);
        $this->assign('address', $address);
        $this->assign('call_back', $call_back);
        return $this->fetch();
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 更换自提点
     */
    public function ajax_PickupPoint()
    {
        $province_id = I('province_id/d');
        $city_id = I('city_id/d');
        $district_id = I('district_id/d');
        $pick_up_model = new Pickup();
        $pick_up_list = $pick_up_model->getPickupListByPCD($province_id,$city_id,$district_id);
        exit(json_encode($pick_up_list));
    }


    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3()
    {

        if($this->user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        if($_REQUEST['act'] == 'order_price')
        {
            $address_id = I('address_id/d');//地址id
            $item_id = I('item_sku/a');//商品信息
            $code = I('delivery_id/a'); //物流编码
            $count = I('count/a'); //商品数量
            $calculate_price = Trade(CalcPrice);//dump($count);die;
            $address_id&&$calculate_price->setAddressId($address_id);
            if($code){
                foreach ($code as $k=>$v){
                    $method[$k] = Trade(Method);
                    $method[$k]->setProviderId($k);
                    $method[$k]->setCode($v);
                }
            }

            $method&&$calculate_price->setMethod($method);
            foreach ($item_id as $k=>$v){
                $item[$k] = Trade(ItemInfo);
                $item[$k]->setItemId(explode(',',$v)[0]);
                $item[$k]->setSkuId(explode(',',$v)[1]);
                $item[$k]->setCount((int)$count[$v]);
            }
            $calculate_price->setItemInfo($item);
            list($res,$status) = GRPC(Trade)->GetOrderPrice($calculate_price)->wait();
            if($res){
                $order_price['item_price'] = $res->getItemPrice();
                $order_price['shipping_price'] = $res->getShippingPrice();
                $order_price['total_amount'] = $res->getTotalAmount();
                foreach ($res->getDeliveryPrice() as $k=>$v){
                    $order_price['delivery_price'][$v->getProviderId()] = $v->getShippingPrice();
                }
            }

            exit(json_encode(array('status'=>1,'msg'=>'计算价格成功','result'=>$order_price)));
        }

        // 提交订单

        if($_REQUEST['act'] == 'submit_order')
        {
            $cartlist = I('item_sku/a');
            $address_id = I('address_id/d');
            if(!$cartlist){
                exit(json_encode(array('status'=>3,'msg'=>'请选择商品信息','result'=>'')));
            }
            if(!$address_id){
                exit(json_encode(array('status'=>2,'msg'=>'请选择地址信息','result'=>'')));
            }
            $note = I('buyer_message/a');
            $shipping_price = I('shipping_price/a');
            $order_ids = $this->cartLogic->addOrder($this->user_id,$cartlist,$address_id,$note,$shipping_price);

            exit(json_encode(array('status'=>1,'msg'=>'提交订单成功','result'=>$order_ids)));

        }


    }
    /**
     *
     *生成订单
     *
     */
    public function cart5(){
        $address_id = I('address_id/d');
        $cartlist = I('cart_ids/a');
        $note = I('buyer_message/a');
        $client = GRPC(Trade);
        $cart = Trade(AllChangeOrder);
        foreach ($note as $k=>$v){
            $comment[$k] = Trade(Comment);
            $comment[$k]->setProviderId($k);
            $comment[$k]->setNote($v);
        }
        $cart->setBuyerMessage($comment);
        $cart->setMemberId(1);
        $cart->setPlatformId(1);
        $cart->setAddressId($address_id);
        $cart->setType(1);
        $cart->setCartIdList($cartlist);
        list($res,$status) = $client->CreateOrderFromShoppingCart($cart)->wait();
        foreach ($res->getOrderId() as $k=>$v){
            $order_ids[] = $v;
        }
        $order_ids = implode(',',$order_ids);
        exit(json_encode(array('status'=>1,'msg'=>'提交订单成功','result'=>$order_ids)));
    }
    /*
     * 订单支付页面
     */
    public function cart4()
    {
        $orderid = $_REQUEST['order_id'];
        if(is_array($orderid))
            $orderid = implode(',',$orderid);
        //根据订单id获取支付状态
        $client = GRPC(Trade);
        foreach (explode(',',$orderid) as $v) {
            $ordid = Trade(OrdId);
            $ordid->setOrderId((int)$v);
            list($res,$status) = $client->GetPayStatus($ordid)->wait();
            $pay_status = $res->getPayStatus();
            if($pay_status == 2){
                $order_detail_url = U("Home/User/order_detail",array('id'=>$v));
                header("Location: $order_detail_url");
                exit;
            }
            $order_sn = $res->getOrderSn();
        }
        //根据订单id获取订单金额
        $orderids = array_map(function($v){
            return (int)$v;
        },explode(',',$orderid));
        //根据订单id列表获取总金额
        $order_id = Trade(OrderIds);
        if ($orderids[0] == 0){
            $this->error("此订单无效");
        }
        $order_id->setOrderId($orderids);
        list($total_amount,$status) = $client->GetOrderTotalAmount($order_id)->wait();
        $amount = $total_amount->getOrderAmount();
        $order = array(
            'order_sn'=>$order_sn,
            'amount'=>$amount,
        );

        //获取账户可用余额
        $balance = new AccountCenterLogic();
        $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);
        $order['available_balance'] = empty($accountData['balance']) ? '0.00' : sprintf('%.2f',$accountData['balance']);

        //获取买呗余额
        $buy_pay_info = new UsersLogic();
        $buy_pay = $buy_pay_info->member_buy($this->user_id);
        $order['buy_available_balance'] = empty($buy_pay['can_use_money']) ? '0.00' : sprintf('%.2f',$buy_pay['can_use_money']);

        $paymentList = require_once("application/conf/payment.php");//取出支付配置
        $paymentList = convert_arr_key($paymentList, 'code');
        foreach($paymentList as $key => $val)
        {
            if($val['scene'] == 1 || $val['status'] == 0)
            {
                unset($paymentList[$key]); //0 PC+手机 1手机 2PC
            }
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('order_ids',$orderid);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }
    //ajax 请求购物车列表
    public function header_cart_list()
    {
        $flag = I('flag');
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cart_result = $cartLogic->getUserCartList($flag);
        if(empty($cart_result['total_price']))
            $cart_result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        $this->assign('cartList', $cart_result['cartList']); // 购物车的商品
        $this->assign('cart_total_price', $cart_result['total_price']); // 总计
        $this->assign('flag',$flag);
        $template = I('template','header_cart_list');
        return $this->fetch($template);
    }
    /**
     * 预售商品下单流程
     */

}

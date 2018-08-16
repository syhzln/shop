<?php
namespace app\mobile\controller;

use app\admin\model\GroupBuy;
use app\home\logic\CartLogic;
use app\home\logic\OrderLogic;
use think\Db;
use app\home\logic\GoodsLogic;
use app\home\logic\UsersLogic;
use app\admin\logic\AccountCenterLogic;
use Grpc;
use Psp;

class Cart extends MobileBase 
{
    
    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();        
    /**
     * 析构流函数
     */
    public function  __construct() 
    {
        parent::__construct();
        $this->cartLogic = new \app\home\logic\CartLogic();
        //从token中取出user_id
        $jwt_token =$_COOKIE['token'];
        $payload =validate_json_web_token($jwt_token);//解码token
        if ($payload['user_id']) {
            $logic = new UsersLogic();
            $user = $logic->get_info($payload['user_id']);
            $user = $user['result'];
            $token = create_json_web_token($payload);
            setrawcookie('token',$token,0,'/',get_host(),false,true); //覆盖原token
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息

        }
    }

    public function cart()
    {
        $flag = I('flag'); //标记专区商品
        //获取热卖商品
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
        }
        $platform = I('get.platform_id','1');
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex($p);
        $page->setLimit(20);
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
        $hot_goods = $Favorite_list;
        $this->assign('hot_goods',$hot_goods);
        $this->assign('flag',$flag); //输出到页面用于ajax获取购物车列表时候 区分专区商品
        return $this->fetch('cart');
    }

    /**
     * ajax 将商品加入购物车
     */
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
     * 购物车第二步确定页面
     */
    public function cart2()
    {
        $item =I('item_id/a');
        $address_id = I('address_id');
        if($address_id)$item = unserialize(base64_decode(I('item_id')));
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
            $this->error('购物车为空',U('cart'));exit();
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
        foreach ($cartList as $key=>$value) {
            $GetShoppingCartItems[]= $value;
        }
        foreach ($GetShoppingCartItems as $k => $val) {
            $total_price += $val['amount'] * $val['price'];
        }
        $total_price = array('totalizing' => $total_price); // 总计
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
        //dump($cartList);die;
        //获取用户的地址列表
        $address = new Psp\Member\Uid();
        $address->setUid($this->user_id);
        list($res,$status) = GRPC('member')->GetUserAddress($address)->wait();
        $areamap= new \area\area();
        if($res){
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
                if($arr[$k]['is_default']===true || $arr[$k]['address_id']==$address_id){
                    $addr = $arr[$k];
                }

            }
        }
        $this->assign('item', base64_encode(serialize($item))); // 默认地址
        $this->assign('address', $addr); // 默认地址
        $this->assign('shippingList', $deli); // 物流公司
        $this->assign('total_price',$total_price);
        $this->assign('cartList',  $cart); // 购物车的商品
       // $this->assign('cartLists',  $cart); // 购物车的商品
        $this->assign('storeName',  $store_name); // 购物车的商品
        return $this->fetch();
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
            $calculate_price = Trade(CalcPrice);
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
            if($val['scene'] !=1 || $val['status'] == 0)
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


    /*
    * ajax 请求获取购物车列表
    */
    public function ajaxCartList()
    {

        $flag = I('flag');
        $amount = I("amount"); // amount 购物车商品数量
        $item_id = I("item_id");//加减 商品的id
        $sku_id = I("sku_id");//加减 商品的sku_id

        if ($_POST['num'] == 1) {
            // 修改购物车数量 和勾选状态

            $cartitems = new Psp\Trade\CartItems();
            $cartitems->setMemberId($this->user_id);
            $cartitems->setItemId($item_id);
            $cartitems->setSkuId($sku_id);
            $cartitems->setAmount($amount);
            list($res, $status) = GRPC('Trade')->IncreaseShoppingItemAmount($cartitems)->wait();
        }
        if ($_POST['num'] == -1) {
            if($amount > 1) {

                $cartitems = new Psp\Trade\CartItems();
                $cartitems->setMemberId($this->user_id);
                $cartitems->setItemId($item_id);
                $cartitems->setSkuId($sku_id);
                $cartitems->setAmount($amount);
                list($res, $status) =  GRPC('Trade')->DecreaseShoppingItemAmount($cartitems)->wait();
            }
        }

        $memberId = new Psp\Trade\MemberId();
        $memberId->setMemberId($this->user_id);
        list($res,$status) =  GRPC('Trade')->GetShoppingCartItems($memberId)->wait();
        foreach ($res->getShoppingItemList()as $k=>$v) {
            $cartLists[$k]['item_id']= $v->getShoppingItem()->getItemId();
            $cartLists[$k]['amount']= $v->getShoppingItem()->getAmount();
            $cartLists[$k]['add_date']= $v->getShoppingItem()->getAddDate()->getSeconds();
            $cartLists[$k]['sku_id']= $v->getShoppingItem()->getSkuId();
            $cartLists[$k]['goods_name']= $v->getShoppingItem()->getGoodsName();
            $cartLists[$k]['promo_type']= $v->getShoppingItem()->getPromoType();
            $cartLists[$k]['spec_name']= $v->getShoppingItem()->getSpecName();
            $cartLists[$k]['cart_id']= $v->getCartId();
            $cartLists[$k]['shop_price']= $v->getShopPrice();
            $cartLists[$k]['market_price']= $v->getMarketPrice();
            $cartLists[$k]['thumb_img_url']= $v->getThumbImgUrl();
        }

        if (empty($cartLists)){ // 全部空
            if ($flag == 1){
                $goSee =1;
            }
            $cart_empty = 1;
            $this->assign('goSee',$goSee);
            $this->assign('cartEmpty',$cart_empty);
            return $this->fetch('ajax_cart_list');
        }

        foreach ($cartLists as $key=>$value) {
            if($value['promo_type']==1){
                $GetShoppingCartItems1[]= $value; //专区商品
            }else{
                $GetShoppingCartItems2[]= $value;
            }
        }

        if($flag==1){
            $GetShoppingCartItems = $GetShoppingCartItems1;
            $goSee = 1;
        }else{
            $GetShoppingCartItems = $GetShoppingCartItems2;
        }

        if (empty($GetShoppingCartItems)){ // 专区或普通购物车空
            $cart_empty = 1;
            $this->assign('cartEmpty',$cart_empty);
            $this->assign('goSee',$goSee);
            return $this->fetch('ajax_cart_list');
        }

        $total_goods_num = $total_price = $save_money = 0;//初始化数据。商品总共数量/商品总额/节约金额
        foreach ($GetShoppingCartItems as $k => $val) {
            /*$GetShoppingCartItems[$k]['small_total'] = $val['amount'] * $val['shop_price'];//price会员价
            $GetShoppingCartItems[$k]['store_count'] = getGoodNum($val['item_id'], $val['spec_name']) ?: 0; // 最多可购买的库存数量
            $total_goods_num += $val['amount'];
            // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
            if ($selected == 1 && $val['selected'] == 0){
                continue;
            }*/
            $total_price += $val['amount'] * $val['shop_price'];
            $save_money += $val['amount'] * $val['market_price'] - $val['amount'] * $val['shop_price'];
        }
        $total_price = array('totalizing' => $total_price, 'save_money' => $save_money, 'num' => $total_goods_num); // 总计
        setcookie('cn', $total_goods_num, null, '/',get_host());
        $cartList=array('cartList' => $GetShoppingCartItems, 'total_price' => $total_price);
        $this->assign('cartLists', $GetShoppingCartItems); // 购物车的商品
        $this->assign('cartList',$cartList);
        $this->assign('total_price',$total_price);
        return $this->fetch('ajax_cart_list');
    }

    /*
 * ajax 获取用户收货地址 用于购物车确认订单页面
 */
    public function ajaxAddress()
    {
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


}

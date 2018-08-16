<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\logic;
use app\admin\logic\FlashSaleLogic;
use app\admin\logic\GroupBuyLogic;
use app\admin\logic\PromGoodsLogic;
use app\admin\model\Cart;
use app\admin\model\Goods;
use think\Model;
use think\Db;
use Grpc;
use Psp;
use think\log;
/**
 * 购物车 逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class CartLogic extends Model
{
    protected $goods;//商品模型
    protected $session_id;//session_id
    protected $user_id = 0;//user_id

    public function __construct()
    {
        parent::__construct();
        $this->session_id = session_id();
    }

    /**
     * 将session_id改成unique_id
     * @param $uniqueId|api唯一id 类似于 pc端的session id
     */
    public function setUniqueId($uniqueId){
        $this->session_id = $uniqueId;
    }
    /**
     * 包含一个商品模型
     * @param $goods_id
     */
    public function setGoodsModel($goods_id)
    {
        $goodsModel = new Goods();
        $this->goods = $goodsModel::get($goods_id);
    }

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * modify ：addCart
     * @param $goods_num|购买商品数量
     * @param $goods_spec_key|购买商品规格
     * @return array
     */
    public function addGoodsToCart($amount, $goods_spec_key,$member_id,$goods_id,$sku_id)
    {
       /* $goodsModel = new Goods();
        $this->goods = $goodsModel::get($goods_id);
        if(empty($this->goods)){
            return ['status'=>'-3','msg'=>'购买商品不存在','result'=>''];
        }
        if ($this->goods['prom_type'] > 0 && $this->user_id == 0) {
            return array('status' => -101, 'msg' => '购买活动商品必须先登录', 'result' => '');
        }*/
        /*$userCartCount = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id])->count();//获取用户购物车的商品有多少种
        if ($userCartCount >= 20) {
            return array('status' => -9, 'msg' => '购物车最多只能放20种商品', 'result' => '');
        }*/
        /*if($this->goods['prom_type'] == 1){
            $result = $this->addFlashSaleCart($amount, $goods_spec_key,$member_id);
        }elseif($this->goods['prom_type'] == 2){
            $result = $this->addGroupBuyCart($amount);
        }elseif($this->goods['prom_type'] == 3){
            $result = $this->addPromGoodsCart($amount, $goods_spec_key,$member_id);
        }else{
            $result = $this->addNormalCart($amount, $goods_spec_key,$member_id);
        }*/
        $result = $this->addNormalCart($amount, $goods_spec_key,$member_id,$goods_id,$sku_id);
        $result['result'] = $UserCartGoodsNum = $this->getUserCartGoodsNum(); // 查找购物车数量
        setcookie('cn', $UserCartGoodsNum, null, '/',get_host());
        return $result;
    }

    /**
     * modify ：addOrder
     * @param $user_id|用户id
     * @param $address_id|收货地址id
     * @param $cartlist|购买商品信息
     * @return string
     */
    public function addOrder($user_id,$cartlist,$address_id,$note,$shipping_price){
        //根据购物车列表获取商品的信息
        $user = Trade(CartInfo);
        $user->setMemberId($user_id);
        foreach ($cartlist as $k=>$v){
            $item[$k] = Trade(ItemInfo);
            $item[$k]->setItemId(explode(',',$v)[0]);
            $item[$k]->setSkuId(explode(',',$v)[1]);
        }
        $user->setItemInfo($item);
        list($res,$status) = GRPC('Trade')->GetCartInfo($user)->wait();
        foreach ($res->getCartOrderList() as $k=>$v){
            $order_item[$k]['provider_id'] = $v->getProviderId();
            $order_item[$k]['sku_id'] = $v->getSkuId();
            $order_item[$k]['title'] = $v->getTitle();
            $order_item[$k]['item_id'] = $v->getItemId();
            $order_item[$k]['thumb_img_url'] = $v->getThunbImageUrl();
            $order_item[$k]['count'] = $v->getCount();
            $order_item[$k]['price'] = $v->getPrice();
            $order_item[$k]['cost'] = $v->getCost();
        }

        //将商品信息按照供应商分类
        foreach ($order_item as $k=>$v){
            $order_items[$v['provider_id']][] = $v;
        }
        $order = Trade(OrderInfo);
        //循环生成订单
        foreach ($order_items as $k=>$v){
            $order->setMemberId($user_id);
            $order->setProviderId((int)$k);
            $order->setPlatformId(PLATFORM);
            $order->setAddressId($address_id);
            $order->setType(1);
            foreach ($v as $kk=>$vv){
                $orderitem[$kk] = Trade(OrderItem);
                $orderitem[$kk]->setName($vv['title']);
                $orderitem[$kk]->setItemId((int)$vv['item_id']);
                $orderitem[$kk]->setProviderId((int)$vv['provider_id']);
                if (!empty($vv['sku_id'])) {
                    $orderitem[$kk]->setSkuId($vv['sku_id']);
                }
                $orderitem[$kk]->setPrice($vv['price']);
                $orderitem[$kk]->setCost($vv['cost']);
                $orderitem[$kk]->setCurrencey(1);
                $orderitem[$kk]->setAmount($vv['count']);
                //$orderitem[$kk]->setSkuUnit($vv['sku_unit']);
                $orderitem[$kk]->setThumbImageUrl($vv['thumb_img_url']);
            }
            $order->setOrderItems($orderitem);
            unset($orderitem);
            $order->setComment($note[$k]);
            $shipping_price[$k]&&$order->setShippingPrice($shipping_price[$k]);
            list($res,$status) = GRPC('Trade')->CreateOrderItem($order)->wait();
            $order_ids[]=$res->getOrderId();
        }
        $order_ids = implode(',',$order_ids);
        return $order_ids;
    }

    /**
     * 购物车添加普通商品
     * @param $goods_num|购买的商品数量
     * @param $goods_spec_key|购买的商品规格
     * @return array
     */
    private function addNormalCart($amount,$goods_spec_key,$member_id,$goods_id,$sku_id)
    {
        //$CartModel = new Cart();
        $goodsLogic = new GoodsLogic();
        // 获取商品对应的规格价钱 库存 条码
        /*$specGoodsPriceList = M('SpecGoodsPrice')->where("goods_id", $this->goods['goods_id'])->cache(true, WALHAO_CACHE_TIME)->getField("key,key_name,price,store_count,sku");
        if(!empty($specGoodsPriceList)){
            if(empty($goods_spec_key)){
                return array('status' => -1, 'msg' => '必须传递商品规格', 'result' => '');
            }
            $specPrice = $specGoodsPriceList[$goods_spec_key]['price']; // 获取规格指定的价格
        }*/
        // 查询购物车是否已经存在这商品
        //$userCartGoods = $CartModel::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_name'=>$goods_spec_key]);
        // 如果该商品已经存在购物车
        /*if ($userCartGoods) {
            $userWantGoodsNum = $amount + $userCartGoods['amount'];//本次要购买的数量加上购物车的本身存在的数量
            if($userWantGoodsNum > $this->goods['store_count']){
                return array('status' => -4, 'msg' => '商品库存不足，剩余'.$this->goods['store_count'].',当前购物车已有'.$userCartGoods['amount'].'件', 'result' => '');
            }*/
            //如果有阶梯价格
            /*if (!empty($goods['price_ladder'])) {
                $price_ladder = unserialize($goods['price_ladder']);
                //$price = $goodsLogic->getGoodsPriceByLadder($userWantGoodsNum, $this->goods['shop_price'], $price_ladder);
            } else {
                //没有阶梯价格，如果有规格价格，就使用规格价格，否则使用本店价。
                $price = isset($specPrice) ? $specPrice : $this->goods['shop_price'];
            }
            $cartResult = $CartModel->save(['amount' => $userWantGoodsNum,'goods_price'=>$price,'member_goods_price'=>$price], ['id' => $userCartGoods['id']]);
        }else{*/
            //如果有阶梯价格
        if (!empty($goods['price_ladder'])) {
                $price_ladder = unserialize($goods['price_ladder']);
                $price = $goodsLogic->getGoodsPriceByLadder($amount, $this->goods['shop_price'], $price_ladder);
            } else {
                //没有阶梯价格，如果有规格价格，就使用规格价格，否则使用本店价。
                //$price = isset($specPrice) ? $specPrice : $this->goods['shop_price'];
            //}
            /*$cartAddData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $price,  // 购买价
                'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
                'amount' => $amount, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 0,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id' => 0,   // 活动id
                );*/
            if($goods_spec_key){
                $AddShoppingItem['spec_key'] = $goods_spec_key;
                //$cartAddData['spec_key_name'] = $specGoodsPriceList[$goods_spec_key]['key_name']; // 规格 key_name
            }
            //$cartResult = Db::name('Cart')->insert($cartAddData);
                //Log::debug(date('Ymdhis')."  calling start Class: ".CONTROLLER_NAME." method: ".ACTION_NAME."Start");
                $client=GRPC('Trade');
                $cart = new Psp\Trade\Cart();
                $cart->setMemberId($member_id);
                $cart->setItemId($goods_id);
                if ($sku_id !== 0){
                    $cart->setSkuId($sku_id);
                }
                $cart->setAmount($amount);
                $cart->setPromoType(1);
                $cart->setPromoId(1);
                $AddDate = time();
                if ($AddDate) {
                    $time = new Psp\Timestamp();
                    $time->setSeconds($AddDate);
                    $cart->setAddDate($time);
                }
            list($res,$status) = $client->AddShoppingItem($cart)->wait();
            if ($res->getValue()) {
                    exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => '')));
                } else {
                    //exit(json_encode(array('status' => -1, 'msg' => '加入购物车失败', 'result' => '')));
                    exit(json_encode(array('status'=>-4,'msg'=>'库存不足','result'=>'')));
                }
            }
    }

    /**
     * 购物车添加秒杀商品
     * @param $goods_num|购买的商品数量
     * @return array
     */
    private function addFlashSaleCart($amount,$goods_spec_key)
    {
       // $CartModel = new Cart();
        $flashSaleLogic = new FlashSaleLogic($this->goods['prom_id']);
        $flashSale = $flashSaleLogic->getPromModel();
        $flashSaleIsEnd = $flashSaleLogic->checkFlashSaleIsEnd();
        if($flashSaleIsEnd){
            return array('status' => -9, 'msg' => '秒杀活动已结束', 'result' => '');
        }
        $flashSaleIsAble = $flashSaleLogic->checkActivityIsAble();
        if(!$flashSaleIsAble){
            //活动没有进行中，走普通商品下单流程
            return $this->addNormalCart($amount,$goods_spec_key);
        }
        //获取用户购物车的抢购商品
        //$userCartGoods = $CartModel::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id']]);
        /*$userCartGoodsNum = empty($userCartGoods) ? 0 : $userCartGoods['amount'];///获取用户购物车的抢购商品数量
        $userFlashOrderGoodsNum = $flashSaleLogic->getUserFlashOrderGoodsNum($this->user_id); //获取用户抢购已购商品数量
        $flashSalePurchase = $flashSale['amount'] - $flashSale['buy_num'];//抢购剩余库存
        $userBuyGoodsNum = $amount + $userFlashOrderGoodsNum + $userCartGoodsNum;
        if($userBuyGoodsNum > $flashSale['buy_limit']){
            return array('status' => -4, 'msg' => '每人限购'.$flashSale['buy_limit'].'件，您已下单'.$userFlashOrderGoodsNum.'件'.'购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }
        $userWantGoodsNum = $amount + $userCartGoodsNum;//本次要购买的数量加上购物车的本身存在的数量
        if($userWantGoodsNum > $flashSalePurchase){
            return array('status' => -4, 'msg' => '商品库存不足，剩余'.$flashSalePurchase.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }*/

        //if($userCartGoodsNum > 0){
            //$AddFlashSale = $CartModel->save(['amount' => $userWantGoodsNum], ['id' => $userCartGoods['id']]);
        //}else{
            /*$cartAddFlashSaleData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $flashSale['price'],  // 购买价
                'member_goods_price' => $flashSale['price'],  // 会员折扣价 默认为 购买价
                'goods_num' => $userWantGoodsNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 1,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id' => $this->goods['prom_id'],   // 活动id
            );
            $cartResult = Db::name('Cart')->insert($cartAddFlashSaleData);
        }*/

            $AddShoppingItem = array(
                "member_id" => 9,
                "goods_id" => 10,
                "goods_sn" => "1242153", //商品编号
                "goods_name" => "野菊花",
                "market_price" => "10.00", //市场价
                "shop_price" => 200, //商城价
                "amount" => 2,
                "add_date" => time(), //加入购物车的时间
                "prom_type" => 1, //0 普通订单 1.限时抢购 2.团购 3.促销优惠
                "promo_id" => 1 //促销活动id
            );
        if($AddShoppingItem !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     *  购物车添加优惠促销商品
     * @param $goods_num|购买的商品数量
     * @param $goods_spec_key|购买的商品规格
     * @return array
     */
     private function addPromGoodsCart($amount,$goods_spec_key)
     {
        $CartModel = new Cart();
         $promGoodsLogic = new PromGoodsLogic($this->goods['prom_id']);
        $promGoods = $promGoodsLogic->getPromModel();
        //活动是否存在，是否关闭，是否处于有效期
        if(empty($promGoods) || $promGoods['is_close'] == 1 || !(time() > $promGoods['start_time'] && time() < $promGoods['end_time'])){
            //活动不存在，已关闭，不处于有效期,走添加普通商品流程
            return $this->addNormalCart($amount,$goods_spec_key);
        }
        // 获取商品对应的规格价钱 库存 条码
        //$specGoodsPriceList = M('SpecGoodsPrice')->where("goods_id", $this->goods['goods_id'])->cache(true, WALHAO_CACHE_TIME)->getField("key,key_name,price,store_count,sku");
        /*if(!empty($specGoodsPriceList)){
            $specPrice = $specGoodsPriceList[$goods_spec_key]['price']; // 获取规格指定的价格
        }*/
        //如果有规格价格，就使用规格价格，否则使用本店价。
        //$priceBefore = isset($specPrice) ? $specPrice : $this->goods['shop_price'];
        //计算优惠价格
        //$priceAfter = $promGoodsLogic->getPromotionPrice($priceBefore);
        // 查询购物车是否已经存在这商品
        //$userCartGoods = $CartModel::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>$goods_spec_key]);
        // 如果该商品已经存在购物车
        /*if ($userCartGoods) {
            $userWantGoodsNum = $amount + $userCartGoods['amount'];//本次要购买的数量加上购物车的本身存在的数量
            $AddPromotion = $CartModel->save(['amount' => $userWantGoodsNum,'goods_price'=>$priceAfter,'member_goods_price'=>$priceAfter], ['id' => $userCartGoods['id']]);
        }else{*/
            /*$cartAddData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $priceAfter,  // 购买价
                'member_goods_price' => $priceAfter,  // 会员折扣价 默认为 购买价
                'goods_num' => $amount, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 3,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id' => $this->goods['prom_id'],   // 活动id
            );
            if($goods_spec_key){
                $cartAddData['spec_key'] = $goods_spec_key;
                $cartAddData['spec_key_name'] = $specGoodsPriceList[$goods_spec_key]['key_name']; // 规格 key_name
            }
            $cartResult = Db::name('Cart')->insert($cartAddData);*/
            $AddShoppingItem = array(
                "member_id" => 9,
                "goods_id" => 10,
                "goods_sn" => "1242153", //商品编号
                "goods_name" => "野菊花",
                "market_price" => "10.00", //市场价
                "shop_price" => 200, //商城价
                "amount" => 2,
                "add_date" => time(), //加入购物车的时间
                "type" => 3, //0 普通订单 1.限时抢购 2.团购 3.促销优惠
                "promo_id" => 1 //促销活动id
            );
        //}
        if($AddShoppingItem !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     *  购物车添加团购商品
     * @param $goods_num|购买的商品数量
     * @return array
     */
    private function addGroupBuyCart($amount)
    {
        //$CartModel = new Cart();
        $groupBuyLogic = new GroupBuyLogic($this->goods['prom_id']);
        $groupBuy = $groupBuyLogic->getPromModel();
        //活动是否已经结束
        if($groupBuy['is_end'] == 1 || empty($groupBuy)){
            return array('status' => -4, 'msg' => '团购活动已结束', 'result' => '');
        }
        //获取用户购物车的团购商品
        /*$userCartGoods = $CartModel::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id']]);
        $userCartGoodsNum = empty($userCartGoods) ? 0 : $userCartGoods['amount'];///获取用户购物车的团购商品数量
        $userWantGoodsNum = $userCartGoodsNum + $amount;//购物车加上要加入购物车的商品数量
        $groupBuyPurchase = $groupBuy['amount'] - $groupBuy['buy_num'];//团购剩余库存
        if($userWantGoodsNum > $groupBuyPurchase){
            return array('status' => -4, 'msg' => '商品库存不足，剩余'.$groupBuyPurchase.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }
        // 如果该商品已经存在购物车
        if($userCartGoodsNum > 0){
            $AddGroupBuy = $CartModel->save(['amount' => $userWantGoodsNum], ['id' => $userCartGoods['id']]);
        }else{*/
           /* $cartAddFlashSaleData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $groupBuy['price'],  // 购买价
                'member_goods_price' => $groupBuy['price'],  // 会员折扣价 默认为 购买价
                'goods_num' => $userWantGoodsNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 2,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id' => $this->goods['prom_id'],   // 活动id
            );
            $cartResult = Db::name('Cart')->insert($cartAddFlashSaleData);*/
            $AddShoppingItem = array(
                "member_id" => 9,
                "goods_id" => 10,
                "goods_sn" => "1242153", //商品编号
                "goods_name" => "野菊花",
                "market_price" => "10.00", //市场价
                "shop_price" => 200, //商城价
                "amount" => 2,
                "add_date" => time(), //加入购物车的时间
                "prom_type" => 2, //0 普通订单 1.限时抢购 2.团购 3.促销优惠
                "promo_id" => 1 //促销活动id
            );
        //}
        if($AddShoppingItem !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     * 获取用户购物车商品总数
     * @return float|int
     */
    public function getUserCartGoodsNum()
    {
        /*$goods_num = Db::name('cart')->where(['user_id' => $this->user_id, 'session_id' => $this->session_id])->sum('goods_num');
        return empty($goods_num) ? 0 : $goods_num;*/
        $GetGoodsCount = array(
            array(
                "member_id" => 9,
                "goods_id" => 39,
                "amount" => 3,
            ),
            array(
                "member_id" => 9,
                "goods_id" => 1,
                "amount" => 9,
             ),
            array(
                "member_id" => 9,
                "amount" => 10,
                "goods_id" => 41,
            )
        );
        return $GetGoodsCount;
    }

    /**
     * 获取用户的购物车列表
     * @param int $selected|是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * @return array
     */
    public function getUserCartList($selected)
    {
        /*// 如果用户已经登录则按照用户id查询
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
            // 给用户计算会员价 登录前后不一样
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }
        $cartList = DB::name('Cart')->where($cartWhere)->select();  // 获取购物车商品
        $total_goods_num = $total_price = $cut_fee = 0;//初始化数据。商品总共数量/商品总额/节约金额
        foreach ($cartList as $k => $val) {
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];
            $cartList[$k]['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']) ?: 0; // 最多可购买的库存数量
            $total_goods_num += $val['goods_num'];

            // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
            if ($selected == 1 && $val['selected'] == 0){
                continue;
            }
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];
            $total_price += $val['goods_num'] * $val['member_goods_price'];
        }

        $total_price = array('total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $total_goods_num,); // 总计
        setcookie('cn', $total_goods_num, null, '/');
        return array('cartList' => $cartList, 'total_price' => $total_price);*/
        // 如果用户已经登录则按照用户id查询
       /*if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
            // 给用户计算会员价 登录前后不一样
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }*/
        //Log::debug(date('Ymdhis')."  calling start Class: ".CONTROLLER_NAME." method: ".ACTION_NAME."Start");
        $client=GRPC('Trade');
        $memberId = new Psp\Trade\MemberId();
        $memberId->setMemberId($this->user_id);
        list($res,$status) = $client->GetShoppingCartItems($memberId)->wait();
       //Log::debug(date('Ymdhis')." calling end");
        foreach ($res->getShoppingItemList()as $k=>$v) {
            $cartLists[$k]['item_id']= $v->getShoppingItem()->getItemId();
            $cartLists[$k]['amount']= $v->getShoppingItem()->getAmount();
            $cartLists[$k]['add_date']= $v->getShoppingItem()->getAddDate()->getSeconds();
            $cartLists[$k]['sku_id']= $v->getShoppingItem()->getSkuId();
            $cartLists[$k]['goods_name']= $v->getShoppingItem()->getGoodsName();
            $cartLists[$k]['spec_name']= $v->getShoppingItem()->getSpecName();
            $cartLists[$k]['promo_type']= $v->getShoppingItem()->getPromoType();
            $cartLists[$k]['cart_id']= $v->getCartId();
            $cartLists[$k]['shop_price']= $v->getShopPrice();
            $cartLists[$k]['market_price']= $v->getMarketPrice();
            $cartLists[$k]['thumb_img_url']= $v->getThumbImgUrl();
        }

        if (empty($cartLists)){
            setcookie('cn', 0, null, '/',get_host());
            return;
        }
        foreach ($cartLists as $key=>$value) {
            if($value['promo_type']==1){
                $GetShoppingCartItems1[]= $value; //专区商品
            }else{
                $GetShoppingCartItems2[]= $value;
            }

        }
        if($selected==1){
            $GetShoppingCartItems = $GetShoppingCartItems1;
        }else{
            $GetShoppingCartItems = $GetShoppingCartItems2;
        }
        if (empty($GetShoppingCartItems)){
            setcookie('cn', 0, null, '/',get_host());
            return;
        }
        $total_goods_num = $total_price = $save_money = 0;//初始化数据。商品总共数量/商品总额/节约金额
        foreach ($GetShoppingCartItems as $k => $val) {
            $GetShoppingCartItems[$k]['small_total'] = $val['amount'] * $val['shop_price'];//price会员价
            //$GetShoppingCartItems[$k]['store_count'] = getGoodNum($val['item_id'], $val['spec_name'])?getGoodNum($val['item_id'], $val['spec_name']):0; // 最多可购买的库存数量
            $total_goods_num += $val['amount'];

            // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
            if ($selected == 1 && $val['selected'] == 0){
                continue;
            }
            $total_price += $val['amount'] * $val['shop_price'];
            $save_money += $val['amount'] * $val['market_price'] - $val['amount'] * $val['shop_price'];
        }
        $total_price = array('totalizing' => $total_price, 'save_money' => $save_money, 'num' => $total_goods_num); // 总计
        setcookie('cn', $total_goods_num, null, '/',get_host());
        return array('cartList' => $GetShoppingCartItems, 'total_price' => $total_price);
    }
    /**
     *  modify ：cart_count
     *  获取用户购物车欲购买的商品有多少种
     * @return int|string
     */
    public function getUserCartOrderCount()
    {
        /*$count = Db::name('Cart')->where(['user_id' => $this->user_id , 'selected' => 1])->count();
        return $count;*/
        $GetCartCount = array(
            array(
                "member_id" => 9,
                "goods_id" => 39,
                "selected" => 1
            ),
            array(
                "member_id" => 9,
                "goods_id" => 1,
                "selected" => 1
            ),
            array(
                "member_id" => 9,
                "goods_id" => 41,
             )
        );
        return $GetCartCount;
    }

    /**
     * 用户登录后 对购物车操作
     * modify：login_cart_handle
     */
    public function doUserLoginHandle()
    {
        if (empty($this->session_id) || empty($this->user_id)) {
            return;
        }
        //登录后将购物车的商品的 user_id 改为当前登录的id
        /*$cart = new Cart();
        $cart->save(['user_id' => $this->user_id], ['session_id' => $this->session_id, 'user_id' => 0]);
        // 查找购物车两件完全相同的商品
        $cart_id_arr = $cart->field('id')->where(['user_id' => $this->user_id])->group('goods_id,spec_key')->having('count(goods_id) > 1')->select();
        if (!empty($cart_id_arr)) {
            $cart_id_arr = get_arr_column($cart_id_arr, 'id');
            M('cart')->delete($cart_id_arr); // 删除购物车完全相同的商品
        }*/
        $CartAction = array(
            array(
                "member_id" => 9,
                "goods_id" => 1,
                "spec_name" => "黄色"
             ),
            array(
                "member_id" => 9,
                "goods_id" => 1,
                "spec_name" => "黄色"
            ),
            array(
                "member_id" => 9,
                "goods_id" => 1,
                "spec_name" => "红色"
            )
        );
        return $CartAction;
    }
}
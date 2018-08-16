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
use app\seller\logic\OrderLogic;
use GPBMetadata\Trade\SellerOrder;
use Symfony\Component\Yaml\Dumper;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Log;
use Psp;
use Grpc;
class Order extends Base {
    public  $order_status;
    public  $pay_status;
    public  $shipping_status;
    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = C('ORDER_STATUS');
        $this->pay_status = C('PAY_STATUS');
        $this->shipping_status = C('SHIPPING_STATUS');
        $this->pay_type =array('1'=>"PC端支付宝",
            '2'=>"app端支付宝",
            '3'=>"手机网站支付宝",
            '4'=>"微信支付",
            '5'=>"银联在线支付",
            '6'=>"银联app支付",
            '9'=>"钱包支付",
            '10'=>"买呗支付");
        // 订单 支付 发货状态
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
        $this->assign('payType',$this->pay_type);
    }

    /*
     *订单首页
     */
    public function index(){
    	$begin = date('Y-m-d',strtotime("-1 year"));//30天前
    	$end = date('Y/m/d',strtotime('+1 days')); 	
    	$this->assign('timegap',$begin.'-'.$end);
        return $this->fetch();
    }

    public function ajaxindex(){

        $add_time_begin=strtotime(I('post.time_begin'));//下单开始时间
        $add_time_end=strtotime(I('post.time_end'));//下单结束时间

        $client=GRPC('SellerOrder');
        $p = I('p/d',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_date");
        $page->setIndex($p);
        $page->setLimit(20);
        $searchinfo = new Psp\Trade\SearchInfo();
        $searchinfo->setProviderId(STORE_ID);
        if (I('shipping_status')) {
            $searchinfo->setShippingStatus(I('shipping_status'));
        }
        if (I('order_status')) {
            $searchinfo->setOrderStatus(I('order_status'));
        }
        if (I('pay_code')) {
            $searchinfo->setPayType(I('pay_code'));
        }
        if (I('pay_status')) {
            $searchinfo->setPayStatus(I('pay_status'));
        }

        if(I('post.keytype')=='consignee'){
            $searchinfo->setConsignee(I('post.order_sn'));//传入收货人
        }elseif(I('post.keytype')=='order_sn'){
            $searchinfo->setOrderSn(I('post.order_sn'));//传入订单编号
        }

        I('time')&&$searchinfo->setTime(I('time'));
        $add_time_begin&& $searchinfo->setStartTime(grpcTime($add_time_begin));
        $add_time_end&& $searchinfo->setEndTime(grpcTime($add_time_end));

        $searchinfo->setPagination($page);

        list($res,$status) = $client->GetSellerOrderList($searchinfo)->wait();

        foreach ($res->getOrderLis() as $k=>$v) {
            $orderList[$k]['order_id'] = $v->getOrderId();//订单id
            $orderList[$k]['order_sn'] = $v->getOrderSn();//订单编号
            $orderList[$k]['consignee'] = $v->getConsignee();//收货人
            $orderList[$k]['add_time'] = $v->getCreateTime()->getSecondS();//订单创建时间
            $orderList[$k]['pay_status'] = $v->getPayStatus();//支付状态
            $orderList[$k]['shipping_status'] = $v->getShippingStatus();//发货状态
            $orderList[$k]['order_status'] = $v->getOrderStatus();//订单状态
            $orderList[$k]['pay_type'] = $v->getPayType();//支付方式
            $orderList[$k]['shipping_name'] = $v->getShippingName();//配送方式
            $orderList[$k]['order_amount'] = round($v->getOrderAmount(),2);//订单总价
            $orderList[$k]['pay_money'] = round($v->getPayMoney(),2);//应付金额
            $orderList[$k]['138_id'] = $v->getTp138UserId();//138_id
            $orderList[$k]['store_name'] = $v->getStoreName();//店铺名称
            $orderList[$k]['wh_id'] = $v->getWalhaoId();//店铺名称

            $orderList[$k]['type'] = $v->getType();//订单类型
            $orderList[$k]['mobile'] = $v->getMobile();//收货人联系方式
        }
        $total_count = $res->getPaginationResult()->getTotalRecords();
        $Page = new AjaxPage($total_count,20);
        $show = $Page->show();
        $this->assign('total_count',$total_count);
        $this->assign('page',$show);
        $this->assign('orderList',$orderList);
        return $this->fetch();
    }

    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery(){
//    	$orderLogic = new OrderLogic();
//    	$condition = array();
//    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
//    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
//    	$shipping_status = I('shipping_status');
//    	$condition['shipping_status'] = empty($shipping_status) ? array('neq',1) : $shipping_status;
//        $condition['order_status'] = array('in','1,2,4');
//    	$count = M('order')->where($condition)->count();
//    	$Page  = new AjaxPage($count,10);
//    	//搜索条件下 分页赋值
//    	foreach($condition as $key=>$val) {
//            if(!is_array($val)){
//                $Page->parameter[$key]   =   urlencode($val);
//            }
//    	}
//    	$show = $Page->show();
//    	$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
//    	$this->assign('orderList',$orderList);
//    	$this->assign('page',$show);// 赋值分页输出
//    	$this->assign('pager',$Page);
//    	return $this->fetch();
    }
    
    public function refund_order_list(){
//    	$orderLogic = new OrderLogic();
//    	$condition = array();
//    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
//    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
//    	$condition['shipping_status'] = 0;
//    	$condition['order_status'] = 3;
//    	$condition['pay_status'] = array('gt',0);
//    	$count = M('order')->where($condition)->count();
//    	$Page  = new Page($count,20);
    	//搜索条件下 分页赋值
//    	foreach($condition as $key=>$val) {
//    		if(!is_array($val)){
//    			$Page->parameter[$key]   =   urlencode($val);
//    		}
//    	}
//    	$show = $Page->show();
//    	$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
//    	$this->assign('orderList',$orderList);
//    	$this->assign('page',$show);// 赋值分页输出
//    	$this->assign('pager',$Page);
    	return $this->fetch();
    }
    
    public function refund_order_info($order_id){
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	$this->assign('order',$order);
    	$this->assign('orderGoods',$orderGoods);
    	return $this->fetch();
    }
    
    public function refund_order(){
    	$data = I('post.');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($data['order_id']);
    	if(!order){
    		$this->error('订单不存在或参数错误');
    	}
    	//退到用户余额
    	if($data['pay_status'] == 3 && $data['refund_type']== 1){
    		accountLog($order['user_id'],$order['order_amount'],$order['integral'],'用户申请商品退款',0,$order['order_id'],$order['order_sn']);
//    		M('order')->where(array('order_id'=>$order['order_id']))->save($data);
    		$this->success('退款成功');
    	}
    	if($data['pay_status'] == 3 && $data['refund_type']== 0){
    		//支付原路退回
    		if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){
    			$return_money = $order['order_amount'];
    			if($order['pay_code'] == 'weixin'){
    				include_once  PLUGIN_PATH."payment/weixin/weixin.class.php";
    				$payment_obj =  new \weixin();
    				$data = array('transaction_id'=>$order['transaction_id'],'total_fee'=>$order['order_amount'],'refund_fee'=>$return_money);
    				$result = $payment_obj->payment_refund($data);
    				if($result['return_code'] == 'SUCCESS'){
    					//使用积分或者余额抵扣部分一一退还
    					if($order['user_money']>0 || $order['integral']>0){
    						accountLog($order['user_id'],$order['user_money'],$order['integral'],'用户申请订单退款',0,$order['order_id'],$order['order_sn']);
    					}
//    					M('order')->where(array('order_id'=>$order['order_id']))->save($data);
    					$this->success('退款成功');
    				}else{
    					$this->error($result['return_msg']);
    				}
    			}else{
    				include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
    				$payment_obj = new \alipay();
    				$detail_data = $order['transaction_id'].'^'.$return_money.'^'.'用户申请订单退款';
    				$data = array('batch_no'=>date('YmdHi').$order['order_id'],'batch_num'=>1,'detail_data'=>$detail_data);
    				$payment_obj->payment_refund($data);
    				//使用积分或者余额抵扣部分一一退还
    				if($order['user_money']>0 || $order['integral']>0){
    					accountLog($order['user_id'],$order['user_money'],$order['integral'],'用户申请订单退款',0,$order['order_id'],$order['order_sn']);
    				}
    				$this->success('操作成功');
    			}
    		}else{
    			$this->error('该订单支付方式不支持在线退回');
    		}
    	}else{
//    		M('order')->where(array('order_id'=>$order['order_id']))->save($data);
    		$this->success('拒绝退款操作成功');
    	}   	
    }
    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id){
        $client=GRPC('SellerOrder');
        $orderId = new Psp\Trade\OrderId();
        $orderId->setOrderId($order_id);
        list($res,$status) = $client->OrderDetails($orderId)->wait();
        $GetOrderInfo['order_id'] = $res->getOrderId();
        $GetOrderInfo['member_id'] = $res->getMemberId();
        $GetOrderInfo['state'] = $res->getState();
        $GetOrderInfo['type'] = $res->getType();
        $GetOrderInfo['platform_id'] = $res->getPlatformId();
        $GetOrderInfo['money'] = round($res->getMoney(),2);
        $GetOrderInfo['currency'] = $res->getCurrency();
        $GetOrderInfo['order_date'] = $res->getOrderDate()->getSecondS();
        $GetOrderInfo['order_sn'] = $res->getOrderSn();
        $GetOrderInfo['shipping_price'] = round($res->getShippingPrice(),2);
        $GetOrderInfo['pay_status'] = $res->getPayStatus();
        $GetOrderInfo['delivery_status'] = $res->getDeliveryStatus();
        $GetOrderInfo['receiver'] = $res->getReceiver();
        $GetOrderInfo['receiver_address'] = $res->getReceiverAddress();
        $GetOrderInfo['receiver_phone'] = $res->getReceiverPhone();
        $GetOrderInfo['buyer_message'] = $res->getBuyerMessage();
        $GetOrderInfo['pay_date'] = $res->getPayDate()?$res->getPayDate()->getSecondS():0;
        $GetOrderInfo['location_id'] = $res->getLocationId();
        $areamap= new \area\area();
        $GetOrderInfo['address_info'] =$areamap->getAddrstr($GetOrderInfo['location_id']);
        $GetOrderInfo['wh_id'] = $res->getWhId();
        $GetOrderInfo['pay_type'] = $res->getPayType();
        //物流信息
        if($res->getLogictics()){
            $GetOrderInfo['delivery_sn'] = $res->getLogictics()->getDeliverySn();
            $GetOrderInfo['deliverer'] = $res->getLogictics()->getDeliverer();
            $GetOrderInfo['shipping_date'] = $res->getLogictics()->getShippingDate()?$res->getLogictics()->getShippingDate()->getSecondS():0;
        }


        foreach ($res->getItems() as $k=>$v) {
            $orderGoods[$k]['name'] = $v->getName();
            $orderGoods[$k]['item_id'] = $v->getItemId();
            $orderGoods[$k]['provider_id'] = $v->getProviderId();
            $orderGoods[$k]['sku_id'] = $v->getSkuId();
            $orderGoods[$k]['sku_name'] = $v->getSkuName();
            $orderGoods[$k]['price'] = $v->getPrice();
            $orderGoods[$k]['cost'] = $v->getCost();
            $orderGoods[$k]['currency'] = $v->getCurrencey();
            $orderGoods[$k]['amount'] = $v->getAmount();
            $orderGoods[$k]['sku_unit'] = $v->getSkuUnit();
            $orderGoods[$k]['thumb_image_url'] = $v->getThumbImageUrl();
            $orderGoods[$k]['return'] = $v->getReturn(); //标记该商品是否存在退换货
            if($orderGoods[$k]['return']!=1)$return_goods[]=$orderGoods[$k];
        }
        //获取订单备注
        $order = Trade(OrdId);
        $order->setOrderId($order_id);
        list($res,$status) = GRPC(Trade)->GetNote($order)->wait();
        if($res){
            foreach ($res->getNoteList() as $k=>$v){
                $note[$k]['note'] = $v->getNote();
                $note[$k]['user_name'] = $v->getUserName();
                $note[$k]['add_time'] = $v->getAddTime()->getSeconds();
            }
            $this->assign('note',$note);
        }
        //$this->assign('admins',$admins);
        $this->assign('return_goods', $return_goods);
        $this->assign('order',$GetOrderInfo);
        //$this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        // $split = count($orderGoods) >1 ? 1 : 0;
        // foreach ($orderGoods as $val){
        // 	if($val['goods_num']>1){
        // 		$split = 1;
        // 	}
        // }
        // $this->assign('split',$split);
        // $this->assign('button',$button);
        return $this->fetch();
    }

    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function edit_order(){
        $order_id = I('order_id');

        //$orderLogic = new OrderLogic();
        //$order = $orderLogic->getOrderInfo($order_id);
        //dump($orderinfo);die;
        /*if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id);*/
        if(IS_POST)
        { $client=GRPC('Trade');
            /*$order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');
            $order['pay_code'] = I('payment');// 支付方式
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');
            $goods_id_arr = I("goods_id/a");
            $new_goods = $old_goods_arr = array();*/
            if (I('post.order_id')){//改
                $info = new Psp\Trade\Info();
                $info->setOrderId($order_id);
                $info->setDeliveryAmount(I('vo'));
                $info->setReceiver(I('consignee'));
                $info->setReceiverPhone(I('mobile'));
                $info->setAddress(I('address'));
                $info->setReceiverLocation(2);
                list($res,$status) = $client->UpdateOrderInfo($info)->wait();
                if ($res->getValue()){
                    $this->success("操作成功",U('Seller/Order/index'));
                } else {
                    $this->error('操作失败');
                }
            }
            //################################订单添加商品
            /*if($goods_id_arr){
                $new_goods = $orderLogic->get_spec_goods($goods_id_arr);
                foreach($new_goods as $key => $val) {
                    $val['order_id'] = $order_id;
                    $rec_id = M('order_goods')->add($val);//订单添加商品
                    if (!$rec_id)
                        $this->error('添加失败');
                }*/
        } else {
            $client=GRPC('SellerOrder');
            $orderId = new Psp\Trade\OrderId();
            $orderId->setOrderId($order_id);
            list($res,$status) = $client->GetOrderInfo($orderId)->wait();
            $order['order_id'] = $res->getOrderId();
            $order['receiver'] = $res->getReceiver();
            $order['receiver_phone'] = $res->getReceiverPhone();
            $order['receiver_address'] = $res->getReceiverAddress();
            $order['shipping_id'] = $res->getShippingId();
            $order['shipping_name'] = $res->getShippingName();
            $pay['pay_code'] = $res->getPayCode();
            $pay['type'] = $res->getPayType();
            $order['invoice_title'] = $res->getInvoiceTitle();
            $order['remark'] = $res->getRemark();
            foreach ($res->getOrderItemList() as $k=>$v) {
                $item[$k]['title'] = $v->getTitle();
                $item[$k]['sku_id'] = $v->getSkuId();
                $item[$k]['sku_name'] = $v->getSkuName();
                $item[$k]['shop_price'] = $v->getShopPrice();
                $item[$k]['amount'] = $v->getAmount();
            }
            $this->assign('order',$order);
            $this->assign('item',$item);
            $this->assign('pay',$pay);
            return $this->fetch();
        }

        //################################订单修改删除商品
        //$old_goods = I('old_goods/a');
        /* foreach ($orderGoods as $val){
             if(empty($old_goods[$val['rec_id']])){
                 M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
             }else{
                 //修改商品数量
                 if($old_goods[$val['rec_id']] != $val['goods_num']){
                     $val['goods_num'] = $old_goods[$val['rec_id']];
                     M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
                 }
                 $old_goods_arr[] = $val;
             }
         }*/

        //$goodsArr = array_merge($old_goods_arr,$new_goods);
        //$result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
        /* if($result['status'] < 0)
         {
             $this->error($result['msg']);
         }*/

        //################################修改订单费用
        /*$order['goods_price']    = $result['result']['goods_price']; // 商品总价
        $order['shipping_price'] = $result['result']['shipping_price'];//物流费
        $order['order_amount']   = $result['result']['order_amount']; // 应付金额
        $order['total_amount']   = $result['result']['total_amount']; // 订单总价
        $o = M('order')->where('order_id='.$order_id)->save($order);

        $l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
        if($o && $l){
            $this->success('修改成功',U('Admin/Order/editprice',array('order_id'=>$order_id)));
        }else{
            $this->success('修改失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
        }
        exit;
    }
    // 获取省份
    $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
    //获取订单城市
    $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
    //获取订单地区
    $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
    //获取支付方式
    $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
    //获取配送方式
    $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
*/
        //$this->assign('order',$order);
        /*$this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);*/
        // return $this->fetch();
    }

    /*
     * 拆分订单
     */
    public function split_order(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	if($order['shipping_status'] != 0){
    		$this->error('已发货订单不允许编辑');
    		exit;
    	}
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	if(IS_POST){
    		$data = I('post.');
    		//################################先处理原单剩余商品和原订单信息
    		$old_goods = I('old_goods/a');

    		foreach ($orderGoods as $val){
    			if(empty($old_goods[$val['rec_id']])){
//    				M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
    			}else{
    				//修改商品数量
    				if($old_goods[$val['rec_id']] != $val['goods_num']){
    					$val['goods_num'] = $old_goods[$val['rec_id']];
//    					M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
    				}
    				$oldArr[] = $val;//剩余商品
    			}
    			$all_goods[$val['rec_id']] = $val;//所有商品信息
    		}
    		$result = calculate_price($order['user_id'],$oldArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    		if($result['status'] < 0)
    		{
    			$this->error($result['msg']);
    		}
    		//修改订单费用
    		$res['goods_price']    = $result['result']['goods_price']; // 商品总价
    		$res['order_amount']   = $result['result']['order_amount']; // 应付金额
    		$res['total_amount']   = $result['result']['total_amount']; // 订单总价
//    		M('order')->where("order_id=".$order_id)->save($res);
			//################################原单处理结束

    		//################################新单处理
    		for($i=1;$i<20;$i++){
                $temp = $this->request->param($i.'_old_goods/a');
    			if(!empty($temp)){
    				$split_goods[] = $temp;
    			}
    		}

    		foreach ($split_goods as $key=>$vrr){
    			foreach ($vrr as $k=>$v){
    				$all_goods[$k]['goods_num'] = $v;
    				$brr[$key][] = $all_goods[$k];
    			}
    		}

    		foreach($brr as $goods){
    			$result = calculate_price($order['user_id'],$goods,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    			if($result['status'] < 0)
    			{
    				$this->error($result['msg']);
    			}
    			$new_order = $order;
    			$new_order['order_sn'] = date('YmdHis').mt_rand(1000,9999);
    			$new_order['parent_sn'] = $order['order_sn'];
    			//修改订单费用
    			$new_order['goods_price']    = $result['result']['goods_price']; // 商品总价
    			$new_order['order_amount']   = $result['result']['order_amount']; // 应付金额
    			$new_order['total_amount']   = $result['result']['total_amount']; // 订单总价
    			$new_order['add_time'] = time();
    			unset($new_order['order_id']);
//    			$new_order_id = DB::name('order')->insertGetId($new_order);//插入订单表
//    			foreach ($goods as $vv){
//    				$vv['order_id'] = $new_order_id;
//    				unset($vv['rec_id']);
////    				$nid = M('order_goods')->add($vv);//插入订单商品表
//    			}
    		}
    		//################################新单处理结束
    		$this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            exit;
    	}

    	foreach ($orderGoods as $val){
    		$brr[$val['rec_id']] = array('goods_num'=>$val['goods_num'],'goods_name'=>getSubstr($val['goods_name'], 0, 35).$val['spec_key_name']);
    	}
    	$this->assign('order',$order);
    	$this->assign('goods_num_arr',json_encode($brr));
    	$this->assign('orderGoods',$orderGoods);
    	return $this->fetch();
    }

    /*
     * 价钱修改
     */
    public function editprice($order_id){

        if(IS_POST){
            $order_id = I('order_id/d');
            $shipping_price =I('shipping_price');
            $use = Trade(Info);
            $use->setOrderId($order_id);
            $use->setDeliveryAmount($shipping_price);
            list($res,$status) = GRPC(Trade)->UpdateOrderInfo($use)->wait();
            $row = $res->getValue();
//        	$admin_id = session('admin_id');
//            if(empty($admin_id)){
//                $this->error('非法操作');
//                exit;
//            }
//            $update['discount'] = I('post.discount');
//            $update['shipping_price'] = I('post.shipping_price');
//			$update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
//            $row = M('order')->where(array('order_id'=>$order_id))->save($update);
            if(!$row){
                $this->success('没有更新数据',U('Seller/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',U('Seller/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }else{
            $orderLogic = new OrderLogic();
            $order = $orderLogic->getOrderInfo($order_id);
            $this->editable($order);
            $this->assign('order',$order);
            return $this->fetch();
        }

    }

    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order($order_id){
    	$orderLogic = new OrderLogic();
    	$del = $orderLogic->delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
        	$this->error('订单删除失败');
        }
    }

    /**
     * 订单取消付款
     */
    public function pay_cancel($order_id){
    	if(I('remark')){
    		$data = I('post.');
    		$note = array('退款到用户余额','已通过其他方式退款','不处理，误操作项');
    		if($data['refundType'] == 0 && $data['amount']>0){
    			accountLog($data['user_id'], $data['amount'], 0,  '退款到用户余额');
    		}
    		$orderLogic = new OrderLogic();
            $orderLogic->orderProcessHandle($data['order_id'],'pay_cancel');
    		$d = $orderLogic->orderActionLog($data['order_id'],'pay_cancel',$data['remark'].':'.$note[$data['refundType']]);
    		if($d){
    			exit("<script>window.parent.pay_callback(1);</script>");
    		}else{
    			exit("<script>window.parent.pay_callback(0);</script>");
    		}
    	}else{
//    		$order = M('order')->where("order_id=$order_id")->find();
//    		$this->assign('order',$order);
    		return $this->fetch();
    	}
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print(){
        $client=GRPC('SellerOrder');
        $orderId = new Psp\Trade\OrderId();
        $orderId->setOrderId(I('order_id/d'));
        list($res,$status) = $client->OrderDetails($orderId)->wait();
        $GetOrderInfo['order_id'] = $res->getOrderId();
        $GetOrderInfo['member_id'] = $res->getMemberId();
        $GetOrderInfo['state'] = $res->getState();
        $GetOrderInfo['type'] = $res->getType();
        $GetOrderInfo['platform_id'] = $res->getPlatformId();
        $GetOrderInfo['money'] = round($res->getMoney(),2);
        $GetOrderInfo['currency'] = $res->getCurrency();
        $GetOrderInfo['order_date'] = $res->getOrderDate()->getSecondS();
        $GetOrderInfo['order_sn'] = $res->getOrderSn();
        $GetOrderInfo['shipping_price'] = round($res->getShippingPrice(),2);
        $GetOrderInfo['pay_status'] = $res->getPayStatus();
        $GetOrderInfo['delivery_status'] = $res->getDeliveryStatus();
        $GetOrderInfo['receiver'] = $res->getReceiver();
        $GetOrderInfo['receiver_address'] = $res->getReceiverAddress();
        $GetOrderInfo['receiver_phone'] = $res->getReceiverPhone();
        $GetOrderInfo['buyer_message'] = $res->getBuyerMessage();
        $GetOrderInfo['pay_date'] = $res->getPayDate()?$res->getPayDate()->getSecondS():0;
        $GetOrderInfo['location_id'] = $res->getLocationId();
        $areamap= new \area\area();
        $GetOrderInfo['address_info'] =$areamap->getAddrstr($GetOrderInfo['location_id']);
        $GetOrderInfo['pay_type'] = $res->getPayType();
        foreach ($res->getItems() as $k=>$v) {
            $orderGoods[$k]['name'] = $v->getName();
            $orderGoods[$k]['item_id'] = $v->getItemId();
            $orderGoods[$k]['provider_id'] = $v->getProviderId();
            $orderGoods[$k]['sku_id'] = $v->getSkuId();
            $orderGoods[$k]['sku_name'] = $v->getSkuName();
            $orderGoods[$k]['price'] = $v->getPrice();
            $orderGoods[$k]['cost'] = $v->getCost();
            $orderGoods[$k]['currency'] = $v->getCurrencey();
            $orderGoods[$k]['amount'] = $v->getAmount();
            $orderGoods[$k]['sku_unit'] = $v->getSkuUnit();
            $orderGoods[$k]['thumb_image_url'] = $v->getThumbImageUrl();
        }
//        $client=GRPC('SellerOrder');
//        $orderId = new Psp\Trade\OrderId();
//        $orderId->setOrderId(I('order_id'));
//        list($res,$status) = $client->OrderPrint($orderId)->wait();
//        $order['receiver'] = $res->getReceiver();
//        $order['receiver_address'] = $res->getReceiverAddress();
//        $order['receiver_phone'] = $res->getReceiverPhone();
//        $order['order_sn'] = $res->getOrderSn();
//        $order['create_time'] = $res->getCreateTime()->getSecondS();
//        $order['order_amount'] = $res->getOrderAmount();
//        $order['total_amount'] = $res->getTotalAmount();
//        $order['pay_type'] = $res->getPayType();
//        $order['delivery_status'] = $res->getDeliveryStatus();
//        foreach ($res->getOrderInformations() as $k=>$v) {
//            $orderGoods[$k]['goods_name'] = $v->getGoodsName();
//            $orderGoods[$k]['amount'] = $v->getAmount();
//            $orderGoods[$k]['price'] = $v->getPrice();
//            $orderGoods[$k]['sku_id'] = $v->getSkuId();
//        }
        $payType=array('1'=>"PC端支付宝",
            '2'=>"app端支付宝",
            '3'=>"手机网站支付宝",
            '4'=>"微信支付",
            '5'=>"银联在线支付",
            '6'=>"银联app支付",
            '9'=>'钱包支付');
        $this->assign('payType',$payType);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('order',$GetOrderInfo);
        return $this->fetch('print');
    }

    /**
     * 快递单打印
     */
    public function shipping_print(){
        $order_id = I('get.order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        //查询是否存在订单及物流
//        $shipping = M('plugin')->where(array('code'=>$order['shipping_code'],'type'=>'shipping'))->find();
//        if(!$shipping){
//        	$this->error('物流插件不存在');
//        }
		if(empty($shipping['config_value'])){
			$this->error('请设置'.$shipping['name'].'打印模板');
		}
        $shop = tpCache('shop_info');//获取网站信息
        $shop['province'] = empty($shop['province']) ? '' : getRegionName($shop['province']);
        $shop['city'] = empty($shop['city']) ? '' : getRegionName($shop['city']);
        $shop['district'] = empty($shop['district']) ? '' : getRegionName($shop['district']);

        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        if(empty($shipping['config'])){
        	$config = array('width'=>840,'height'=>480,'offset_x'=>0,'offset_y'=>0);
        	$this->assign('config',$config);
        }else{
        	$this->assign('config',unserialize($shipping['config']));
        }
        $template_var = array("发货点-名称", "发货点-联系人", "发货点-电话", "发货点-省份", "发货点-城市",
        		 "发货点-区县", "发货点-手机", "发货点-详细地址", "收件人-姓名", "收件人-手机", "收件人-电话",
        		"收件人-省份", "收件人-城市", "收件人-区县", "收件人-邮编", "收件人-详细地址", "时间-年", "时间-月",
        		"时间-日","时间-当前日期","订单-订单号", "订单-备注","订单-配送费用");
        $content_var = array($shop['store_name'],$shop['contact'],$shop['phone'],$shop['province'],$shop['city'],
        	$shop['district'],$shop['phone'],$shop['address'],$order['consignee'],$order['mobile'],$order['phone'],
        	$order['province'],$order['city'],$order['district'],$order['zipcode'],$order['address'],date('Y'),date('M'),
        	date('d'),date('Y-m-d'),$order['order_sn'],$order['admin_note'],$order['shipping_price'],
        );
        $shipping['config_value'] = str_replace($template_var,$content_var, $shipping['config_value']);
        $this->assign('shipping',$shipping);
        return $this->fetch("Plugin/print_express");
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $order_id = I('order_id/d');

        $invoice_no = trim(I('invoice_no'));
        $remark = trim(I('note'));
        $client = GRPC(Trade);
        $delivery = Trade(DeliveryInfo);
        $delivery->setOrderId($order_id);
        $delivery->setDeliverySn($invoice_no);
        $delivery->setRemark($remark);

        list($res,$status) = $client->AddDeliveryInfo($delivery)->wait();

        $result = $res->getValue();

//        $orderLogic = new OrderLogic();
//		$data = I('post.');
//		$res = $orderLogic->deliveryHandle($data);
		if($result){
			$this->success('操作成功',U('Seller/Order/delivery_list'));
		}else{
			$this->error('操作失败',U('Seller/Order/delivery_list'));
		}
    }


    public function delivery_info(){
        $order_id = I('order_id/d');
        $client=GRPC('SellerOrder');
        $orderId = new Psp\Trade\OrderId();
        $orderId->setOrderId($order_id);
        list($res,$status) = $client->OrderDetails($orderId)->wait();
        $GetOrderInfo['order_id'] = $res->getOrderId();
        $GetOrderInfo['member_id'] = $res->getMemberId();
        $GetOrderInfo['state'] = $res->getState();
        $GetOrderInfo['type'] = $res->getType();
        $GetOrderInfo['platform_id'] = $res->getPlatformId();
        $GetOrderInfo['money'] = round($res->getMoney(),2);
        $GetOrderInfo['currency'] = $res->getCurrency();
        $GetOrderInfo['order_date'] = $res->getOrderDate()->getSecondS();
        $GetOrderInfo['order_sn'] = $res->getOrderSn();
        $GetOrderInfo['shipping_price'] = round($res->getShippingPrice(),2);
        $GetOrderInfo['pay_status'] = $res->getPayStatus();
        $GetOrderInfo['delivery_status'] = $res->getDeliveryStatus();
        $GetOrderInfo['receiver'] = $res->getReceiver();
        $GetOrderInfo['receiver_address'] = $res->getReceiverAddress();
        $GetOrderInfo['receiver_phone'] = $res->getReceiverPhone();
        $GetOrderInfo['buyer_message'] = $res->getBuyerMessage();
        $GetOrderInfo['pay_date'] = $res->getPayDate()?$res->getPayDate()->getSecondS():0;
        $GetOrderInfo['location_id'] = $res->getLocationId();
        $areamap= new \area\area();
        $GetOrderInfo['address_info'] =$areamap->getAddrstr($GetOrderInfo['location_id']);
        $GetOrderInfo['wh_id'] = $res->getWhId();
        $GetOrderInfo['pay_type'] = $res->getPayType();
        //物流信息
        if($res->getLogictics()){
            $GetOrderInfo['delivery_sn'] = $res->getLogictics()->getDeliverySn();
            $GetOrderInfo['deliverer'] = $res->getLogictics()->getDeliverer();
            $GetOrderInfo['shipping_date'] = $res->getLogictics()->getShippingDate()?$res->getLogictics()->getShippingDate()->getSecondS():0;
            $GetOrderInfo['remark'] = $res->getLogictics()->getRemark();
        }


        foreach ($res->getItems() as $k=>$v) {
            $orderGoods[$k]['name'] = $v->getName();
            $orderGoods[$k]['item_id'] = $v->getItemId();
            $orderGoods[$k]['provider_id'] = $v->getProviderId();
            $orderGoods[$k]['sku_id'] = $v->getSkuId();
            $orderGoods[$k]['sku_name'] = $v->getSkuName();
            $orderGoods[$k]['price'] = $v->getPrice();
            $orderGoods[$k]['cost'] = $v->getCost();
            $orderGoods[$k]['currency'] = $v->getCurrencey();
            $orderGoods[$k]['amount'] = $v->getAmount();
            $orderGoods[$k]['sku_unit'] = $v->getSkuUnit();
            $orderGoods[$k]['thumb_image_url'] = $v->getThumbImageUrl();
            $orderGoods[$k]['return'] = $v->getReturn(); //标记该商品是否存在退换货
            if($orderGoods[$k]['return']!=1)$return_goods[]=$orderGoods[$k];
        }

        $this->assign('order',$GetOrderInfo);
        $this->assign('order_id',$order_id);
        $this->assign('orderGoods',$orderGoods);

        return $this->fetch();
    }

    /**
     * 发货单列表
     */
    public function delivery_list()
    {
        $client=GRPC('Trade');

        $limit = 20;
        $p = I('p/d',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($limit);

        $delivery = new Psp\Trade\SearchDelivery();
        $delivery->setProviderId(STORE_ID); //用户id

        if(I('consignee')){

            $delivery->setReceiver(trim(I('consignee'))); // 收货人
        }

        if(I('order_sn')){

            $delivery->setOrderSn(trim(I('order_sn'))); // 订单编号
        }

        if(I('shipping_status')){

            $delivery->setDeliveryStatus(I('shipping_status')); // 发货状态
        }
        $delivery->setPagination($page); // 分页
        list($res,$status) = $client->GetDeliveryList($delivery)->wait();
        if($res){
            foreach ($res->getDeliveryList() as $k=>$v) {
                $orderList[$k]['order_id'] = $v->getOrderId();//订单id
                $orderList[$k]['order_sn'] = $v->getOrderSn();//订单编号
                $orderList[$k]['consignee'] = $v->getReceiver();//收货人
                $orderList[$k]['mobile'] = $v->getReceiverPhone();//收件人电话
                $orderList[$k]['add_time'] = $v->getOrderDate()?$v->getOrderDate()->getSecondS():'';//订单创建时间
                $orderList[$k]['pay_time'] = $v->getPayTime()?$v->getPayTime()->getSecondS():'';//支付时间
                $orderList[$k]['shipping_price'] = $v->getShippingPrice();//物流费用
                $orderList[$k]['shipping_name'] = $v->getDeliveryName();//物流名称
                $orderList[$k]['total_amount'] = $v->getOrderAmount();//订单总价
                $orderList[$k]['order_status'] = $v->getOrderStatus();//订单状态
                $orderList[$k]['shipping_status'] = $v->getShippingStatus();//物流状态
            }
            $total_count = $res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,$limit) ;
            $show = $Page->show();
            $this->assign('page',$show);
        }else{
            $orderList = '';
        }

        $this->assign('orderList',$orderList);
        return $this->fetch();
    }

    /*
     * ajax 退货订单列表
     */
    public function ajax_return_list(){
        // 搜索条件
        $order_sn = trim(I('order_sn'));

        //分页页码
        $p=I('p/d',1);

        $status =  I('status');

        $status==""?$status=0:$status;

        $client = GRPC('Trade');


        $co=3;

        $page = new Psp\Pagination();

        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($co);

        $store = Trade(StoreId);

        $store->setProviderId(STORE_ID);
        $store->setOrderSn($order_sn);
        $store->setPagination($page);

        $store->setReturningStatus($status);


        list($res,$status) = $client->GetReturnList($store)->wait();


        foreach ($res->getReturnList() as $k=>$v) {

            $data[$k]['order_id'] = $v->getOrderId();
            $data[$k]['order_sn'] = $v->getOrderSn();
            $data[$k]['title'] = $v->getTitle();
            $data[$k]['shop_name']= $v->getShopName();
            $data[$k]['type']=$v->getType();

            $data[$k]['add_time'] = $v->getOrderDate()->getSeconds();
            $data[$k]['returning_status'] = $v->getReturningStatus();

            $data[$k]['parent_id'] = $v->getParentId();
            $data[$k]['parent_sn'] = $v->getParentSn();

        }


        //获得总条数
        $count=$res->getPaginationResult()->getTotalRecords();

        $Page  = new AjaxPage($count,$co);
        $show = $Page->show();
        $state = C('REFUND_STATUS');
        $this->assign('state',$state);
        //$this->assign('goods_list',$goods_list);

//        // 搜索条件
//        $order_sn =  trim(I('order_sn'));
//        $order_by = I('order_by') ? I('order_by') : 'id';
//        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
//        $status =  I('status');
//
//
//        $where = " 1 = 1 ";
//        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
//        empty($order_sn)&& !empty($status) && $where.= " and status = '$status' ";
//        $count = M('return_goods')->where($where)->count();
//        $Page  = new AjaxPage($count,13);
//        $show = $Page->show();
//        //$list = M('return_goods')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();
//
//        $list = array(
//            array(
//                'order_id' =>30,
//                'order_sn'=>'201704111622453006',
//                'addtime'=>'1499166521',
//                'status'=>1,
//                'typy'=>0,
//                'goods_name'=>'Haier/海尔 BCD-160TMPQ 160升家用节能两门电冰箱双门 冷藏冷冻',
//                'shop_name' =>'店铺名称'
//            ),
//             array(
//                 'order_id' =>22,
//                 'order_sn'=>'201703241708017153',
//                 'addtime'=>'1499223652',
//                 'status'=>0,
//                 'typy'=>1,
//                 'goods_name'=>'小米手机5,十余项黑科技，很轻狠快',
//                 'shop_name' =>'店铺名称'
//             )
//        );
//
////        $goods_id_arr = get_arr_column($list, 'goods_id');
////        if(!empty($goods_id_arr)){
////            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
////        }
//
//        $state = C('REFUND_STATUS');
//        $this->assign('state',$state);
//        //$this->assign('goods_list',$goods_list);
        $this->assign('list',$data);
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 删除某个退换货申请
     */
    public function return_del(){
        $id = I('get.id ');
        $client=GRPC('AdminOrder');
        $user = new Psp\Trade\AdminOrderId();

        $user->setOrderId($id);


        //        $user->setTp138UserId('2');//传入138ID
//        $user->setCreateTime('2');//传入下单日期

        //$user->setReturningStatus();setConsignee
        list($res,$status) = $client->DelReturn($user)->wait();

        $res->getValue();


        //M('return_goods')->where("id = $id")->delete();
        $this->success('成功删除!');

    }

    /**
     * 退换货操作
     */
    public function return_info()
    {
        $order_id = I('id/d');
        $client = GRPC(Trade);
        $return = Trade(OrdId);
        $return->setOrderId($order_id);
        list($res,$status) = $client->GetReturnDetailInfo($return)->wait();
        $return_info['order_id'] = $res->getOrderId();
        $return_info['order_sn'] = $res->getOrderSn();
        $return_info['type'] = $res->getType();
        $return_info['user_name'] = $res->getUserName();
        $return_info['return_money'] = $res->getReturnMoney();
        $return_info['title'] = $res->getTitle();
        $return_info['reason'] = $res->getReason();
        $return_info['remark'] = $res->getRemark();
        $return_info['returning_status'] = $res->getReturningStatus();
        $return_info['shipping_name'] = $res->getShippingName();
        $return_info['shipping_no'] = $res->getShippingNo();
        $return_info['shipping_time'] = $res->getShippingTime()?$res->getShippingTime()->getSeconds():0;
        $return_info['add_time'] = $res->getOrderDate()?$res->getOrderDate()->getSeconds():0;

        if(IS_POST)
        {
            $order_id = I('id/d');
            $returning_status = I('status/d');
            $remark = I('remark');
            //$client = GRPC(Trade);
            $return = Trade(ReturnInfo);
            $return->setOrderId($order_id);
            $return->setReturningStatus($returning_status);
            $remark&&$return->setRemark($remark);
            list($res,$status) = $client->ReturnAction($return)->wait();
            $value = $res->getValue();
            if($value)
                $this->success('修改成功!');
            exit;
//            $data = I('post.');
//            if($return_goods['type'] == 2 && $return_goods['is_receive'] == 1){
//            	$data['seller_delivery']['express_time'] = date('Y-m-d H:i:s');
//            	$data['seller_delivery'] = serialize($data['seller_delivery']); //换货的物流信息
//            }
//            $note ="退换货:{$type_msg[$return_goods['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
//            $result = M('return_goods')->where("id= $return_id")->save($data);
//            if($result && $data['status']==1)
//            {
//                $orderLogic = new OrderLogic();
//                //审核通过才更改订单商品状态，进行退货，退款时要改对应商品修改库存
//                $type = ($return_goods['type']<2) ? 3 : 2;
//                M('order_goods')->where(['order_id' =>$return_goods['order_id'],'goods_id'=>$return_goods['goods_id']])
//                        ->save(array('is_send' => $type));//更改商品状态
//                $orderLogic->alterReturnGoodsInventory($return_goods['order_id'],$return_goods['goods_id']); //审核通过，恢复原来库存
//            }
//            $log = $orderLogic->orderActionLog($return_goods['order_id'],'refund',$note);
//            $this->success('修改成功!');
//            exit;
        }
//        $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);  //订单的物流信息，服务类型为换货会显示
//        if($return_goods['imgs']) $return_goods['imgs'] = explode(',', $return_goods['imgs']);
//        $this->assign('id',$return_id); // 用户
//        $this->assign('user',$user); // 用户
//        $this->assign('goods',$goods);// 商品
//        $this->assign('return_goods',$return_goods);// 退换货
//        $order = M('order')->where(array('order_id'=>$return_goods['order_id']))->find();
        $this->assign('order',$return_info);//退货订单信息
        return $this->fetch();
    }

    /**
     * 账户资金调节
     */
    public function account_edit(){
        if(IS_POST){ //退款到用户余额 表示退货处理完成   修改账户金额  修改订单状态  库存添加

            $order_id = I('id/d');
            $money_type = I('money_act_type/d');
            $money = I('user_money');
            $client = GRPC(Trade);
            $return = Trade(Returns);
            $return->setOrderId($order_id);
            $money&&$return->setMoney($money);
            $return->setMoneyType($money_type);
//            $seller_id&&$return->setSellerId($seller_id);
            list($res,$status) = $client->UpdateReturnMoney($return)->wait();
            $result = $res->getValue();
            if(!$result){
                $this->error("操作失败,商家余额不足");
            }else{
                $this->success("操作成功",U("Seller/Order/return_info",array('id'=>$order_id)));
            }
            exit;

        }
        $this->assign('money',I('m'));
        return $this->fetch();
    }

    public function refund_back(){
    	$return_id = I('id');
//        $return_goods = M('return_goods')->where("id= $return_id")->find();
//    	$rec_goods = M('order_goods')->where(array('order_id'=>$return_goods['order_id'],'goods_id'=>$return_goods['goods_id']))->find();
//    	$order = M('order')->where(array('order_id'=>$rec_goods['order_id']))->find();
//    	if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){
//    		$return_money = $return_goods['refund_money'];
//    		if($order['pay_code'] == 'weixin'){
//    			include_once  PLUGIN_PATH."payment/weixin/weixin.class.php";
//    			$payment_obj =  new \weixin();
//    			$data = array('transaction_id'=>$order['transaction_id'],'total_fee'=>$order['order_amount'],'refund_fee'=>$return_money);
//    			$result = $payment_obj->payment_refund($data);
//    			if($result['return_code'] == 'SUCCESS'){
//    				M('order_goods')->where(array('rec_id'=>$rec_goods['rec_id']))->save(array('is_send'=>3));
//    				$updata = array('refund_type'=>2,'refundtime'=>time(),'status'=>3);
//    				M('return_goods')->where("id= $return_id")->save($updata);
//    				//使用积分或者余额抵扣部分一一退还
//    				if($return_goods['refund_deposit']>0 || $return_goods['refund_integral']>0){
//    					accountLog($return_goods['user_id'],$return_goods['refund_deposit'],$return_goods['refund_integral'],'用户申请商品退款',0,$return_goods['order_id'],$return_goods['order_sn']);
//    				}
    				//若该商品有赠送积分则追回
//    				$order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
//    				if($order_goods['give_integral']>0){
//    					accountLog($return_goods['user_id'],0,-$return_goods['refund_integral'],'退货积分追回',0,$return_goods['order_id'],$return_goods['order_sn']);
//    				}
//    				$this->success('退款成功');
//    			}else{
//    				$this->error($result['return_msg']);
//    			}
//    		}else{
//    			include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
//    			$payment_obj = new \alipay();
//    			$detail_data = $order['transaction_id'].'^'.$return_money.'^'.'用户申请订单退款';
//    			$data = array('batch_no'=>date('YmdHi').$rec_goods['rec_id'],'batch_num'=>1,'detail_data'=>$detail_data);
//    			$payment_obj->payment_refund($data);
//    			//使用积分或者余额抵扣部分一一退还
//    			if($return_goods['refund_deposit']>0 || $return_goods['refund_integral']>0){
//    				accountLog($return_goods['user_id'],$return_goods['refund_deposit'],$return_goods['refund_integral'],'用户申请商品退款',0,$return_goods['order_id'],$return_goods['order_sn']);
//    			}
//    			//若该商品有赠送积分则追回
//    			$order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
//    			if($order_goods['give_integral']>0){
//    				accountLog($return_goods['user_id'],0,-$return_goods['refund_integral'],'退货积分追回',0,$return_goods['order_id'],$return_goods['order_sn']);
//    			}
//    		}
//    	}else{
//    		$this->error('该订单支付方式不支持在线退回');
//    	}
    }

    /**
     * 管理员生成申请退货单
     */
    public function add_return_goods()
   {
            $order_id = I('order_id');
            $goods_id = I('goods_id');

//            $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id")->find();
            if(!empty($return_goods))
            {
                $this->error('已经提交过退货申请!',U('Admin/Order/return_list'));
                exit;
            }
//            $order = M('order')->where("order_id = $order_id")->find();

            $data['order_id'] = $order_id;
//            $data['order_sn'] = $order['order_sn'];
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
//            $data['user_id'] = $order[user_id];
            $data['remark'] = '管理员申请退换货'; // 问题描述
//            M('return_goods')->add($data);
            $this->success('申请成功,现在去处理退货',U('Admin/Order/return_list'));
            exit;
    }

    /**
     * 订单操作
     * @param $id
     */
    public function order_action(){
        $client = GRPC(Trade);
        $order_action = Trade(ActionInfo);
        $order_id = I('order_id/d');
        $action = I('type');
        $order_action->setOrderId($order_id);
        $order_action->setAction($action);
        list($res,$status) = $client->OrderAction($order_action)->wait();
        $result = $res->getValue();
        if(!$result){
            $this->error('操作失败',U('Seller/Order/detail',array('order_id'=>$order_id)));
            exit;
        }
        $this->success('操作成功',U('Seller/Order/detail',array('order_id'=>$order_id)));
        exit;
//        $orderLogic = new OrderLogic();
//        $action = I('get.type');
//        $order_id = I('get.order_id');
//        if($action && $order_id){
//            if($action !=='pay'){
//                $res = $orderLogic->orderActionLog($order_id,$action,I('note'));
//            }
//        	 $a = $orderLogic->orderProcessHandle($order_id,$action,array('note'=>I('note'),'admin_id'=>0));
//        	 if($res !== false && $a !== false){
//                 if ($action == 'remove') {
//                     exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => U('admin/order/index')))));
//                 }
//        	 	exit(json_encode(array('status' => 1,'msg' => '操作成功')));
//        	 }else{
//                 if ($action == 'remove') {
//                     exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => U('admin/order/index')))));
//                 }
//        	 	exit(json_encode(array('status' => 0,'msg' => '操作失败')));
//        	 }
//        }else{
//        	$this->error('参数错误',U('Admin/Order/detail',array('order_id'=>$order_id)));
//        }
    }
    
    public function order_log(){
    	$timegap = I('timegap');
    	if($timegap){
    		$gap = explode('-', $timegap);
    		$begin = strtotime($gap[0]);
    		$end = strtotime($gap[1]);
    	}else{
    	    //@new 兼容新模板
    	    $begin = strtotime(I('timegap_begin'));
    	    $end = strtotime(I('timegap_end'));
    	}
    	$condition = array();
//    	$log =  M('order_action');
    	if($begin && $end){
    		$condition['log_time'] = array('between',"$begin,$end");
    	}
    	$admin_id = I('admin_id');
		if($admin_id >0 ){
			$condition['action_user'] = $admin_id;
		}
//    	$count = $log->where($condition)->count();
//    	$Page = new Page($count,20);
    	foreach($condition as $key=>$val) {
//    		$Page->parameter[$key] = urlencode($val);
    	}
//    	$show = $Page->show();
//    	$list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $orderIds = [];
//        foreach ($list as $log) {
//            if (!$log['action_user']) {
//                $orderIds[] = $log['order_id'];
//            }
//        }
        if ($orderIds) {
//            $users = M("users")->alias('u')->join('__ORDER__ o', 'o.user_id = u.user_id')->getField('o.order_id,u.nickname');
        }
//        $this->assign('users',$users);
//    	$this->assign('list',$list);
//    	$this->assign('pager',$Page);
//    	$this->assign('page',$show);
//    	$admin = M('admin')->getField('admin_id,user_name');
//    	$this->assign('admin',$admin);
    	return $this->fetch();
    }

    /**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order){
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    //导出订单列表
    public function export_order()
    {
        set_time_limit(0);
        $s = microtime(true);
        $add_time_begin=strtotime(I('post.time_begin'));//下单开始时间
        $add_time_end=strtotime(I('post.time_end'));//下单结束时间
        $pay_status=I('post.pay_status');//支付状态

        $pay_type=I('post.pay_code');//支付方式
        //var_dump($add_time_begin);die;
        $shipping_status=I('post.shipping_status');//发货状态
        $order_status=I('post.order_status');//订单状态

        $keytype=I('post.keytype');//查询方式收货人或者订单编号

        $keywords=I('post.order_sn');//搜索内容

        $order_ids = I('post.order_ids');

        $client = GRPC('AdminOrder');

        $user = new Psp\Trade\ExportConditions();

        if($keytype=='consignee'){
            $user->setConsignee($keywords);//传入收货人
        }elseif($keytype=='order_sn'){
            $user->setOrderSn($keywords);//传入订单编号
        }

        $order_ids&&$user->setOrderIds($order_ids);
        $user->setTime('order_date');
        $add_time_begin&&$user->setStartTime(grpcTime($add_time_begin));
        $add_time_end&&$user->setEndTime(grpcTime($add_time_end));
        $pay_status&&$user->setPayStatus($pay_status);//传入支付状态
        $pay_type&&$user->setPayType($pay_type);//传入支付方式s
        $shipping_status&&$user->setShippingStatus($shipping_status);//传入发货状态
        $order_status&&$user->setOrderStatus($order_status);//传入订单状态

        $user->setProviderId(STORE_ID); //传入店铺id

        list($res,$status) = $client->ExportOrder($user)->wait();


        foreach ($res->getOrderList() as $k=>$v) {
            $data[$k]['order_sn'] = $v->getOrderSn();
            $data[$k]['create_time'] = $v->getCreateTime()->getSeconds();
            $data[$k]['consignee'] = $v->getConsignee();
            $data[$k]['receiver_phone'] = $v->getReceiverPhone();
            $data[$k]['order_amount'] = $v->getOrderAmount();
            $data[$k]['pay_money'] = $v->getPayMoney();
            $data[$k]['pay_type'] = $this->pay_type[$v->getPayType()];
            $data[$k]['pay_status'] = $this->pay_status[$v->getPayStatus()];
            $data[$k]['shipping_status'] = $this->shipping_status[$v->getShippingStatus()];

            $data[$k]['count'] = $v->getCount();
            $data[$k]['receiver_address'] = $v->getReceiverAddress();
            $data[$k]['location_id'] = $v->getLocationId();
            $areamap= new \area\area();
            $data[$k]['address_info'] =$areamap->getAddrstr($data[$k]['location_id']);
            $data[$k]['delivery_no'] = $v->getDeliveryNo();
            $data[$k]['store_name'] = $v->getStoreName();
            foreach ($v->getOrderItem() as $kk=>$vv){
                $data[$k]['order_item'][$kk]['name'] = $vv->getName();
                $data[$k]['order_item'][$kk]['item_id'] = $vv->getItemId();
                $data[$k]['order_item'][$kk]['sku_id'] = $vv->getSkuId();
                $data[$k]['order_item'][$kk]['sku_name'] = $vv->getSkuName();
                $data[$k]['order_item'][$kk]['price'] = $vv->getPrice(); //成本价
                $data[$k]['order_item'][$kk]['cost'] = $vv->getCost(); //单价
                $data[$k]['order_item'][$kk]['currencey'] = $vv->getCurrencey();
                $data[$k]['order_item'][$kk]['amount'] = $vv->getAmount();
            }

            if($v->getOrderNote()){
                foreach ($v->getOrderNote() as $k2=>$v2){
                    $data[$k]['order_note'][$k2]['note'] = $v2->getNote();
                    $data[$k]['order_note'][$k2]['add_time'] = $v2->getAddTime()->getSeconds();
                }
            }
        }
        $arraydata[] = ['订单号','日期','收货人','收货地址','电话','订单金额','实际支付','支付方式','支付状态','发货状态','商品信息','成本价','运单号','处理备注','店铺名称'];
        $k=1;
        foreach($data as $val){
            $arraydata[$k][] = "\t".$val['order_sn'];
            $arraydata[$k][] = date('Y/m/d',$val['create_time']);
            $arraydata[$k][] = $val['consignee'];
            $arraydata[$k][] = $val['address_info'].$val['receiver_address'];
            $arraydata[$k][] = $val['receiver_phone'];
            $arraydata[$k][] = $val['order_amount'];
            $arraydata[$k][] = $val['pay_money'];
            $arraydata[$k][] = $val['pay_type'];
            $arraydata[$k][] = $val['pay_status'];
            $arraydata[$k][] = $val['shipping_status'];
            $strGoods='';
            foreach($val['order_item'] as $goods){
                $strGoods .= "品名：".$goods['name']." 数量:".$goods['amount']."本店售价:".$goods['price']."  成本价:".$goods['cost'];
                if ($goods['sku_name'] != '') $strGoods .= " 规格：".$goods['sku_name'];
                $cost[]= $goods['amount'] *$goods['cost'];
            }
            $arraydata[$k][] = $strGoods;
            $arraydata[$k][] = array_sum($cost);
            unset($cost);
            $arraydata[$k][] = $val['delivery_no']; //快递单号
            $remarkstr = '';
            if($val['order_note']){
                foreach($val['order_note'] as $v){
                    $remarkstr .= date('Y-m-d',$v['add_time']).'-'.$v['note']."\n";
                }
            }

            $arraydata[$k][] = $remarkstr;//订单备注
            unset($remarkstr);
            //$arraydata[$k][] = " 备注";
            $arraydata[$k][] = $val['store_name'];
            $k++;
        }
        $e = microtime(true);
        $nm= $e-$s;
        create_xls($arraydata,$nm,1);
        exit();

    }


    /**
     * 上传表格,批量生成order_sn与快递单对应信息
     * @Authorhtl {Ning<nk11@qq.com>}
     * @DateTime  2017-02-27T11:06:37+0800
     */
    public function uploadxml(){
        // exit('为缓解压力暂停该功能~');
        $start = microtime(true);
        if (IS_POST) {
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('import');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->validate(['size'=>3145728,'ext'=>'xlsx,xls'])->move(ROOT_PATH . 'public' . DS .'upload'.DS .'uploadxls');
            if($info){
                // 成功上传后 获取上传信息
                $file_path = $info->getPathname();

                $exfn = import_excel($file_path);//以数组的形式打开excle表格,读取内容
                //dump(array_filter($exfn));die;
                $arr=array();
                foreach($exfn as $k => $v1){//验证上传文件合法性/生成可用数租对象

                    if(preg_match('/(201|202)[0-9]{15}/', trim($v1[0]),$order_sn) ) $arr[$k]['order_sn'] = $order_sn[0];//匹配订单号
                    //if ($this->getOrderstatus($arr[$k]['order_sn'])<1) $this->error('订单'.$v1[0].'状态有误','',3);//检测订单状态
                    if(preg_match("/\d+/",trim($v1[1]),$matches)){//匹配物流信息
                        $arr[$k]['inoice_no'] = $matches[0];
                    }else{
                        $this->error('表格类型不正确,请参考');
                    }
                }
                foreach ($arr as $k => $v){
                    $order[$k] = Trade(DeliverySn);
                    $order[$k]->setOrderSn($v['order_sn']);
                    $order[$k]->setInvoiceSn($v['inoice_no']);
                }
                $orde = Trade(DeliveryNo);
                $orde->setDeliverySn($order);
                $orde->setProviderId(STORE_ID);
                list($res,$status) = GRPC('SellerOrder')->SellerExportDelivery($orde)->wait();
                //dump($res->getResult());die;
                if($res->getResult() == 1){
                    $end = microtime(true);
                    $implementtime = $end-$start;
                    $admin = session('seller_id');
                    $store = session('store_id');
                    //file_put_contents('/home/time.txt',$file.'执行'.$implementtime."秒/操作id:$admin/商店:$store/date('YmdHis')\n",FILE_APPEND);
                    $this->success('批量处理成功','delivery_list',3);
                }elseif ($res->getResult() == 2){
                    $this->error('存在无效订单');
                }elseif ($res->getResult() == 3){
                    $this->error('存在未支付订单');
                }elseif ($res->getResult() == 4){
                    $this->error('存在未确认订单');
                }


            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }


            }
        }

    
    /**
     * 退货单列表
     */
    public function return_list(){
        return $this->fetch();
    }
    
    /**
     * 添加一笔订单
     */
    public function add_order()
    {
        //*****可以在用一个接口获取相关信息
        $order = array();
        //  获取省份
//        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
//        //  获取订单城市
//        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
//        //  获取订单地区
//        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
//        //  获取配送方式
//        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
//        //  获取支付方式
//        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        // if(IS_POST)
        // {
        //     $order['user_id'] = I('user_id');// 用户id 可以为空
        //     $order['consignee'] = I('consignee');// 收货人
        //     $order['province'] = I('province'); // 省份
        //     $order['city'] = I('city'); // 城市
        //     $order['district'] = I('district'); // 县
        //     $order['address'] = I('address'); // 收货地址
        //     $order['mobile'] = I('mobile'); // 手机           
        //     $order['invoice_title'] = I('invoice_title');// 发票
        //     $order['admin_note'] = I('admin_note'); // 管理员备注            
        //     $order['order_sn'] = date('YmdHis').mt_rand(1000,9999); // 订单编号;
        //     $order['admin_note'] = I('admin_note'); // 
        //     $order['add_time'] = time(); //                    
        //     $order['shipping_code'] = I('shipping');// 物流方式
        //     $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');            
        //     $order['pay_code'] = I('payment');// 支付方式            
        //     $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');            
                            
        //     $goods_id_arr = I("goods_id/a");
        //     $orderLogic = new OrderLogic();
        //     $order_goods = $orderLogic->get_spec_goods($goods_id_arr);          
        //     $result = calculate_price($order['user_id'],$order_goods,$order['shipping_code'],0,$order[province],$order[city],$order[district],0,0,0,0);      
        //     if($result['status'] < 0)	
        //     {
        //          $this->error($result['msg']);      
        //     } 
           
        //    $order['goods_price']    = $result['result']['goods_price']; // 商品总价
        //    $order['shipping_price'] = $result['result']['shipping_price']; //物流费
        //    $order['order_amount']   = $result['result']['order_amount']; // 应付金额
        //    $order['total_amount']   = $result['result']['total_amount']; // 订单总价
           
        //     // 添加订单
        //     $order_id = M('order')->add($order);
        //     $order_insert_id = DB::getLastInsID();
        //     if($order_id)
        //     {
        //         foreach($order_goods as $key => $val)
        //         {
        //             $val['order_id'] = $order_id;
        //             $rec_id = M('order_goods')->add($val);
        //             if(!$rec_id)
        //                 $this->error('添加失败');                                  
        //         }
  
        //         M('order_action')->add([
        //             'order_id'      => $order_id,
        //             'action_user'   => session('admin_id'),
        //             'order_status'  => 0,  //待支付
        //             'shipping_status' => 0, //待确认
        //             'action_note'   => $order['admin_note'],
        //             'status_desc'   => '提交订单',
        //             'log_time'      => time()
        //         ]);
        //         $this->success('添加商品成功',U("Admin/Order/detail",array('order_id'=>$order_insert_id)));
        //         exit();
        //     }
        //     else{
        //         $this->error('添加失败');
        //     }                
        //}  
        //调用添加订单接口

//        $this->assign('shipping_list',$shipping_list);
//        $this->assign('payment_list',$payment_list);
//        $this->assign('province',$province);
//        $this->assign('city',$city);
//        $this->assign('area',$area);
        if($_POST){
            $this->success("添加成功");
        }        
        return $this->fetch();
    }
    
    /**
     * 选择搜索商品
     */
    public function search_goods()
    {
//    	$brandList =  M("brand")->select();
//    	$categoryList =  M("goods_category")->select();
//    	$this->assign('categoryList',$categoryList);
//    	$this->assign('brandList',$brandList);
    	$where = ' is_on_sale = 1 ';//搜索条件
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));    		
            $grandson_ids = getCatGrandson(I('cat_id')); 
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
                
    	}
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}
//        $goods_count =M('goods')->where($where)->count();
//        $Page = new Page($goods_count,C('PAGESIZE'));
//    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow,$Page->listRows)->select();
                
//        foreach($goodsList as $key => $val)
//        {
//            $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
//            $goodsList[$key]['spec_goods'] = $spec_goods;
//        }
//        if($goodsList){
//            //计算商品数量
//            foreach ($goodsList as $value){
//                if($value['spec_goods']){
//                    $count += count($value['spec_goods']);
//                }else{
//                    $count++;
//                }
//            }
//            $this->assign('totalSize',$count);
//        }

//    	$this->assign('page',$Page->show());
//    	$this->assign('goodsList',$goodsList);
    	return $this->fetch();
    }
    
    public function ajaxOrderNotice(){
//        $order_amount = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();
//        echo $order_amount;
    }

    public function add_note(){
        $jwt_token =$_COOKIE['accesstoken'];
        $payload =validate_json_web_token($jwt_token);//解码token
        $seller_id = $payload['seller_id']?$payload['seller_id']:4;
        $note = I('note');
        $order_id=I('order_id/d');
        $client = GRPC(Trade);
        $admin = Trade(Note);
        $admin->setAdminId($seller_id);
        $admin->setNote($note);
        $admin->setOrderId($order_id);
        list($res,$status) = $client->AddNoteFromAdmin($admin)->wait();
        if($res->getValue()){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    public function batch_confirm(){
        $order_ids = I('ids');
        $order_ids = array_filter(explode(',',$order_ids));
        $client = GRPC(Trade);
        $order_id = Trade(IdList);
        $order_id->setOrderId($order_ids);
        list($res,$status) = $client->BatchComfirm($order_id)->wait();
        if($res->getValue()){
            $this->success('批量确认成功');
        }else{
            $this->error('批量确认失败');
        }
    }
}

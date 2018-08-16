<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\OrderLogic;
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
        adminOperateLog('订单首页',3);
    	$this->assign('timegap',$begin.'-'.$end);
        return $this->fetch();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex(){
        $add_time_begin=strtotime(I('post.add_time_begin'));//下单开始时间
        $add_time_end=strtotime(I('post.add_time_end'));//下单结束时间
        $pay_status=I('post.pay_status');//支付状态
        $pay_type=I('post.pay_code');//支付方式
        //var_dump($add_time_begin);die;
        $shipping_status=I('post.shipping_status');//发货状态
        $order_status=I('post.order_status');//订单状态
        $keytype=I('post.keytype');//查询方式收货人或者订单编号
        $keywords=trim(I('post.keywords'));//搜索内容
        $rightParam = getRightParam();//获取绑定的商家id
        //分页页码
        $p=I('get.p/d',1);
        $client = GRPC('AdminOrder');
        $co=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($co);
        $user = new Psp\Trade\AdminConditions();
        $user->setPagination($page);//传入分页
        if($keytype=='consignee'){
            $user->setConsignee($keywords);//传入收货人
        }elseif($keytype=='order_sn'){
            $user->setOrderSn($keywords);//传入订单编号
        }elseif ($keytype=='wh_id'){
            $user->setWalhaoId($keywords);//传入订单编号
        }elseif ($keytype=='store_name'){
            $user->setStoreName($keywords);//传入店铺名称
        }elseif ($keytype=='pay_code') {
            $user->setPayCode($keywords);//传入店铺名称
        }

        //$user->setTp138UserId('2');//传入138ID
        $user->setRightParams($rightParam);
        $user->setTime('order_date');
        $add_time_begin&&$user->setStartTime(grpcTime($add_time_begin));
        $add_time_end&&$user->setEndTime(grpcTime($add_time_end));
        $pay_status&&$user->setPayStatus($pay_status);//传入支付状态
        $pay_type&&$user->setPayType($pay_type);//传入支付方式s
        $shipping_status&&$user->setShippingStatus($shipping_status);//传入发货状态
        $order_status&&$user->setOrderStatus($order_status);//传入订单状态
        //$user->setReturningStatus();setConsignee

        list($res,$status) = $client->GetOrderList($user)->wait();
        foreach ($res->getOrderList() as $k=>$v) {
            //var_dump($v->getLogictics());
            $data[$k]['store_name'] = $v->getStoreName();
            $data[$k]['consignee'] = $v->getConsignee();
            $data[$k]['order_sn'] = $v->getOrderSn();
            $data[$k]['walhao_id'] = $v->getWalhaoId();
            $data[$k]['create_time'] = $v->getCreateTime()->getSeconds();
            $data[$k]['pay_status'] = $v->getPayStatus();
            $data[$k]['pay_code'] = $v->getPayType();
            $data[$k]['shipping_status'] = $v->getShippingStatus();
            $data[$k]['order_status'] = $v->getOrderStatus();
            $data[$k]['shipping_name'] = $v->getShippingName();
            $data[$k]['order_amount'] = round($v->getOrderAmount(),2);
            $data[$k]['pay_money'] = round($v->getPayMoney(),2);
            $data[$k]['order_id'] = $v->getOrderId();
            $data[$k]['code'] = $v->getPayCode();
            $data[$k]['mobile'] = $v->getReceiverPhone();
            $data[$k]['type'] = $v->getOrderType();
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        $lim=$res->getPaginationResult()->getPageSize();
        $Page  = new AjaxPage($count,$lim);
        $show = $Page->show();
        $this->assign('orderList',$data);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();


    }

    /*
     * ajax 发货订单列表
    */
//    public function ajaxdelivery(){
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
//    }
    
    public function refund_order_list(){
    	$orderLogic = new OrderLogic();
    	$condition = array();
    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
    	$condition['shipping_status'] = 0;
    	$condition['order_status'] = 3;
    	$condition['pay_status'] = array('gt',0);
    	//$count = M('order')->where($condition)->count();
    	//$Page  = new Page($count,10);
    	//搜索条件下 分页赋值
    	foreach($condition as $key=>$val) {
    		if(!is_array($val)){
    			//$Page->parameter[$key]   =   urlencode($val);
    		}
    	}
    	//$show = $Page->show();
    	//$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
    	//$this->assign('orderList',$orderList);
    	//$this->assign('page',$show);// 赋值分页输出
    	//$this->assign('pager',$Page);
        adminOperateLog('取消退款单列表',3);
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
    		//M('order')->where(array('order_id'=>$order['order_id']))->save($data);
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
    					//M('order')->where(array('order_id'=>$order['order_id']))->save($data);
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
    		//M('order')->where(array('order_id'=>$order['order_id']))->save($data);
    		$this->success('拒绝退款操作成功');
    	}   	
    }
    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id){
        $return_id =I('get.order_id');


        $client = GRPC('AdminOrder');
        //var_dump($client);die;

        $user = new Psp\Trade\AdminUserOrderId();

        $user->setOrderId($return_id);

        list($res,$status) = $client->GetOrderInfo($user)->wait();
        $return_goods['order_id']=$res->getOrder()->getOrderId();//订单id
        $return_goods['order_sn']=$res->getOrder()->getOrderSn();//订单编号
        $return_goods['grounp_id']=$res->getOrder()->getGrounpId();//订单组id
        $return_goods['type']=$res->getOrder()->getType();//订单类型
        $return_goods['platform_id']=$res->getOrder()->getPlatformId();//平台id
        $return_goods['user_id']=$res->getOrder()->getUserId();//用户id(包括会员用户和商家用户)
        $return_goods['provider_id']=$res->getOrder()->getProviderId();//商家id(如果是平台订单，则为0)
        $return_goods['pay_code']=$res->getOrder()->getPayCode();//支付码
        $return_goods['order_status']=$res->getOrder()->getOrderStatus();//订单状态
        $return_goods['delivery_status']=$res->getOrder()->getDeliveryStatus();//物流状态
        $return_goods['pay_status']=$res->getOrder()->getPayStatus();//支付状态
        $return_goods['returning_status']=$res->getOrder()->getReturningStatus();//退货状态
        $return_goods['returning_delivery_status']=$res->getOrder()->getReturningDeliveryStatus();///退货物流状态
        $return_goods['delivery_id']=$res->getOrder()->getDeliveryId();//物流id
        $return_goods['returning_delivery_id']=$res->getOrder()->getReturningDeliveryId();//退货物流id
        $return_goods['currency']=$res->getOrder()->getCurrency();//订单货币类型
        $return_goods['order_amount']=round($res->getOrder()->getOrderAmount(),2);//订单金额
        $return_goods['delivery_amount']=round($res->getOrder()->getDeliveryAmount(),2);//运费金额
        $return_goods['promo_amount']=round($res->getOrder()->getPromoAmount(),2);//优惠金额
        $return_goods['total_amount']=round($res->getOrder()->getTotalAmount(),2);//订单总金额
        $return_goods['receiver']=$res->getOrder()->getReceiver();//收件人名称
        $return_goods['receiver_location']=$res->getOrder()->getReceiverLocation();//收件人地区id
        $areamap= new \area\area();
        $return_goods['address_info'] =$areamap->getAddrstr($return_goods['receiver_location']);
        $return_goods['receiver_address']=$res->getOrder()->getReceiverAddress();//收件人详细地址
        $return_goods['receiver_phone']=$res->getOrder()->getReceiverPhone();//收件人电话
        $return_goods['sms_notify']=$res->getOrder()->getSmsNotify();//是否短信通知收件人
        $return_goods['order_date']=$res->getOrder()->getOderDate()?$res->getOrder()->getOderDate()->getSeconds():0;//下单时间
        $return_goods['pay_date']=$res->getOrder()->getPayDate()?$res->getOrder()->getPayDate()->getSeconds():0;//支付时间
        $return_goods['shipping_date']=$res->getOrder()->getShippingDate()?$res->getOrder()->getShippingDate()->getSeconds():0;//发货时间
        $return_goods['receipted_date']=$res->getOrder()->getReceiptedDate()?$res->getOrder()->getReceiptedDate()->getSeconds():0;//签收时间
        $return_goods['invoice_title']=$res->getOrder()->getInvoiceTitle();//发票抬头
        $return_goods['buyer_message']=$res->getOrder()->getBuyerMessage();//买家备注
        $return_goods['pay_type']=$this->pay_type[$res->getOrder()->getPayType()];//支付方式
        $return_goods['wh_id']=$res->getWhId();//wh_id
        //物流信息
        if($res->getDeliveryDoc()){
            $return_goods['create_time'] =$res->getDeliveryDoc()->getCreateTime()?$res->getDeliveryDoc()->getCreateTime()->getSeconds():0;//发货时间
            $return_goods['invoice_no'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getInvoiceNo():''; //物流单号
            $return_goods['shipping_name'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getShippingName():''; //物流公司
            $return_goods['note'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getNote():'';//发货备注
        }


        foreach($res->getOrderItem()as $k=>$v){
            $goods[$k]['name']=$v->getName();//商品名称
            $goods[$k]['item_id']=$v->getItemId();//商品id
            $goods[$k]['sku_id']=$v->getSkuId();//商品sku
            $goods[$k]['price']=$v->getPrice();//商品成本价
            $goods[$k]['cost']=$v->getCost();//商品单价
            $goods[$k]['currencey']=$v->getCurrencey();//币种
            $goods[$k]['amount']=$v->getAmount();//数量
            $goods[$k]['sku_unit']=$v->getSkuUnit();//单位
            $goods[$k]['sku_name']=$v->getSkuName();//规格名称
            $goods[$k]['thumb_image_url']=$v->getThumbImageUrl();//缩略图
            $goods[$k]['return']=$v->getReturn();//标记该商品是否存在退换货
            if($goods[$k]['return']!=1)$return_good[]=$goods[$k];
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
        adminOperateLog('订单详情',3);
        //$this->assign('admins',$admins);
        $this->assign('return_good', $return_good);
        $this->assign('order', $return_goods);
        //$this->assign('action_log',$action_log);
        $this->assign('goods',$goods);
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
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }

        $orderGoods = $orderLogic->getOrderGoods($order_id);

       	if(IS_POST)
        {
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //
            $order['shipping_code'] = I('shipping');// 物流方式
            //$order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');
            $order['pay_code'] = I('payment');// 支付方式
            //$order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');
            $goods_id_arr = I("goods_id/a");
            $new_goods = $old_goods_arr = array();
            //################################订单添加商品
            if($goods_id_arr){
            	$new_goods = $orderLogic->get_spec_goods($goods_id_arr);
            	foreach($new_goods as $key => $val)
            	{
            		$val['order_id'] = $order_id;
            		//$rec_id = M('order_goods')->add($val);//订单添加商品
            		/*if(!$rec_id)
            			$this->error('添加失败');*/
            	}
            }

            //################################订单修改删除商品
            $old_goods = I('old_goods/a');
            foreach ($orderGoods as $val){
            	if(empty($old_goods[$val['rec_id']])){
            		//M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
            	}else{
            		//修改商品数量
            		if($old_goods[$val['rec_id']] != $val['goods_num']){
            			$val['goods_num'] = $old_goods[$val['rec_id']];
            			//M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
            		}
            		$old_goods_arr[] = $val;
            	}
            }

            $goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
            	$this->error($result['msg']);
            }

            //################################修改订单费用
            $order['goods_price']    = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['shipping_price'];//物流费
            $order['order_amount']   = $result['result']['order_amount']; // 应付金额
            $order['total_amount']   = $result['result']['total_amount']; // 订单总价
            //$o = M('order')->where('order_id='.$order_id)->save($order);

            $l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            /*if($o && $l){
            	$this->success('修改成功',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
            	$this->success('修改失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }*/
            exit;
        }
        // 获取省份
        //$province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //获取订单城市
        //$city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //获取订单地区
        //$area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //获取支付方式
        //$payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        //获取配送方式
        //$shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        adminOperateLog('订单编辑',3);
        $this->assign('order',$order);
        /*$this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);*/
        return $this->fetch();
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
    				//M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
    			}else{
    				//修改商品数量
    				if($old_goods[$val['rec_id']] != $val['goods_num']){
    					$val['goods_num'] = $old_goods[$val['rec_id']];
    					//M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
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
    		//M('order')->where("order_id=".$order_id)->save($res);
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
    			//$new_order_id = DB::name('order')->insertGetId($new_order);//插入订单表
    			foreach ($goods as $vv){
    				//$vv['order_id'] = $new_order_id;
    				unset($vv['rec_id']);
    				//$nid = M('order_goods')->add($vv);//插入订单商品表
    			}
    		}
    		//################################新单处理结束
    		$this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            exit;
    	}

    	foreach ($orderGoods as $val){
    		$brr[$val['rec_id']] = array('goods_num'=>$val['goods_num'],'goods_name'=>getSubstr($val['goods_name'], 0, 35).$val['spec_key_name']);
    	}
        adminOperateLog('拆分订单',3);
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
            adminOperateLog('价钱修改',3);
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
        adminOperateLog('删除订单',3);
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
    		//$order = M('order')->where("order_id=$order_id")->find();
    		//$this->assign('order',$order);
    		return $this->fetch();
    	}
        adminOperateLog('订单取消付款',3);
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print(){
    	$order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $shop = tpCache('shop_info');
        adminOperateLog('订单打印',3);
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        $template = I('template','print');
        return $this->fetch($template);
    }

    /**
     * 快递单打印
     */
    public function shipping_print(){
        $order_id = I('get.order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        //查询是否存在订单及物流
        //$shipping = M('plugin')->where(array('code'=>$order['shipping_code'],'type'=>'shipping'))->find();
        /*if(!$shipping){
        	$this->error('物流插件不存在');
        }*/
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
        adminOperateLog('快递单打印',3);
        $this->assign('shipping',$shipping);
        return $this->fetch("Plugin/print_express");
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $orderLogic = new OrderLogic();
		$data = I('post.');
		$res = $orderLogic->deliveryHandle($data);
		if($res){
			$this->success('操作成功',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}else{
			$this->success('操作失败',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}
        adminOperateLog('生成发货单',3);
    }


    public function delivery_info()
    {
        $order_id = I('order_id');
        $client = GRPC('Trade');
        $orderId = new Psp\Trade\OrdId();
        $orderId->setOrderId($order_id);

        list($res,$status) = $client->GetDeliveryInfo($orderId)->wait();

        $order['order_sn'] = $res->getOrderSn();//订单单号
        $order['add_time'] = $res->getOrderDate()->getSecondS();//下单时间
        $order['mobile'] = $res->getReceiverPhone();//收件人电话
        $order['consignee'] = $res->getReceiver();//收货人
        $order['shipping_price'] = $res->getShippingPrice();//配送费用
        $order['shipping_name'] = $res->getShippingName();// 物流名称
        $order['user_note'] = $res->getBuyerMessage();// 买家备注
        $order['invoice_title'] = $res->getInvoiceTitle();// 发票抬头
        $order['invoice_title'] = $res->getInvoiceTitle();// 发票抬头
        $order['address'] = $res->getAddress();//收货地址
        foreach ($res->getOrderGoods() as $k=>$v) {
            $orderGoods[$k]['goods_name'] = $v->getName();//商品名称
            $orderGoods[$k]['goods_id'] = $v->getItemId();//商品id
            $orderGoods[$k]['sku_id'] = $v->getSkuId();//商品sku
            $orderGoods[$k]['spec_key_name'] = $v->getSkuName();//规格属性名称
            $orderGoods[$k]['goods_price'] = $v->getCost();//商品单价
            $orderGoods[$k]['currencey'] = $v->getCurrencey();//币种
            $orderGoods[$k]['goods_num'] = $v->getAmount();//数量
            $orderGoods[$k]['sku_unit'] = $v->getSkuUnit();//单位
            $orderGoods[$k]['goods_thum_images'] = $v-> getThumbImageUrl();//缩略图
        }
        $order['order_status'] = $res->getOrderStatus();//订单状态
        $order['shipping_status'] = $res->getShippingStatus();//物流状态
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
        return $this->fetch();
    }

    /**
     * 发货单列表
     */
    public function delivery_list()
    {
//        $client = new Psp\Trade\AdminOrderServiceClient('192.168.1.111:9300', [
//            'credentials' => Grpc\ChannelCredentials::createInsecure()
//        ]);
        $client = GRPC('AdminOrder');
        $limit = 20;
        if(I('p/d')){
            $p = I('p/d');
        } else{
            $p = 1;
        }
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($limit);

        $delivery = new Psp\Trade\DeliveryConditions();

        if(I('consignee')){

            $delivery->setConsignee(trim(I('consignee'))); // 收货人
        }

        if(I('order_sn')){

            $delivery->setOrderSn(trim(I('order_sn'))); // 订单编号
        }

        if(I('shipping_status')){

            $delivery->setShippingStatus(I('shipping_status')); // 发货状态
        }
        $delivery->setPagination($page); // 分页
        list($res,$status) = $client->GetDeliveryList($delivery)->wait();
        if($res){
            foreach ($res->getOrderList() as $k=>$v) {
                $orderList[$k]['order_id'] = $v->getOrderId();//订单id
                $orderList[$k]['order_sn'] = $v->getOrderSn();//订单编号
                $orderList[$k]['consignee'] = $v->getConsignee();//收货人
                $orderList[$k]['mobile'] = $v->getReceiverPhone();//收件人电话
                $orderList[$k]['add_time'] = $v->getCreateTime()->getSecondS();//订单创建时间
                $orderList[$k]['pay_time'] = $v->getPayDate()?$v->getPayDate()->getSecondS():0;//支付时间
                $orderList[$k]['shipping_price'] = $v->getDeliveryAmount();//物流费用
                $orderList[$k]['shipping_name'] = $v->getShippingName();//物流名称
                $orderList[$k]['total_amount'] = round($v->getOrderAmount(),2);//订单总价
                $orderList[$k]['shipping_status'] = $v->getShippingStatus();//发货状态
            }
            $total_count = $res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,$limit) ;
            $show = $Page->show();
            $this->assign('page',$show);
        }else{
            $orderList = '';
        }
        if($p ==1){
            adminOperateLog('发货单列表',3);
        }
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /*
     * ajax 退货订单列表
     */
    public function ajax_return_list(){
        // 搜索条件
        $order_sn =  trim(I('order_sn'));

        //分页页码
        $p=I('get.p/d',1);

        $status =  I('status');
        $status==""?$status=0:$status;

        $client = GRPC('AdminOrder');
        //var_dump($client);die;
        $co=20;
        //$start=($p-1)*$co;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_date");
        $page->setIndex($p);
        $page->setLimit($co);

        $user = new Psp\Trade\ReturnConditions();
        //var_dump($user);die;
        $order_sn&&$user->setOrderSn($order_sn);
        $user->setPagination($page);
//        if($add_time)
//        {
//            $time = new Psp\Timestamp();
//            $time->setSeconds($add_time);
//            $user -> setAddTime($time);
//        }

//        $user->setShopName('');
//        $user->setIsFromAdmin('');
        $user->setReturningStatus($status);
        list($res,$status) = $client->GetReturnList($user)->wait();
        //var_dump($res->getReturnList());die;

        foreach ($res->getReturnList() as $k=>$v) {

            $data[$k]['order_id'] = $v->getOrderId();
            $data[$k]['order_sn'] = $v->getOrderSn();
            $data[$k]['title'] = $v->getTitle();
            $data[$k]['shop_name']= $v->getShopName();
            $data[$k]['type']=$v->getType();
            $data[$k]['add_time'] = $v->getAddTime()->getSeconds();
            $data[$k]['returning_status'] = $v->getReturningStatus();
            $data[$k]['parent_id'] = $v->getParentId();
            $data[$k]['parent_sn'] = $v->getParentSn();

        }


        //获得总条数
        $count=$res->getPaginationResult()->getTotalRecords();
        $lim=$res->getPaginationResult()->getPageSize();
        if($p ==1){
            adminOperateLog('退货订单列表',3);
        }
        $Page  = new AjaxPage($count,$lim);
        $show = $Page->show();
        $state = C('REFUND_STATUS');
        $this->assign('state',$state);
        $this->assign('count',$count);
        $this->assign('list',$data);
        $this->assign('pager',$page);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();


    }

    /**
     * 删除某个退换货申请
     */
    public function return_del(){
        $id = I('post.id');
        $client = GRPC('AdminOrder');
        $order_id = new Psp\Trade\AdminOrderId();
        $order_id->setOrderId($id);
        list($res,$status) = $client->DelReturn($order_id)->wait();
        adminOperateLog('删除某个退换货申请',3);
        if($res->getValue()){
            exit(json_encode(['status'=>1,'msg'=>'删除成功']));
        }else{
            exit(json_encode(['status'=>-1,'msg'=>'删除失败']));

        }

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
        adminOperateLog('退换货操作',3);
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

            list($res,$status) = $client->UpdateReturnMoney($return)->wait();
            $result = $res->getValue();
            //1.成功 2:用户不存在 3余额不足
            if($result==3){
                $this->error("操作失败,商家余额不足");
            }elseif($result==1){
                $this->success("操作成功",U("Admin/Order/return_info",array('id'=>$order_id)));
            }elseif($result==2){
                $this->error("操作失败,商家账户不存在");
            }
            exit;
        }
        adminOperateLog('账户资金调节',3);
        $this->assign('money',I('m'));
        return $this->fetch();
    }
    
    public function refund_back(){
    	$return_id = I('id');
        //$return_goods = M('return_goods')->where("id= $return_id")->find();
    	//$rec_goods = M('order_goods')->where(array('order_id'=>$return_goods['order_id'],'goods_id'=>$return_goods['goods_id']))->find();
    	//$order = M('order')->where(array('order_id'=>$rec_goods['order_id']))->find();
    	/*if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){
    		$return_money = $return_goods['refund_money'];
    		if($order['pay_code'] == 'weixin'){
    			include_once  PLUGIN_PATH."payment/weixin/weixin.class.php";
    			$payment_obj =  new \weixin();
    			$data = array('transaction_id'=>$order['transaction_id'],'total_fee'=>$order['order_amount'],'refund_fee'=>$return_money);
    			$result = $payment_obj->payment_refund($data);
    			if($result['return_code'] == 'SUCCESS'){
    				M('order_goods')->where(array('rec_id'=>$rec_goods['rec_id']))->save(array('is_send'=>3));
    				$updata = array('refund_type'=>2,'refundtime'=>time(),'status'=>3);
    				M('return_goods')->where("id= $return_id")->save($updata);
    				//使用积分或者余额抵扣部分一一退还
    				if($return_goods['refund_deposit']>0 || $return_goods['refund_integral']>0){
    					accountLog($return_goods['user_id'],$return_goods['refund_deposit'],$return_goods['refund_integral'],'用户申请商品退款',0,$return_goods['order_id'],$return_goods['order_sn']);
    				}
    				//若该商品有赠送积分则追回
    				$order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
    				if($order_goods['give_integral']>0){
    					accountLog($return_goods['user_id'],0,-$return_goods['refund_integral'],'退货积分追回',0,$return_goods['order_id'],$return_goods['order_sn']);
    				}
    				$this->success('退款成功');
    			}else{
    				$this->error($result['return_msg']);
    			}
    		}else{
    			include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
    			$payment_obj = new \alipay();
    			$detail_data = $order['transaction_id'].'^'.$return_money.'^'.'用户申请订单退款';
    			$data = array('batch_no'=>date('YmdHi').$rec_goods['rec_id'],'batch_num'=>1,'detail_data'=>$detail_data);
    			$payment_obj->payment_refund($data);
    			//使用积分或者余额抵扣部分一一退还
    			if($return_goods['refund_deposit']>0 || $return_goods['refund_integral']>0){
    				accountLog($return_goods['user_id'],$return_goods['refund_deposit'],$return_goods['refund_integral'],'用户申请商品退款',0,$return_goods['order_id'],$return_goods['order_sn']);
    			}
    			//若该商品有赠送积分则追回
    			$order_goods = M('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
    			if($order_goods['give_integral']>0){
    				accountLog($return_goods['user_id'],0,-$return_goods['refund_integral'],'退货积分追回',0,$return_goods['order_id'],$return_goods['order_sn']);
    			}
    		}
    	}else{
    		$this->error('该订单支付方式不支持在线退回');
    	}*/
    }

    /**
     * 管理员生成申请退货单
     */
    public function add_return_goods()
   {
            $order_id = I('order_id');
            $goods_id = I('goods_id');

            //$return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id")->find();
            if(!empty($return_goods))
            {
                $this->error('已经提交过退货申请!',U('Admin/Order/return_list'));
                exit;
            }
            //$order = M('order')->where("order_id = $order_id")->find();

            $data['order_id'] = $order_id;
            //$data['order_sn'] = $order['order_sn'];
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            //$data['user_id'] = $order[user_id];
            $data['remark'] = '管理员申请退换货'; // 问题描述
            //M('return_goods')->add($data);
            $this->success('申请成功,现在去处理退货',U('Admin/Order/return_list'));
       adminOperateLog('管理员生成申请退货单',3);
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
            $this->error('操作失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
            exit;
        }
        adminOperateLog('订单操作',3);
        $this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
        exit;
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
    	//$log =  M('order_action');
    	if($begin && $end){
    		$condition['log_time'] = array('between',"$begin,$end");
    	}
    	$admin_id = I('admin_id');
		if($admin_id >0 ){
			$condition['action_user'] = $admin_id;
		}
    	//$count = $log->where($condition)->count();
    	//$Page = new Page($count,20);
    	foreach($condition as $key=>$val) {
    		//$Page->parameter[$key] = urlencode($val);
    	}
    	//$show = $Page->show();
    	//$list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $orderIds = [];
        //foreach ($list as $log) {
            //if (!$log['action_user']) {
                //$orderIds[] = $log['order_id'];
           // }
        //}
        if ($orderIds) {
            //$users = M("users")->alias('u')->join('__ORDER__ o', 'o.user_id = u.user_id')->getField('o.order_id,u.nickname');
        }
       // $this->assign('users',$users);
    	//$this->assign('list',$list);
    	//$this->assign('pager',$Page);
    	//$this->assign('page',$show);
    	//$admin = M('admin')->getField('admin_id,user_name');
    	//$this->assign('admin',$admin);
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

    public function export_order()
    {
        set_time_limit(0);
        $s = microtime(true);
        $add_time_begin=strtotime(I('post.add_time_begin'));//下单开始时间
        $add_time_end=strtotime(I('post.add_time_end'));//下单结束时间
        $pay_status=I('post.pay_status');//支付状态

        $pay_type=I('post.pay_code');//支付方式
        //var_dump($add_time_begin);die;
        $shipping_status=I('post.shipping_status');//发货状态
        $order_status=I('post.order_status');//订单状态

        $keytype=I('post.keytype');//查询方式收货人或者订单编号

        $keywords=trim(I('post.keywords'));//搜索内容

        $order_ids = I('post.order_ids');

        $client = GRPC('AdminOrder');

        $user = new Psp\Trade\ExportConditions();

        if($keytype=='consignee'){
            $user->setConsignee($keywords);//传入收货人
        }elseif($keytype=='order_sn'){
            $user->setOrderSn($keywords);//传入订单编号
        }elseif ($keytype=='wh_id'){
            $user->setWalhaoId($keywords);//传入订单编号
        }elseif ($keytype=='store_name'){
            $user->setStoreName($keywords);//传入店铺名称
        }

        $rightParam = getRightParam();//获取绑定的商家
        $order_ids&&$user->setOrderIds($order_ids);
        $user->setTime('o.order_date');
        $user->setRightParams($rightParam);
        $add_time_begin&&$user->setStartTime(grpcTime($add_time_begin));
        $add_time_end&&$user->setEndTime(grpcTime($add_time_end));
        $pay_status&&$user->setPayStatus($pay_status);//传入支付状态
        $pay_type&&$user->setPayType($pay_type);//传入支付方式s
        $shipping_status&&$user->setShippingStatus($shipping_status);//传入发货状态
        $order_status&&$user->setOrderStatus($order_status);//传入订单状态

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
        $time = date('YmdHi');
        $file_name = $time.'_'.$nm; //文件名
        create_xls($arraydata,$file_name,1);
        exit();


    }

    /*//新导单 2018/5/17
    public function export_order()
    {
        $s = microtime(true);
        $add_time_begin=strtotime(I('post.add_time_begin'));//下单开始时间
        $add_time_end=strtotime(I('post.add_time_end'));//下单结束时间
        $pay_status=I('post.pay_status');//支付状态

        $pay_type=I('post.pay_code');//支付方式
        //var_dump($add_time_begin);die;
        $shipping_status=I('post.shipping_status');//发货状态
        $order_status=I('post.order_status');//订单状态

        $keytype=I('post.keytype');//查询方式收货人或者订单编号

        $keywords=trim(I('post.keywords'));//搜索内容

        $order_ids = I('post.order_ids');

        $client = GRPC('AdminOrder');

        $user = new Psp\Trade\ExportConditions();

        if($keytype=='consignee'){
            $user->setConsignee($keywords);//传入收货人
        }elseif($keytype=='order_sn'){
            $user->setOrderSn($keywords);//传入订单编号
        }elseif ($keytype=='wh_id'){
            $user->setWalhaoId($keywords);//传入订单编号
        }elseif ($keytype=='store_name'){
            $user->setStoreName($keywords);//传入店铺名称
        }

        $order_ids&&$user->setOrderIds($order_ids);
        $user->setTime('o.order_date');
        $add_time_begin&&$user->setStartTime(grpcTime($add_time_begin));
        $add_time_end&&$user->setEndTime(grpcTime($add_time_end));
        $pay_status&&$user->setPayStatus($pay_status);//传入支付状态
        $pay_type&&$user->setPayType($pay_type);//传入支付方式s
        $shipping_status&&$user->setShippingStatus($shipping_status);//传入发货状态
        $order_status&&$user->setOrderStatus($order_status);//传入订单状态

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
        $top_array = ['订单号','日期','收货人','收货地址','电话','订单金额','实际支付','支付方式','支付状态','发货状态','商品信息','成本价','运单号','处理备注','店铺名称'];
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

        exportToExcel($nm.'_order_export.csv',$top_array,$arraydata);

        exit();


    }*/
    
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
        /*$province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //  获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //  获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        //  获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();*/
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

        /*$this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);*/
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
    	/*$brandList =  M("brand")->select();
    	$categoryList =  M("goods_category")->select();
    	$this->assign('categoryList',$categoryList);
    	$this->assign('brandList',$brandList);*/
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
        //$goods_count =M('goods')->where($where)->count();
        //$Page = new Page($goods_count,C('PAGESIZE'));
    	//$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow,$Page->listRows)->select();
                
        //foreach($goodsList as $key => $val)
        //{
           // $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
           // $goodsList[$key]['spec_goods'] = $spec_goods;
        //}
        /*if($goodsList){
            //计算商品数量
            foreach ($goodsList as $value){
                if($value['spec_goods']){
                    $count += count($value['spec_goods']);
                }else{
                    $count++;
                }
            }
            $this->assign('totalSize',$count);
        }*/

    	//$this->assign('page',$Page->show());
    	//$this->assign('goodsList',$goodsList);
    	return $this->fetch();
    }
    
    public function ajaxOrderNotice(){
        //$order_amount = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();
        //echo $order_amount;
    }

    public function add_note(){
        $payload =validate_json_web_token(cookie('_authtoken'));//解码token
        $admin_id =$payload['admin_id']?$payload['admin_id']:0;
        $note = I('note');
        $order_id=I('order_id/d');
        $client = GRPC(Trade);
        $admin = Trade(Note);
        $admin->setAdminId($admin_id);
        $admin->setNote($note);
        $admin->setOrderId($order_id);
        list($res,$status) = $client->AddNoteFromAdmin($admin)->wait();
        if($res->getValue()){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    public function order_total(){
        $client = GRPC('Trade');
        list($res,$status) = $client->GetOrderTotal(grpcEmpty())->wait();
        $order['total_amount'] = $res->getTotalAmount();
        $order['total_sum'] = $res->getTotalSum();
        $order['flag_amount'] = $res->getFlagAmount();
        $order['flag_sum'] = $res->getFlagSum();
        $order['normal_amount'] = $res->getNormalAmount();
        $order['normal_sum'] = $res->getNormalSum();
        $order['return_amount'] = $res->getReturnAmount();
        $order['return_sum'] = $res->getReturnSum();
        $order['return_flag_amount'] = $res->getReturnFlagAmount();
        $order['return_flag_sum'] = $res->getReturnFlagSum();
        $order['return_normal_amount'] = $res->getReturnNormalAmount();
        $order['return_normal_sum'] = $res->getReturnNormalSum();
        $str = "<table border='1'>";
        $str.="<tr><td>订单总数量</td><td>订单总金额</td><td>专区订单总数量</td><td>专区订单总金额</td><td>普通订单总数量</td><td>普通订单总金额</td><td>退货订单总数量</td><td>退货订单总金额</td><td>专区退货订单总数量</td><td>专区退货订单总金额</td><td>普通退货订单总数量</td><td>普通退货订单总金额</td></tr>";
        $str.="<tr><td>$order[total_sum]</td><td>$order[total_amount]</td><td>$order[flag_sum]</td><td>$order[flag_amount]</td><td>$order[normal_sum]</td><td>$order[normal_amount]</td><td>$order[return_sum]</td><td>$order[return_amount]</td><td>$order[return_flag_sum]</td><td>$order[return_flag_amount]</td><td>$order[return_normal_sum]</td><td>$order[return_normal_amount]</td></tr>";
        $str.= "</table>";
        adminOperateLog('订单统计',3);
        echo $str;
    }

}

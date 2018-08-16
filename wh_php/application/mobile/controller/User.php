<?php
/**
 * @Author: qiang_ge
 * @Date:   2018-06-19 14:00:15
 * @Last Modified by:   qiang_ge
 * @Last Modified time: 2018-06-19 14:01:39
 */
namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use app\home\model\Message;
use app\common\logic\OrderLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\db;
use Grpc;
use Psp;
use app\admin\logic\AccountCenterLogic;
use think\captcha\Captcha;

class User extends MobileBase
{

    public $user_id = 0;
    public $user = array();
    public $wh181_id=0;

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();

        if($this->checkLogin())
        {
            //从token中取出user_id
            $jwt_token =$_COOKIE['token'];
            $payload =validate_json_web_token($jwt_token);//解码token
            //每次调用重新签发token
            $token = create_json_web_token($payload);
            setrawcookie('token',$token,0,'/',get_host(),false,true); //覆盖原token
            $this->user_id = (int)$payload['user_id'];
            $this->mobile = $payload['mobile'];
            $this->nickname = $payload['nickname'];
            $this->wh181_id = $payload['tps138_id'];
            $this->org_id = $payload['org_id'];//组织id
            $user = ['mobile'=>$this->mobile,'wh181_id'=>$this->wh181_id,'org_id'=>$this->org_id,'nickname'=>$this->nickname];
            //获取用户未读信息的数量
            $user_id = new Psp\Member\Uid();
            $user_id->setUid($this->user_id);
            list($res,$status) = GRPC('member')->GetUserUnreadMessageCount($user_id)->wait();
            if(!empty($res)){
                $count = $res->getCount();
            }
            $this->assign('user_id',$this->user_id);
            $this->assign('user',$user);
            $this->assign('user_message_count', $count);
        }
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
        );

        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            header("location:" . U('Mobile/User/login'));
            exit;
        }

        $order_status_coment = array(
            'WAITPAY' => '待付款 ', //订单查询状态 待支付
            'WAITSEND' => '待发货', //订单查询状态 待发货
            'WAITRECEIVE' => '待收货', //订单查询状态 待收货
            'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment', $order_status_coment);
    }

    /*
     * 用户中心首页
     */
    public function index()
    {
        /*$user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        $comment_count = M('comment')->where("user_id", $user_id)->count();   // 我的评论数
        $level_name = M('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        //获取用户信息的数量
        $user_message_count = D('Message')->getUserMessageCount();
        $this->assign('user_message_count', $user_message_count);
        $this->assign('level_name', $level_name);
        $this->assign('comment_count', $comment_count);*/
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $num= $logic->user_goods_collect($this->user_id);
        $user['result']['collect_count'] = $num['goods_num'] ?  $num['goods_num'] : '0';//商品收藏数量
        //获取账户信息
        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        $money =  sprintf("%.2f", $account_info['balance']);//余额
        $shouyi = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM);
        $user['result']['shouyi'] =  sprintf("%.2f", $shouyi['balance']);//余额
        $this->assign('money',$money);
        $this->assign('user',$user['result']);
        return $this->fetch();
    }


    public function logout()
    {
        /*cookie('token', null);
        cookie('uname',null);*/
        setcookie("token", null, time() - 3600, "/", get_host());
        setcookie("uname", null, time() - 3600, "/", get_host());
        setcookie("cn", null, time() - 3600, "/", get_host());//原来的  购物车数量标记
        setcookie("curLogin", null, time() - 3600, "/", get_host());
        $this->redirect('Mobile/Index/index');
        exit();
    }

    /*
     * 账户资金
     */
    public function account()
    {
//        $user = session('user');
        //获取账户资金记录
//        $logic = new UsersLogic();
//        $data = $logic->get_account_log($this->user_id, I('get.type'));

        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        $user['balance'] = sprintf("%.2f", $account_info['balance']);
//        $account_log = $data['result'];

        $this->assign('user',$user);
//        $this->assign('account_log', $account_log);
//        $this->assign('page', $data['show']);

//        if ($_GET['is_ajax']) {
//            return $this->fetch('ajax_account_list');
//            exit;
//        }
        return $this->fetch();
    }


    /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
//
            header("Location: " . U('Mobile/User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Mobile/User/index");
        $this->assign('referurl', $referurl);
        return $this->fetch();
    }

    /**
     * 登录
     */
    public function do_login()
    {

        $username = trim(I('post.username'));
        $password = trim(I('post.password'));
        /*$verify_code = I('post.verify_code');
        $verify = new Verify();
        if (!$verify->check($verify_code, 'user_login')) {
            $res = array('status' => 0, 'msg' => '验证码错误');
            exit(json_encode($res));
        }*/
        $logic = new UsersLogic();
        $res = $logic->login($username,$password);
        if($res['status'] == 1){
            $res['url'] =  urldecode(I('post.referurl'));
            /*$cartLogic = new \app\home\logic\CartLogic();
            $cartLogic->setUserId($res['result']['user_id']);
            $cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作*/
        }

        exit(json_encode($res));
    }

    /**
     *  注册
     */
    /**
     *  注册
     */
    public function reg()
    {

//        $this->error('维护中,敬请谅解!');exit;

        if($this->user_id > 0) {
            $this->redirect(U('Mobile/User/index'));
        }
        if (IS_POST) {
            $logic = new UsersLogic();
            $mobile = I('post.mobile', '');
//            $email = I('post.email','');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            $recommond_id = I('post.recommend');
            $real_name = I('post.realName');
            $verify_code = I('post.verify_code','');
            //验证码检验
            $verify = new Verify();
            if (!$verify->check($verify_code,'user_reg'))
            {
                $res = array('status'=>0,'msg'=>'验证码错误');
                exit(json_encode($res));
            }
            $data = $logic->reg($mobile,$recommond_id,$real_name,$password);

            if ($data['status'] != 1){
                $this->ajaxReturn($data);
            }

            $this->ajaxReturn($data);
            exit;
        }

        return $this->fetch();
    }

    /*
     * 订单列表
     */
    public function order_list()
    {

        $search = trim(I('search_key'));
        $type = I('type');
        $client = GRPC(Trade);

        $limit = 10;
        $p = I('p/d',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($limit);
        $user = Trade(OrderCondition);
        $user->setMemberId((int)$this->user_id);
        $user->setCondition($type);
        $user->setPagination($page); // 分页
        $user->setSearch($search);
        list($res,$status) = $client->GetUserOrder($user)->wait();
        foreach ($res->getUserOrder() as $k=>$v) {
            //var_dump($v->getLogictics());
            $data[$k]['order_id'] = $v->getOrderId();
            $data[$k]['order_sn'] = $v->getOrderSn();
            $data[$k]['order_status'] = $v->getState();
            $data[$k]['member_goods_price'] = $v->getMoney();
            $data[$k]['add_time'] = $v->getOrderDate()->getSeconds();
            foreach($v->getItems() as $kk=>$vv){
                $data[$k]['order_item'][$kk]['goods_name'] = $vv->getName();
                $data[$k]['order_item'][$kk]['goods_id'] = $vv->getItemId();
                $data[$k]['order_item'][$kk]['price'] = $vv->getPrice();
                $data[$k]['order_item'][$kk]['cost'] = $vv->getCost();
                $data[$k]['order_item'][$kk]['goods_num'] = $vv->getAmount();
                $data[$k]['order_item'][$kk]['thumb_img_url']= $vv->getThumbImageUrl();
                $data[$k]['order_item'][$kk]['order_item_id']= $vv->getOrderItemId();
                $data[$k]['order_item'][$kk]['sku_name'] = $vv->getSkuName();
            }

            $data[$k]['shipping_price'] = $v->getShippingPrice();
            $data[$k]['pay_status'] = $v->getPayStatus();
            $data[$k]['delivery_status'] = $v->getDeliveryStatus();
            $data[$k]['shop_name'] = $v->getShopname();
            $data[$k]['qq'] = $v->getQq();

        }
        //dump($data);die;

        //获取订单商品
        /* $model = new UsersLogic();
         foreach ($order_list as $k => $v) {
             $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
             //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
             $data = $model->get_order_goods($v['order_id']);*/
//            $order_list['goods_list'] = $data;
//            var_dump($order_list['goods_list']);die;
//        }
        //统计订单商品数量
        /* foreach ($order_list as $key => $value) {
             $count_goods_num = '';
             foreach ($value['goods_list'] as $kk => $vv) {
                 $count_goods_num += $vv['goods_num'];
             }
             $order_list[$key]['count_goods_num'] = $count_goods_num;
         }*/
//        var_dump($count_goods_num);die;
        $this->assign('order_status', C('ORDER_STATUS'));
        $this->assign('shipping_status', C('SHIPPING_STATUS'));
        $this->assign('pay_status', C('PAY_STATUS'));
        $this->assign('page', $show);
        $this->assign('lists', $data);
        $this->assign('active', 'order_list');
        $this->assign('active_status', I('get.type'));
        /* if ($_GET['is_ajax']) {
             return $this->fetch('ajax_order_list');
             exit;
         }*/
        return $this->fetch();
    }


    /*
     * 订单详情
     */
    public function order_detail()
    {
        $id = I('get.id/d');
        /*$map['order_id'] = $id;
        $map['user_id'] = $this->user_id;*/
//        $order_info = M('order')->where($map)->find();
        $client = GRPC('AdminOrder');
        $user = new Psp\Trade\AdminUserOrderId();
        $user->setOrderId($id);
        $user->setUserId($this->user_id);
        list($res,$status) = $client->GetOrderInfo($user)->wait();
        if(!$res->getOrder())$this->error('获取订单详情有误',U('order_list'));
        if($res){
            $areamap= new \area\area();
            $order['order_id']=$res->getOrder()->getOrderId();//订单id
            $order['order_sn']=$res->getOrder()->getOrderSn();//订单编号
            $order['grounp_id']=$res->getOrder()->getGrounpId();//订单组id
            $order['type']=$res->getOrder()->getType();//订单类型
            $order['platform_id']=$res->getOrder()->getPlatformId();//平台id
            $order['user_id']=$res->getOrder()->getUserId();//用户id(包括会员用户和商家用户)
            $order['provider_id']=$res->getOrder()->getProviderId();//商家id(如果是平台订单，则为0)
            $order['pay_code']=$res->getOrder()->getPayCode();//支付码
            $order['order_status']=$res->getOrder()->getOrderStatus();//订单状态
            $order['delivery_status']=$res->getOrder()->getDeliveryStatus();//物流状态
            $order['pay_status']=$res->getOrder()->getPayStatus();//支付状态
            $order['returning_status']=$res->getOrder()->getReturningStatus();//退货状态
            $order['returning_delivery_status']=$res->getOrder()->getReturningDeliveryStatus();///退货物流状态
            $order['delivery_id']=$res->getOrder()->getDeliveryId();//物流id
            $order['returning_delivery_id']=$res->getOrder()->getReturningDeliveryId();//退货物流id
            $order['currency']=$res->getOrder()->getCurrency();//订单货币类型
            $order['order_amount']=round($res->getOrder()->getOrderAmount(),2);//订单金额
            $order['delivery_amount']=round($res->getOrder()->getDeliveryAmount(),2);//运费金额
            $order['promo_amount']=round($res->getOrder()->getPromoAmount(),2);//优惠金额
            $order['total_amount']=round($res->getOrder()->getTotalAmount(),2);//订单总金额
            $order['consignee']=$res->getOrder()->getReceiver();//收件人名称
            $order['receiver_location']=$res->getOrder()->getReceiverLocation();//收件人地区id
            $order['address_info'] =$areamap->getAddrstr($order['receiver_location']);
            $address =explode(',',$order['address_info']);
            $order['province'] = $address[0];
            $order['city'] = $address[1];
            $order['district'] = $address[2];
            $order['address']=$res->getOrder()->getReceiverAddress();//收件人详细地址
            $order['receiver_phone']=$res->getOrder()->getReceiverPhone();//收件人电话
            $order['sms_notify']=$res->getOrder()->getSmsNotify();//是否短信通知收件人
            $order['add_time']=$res->getOrder()->getOderDate()?$res->getOrder()->getOderDate()->getSeconds():0;//下单时间
            $order['pay_date']=$res->getOrder()->getPayDate()?$res->getOrder()->getPayDate()->getSeconds():0;//支付时间
            $order['shipping_date']=$res->getOrder()->getShippingDate()?$res->getOrder()->getShippingDate()->getSeconds():0;//发货时间
            $order['receipted_date']=$res->getOrder()->getReceiptedDate()?$res->getOrder()->getReceiptedDate()->getSeconds():0;//签收时间
            $order['invoice_title']=$res->getOrder()->getInvoiceTitle();//发票抬头

            $order['shipping_type'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getShippingName():'';//物流公司

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

            }

        }else{
            $this->redirect(U('order_list'));
        }


//        $order_info = set_btn_order_status($order);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
//        if (!$order_info) {
//            $this->error('没有获取到订单信息');
//            exit;
//        }dump($order_info);
        //获取订单商品
        /*        $model = new UsersLogic();
                $data = $model->get_order_goods($order_info['order_id']);
                $order_info['goods_list'] = $data['result'];
                //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];

                $region_list = get_region_list();
                $invoice_no = M('DeliveryDoc')->where("order_id", $id)->getField('invoice_no', true);
                $order_info[invoice_no] = implode(' , ', $invoice_no);
                //获取订单操作记录
                $order_action = M('order_action')->where(array('order_id' => $id))->select();*/
        $this->assign('order_status', C('ORDER_STATUS'));
        $this->assign('shipping_status', C('SHIPPING_STATUS'));
        $this->assign('pay_status', C('PAY_STATUS'));
//        $this->assign('region_list', $region_list);
        $this->assign('order_info', $order);
//        $this->assign('order_action', $order_action);

        if (I('waitreceive')) {  //待收货详情
            return $this->fetch('wait_receive_detail');
        }
        return $this->fetch();
    }

    public function express()
    {
        /*$order_id = I('get.order_id/d', 195);
        $order_goods = M('order_goods')->where("order_id", $order_id)->select();
        $delivery = M('delivery_doc')->where("order_id", $order_id)->find();
        $this->assign('order_goods', $order_goods);
        $this->assign('delivery', $delivery);
        return $this->fetch();*/
    }

    /*
     * 取消订单
     */
    public function cancel_order()
    {
        $order_id = I('get.order_id/d');
        //检查是否有积分，余额支付
        /*$logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id,$id);
        if($data['status'] < 0)
            $this->error($data['msg']);
        $this->success($data['msg']);*/
        $client = GRPC(Trade);
        $ordid = Trade(OrdId);
        $ordid->setOrderId((int)$order_id);
        list($res,$status) = $client->CancelOrder($ordid)->wait();
        $row = $res->getValue();
        if(!$row)
            $this->error("取消失败");
        $this->success('取消成功');
    }

    /*
     * 用户地址列表
     */
    public function address_list()
    {
        /*$address_lists = get_user_address_list($this->user_id);
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        $this->assign('lists', $address_lists);
        return $this->fetch();*/
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
            $arr[$k]['district'] = $address[2];
            $arr[$k]['address'] = $v->getAddress();
            $arr[$k]['consignee'] = $v->getName();
            $arr[$k]['post_code'] = $v->getPostCode();
            $arr[$k]['mobile'] = $v->getPhone();
            $arr[$k]['is_default'] = $v->getIsDefault();

        }
        $this->assign('lists',$arr);
        $this->assign('item_id',I('item_id'));
        return $this->fetch();
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, I('post.'));
            if ($data['status'] != 1)
                $this->error($data['msg']);
            elseif (I('post.source') == 'cart2') {
                header('Location:' . U('/Mobile/Cart/cart2', array('address_id' => $data['result'],'item_id'=>I('item_id'))));
                exit;
            }

            $this->success($data['msg'], U('/Mobile/User/address_list',array('item_id'=>I('item_id'))));
            exit();
        }
        $areamap= new \area\area();
        $p = $areamap->getProv();
        $this->assign('province', $p);
        $this->assign('item_id', I('item_id'));
//        return $this->fetch('edit_address');
        return $this->fetch();

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('get.id/d');

        $address = new Psp\Member\AddressId();
        $address->setAddressId($id);
        list($reply,$status) = GRPC('member')->GetUserEditAddress($address)->wait();
        $areamap= new \area\area();
        $arr['address_id'] = $reply->getAddressId();
        $arr['location_code'] = $reply->getLocationCode();
        $arr['address_info'] =$areamap->getAddrstr($arr['location_code']);
        $address =explode(',',$arr['address_info']);
        $arr['province'] = $address[0];
        $arr['city'] = $address[1];
        $arr['district'] = $address[2];
        $arr['consignee'] = $reply->GetName();
        $arr['address'] = $reply->getAddress();
        $arr['zipcode'] = $reply->getPostCode();
        $arr['mobile'] = $reply->getPhone();
        $arr['is_default'] = $reply->getIsDefault();

        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($_POST['source'] == 'cart2') {
                header('Location:' . U('/Mobile/Cart/cart2', array('address_id' => $id,'item_id'=>I('item_id'))));
                exit;
            } else
                $this->success($data['msg'], U('/Mobile/User/address_list',array('item_id'=>I('item_id'))));
            exit();
        }
        $this->assign('address', $arr);
        $this->assign('item_id', I('item_id'));
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $id = I('get.id/d');
        $source = I('get.source');
        $address_id = new Psp\Member\SetDefault();
        $address_id->setUserId($this->user_id);
        $address_id->setAddressId($id);
        list($res,$status) = GRPC('member')->SetDefaultAddress($address_id)->wait();
        $row=$res->getValue();
        if($row==true){
            if ($source == 'cart2') {
                header("Location:" . U('Mobile/Cart/cart2'));
                exit;
            } else {
                header("Location:" . U('Mobile/User/address_list'));
            }
        }
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('get.id/d');

        $address_id = new Psp\Member\AddressId();
        $address_id->setAddressId($id);
        $row = GRPC('member')->DelUserAddress($address_id)->wait();

        if(!$row)
            $this->error('操作失败',U('User/address_list'));
        else
            $this->success("操作成功",U('User/address_list'));
    }

    /*
     * 评论晒单
     */
    public function comment()
    {
        $user_id = $this->user_id;
        $status = I('get.status');
        $logic = new \app\common\logic\CommentLogic;
        $result = $logic->getComment($user_id, $status); //获取评论列表
        $this->assign('comment_list', $result['result']);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_comment_list');
            exit;
        }
        return $this->fetch();
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
        if (IS_POST) {

            $userLogic = new UsersLogic();
            /*$user_info = $userLogic->get_info($this->user_id); // 获取用户信息
            $user_info = $user_info['result'];*/
            $add['goods_id'] = I('goods_id/d');
            $add['mobile'] = $this->phone;
            $hide_username = I('hide_username');
            if (empty($hide_username)) {
                $add['username'] = $this->nickname;
            }
            $add['is_anonymous'] = $hide_username;  //是否匿名评价:0不是\1是
            $add['order_id'] = I('order_id/d');
            $add['service_rank'] = I('service_rank');
            $add['deliver_rank'] = I('deliver_rank');
            $add['goods_rank'] = I('goods_rank');
            $add['is'] = I('goods_rank');
            //$add['content'] = htmlspecialchars(I('post.content'));
            $add['content'] = I('content');
            $add['add_time'] = time();
            $add['ip_address'] = request()->ip();
            $add['user_id'] = $this->user_id;
            //添加评论
            $row = $userLogic->add_comment($add);
            if ($row['status'] == 1) {
                $this->success('评论成功', U('/Mobile/User/comment', array('status'=>1)));
                exit();
            } else {
                $this->error($row['msg']);
            }
        }
        /* $rec_id = I('rec_id/d');
        $order_goods = M('order_goods')->where("rec_id", $rec_id)->find();*/
        $order_id = I('order_id/d');
        $goods_id = I('goods_id/d');

        if (empty($order_id) || empty($goods_id)) {
            $this->error("参数错误");
        } else {
            $client = GRPC('AdminOrder');
            $user = new Psp\Trade\AdminOrderId();
            $user->setOrderId($order_id);
            list($res,$status) = $client->GetOrderInfo($user)->wait();
            $arr['order_id']=$res->getOrder()->getOrderId();//订单id
            $arr['order_sn']=$res->getOrder()->getOrderSn();//订单编号
            $arr['goods_price']=round($res->getOrder()->getTotalAmount(),2);//订单总金额
            $arr['oder_date']=$res->getOrder()->getOderDate()?$res->getOrder()->getOderDate()->getSeconds():0;//下单时间
            $arr['goods_id'] = $goods_id;
            foreach($res->getOrderItem()as $k=>$v){
                $goods[$k]['name']=$v->getName();//商品名称

            }
            $arr['goods_name'] = $goods[$k]['name'];
            $this->assign('order_goods', $arr);
            return $this->fetch();
        }

        return $this->fetch();
    }

    /*
     * 个人信息
     */
    public function userinfo()
    {
        $userLogic = new UsersLogic();
        $user_data = $userLogic->get_info($this->user_id);
        $user_data = $user_data['result'];

        if (IS_POST) {

            $files = request()->file('head_pic');
            if($files){
                $save_url = UPLOAD_PATH.'head_pic/' . date('Y', time()) . '/' . date('m-d', time());
                $ossConfig = tpCache('oss');
                if ($ossConfig['oss_switch']) {
                    $ossClient = new \app\common\logic\OssLogic;
                    //oss上传
                    $object = $save_url.md5(microtime(true).rand(9999,99999)).'.'.pathinfo($files->getInfo('name'), PATHINFO_EXTENSION);
                    $post['head_pic'] = $ossClient->uploadFile($files->getRealPath(), $object);
                    @unlink($files->getRealPath());

                } else {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $image_upload_limit_size = config('image_upload_limit_size');
                    $info = $files->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' =>
                        'jpg,png,gif,jpeg'])->move($save_url);
                    if ($info) {
                        // 成功上传后 获取上传信息 // 输出 jpg
                        $post['head_pic'] = '/' . $save_url . '/' . $info->getFilename();
                    } else {
                        // 上传失败获取错误信息
                        $this->error($files->getError());
                    }
                }
            }
            $post['nickname'] = I('post.nickname') ? I('post.nickname') : $user_data['nickname'];//昵称
            $post['head_pic'] = $post['head_pic'] ? $post['head_pic'] : $user_data['head_pic'];//头像地址
            $post['mobile'] = I('post.mobile') ? I('post.mobile') : $user_data['mobile'];//手机
            $post['sex'] = I('post.sex') ? I('post.sex') : $user_data['sex'];//性别
            $post['birthday'] = I('post.birthday') ? strtotime(I('post.birthday')) : $user_data['birthday'];//生日
//            $post['email'] = I('post.email') ? strtotime(I('post.email')) : $user_data['email'];//邮箱
            $post['qq'] = I('post.qq') ? I('post.qq') : $user_data['qq'];

            $email = I('post.email');
            $mobile = I('post.mobile');
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 6);

            if (!empty($email)) {
                //$c = M('users')->where(['email' => input('post.email'), 'user_id' => ['<>', $this->user_id]])->count();
                //$c && $this->error("邮箱已被使用");
            }
            if (!empty($mobile)) {
                //$c = M('users')->where(['mobile' => input('post.mobile'), 'user_id' => ['<>', $this->user_id]])->count();
                //$c && $this->error("手机已被使用");
                if (!$code)
                    $this->error('请输入验证码');
                $check_code = $userLogic->check_validate_code($code, $mobile, 'phone', $this->session_id, $scene);
                if ($check_code['status'] != 1)
                    $this->error($check_code['msg']);
            }

            if (!$userLogic->update_info($this->user_id, $post))
                $this->error("保存失败");
            setcookie('uname',urlencode($post['nickname']),null,'/',get_host());
            $this->success("操作成功");
            exit;
        }
        /*  //  获取省份
          $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = M('region')->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
        //  获取订单地区
        $area = M('region')->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('user', $user_info);*/
        $this->assign('user',$user_data);
        $this->assign('sex', C('SEX'));
        //从哪个修改用户信息页面进来，
        $dispaly = I('action');
        if ($dispaly != '') {
            return $this->fetch("$dispaly");
        }
        return $this->fetch();
    }


    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $mobile || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('mobile_code', null);
                    session('mobile_step1', null);
                    if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                        $this->error('手机已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('mobile_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/mobile_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /**
     * 用户收藏列表
     */
    public function collect_list()
    {
        $p = I('p/d', 1);
        $uid = $this->user_id;
        $logic = new UsersLogic();
        $result = $logic->user_goods_collect($this->user_id,$p);
//        $this->assign('page', $data['show']);// 赋值分页输出
        if (IS_AJAX) {      //ajax加载更多
            return $this->fetch('ajax_collect_list');
            exit;
        }
        $this->assign('page', $result['show']);
        $this->assign('active','goods_collect');
        $this->assign('goods_list', $result['list']);
        return $this->fetch();
    }

    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $item_id = I('collect_id/d');
        if(!$item_id)
            $this->error("缺少ID参数");
        $uid = $this->user_id;
        $item = new Psp\Member\ItemId();
        $item->setUserId($uid);
        $item->setItemId($item_id);
        $row = GRPC('member')->DelFavoriteItem($item)->wait();
        if(!$row)
            $this->error("删除失败");
        $this->success('删除成功');
    }

    /**
     * 我的留言
     */
    public function message_list()
    {
        C('TOKEN_ON', true);
        if (IS_POST) {
            $this->verifyHandle('message');

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $user = session('user');
            $data['user_name'] = $user['nickname'];
            $data['msg_time'] = time();
            /*if (M('feedback')->add($data)) {
                $this->success("留言成功", U('User/message_list'));
                exit;
            } else {
                $this->error('留言失败', U('User/message_list'));
                exit;
            }*/
        }
        $msg_type = array(0 => '留言', 1 => '投诉', 2 => '询问', 3 => '售后', 4 => '求购');
        //$count = M('feedback')->where("user_id", $this->user_id)->count();
        //$Page = new Page($count, 100);
        //$Page->rollPage = 2;
        //$message = M('feedback')->where("user_id", $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$showpage = $Page->show();
        header("Content-type:text/html;charset=utf-8");
        //$this->assign('page', $showpage);
        //$this->assign('message', $message);
        $this->assign('msg_type', $msg_type);
        return $this->fetch();
    }

    /**账户明细*/
    public function points()
    {
        $type = I('type', 'all');    //获取类型
        $this->assign('type', $type);
        if ($type == 'recharge') {
            //充值明细
            /*$count = M('recharge')->where("user_id", $this->user_id)->count();
            $Page = new Page($count, 16);
            $account_log = M('recharge')->where("user_id", $this->user_id)->order('order_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();*/
        } else if ($type == 'points') {
            //积分记录明细
           /* $count = M('account_log')->where(['user_id' => $this->user_id, 'pay_points' => ['<>', 0]])->count();
            $Page = new Page($count, 16);
            $account_log = M('account_log')->where(['user_id' => $this->user_id, 'pay_points' => ['<>', 0]])->order('log_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();*/
        } else {
            //全部
            /*$count = M('account_log')->where(['user_id' => $this->user_id])->count();
            $Page = new Page($count, 16);
            $account_log = M('account_log')->where(['user_id' => $this->user_id])->order('log_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();*/
        }
        //$showpage = $Page->show();
        //$this->assign('account_log', $account_log);
        //$this->assign('page', $showpage);
        //$this->assign('listRows', $Page->listRows);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_points');
            exit;
        }
        return $this->fetch();
    }

    /*
     * 密码修改
     */
    public function password()
    {

        if ($this->mobile == '') {
            $this->error('请先绑定手机号', U('Mobile/User/userinfo'));
        }
        if (IS_POST) {
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id, I('post.old_password'), I('post.new_password'), I('post.confirm_password')); // 获取用户信息
            if ($data['status'] == -1) {
                $this->error($data['msg']);
            }
            return $this->success($data['msg'], U('Mobile/User/index'));
        }

        $this->assign('has_password', $user['password'] !== '');
        return $this->fetch();
    }

    function forget_pwd()
    {
        if ($this->user_id > 0) {
            $this->redirect("User/index");
        }
        $username = I('username');
        if (IS_POST) {
            if (!empty($username)) {
                $this->verifyHandle('forget');
                $field = 'mobile';
                if (check_email($username)) {
                    $field = 'email';
                }
                $user_id = new Psp\Member\SavePwd();
                $user_id->setMemberAccount($username);
                list($res,$status) = GRPC('member')->UpdateUserPassword($user_id)->wait();
                $arr['id'] = $res->getTpsinfo()->getId();
                $arr['name'] =$res->getTpsinfo()->getName();
                $arr['email'] =$res->getTpsinfo()->getEmail();
                $arr['tps13_id']=$res->getTpsinfo()->getTps138Id();
                $arr['phone']=$res->getTpsinfo()->getPhone();
                $arr['pwd']=$res->getPwd()->getPwd();
                if ($arr) {
                    cookie('find_password', array('user_id' => $arr['user_id'], 'username' => $username,
                        'email' => $arr['email'], 'mobile' => $arr['phone'], 'type' => $field));
                    header("Location: " . U('User/find_pwd'));
                    exit;
                } else {
                    $this->error("用户名不存在，请检查");
                }
            }
        }
        return $this->fetch();
    }

    function find_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/index'));
        }
        $user = cookie('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('user', $user);
        return $this->fetch();
    }


    public function set_pwd()
    {
        if ($this->user_id > 0) {
            $this->redirect('Mobile/User/index');
        }
        $check = cookie('validate_code');
        if (empty($check)) {
            header("Location:" . U('User/forget_pwd'));
        } elseif ($check['is_check'] == 0) {
            $this->error('验证码还未验证通过', U('User/forget_pwd'));
        }
        if (IS_POST) {
            $password = I('post.password');
            $password2 = I('post.password2');
            if ($password2 != $password) {
                $this->error('两次密码不一致', U('User/forget_pwd'));
            }
            if ($check['is_check'] == 1) {
                //$user = get_user_info($check['sender'],1);
                $username = $check['sender'];
                $user_id = new Psp\Member\SavePwd();
                $user_id->setMemberAccount($username);
                list($res, $status) = GRPC('member')->UpdateUserPassword($user_id)->wait();
                $arr['id'] = $res->getTpsinfo()->getId();

                $uid = new Psp\Member\Uid();
                $uid->setUid($arr['id']);
                list($res, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
                $token = $res->getToken();
                $newpwd = sha1('!#*' . trim("$password") . $token . 'tps');

                $pwd = new Psp\Member\SetPassword();
                $pwd->setUserId($arr['id']);
                $pwd->setNewPassword($newpwd);
                list($res, $status) = GRPC('member')->SetNewPassword($pwd)->wait();
                $row = $res->getValue();
                if ($row == true) {
                    cookie('validate_code', null);
                    $this->success('新密码已设置,请牢记新密码', U('User/index'));
                    exit;
                } else {
                    $this->error('验证码还未验证通过', U('User/forget_pwd'));
                }
                //header("Location:".U('User/set_pwd',array('is_set'=>1)));

            }
        }
        $is_set = I('is_set', 0);
        $this->assign('is_set', $is_set);
        return $this->fetch();
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Captcha();
        $result = $verify->check(I('post.verify_code'), $id ? $id : 'user_login');
        if (!$result) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 35,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => true,
            'reset' => false,
            'fontttf' => '4.ttf',
            'codeSet' => '0123456789'
        );
        $Verify = new Captcha($config);
        return $Verify->entry($type);
//        exit();
    }

    /**
     * 账户管理
     */
    public function accountManage()
    {
        return $this->fetch();
    }

    /**
     * 确定收货成功
     */
    public function order_confirm()
    {
        $id = I('get.id/d',0);
        $data = confirm_order($id,$this->user_id);
        if($data['status'] != 1){
            $this->error($data['msg'] ,U('User/order_list'));
        } else  {
            //transferSplit($id); //订单确认收货,接口调用这个方法,传订单id,执行资金拆分,无返回
            $this->success($data['msg'],U('User/order_list'));
        }
    }

    /**
     * 申请退货
     */
    public function return_goods()
    { $order_item_id = I('order_item_id/d',0);
        $client = GRPC(Trade);
        $order = Trade(OrderItemId);
        $order->setOrderItemId($order_item_id);
        list($res,$status) = $client->GetOrderItem($order)->wait();
        $goods['name'] = $res->getTitle();
        $goods['price'] = $res->getPrice();
        $goods['count'] = $res->getCount();
        $goods['address'] = $res->getAddress();
        $goods['order_item_id'] = $res->getOrderItemId();
        $goods['order_id'] = $res->getOrderId();
        $goods['item_id'] = $res->getItemId();
        $goods['thumb_img_url'] = $res->getThumbImgUrl();
        $goods['shipping_status'] = $res->getShippingStatus();
        $code = $res->getReturnMessage()->getRet();
        $reason = $res->getReturnMessage()->getMsg();
        if($code == 'fail'){
            $this->error($reason,U('mobile/User/return_goods_list'));
        }

        if(IS_POST)
        {
            $order_item_id = I('order_item_id/d');
            $count = I('goods_num/d');
            $reason = I('reason');
            $type = I('type');
            $order_id = I('order_id/d');
            $shipping_status = I('shipping_status/d')==1?0:1;
            $return = Trade(ReturnBaseInfo);
            $return->setOrderItemId($order_item_id);
            $return->setCount($count);
            $return->setReason($reason);
            $return->setType($type);

            list($res,$status) = $client->AddReturnGoods($return)->wait();
            $result = $res->getValue();
            if($result){
                //returnOrderTransfer($order_id, $shipping_status); //退货订单资金处理
                $this->success('申请成功,客服第一时间会帮你处理',U('mobile/User/return_goods_list'));
                exit;
            }

        }
        //dump($goods);
        $this->assign('goods',$goods);
        return $this->fetch();
    }

    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
        $where = " user_id=$this->user_id ";
        $where = "user_id = 1";
        // 搜索订单 根据商品名称 或者 订单编号
        $search_key = trim(I('search_key'));
        if($search_key)
        {
            $where .= " and order_sn=$search_key";
        }

        $p = I('p/d', 1);

        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("order_id");
        $page->setIndex(1);
        $page->setLimit(3);
        $uid = $this->user_id;
        $order = new Psp\Member\GetPage();
        $order->setUid($uid);
        $order->setPagination($page);
        list($res,$status) = GRPC('member')->GetReturnOrderList($order)->wait();

        foreach ($res->getReturnOrderList() as $k=>$v) {
            $arr[$k]['order_id'] = $v->getOrderId();
            $arr[$k]['order_sn'] = $v->getOrderSn();
            $arr[$k]['goods_name'] = $v->getGoodsName();
            $arr[$k]['add_time'] = $v->getReturnTime()->getSeconds();
            $arr[$k]['reason'] = $v->getReason();
            $arr[$k]['status'] = $v->getStatus();
        }

        //总条数
        $total_count = $res->getPageResult()->getTotalRecords();

//        //总页数
        $Page = new Page($total_count, 3);
        $show = $Page->show();


        $this->assign('list', $arr);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     *  退货详情
     */
    public function return_goods_info()
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
        $return_info['shipping_time'] = $res->getShippingTime()?$res->getShippingTime()->getSeconds():'';
        $return_info['add_time'] = $res->getOrderDate()?$res->getOrderDate()->getSeconds():'';
        $this->assign('return_goods',$return_info);
        return $this->fetch();
    }


    public function recharge()
    {
        $this->error('该功能暂不可用!');exit;
        $order_id = I('order_id/d');
        //$paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,1)")->select();
        //微信浏览器
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //$paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code='weixin'")->select();
        }
       // $paymentList = convert_arr_key($paymentList, 'code');

       /* foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }*/
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        //$payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        //$this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
       // $this->assign('bankCodeList', $bankCodeList);

        if ($order_id > 0) {
           // $order = M('recharge')->where("order_id", $order_id)->find();
            //$this->assign('order', $order);
        }
        return $this->fetch();
    }

    /**
     * 申请提现记录
     */
    public function withdrawals()
    {

        C('TOKEN_ON', true);
        /**目前只支持会员 余额提现**/
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $user['paypwd'] = $resp->getPwdWithdraw();//支付密码
        $token = $resp->getToken();
        //账户余额 //获取 账户余额  账户可提现收益
        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM);
        $account_info1 = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        $money = $account_info['balance'];//收益
        $user['user_money'] =sprintf("%.2f",$money);
        $user['user_money1'] =sprintf("%.2f",$account_info1['balance']);

        if (IS_POST) {

            if(!$this->verifyHandle('withdrawals')){
                $this->ajaxReturn(['status'=>-1,'msg'=>'图像验证码错误']);
            };

            $data = I('post.');
            if($data['with_type'] == 1){
                $user_money = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM)['balance'];
                $type = 3;
            }else{
                $user_money = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM)['balance'];
                $type = 1;
            }
            $distribut_min = 100;//最少提现额度

            /*if(encrypt($data['paypwd']) != $this->user['paypwd']){
                $this->ajaxReturn(['status'=>-2,'msg'=>'支付密码错误']);
            }*/

            if ($data['money'] < $distribut_min) {
                $this->ajaxReturn(['status'=>3,'msg'=>'每次最少提现额度' . $distribut_min]);
            }
            if ($data['money'] > $user_money) {
                $this->ajaxReturn(['status'=>-4,'msg'=>"你最多可提现 {$user['user_money']}"]);
            }

            $payPwd = sha1('!#*' . md5(trim($data['paypwd'])).$token);
            if($payPwd != $user['paypwd']){
                $this->ajaxReturn(['status'=>-3,'msg'=>"支付密码错误"]);
            }

            /*$withdrawal = M('withdrawals')->where(array('user_id' => $this->user_id, 'status' => 0))->sum('money');
            if ($this->user['user_money'] < ($withdrawal + $data['money'])) {
                $this->ajaxReturn(['status'=>-5,'msg'=>'您有提现申请待处理，本次提现余额不足']);
            }*/

            //调用账务中心提现接口
            $res = $user_account->addWithdraw($type,$this->user_id,$data['money'],1,$data['account_name'],$data['account_bank'],0);
            if ($res['ret'] == 'ok') {
                $this->ajaxReturn(['status'=>1,'msg'=>"已提交申请",'url'=>U('User/withdrawals_list')]);
            } else {
                $this->ajaxReturn(['status'=>-6,'msg'=>"{$res['msg']}"]);
            }
        }


        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
            exit;
        }

        $this->assign('user',$user);    //用户余额
        return $this->fetch();
    }

    /**
     * 提现记录列表
     */
    public function withdrawals_list()
    {
        $p = I('p/d',1);
        $user = new UsersLogic();
        $result = $user->get_withdrawals_log($this->user_id,$p,0);
        $this->assign('page',$result['show']);// 赋值分页输出
        $this->assign('list', $result['result']); //
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
            exit;
        }
        return $this->fetch();
    }

    /**
     * 会员转账 会员->会员
     */
    public function transfer()
    {
        //获取账户信息
        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        $money = $account_info['balance'];//余额
        $shouyi = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM);
        $my_account_id = (int)$account_info['account_id'];
        $user['user_money'] =sprintf("%.2f",$money);
        $user['shouyi'] =sprintf("%.2f",$shouyi['balance']);
        $user['user_id'] = $this->user_id;
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $user['paypwd'] = $resp->getPwdWithdraw();//支付密码
        $token = $resp->getToken();
        unset($resp);
        if(IS_POST){
//            exit(json_encode(array('status'=>-100,'msg'=>'该功能暂未开放,敬请期待')));

            $verify_code = I('post.verify_code');
            $his_138_id = trim(I('post.account_id/s','0'));//138id
            $money= I('post.money');
            $paypwd = I('post.paypwd');
            $type = I('with_type');  //1余额转余额    3收益转余额(扣除3%手续费)

//            $verify = new Verify();
//            if (!$verify->check($verify_code,'transfer'))
//            {
//                $this->error('验证码错误!');
//                exit;
//            }
            if($money < 10){
                $this->ajaxReturn(['status'=>-5,'msg'=>'转账金额太少了']);
            }

            if($type==1){
                if((float)$money > (float)$user['user_money']){
                    $this->ajaxReturn(['status'=>-9,'msg'=>"账户余额不足"]);
                }
            }else{
                if((float)$money > (float)$user['shouyi']){
                    $this->ajaxReturn(['status'=>-1,'msg'=>"账户余额不足"]);
                }
            }

            $payPwd = sha1('!#*' . md5(trim($paypwd)).$token);
            if($payPwd != $user['paypwd']){
                $this->ajaxReturn(['status'=>-3,'msg'=>"支付密码错误"]);
            }
            //根据138id/wh181_id 取出对方用户id
            $logic_id = new AccountCenterLogic();
            $his_uid = $logic_id->getUid($his_138_id);//转入方id
            //取出对方的账户id
            if($type==1){//余额转余额
                $his_account = $user_account->getAccount($his_uid,1,0,(int)PLATFORM);
                $my_account_id = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
                $transfer_type  = 1;
            }elseif ($type==3){//收益转余额
                $his_account = $user_account->getAccount($his_uid,1,0,(int)PLATFORM);
                $my_account_id = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM);
                $transfer_type  = 5;
            }
            //$his_account = $user_account->getAccount($his_uid,4,11,(int)PLATFORM);
            if($my_account_id['account_id'] == $his_account['account_id']){
                $this->ajaxReturn(['status'=>-2,'msg'=>"对不起,不能给自己转账"]);
            }
            //执行转账
            $res = $user_account->addTransfer($my_account_id['account_id'],(int)$his_account['account_id'],
                $money,$transfer_type);//调用转账接口
            if($res['state'] ==1){
                $this->ajaxReturn(['status'=>1,'msg'=>"转账成功",'url'=>U('User/transfer_list')]);
            }else{
                $this->ajaxReturn(['status'=>-6,'msg'=>"{$res['msg']}"]);//失败
            }

        }

        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
    **转账记录列表
     */
    public function transfer_list(){
        $p = I('p/d',1);
        $user = new UsersLogic();
        $result = $user->get_transfer($this->user_id,$p,0);
        $this->assign('page',$result['show']);
        $this->assign('list',$result['result']);
        if(I('is_ajax')){
            return $this->fetch('ajax_transfer_list');
            exit;
        }

        return $this->fetch();
    }


    /**
     * 删除已取消的订单
     */
    public function order_del()
    {
        $user_id = $this->user_id;
        $order_id = I('get.order_id/d');
        /*$order = M('order')->where(array('order_id' => $order_id, 'user_id' => $user_id))->find();
        if (empty($order)) {
            return $this->error('订单不存在');
            exit;
        }*/
        /*$res = M('order')->where("order_id=$order_id and order_status=3")->delete();
        $result = M('order_goods')->where("order_id=$order_id")->delete();
        if ($res && $result) {
            return $this->success('成功', "mobile/User/order_list");
            exit;
        } else {
            return $this->error('删除失败');
            exit;
        }*/
    }

    /**
     * 我的关注
     * @author lxl
     * @time   2017/1
     */
    public function myfocus()
    {
        return $this->fetch();
    }

    /**
     * 待收货列表
     * @author lxl
     * @time   2017/1
     */
    public function wait_receive()
    {
        $where = ' user_id=' . $this->user_id;
        //条件搜索
        if (I('type') == 'WAITRECEIVE') {
            $where .= C(strtoupper(I('type')));
        }
        //$count = M('order')->where($where)->count();
        $pagesize = C('PAGESIZE');
        //$Page = new Page($count, $pagesize);
        //$show = $Page->show();
        $order_str = "order_id DESC";
        //$order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //获取订单商品
        $model = new UsersLogic();
        /*foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }*/

        //统计订单商品数量
//        foreach ($order_list as $key => $value) {
//            $count_goods_num = '';
//            foreach ($value['goods_list'] as $kk => $vv) {
//                $count_goods_num += $vv['goods_num'];
//            }
//            $order_list[$key]['count_goods_num'] = $count_goods_num;
//            //订单物流单号
//            $invoice_no = M('DeliveryDoc')->where("order_id", $value['order_id'])->getField('invoice_no', true);
//            $order_list[$key][invoice_no] = implode(' , ', $invoice_no);
//        }
        //$this->assign('page', $show);
        //$this->assign('order_list', $order_list);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_wait_receive');
            exit;
        }
        return $this->fetch();
    }

    /**
     *  用户消息通知
     * @author dyr
     * @time 2016/09/01
     */
    public function message_notice()
    {
        return $this->fetch();
    }

    /**
     * ajax用户消息通知请求
     * @author dyr
     * @time 2016/09/01
     */
    public function ajax_message_notice()
    {
        $type = I('type',0);
        $user_logic = new UsersLogic();
        $message_model = new Message();

        //系统消息
        $user_sys_message = $message_model->getUserMessageNotice();

        $this->assign('page',$user_sys_message['show']);
        $this->assign('messages', $user_sys_message['result']);
        return $this->fetch();

    }
    /**
     * 删除消息
     */
    public function del_message()
    {
        $id = I('post.message_id/d');
        $msg = new Psp\Member\MsgId();
        $msg->setUserId($this->user_id);
        $msg->setMsgId($id);
        list($reply,$status) = GRPC('member')->DelUserMessage($msg)->wait();
        $row = $reply->getValue();
        if($row == true)
            $this->ajaxReturn(['status'=>'1','msg'=>'删除成功']);
        else
            $this->ajaxReturn(['status'=>'-1','msg'=>'删除失败,请重试']);
    }

    /**
     * 设置消息为已读
     */
    public function set_read()
    {
        $id = I('post.message_id/d');
        $msg = new Psp\Member\MsgId();
        $msg->setUserId($this->user_id);
        $msg->setMsgId($id);
        list($reply,$status) = GRPC('member')->SetMessageRead($msg)->wait();
        $this->ajaxReturn(['status'=>'1','msg'=>'设置成功']);
        /*if(!$reply)
            $this->error('操作失败',U('mobile/user/message_notice'));
        else
            $this->success("操作成功",U('mobile/user/message_notice'));*/
    }

    /**
     * 设置消息通知
     */
    public function set_notice(){
        //暂无数据
        return $this->fetch();
    }

    /**
     * 取消售后服务
     * @author lxl
     * @time 2017-4-19
     */
    public function return_goods_cancel(){
        $id = I('id',0);
        if(empty($id))$this->error('参数错误');
        //$return_goods = M('return_goods')->where(array('id'=>$id,'user_id'=>$this->user_id))->find();
        if(empty($return_goods)) $this->error('参数错误');
        //M('return_goods')->where(array('id'=>$id))->save(array('status'=>-2,'canceltime'=>time()));
        $this->success('取消成功',U('User/return_goods_list'));
        exit;
    }

    /**
     * 换货商品确认收货
     * @author lxl
     * @time  17-4-25
     * */
    public function receiveConfirm(){
        //$return_id=I('return_id/d');
        //$return_info=M('return_goods')->field('order_id,order_sn,goods_id,spec_key')->where('id',$return_id)->find(); //查找退换货商品信息
        //$update = M('return_goods')->where('id',$return_id)->save(['status'=>3]);  //要更新状态为已完成
        /*if($update) {
            M('order_goods')->where(array(
                'order_id' => $return_info['order_id'],
                'goods_id' => $return_info['goods_id'],
                'spec_key' => $return_info['spec_key']))->save(['is_send' => 2]);  //订单商品改为已换货
            $this->success("操作成功", U("User/return_goods_info", array('id' => $return_id)));
        }*/
        //$this->error("操作失败");
    }

    /**
     * 浏览记录
     */
    public function visit_log()
    {
        /*$count = M('goods_visit')->where('user_id', $this->user_id)->count();
        $Page = new Page($count, 20);
        $visit = M('goods_visit')->alias('v')
            ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id')
            ->join('__GOODS__ g', 'v.goods_id=g.goods_id')
            ->where('v.user_id', $this->user_id)
            ->order('v.visittime desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();*/

        /* 浏览记录按日期分组 */
        //$curyear = date('Y');
        $visit_list = [];
        /*foreach ($visit as $v) {
            if ($curyear == date('Y', $v['visittime'])) {
                $date = date('m月d日', $v['visittime']);
            } else {
                $date = date('Y年m月d日', $v['visittime']);
            }
            $visit_list[$date][] = $v;
        }*/

        $this->assign('visit_list', $visit_list);
        if (I('get.is_ajax', 0)) {
            return $this->fetch('ajax_visit_log');
        }
        return $this->fetch();
    }

    /**
     * 删除浏览记录
     */
    public function del_visit_log()
    {
        /*$visit_ids = I('get.visit_ids', 0);
        $row = M('goods_visit')->where('visit_id','IN', $visit_ids)->delete();

        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }*/
    }

    /**
     * 清空浏览记录
     */
    public function clear_visit_log()
    {
        /*$row = M('goods_visit')->where('user_id', $this->user_id)->delete();

        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }*/
    }

    /**我的会员***/
    public function myMembers(){
        $p = I('p/d',1);
        $limit =10;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("uid");
        $page->setIndex($p);
        $page->setLimit($limit);
        $getPage = new Psp\Member\GetPage();
        $getPage->setUid($this->wh181_id);
        $getPage->setPagination($page);
        list($resp,$status) = GRPC('member')->GetMyMembers($getPage)->wait();
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        if(!empty($resp)){
            foreach ($resp->getMyMember() as $k=>$v){
                $data[$k]['user_id'] = $v->getUserId();
                $data[$k]['wh181_id'] = $v->getWh181Id();
                $data[$k]['mobile'] = $v->getMobile();
                $add_time = explode(':',$v->getAddTime());
                $data[$k]['add_time'] = trim($add_time[1]);
            }
        }
        $this->assign('list',$data);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_myMembers');
            exit;
        }
        $this->assign('user',$user['result']);
        return $this->fetch();
    }

    /***我的会员 详情**/
    public function myMemberInfo(){
        $user_id = I('post.user_id/d',0);
        $uid = new Psp\Member\Uid();
        $uid->setUid($user_id);
        list($resp,$status) = GRPC('member')->GetMyMemberInfo($uid)->wait();
        $info['user_id'] = $resp->getUserId();
        $info['wh181_id'] = $resp->getWh181Id();
        $info['mobile'] = $resp->getMobile() ? $resp->getMobile() : '暂无';
        $info['real_name'] = $resp->getRealName() ? $resp->getRealName() : '暂无';
        $info['user_level'] = $resp->getUserLevel();
        $info['user_title'] = $resp->getUserTitle();
        $add_time = explode(':',$resp->getAddTime());
        $info['add_time'] = date('Y-m-d H:i',trim($add_time[1]));
        exit(json_encode($info));

    }

    /**
     * 余额支付
     * @author fzq
     * time 2018/03/22
     **/
    public function balance_pay()
    {
//        exit(json_encode(array('status'=>-100,'msg'=>'该功能暂未开放,敬请期待')));
        $paypwd = trim(I('post.pay_pwd/s'));
        $order_amount = I('post.order_amount');//订单金额
        $order_ids = I('post.order_id');//订单号
        //获取用户支付密码
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp,$status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $replyPwd = $resp->getPwdWithdraw();//支付密码
        $token = $resp->getToken();//token安全码

        $pay_pwd = sha1('!#*' . md5($paypwd).$token);
        if($pay_pwd != $replyPwd){
            exit(json_encode(array('status'=>-1,'msg'=>'支付密码不正确')));
        }

        if($order_amount <= 0){
            exit(json_encode(array('status'=>-6,'msg'=>'参数错误')));
        }

        //获取账户可用余额
        $balance = new AccountCenterLogic();
        $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);
        if($order_amount > $accountData['balance']){
            exit(json_encode(array('status'=>-2,'msg'=>'余额不足,请充值')));
        }
        //获取支付号
        $pay_code = create_paycode(1,$order_amount,1,1,$this->user_id,$this->org_id,(int)PLATFORM,$order_ids,'钱包支付',9);
//                echo '111';die;
        if(empty($pay_code))
            exit(json_encode(array('status'=>-5,'支付号获取失败!')));
        $res = $balance->addTransfer($accountData['account_id'],(int)PLATFORM,$order_amount,5);//调用转账接口 5会员->平台
        if($res['state'] ==1){
            //修改订单状态
            update_pay_status($pay_code,$order_amount);
            exit(json_encode(array('status'=>1,'msg'=>'支付成功','url'=>U('User/payOk',array('order_id'=>$order_ids,'order_sn'=>$pay_code,'order_amount'=>$order_amount)))));
        }else{
            exit(json_encode(array('status'=>-3,'msg'=>'支付失败,请仔细检查账户')));
        }

    }


    //买呗支付
    public function buy_pay(){
        $paypwd = trim(I('post.pay_pwd/s'));
        $order_amount = I('post.order_amount');//订单金额
        $order_ids = I('post.order_id');//订单号
        exit(json_encode(array('status'=>-22,'msg'=>'买呗支付暂时关闭,敬请谅解!')));
        //获取用户支付密码
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp,$status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $replyPwd = $resp->getPwdWithdraw();//支付密码
        $token = $resp->getToken();//token安全码

        $pay_pwd = sha1('!#*' . md5($paypwd).$token);
        if($pay_pwd != $replyPwd){
            exit(json_encode(array('status'=>-1,'msg'=>'支付密码不正确')));
        }

        if($order_amount <= 0){
            exit(json_encode(array('status'=>-6,'msg'=>'参数错误')));
        }

        //获取买呗余额
        $buy_info = new UsersLogic();
        $buy_pay = $buy_info->member_buy($this->user_id);
        $buy_account_balance = $buy_pay['can_use_money'];//买呗余额
        if($buy_account_balance < $order_amount){
            exit(json_encode(array('status'=>-2,'msg'=>'买呗余额不足!')));
        }
        //获取支付号
        $pay_code = create_paycode(1,$order_amount,1,1,$this->user_id,$this->org_id,(int)PLATFORM,$order_ids,'买呗支付',10);
        if(empty($pay_code))
            exit(json_encode(array('status'=>-3,'msg'=>'订单已支付或买呗未还款')));
        //扣除买呗账户金额
        $status = $buy_info->updateMemberBalance($this->user_id,$order_amount);
        if($status['ret']=='ok'){
            //修改订单状态
            update_pay_status($pay_code,$order_amount);
            exit(json_encode(array('status'=>1,'msg'=>'支付成功','url'=>U('User/payOk',array('order_id'=>$order_ids,'order_sn'=>$pay_code,'order_amount'=>$order_amount)))));
        }else{
            exit(json_encode(array('status'=>-4,'msg'=>$status['msg'])));
        }

    }

    /**手機余额支付成功跳转页面**/
    public function payOk(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号   小强的pay_code是支付号  此处用订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('payment/success');
    }

    /**手機余额支付失败跳转页面**/
    public function payFail(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('payment/error');
    }

    //修改支付密码
    public function set_paypwd(){
        $step = I('step',1);
        if ($step > 1) {
            if (cookie('verifyed') != 1) {
                $this->error('密码还未验证通过', U('Mobile/User/set_paypwd'));
            }
        }
        if (IS_POST && $step == 2) {
            $userLogic = new UsersLogic();
            $data = I('post.');
            $data = $userLogic->paypwd($this->user_id, $data['new_password'], $data['confirm_password']);
            if ($data['status'] == -1){
                $this->ajaxReturn($data);
            }else{
                $data['url'] = U('Mobile/User/userinfo');
                $this->ajaxReturn($data);
                exit;
            }
        }

        $this->assign('step',$step);
        return $this->fetch();
    }


    //验证 登录密码wap
    public function verifyed_login_secret(){
        $pwd = trim(I('post.code'));
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($res, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $reply_token = $res->getToken();
        $reply_pwd = $res->getPwd();
        $newpwd =sha1('!#*'.trim("$pwd").$reply_token.'tps');
        if($newpwd != $reply_pwd){
            exit(json_encode(['status'=>-1,'msg'=>'登录密码错误']));
        }else{
            setcookie('verifyed','1',0,'/'); //标记通过验证
            exit(json_encode(['status'=>1,'msg'=>'ok']));
        }
    }


    //奖金报表
    public function commission_report(){
        $year = I('get.year','');
        $month = I('get.month','');
        $search_time =$year.$month;
        $time = date('Ym',time()); //当前月
        if($search_time){
            $time = $search_time;
        }
        $logic = new UsersLogic();
        $result = $logic->get_commission_report($this->user_id,$time);//明细

        $year = substr($time,0,4);
        $month = substr($time,4);
        $time =$year.'年'.$month.'月';
        $this->assign('time',$time);
        $this->assign('bonus',$result['bonus']);
        $this->assign('bonus_detail',$result['bonus_detail']);
        return $this->fetch();
    }
    //新建工单
    public function leave_message(){
        if(IS_POST){
            $text=I('post.text');//留言内容
            $wh181ID=I('post.wh181ID');
            $nickname=I('post.nickname');
            //(int) $uid=I('post.uid');
            $mtitle=I('post.mtitle');
            $numbers = rand (1,10000);
            $message_number= 'GD'.$numbers.time('md');
            $client = GRPC('user');
            $user = new Psp\User\SetUserMessage();
           // $user->setUid($uid);//会员id
            $user->setWh181Id($wh181ID);//沃好id
            $user->setName($nickname);//昵称
            $user->setUmessage($text);//会员留言
            $user->setMessageNumber($message_number);//工单编号
            $user->setMessageTitle($mtitle);//工单名
            list($res,$status) = $client->SetLeavingMessage($user)->wait();

            if($res){
                return(1);
            }
        }

        return $this->fetch();
    }
    //我的工单
    public function my_message(){
        $wh181_id=$this->wh181_id;
        $client = GRPC('user');
        $p =I('p/d',1);
        $limit=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("id");
        $page->setIndex($p);
        $page->setLimit($limit);
        $talk=new Psp\User\Wh181Id();
        $talk->setWh181Id($wh181_id);
        $talk->setPageInfo($page);
        $talk->setType('home');
        list($res,$status) = $client->GetUserMessageList($talk)->wait();

        foreach($res->getUserMessageList() as $k=>$v){
           $talklist[$k]['wh181_id']=$v->getWh181Id();
           $talklist[$k]['name']=$v->getName();
           $talklist[$k]['u_message']=$v->getUmessage();
           $talklist[$k]['message_time']=$v->getMessageTime();
           $talklist[$k]['message_number']=$v->getMessageNumber();
           $talklist[$k]['message_title']=$v->getMessageTitle();
           $talklist[$k]['is_talk']=$v->getIsTalk();
        }


         $total_count = $res->getPaginationResult()->getTotalRecords();
        //var_dump($total_count);
        $Page = new Page($total_count,$limit) ;
        $show = $Page->show();

        $this->assign('page',$show);
        $this->assign('talklist',$talklist);
        return $this->fetch();
    }

    public function ajax_my_message(){

            $whid=I('post.whid');

            $is_talk=I('post.is_talk');
            $number=I('post.message_number');

            $client = GRPC('user');
            $talk = new Psp\User\GetLeavingTalks();
            $talk->setWh181Id($whid);
            $talk->setMessageNumber($number);
            //$talk->setPageInfo($page);
            list($res,$status) = $client->GetTalkLists($talk)->wait();

        foreach($res->getTalkLi() as $k=>$v){
            $talks[$k]['u_message']=$v->getUmessage();
            $talks[$k]['k_message']=$v->getKmessage();
            $talks[$k]['message_time']=$v->getMessageTime();
             }

                $text=I('post.texts');
                $wh181id=I('post.whid');
                $message_number=I('post.message_number');
            if($text){
                $client = GRPC('user');
                $user = new Psp\User\SetUserMessage();
                //$user->setUid($uid);//会员id
                $user->setWh181Id($wh181id);//沃好id
                //$user->setName($nickname);//昵称
                $user->setUmessage($text);//会员留言
                $user->setMessageNumber($message_number);//工单编号
                //$user->setMessageTitle($mtitle);//工单名
                list($res,$status) = $client->SetLeavingMessage($user)->wait();
            }
                 //exit(json_encode($talks));
        $this->assign('whid',$whid);
        $this->assign('number',$number);
        $this->assign('talks',$talks);
        $this->assign('is_talk',$is_talk);
        return $this->fetch();
    }
    //我的买呗
    public function my_loan(){
        $type = I('type',1);
        $p = I('p',1);
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp) = GRPC('member')->GetMemberBuyDetail($uid)->wait();
        $status = $resp->getStatus();
        $reason = $resp->getReason();
        $limit_money = $resp->getLimitMoney();//我的额度
        $now_money = $resp->getNowMonthMoney();//本月花费
        $last_money = $resp->getLastMonthMoney();//上月花费
        $last_month = $resp->getLastMonth();//上月时间
        $now_month = $resp->getNowMonth();// 当月时间
        $can_use_money = $limit_money - $now_money - $last_money;
        $repayment_money = $now_money + $last_money; //应还总金额
        $Userlogic = new UsersLogic();
        $result=$Userlogic->MemberBuyUseList($this->user_id,$p,$type);  //明细  1支出 2还款
        //获取账户可用余额
        $balance = new AccountCenterLogic();
        $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);
        $balance = sprintf('%.2f',$accountData['balance']);
        $is_upgrade = 0;
        if($status == 2){
            //判断是否可升级额度
            $money_info =['4'=>1000,'5'=>2000,'6'=>3000];

            //读取会员信息
            $logic = new UsersLogic();
            $user = $logic->get_info($this->user_id);
            $user = $user['result'];
            //可升级金额
            $up_money = $money_info[$user['user_title']];
            if($limit_money < $up_money){
                $is_upgrade = 1;//可以升级额度
            }
        }

        //如果状态不等于 1  会员可以编辑申请
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        $this->assign('can_use_money',$can_use_money);
        $this->assign('limit_money',$limit_money);
        $this->assign('now_money',$now_money);
        $this->assign('last_money',$last_money);
        $this->assign('repayment_money',$repayment_money);//应还总金额
        $this->assign('last_month',$last_month);
        $this->assign('now_month',$now_month);
        $this->assign('balance',$balance);
        $this->assign('reason',$reason);
        $this->assign('status',$status);
        $this->assign('is_upgrade',$is_upgrade);
        return $this->fetch();
    }
    //申请买呗
    public function apply_loan(){
        if(IS_POST){

            $logic = new UsersLogic();
            $user = $logic->get_info($this->user_id);
            $user = $user['result'];
            if($user['user_title'] <= 3 ){
                $this->error('您的职称等级不够,暂时无法申请');exit;
            }
            //处理图片上传
            $files = request()->file('idcard');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            $ossConfig = tpCache('oss');
            if($files){
                if ($ossConfig['oss_switch']) {
                    $ossClient = new \app\common\logic\OssLogic;
                    //循环处理  oss上传
                    foreach ($files as $file){
                        $object = $save_url.md5(microtime(true).rand(9999,99999)).'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
                        $return_url = $ossClient->uploadFile($file->getRealPath(), $object);
                        $card_img[] = $return_url;
                        @unlink($file->getRealPath());
                    }

                } else {
                    foreach ($files as $file) {
                        // 移动到框架应用根目录/public/uploads/ 目录下
                        $image_upload_limit_size = config('image_upload_limit_size');
                        $info = $file->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                        if ($info) {
                            // 成功上传后 获取上传信息 // 输出 jpg
                            $card_img[] = '/' . $save_url . '/' . $info->getFilename();
                        } else {
                            // 上传失败获取错误信息
                            $this->error($file->getError());
                        }
                    }
                }
            }else{
                $this->error("证件上传失败");exit;
            }

            $data = I('post.');
            if(empty($data)){
                $this->error('参数有误!');exit;
            }

            $apply = new Psp\Member\ApplyBuy();
            $apply->setUserId($this->user_id);
            $apply->setWhId($data['wh_id']);
            $apply->setUserName($data['real_name']);//姓名
            $apply->setCardNum($data['cardnum']); //身份证号码
            $apply->setPhone($data['tel']);//手机号码
            $apply->setFrontUrl($card_img[0]);//身份证 正面
            $apply->setBackUrl($card_img[1]);// 反面
            $apply->setOrgId(PLATFORM);//平台id
            list($resp) = GRPC('member')->AddMemberBuy($apply)->wait();
            $ret = $resp->getRet();
            $msg = $resp->getMsg();

            if($ret == 'ok'){
                $this->success('申请已提交,请耐心等待',U('Mobile/User/my_loan'));

            }else{
                $this->error("{$msg}",U('Mobile/User/my_loan'));exit;
            }
        }

        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp) = GRPC('member')->GetMemberBuyDetail($uid)->wait();
        $info['real_name'] = $resp->getUserName();//姓名
        $info['phone'] = $resp->getPhone();//手机号
        $info['card_num'] = $resp->getCardNum();//身份证号
        $info['wh_id'] = $this->wh181_id;
        $this->assign('info',$info);
        return $this->fetch();
    }
    //买呗还款
    public function loan_payback(){
        $paypwd = trim(I('post.pay_pwd/s'));
        $order_amount = I('post.order_amount');//订单金额
        $pay_month = I('post.time');
        $payment_way = I('post.payment_way'); //付款方式


        //获取账户可用余额
        $balance = new AccountCenterLogic();
        $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);

        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($resp) = GRPC('member')->GetMemberBuyDetail($uid)->wait();
        $last_month = $resp->getLastMonth();//上月时间
        $now_month = $resp->getNowMonth();// 当月时间

        if(IS_POST){
            if($payment_way == 'walletpay'){
                //获取用户支付密码
                $uid = new Psp\Member\Uid();
                $uid->setUid($this->user_id);
                list($resp,$status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
                $replyPwd = $resp->getPwdWithdraw();//支付密码
                $token = $resp->getToken();//token安全码
                $pay_pwd = sha1('!#*' . md5($paypwd).$token);
                if($pay_pwd != $replyPwd){
                    exit(json_encode(array('status'=>-1,'msg'=>'支付密码不正确')));
                }
                if($order_amount > $accountData['balance']){
                    exit(json_encode(array('status'=>-2,'msg'=>'账户余额不足')));
                }
                //转账(买呗还款)
                $res = $balance->addTransfer($accountData['account_id'],(int)PLATFORM,$order_amount,7);//调用转账接口 7 会员->平台(买呗还款)
                if($res['state'] ==1){
                    //扣去账户余额
                    $repayParams = new Psp\Member\MemberRepayment();
                    $repayParams->setUserId($this->user_id);
                    $repayParams->setRepayMonth($pay_month);
                    $repayParams->setGiveMoney($order_amount);
                    $repayParams->setPayType(1);//余额
                    $repayParams->setOrgId(PLATFORM);
                    $repayParams->setStatus(2);//余额还款 直接成功
                    list($reply) = GRPC('member')->UpdateMemberRepaymentOperate($repayParams)->wait();
                    $ret = $reply->getRet();
                    $msg = $reply->getMsg();
                    if($ret == 'ok'){
                        exit(json_encode(array('status'=>1,'msg'=>'还款成功','url'=>U('User/my_loan'))));
                    }else{
                        exit(json_encode(array('status'=>-3,'msg'=>"{$msg}")));
                    }
                }else{
                    exit(json_encode(array('status'=>-4,'msg'=>"{$res['reject_reason']}")));
                }
            }else{
                exit(json_encode(array('status'=>-6,'msg'=>'亲,走错地方了')));
            }
        }

        $this->assign('last_month',$last_month);
        $this->assign('now_month',$now_month);
        $this->assign('balance',$accountData['balance']);
        return $this->fetch();
    }


    //获取买呗待还金额
    public function member_not_repayment(){
        $repaymentParams = new Psp\Member\RepaymentParams();
        $repaymentParams->setUserId($this->user_id);
        $repaymentParams->setRepayMonth(I('post.repay_month'));
        list($resp) = GRPC('member')->GetMemberNotRepayment($repaymentParams)->wait();
        $month = $resp->getRepayMonth();
        $money = $resp->getRepayMoney();
        $data = ['month'=>$month,'money'=>$money,'status'=>1];
        $this->ajaxReturn($data);
    }


}


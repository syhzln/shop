<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller;
use app\admin\logic\ArticleCatLogic;
use app\home\logic\UsersLogic;
use app\home\logic\CartLogic;
use app\home\model\Message;
use app\admin\logic\AccountCenterLogic;
use Symfony\Component\Yaml\Dumper;
use think\Controller;
use think\Url;
use think\Page;
use think\Config;
use think\Verify;
use think\Db;
use think\Request;
use Grpc;
use Psp;
use think\log;
use think\captcha\Captcha;


class User extends Base{

    public $user_id = 0;
    public $user = array();
    public $wh181_id=0;
    public function _initialize() {
        parent::_initialize();
        if($this->checkLogin())
        {
            $jwt_token =$_COOKIE['token'];
            $payload =validate_json_web_token($jwt_token);//解码token
            $token = create_json_web_token($payload);//每次调用重新签发token
            $this->user_id = (int)$payload['user_id'];
            $this->org_id = $payload['org_id'];//组织id
            $this->phone = $payload['mobile'];
            $this->nickname =  $payload['nickname'];
            $this->wh181_id = $payload['tps138_id'];//量子id

            setrawcookie('token',$token,0,'/',get_host(),false,true);//签发token
            $user = ['org_id'=>$this->org_id,'mobile'=>$this->phone,'nickname'=>$this->nickname,'wh181_id'=>$this->wh181_id];
            //获取用户未读信息的数量
            $user_id = new Psp\Member\Uid();
            $user_id->setUid($this->user_id);
            list($res,$status) = GRPC('member')->GetUserUnreadMessageCount($user_id)->wait();
            if(!empty($res)){
                $count = $res->getCount();
                unset($res);
            }
            $this->assign('user_id',$this->user_id);
            $this->assign('user',$user);
            $this->assign('user_message_count', $count);
        }else{
            setcookie("curLogin", null, time() - 3600, "/", get_host());//token过期 清除登录态
            setcookie("uname", null, time() - 3600, "/", get_host());

            $nologin = array(
                'login','pop_login','do_login','logout','verify','set_pwd','finished',
                'verifyHandle','reg','send_sms_reg_code','identity','check_validate_code',
                'forget_pwd','check_captcha','check_username','send_validate_code',
            );
            if(!in_array(ACTION_NAME,$nologin)){
                $this->redirect('Home/User/login');
                exit;
            }
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user',$navigate_user);
    }

    /*
     * 用户中心首页
     */
    public function index(){

        $logic = new UsersLogic();
        $user_info = $logic->get_info($this->user_id);
        //获取账户信息
        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        //判断是否存在资金账户
//        if(empty($account_info)){
//            $money ='0.00';
//            //创建资金账户
//
//        }else{
//            $money = sprintf("%.2f", $account_info['balance']);//账户余额
//        }
        $money = sprintf("%.2f", $account_info['balance']);
        $user_info = $user_info['result'];
        $client = GRPC(Trade);
        $limit = 2;
        $p = I('p/d',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("order_id");
        $page->setIndex($p);
        $page->setLimit($limit);

        $user = Trade(OrderCondition);
        $user->setMemberId((int)$this->user_id);
        $user->setCondition(all);
        $user->setPagination($page); // 分页
        //$user->setSearch($search);
        list($res,$status) = $client->GetUserOrder($user)->wait();
        if(!empty($res)){
            foreach ($res->getUserOrder() as $k=>$v) {
                //var_dump($v->getLogictics());
                $data[$k]['order_id'] = $v->getOrderId();
                $data[$k]['order_sn'] = $v->getOrderSn();
                $data[$k]['order_status'] = $v->getState();
                $data[$k]['money'] = $v->getMoney();
                $data[$k]['add_time'] = $v->getOrderDate()->getSeconds();
                foreach($v->getItems() as $kk=>$vv){
                    $data[$k]['order_item'][$kk]['title'] = $vv->getName();
                    $data[$k]['order_item'][$kk]['price'] = $vv->getPrice();
                    $data[$k]['order_item'][$kk]['goods_id'] = $vv->getItemId();
                    $data[$k]['order_item'][$kk]['cost'] = $vv->getCost();
                    $data[$k]['order_item'][$kk]['amount'] = $vv->getAmount();
                    $data[$k]['order_item'][$kk]['thumb_img_url']= $vv->getThumbImageUrl();
                    $data[$k]['order_item'][$kk]['order_item_id']= $vv->getOrderItemId();
                }

                $data[$k]['shipping_price'] = $v->getShippingPrice();
                $data[$k]['pay_status'] = $v->getPayStatus();
                $data[$k]['delivery_status'] = $v->getDeliveryStatus();
                $data[$k]['shop_name'] = $v->getShopname();

            }
        }

        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex(1);
        $page->setLimit(5);
        $fav_items = new Psp\Member\GetPage();
        $fav_items->setUid($this->user_id);
        $fav_items->setPagination($page);
        list($res,$status) = GRPC('member')->GetFavoriteItem($fav_items)->wait();
        if($res)
            foreach($res->getFavoriteItemList() as $k=>$v){

                $arr[$k]['goods_id'] = $v->getItemId();
                $arr[$k]['add_time'] = $v->getAddTime()->getSeconds();
                $arr[$k]['goods_name'] = $v->getItemName();
                $arr[$k]['shop_price'] = number_format($v->getItemPrice(), 2);
                $arr[$k]['goods_img'] = $v->getItemImg();
                $arr[$k]['item_stock'] = $v->getItemStock();
            }

        $this->assign('collect_result',$arr);
        $this->assign('user',$user_info);
        $this->assign('balance',$money);
        $this->assign('order_list',$data);
//        $this->assign('coupon',$GetCoupon);
        return $this->fetch();
    }


    public function logout()
    {

        setcookie("token", null, time() - 3600, "/", get_host());
        setcookie("uname", null, time() - 3600, "/", get_host());
        setcookie("cn", null, time() - 3600, "/", get_host());//原来的  购物车数量标记
        setcookie("curLogin", null, time() - 3600, "/", get_host());
        /*cookie('token', null);
        cookie('uname',null);
        cookie('cn',null);
        cookie('curLogin',null);*/
        $this->redirect('Home/Index/index');
        exit;
    }

    /*
     * 账户资金
     */
    public function account(){
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id,I('get.type'));
        $account_log = $data['result'];

        /*
           $account = new Psp\Member\Uid();
           $account->setUid($this->user_id);
           list($res,$status)=GRPC('member')->GetAccount($account)->wait();*/


        $this->assign('user',$user);
        $this->assign('account_log',$account_log);
        $this->assign('page',$data['show']);
        $this->assign('active','account');
        return $this->fetch();

    }
    /*
     * 优惠券列表
     */
    public function coupon(){

        $uid = $this->user_id;
        $coupon = new Psp\Member\Uid();
        $coupon->setUid($uid);
        list($reply,$status) = GRPC('member')->GetCoupon($coupon)->wait();
        /* var_dump($reply);die;*/
        //todo 暂时未用到,保留代码
//        $this->assign('coupon_list', $GetCoupon);
        return $this->fetch();
    }
    /**
     *  登录
     */
    public function login()
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
        $login = new Psp\Newhome\AdPid();
        $login->setPid(1135);
        $login->setPlatform($platform);
        $login->setLanguage($lang);
        list($res, $status) = GRPC('NewHome')->GetAdlist($login)->wait();
        if ($res){
            foreach ($res->getAdItems() as $k => $v) {
                $login_ad[$k]['ad_id'] = $v->getAdId();
                $login_ad[$k]['ad_link'] = $v->getAdLink();
                $login_ad[$k]['ad_code'] = $v->getAdCode();
                $login_ad[$k]['target'] = $v->getTarget();
            }
        }
        if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl',$referurl);
        $this->assign('login_ad',$login_ad);
        return $this->fetch();
    }

    public function pop_login()
    {
        if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl',$referurl);
        return $this->fetch();
    }

    public function do_login()
    {

        $username = trim(I('post.username'));
        $password = trim(I('post.password'));
        $verify_code = I('post.verify_code');
        $verify = new Captcha();
        if (!$verify->check($verify_code,'user_login'))
        {
            $res = array('status'=>0,'msg'=>'验证码错误');
            exit(json_encode($res));
        }

        $logic = new UsersLogic();
        $res = $logic->login($username,$password);
        if($res['status'] == 1){
            $res['url'] =  urldecode(I('post.referurl'));
            /*$cartLogic = new CartLogic();
            $cartLogic->setUserId($res['result']['user_id']);
            $cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作*/
        }

        exit(json_encode($res));
    }

    /**
     *  注册
     */
    public function reg()
    {

//        $this->error('维护中,敬请谅解!');exit;
        if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }

        if(IS_POST){
            $logic = new UsersLogic();
            $mobile = I('post.mobile','');
            $email = I('post.email','');
            $recommondId = I('post.recommondId');
            $real_name = I('post.realName');
//            $id_card = I('post.id_card');
//            $bank_name = I('post.bank');
//            $bank_card = I('post.card_num');
//            $alipay_code =I('post.alipay');
//            $face_url =I('post.face_url');
            $password = I('post.password','');
//            $password2 = I('post.password2','');
            $verify_code = I('post.verify_code','');
            //验证码检验
            $verify = new Verify();
            if (!$verify->check($verify_code,'user_reg'))
            {
                $res = array('status'=>0,'msg'=>'验证码错误');
                exit(json_encode($res));
            }
            $data = $logic->reg($mobile,$recommondId,$real_name,$password);
            if($data['status'] != 1){
                $this->ajaxReturn($data);
            }
            //注册成功之后跳转首页
//            $payload = array(
//                'user_id'=>$data['result']['user_id'],
//                'org_id'=>$data['result']['org_id'],
//                'tps138_id'=>$data['result']['tps138_id'],
//                'nickname'=>$data['result']['nickname'],
//                'mobile'=>$data['result']['mobile'],
//            );
//            $this->user_id = (int)$payload['user_id'];
//            $jwt = create_json_web_token($payload);
//            setrawcookie('token', $jwt, 0, '/', '', false, true);
//            $nickname = empty($data['result']['nickname']) ? $mobile : $data['result']['nickname'];
//            setcookie('uname',urlencode($nickname),null,'/');
//            setcookie('cn',0,time()-3600,'/');
//            $cartLogic = new CartLogic();
//            $cartLogic->setUserId($this->user_id);
            //$cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作
            $this->ajaxReturn($data);
            exit;
        }
        $bank_name = include APP_PATH.'home/bank.php';
        $this->assign("bank_list",$bank_name);
        return $this->fetch();
    }


    /*
     * 订单列表
     */
    public function order_list(){
        $search = trim(I('search_key'));
        $type = I('type');
        $client = GRPC(Trade);

        $limit = 6;
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
        if($res){
            foreach ($res->getUserOrder() as $k=>$v) {
                //var_dump($v->getLogictics());
                $data[$k]['order_id'] = $v->getOrderId();
                $data[$k]['order_sn'] = $v->getOrderSn();
                $data[$k]['order_status'] = $v->getState();
                $data[$k]['money'] = $v->getMoney();
                $data[$k]['add_time'] = $v->getOrderDate()->getSeconds();
                foreach($v->getItems() as $kk=>$vv){
                    $data[$k]['order_item'][$kk]['title'] = $vv->getName();
                    $data[$k]['order_item'][$kk]['goods_id'] = $vv->getItemId();
                    $data[$k]['order_item'][$kk]['price'] = $vv->getPrice();
                    $data[$k]['order_item'][$kk]['cost'] = $vv->getCost();
                    $data[$k]['order_item'][$kk]['amount'] = $vv->getAmount();
                    $data[$k]['order_item'][$kk]['thumb_img_url']= $vv->getThumbImageUrl();
                    $data[$k]['order_item'][$kk]['order_item_id']= $vv->getOrderItemId();
                    $data[$k]['order_item'][$kk]['sku_name'] = $vv->getSkuName();
                    $data[$k]['return'] = $vv->getReturn();
                }
                if($v->getDeliveryStatus()>1){
                    if($v->getLogictics()){

                        $data[$k]['delivery_sn']= $v->getLogictics()->getDeliverySn();
                        $data[$k]['delivery_time'] = $v->getLogictics()->getShippingDate()?$v->getLogictics()->getShippingDate()->getSeconds():'';

                        if($data[$k]['delivery_time']+864000<time()){//发货后十天不予退货
                            $data[$k]['flag']=1;
                        }
                    }
                }


                $data[$k]['shipping_price'] = $v->getShippingPrice();
                $data[$k]['pay_status'] = $v->getPayStatus();
                $data[$k]['delivery_status'] = $v->getDeliveryStatus();
                $data[$k]['shop_name'] = $v->getShopname();
                $data[$k]['qq'] = $v->getQq();

            }
            $total_count = $res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,$limit) ;
            $show = $Page->show();
        }


        $this->assign('page',$show);

        $this->assign('active_status',I('get.type'));
        $this->assign('lists',$data);
        return $this->fetch();
    }

    /*
     * 订单详情
     */
    public function order_detail(){
        $id = I('get.id/d');
        $client = GRPC('AdminOrder');
        $user = new Psp\Trade\AdminUserOrderId();
        $user->setOrderId($id);
        $user->setUserId($this->user_id);
        list($res,$status) = $client->GetOrderInfo($user)->wait();
        if(!$res->getOrder())$this->error('获取订单详情有误',U('order_list'));
        if($res){
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
            $order['receiver']=$res->getOrder()->getReceiver();//收件人名称
            $order['receiver_location']=$res->getOrder()->getReceiverLocation();//收件人地区id
            $order['receiver_address']=$res->getOrder()->getReceiverAddress();//收件人详细地址
            $order['receiver_phone']=$res->getOrder()->getReceiverPhone();//收件人电话
            $order['sms_notify']=$res->getOrder()->getSmsNotify();//是否短信通知收件人
            $order['oder_date']=$res->getOrder()->getOderDate()?$res->getOrder()->getOderDate()->getSeconds():0;//下单时间
            $order['pay_date']=$res->getOrder()->getPayDate()?$res->getOrder()->getPayDate()->getSeconds():0;//支付时间
            $order['shipping_date']=$res->getOrder()->getShippingDate()?$res->getOrder()->getShippingDate()->getSeconds():0;//发货时间
            $order['receipted_date']=$res->getOrder()->getReceiptedDate()?$res->getOrder()->getReceiptedDate()->getSeconds():0;//签收时间
            $order['invoice_title']=$res->getOrder()->getInvoiceTitle();//发票抬头

            $order['pay_type']=$res->getOrder()->getPayType();//支付方式

            $order['shipping_type'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getShippingName():'';//物流公司
            $order['invoice_no'] = $res->getDeliveryDoc()?$res->getDeliveryDoc()->getInvoiceNo():'';//快递单号
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
        $payType=array('1'=>"PC端支付宝",
            '2'=>"app端支付宝",
            '3'=>"手机网站支付宝",
            '4'=>"微信支付",
            '5'=>"银联在线支付",
            '6'=>"银联app支付",
            '9'=>"钱包支付",
            '10'=>"买呗支付");
        $this->assign('payType',$payType);
        $this->assign('good',$goods);
        $this->assign('order_info',$order);
        return $this->fetch();

    }

    /*
     * 取消订单
     */
    public function cancel_order(){
        $id = I('get.id/d');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id,$id);
        if($data['status'] < 0)
            $this->error($data['msg']);
        $this->success($data['msg']);
    }

    /*
     * 用户地址列表
     */
    public function address_list(){
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
        $this->assign('lists',$arr);
        return $this->fetch();
    }
    /*
     * 添加地址
     */
    public function add_address(){
        header("Content-type:text/html;charset=utf-8");
        if(IS_POST){
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,0,I('post.'));
            if($data['status'] != 1)
                exit('<script>alert("'.$data['msg'].'");history.go(-1);</script>');
            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        $areamap= new \area\area();
        $p = $areamap->getProv();

        $this->assign('province',$p);
        return $this->fetch('edit_address');

    }

    /*
     * 地址编辑
     */
    public function edit_address(){

        header("Content-type:text/html;charset=utf-8");
        $id = I('get.id/d');

        $address = new Psp\Member\AddressId();
        $address->setAddressId($id);
        list($reply,$status) = GRPC('member')->GetUserEditAddress($address)->wait();
        $areamap= new \area\area();
        $arr['location_code'] = $reply->getLocationCode();
        $arr['address_info'] =$areamap->getAddrstr($arr['location_code']);
        $address =explode(',',$arr['address_info']);
        $arr['province'] = $address[0];
        $arr['city'] = $address[1];
        $arr['area'] = $address[2];
        $arr['consignee'] = $reply->GetName();
        $arr['address'] = $reply->getAddress();
        $arr['zipcode'] = $reply->getPostCode();
        $arr['mobile'] = $reply->getPhone();


        if(IS_POST){
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,$id,I('post.'));
            if($data['status'] != 1)
                exit('<script>alert("'.$data['msg'].'");history.go(-1);</script>');

            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        //获取省份
        $areamap= new \area\area();
        $p = $areamap->getProv();
        $this->assign('province',$p);

        $this->assign('address',$arr);
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default(){
        $id = I('get.id/d');
        $address_id = new Psp\Member\SetDefault();
        $address_id->setUserId($this->user_id);
        $address_id->setAddressId($id);
        list($res,$status) = GRPC('member')->SetDefaultAddress($address_id)->wait();
        $row=$res->getValue();
        if($row==false)
            $this->error('操作失败');
        $this->success("操作成功");
    }

    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('get.id/d');

        $address_id = new Psp\Member\AddressId();
        $address_id->setAddressId($id);
        $row = GRPC('member')->DelUserAddress($address_id)->wait();

        if(!$row)
            $this->error('操作失败',U('User/address_list'));
        else
            $this->success("操作成功",U('User/address_list'));
    }


    public function save_pickup()
    {
        $post = I('post.');
        if (empty($post['consignee'])) {
            return array('status' => -1, 'msg' => '收货人不能为空', 'result' => '');
        }
        if (!$post['province'] || !$post['city'] || !$post['district']) {
            return array('status' => -1, 'msg' => '所在地区不能为空', 'result' => '');
        }
        if(!check_mobile($post['mobile'])){
            return array('status'=>-1,'msg'=>'手机号码格式有误','result'=>'');
        }
        if(!$post['pickup_id']){
            return array('status'=>-1,'msg'=>'请选择自提点','result'=>'');
        }

        $user_logic = new UsersLogic();
        $res = $user_logic->add_pick_up($this->user_id, $post);
        if($res['status'] != 1){
            exit('<script>alert("'.$res['msg'].'");history.go(-1);</script>');
        }
        $call_back = $_REQUEST['call_back'];
        echo "<script>parent.{$call_back}({$post['province']},{$post['city']},{$post['district']});</script>";
        exit(); // 成功 回调closeWindow方法 并返回新增的id
    }

    /*
     * 评论晒单
     */
    public function comment(){
        $user_id = $this->user_id;
        $state = I('get.state',2);
        $logic = new \app\common\logic\CommentLogic;
        $data = $logic->getComment($user_id,$state); //获取评论列表
        $this->assign('comment_list',$data['result']);
        if($data['result']){
            $this->assign('page',$data['show']);// 赋值分页输出
        }
//        $this->assign('active','comment');
        return $this->fetch();

    }

    /**
     * @time 2017/2/9
     * @author lxl
     * 订单商品评价列表
     */
    public function comment_list()
    {
        $order_id = I('order_id/d');
        $goods_id = I('goods_id/d');
//        var_dump($order_id);die;
        if (empty($order_id) || empty($goods_id)) {
            $this->error("参数错误");
        } else {
            $client = GRPC('AdminOrder');
            $user = new Psp\Trade\AdminOrderId();
            $user->setOrderId($order_id);
            list($res,$status) = $client->GetOrderInfo($user)->wait();
            $arr['order_id']=$res->getOrder()->getOrderId();//订单id
            $arr['order_sn']=$res->getOrder()->getOrderSn();//订单编号
            $arr['goods_price']=sprintf("%.2f",$res->getOrder()->getTotalAmount());//订单总金额
            $arr['oder_date']=$res->getOrder()->getOderDate()?$res->getOrder()->getOderDate()->getSeconds():0;//下单时间
            $arr['goods_id'] = $goods_id;
            foreach($res->getOrderItem()as $k=>$v){
                $goods[$k]['name']=$v->getName();//商品名称

            }
            $arr['goods_name'] = $goods[$k]['name'];
            $this->assign('order_info', $arr);
            return $this->fetch();
        }
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
        $logic = new UsersLogic();

        $comment_img = serialize(I('comment_img/a')); // 上传的图片文件
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
        //$add['content'] = htmlspecialchars(I('post.content'));
        $add['content'] = I('content');
        $add['img'] = $comment_img;
        $add['add_time'] = time();
        $add['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $add['user_id'] = $this->user_id;
        $logic = new UsersLogic();
        //添加评论
        $row = $logic->add_comment($add);
        exit(json_encode($row));
    }

    /*
     * 个人信息
     */
    public function info(){
        $userLogic = new UsersLogic();
        $user_data = $userLogic->get_info($this->user_id);
        if(IS_POST){
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区

            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            setcookie('uname',urlencode($post['nickname']),null,'/',get_host());
            $this->success("操作成功");
            exit;
        }
        $this->assign('user',$user_data['result']);
        $this->assign('sex',C('SEX'));
        $this->assign('active','info');
        return $this->fetch();
    }

    /*
     * 邮箱验证
     */
    public function email_validate(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step',1);
        if(IS_POST){
            $email = I('post.email');
            $old_email = I('post.old_email',''); //旧邮箱
            $code = I('post.code');
            $info = session('validate_code');
            if(!$info)
                $this->error('非法操作');
            if($info['time']<time()){
                session('validate_code',null);
                $this->error('验证超时，请重新验证');
            }
            //检查原邮箱是否正确
            if($user_info['email_validated'] == 1 && $old_email != $user_info['email'])
                $this->error('原邮箱匹配错误');
            //验证邮箱和验证码
            if($info['sender'] == $email && $info['code'] == $code){
                session('validate_code',null);
                if(!$userLogic->update_email_mobile($email,$this->user_id))
                    $this->error('邮箱已存在');
                $this->success('绑定成功',U('Home/User/index'));
                exit;
            }
            $this->error('邮箱验证码不匹配');
        }
        $this->assign('user_info',$user_info);
        $this->assign('step',$step);
        return $this->fetch();
    }


    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $config = tpCache('sms');
        $sms_time_out = $config['sms_time_out'];
        $step = I('get.step', 1);
        if (IS_POST) {
            $mobile = I('post.mobile');
            $old_mobile = I('post.old_mobile');
            $code = I('post.code');
            $scene = I('post.scene', 6);
            $session_id = I('unique_id', session_id());

            $logic = new UsersLogic();
            $res = $logic->check_validate_code($code, $mobile, 'phone', $session_id, $scene);

            if (!$res && $res['status'] != 1) $this->error($res['msg']);

            $logic = new UsersLogic();
            $user = $logic->get_info($this->user_id);
            $user = $user['result'];
            //检查原手机是否正确
            if ($user['mobile_validated'] == 1 && $old_mobile != $this->phone)
                $this->error('原手机号码错误');
            //验证手机和验证码
            if ($res['status'] == 1) {
                //验证有效期
                if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                    $this->error('手机已存在');
                $this->success('绑定成功', U('Home/User/index'));
                exit;
            } else {
                $this->error($res['msg']);
            }

        }
        $this->assign('time', $sms_time_out);
        $this->assign('step', $step);
//        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 发送手机注册验证码
     */
    public function send_sms_reg_code(){

        $mobile = I('mobile');
        $userLogic = new UsersLogic();
        if(!check_mobile($mobile))
            exit(json_encode(array('status'=>-1,'msg'=>'手机号码格式有误')));
        $code =  rand(1000,9999);
        $send = $userLogic->sms_log($mobile,$code,$this->session_id);
        if($send['status'] != 1)
            exit(json_encode(array('status'=>-1,'msg'=>$send['msg'])));
        exit(json_encode(array('status'=>1,'msg'=>'验证码已发送，请注意查收')));
    }
    /*
     *商品收藏
     */
    public function goods_collect(){

        $p = I('p/d', 1);
        $logic = new UsersLogic();
        $result = $logic->user_goods_collect($this->user_id,$p);
        $this->assign('count',$result['goods_num']);
        $this->assign('lists', $result['list']);
        $this->assign('page',$result['page']);
        $this->assign('active','goods_collect');
        return $this->fetch();
    }

    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect(){
        $item_id = I('get.id/s');
        if(!$item_id)
            $this->error("缺少ID参数");
//        $uid = $this->user_id;
        $item = new Psp\Member\ItemId();
        $item->setUserId( $this->user_id);
        $item->setItemId($item_id);
        $row = GRPC('member')->DelFavoriteItem($item)->wait();
        if(!$row)
            $this->error("删除失败");
        $this->redirect('Home/User/goods_collect',1, '删除成功');
    }

    /*
     * 密码修改
     */
    public function password(){
        if($this->phone == '')
            $this->error('请先绑定手机号',U('Home/User/info'));
        if(IS_POST){
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id,I('post.old_password'),I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            if($data['status'] == -1)
                $this->error($data['msg']);
            $this->success($data['msg'],U('User/index'));
            exit;
        }
        return $this->fetch();
    }

    public function forget_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        if (IS_POST) {
            $username = I('username');
            if (!empty($username)) {

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
                    cookie('find_password', array('user_id' => $arr['id'], 'username' => $username,
                        'email' => $arr['email'], 'mobile' => $arr['phone'], 'type' => $field));
                    header("Location: " . U('User/identity'));
                    exit;
                } else {
                    echo "用户名不存在，请检查";
                    $this->error("用户名不存在，请检查");
                }
            }
        }
        return $this->fetch();
    }

    public function set_pwd(){
        if($this->user_id > 0){
            $this->redirect('Home/User/Index');
        }
        $check = cookie('validate_code');
        $logic = new UsersLogic();
        if(empty($check)){
            $this->redirect('Home/User/forget_pwd');
        }elseif($check['is_check']==0){
            $this->error('验证码还未验证通过',U('Home/User/forget_pwd'));
        }
        if(IS_POST){
            $password = I('post.password');
            $password2 = I('post.password2');
            if($password2 != $password){
                $this->error('两次密码不一致',U('Home/User/forget_pwd'));
            }
            if($check['is_check']==1) {

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
                    $this->redirect('Home/User/finished');
                } else {
                    $this->error('验证码还未验证通过', U('Home/User/forget_pwd'));
                }
            }
        }
        return $this->fetch();
    }

    public function finished(){
        if($this->user_id > 0){
            $this->redirect('Home/User/Index');
        }
        return $this->fetch();
    }

    public function check_captcha(){

        $verify = new Verify();

        $type = I('post.type','user_login');
        if (!$verify->check(I('post.verify_code'), $type)) {
            exit(json_encode(0));
        }else{
            exit(json_encode(1));
        }
    }

    public function check_username(){
        $username = I('post.username');
        if(!empty($username)) {
            $user_id = new Psp\Member\SavePwd();
            $user_id->setMemberAccount($username);
            list($res, $status) = GRPC('member')->UpdateUserPassword($user_id)->wait();
            $arr['id'] = $res->getTpsinfo()->getId();
            if ($arr['id'] != 0) {
                $count = 1;
                exit(json_encode(intval($count)));
            } else {
                exit(json_encode(0));
            }
        }
    }

    public function identity()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $user = cookie('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('userinfo', $user);
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
     * 订单确认收货
     */
    public function order_confirm(){
        $id = I('get.id/d',0);
        $data = confirm_order($id,$this->user_id);
        if($data['status'] != 1){
            $this->error($data['msg'] ,U('User/order_list'));
        } else	{
            //transferSplit($id); //订单确认收货,接口调用这个方法,传订单id,执行资金拆分,无返回
            $this->success($data['msg'],U('User/order_list'));
        }
    }
    /**
     * 申请退货
     */
    public function return_goods()
    {
        $order_item_id = I('order_item_id/d',0);
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
            $this->error($reason,U('Home/User/return_goods_list'));
        }

        if(IS_POST)
        {
            $order_item_id = I('order_item_id/d');
            $count = I('count/d');
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
                $this->success('申请成功,客服第一时间会帮你处理',U('Home/User/order_list'));
                exit;
            }

        }

        $this->assign('goods',$goods);
        return $this->fetch();
    }
    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
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
            $arr[$k]['father_id'] = $v->getFatherId();
            $arr[$k]['father_sn'] = $v->getFatherSn();
            $arr[$k]['type'] = $v->getType();
        }
        //总条数
        $total_count = $res->getPageResult()->getTotalRecords();
        //总页数
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
        $return_info['item_id'] = $res->getItemId();
        $return_info['reason'] = $res->getReason();
        $return_info['remark'] = $res->getRemark();
        $return_info['returning_status'] = $res->getReturningStatus();
        $return_info['shipping_name'] = $res->getShippingName();
        $return_info['shipping_no'] = $res->getShippingNo();
        $return_info['shipping_time'] = $res->getShippingTime()?$res->getShippingTime()->getSeconds():'';
        $return_info['add_time'] = $res->getOrderDate()?$res->getOrderDate()->getSeconds():'';
        $return_info['order_status'] = $res->getOrderStatus();
        $return_info['price'] = $res->getPrice();
        $return_info['returning_delivery_status'] = $res->getReturnningDeliveryStatus();
        $return_info['thumb_img_url'] = $res->getThumbUrl();
        $return_info['count'] = $res->getCount();
        $return_info['qq'] = $res->getQq();
        $this->assign('return_goods',$return_info);
        return $this->fetch();
    }


    /**
     * 安全设置
     */
    public function safety_settings()
    {
        $userLogic = new UsersLogic();
        //读取支付密码 供前端判断使用
        $uid = new Psp\Member\Uid();
        $uid->setUid($this->user_id);
        list($res, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $paypwd = $res->getPwdWithdraw();
        $user_info['paypwd'] = $paypwd;
        return $this->fetch();
    }
    /**
     * 申请提现
     */
    public function withdrawals(){
//        exit('业务升级中,请稍后......');
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
        if(IS_POST)
        {
//            $this->ajaxReturn(['status'=>-1,'msg'=>"对不起,提现暂未开放!!"]);
            if(!$this->verifyHandle('withdrawals')){
                $this->ajaxReturn(['status'=>0,'msg'=>'图像验证码错误']);
            };
            $with_type = I('post.with_type');//提现类型  1余额 2收益
            $with_way = I('post.with_way');//提现方式  1支付宝 2银行卡
            $withdraw_money = (float)trim(I('post.money')); //提现金额
            $accout_name = trim(I('account_bank')); //提款账号
            $real_name = I('post.account_name'); //所属账号姓名
            $mark = I('post.mark');//备注
            $paypwd = I('post.paypwd');//支付密码
            //最多可提现 根据提现类型判断
            if($with_type == 1){
                $user_money = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM)['balance'];
                $type = 3;
            }else{
                $user_money = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM)['balance'];
                $type = 1;
            }

            $distribut_min = 100; // 最少提现额度
            if($withdraw_money < $distribut_min)
            {
                $this->ajaxReturn(['status'=>-1,'msg'=>"每次最少提现额度 {$distribut_min}"]);
            }
            //可提现收益/余额
            if($withdraw_money  > $user_money)
            {
                $this->ajaxReturn(['status'=>-2,'msg'=>"你最多可提现金额 {$user_money}"]);
            }

            $payPwd = sha1('!#*' . md5(trim($paypwd)).$token);
            if($payPwd != $user['paypwd']){
                $this->ajaxReturn(['status'=>-3,'msg'=>"支付密码错误"]);
            }
            //调用账务中心提现接口
            $res = $user_account->addWithdraw($type,$this->user_id,$withdraw_money,1,$real_name,$accout_name,0);
            if($res['ret'] == 'ok'){
                $this->ajaxReturn(['status'=>1,'msg'=>"已提交申请",'url'=>U('User/recharge',['type'=>1])]);
            }else{
                $this->ajaxReturn(['status'=>-4,'msg'=>"{$res['msg']}"]);
            }
        }

        $this->assign('user',$user);
        return $this->fetch();
    }


    //账户资金
    public  function recharge(){
        //会员资金信息
        $user_account = new AccountCenterLogic();
        $account_info = $user_account->getAccount($this->user_id,1,0,(int)PLATFORM);
        $money = sprintf("%.2f", $account_info['balance']);//账户余额
        $shouyi = $account_info = $user_account->getAccount($this->user_id,4,11,(int)PLATFORM);
        $profit = sprintf("%.2f",$shouyi['balance']);
        //会员收益信息
        $profit_info = $user_account->getMemberTotalProfit($this->user_id);
        //$profit = sprintf("%.2f",$profit_info['total_profit']);
        $other_profit = $money - $profit;  //其他收益

        if(IS_POST){
            $this->error('账户充值暂未开放,敬请期待!');
            exit;
            $user = session('user');
            $data['user_id'] = $this->user_id;
            $data['nickname'] = $user['nickname'];
            $data['account'] = I('account');
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            if($order_id){
                $url = U('Payment/getPay',array('pay_radio'=>$_REQUEST['pay_radio'],'order_id'=>$order_id));
                $this->redirect($url);
            }else{
                $this->error('提交失败,参数有误!');
            }
        }
        //充值
        $paymentList = require_once("application/conf/payment.php");//取出支付配置
        $paymentList = convert_arr_key($paymentList, 'code');
        foreach($paymentList as $key => $val)
        {
            if($val['scene'] == 1 || $val['status'] == 0 || $val['code'] == 'walletpay')
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
        $this->assign('bankCodeList',$bankCodeList);

        $type = I('type/d',1);//提现类型
        $p = I('p/d',1);//页码
        $Userlogic = new UsersLogic();
        if($type == 1||$type==3){
            $result=$Userlogic->get_withdrawals_log($this->user_id,$p,$type);  // 3余额 1收益 提现记录
        }elseif($type == 2){
            $result=$Userlogic->get_transfer($this->user_id,$p);  // 转账记录
        }else{
            $result=$Userlogic->get_recharge_log($this->user_id);  //充值记录  //暂无
        }
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        $this->assign('profit',$profit);//总收益
        //$this->assign('other_profit',$other_profit); //其他
        $this->assign('balance',$money);//账户余额
        return $this->fetch();
    }

    /**
     * 会员转账 会员->会员
     */
    public function transfer(){
//        exit('业务升级中,请稍后......');
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
            $his_138_id = trim(I('post.account_id/s','0'));//wh_id
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
                $this->error('转账金额太少了!');
                exit;
            }

            if($type == 1){
                if((float)$money > (float)$user['user_money']){
                    $this->error('账户余额不足!');
                    exit;
                }
            }else{
                if((float)$money > (float)$user['shouyi']){
                    $this->error('账户余额不足!');
                    exit;
                }
            }

            $payPwd = sha1('!#*' . md5(trim($paypwd)).$token);
            if($payPwd != $user['paypwd']){
                $this->error('支付密码错误!');
                exit;
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
                $this->error('对不起,不能给自己转账');
                exit;
            }
            //执行转账
            $res = $user_account->addTransfer($my_account_id['account_id'],(int)$his_account['account_id'],$money,$transfer_type);//调用转账接口
            if($res['state'] ==1){
                $this->success('转账成功',U('Home/User/recharge'));
            }else{
                $this->error("{$res['reject_reason']}");//失败
            }

        }

        $this->assign('user',$user);
        return $this->fetch();
    }

    //根据 量子id取出会员信息
    public function getMemberInfo(){
        $wh_id  = I('post.wh_id');
        $wal_id = new Psp\Member\Tps138Id();
        $wal_id->setTps138Id($wh_id);
        list($resp,$status) = GRPC('member')->GetUser138Id($wal_id)->wait();
        $uid = $resp->getUid();
        $user = new UsersLogic();
        $info = $user->get_info($uid);
        if(!empty($uid)){
            $name = $info['result']['nickname'] ? $info['result']['nickname'] : $info['result']['mobile'];
            $data =array('status'=>1,'name'=>$name);
            $this->ajaxReturn($data);
        }else{
            $this->ajaxReturn(array('status'=>-1,'name'=>'该账户不存在!'));
        }


    }
    /**
     * 余额支付
     * @author fzq
     * time 2018/01/06
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

        if(empty($pay_code))
            exit(json_encode(array('status'=>-5,'msg'=>'订单已支付或买呗未还款')));
        $res = $balance->addTransfer($accountData['account_id'],(int)PLATFORM,$order_amount,6);//调用转账接口 6会员->平台
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
        exit(json_encode(array('status'=>-22,'msg'=>'根据上级要求,买呗支付暂时关闭,敬请谅解!')));
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
//        var_dump($status);die;
        if($status['ret']=='ok'){
            //修改订单状态
            update_pay_status($pay_code,$order_amount);
            exit(json_encode(array('status'=>1,'msg'=>'支付成功','url'=>U('User/payOk',array('order_id'=>$order_ids,'order_sn'=>$pay_code,'order_amount'=>$order_amount)))));
        }else{
            exit(json_encode(array('status'=>-4,'msg'=>$status['msg'])));
        }

    }


    /**余额支付成功跳转页面**/
    public function payOk(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号   小强的pay_code是支付号  此处用订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('payment/success');
    }

    /**余额支付失败跳转页面**/
    public function payFail(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('payment/error');
    }

    /**
     *  用户消息通知
     * @author dyr
     * @time 2016/09/01
     */
    public function message_notice()
    {
        return $this->fetch('user/message_notice');
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
        return $this->fetch('user/ajax_message_notice');

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
        if(!$reply)
            $this->ajaxReturn(['status'=>'-1','msg'=>'操作失败']);
        else
            $this->ajaxReturn(['status'=>'1','msg'=>'操作成功']);
    }

    /**/
    public function paypwd()
    {
//        检查是否第三方登录用户

        if ($this->phone =='')
            $this->error('请先绑定手机号', U('Home/User/info'));
        $step = I('step', 1);
//         $step =2;
        if ($step > 1) {
            $check = cookie('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('Home/User/paypwd'));
            }
        }
        if (IS_POST && $step == 3) {
            $userLogic = new UsersLogic();
            $data = I('post.');
            $data = $userLogic->paypwd($this->user_id, I('new_password'), I('confirm_password'));
            if ($data['status'] == -1)
                $this->error($data['msg']);
            //$this->success($data['msg']);
            $this->redirect(U('Home/User/paypwd', array('step' => 3)));
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    //使用登录密码修改支付密码
    public function set_paypwd()
    {

        $step = I('step', 1);

        if ($step > 1) {
            if (cookie('verifyed') != 1) {
                $this->error('密码还未验证通过', U('Home/User/set_paypwd'));
            }
        }
        if (IS_POST && $step == 3) {
            $userLogic = new UsersLogic();
            $data = I('post.');
            $data = $userLogic->paypwd($this->user_id, I('new_password'), I('confirm_password'));
            if ($data['status'] == -1)
                $this->error($data['msg']);
            //$this->success($data['msg']);
            $this->redirect(U('Home/User/set_paypwd', array('step' => 3)));
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    //验证 登录密码
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

    /**
     * 取消售后服务
     * @author lxl
     * @time 2017-4-19
     */
    public function return_goods_cancel(){
        $id = I('id',0);
        if(empty($id))$this->error('参数错误');
//        $return_goods = M('return_goods')->where(array('id'=>$id,'user_id'=>$this->user_id))->find();
        if(empty($return_goods)) $this->error('参数错误');
//        M('return_goods')->where(array('id'=>$id))->save(array('status'=>-2,'canceltime'=>time()));
        $this->success('取消成功',U('User/return_goods_list'));
        exit;
    }

    /**
     *  点赞
     * @author lxl
     * @time  17-4-20
     * 拷多商家Order控制器
     */
    public function ajaxZan()
    {
        $comment_id = I('post.comment_id/d');
        $user_id = $this->user_id;
        $zan = new Psp\Store\ZanCondition();
        $zan->setMemberId($user_id);
        $zan->setCommentId($comment_id);

        list($res,$status) = GRPC('sellerstore')->AjaxZan($zan)->wait();
        exit(json_encode($res->getStatus()));
    }

    /**
     * 删除足迹
     * @author lxl
     * @time  17-4-20
     * 拷多商家User控制器
     */
    public function del_visit_log(){

        $visit_id = I('visit_id/d' , 0);
//        $row = M('goods_visit')->where(['visit_id'=>$visit_id])->delete();
//        if($row>0){
//            return $this->ajaxReturn(['status'=>1 , 'msg'=> '删除成功']);
//        }else{
//            return $this->ajaxReturn(['status'=>-1 , 'msg'=> '删除失败']);
//        }
    }

    /**
     * 我的足迹
     * @author lxl
     * @time  17-4-20
     * 拷多商家User控制器
     * */
    public function visit_log()
    {
        $cat_id = I('cat_id', 0);
        $map['user_id'] = $this->user_id;
        if ($cat_id > 0) $map['a.cat_id'] = $cat_id;
//        $count = M('goods_visit a')->where($map)->count();
//        $Page = new Page($count, 20);
//        $visit_list = M('goods_visit a')->field("a.*,g.goods_name,g.shop_price")
//            ->join('__GOODS__ g', 'a.goods_id = g.goods_id', 'LEFT')
//            ->where($map)
//            ->limit($Page->firstRow . ',' . $Page->listRows)
//            ->order('a.visittime desc')
//            ->select();
        $visit_log = $cates = array();
        $visit_total = 0;
//        if ($visit_list) {
//            $now = time();
//            $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
//            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
//            foreach ($visit_list as $k => $val) {
//                if ($now - $val['visittime'] < 3600 * 24 * 7) {
//                    if (date('Y-m-d') == date('Y-m-d', $val['visittime'])) {
//                        $val['date'] = '今天';
//                    } else {
//                        if ($val['visittime'] < $endLastweek) {
//                            $val['date'] = "上周" . $weekarray[date("w", $val['visittime'])];
//                        } else {
//                            $val['date'] = "周" . $weekarray[date("w", $val['visittime'])];
//                        }
//                    }
//                } else {
//                    $val['date'] = '更早以前';
//                }
//                $cat_ids[] = $val['cat_id'];
//                $visit_log[$val['date']][] = $val;
//            }
//            $cateArr = M('goods_category')->where(array('id' => array('in', array_unique($cat_ids))))->getField('id,name');
//            $cates = M('goods_visit a')->field('cat_id,COUNT(cat_id) as csum')->where($map)->group('cat_id')->select();
//            foreach ($cates as $k => $v) {
//                if (isset($cateArr[$v['cat_id']])) $cates[$k]['name'] = $cateArr[$v['cat_id']];
//                $visit_total += $v['csum'];
//            }
//        }
        $this->assign('visit_total', $visit_total);
        $this->assign('catids', $cates);
//        $this->assign('page', $Page->show());
        $this->assign('visit_log', $visit_log); //浏览记录
        return $this->fetch();
    }

    /**
     * 换货商品确认收货
     * @author lxl
     * @time  17-4-25
     * */
    public function receiveConfirm(){
        $return_id=I('return_id/d');
//        $return_info=M('return_goods')->field('order_id,order_sn,goods_id,spec_key')->where('id',$return_id)->find(); //查找退换货商品信息
//        $update = M('return_goods')->where('id',$return_id)->save(['status'=>3]);  //要更新状态为已完成
//        if($update) {
//            M('order_goods')->where(array(
//                'order_id' => $return_info['order_id'],
//                'goods_id' => $return_info['goods_id'],
//                'spec_key' => $return_info['spec_key']))->save(['is_send' => 2]);  //订单商品改为已换货
//            $this->success("操作成功", U("User/return_goods_info", array('id' => $return_id)));
//        }
        $this->error("操作失败");
    }



    /**收益中心**/
    public function commission()
    {



        return $this->fetch();
    }

    /**奖金报表**/
    public function commission_report()
    {
        /*$year = I('get.year',date('Y'));
        $month = I('get.month',date('m'));
        //连接数据库
        $db = Db::connect([  'type'   => 'mysql',
            'hostname'       =>'47.96.170.247',
            'database'       => 'ac_center',
            'username'       => 'dev_ac',
            'password'       => 'fECwWbqH',
            'hostport'       => '3306',]);
        //今日奖金
        $sql =" select SUM(money) as today_money from profit_copy where user_id=".$this->user_id." AND platform_id=".PLATFORM." and to_days(add_time) = to_days(now())";
        //当月奖金
        $sql2 = "select SUM(money) as  month_money from profit_copy where user_id=".$this->user_id." AND platform_id=".PLATFORM." and month(add_time) =month(curdate()) and year(add_time) = year(curdate())";

        //各个奖项明细/搜索
        $sql3 = "select SUM(money) from profit_copy where user_id=".$this->user_id." AND platform_id=".PLATFORM." AND month(add_time)=".$month." AND  year(add_time)=".$year." group by type";*/

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

    /***奖金补单***/
    public function commission_order_repair()
    {
        return $this->fetch();
    }


    /***分红点转入记录***/
    public function profit_sharing_point_log()
    {
        return $this->fetch();
    }

    /***分红点转账记录***/
    public function profit_sharing_point_to_money_log()
    {
        return $this->fetch();
    }

    /**我的团队***/
    public function myMembers(){
        $p = I('p/d',1);
        $limit =14;
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("uid");
        $page->setIndex($p);
        $page->setLimit($limit);
        $getPage = new Psp\Member\GetPage();
        $getPage->setUid($this->wh181_id);
        $getPage->setPagination($page);
        list($resp,$status) = GRPC('member')->GetMyMembers($getPage)->wait();

        $title = array(0=>'乡镇代理(LZ0)',1=>'县级代理(LZ1)',2=>'市级代理(LZ2)',3=>'省级代理(LZ3)',4=>'大区代理(LZ4)',5=>'全国代理(LZ5)',6=>'全球总代理(LZ6)',-1=>'乡镇代理(LZ0)');
        $level = array(0=>'普通会员',1=>'青铜会员',2=>'白银会员',3=>'铂金会员',4=>'钻石会员',-1=>'普通会员');
        //获取推荐人
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        if(!empty($resp)){
            foreach ($resp->getMyMember() as $k=>$v){
                $data[$k]['user_id'] = $v->getUserId();
                $data[$k]['wh181_id'] = $v->getWh181Id();
                $data[$k]['mobile'] = $v->getMobile();
                $data[$k]['real_name'] = $v->getRealName();
                $user_level = $v->getUserLevel();
                $data[$k]['user_level'] = $level[$user_level];
                $user_title = $v->getUserTitle();
                $data[$k]['user_title'] =$title[$user_title];

                $add_time = explode(':',$v->getAddTime());
                $data[$k]['add_time'] = trim($add_time[1]);
            }
            $total_count = $resp->getPageResult()->getTotalRecords();//总记录数
            $Page = new Page($total_count,$limit) ;
            $show = $Page->show();
            $this->assign('page',$show);
        }

        $this->assign('user',$user['result']);
        $this->assign('list',$data);
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
            //$user->setUid($uid);//会员id
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
//        $info['apply_id'] = $resp->getApplyId();//申请id
        $info['front_url'] = $resp->getFrontUrl();//身份证正面
        $info['back_url'] = $resp->getBackUrl();//身份证反面
        $info['real_name'] = $resp->getUserName();//姓名
        $info['phone'] = $resp->getPhone();//姓名
        $info['card_num'] = $resp->getCardNum();//身份证号
        $info['wh_id'] = $this->wh181_id;
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

        $can_use_money = $limit_money - $now_money - $last_money;
        $repayment_money = $now_money + $last_money; //应还总金额
        $Userlogic = new UsersLogic();
        $result=$Userlogic->MemberBuyUseList($this->user_id,$p,$type);  //明细  1支出 2还款
        //获取账户可用余额
        $balance = new AccountCenterLogic();
        $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);
        $balance = sprintf('%.2f',$accountData['balance']);
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        $this->assign('can_use_money',$can_use_money);
        $this->assign('limit_money',$limit_money);
        $this->assign('now_money',$now_money);
        $this->assign('last_money',$last_money);
        $this->assign('info',$info);
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
    public function applyMyBuy(){
        //读取会员职称
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];
        if($user['user_title'] <= 3 ){
            $this->ajaxReturn(json_encode(['status'=>-2,'msg'=>'您的职称等级不够,暂时无法申请']));
        }

        if(IS_POST){
            $data = I('post.');

            if(empty($data))
                $this->ajaxReturn(json_encode(['status'=>-5,'msg'=>'参数有误']));

            $apply = new Psp\Member\ApplyBuy();
            $apply->setUserId($this->user_id);
            $apply->setWhId($data['wh_id']);
            $apply->setUserName($data['real_name']);//姓名
            $apply->setCardNum($data['cardnum']); //身份证号码
            $apply->setPhone($data['tel']);//手机号码
            $apply->setFrontUrl($data['idcard_pic']);//身份证 正面
            $apply->setBackUrl($data['idcard_backpic']);// 反面
//        $apply->setHouseholdRegister($data['housebook_pic']);//户口本
            $apply->setOrgId(PLATFORM);//平台id
            list($resp) = GRPC('member')->AddMemberBuy($apply)->wait();
            $ret = $resp->getRet();
            $msg = $resp->getMsg();
            if($ret == 'ok'){
                $this->ajaxReturn(json_encode(['msg'=>'申请已提交,请耐心等待','status'=>1]));
            }else{
                $this->ajaxReturn(json_encode(['msg'=>"{$msg}",'status'=>-1]));
            }
        }

    }


    //买呗还款
    public function member_buy_repayment(){
        $paypwd = trim(I('post.pay_pwd/s'));
        $order_amount = I('post.order_amount');//订单金额
        $pay_month = I('post.time');
        $payment_way = I('post.payment_way'); //付款方式

        //钱包还款
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
            //获取账户可用余额
            $balance = new AccountCenterLogic();
            $accountData = $balance->getAccount($this->user_id,1,0,(int)PLATFORM);
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

            $whid=I('post.wh_id');
            $number=I('post.message_number');
            $is_talk=I('post.is_talk');

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



}

<?php
namespace Home\Controller;
use Home\Logic\OrderGoodsLogic;
use Home\Logic\StoreLogic;
use Home\Logic\UsersLogic;
use Think\Page;
use Think\Verify;
use Think\AjaxPage;
class UserController extends BaseController {

    public $user_id = 0;
    public $user = array();
    
    public function _initialize() {
        parent::_initialize();
        if(session('?user'))
        {
            $user = session('user');
            $user = M('users')->where("user_id = {$user['user_id']}")->find();
            session('user',$user);  //覆盖session 中的 user
            // echo"<pre>";
            // var_dump($user['wh_id']);die;
            $this->user = $user;
            $this->user_id = $user['user_id'];
        
            
            $this->assign('user',$user); //存储用户信息
            $this->assign('user_id',$this->user_id);
        }else{
            $nologin = array('login','signUp','loginIn','isUseMobile','isUseEmail','checktuijian','pop_login','do_login','logout','verify','set_pwd','finished','verifyHandle','reg','send_sms_reg_code','identity','check_validate_code', 'forget_pwd','check_captcha','check_username','send_validate_code',
            );
            if(!in_array(ACTION_NAME,$nologin)){
                header("location:".U('Home/User/login'));
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
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];
        $level = M('user_level')->select();
        $level = convert_arr_key($level,'level_id');
        //显示未读公告条数
        $no_read=M('notice_log')->select();
        if(!$no_read){
            $count=M('notice')->count();
                
        }else{
            $notice_id=M('notice')->field('notice_id')->select();
            //将获取的id变成一维数组
            $notice_id=array_column($notice_id, 'notice_id');
            $where['user_id']=$this->user_id;
            $notice_log=M('notice_log')->where($where)->field('notice_id')->select();
            //将获取的id变成一维数组
            $notice_log=array_column($notice_log, 'notice_id');
            $notice_log=implode(",",$notice_log);
            $notice_log=explode(",",$notice_log);
            $c=array_diff($notice_id,$notice_log);
            $count=count($c);
           
        }
        $this->assign('count',$count);
        $this->assign('level',$level);
        $this->assign('user',$user);
        $this->display();
    }


    public function logout(){
        setcookie('uname','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        session_unset();
        session_destroy();
        redirect(U('Index/index'));
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
        $this->assign('user',$user);
        $this->assign('account_log',$account_log);
        $this->assign('page',$data['show']);
        $this->assign('active','account');
        $this->display();
    }
    /*
     * 优惠券列表
     */
    public function coupon(){
        $logic = new UsersLogic();
        $data = $logic->get_coupon($this->user_id,$_REQUEST['type']);
        $coupon_list = $data['result'];
        $this->assign('coupon_list',$coupon_list);
        $this->assign('page',$data['show']);
        $this->assign('active','coupon');
        $this->display();
    }
    /**
     *  登录
     */
    public function login(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl',$referurl);
        $this->display();
    }

    public function pop_login(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl',$referurl);
        $this->display();
    }


    /**
     * do_login 执行登陆操作,本地及沃好交换数据
     * @Authorhtl {Ning<nk11@qq.com>}
     * @DateTime  2017-05-12T13:08:45+0800
     * @return json{bool}
     */
    public function do_login(){
        $username = I('post.username');
        $password = I('post.password');
        $username = trim($username);
        $password = trim($password);
        $verify_code = I('post.verify_code');

        $verify = new Verify();
        if (!$verify->check($verify_code,'user_login'))
        {
            $res = array('status'=>0,'msg'=>'인증 코드 오류');
            exit(json_encode($res));
        }

//        $user = M('users')->where("OR mobile='{$username}' OR email='{$username}' OR wh_id='{$username}'")->find();
//        if ($user){//本地有数据取本地数据
            $logic = new UsersLogic();
            $res = $logic->login($username,$password);
//        }

        /* //此处将状态判断去掉 $res['status']!=1||
         if(empty($user)){//本地没有数据或本地账号异常 访问walhao.com的数据

             $url = "http://walhao.com/home/userapi/do_login";
             $data = ['username'=>$username,'password'=>$password];

             unset($res);//清空本地查询数据
             $res = httpRequest($url,"POST",$data);
             $res = json_decode($res,true);
             unset($res['result']['user_id']);//释放掉walhao数据库的uid信息

             if($res['status'] == 1&&empty($user)){//沃好可以正常登陆,本地没有数据---添加本地数据,修改数据包用户id
                 //更新入库前取出 最后一条 wh_id
                 $wh_id = M('users')->field('wh_id')->order('user_id desc')->limit(1)->select();
                 $last_wh_id = ((int)$wh_id[0]['wh_id']) + 1; //取值 +1
                 $res['result']['wh_id'] = $last_wh_id;
                 //end
                 $res['result']['user_id']=M('users')->add($res['result']);
             }
             if($res['status'] == 1&&$user){//沃好可以正常登陆本地数据不能正常登陆--更新本地数据
                 M('users')->where(['user_id'=>$user['user_id']])->save($res['result']);
                 $res['result']['user_id']=$user['user_id'];
             }
         }*/

        if($res['status'] == 1){
            $res['url'] =  urldecode(I('post.referurl'));
            $res['result']['nickname'] = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('user_id',$res['result']['user_id'],null,'/');
            setcookie('is_distribut',$res['result']['is_distribut'],null,'/');
            setcookie('uname',urlencode($res['result']['nickname']),null,'/');
            setcookie('cn',0,time()-3600,'/');
            session('user',$res['result']);
            $cartLogic = new \Home\Logic\CartLogic();
            $cartLogic->login_cart_handle($this->session_id,$res['result']['user_id']);  //用户登录后 需要对购物车 一些操作
        }
        exit(json_encode($res));
    }
    /**
     *  注册
     */
    public function reg(){
        $this->error('No open registration');//英文版,不开放注册
        exit;
        if($this->user_id > 0) header("Location: ".U('Home/User/index'));

        if(IS_POST){
            $logic = new UsersLogic();
            //验证码检验
            $this->verifyHandle('user_reg');
            $username = I('post.username','');
            $password = I('post.password','');
            $password2 = I('post.password2','');
            $code = I('post.code','');
            $session_id = session_id();
            //是否开启注册验证码机制
            if(check_mobile($username)){
                $check_code = $logic->check_validate_code($code, $username, $session_id);
                if($check_code['status'] != 1){
                    $this->error($check_code['msg']);
                }
            }
            //是否开启注册邮箱验证码机制
            if(check_email($username)){
                $check_code = $logic->check_validate_code($code, $username);
                if($check_code['status'] != 1){
                    $this->error($check_code['msg']);
                }
            }
            $data = $logic->reg($username,$password,$password2);
            if($data['status'] != 1){
                $this->error($data['msg']);
            }
            session('user',$data['result']);
            setcookie('user_id',$data['result']['user_id'],null,'/');
            setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
            $nickname = empty($data['result']['nickname']) ? $username : $data['result']['nickname'];
            setcookie('uname',$nickname,null,'/');
            $cartLogic = new \Home\Logic\CartLogic();
            $cartLogic->login_cart_handle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作

            $this->success($data['msg'],U('Home/User/index'));
            exit;
        }
        $this->assign('regis_sms_enable',tpCache('sms.regis_sms_enable')); // 注册启用短信：
        $this->assign('regis_smtp_enable',tpCache('smtp.regis_smtp_enable')); // 注册启用邮箱：
        $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        $this->display();
    }

    /*
     * 订单列表
     */
    public function order_list(){
        $where = ' user_id='.$this->user_id;
        //条件搜索
        $start_time = I('start_time');
        $end_tine = I('end_time');
        if(!empty($start_time) ^ !empty($start_time)){
            $this->error('The order time inquiry condition is incomplete');
        }
        if(!empty($start_time) && !empty($start_time)){
            $add_start_time = strtotime($start_time);
            $add_end_time = strtotime($end_tine);
            if($add_start_time > $add_end_time){
                $this->error('The order time inquiry condition is wrong');
            }
            $where .=" and add_time >= ".$add_start_time." and add_time <= ".$add_end_time;
        }

        if(I('get.type')){
            $where .= C(strtoupper(I('get.type')));
        }
        // 搜索订单 根据商品名称 或者 订单编号
        $search_key = trim(I('search_key'));
        if($search_key)
        {
            $where .= " and (order_sn like '%$search_key%' or order_id in (select order_id from `".C('DB_PREFIX')."order_goods` where goods_name like '%$search_key%') ) ";
        }
        $count = M('order')->where($where)->count();
        $Page       = new Page($count,5);
        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach($order_list as $k=>$v)
        {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        $store_id_list = get_arr_column($order_list, 'store_id');
        if(!empty($store_id_list))
            $store_list = M('store')->where("store_id in (".  implode(',', $store_id_list).")")->getField('store_id,store_name,store_qq');
        $this->assign('store_list',$store_list);
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('page', $show);
        $this->assign('lists', $order_list);
        $this->assign('active','order_list');
        $this->assign('active_status',I('get.type'));
        $this->assign('now',time());
        $this->display();
    }

    /*
     * 订单详情
     */
    public function order_detail(){

        $id = I('get.id');
        $map['order_id'] = $id;
        if(empty($map['order_id']))
            $this->redirect('Home/User/order_list');

        $map['user_id'] = $this->user_id;
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性

        if(!$order_info){
            $this->error('No order information was obtained');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];
        //获取订单进度条
        $sql = "SELECT action_id,log_time,status_desc,order_status FROM ((SELECT * FROM __PREFIX__order_action WHERE order_id = $id AND status_desc <>'' ORDER BY action_id) AS a) GROUP BY status_desc ORDER BY action_id";
        $items = M()->query($sql);
        $items_count = count($items);
        // $region_list = get_region_list();

        $invoice_no = M('DeliveryDoc')->where("order_id = $id")->getField('invoice_no',true);
        $order_info[invoice_no] = implode(' , ', $invoice_no);
        $store = M('store')->where("store_id = {$order_info['store_id']}")->find(); // 找出这个商家
        // 店铺地址id
        $ids[] = $store['province_id'];
        $ids[] = $store['city_id'];
        $ids[] = $store['district'];

        $ids[] = $order_info['province'];
        $ids[] = $order_info['city'];
        $ids[] = $order_info['district'];
        if(!empty($ids))
            $regionLits = M('region')->where("id in (".  implode(',', $ids).")")->getField("id,name");

        //获取订单操作记录
        $order_action = M('order_action')->where(array('order_id'=>$id))->select();
        $this->assign('store',$store);
        $this->assign('regionLits',$regionLits);
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        // $this->assign('region_list',$region_list);
        $this->assign('regionLits',$regionLits);
        $this->assign('order_info',$order_info);
        $this->assign('order_action',$order_action);
        $this->assign('active','order_list');
        $this->display();
    }

    /*
     * 取消订单
     */
    public function cancel_order(){
        $id = I('get.id');
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
        $address_lists = get_user_address_list($this->user_id);
        $region_list = get_region_list();
        $this->assign('region_list',$region_list);
        $this->assign('lists',$address_lists);
        $this->assign('active','address_list');

        $this->display();
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
        $p = M('region')->where(array('parent_id'=>0,'level'=> 1))->select();
        $this->assign('province',$p);
        $this->display('edit_address');

    }

    /*
     * 地址编辑
     */
    public function edit_address(){
        header("Content-type:text/html;charset=utf-8");
        $id = I('get.id');
        $address = M('user_address')->where(array('address_id'=>$id,'user_id'=> $this->user_id))->find();
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
        $p = M('region')->where(array('parent_id'=>0,'level'=> 1))->select();
        $c = M('region')->where(array('parent_id'=>$address['province'],'level'=> 2))->select();
        $d = M('region')->where(array('parent_id'=>$address['city'],'level'=> 3))->select();
        if($address['twon']){
            $e = M('region')->where(array('parent_id'=>$address['district'],'level'=>4))->select();
            $this->assign('twon',$e);
        }

        $this->assign('province',$p);
        $this->assign('city',$c);
        $this->assign('district',$d);
        $this->assign('address',$address);
        $this->display();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default(){
        $id = I('get.id');
        M('user_address')->where(array('user_id'=>$this->user_id))->save(array('is_default'=>0));
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->save(array('is_default'=>1));
        if(!$row)
            $this->error('error');
        $this->success("success");
    }

    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('get.id');

        $address = M('user_address')->where("address_id = $id")->find();
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if($address['is_default'] == 1)
        {
            $address2 = M('user_address')->where("user_id = {$this->user_id}")->find();
            $address2 && M('user_address')->where("address_id = {$address2['address_id']}")->save(array('is_default'=>1));
        }
        if(!$row)
            $this->error('error',U('User/address_list'));
        else
            $this->success("Success",U('User/address_list'));
    }

    /*
  * ajax 获取用户收货地址
  */
    public function ajaxAddress(){
        $address_list = M('UserAddress')->where("user_id = {$this->user_id}")->select();
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
            $regionList = M('region')->where("id in ($area_id)")->getField('id,name');
            $this->assign('regionList', $regionList);
        }
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('address_list', $address_list);
        $this->display('ajax_address');
    }

    /*
  * ajax 修改用户收货地址
  */
    public function chooseAddress()
    {
        $ad_id = I('post.ad_id');
        $order_id = I('post.order_id');
        $address = M('UserAddress')->where("address_id = {$ad_id}")->find();
        $res = M('order')->where("order_id =".$order_id)->field('consignee,country,province,city,district,twon,address,zipcode,mobile')->filter('strip_tags')->save($address);
        $this->ajaxReturn($res);
    }

    /*
     * 评论晒单
     */
    public function comment(){
        $user_id = $this->user_id;
        $status = I('get.status',-1);
        $logic = new UsersLogic();
        $data = $logic->get_comment($user_id,$status); //获取评论列表
        $this->assign('page',$data['show']);// 赋值分页输出
        $this->assign('comment_page',$data['page']);
        $this->assign('comment_list',$data['result']);
        $this->assign('active','comment');
        $this->display();
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
        $user_info = session('user');
        $comment_img = serialize(I('comment_img')); // 上传的图片文件
        $add['goods_id'] = I('goods_id');
        $add['email'] = $user_info['email'];
        //$add['nick'] = $user_info['nickname'];
        $add['username'] = $user_info['nickname'];
        $add['order_id'] = I('order_id');
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

    /**
     * @time 2016/8/5
     * @author dyr
     * 订单评价列表
     */
    public function comment_list()
    {
        $order_id = I('get.order_id');
        $store_id = I('get.store_id');
        $part_finish = I('get.part_finish', 0);
        if (empty($order_id) || empty($store_id)) {
            $this->error("Parameter error");
        } else {
            //查找店铺信息
            $store_where['store_id'] = $store_id;
            $store_info = M('store')->field('store_id,store_name,store_phone,store_address,store_logo')->where($store_where)->find();
            if (empty($store_info)) {
                $this->error("The merchant does not exist");
            }
            //查找订单是否已经被用户评价
            $order_comment_where['order_id'] = $order_id;
            $order_comment_where['deleted'] = 0;
            $order_info = M('order')->field('order_id,order_sn,is_comment,add_time')->where($order_comment_where)->find();
            //查找订单下的所有未评价的商品
            $order_goods_logic = new OrderGoodsLogic();
            $no_comment_goods_list = $order_goods_logic->get_no_comment_goods_list($order_id);
            $goods_id_list = array();
            foreach ($no_comment_goods_list as $key => $value) {
                array_push($goods_id_list, $value['goods_id']);
            }
            $this->assign('goods_id_list', $goods_id_list);
            $this->assign('store_info', $store_info);
            $this->assign('order_info', $order_info);
            $this->assign('no_comment_goods_list', $no_comment_goods_list);
            $this->assign('no_comment_goods_list_count',count($no_comment_goods_list));
            $this->assign('part_finish', $part_finish);
            $this->display();
        }
    }

    /**
     * @time 2016/8/5
     * @author dyr
     *  添加评论
     */
    public function conmment_add()
    {
        $remark = I("post.remark");
        $anonymous = I('post.anonymous');
        $store_score['describe_score'] = I('post.store_packge_hidden');
        $store_score['seller_score'] = I('post.store_speed_hidden');
        $store_score['logistics_score'] = I('post.store_sever_hidden');
        $order_id = $store_score['order_id'] = $store_score_where['order_id'] = I('post.order_id');
        $store_score['user_id'] = $store_score_where['user_id'] = $this->user_id;
        $store_score_where['deleted'] = 0;
        $store_id = M('order')->where(array('order_id' => $store_score_where['order_id']))->getField('store_id');
        $store_score['store_id'] = $store_id;
        //处理订单评价
        if (!empty($store_score['describe_score']) && !empty($store_score['seller_score']) && !empty($store_score['logistics_score'])) {
            $order_comment = M('order_comment')->where($store_score_where)->find();
            if ($order_comment) {
                M('order_comment')->where($store_score_where)->save($store_score);
                M('order')->where(array('order_id' => $order_id))->save(array('is_comment' => 1));
            } else {
                M('order_comment')->add($store_score);//订单打分
                M('order')->where(array('order_id' => $order_id))->save(array('is_comment' => 1));
            }
            //订单打分后更新店铺评分
            $store_logic = new StoreLogic();
            $store_logic->updateStoreScore($store_id);
        }
        //处理商品评价
        if (is_array($remark)) {
            foreach ($remark as $key => $value) {
                if (!empty($value['rank']) && !empty($value['content'])) {
                    $comment['goods_id'] = $key;
                    $comment['order_id'] = $store_score['order_id'];
                    $comment['store_id'] = $store_id;
                    $comment['user_id'] = $this->user_id;
                    $comment['content'] = $value['content'];
                    $comment['ip_address'] = get_client_ip();
                    $comment['spec_key_name'] = $value['spec_key_name'];
                    $comment['goods_rank'] = $value['rank'];
                    $comment['img'] = (empty($value['commment_img'][0])) ? '' : serialize($value['commment_img']);
                    $comment['impression'] = (empty($value['tag'][0])) ? '' : implode(',', $value['tag']);
                    $comment['is_anonymous'] = empty($anonymous) ? 1 : 0;
                    $comment['add_time'] = time();
                    M('comment')->add($comment);//想评论表插入数据
                    M('order_goods')->where(array('order_id' => $store_score['order_id'], 'goods_id' => $key))->save(array('is_comment' => 1));
                    M('goods')->where(array('goods_id' => $key))->setInc('comment_count', 1);
                    unset($comment);
                }
            }
        }
        //查找订单下是否有没有评价的商品
        $order_goods_logic = new OrderGoodsLogic();
        $no_comment_goods_list = $order_goods_logic->get_no_comment_goods_list($order_id);
        $no_comment_goods_count = count($no_comment_goods_list);
        if ($no_comment_goods_count > 0) {
            redirect(U('User/comment_list', array('part_finish' => 1, 'order_id' => $order_id, 'store_id' => $store_id)));
        } else {
            redirect(U('User/comment_list', array('order_id' => $order_id, 'store_id' => $store_id)));
        }
    }

    /**
     *  点赞
     *  @author dyr
     */
    public function ajaxZan(){
        $comment_id = I('post.comment_id');
        $user_id = $this->user_id;
        $comment_info = M('comment')->where(array('comment_id'=>$comment_id))->find();
        $comment_user_id_array = explode(',', $comment_info['zan_userid']);
        if (in_array($user_id, $comment_user_id_array)) {
            $result['success'] = 0;
        }else{
            array_push($comment_user_id_array,$user_id);
            $comment_user_id_string = implode(',',$comment_user_id_array);
            $comment_data['zan_num'] = $comment_info['zan_num'] + 1;
            $comment_data['zan_userid'] = $comment_user_id_string;
            M('comment')->where(array('comment_id'=>$comment_id))->save($comment_data);
            $result['success'] = 1;
        }
        exit(json_encode($result));
    }

    /**
     * 添加回复
     * @author dyr
     */
    public function reply_add()
    {
        $comment_id = I('post.comment_id');
        $reply_id = I('post.reply_id', 0);
        $content = I('post.content');
        $to_name = I('post.to_name', '');
        $goods_id = I('post.goods_id');
        $reply_data = array(
            'comment_id' => $comment_id,
            'parent_id' => $reply_id,
            'content' => $content,
            'user_name' => $this->user['nickname'],
            'to_name' => $to_name,
            'reply_time' => time()
        );
        $db_prefix = C('DB_PREFIX');
        $table_array = array($db_prefix.'order'=>'o');
        $where = array('o.user_id' => $this->user_id, 'og.goods_id' => $goods_id, 'o.pay_status' => 1);
        $user_goods_count = M()
            ->table($table_array)
            ->join('left join __ORDER_GOODS__ AS og ON o.order_id = og.order_id')
            ->where($where)
            ->count();
        if($user_goods_count > 0){
            M('reply')->add($reply_data);
            M('comment')->where(array('comment_id'=>$comment_id))->setInc('reply_num');
            $json['success'] = true;
        }else{
            $json['success'] = false;
            $json['msg'] = 'Only when you buy this product can you reply';
        }
        $this->ajaxReturn($json);
    }

    /**
     * 个人信息
     */
    public function info(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
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
                $this->error("Save failed");
            $this->success("Save successful");
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$user_info['province'],'level'=>2))->select();
        //获取订单地区
        $area =  M('region')->where(array('parent_id'=>$user_info['city'],'level'=>3))->select();

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('user',$user_info);
        $this->assign('sex',C('SEX'));
        $this->assign('active','info');
        $this->display();
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
            $old_email = I('post.old_email'); //旧邮箱
            $code = I('post.code');
            $info = session('validate_code');
            if(!$info)
                $this->error('Illegal operation');
            if($info['time']<time()){
                session('validate_code',null);
                $this->error('Verify timeout. Please try again');
            }
            //检查原邮箱是否正确
            if($user_info['email_validated'] == 1 && $old_email != $user_info['email'])
                $this->error('Original mailbox match error');
            //验证邮箱和验证码
            if($info['sender'] == $email && $info['code'] == $code){
                session('validate_code',null);
                if(!$userLogic->update_email_mobile($email,$this->user_id))
                    $this->error('The mailbox already exists');
                $this->success('Bind successfully',U('Home/User/index'));
                exit;
            }
            $this->error('The mailbox validation code does not match');
        }
        $this->assign('step',$step);
        $this->assign('user_info',$user_info);
        $this->display();
    }


    /*
    * 手机验证
    */
    public function mobile_validate(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); //获取用户信息
        $user_info = $user_info['result'];
        $config = F('sms','',TEMP_PATH);
        $sms_time_out = $config['sms_time_out'];
        $step = I('get.step',1);
        if(IS_POST){
            $mobile = I('post.mobile');
            $old_mobile = I('post.old_mobile');
            $code = I('post.code');
            $session_id = I('unique_id',session_id());

            $logic = new UsersLogic();
            $res = $logic->check_validate_code($code, $mobile , $session_id);

            if(!$res && $res['status'] != 1)$this->error($res['msg']);

            //检查原手机是否正确
            if($user_info['mobile_validated'] == 1 && $old_mobile != $user_info['mobile'])
                $this->error('Original cell phone number error');
            //验证手机和验证码

            if($res['status'] == 1){
                //验证有效期
                if(!$userLogic->update_email_mobile($mobile,$this->user_id,2))
                    $this->error('Mobile already exists');
                $this->success('Bind successfully',U('Home/User/index'));
                exit;
            }else{
                $this->error($res['msg']);
            }

        }
        $this->assign('time',$sms_time_out);
        $this->assign('step',$step);
        $this->assign('user_info',$user_info);
        $this->display();
    }

    /**
     *我的收藏
     */
    public function goods_collect()
    {
        $type = I('get.type', 1);
        if ($type == 1) {
            //商品收藏
            $userLogic = new UsersLogic();
            $data = $userLogic->get_goods_collect($this->user_id);
            $this->assign('page', $data['show']);// 赋值分页输出
            $this->assign('lists', $data['result']);
            $this->assign('active', 'goods_collect');
            $this->display();
        } else {
            //店铺收藏
            $sc_id = I('get.sc_id');
            $store_class = M('store_class')->field('sc_id,sc_name')->where('')->select();
            $storeLogic = new StoreLogic();
            $store_collect_list = $storeLogic->getCollectStore($this->user_id, $sc_id);
            $this->assign('page', $store_collect_list['show']);// 赋值分页输出
            $this->assign('store_collect_list', $store_collect_list['result']);
            $this->assign('store_class', $store_class);//店铺分类
            $this->display('bookmark');
        }
    }

    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect(){
        $id = I('get.id');
        if(!$id)
            $this->error("The ID parameter is missing");
        $row = M('goods_collect')->where(array('collect_id'=>$id,'user_id'=>$this->user_id))->delete();
        if(!$row)
            $this->error("Delete failed");
        $this->success('Delete successful');
    }

    /**
     *  删除一个收藏店铺
     */
    public function del_store_collect(){
        $id = I('get.log_id');
        if(!$id)
            $this->error("The ID parameter is missing");
        $store_id = M('store_collect')->where(array('log_id'=>$id,'user_id'=>$this->user_id))->getField('store_id');
        $row = M('store_collect')->where(array('log_id'=>$id,'user_id'=>$this->user_id))->delete();
        M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
        if(!$row)
            $this->error("Delete failed");
        $this->success('Delete successful');
    }

    /*
     * 密码修改
     */
    public function password(){
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if($user['mobile'] == ''&& $user['email'] == '')
            $this->error('Please bind your cell phone or mailbox first',U('Home/User/info'));
        if(IS_POST){
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id,I('post.old_password'),I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            if($data['status'] == -1)
                $this->error($data['msg']);
            $this->success($data['msg']);
            exit;
        }
        $this->display();
    }

    public function bind_remove()
    {

    }

    public function forget_pwd(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/Index'));
        }
        if(IS_POST){
            $logic = new UsersLogic();
            $username = I('post.username');
            $code = I('post.code');
            $new_password = I('post.new_password');
            $confirm_password = I('post.confirm_password');
            $pass = false;

            //检查是否手机找回
            if(check_mobile($username)){
                if(!$user = get_user_info($username,2))
                    $this->error('Account does not exist');
                $check_code = $logic->sms_code_verify($username,$code,$this->session_id);
                if($check_code['status'] != 1)
                    $this->error($check_code['msg']);
                $pass = true;
            }
            //检查是否邮箱
            if(check_email($username)){
                if(!$user = get_user_info($username,1))
                    $this->error('Account does not exist');
                $check = session('forget_code');
                if(empty($check))
                    $this->error('Illegal Operation');
                if(!$username || !$code || $check['email'] != $username || $check['code'] != $code)
                    $this->error('The mailbox validation code does not match');
                $pass = true;
            }
            if($user['user_id'] > 0 && $pass)
                $data = $logic->password($user['user_id'],'',$new_password,$confirm_password,false); // 获取用户信息
            if($data['status'] != 1)
                $this->error($data['msg'] ? $data['msg'] :  'Operation failed');
            $this->success($data['msg'],U('Home/User/login'));
            exit;
        }
        $this->display();
    }

    public function set_pwd(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/Index'));
        }
        $check = session('validate_code');
        $logic = new UsersLogic();
        //签名
        /* if(empty($check)){
            header("Location:".U('Home/User/forget_pwd'));
        }elseif($check['is_check']==0){
            $this->error('验证码还未验证通过',U('Home/User/forget_pwd'));
        } */
        if(IS_POST){
            $password = I('post.password');
            $password2 = I('post.password2');
            if($password2 != $password){
                $this->error('The two password is inconsistent',U('Home/User/forget_pwd'));
            }
            if($check['is_check']==1){
                $user = M('users')->where("mobile = '{$check['sender']}' or email = '{$check['sender']}'")->field('user_id,pwd_token')->select();
                $pwd_token = $user[0]['pwd_token'];
                $uid = $user[0]['user_id'];
                $newPwd=sha1('!#*'.trim("$password").$pwd_token.'tps');
                M('users')->where("user_id=".$uid)->save(array('password'=>$newPwd));
                session('validate_code',null);
                header("Location:".U('Home/User/finished'));
            }else{
                $this->error('The verification code has not been validated',U('Home/User/forget_pwd'));
            }
        }
        $this->display();
    }

    public function finished(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/Index'));
        }
        $this->display();
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
        if(!empty($username)){
            $count = M('users')->where("mobile='$username' or wh_id='$username'")->count();
            exit(json_encode(intval($count)));
        }else{
            exit(json_encode(0));
        }
    }

    public function identity(){
        if($this->user_id > 0){
            header("Location: ".U('Home/User/Index'));
        }
        $username = I('post.username');
        $userinfo = array();
        if($username){
            $userinfo = M('users')->where("email='$username' or mobile='$username'")->find();
            $userinfo['username'] = $username;
            session('userinfo',$userinfo);
        }else{
            $this->error('Parameter error！！！');
        }
        if(empty($userinfo)){
            $this->error('Illegal request！！！');
        }
        unset($user_info['password']);
        $this->assign('userinfo',$userinfo);
        $this->display();
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("Verify code error");
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
            'fontSize' => 50,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => true,
            'reset' => false,
            'fontttf' => '4.ttf',
            'codeSet' => '0123456789'
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

    public function order_confirm(){
        $id = I('get.id',0);
        $data = confirm_order($id,$this->user_id);
        if(!$data['status'])
            $this->error($data['msg']);
        else{
            // $seller = A('Seller/Admin');
            // $seller->login_task();
            $this->success($data['msg']);
        }

        //重定向到自动结算方法中
        // $this->redirect('Seller/Admin/login_task');
    }
    /**
     * 申请退货
     */
    public function return_goods()
    {
        $order_id = I('order_id',0);
        $order_sn = I('order_sn',0);
        $goods_id = I('goods_id',0);
        $spec_key = I('spec_key');

        // $action = A('Home/Sendorder');//退货触发推送退货单到tps,取消12小时之前订单的机制
        // $action->amatic();

        $c = M('order')->where("order_id = $order_id and user_id = {$this->user_id}")->find();
        $confirm_time_config = tpCache('shopping.auto_transfer_date');

        if(count($c) == 0)
        {
            $this->error('Illegal operation');
            exit;
        }
        if($c['order_status']==1){
            $this->error('The order is under processing, please contact the merchant to return it','',5);
            exit;
        }
        $confirm_time = $confirm_time_config * 24 * 60 * 60;
        // $confirm_time =strtotime('+7 day');
        if (!empty($c['confirm_time']) && (time() - $c['confirm_time']) > $confirm_time ) {
            $this->error('Return time has been more than ' . $confirm_time_config . "days");
            exit;
        }
        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id  and spec_key = '$spec_key'")->find();
        if(!empty($return_goods))
        {
            $this->success('Application for returned goods has been submitted!',U('Home/User/return_goods_info',array('id'=>$return_goods['id'])));
            exit;
        }
        if(IS_POST)
        {
            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = I('type'); // 服务类型  退货 或者 换货
            $data['reason'] = I('reason'); // 问题描述
            $data['imgs'] = I('imgs'); // 用户拍照的相片
            $data['spec_key'] = I('spec_key'); // 商品规格
            $data['store_id'] = M('order')->where("order_id = $order_id")->getField('store_id'); // 店铺id
            M('return_goods')->add($data);
            $this->success('Successful application, customer service will help you deal with for the first time',U('Home/User/order_list'));
            exit;
        }

        $goods = M('goods')->where("goods_id = $goods_id")->find();
        $store = M('store')->where(array('store_id' => $goods['store_id']))->find();
        $province_name = M('region')->where(array('id'=>$store['province_id']))->getField('name');
        $city_name= M('region')->where(array('id'=>$store['city_id']))->getField('name');
        $district_name = M('region')->where(array('id'=>$store['district']))->getField('name');
        $store_region = $province_name . ',' . $city_name . ',' . $district_name . ',';
        $this->assign('goods', $goods);
        $this->assign('order_id',$order_id);
        $this->assign('order_sn',$order_sn);
        $this->assign('goods_id',$goods_id);
        $this->assign('store_region', $store_region);
        $this->assign('store', $store);
        $this->display();
    }

    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count,10);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
        $this->display();
    }

    /**
     *  退货详情
     */
    public function return_goods_info()
    {
        $id = I('id',0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = M('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();
        $this->assign('goods',$goods);
        $this->assign('return_goods',$return_goods);
        $this->display();
    }

    /**
     * 安全设置
     */
    public function safety_settings()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $this->assign('user',$user_info);
        $this->display();
    }

    /**
     * 申请提现记录
     */
    public function withdrawals(){
        //C('TOKEN_ON',true);
        $data = I('post.');
        if(I('post.with_id')){
            $data['status'] = 3;
            M('withdrawals')->where(array('id' => $data['with_id']))->save($data);
            $end['user_money'] = $data['user_money'] + $data['with_money'];
            M('users')->where(array('user_id' => $this->user_id))->save($end);
            $this->success("성공했습니다.");
            exit;
        }else {
            if (IS_POST) {
                $this->verifyHandle('withdrawals');
                $data['user_id'] = $this->user_id;
                $data['create_time'] = time();
                $distribut_min = tpCache('distribut.min'); // 最少提现额度
                $distribut_need = tpCache('distribut.need'); //满多少才能提
                if ($data['money'] < $distribut_min) {
                    $this->error('每次最少提现额度' . $distribut_min);
                }
                if ($data['money'] > $this->user['user_money']) {
                    $this->error("你最多可提现{$this->user['user_money']}账户余额.");
                }
                if ($this->user['user_money'] < $distribut_need) {
                    $this->error('账户余额最少达到' . $distribut_need . '才能提现');
                }

                $withdrawal = M('withdrawals')->where(array('user_id' => $this->user_id, 'status' => 0))->sum('money');
                if ($this->user['user_money'] < ($withdrawal + $data['money'])) {
                    $this->error('您有提现申请待处理，本次提现余额不足');
                }
                if($this->user['user_money'] > $data['user_money'] ) {
                    if (M('withdrawals')->add($data)) {
                        $bank['bank_name'] = $data['bank_name'];
                        $bank['bank_card'] = $data['account_bank'];
                        $bank['turename'] = $data['account_name'];
                        $bank['user_money'] = $this->user['user_money'] - $data['money'];
                        $this->user['user_money'] = $bank['user_money'];
                        M('users')->where(array('user_id' => $this->user_id))->save($bank);
                        $this->success("已提交申请");
                        exit;
                    } else {
                        $this->error('提交失败,联系客服!');
                    }
                }else{
                    $this->error('本次提现余额不足!');
                }
            }
        }
        $money = $this->user['user_money'] +$data['money'];
        $where = " user_id = {$this->user_id}";
        $count = M('withdrawals')->where($where)->count();
        $page = new Page($count,16);
        $show = $page->show();
        $list = M('withdrawals')->where($where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        $this->assign('show',$show);// 赋值分页输出
        $this->assign('list',$list); // 下线
        $this->assign('money',$money);
        $this->display();
    }


    public  function recharge(){
        if(IS_POST){
            $user = session('user');
            $data['user_id'] = $this->user_id;
            $data['nickname'] = $user['nickname'];
            $data['account'] = I('account');
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
            if($order_id){
                $url = U('Home/Payment/getPay',array('pay_radio'=>$_REQUEST['pay_radio'],'order_id'=>$order_id));
                redirect($url);
            }else{
                $this->error('提交失败,参数有误!');
            }
        }

        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and scene in(0,2)")->select();
        $paymentList = convert_arr_key($paymentList, 'code');
        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include 'Application/Home/Conf/bank.php'; // 银行对应图片
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('bankCodeList',$bankCodeList);

        $count = M('recharge')->where(array('user_id'=>$this->user_id))->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $recharge_list = M('recharge')->where(array('user_id'=>$this->user_id))->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('recharge_list',$recharge_list);//充值记录

        $count2 = M('account_log')->where(array('user_id'=>$this->user_id,'user_money'=>array('gt',0)))->count();
        $Page2 = new Page($count2,10);
        $consume_list = M('account_log a')->field("a.*,t.order_sn")->join("left join tp_order t on a.order_id=t.order_id")->where(array('a.user_id'=>$this->user_id,'a.user_money'=>array('gt',0)))->limit($Page2->firstRow.','.$Page2->listRows)->select();

        $user_profit = M('user_account')->where(['user_id'=>$this->user_id])->getField('user_money');

        //转账记录

        $count_transfer = M('user_transfer_log')->where(array('user_id'=>$this->user_id))->count();
        $Page_transfer = new Page($count,10);
        $show_transfer = $Page->show();
        $transfer_list = M('user_transfer_log')
            ->where("transfer_id = $this->user_id or acceptor_id = $this->user_id")
            ->order('create_time desc')
            ->limit($Page_transfer->firstRow.','.$Page_transfer)
            ->select();
        $data = [];
        foreach ($transfer_list as $k=>$v){

            $v['out'] = M('users')->where(['user_id'=>$v['transfer_id']])->getField('wh_id');
            $v['in'] = M('users')->where(['user_id'=>$v['acceptor_id']])->getField('wh_id');
            $data[] = $v;
        }

        $this->assign('page_transfer',$show_transfer);
        $this->assign('transfer_list',$data);//记录



        $this->assign('user_profit',$user_profit);//收益账户金额
        $this->assign('consume_list',$consume_list);//消费记录
        $this->assign('page2',$Page2->show());
        $this->assign('point_rate', tpCache('shopping.point_rate'));
        $this->display();
    }

    /**
     *  用户消息通知
     * @author dyr
     * @time 2016/09/01
     */
    public function message_notice()
    {
        $notice=M('notice')->select();
        $user_id=$this->user_id;
        $this->assign('user_id',$user_id);
        $this->assign('messages', $notice);
        $this->display();
    }

    /**
     * ajax用户消息通知请求
     * @author dyr
     * @time 2016/09/01
     */
    public function ajax_message_notice()
    {
         $notice_id= I('type',0);
         $user_id=I('user_id',0);
        $where['notice_id']=$notice_id;
        $we['user_id']=$user_id;
        $no_read=M('notice_log')->where($we)->select();
        $data['user_id']=$user_id;
        $data['notice_id']=$where['notice_id'];
        if(!$no_read){
            M('notice_log')->add($data);
        }else{

            
            $notice_log=M('notice_log')->where($we)->field('notice_id')->select();
            //将获取的id变成一维数组
            $notice_log=array_column($notice_log, 'notice_id');
            $notice_log=implode(",",$notice_log);
            $notice_log=explode(',',$notice_log);
            $is_in=in_array($notice_id,$notice_log);

            if(!$is_in){
                $we['user_id']=$user_id;
                $notice_log=implode(',',$notice_log);
                
                $notice_lo=$notice_log.','.$notice_id;

                $da['notice_id']=$notice_lo;

                M('notice_log')->where($we)->save($da);
                
            }
            
        }
      
        $notice=M('notice')->where($where)->select();
        
        $this->assign('messages', $notice);
        
        $this->display();
    }
    //检测手机号是否可用
    public function isUseMobile(){
        $mobile =trim(I('post.mobile','0'));
        $data =M('users')->field('mobile')->where('mobile='.$mobile)->find();
        $status =0;
        if($data){
            $status =1;  //已被使用 0 未使用
        }
        echo json_encode($status);
    }
    //检测邮箱是否可用
    public function isUseEmail(){
        $email =trim(I('post.email','0'));
        $data =M('users')->field('email')->where(array('email'=>$email))->find();
        $status =0;
        if($data){
            $status =1;  //已被使用 0 未使用
        }
        echo json_encode($status);
    }

    //检测推荐人id是否存在  必须是以18开头的
    public function checktuijian(){
        $rId = trim(I('post.referees'));
        $data = M('users')->field('wh_id')->where(array('wh_id'=>$rId))->find();
        $wh_id = $data['wh_id'];
        $status =1;
        if(preg_match("/^18[0-9]{8}$/", $wh_id)){
            $status = 0;//存在  可作为推荐人id
        }else{
            $status = 1;
        }
        echo json_encode($status);
    }

    //棒子注册
    public function signUp()
    {
        if($this->user_id > 0) header("Location: ".U('Home/User/index'));
        if(IS_POST){
            $tp_id ='9'.mt_rand(1000,9999).substr(time(),5);//韩文站点 新注册用户 138id 为 9开头
            $store_prefix ='wh'.$tp_id;
            $recommond_id = (I('post.referees'));//推荐人id /wh_id
            $mobile =trim(I('post.mobile'));
            $email =trim(I('post.email'));
            $pwd_token =md5(time());
            $pwd =sha1('!#*'.trim(I('post.password')).$pwd_token.'tps');
            $verify_code =I('post.verify_code');
            //检测验证码
            $verify = new Verify();
            if(!$verify->check($verify_code,'user_reg')){
                $this->ajaxReturn(array('status'=>-2,'msg'=>'인증 코드 오류')); //验证码错误
            }

            //验证一下邮箱 手机号  防止跳回用同一个账号注册
            $where['mobile']= $mobile;
            $where['email']= $email;
            $where['tp138_user_id'] = $tp_id ;
            $where['_logic']= 'OR';
            $uid =M('users')->where($where)->find();
            if($uid){
                $this->ajaxReturn(array('status'=>-3,'msg'=>'계정이 이미 존재합니다')); //账户已存在
            }

            //取出最后一条 wh_id值
            $wh_id = M('users')->field('wh_id')->order('user_id desc')->limit(1)->select();
            $last_wh_id = ((int)$wh_id[0]['wh_id']) + 1; //取出原值 +1

            if($last_wh_id == 1){
                $this->ajaxReturn(array('status'=>-4,'msg'=>'인자 오류가 발생했습니다'));//参数有误
            }

            $data =array(
                'mobile'=>$mobile,
                'email'=>$email,
                'tp138_user_id'=> $tp_id, //138id
                'wh_id' =>$last_wh_id,//沃好id
                'store_prefix'=>$store_prefix,
                'pwd_token'=>$pwd_token,
                'first_leader'=>$recommond_id,//推荐人id
                'password'=>$pwd,
                'reg_time'=>time()
            );
            $res =M('users')->data($data)->add();
            if($res){
                $this->ajaxReturn(array('status'=>1,'wh_id'=>$last_wh_id,'msg'=>'등록 성공')); //注册成功
            }else{
                $this->ajaxReturn(array('status'=>-1,'wh_id'=>'','msg'=>'등록 실패')); //注册失败

            }
            exit;
        }

        $this->display();
    }

    //我的团队
    public function myMembers(){
        //本地会员下级数
        $list = M('users')->field('user_id,tp138_user_id,wh_id,mobile,nickname,level,reg_time,user_title')->where('first_leader='.$this->user['wh_id'].' or first_leader='.$this->user['tp138_user_id'].' and is_delete =0')->select();
        //获取沃好会员下级数
//        $url = "https://www.walhao.com/home/userapi/getMemberTeam";
//        $data = array('tp138_user_id'=>$this->user['tp138_user_id']);
//        $res = httpRequest($url,"POST",$data);
//        $res = json_decode($res,true);
//        $list2 = $res['result'];
//        foreach ($list2 as $k=>$v){
//            $list2[$k]['wh_id'] = '';
//        }
        //将$list2中重复的138id删除
//        foreach ($list1 as $k1 => $v1) {

//            foreach ($list2 as $k2 => $v2) {
//                if($list1[$k1]['tp138_user_id'] == $list2[$k2]['tp138_user_id']){
//                    unset($list2[$k2]);
//                }
//            }
//        }
//        $list = array_merge($list1,$list2);//合并数组
//        unset($list1);
//        unset($list2);
        if(!empty($list)){
            foreach ($list as $k=>$v){
                if(strlen($v['reg_time']) <12){
                    $list[$k]['reg_time'] = date('Y-m-d H:i:s',$v['reg_time']);
                }
            }
        }
        $this->assign('recommond',$this->user['first_leader']);
        $this->assign('list',$list);
        $this->display();
    }

    public function user_transfer(){
        $wh_id = trim(I('wh_id'));
        $money = trim(I('money'));
        $password = trim(I('password'));

        $type = trim(I('type'));//类型1 余额互转,2收益互转

        $user = M('users')->where(array('user_id'=>$this->user_id))->find();//查找当前登录用户可直接使用$this->user,类参数

        if(sha1('!#*'.trim("$password").$user['pwd_token'].'tps')!= $user['password']){
            exit("암호 오류"); //密码错误
        }

        $in_uid =M('users')->where( ['wh_id' =>$wh_id] )->getField('user_id');

        $type==1 && $in_uid==$this->user_id && exit("오류 동작");//从自身余额转到自身余额--禁止

        $type==1&&$out_user_money = M('users')->where(array('user_id'=>$this->user_id))->getField('user_money');
        $type==2&&$out_user_money = M('user_account')->where(array('user_id'=>$this->user_id))->getField('user_money');


        if(!$in_uid){
            exit("사용자가 존재하지 않습니다 / 사용자 이름이 일치하지 않습니다"); //用户不存在 或者id跟用户名不匹配
        }else{
            if($out_user_money<$money){
                exit("잔액이 부족하니 금액을 다시 입력해 주십시오."); //余额不够,请重新输入金额
            }

            M()->startTrans();//开启事务
            $type==1 && $outMoney = M("users")->where(['user_id'=>$this->user_id])->setDec('user_money',$money);
            $type==2 && $outMoney = M("user_account")->where(['user_id'=>$this->user_id])->setDec('user_money',$money);

            $inMoney = M('users')->where(['user_id'=>$in_uid])->setInc('user_money',$money);



            $log = M("user_transfer_log")->add(
                array(
                    'transfer_id'=>$this->user_id,
                    'acceptor_id'=>$in_uid,
                    'create_time'=>time(),
                    'money'=>$money,
                    'type'=>1
                )
            );

            if($outMoney&&$inMoney&&$log) M()->commit();
            else M()->rollback();

            exit("계좌 이체 성공"); //转账成功
        }

    }


    public function commission_report(){
        C('TOKEN_ON',false);
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $today_award_inc =  M('user_profit')->where('uuid='.$this->user['wh_id']." and add_time between {$beginToday} and {$endToday} and type>0")->sum('money');//当日奖金
        $today_award_dec =  M('user_profit')->where('uuid='.$this->user['wh_id']." and add_time between {$beginToday} and {$endToday} and type<0")->sum('money');//当日奖金
        $today_award = $today_award_inc -$today_award_dec;
        $year = I('get.year');
        $month = I('get.month');
        if($year && $month){
            $start_time = mktime(0, 0, 0, $month,1,$year);
            $end_time = mktime(0,0,0,$month+1,1,$year) - 1;
        }else{
            $start_time = mktime(0, 0, 0, date('m'),1,date('Y'));
            $end_time = mktime(0,0,0,date('m')+1,1,date('Y')) - 1;
        }

        $report_data = M('user_profit')->where('uuid='.$this->user['wh_id']." and add_time between {$start_time} and {$end_time}")->getField('type,money');

        $money = 0;
        foreach ($report_data as $k=>$v){
            if($k>0) $money+= $v;
            else $money -= $v;
        }
        $report_data[0] = $money;

        $this->assign('today_reward',$today_award);
        $this->assign('report',$report_data);
        $this->display();


    }


    public function ajax_transfer(){

        $type = I('type',0);
        $type == 1 && $user_money = M('users')->where(['user_id'=>$this->user_id])->getField('user_money');
        $type == 2 && $user_money = M('user_account')->where(['user_id'=>$this->user_id])->getField('user_money');

        $this->assign('type',$type);
        $this->assign('user_balance',$user_money);
        return $this->display();
    }

    public function check_user(){

        $wh_id = I('uuid',false);
        if(!$wh_id) exit('111');
        $user_info = M('users')->where(['wh_id'=>$wh_id])->getField('wh_id,nickname');

        if (empty($user_info)) exit('사용자가 존재하지 않습니다.');//用户不存在
        if(empty($user_info[$wh_id]))exit('사용자 이름을 작성하지 않았습니다.');
        exit($user_info[$wh_id]);

    }

    //新建工单
    public function leave_message(){
        if(IS_POST){
            $data['u_message']=I('post.text');//留言内容
            $data['wh181_id']=I('post.wh181ID');//181ID
            $data['NAME']=I('post.nickname');//名称

            //(int) $uid=I('post.uid');
            $data['message_title']=I('post.mtitle');//工单标题
            $numbers = rand (1,10000);
            $message_number= 'GD'.$numbers.time('md');
            $data['message_number']=$message_number;//工单编号
            $data['message_time']=date('Y-m-d h:i:s',time());
            $res=M('user_message_list')->add($data);

            $text['name']=$data['NAME'];
            $text['u_message']=$data['u_message'];
            $text['wh181_id']=$data['wh181_id'];
            $text['message_time']=$data['message_time'];
            $data['message_number']=$data['message_number'];
            M('user_message')->add($data);
            if($res){
                return(1);
            }
        }
        
        return $this->display();
    }
    //我的工单
    public function my_message(){
        $where['wh181_id']=$this->user['wh_id'];

        $User = M('user_message_list'); // 实例化User对象
        $count      = $User->where($where)->count();// 查询满足要求的总记录数
        $Page  = new AjaxPage($count,10);// 实例化分页类 
        //传入总记录数和每页显示的记录数(25)
        $show = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $talklist = $User->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('talklist',$talklist);
        return $this->display();
    }

    public function ajax_my_message(){

            $whid=I('post.wh_id');
            $number=I('post.message_number');
            $is_talk=I('post.is_talk');
            $where['wh181_id']=$whid;
            $where['message_number']=$number;
            $talks=M('user_message')->where($where)->select();
            
        
                $text=I('post.texts');
                $wh181id=I('post.whid');
                $message_number=I('post.message_number');
            if($text){
                $data['u_message']=$text;
                $data['wh181_id']=$wh181id;
                $data['message_number']=$message_number;
                $data['message_time']=date('Y-m-d h:i:s',time());
                M('user_message')->add($data);
                
            }
                 //exit(json_encode($talks));
        $this->assign('whid',$whid);
        $this->assign('number',$number);
        $this->assign('talks',$talks);
        $this->assign('is_talk',$is_talk);
        return $this->display();
    }

    public function message_guizhang(){
        $notice=M('guizhang')->select();
        
        $this->assign('messages', $notice);
        $this->display();
    }
    public function ajax_message_guizhang()
    {
          $notice_id= I('type',0);
        $where['notice_id']=$notice_id;
        $no_read=M('notice_log')->select();
        $data['user_id']=$this->user_id;
        $data['notice_id']=$where['notice_id'];
        if(!$no_read){
            M('notice_log')->add($data);
        }else{
            $we['user_id']=$this->user_id;
            $notice_log=M('notice_log')->where($we)->field('notice_id')->select();
            //将获取的id变成一维数组
            $notice_log=array_column($notice_log, 'notice_id');
            $is_in=in_array($notice_id,$notice_log);
            if(!$is_in){
                $notice_log=implode($notice_log);

                $notice_lo=$notice_log.','.$notice_id;
                $da['notice_id']=$notice_lo;

                M('notice_log')->where($we)->save($da);
                
            }
            
        }
      
        $notice=M('guizhang')->where($where)->select();
        
        $this->assign('messages', $notice);
        
        $this->display();
    }
}

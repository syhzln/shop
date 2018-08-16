<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\logic;

use think\Model;
use think\Page;
use think\db;
use app\home\model\UserAddress;
use app\common\logic\CommentLogic;
use Psp;
use Grpc;

/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class UsersLogic extends Model
{
    /*
     * 登陆
     */
    public function login($username,$password){

        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $request = new Psp\Member\LoginRequest();
        $request->setUsername($username);
        $request->setIp(request()->ip());
        $request->setTime($time);
        list($reply,$status) = GRPC('member')->Login($request)->wait();
        //如果账号存在取得用户信息
        $status = $reply->getStatus();
        $reply_password =$reply->getPwd();
        $reply_token = $reply->getToken();
        $newpwd =sha1('!#*'.trim("$password").$reply_token.'tps');
        if($status == '3'){
            $result = array('status'=>3,'msg'=>'账号不存在');
        }elseif($newpwd != $reply_password){
            $result = array('status'=>2,'msg'=>'密码错误！');
        }elseif($status == '4'){
            $result = array('status'=>4,'msg'=>'账号异常！！！');
        }else{
            $user_id = $reply->getUserId();
            $org_id = $reply->getOrgId();
            //$tps138_id = $reply->getTps138id();
            $wh_id = $reply->getWh181Id();
            $mobile = $reply->getPhone();
            $nick_name = empty($reply->getNickname()) ? $wh_id : $reply->getNickname(); //姓名
            $payload = array(
                'user_id'=>$user_id,
                'org_id'=>$org_id,
                'tps138_id'=>$wh_id, //沃好id
                'nickname'=>$nick_name,
                'mobile'=>$mobile,
            );
            $jwt = create_json_web_token($payload);
            //设置token
            setrawcookie('token', $jwt, 0, '/', get_host(), false, true);
            //登录识别  解决js脚本读取不到token的bug
            setcookie('curLogin','1',0,'/',get_host()); //登录成功
            setcookie('uname',urlencode($nick_name),0,'/',get_host());
            setcookie('cn','0',time()-3600,'/',get_host());

            $result = array('status'=>1,'msg'=>'登陆成功','result'=>$payload);
        }
        return $result;
    }



    /*
     * app端登陆
     */
    public function app_login($username, $password, $capache, $push_id=0)
    {
        $result = array();
        if(!$username || !$password)
            $result= array('status'=>0,'msg'=>'请填写账号或密码');
      /*  $user = M('users')->where("mobile|email","=",$username)->find();
        if(!$user){
            $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif($password != $user['password']){
            $result = array('status'=>-2,'msg'=>'密码错误!');
        }elseif($user['is_lock'] == 1){
            $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
            //查询用户信息之后, 查询用户的登记昵称
            $levelId = $user['level'];
            $levelName = M("user_level")->where("level_id", $levelId)->getField("level_name");
            $user['level_name'] = $levelName;
            $user['token'] = md5(time().mt_rand(1,999999999));
            M('users')->where("user_id", $user['user_id'])->save(array('token'=>$user['token'],'last_login'=>time(), 'push_id' => $push_id));
            $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;*/
    }

    /*
     * app端登出
     */
    public function app_logout($token = '')
    {
        if (empty($token)){
            ajaxReturn(['status'=>-100, 'msg'=>'已经退出账户']);
        }

       /* $user = M('users')->where("token", $token)->find();
        if (empty($user)) {
            ajaxReturn(['status'=>-101, 'msg'=>'用户不在登录状态']);
        }

        M('users')->where(["user_id" => $user['user_id']])->save(['last_login' => 0, 'token' => '']);
        session(null);*/

        return ['status'=>1, 'msg'=>'退出账户成功'];;
    }

    //绑定账号
    public function oauth_bind($data = array()){
        $user = session('user');
        if(empty($user['openid'])){
//            if(M('users')->where(array('openid'=>$data['openid']))->count()>0){
//                return array('status'=>-1,'msg'=>'您的'.$data['oauth'].'账号已经绑定过账号');
//            }else{
//                M('users')->where(array('user_id'=>$user['user_id']))->save($data);
//                return array('status'=>1,'msg'=>'绑定成功','result'=>$data);
//            }
        }else{
            return array('status'=>-1,'msg'=>'您的账号已绑定过，请不要重复绑定');
        }
    }


    /**
     * 注册
     * @param $username  邮箱或手机
     * @param $password  密码
     * @param $password2 确认密码
     * @return array
     */
    public function reg($mobile,$recommondId,$real_name,$password, $push_id=0){
        if(!check_mobile($mobile)){
            return array('status'=>-1,'msg'=>'手机号格式错误');
        }
//        if(!check_email($email)){
//            return array('status'=>-1,'msg'=>'邮箱格式错误');
//        }
        /**GRPC*/
        $tps138_id ='9'.mt_rand(1000,9999).substr(time(),5);
        $store_prefix ='wh'.$tps138_id;
        $pwd_token = md5(time().mt_rand(1,9999));
        $pwd =sha1('!#*'.trim($password).$pwd_token.'tps');
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $reg = new Psp\Member\MemberRegister();
        $reg->setMobile($mobile);
        $reg->setTps138Id($tps138_id);
        $reg->setPassword($pwd);
        $reg->setToken($pwd_token);
        $reg->setStorePrefix($store_prefix);
        $reg->setOrgId((int)PLATFORM);// 组织id即为平台id
        $reg->setRegTime($time);
        $reg->setNickName($real_name);
        $reg->setStatus(1);
        $reg->setRecommondId($recommondId);//推荐人id 必须以 18开头
        $reg->setRealName($real_name);//真实姓名
//        $reg->setCardNum($id_card); //身份证号
//        $reg->setBankCard($bank_card); //银行卡号
//        $reg->setBankName($bank_name);//银行名称
//        $reg->setCardFace($face_url); //身份证正面照
//        $reg->setAlipayCard($alipay_code);
        list($resp,$status) = GRPC('member')->Register($reg)->wait();
        $ret = $resp->getRet();
        $msg = $resp->getMsg();
        if($ret == 'fail')
            return array('status'=>-1,'msg'=>"{$msg}");
        //取出会员信息
       //$user = $this->get_info($user_id);
        return array('status'=>1,'msg'=>"{$msg}",'result'=>'');
    }

    /*
     * 获取当前登录用户信息
     */
    public function get_info($user_id)
    {
        if (!$user_id) {
            return array('status'=>-1, 'msg'=>'缺少参数');
        }

        $user_info = new Psp\Member\Uid();
        $user_info->setUid($user_id);

        list($reply,$status) = GRPC('member')->GetUserInfo($user_info)->wait();
        if(empty($reply))return;
        $user['user_id'] = $reply->getUserinfo()->getId();//会员主键id
        $user['tps138_id'] = $reply->getUserinfo()->getTps138Id();//138id
        $user['wh181_id'] = $reply->getUserinfo()->getWh181Id(); //id
        $user['parent_id'] = $reply->getUserinfo()->getParentId(); //推荐人id
        $user['org_id'] =  $reply->getUserinfo()->getOrgId();//组织id
        $user['nickname']=$reply->getUserinfo()->getName();
        $user['head_pic']=empty($reply->getUserinfo()->getImg())?'/template/pc/rainbow/static/images/headPic.jpg':$reply->getUserinfo()->getImg();
        $user['sex']=$reply->getUserinfo()->getSex();
        $user['user_level'] =$reply->getUserinfo()->getUserLevel();//会员店铺等级
        $user['user_title'] =$reply->getUserinfo()->getUserTitle();//会员职称等级
        $user['mobile'] = $reply->getUserinfo()->getPhone();
        $user['email'] = $reply->getUserinfo()->getEmail();
        $user['add_time']=empty($reply->getUserinfo()->getCareatTime())?'-28800':$reply->getUserinfo()->getCareatTime()->getSeconds();
        $user['birthday']=empty($reply->getUserinfo()->getBirthdayTime())?'-28800':$reply->getUserinfo()->getBirthdayTime()->getSeconds();
        $user['mobile_validated']=$reply->getUserinfo()->getIsVerifiedMobile();
        $user['email_validated']=$reply->getUserinfo()->getIsVerifiedEmail();
        $user['status']=$reply->getUserinfo()->getStatus();
        $user['user_money']=$reply->getUserinfo()->getUserBalance();//账户余额

        if (!$user) {
            return false;
        }

//        $activityLogic = new \app\common\logic\ActivityLogic;             //获取能使用优惠券个数
//        $user['coupon_count'] = $activityLogic->getUserCouponNum($user_id, 0);

//        $user['collect_count'] = (int)cookie('collect_count'); //获取收藏数量
       /* $user['return_count'] = M('return_goods')->where("user_id=$user_id and status<2")->count();   //退换货数量

        $user['waitPay']     = M('order')->where("user_id = :user_id ".C('WAITPAY'))->bind(['user_id'=>$user_id])->count(); //待付款数量
        $user['waitSend']    = M('order')->where("user_id = :user_id ".C('WAITSEND'))->bind(['user_id'=>$user_id])->count(); //待发货数量
        $user['waitReceive'] = M('order')->where("user_id = :user_id ".C('WAITRECEIVE'))->bind(['user_id'=>$user_id])->count(); //待收货数量
        $user['order_count'] = $user['waitPay'] + $user['waitSend'] + $user['waitReceive'];

        $commentLogic = new CommentLogic;
        $user['comment_count'] = $commentLogic->getHadCommentNum($user_id); //已评论数
        $user['uncomment_count'] = $commentLogic->getWaitCommentNum($user_id); //待评论数*/
        return ['status' => 1, 'msg' => '获取成功', 'result' => $user];
    }

    /*
      * 获取当前登录用户信息
      */
    public function getApiUserInfo($user_id)
    {
        if (!$user_id) {
            return array('status'=>-1, 'msg'=>'缺少参数');
        }

//        $user = M('users')->where('user_id', $user_id)->find();
//        if (!$user) {
//            return false;
//        }

        $activityLogic = new \app\common\logic\ActivityLogic;             //获取能使用优惠券个数
        $user['coupon_count'] = $activityLogic->getUserCouponNum($user_id, 0);

        $user['collect_count'] = $this->getGoodsCollectNum($user_id);; //获取收藏数量
       /* $user['visit_count']   = M('goods_visit')->where('user_id', $user_id)->count();   //商品访问记录数
        $user['return_count'] = M('return_goods')->where("user_id=$user_id and status<2")->count();   //退换货数量

        $user['waitPay']     = M('order')->where("user_id = :user_id ".C('WAITPAY'))->bind(['user_id'=>$user_id])->count(); //待付款数量
        $user['waitSend']    = M('order')->where("user_id = :user_id ".C('WAITSEND'))->bind(['user_id'=>$user_id])->count(); //待发货数量
        $user['waitReceive'] = M('order')->where("user_id = :user_id ".C('WAITRECEIVE'))->bind(['user_id'=>$user_id])->count(); //待收货数量*/
        $user['order_count'] = $user['waitPay'] + $user['waitSend'] + $user['waitReceive'];

        $commentLogic = new CommentLogic;
        $user['comment_count'] = $commentLogic->getHadCommentNum($user_id); //已评论数
        $user['uncomment_count'] = $commentLogic->getWaitCommentNum($user_id); //待评论数

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $cartList = $cartLogic->getUserCartList(1);// 选中的商品
        $user['cart_goods_num'] = $cartList['total_price']['num']; //购物车商品数量

        return ['status' => 1, 'msg' => '获取成功', 'result' => $user];
    }

    /*
     * 获取最近一笔订单
     */
    public function get_last_order($user_id){
//        $last_order = M('order')->where("user_id", $user_id)->order('order_id DESC')->find();
//        return $last_order;
    }


    /*
     * 获取订单商品
     */
    public function get_order_goods($order_id){
        $sql = "SELECT og.*,g.commission FROM __PREFIX__order_goods og LEFT JOIN __PREFIX__goods g ON g.goods_id = og.goods_id WHERE order_id = :order_id";
        $bind['order_id'] = $order_id;
//        $goods_list = DB::query($sql,$bind);

        $return['status'] = 1;
        $return['msg'] = '';
//        $return['result'] = $goods_list;
        return $return;
    }

    /**
     * 获取账户提现记录
     * @param $user_id|用户id
     * @param int $type|收入：3余额  1收益 0所有
     * 提现状态  0申请中 1审核通过 2审核失败 3转账成功 4转账失败 5拒绝提现
     * @return mixed
     */
    public function get_withdrawals_log($user_id,$p = 1,$type = 1){
        $limit = 6;
        $page = new Psp\Pagination();
        $page->setLimit($limit);
        $page->setSortBy('withdraw_id');
        $page->setSortAsc(false);
        $page->setIndex($p);
        $withwrawPamarms = new Psp\Member\WithdrawParams();
        $withwrawPamarms->setUserId($user_id);
        $withwrawPamarms->setPageInfo($page);
        $withwrawPamarms->setPlatForm(PLATFORM);
        $withwrawPamarms->setWithType($type);
        list($reply,$status) = GRPC('Asset')->GetMemberWithdraList($withwrawPamarms)->wait();
        if(!empty($reply->getWithdrawList())){
            foreach ($reply->getWithdrawList() as $k=>$v){
                $data[$k]['apply_id'] =$v->getApplyId();
                $data[$k]['apply_time'] = $v->getApplyTime()->getSeconds();
                $data[$k]['money'] = $v->getMoney();
                $data[$k]['status'] = $v->getStatus();
                $data[$k]['extra_info'] = $v->getExtraInfo();
                $data[$k]['withdraw_way'] = $v->getWithWay();// 1支付宝 2银行卡
                $data[$k]['withdraw_type'] = $v->getWithType();//1 余额 2收益
            }
        }
        $total_count = $reply->getPageResult()->getTotalRecords();//总条数
        $Page = new Page($total_count,$limit) ;
        $return = [
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$data,
            'show'=>$Page->show()
        ];

        return $return;
    }

    /**
     * 获取账户转账记录
     *
     */
    public function get_transfer($user_id,$p = 1){
        $transfer = new Psp\Account\UserId();
        $transfer->setUserId($user_id);
        $transfer->setPagination(grpcPage('transfer_id',$p,6,false));

        list($reply,$status) = GRPC('Account')->getTransList($transfer)->wait();
        if($reply){
            foreach ($reply->getTansferList() as $k=>$v){
                $data[$k]['transfer_id'] =$v->getTransferId();
                $data[$k]['from'] = $v->getFrom();
                $data[$k]['accept'] = $v->getAccept();
                $data[$k]['state'] = $v->getState();
                $data[$k]['amount'] = sprintf("%.2f",$v->getAmount());
                $data[$k]['begin_date'] = $v->getBeginDate()?$v->getBeginDate()->getSeconds():0;
                $data[$k]['end_date'] = $v->getEndDate()?$v->getEndDate()->getSeconds():0;
                $data[$k]['reason'] = $v->getReason();
            }
            $total_count = $reply->getPaginationResult()->getTotalRecords();//总条数
            $Page = new Page($total_count,6) ;

            $return = [
                'status'=>1,
                'msg'=>'获取成功',
                'result'=>$data,
                'show'=>$Page->show()
            ];
        }

        return $return;
    }

    /**
     * 账户记录
     * @author lxl 2017-4-26
     * @param $user_id
     * @param
     * @return mixed
     */
    public function get_account_log($user_id,$withdrawals_status=''){
        $withdrawals_log_where = ['user_id'=>$user_id];
        if($withdrawals_status){
            $withdrawals_log_where['status']=$withdrawals_status;
        }
//        $count = M('withdrawals')->where($withdrawals_log_where)->count();
//        $Page = new Page($count, 10);
//        $withdrawals_log = M('withdrawals')->where($withdrawals_log_where)
//            ->order('id desc')
//            ->limit($Page->firstRow . ',' . $Page->listRows)
//            ->select();
//        $return = [
//            'status'    =>1,
//            'msg'       =>'',
//            'result'    =>$withdrawals_log,
//            'show'      =>$Page->show()
//        ];
//        return $return;
    }

    /**
     * 用户充值记录
     * $author lxl 2017-4-26
     * @param $user_id 用户ID
     * @param int $pay_status 充值状态0:待支付 1:充值成功 2:交易关闭
     * @return mixed
     */
    public function get_recharge_log($user_id,$pay_status=0){
        $recharge_log_where = ['user_id'=>$user_id];
        if($pay_status){
            $pay_status['status']=$pay_status;
        }
//        $count = M('recharge')->where($recharge_log_where)->count();
//        $Page = new Page($count, 10);
//        $recharge_log = M('recharge')->where($recharge_log_where)
//            ->order('order_id desc')
//            ->limit($Page->firstRow . ',' . $Page->listRows)
//            ->select();
//        $return = [
//            'status'    =>1,
//            'msg'       =>'',
//            'result'    =>$recharge_log,
//            'show'      =>$Page->show()
//        ];
//        return $return;
    }
    /*
     * 获取优惠券
     */
    public function get_coupon($user_id, $type =0, $orderBy = null)
    {
        $activityLogic = new \app\common\logic\ActivityLogic;
        $count = $activityLogic->getUserCouponNum($user_id, $type, $orderBy);

        $page = new Page($count, 10);
        $list = $activityLogic->getUserCouponList($page->firstRow, $page->listRows, $user_id, $type, $orderBy);

        $return['status'] = 1;
        $return['msg'] = '获取成功';
        $return['result'] = $list;
        $return['show'] = $page->show();
        return $return;
    }

    public function getGoodsCollectNum($user_id)
    {
//        $count = M('goods_collect')->alias('c')
//            ->join('goods g','g.goods_id = c.goods_id','INNER')
//            ->where('user_id', $user_id)
//            ->count();
//        return $count;
    }

    /**
     * 获取商品收藏列表
     * @param $user_id  用户id
     */
    public function get_goods_collect($user_id){
        $count = $this->getGoodsCollectNum($user_id);
        $page = new Page($count,10);
        $show = $page->show();
        //获取我的收藏列表
//        $result = M('goods_collect')->alias('c')
//            ->field('c.collect_id,c.add_time,g.goods_id,g.goods_name,g.shop_price,g.is_on_sale,g.store_count,g.cat_id ')
//            ->join('goods g','g.goods_id = c.goods_id','INNER')
//            ->where("c.user_id = $user_id")
//            ->limit($page->firstRow,$page->listRows)
//            ->select();
        $return['status'] = 3;
        $return['msg'] = '获取成功';
//        $return['result'] = $result;
        $return['show'] = $show;
        return $return;
    }

    /**
     * 获取评论列表
     * @param $user_id 用户id
     * @param $status  状态 0 未评论 1 已评论 2全部
     * @return mixed
     */
    public function get_comment($user_id,$status=2){
        if($status == 1){
            //已评论
          /*  $commented_count = Db::name('comment')
                ->alias('c')
                ->join('__ORDER_GOODS__ g','c.goods_id = g.goods_id and c.order_id = g.order_id', 'inner')
                ->where('c.user_id',$user_id)
                ->count();
            $page = new Page($commented_count,10);
            $comment_list = Db::name('comment')
                ->alias('c')
                ->field('c.*,g.*,(select order_sn from  __PREFIX__order where order_id = c.order_id ) as order_sn')
                ->join('__ORDER_GOODS__ g','c.goods_id = g.goods_id and c.order_id = g.order_id', 'inner')
                ->where('c.user_id',$user_id)
                ->order('c.add_time desc')
                ->limit($page->firstRow,$page->listRows)
                ->select();*/
        }else{
           /* $comment_where = ['o.user_id'=>$user_id,'og.is_send'=>1,'o.order_status'=>['in',[2,4]]];
            if($status == 0){
                $comment_where['og.is_comment'] = 0;
                $comment_where['o.order_status'] = 2;
            }
            $comment_count = Db::name('order_goods')->alias('og')->join('__ORDER__ o','o.order_id = og.order_id','left')->where($comment_where)->count();
            $page = new Page($comment_count,10);
            $comment_list = Db::name('order_goods')
                ->alias('og')
                ->join('__ORDER__ o','o.order_id = og.order_id','left')
                ->where($comment_where)
                ->order('o.order_id desc')
                ->limit($page->firstRow,$page->listRows)
                ->select();*/
        }
      /*  $show = $page->show();
        if($comment_list){
            $return['result'] = $comment_list;
            $return['show'] = $show; //分页
            return $return;
        }else{
            return array();
        }*/
    }

    /**
     * 添加评论
     * @param $add
     * @return array
     */
    public function add_comment($add)
    {

        /* if(!$add['order_id'] || !$add['goods_id'])
             return array('status'=>-1,'msg'=>'非法操作','result'=>'');

         //检查订单是否已完成
 //        $order = M('order')->field('order_status')->where("order_id", $add['order_id'])->where('user_id', $add['user_id'])->find();
         if($order['order_status'] != 2)
             return array('status'=>-1,'msg'=>'该笔订单还未确认收货','result'=>'');

         //检查是否已评论过
         $goods = M('comment')->where(['order_id'=>$add['order_id'],'goods_id'=>$add['goods_id']])->find();
         if($goods)
             return array('status'=>-1,'msg'=>'您已经评论过该商品','result'=>'');

         $row = M('comment')->add($add);
         if($row)
         {
             //更新订单商品表状态
             M('order_goods')->where(array('goods_id'=>$add['goods_id'],'order_id'=>$add['order_id']))->save(array('is_comment'=>1));
             M('goods')->where(array('goods_id'=>$add['goods_id']))->setInc('comment_count',1); // 评论数加一
             // 查看这个订单是否全部已经评论,如果全部评论了 修改整个订单评论状态
             $comment_count   = M('order_goods')->where("order_id", $add['order_id'])->where('is_comment', 0)->count();
             if($comment_count == 0) // 如果所有的商品都已经评价了 订单状态改成已评价
             {
                 M('order')->where("order_id",$add['order_id'])->save(array('order_status'=>4));
             }
             return array('status'=>1,'msg'=>'评论成功','result'=>'');
         }
         return array('status'=>-1,'msg'=>'评论失败','result'=>'');*/

        $time = new Psp\Timestamp();
        $time->setSeconds($add['add_time']);
        $time->setNanos(1);

        $comment = new Psp\Member\AddCommentInfo();
        $comment->setUid($add['user_id']);
        $comment->setGoodsId($add['goods_id']);
        $comment->setContent($add['content']);
        $comment->setCommentImg($add['img']);
        $comment->setGoodsRank($add['goods_rank']);
        $comment->setIsAnonymous((int)$add['is_anonymous']);
        $comment->setCommentTime($time);
        $comment->setOrderId($add['order_id']);
        list($reply, $status) = GRPC('member')->AddComment($comment)->wait();
        $res = $reply->getValue();
        if ($res == true) {
            return array('status'=>1,'msg'=>'评论成功','result'=>'');
        }
        return array('status'=>-1,'msg'=>'评论失败','result'=>'');
    }

    /**
     * 邮箱或手机绑定
     * @param $email_mobile  邮箱或者手机
     * @param int $type  1 为更新邮箱模式  2 手机
     * @param int $user_id  用户id
     * @return bool
     */
    public function update_email_mobile($email_mobile,$user_id,$type=1){
        //检查是否存在邮件
        if($type == 1)
            $field = 'email';
        if($type == 2)
            $field = 'mobile';
        $condition['user_id'] = array('neq',$user_id);
        $condition[$field] = $email_mobile;

        /*$is_exist = M('users')->where($condition)->find();*/
        $mobile = new Psp\Member\VerifiedMobile();
        $mobile->setMemberId($user_id);
        $mobile->setMobile($email_mobile);
        $mobile->setIsVerifiedMobile(1);
        list($reply, $status) = GRPC('member')->UpdateVerifiedMobile($mobile)->wait();
        $res = $reply->getValue();
        if($res == false)
            return false;
        unset($condition[$field]);
        /*  $condition['user_id'] = $user_id;
          $validate = $field.'_validated';
          M('users')->where($condition)->save(array($field=>$email_mobile,$validate=>1));*/
        return true;
    }

    /**
     * 更新用户信息
     * @param $user_id
     * @param $post  要更新的信息
     * @return bool
     */
    public function update_info($user_id,$post=array()){
        $time = new Psp\Timestamp();
        $time->setSeconds($post['birthday']);
        $time->setNanos(1);

        $u = new Psp\Member\UserInfo();
        $u->setId($user_id);
        $u->setName($post['nickname']);
        $u->setImg($post['head_pic']);
        $u->setSex($post['sex']);
        $u->setBirthdayTime($time);
        $user_info = new Psp\Member\UpdateInfo();
        $user_info->setUserinfo($u);
        list($res,$status) = GRPC('member')->UpdateUserInfo($user_info)->wait();
        $row = $res->getValue();
        if($row === false)
            return false;
        return true;
    }

    /**
     * 地址添加/编辑
     * @param $user_id 用户id
     * @param $user_id 地址id(编辑时需传入)
     * @return array
     */
    public function add_address($user_id,$address_id=0,$data){
        $post = $data;
        $uid = $user_id;
        if($address_id == 0)
        {
            $address_num = new Psp\Member\Uid();
            $address_num->setUid($uid);
            list($res,$status) = GRPC('member')->GetUserAddressCount($address_num)->wait();
            $c = $res->getCount();
            if($c >= 20)
                return array('status'=>-1,'msg'=>'最多只能添加20个收货地址','result'=>'');
        }

        //检查手机格式
        if($post['consignee'] == '')
            return array('status'=>-1,'msg'=>'收货人不能为空','result'=>'');
        if(!$post['province'] || !$post['city'] || !$post['district'])
            return array('status'=>-1,'msg'=>'所在地区不能为空','result'=>'');
        if(!$post['address'])
            return array('status'=>-1,'msg'=>'地址不能为空','result'=>'');
        if(!check_mobile($post['mobile']))
            return array('status'=>-1,'msg'=>'手机号码格式有误','result'=>'');

        //编辑模式

        if($address_id > 0){
            $edit = new Psp\Member\UserAddress();
            $edit->setAddressId($address_id);
            $edit->setUid($user_id);
            $edit->setLocationCode((int)$post['district']);
            $edit->setPostCode($post['zipcode']);
            $edit->setPhone($post['mobile']);
            $edit->setAddress($post['address']);
            $edit->setName($post['consignee']);

            $address_edit = new Psp\Member\UpdateAddress();
            $address_edit->setAddressInfo($edit);
            list($res,$status) = GRPC('member')->UpdateUserAddress($address_edit)->wait();
            $row = $res->getValue();
            if($row === false)
                return array('status'=>-1,'msg'=>'修改失败','result'=>'');
            return array('status'=>1,'msg'=>'编辑成功','result'=>'');
        }
        //添加模式
//        $post['user_id'] = $user_id;
        $address_num = new Psp\Member\Uid();
        $address_num->setUid($uid);
        list($res,$status) = GRPC('member')->GetUserAddressCount($address_num)->wait();
        // 如果目前只有一个收货地址则改为默认收货地址
        if($res->getCount() == 0){
            $post['is_default'] = 1;
        }

        $address = new Psp\Member\UserAddress();
        $address->setUid($user_id);
        $address->setAddress($post['address']);
        $address->setLocationCode((int)$post['district']);
        $address->setName($post['consignee']);
        $address->setPhone($post['mobile']);
        $address->setPostCode($post['zipcode']);
        $address->setIsDefault((boolean)$post['is_default']);
        list($res,$status) = GRPC('member')->AddUserAddress($address)->wait();
        $address_id = $res->getAddressId();
        //如果设为默认地址

        if(!$address_id)
            return array('status'=>-1,'msg'=>'添加失败','result'=>'');


        return array('status'=>1,'msg'=>'添加成功','result'=>$address_id);
    }

    /**
     * 添加自提点
     * @author dyr
     * @param $user_id
     * @param $post
     * @return array
     */
    public function add_pick_up($user_id, $post)
    {
        //检查用户是否已经有自提点
//        $user_pickup_address_id = M('user_address')->where(['user_id'=>$user_id,'is_pickup'=>1])->getField('address_id');
//        $pick_up = M('pick_up')->where(array('pickup_id' => $post['pickup_id']))->find();
//        $post['address'] = $pick_up['pickup_address'];
        $post['is_pickup'] = 1;
        $post['user_id'] = $user_id;
        $user_address = new UserAddress();
        if (!empty($user_pickup_address_id)) {
            //更新自提点
            $user_address_save_result = $user_address->allowField(true)->validate(true)->save($post,['address_id'=>$user_pickup_address_id]);
        } else {
            //添加自提点
            $user_address_save_result = $user_address->allowField(true)->validate(true)->save($post);
        }
        if (false === $user_address_save_result) {
            return array('status' => -1, 'msg' => '保存失败', 'result' => $user_address->getError());
        } else {
            return array('status' => 1, 'msg' => '保存成功', 'result' => '');
        }
    }

    /**
     * 设置默认收货地址
     * @param $user_id
     * @param $address_id
     */
    public function set_default($user_id,$address_id){
       /* M('user_address')->where(array('user_id'=>$user_id))->save(array('is_default'=>0)); //改变以前的默认地址地址状态
        $row = M('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->save(array('is_default'=>1));
        if(!$row)
            return false;
        return true;*/
    }

    /**
     * 修改密码
     * @param $user_id  用户id
     * @param $old_password  旧密码
     * @param $new_password  新密码
     * @param $confirm_password 确认新 密码
     */
    public function password($user_id,$old_password,$new_password,$confirm_password,$is_update=true){



        if(strlen($new_password) < 6)
            return array('status'=>-1,'msg'=>'密码不能低于6位字符','result'=>'');
        if($new_password != $confirm_password)
            return array('status'=>-1,'msg'=>'两次密码输入不一致','result'=>'');
        //验证原密码
        $uid = new Psp\Member\Uid();
        $uid->setUid($user_id);
        list($res,$status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $pwd=$res->getPwd();
        $token = $res->getToken();
        $oldpwd =sha1('!#*'.trim("$old_password").$token.'tps');
        if($is_update && ($pwd != '' && $oldpwd != $pwd))
            return array('status'=>-1,'msg'=>'密码验证失败','result'=>'');
        $newpwd =sha1('!#*'.trim("$new_password").$token.'tps');

        $password = new Psp\Member\NewPassword();
        $password->setUserId($user_id);
        $password->setNewPassword($newpwd);
        $password->setOldPassword($pwd);
        list($reply,$status) = GRPC('member')->UpdatePassword($password)->wait();

        $row = $reply->getValue();

//        $row = M('users')->where("user_id", $user_id)->save(array('password'=>encrypt($new_password)));
        if($row == false)
            return array('status'=>-1,'msg'=>'修改失败','result'=>'');
        return array('status'=>1,'msg'=>'修改成功','result'=>'');
    }

    /**
     *  针对 APP 修改密码的方法
     * @param $user_id  用户id
     * @param $old_password  旧密码
     * @param $new_password  新密码
     * @param $confirm_password 确认新 密码
     */
    public function passwordForApp($user_id,$old_password,$new_password,$is_update=true){
//        $user = M('users')->where('user_id', $user_id)->find();
        if(strlen($new_password) < 6){
            return array('status'=>-1,'msg'=>'密码不能低于6位字符','result'=>'');
        }
        //验证原密码
       /* if($is_update && ($user['password'] != '' && $old_password != $user['password'])){
            return array('status'=>-1,'msg'=>'旧密码错误','result'=>'');
        }*/

       /* $row = M('users')->where("user_id='{$user_id}'")->update(array('password'=>$new_password));
        if(!$row){
            return array('status'=>-1,'msg'=>'密码修改失败','result'=>'');
        }*/
        return array('status'=>1,'msg'=>'密码修改成功','result'=>'');
    }

    /**
     * 设置支付密码
     * @param $user_id  用户id
     * @param $new_password  新密码
     * @param $confirm_password 确认新 密码
     */
    public function paypwd($user_id,$new_password,$confirm_password){
        if(strlen($new_password) != 6 || !is_numeric($new_password))
            return array('status'=>-1,'msg'=>'支付密码必须为6位数字','result'=>'');
        if($new_password != $confirm_password)
            return array('status'=>-1,'msg'=>'两次密码输入不一致','result'=>'');
        //获取salt值
        $uid = new Psp\Member\Uid();
        $uid->setUid((int)$user_id);
        list($res, $status) = GRPC('member')->GetMemberPwdInfo($uid)->wait();
        $token = $res->getToken();
        $paypwd = sha1('!#*' . md5(trim($new_password)).$token); //加密支付密码
        $pwd =new Psp\Member\UserPassword();
        $pwd->setUserId((int)$user_id);
        $pwd->setPassword($paypwd);
        list($reply,$status) = GRPC('member')->UpdateMemebrPayPwd($pwd)->wait();
        $value = $reply->getValue();
        if(!$value){
            return array('status'=>-1,'msg'=>'修改失败','result'=>'');
        }
//        setcookie('verifyed',null,time()-3600,'/');//修改成功清除cookie值
        return array('status'=>1,'msg'=>'修改成功','result'=>'');
    }

    /**
     * 取消订单 lxl 2017-4-29
     * @param $user_id  用户ID
     * @param $order_id 订单ID
     * @param string $action_note 操作备注
     * @return array
     */
    public function cancel_order($user_id,$order_id,$action_note='您取消了订单'){
        $client = GRPC(Trade);
        $ordid = Trade(OrdId);
        $ordid->setOrderId((int)$order_id);
        list($res,$status) = $client->CancelOrder($ordid)->wait();
        $row = $res->getValue();
//        $order = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
//        //检查是否未支付订单 已支付联系客服处理退款
//        if(empty($order))
//            return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
//        //检查是否未支付的订单
//        if($order['pay_status'] > 0 || $order['order_status'] > 0)
//            return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');
//        //获取记录表信息
//        //$log = M('account_log')->where(array('order_id'=>$order_id))->find();
//        //有余额支付的情况
//        if($order['user_money'] > 0 || $order['integral'] > 0){
//            accountLog($user_id,$order['user_money'],$order['integral'],"订单取消，退回{$order['user_money']}元,{$order['integral']}积分");
//        }
//
//		if($order['coupon_price'] >0){
//			$res = array('use_time'=>0,'status'=>0,'order_id'=>0);
//			M('coupon_list')->where(array('order_id'=>$order_id,'uid'=>$user_id))->save($res);
//		}
//
//        $row = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>3));
//
//        $data['order_id'] = $order_id;
//        $data['action_user'] = 0;
//        $data['action_note'] = $action_note;
//        $data['order_status'] = 3;
//        $data['pay_status'] = $order['pay_status'];
//        $data['shipping_status'] = $order['shipping_status'];
//        $data['log_time'] = time();
//        $data['status_desc'] = '用户取消订单';
//        M('order_action')->add($data);//订单操作记录

        if(!$row)
            return array('status'=>-1,'msg'=>'操作失败','result'=>'');
        return array('status'=>1,'msg'=>'操作成功','result'=>'');

    }


    /**
     * 自动取消订单
     * @author lxl 2014-4-29
     * @param $order_id         订单id
     * @param $user_id  用户ID
     * @param $orderAddTime 订单添加时间
     * @param $setTime  自动取消时间/天 默认1天
     */
    public function  abolishOrder($user_id,$order_id,$orderAddTime='',$setTime=1){
        $abolishtime = strtotime("-$setTime day");
        if($orderAddTime<$abolishtime) {
            $action_note = '超过' . $setTime . '天未支付自动取消';
            $result = $this->cancel_order($user_id,$order_id,$action_note);
//           if($result['status']==1)
            return $result;
        }
    }

    /**
     * 发送验证码: 该方法只用来发送邮件验证码, 短信验证码不再走该方法
     * @param $sender 接收人
     * @param $type 发送类型
     * @return json
     */
    public function send_email_code($sender){
        $sms_time_out = tpCache('sms.sms_time_out');
        $sms_time_out = $sms_time_out ? $sms_time_out : 180;
        //获取上一次的发送时间
        $send = cookie('validate_code');
        if(!empty($send) && $send['time'] > time() && $send['sender'] == $sender){
            //在有效期范围内 相同号码不再发送
            $res = array('status'=>-1,'msg'=>'规定时间内,不要重复发送验证码');
            return $res;
        }
        $code =  mt_rand(1000,9999);
        //检查是否邮箱格式
        if(!check_email($sender)){
            $res = array('status'=>-1,'msg'=>'邮箱码格式有误');
            return $res;
        }
        $send = send_email($sender,'验证码','您好，你的验证码是：'.$code);
        if($send['status'] == 1){
            $info['code'] = $code;
            $info['sender'] = $sender;
            $info['is_check'] = 0;
            $info['time'] = time() + $sms_time_out; //有效验证时间
            cookie('validate_code',$info);
            $res = array('status'=>1,'msg'=>'验证码已发送，请注意查收');
        }else{
            $res = $send;
        }
        return $res;
    }

    /**
     * 检查短信/邮件验证码验证码
     * @param unknown $code
     * @param unknown $sender
     * @param unknown $session_id
     * @return multitype:number string
     */
    public function check_validate_code($code, $sender, $type ='email', $session_id=0 ,$scene = -1){

        $timeOut = time();
        $inValid = true;  //验证码失效

        //短信发送否开启
        //-1:用户没有发送短信
        //空:发送验证码关闭
        $sms_status = checkEnableSendSms($scene);

        //邮件证码是否开启
        $reg_smtp_enable = tpCache('smtp.regis_smtp_enable');

        if($type == 'email'){
            if(!$reg_smtp_enable){//发生邮件功能关闭
                $validate_code = cookie('validate_code');
                $validate_code['sender'] = $sender;
                $validate_code['is_check'] = 1;//标示验证通过
                cookie('validate_code',$validate_code);
                return array('status'=>1,'msg'=>'邮件验证码功能关闭, 无需校验验证码');
            }
            if(!$code)return array('status'=>-1,'msg'=>'请输入邮件验证码');
            //邮件
            $data = cookie('validate_code');
            $timeOut = $data['time'];
            if($data['code'] != $code || $data['sender']!=$sender){
                $inValid = false;
            }
        }else{
            if($scene == -1){
                return array('status'=>-1,'msg'=>'参数错误, 请传递合理的scene参数');
            }elseif($sms_status['status'] == 0){
                $data['sender'] = $sender;
                $data['is_check'] = 1; //标示验证通过
                cookie('validate_code',$data);
                return array('status'=>1,'msg'=>'短信验证码功能关闭, 无需校验验证码');
            }

            if(!$code)return array('status'=>-1,'msg'=>'请输入短信验证码');
            //获取设备号
            $device_code = getEquipmentSystem();
            //获取短信记录
            $sms_time_out = tpCache('sms.sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 180;
            $codes = new Psp\Member\SmsSendStatus();
            $codes->setMobile($sender);
            $codes->setStatus(1);
            $codes->setDeviceCode($device_code);
            list($reply,$status) = GRPC('member')->GetSmsSendStatus($codes)->wait();
            $code = $reply->getCode();
            $data['add_time'] = $reply->getSendTime()->getSeconds();
            $data['code'] = $code;
            /*file_put_contents('./test.log', json_encode(['mobile'=>$sender,'session_id'=>$session_id, 'data' => $data]));*/
            if(is_array($data) && $data['code'] == $code){
                $data['sender'] = $sender;
                $timeOut = $data['add_time']+ $sms_time_out;
            }else{
                $inValid = false;
            }
        }


        if(empty($data)){
            $res = array('status'=>-1,'msg'=>'请先获取验证码');
        }elseif($timeOut < time()){
            $res = array('status'=>-1,'msg'=>'验证码已超时失效');
        }elseif(!$inValid)
        {
            $res = array('status'=>-1,'msg'=>'验证失败,验证码有误');
        }else{
            $data['is_check'] = 1; //标示验证通过
            cookie('validate_code',$data);
            $res = array('status'=>1,'msg'=>'验证成功');
        }

        return $res;
    }


    /**
     * @time 2016/09/01
     * @author dyr
     * 设置用户系统消息已读
     */
    public function setSysMessageForRead()
    {
        $user_info = session('user');
        if (!empty($user_info['user_id'])) {
            $data['status'] = 1;
            M('user_message')->where(array('user_id' => $user_info['user_id'], 'category' => 0))->save($data);
        }
    }

    /**
     * 获取访问记录
     * @param type $user_id
     * @param type $p
     * @return type
     */
    public function getVisitLog($user_id, $p = 1)
    {
        /*$visit = M('goods_visit')->alias('v')
            ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id')
            ->join('__GOODS__ g', 'v.goods_id=g.goods_id')
            ->where('v.user_id', $user_id)
            ->order('v.visittime desc')
            ->page($p, 20)
            ->select();*/

        /* 浏览记录按日期分组 */
        $curyear = date('Y');
        $visit_list = [];
   /*     foreach ($visit as $v) {
            if ($curyear == date('Y', $v['visittime'])) {
                $date = date('m月d日', $v['visittime']);
            } else {
                $date = date('Y年m月d日', $v['visittime']);
            }
            $visit_list[$date][] = $v;
        }*/

        return $visit_list;
    }

    /**
     * 上传头像
     */
    public function upload_headpic($must_upload = true)
    {
        if ($_FILES['head_pic']['tmp_name']) {
            $file = request()->file('head_pic');
            $validate = ['size'=>1024 * 1024 * 3,'ext'=>'jpg,png,gif,jpeg'];
            $dir = 'public/upload/head_pic/';
            if (!($_exists = file_exists($dir))) {
                mkdir($dir);
            }
            $parentDir = date('Ymd');
            $info = $file->validate($validate)->move($dir, true);
            if ($info) {
                $pic_path = '/'.$dir.$parentDir.'/'.$info->getFilename();
            } else {
                return ['status' => -1, 'msg' => $info->getError()];
            }
        } elseif ($must_upload) {
            return ['status' => -1, 'msg' => "图片不存在！"];
        }
        return ['status' => 1, 'msg' => '上传成功', 'result' => $pic_path];
    }

    /**
     * 账户明细
     */
    public function account($user_id, $type='all'){
        /*if($type == 'all'){
            $count = M('account_log')->where("user_money!=0 and user_id=" . $user_id)->count();
            $page = new Page($count, 16);
            $account_log = M('account_log')->where("user_money!=0 and user_id=" . $user_id)->order('log_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        }else{
            $where = $type=='plus' ? " and user_money>0 " : " and user_money<0 ";
            $count = M('account_log')->where("user_id=" . $user_id.$where)->count();
            $page = new Page($count, 16);
            $account_log = M('account_log')->where("user_id=" . $user_id.$where)->order('log_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        }
        $result['account_log'] = $account_log;
        $result['page'] = $page;
        return $result;*/
    }

    /**
     * 积分明细
     */
    public function points($user_id, $type='all')
    {
       /* if($type == 'all'){
            $count = M('account_log')->where("user_id=" . $user_id ." and pay_points!=0 ")->count();
            $page = new Page($count, 16);
            $account_log = M('account_log')->where("user_id=" . $user_id." and pay_points!=0 ")->order('log_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        }else{
            $where = $type=='plus' ? " and pay_points>0 " : " and pay_points<0 ";
            $count = M('account_log')->where("user_id=" . $user_id.$where)->count();
            $page = new Page($count, 16);
            $account_log = M('account_log')->where("user_id=" . $user_id.$where)->order('log_id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
        }

        $result['account_log'] = $account_log;
        $result['page'] = $page;
        return $result;*/
    }

    //商品收藏
    public function user_goods_collect($user_id,$p=1){
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("item_id");
        $page->setIndex($p);
        $page->setLimit(5);//优先显示最新收藏的
        $fav_items = new Psp\Member\GetPage();
        $fav_items->setUid($user_id);
        $fav_items->setPagination($page);
        list($res,$status) = GRPC('member')->GetFavoriteItem($fav_items)->wait();
        if(empty($res))return;

        foreach($res->getFavoriteItemList() as $k=>$v){

            $arr[$k]['goods_id'] = $v->getItemId();
            $arr[$k]['add_time'] = $v->getAddTime()->getSeconds();
            $arr[$k]['goods_name'] = $v->getItemName();
            $arr[$k]['shop_price'] = sprintf("%.2f", $v->getItemPrice());
            $arr[$k]['item_img'] = $v->getItemImg();
            $arr[$k]['item_stock'] = $v->getItemStock();
            $arr[$k]['is_on_sale'] = $v->getIsOnSale();
        }

        $total_count = $res->getPageResult()->getTotalRecords();//商品总数
//        setcookie('collect_count',$total_count,0,'/',get_host());//手机端首页显示 数量
        $Page = new Page($total_count,5);
        $show = $Page->show();

        $data =['list'=>$arr,'show'=>$show,'goods_num'=>$total_count,'msg'=>'ok'];

        return $data;
    }

    //奖金报表
    public function get_commission_report($user_id,$time){
        //获取会员奖金总和
        $uid = new Psp\Member\UserId();
        $uid->setUid($user_id);
        $uid->setPlatformId(PLATFORM);
        list($resp,$status) = GRPC('Asset')->GetMemberBonus($uid)->wait();
        if(!empty($resp)){
            $bonus['today_bonus'] = $resp->getTodayBonus();
            $bonus['month_bonus'] = $resp->getMonthBonus();
            unset($resp);
        }
        //获取月奖金明细  带搜索
        $search = new Psp\Member\BonusSearch();
        $search->setUserId($user_id);
        $search->setPlatformId(PLATFORM);
        $search->setSearchTime($time);
        list($resp,$status) = GRPC('Asset')->GetMemberBonusDetail($search)->wait();
        if(!empty($resp)){
            $bonus_detail['month_sales_commission'] = $resp->getMonthSalesCommission();//个人店铺销售提成奖
            $bonus_detail['month_share_commission'] = $resp->getMonthShareCommission();//分享销售提成奖
            $bonus_detail['day_platform_commission'] = $resp->getDayPlatformCommission();//每天平台销售利润分红奖
            $bonus_detail['month_team_sales'] = $resp->getMonthTeamSales();//每月团队销售提成奖
            $bonus_detail['month_special_sales'] = $resp->getMonthSpecialSales();//特殊贡献销售提成奖
            $bonus_detail['month_best_member'] =$resp->getMonthBestMember();//销售精英提成奖
            $bonus_detail['week_leader_award'] =$resp->getWeekLeaderAward();//周领导对等奖
            $bonus_detail['new_comer_award'] =$resp->getNewComerAward();//新人奖
            $bonus_detail['other_award'] =$resp->getOtherBonus();//其它 9其它奖
            $bonus_detail['performance_award'] =$resp->getPerformanceAward();//绩效奖 10绩效奖
            $bonus_detail['team_special_award'] =$resp->getTeamSpecialAward();//团队杰出贡献奖 12绩效奖
            $bonus_detail['replacement_award'] =$resp->getReplacementAward();//奖金补发 20绩效奖
            unset($resp);
        }

        return ['bonus'=>$bonus,'bonus_detail'=>$bonus_detail];
    }

    //会员买呗信息
    public function member_buy($user_id){
        $uid = new Psp\Member\Uid();
        $uid->setUid($user_id);
        list($resp) = GRPC('member')->GetMemberBuyDetail($uid)->wait();
        if(empty($resp)) return;

        $status = $resp->getStatus();
        $reason = $resp->getReason();
        $limit_money = $resp->getLimitMoney();//我的额度
        $now_money = $resp->getNowMonthMoney();//本月花费
        $last_money = $resp->getLastMonthMoney();//上月花费
        $can_use_money = $limit_money - $now_money - $last_money;
        return ['limit_money'=>$limit_money,'now_money'=>$now_money,'last_money'=>$last_money,'can_use_money'=>$can_use_money];
    }

    //扣除买呗账户余额
    public function updateMemberBalance($user_id,$order_money){
        $params = new Psp\Member\MemberPay();
        $params->setUserId($user_id);
        $params->setOrderMoney($order_money);
        list($resp) = GRPC('member')->UpdateMemberBuyMoney($params)->wait();
        $ret = $resp->getRet();
        $msg = $resp->getMsg();
        return ['ret'=>$ret,'msg'=>$msg];

    }

    /*买呗明细 支出/还款
     * tpye 1 支出  2 还款
     *
     */
    public function MemberBuyUseList($user_id,$p,$type=1,$limit =8)
    {

        if($type == 1){
            //支出
            $page = new Psp\Pagination();
            $page->setSortAsc(false);
            $page->setSortBy("order_id");
            $page->setIndex($p);
            $page->setLimit($limit);
            $payout = new Psp\Member\GetPage();
            $payout->setUid($user_id);
            $payout->setPagination($page);
            list($resp,$status) = GRPC('member')->GetMemberPayoutList($payout)->wait();
            if(!empty($resp)){
                foreach ($resp->getMemberbuyPayout() as $k=>$v){
                    $data[$k]['goods_number'] = $v->getGoodsNumber();
//                    $data[$k]['goods_name'] = $v->getGoodsName();
                    $data[$k]['payout_money'] = $v->getPayoutMoney();
                    $data[$k]['store_name'] = $v->getStoreName();
                    $data[$k]['payout_time'] = $v->getPayoutTime();
                }
                $total_num = $resp->getPageResult()->getTotalRecords();//总条数
                $Page = new Page($total_num,$limit);
                $show = $Page->show();
            }
        }

        if($type == 2){
            //还款
            $page = new Psp\Pagination();
            $page->setSortAsc(false);
            $page->setSortBy("id");
            $page->setIndex($p);
            $page->setLimit($limit);
            $repayment = new Psp\Member\GetPage();
            $repayment->setPagination($page);
            $repayment->setUid($user_id);
            list($resp,$status) = GRPC('member')->GetMemberRepaymentList($repayment)->wait();
            if(!empty($resp)){
                foreach ($resp->getRepaymentList() as $k=>$v){
                    $data[$k]['pay_number'] = $v->getPayNumber();
                    $data[$k]['give_money'] = $v->getGiveMoney();
                    $data[$k]['repay_time'] = $v->getRepayTime();
                    $data[$k]['pay_type'] = $v->getPayType();
                    $data[$k]['repay_month'] = $v->getRepayMonth();
                    $data[$k]['status'] = $v->getStatus();

                }
                $total_num = $resp->getPageResult()->getTotalRecords();
                $Page = new Page($total_num,$limit);
                $show = $Page->show();
            }
        }
        $return = [
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$data,
            'show'=>$show
        ];

        return $return;

    }


}
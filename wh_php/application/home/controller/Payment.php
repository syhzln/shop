<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller; 
use think\Request;
use Grpc;
use Psp;
class Payment extends Base {
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct()
    {
        parent::__construct();           
        
        //  订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];

        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }

        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];      
        
        if(empty($this->pay_code))
            exit('pay_code 不能为空');

        //钱包支付 直接退出
        if($this->pay_code == 'walletpay'){
            exit('亲,走错地方啦');
        }
        if($this->pay_code == 'paypal'){
            exit('中国区暂不支持贝宝支付!');
        }
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php";
        $code = '\\'.$this->pay_code; // alipay
        $this->payment = new $code();
    }
   
    /**
     * tpshop 提交支付方式
     */
    public function getCode()
    {
            //C('TOKEN_ON',false); // 关闭 TOKEN_ON
            //header("Content-type:text/html;charset=utf-8");
            $orderid = I('order_id'); // 订单id
            //session('order_id',$order_id); // 最近支付的一笔订单 id

            $orderids = array_map(function($v){
                return (int)$v;
            },explode(',',$orderid));
            //var_dump($orderids);die;
            //根据订单id列表获取总金额
            $order_id = Trade(OrderIds);
            $order_id->setOrderId($orderids);
            list($total_amount,$status) = GRPC(Trade)->GetOrderTotalAmount($order_id)->wait();
            $amount = $total_amount->getOrderAmount();
            switch ($this->pay_code)
            {
                case 'alipay':
                    $pay_type = 1;
                    break;
                case 'alipayMobile':
                    $pay_type = 3;
                    break;
                case 'weixin':
                    $pay_type = 4;
                    break;
                case 'unionpay':
                    $pay_type = 5;
                    break;
                case 'billpay':
                    $pay_type = 7;
                    break;
                case 'billpayMobile':
                    $pay_type = 8;
                    break;
                default:
                    $pay_type = 1;
            }
            $jwt_token =$_COOKIE['token'];
            $payload =validate_json_web_token($jwt_token);
            $user_id = (int)$payload['user_id'];
            $id = create_paycode(1,$amount,1,1,$user_id,1,PLATFORM,$orderid,'订单支付',$pay_type);
            if(!$id){
                $this->error('订单已支付或者买呗未还款', U('Home/User/order_list'));
            }

            $order = array(
                'order_sn'=>$id,//订单号
                'order_amount'=>$amount,//订单总金额
            );

//            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
//            M('order')->where("order_id",$order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
//
//            $order = M('order')->where("order_id", $order_id)->find();
//            if($order['pay_status'] == 1){
//            	$this->error('此订单，已完成支付!');
//            }
            //  订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数

            //微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order,$config_value);
               exit($code_str);
           }else{
           	$code_str = $this->payment->get_code($order,$config_value); //$order存支付码跟支付金额
           }
           $this->assign('code_str', $code_str); 
           $this->assign('order_ids', $orderid);
           return $this->fetch('payment');  // 分跳转 和不 跳转 
    }

    public function getPay()
    {
    	//C('TOKEN_ON',false); // 关闭 TOKEN_ON
    	header("Content-type:text/html;charset=utf-8");

    	$payload = validate_json_web_token($_COOKIE['token']);
        $repayParams = new Psp\Member\MemberRepayment();
        $repayParams->setUserId((int)$payload['user_id']);
        $repayParams->setRepayMonth(I('post.repay_month'));
        $repayParams->setGiveMoney(I('post.order_amount'));
        $repayParams->setPayType(2);// 1余额 2支付宝 3银联 4 其它
        $repayParams->setOrgId(PLATFORM);
        $repayParams->setStatus(1);//
        list($reply) = GRPC('member')->UpdateMemberRepaymentOperate($repayParams)->wait();
        $ret = $reply->getRet();
        $msg = $reply->getMsg();
        if($ret == 'ok'){
            $params = explode(',',$msg);
            $order['order_sn'] = $params[0];
            $order['order_amount'] = $params[1];
            $order['order_id'] = $params[2];

        }else{
            $this->error("{$msg}", U('Home/User/my_loan'));
            exit;
        }
    	$pay_radio = $_REQUEST['pay_radio'];
    	$config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
    	$code_str = $this->payment->get_code($order,$config_value);
    	//微信JS支付
    	if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
//    		$code_str = $this->payment->getJSAPI($order,$config_value);
//    		exit($code_str);
    	}
    	$this->assign('code_str', $code_str);
    	$this->assign('order_id', $order['order_id']);
    	return $this->fetch('recharge'); //分跳转 和不 跳转
    }
    
           
    public function notifyUrl()
    {
        $this->payment->response();            
        exit();
    }


    public function returnUrl()
    {
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        //账户充值
        if(stripos($result['order_sn'],'recharge') !== false)
        {

            $pay_number = new Psp\Member\PayNumber();
            $pay_number->setPayNumber($result['order_sn']);
            list($resp) = GRPC('member')->GetMemberRepaymentInfo($pay_number)->wait();
            $order['order_amount'] = $resp->getPayNumber();//单号
            $order['account'] = $resp->getGiveMoney();//还款金额
            $this->assign('order', $order);

            if($result['status'] == 1)
                $this->redirect('repayment_ok',['pay_code'=>'alipay','order_sn'=>$_GET['out_trade_no'],'order_amount'=>$_GET['total_fee']]);
            else
                $this->redirect('repayment_fail',['pay_code'=>'alipay','order_sn'=>$_GET['out_trade_no'],'order_amount'=>$_GET['total_fee']]);
            exit();
        }

        /*根据支付号获取订单号*/
        $code = Trade(PayCode);
        $code->setPayCode($result['order_sn']);
        list($res,$status) =  GRPC(Trade)->GetOrderFromPaycode($code)->wait();
        $order['order_amount'] = $res->getMoney();
        $order['order_id'] = $res->getOrderIds();
        $order['pay_code'] = $result['order_sn'];
//        $this->assign('order',$order);
        if($result['status'] == 1)
            $this->redirect('payOk',['pay_code'=>'alipay','order_id'=>$order['order_id'],'order_sn'=>$_GET['out_trade_no'],'order_amount'=>$_GET['total_fee']]);
        else
            $this->redirect('payFail',['pay_code'=>'alipay','order_id'=>$order['order_id'],'order_sn'=>$_GET['out_trade_no'],'order_amount'=>$_GET['total_fee']]);

    }

    /**支付成功跳转页面**/
    public function payOk(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号   小强的pay_code是支付号  此处用订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('success');
    }

    /**支付回调失败跳转页面**/
    public function payFail(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('error');
    }

    //批量转账的回调方法
    public function notifyBack(){
        $this->payment->transfer_response();
    }

    //还款成功
    public function repayment_ok(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('recharge_success');
    }

    //还款失败
    public function repayment_fail(){
        $order['order_id'] = $_GET['order_id'];
        $order['pay_code'] =$_GET['order_sn'];//订单号
        $order['order_amount'] =$_GET['order_amount'];
        $this->assign('order',$order);
        return $this->fetch('recharge_error');
    }

}

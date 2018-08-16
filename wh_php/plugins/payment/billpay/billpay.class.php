<?php
/**
 * 商城 支付插件
 * Author: Ning(nk11@qq.com)
 */

use think\Model;
use think\Request;

class billpay extends Model
{   
    public $tableName = 'plugin'; // 插件表,比较恶心的东西,tp3.2.3要不就关闭表自动验证,否则注释此处会报错 
    public $billpay_config = array();// 支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {

        parent::__construct();

        unset($_GET['pay_code']);
        unset($_REQUEST['pay_code']);

        $config = include_once 'config.php';
        $this->config_value =$config['config'];
//        dump($this->config_value);die;

        /*====以下参数为必填参数

        //人民币网关账号，该账号为11位人民币网关商户编号+01,该参数必填。
        $merchantAcctId = "1001213884201";
        //编码方式，1代表 UTF-8; 2 代表 GBK; 3代表 GB2312 默认为1,该参数必填。
        $inputCharset = "1";
        //接收支付结果的页面地址，该参数一般置为空即可。
        $pageUrl = "";
        //服务器接收支付结果的后台地址，该参数务必填写，不能为空。
        $bgUrl = "http://219.233.173.50:8802/futao/rmb_demo/recieve.php";
        //网关版本，固定值：v2.0,该参数必填。
        $version =  "v2.0";
        //语言种类，1代表中文显示，2代表英文显示。默认为1,该参数必填。
        $language =  "1";
        //签名类型,该值为4，代表PKI加密方式,该参数必填。
        $signType =  "4";
        //商户订单号，以下采用时间来定义订单号，商户可以根据自己订单号的定义规则来定义该值，不能为空。
        $orderId = date("YmdHis");
        //订单金额，金额以“分”为单位，商户测试以1分测试即可，切勿以大金额测试。该参数必填。
        $orderAmount = "1";
        //订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101，不能为空。
        $orderTime = date("YmdHis");
        //支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10，必填。
        $payType = "00";
        $pid = "";
        // signMsg 签名字符串 不可空，生成加密签名串
        */


    }
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     */
    function get_code($order)
    {

        include_once ('sign_service.php');
		$params = array(

            //TODO 以下信息需要填写

            'inputCharset'=>$this->config_value[inputCharset],//编码方式
//            'pageUrl'=>SITE_URL.U('Payment/returnUrl',array('pay_code'=>'billpay')),//编码方式
            'bgUrl'=>SITE_URL.U('Payment/notifyUrl',array('pay_code'=>'billpay')),//服务器接收支付结果的后台地址，该参数务必填写，不能为空
            'version'=>$this->config_value[version],//网关版本，固定值：v2.0,该参数必填
            'language'=>$this->config_value[language],//语言种类，1代表中文显示，2代表英文显示
            'signType'=>$this->config_value[signType],//签名类型,该值为4，代表PKI加密方式,该参数必填。
            'merchantAcctId'=>$this->config_value[merchantAcctId],//人民币网关账号，该账号为11位人民币网关商户编号+01,该参数必填
            'orderId'=>$order['order_sn'],	//商户订单号，8-32位数字字母，不能含“-”或“_”
            'orderAmount'=>( $order['order_amount']*100),	//交易金额，单位分
            'orderTime'=>date('YmdHis',$order['add_time']),	//订单发送日期，格式为YYYYMMDD，取北京时间，
            'payType'=> $this->config_value[payType],//支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10
            'pid'=>$this->config_value[pid],//signMsg 签名字符串 不可空，生成加密签名串
            
            //TODO 其他特殊用法请查看
		);

        $params['signMsg'] = SignService::do_sign( $params );//验签,加密
		$html_form = SignService::createAutoFormHtml( $params, $this->config_value['payment_url'] );//生成页面

		return $html_form;
    }
    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {

        file_put_contents('/home/payment/kq.log',json_encode($_REQUEST).date('Ymd H:i:s')."\n",FILE_APPEND);
        //include_once ('sign_service.php');
        //$checkstatus = SignService::checkRecive($_REQUEST);//验签,加密
        empty(!$_REQUEST[merchantAcctId])&& $kq_check_all_para='merchantAcctId='.$_REQUEST[merchantAcctId].'&';
        empty(!$_REQUEST[version])&& $kq_check_all_para .='version='.$_REQUEST[version].'&';
        empty(!$_REQUEST[language])&& $kq_check_all_para .='language='.$_REQUEST[language].'&';
        empty(!$_REQUEST[signType])&& $kq_check_all_para .='signType='.$_REQUEST[signType].'&';
        empty(!$_REQUEST[payType])&& $kq_check_all_para .='payType='.$_REQUEST[payType].'&';
        empty(!$_REQUEST[bankId])&& $kq_check_all_para .='bankId='.$_REQUEST[bankId].'&';
        empty(!$_REQUEST[orderId])&& $kq_check_all_para .='orderId='.$_REQUEST[orderId].'&';
        empty(!$_REQUEST[orderTime])&& $kq_check_all_para .='orderTime='.$_REQUEST[orderTime].'&';
        empty(!$_REQUEST[orderAmount])&& $kq_check_all_para .='orderAmount='.$_REQUEST[orderAmount].'&';
        empty(!$_REQUEST[bindCard])&& $kq_check_all_para .='bindCard='.$_REQUEST[bindCard].'&';
        empty(!$_REQUEST[bindMobile])&& $kq_check_all_para .='bindMobile='.$_REQUEST[bindMobile].'&';
        empty(!$_REQUEST[dealId])&& $kq_check_all_para .='dealId='.$_REQUEST[dealId].'&';
        empty(!$_REQUEST[bankDealId])&& $kq_check_all_para .='bankDealId='.$_REQUEST[bankDealId].'&';
        empty(!$_REQUEST[dealTime])&& $kq_check_all_para .='dealTime='.$_REQUEST[dealTime].'&';
        empty(!$_REQUEST[payAmount])&& $kq_check_all_para .='payAmount='.$_REQUEST[payAmount].'&';
        empty(!$_REQUEST[fee])&& $kq_check_all_para .='fee='.$_REQUEST[fee].'&';
        empty(!$_REQUEST[ext1])&& $kq_check_all_para .='ext1='.$_REQUEST[ext1].'&';
        empty(!$_REQUEST[ext2])&& $kq_check_all_para .='ext2='.$_REQUEST[ext2].'&';
        empty(!$_REQUEST[payResult])&& $kq_check_all_para .='payResult='.$_REQUEST[payResult].'&';
        empty(!$_REQUEST[errCode])&& $kq_check_all_para .='errCode='.$_REQUEST[errCode].'&';

        $trans_body=substr($kq_check_all_para,0,-1);
        $MAC=base64_decode($_REQUEST[signMsg]);
        $cert = file_get_contents("cert1/99bill.cert.rsa.20340630.cer", 8192);
        $pubkeyid = openssl_get_publickey($cert);
        $checkstatus = openssl_verify($trans_body, $MAC, $pubkeyid);

        if ($checkstatus==1){
            switch($_REQUEST[payResult]){
                case '10':
                    //此处做商户逻辑处理
                    update_pay_status($_REQUEST[orderId],$_REQUEST[payAmount]/100);   // 修改订单支付状态
                    $rtnOK=1;
                    $rtnUrl = SITE_URL.U('Payment/returnUrl',array('pay_code'=>'billpay',"status"=>$rtnOK));
                    break;

                default:
                    $rtnOK=0;
                    //以下是我们快钱设置的show页面，商户需要自己定义该页面。
                    $rtnUrl = SITE_URL.U('Payment/returnUrl',array('pay_code'=>'billpay',"status"=>$rtnOK));

                    break;
            }

        }else{
            $rtnOK=0;//验签失败
            $rtnUrl = SITE_URL.U('Payment/returnUrl',array('pay_code'=>'billpay',"status"=>$rtnOK));

        }

        echo '<<result>'.$rtnOK.'</result>'.'<redirecturl>'.$rtnUrl.'</redirecturl>';

    }
    
    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        return array('status'=>$_REQUEST[status],'order_sn'=>$_REQUEST[orderId]);//跳转至成功页面

    }


    
}

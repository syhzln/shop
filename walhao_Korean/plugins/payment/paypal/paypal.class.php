<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/17 0017
 * Time: 22:48
 */
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\PaymentExecution;



class paypal
{




    public function get_code($order){

        require_once "vendor/autoload.php";


        $paypal = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
            'AaiMgYE_w7t6YsqS-pMZMJ4LYBdOy5V7KB191F-f4bf4Xi9dQ1pYRleb5SXeRBDIlyarqk5MCQlIrUkf',
            'EJzwYiCpRu2V3RwPzJuFDI2123EwjuSwweUlj_G58yNlxv2_Fo8hGcatcJ3S72E0JL5qDDi8eQQ6c-Po'
            )
        );
       $paypal->setConfig(['mode' => 'live']);

        if (!isset($order)) {
            die("lose some params");
        }

        $product = "walhao order";
        $price = $order['order_amount'];
        $shipping = $order['shipping_price']; //运费

        $total = $price + $shipping;

        //支付者信息
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');



        //商品信息
        $item = new Item();
        $item->setName($product)
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems([$item]);


        $details = new Details();
        $details->setShipping($shipping)
            ->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($total)
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription("Description")
            ->setInvoiceNumber($order['order_sn']);
        //回调信息
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(SITE_URL.U('Payment/returnUrl',array('pay_code'=>'paypal','state'=>'succ')))
            ->setCancelUrl(SITE_URL.U('Payment/returnUrl',array('pay_code'=>'paypal','state'=>'failed')));

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        try {
            $payment->create($paypal);  //错误点

        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }
         $approvalUrl = $payment->getApprovalLink();
        header("Location: {$approvalUrl}");

    }

    public function respond2(){
        

	file_put_contents('/web/www/plugins/payment/paypal/paypal.log',json_encode($_GET)."\n\n",8);
	file_put_contents('/web/www/plugins/payment/paypal/paypal.log',json_encode($_REQUEST)."\n\n\n",8);
	if($_GET['state']=='failed') 
            return array('status'=>0,'order_sn'=>$ordersn);//跳转页面

        require_once "vendor/autoload.php";

        $paypal = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
            'AaiMgYE_w7t6YsqS-pMZMJ4LYBdOy5V7KB191F-f4bf4Xi9dQ1pYRleb5SXeRBDIlyarqk5MCQlIrUkf',
            'EJzwYiCpRu2V3RwPzJuFDI2123EwjuSwweUlj_G58yNlxv2_Fo8hGcatcJ3S72E0JL5qDDi8eQQ6c-Po'
            )
        );
	
        $paypal->setConfig(['mode' => 'live']);

//        if(!isset($_GET['state'], $_GET['paymentId'], $_GET['PayerID'])){
//            die();
//        }



        $paymentID = $_GET['paymentId'];
        $payerId = $_GET['PayerID'];

        $payment = Payment::get($paymentID, $paypal);

        $ordersn = $payment->getTransactions()[0]->getInvoiceNumber();


        $execute = new PaymentExecution();
        $execute->setPayerId($payerId);


        try{
            $result = $payment->execute($execute, $paypal);
            update_pay_status($ordersn);
            return array('status'=>1,'order_sn'=>$ordersn);//跳转至成功页面


        }catch(Exception $e){

            return array('status'=>0,'order_sn'=>$ordersn);//跳转至成功页面

//            die($e);

        }




    }




    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {
        if (count($_POST) > 0) {
            include 'util/common.php';
            include 'util/SecssUtil.class.php';
            //验签
            $secssUtil = new SecssUtil();
            $securityPropFile = $_SERVER['DOCUMENT_ROOT'] . "/plugins/payment/unionpay/config/security.properties";
            $secssUtil->init($securityPropFile);
            $text = array();
            foreach($_POST as $key=>$value){
                $text[$key] = urldecode($value);
            }
            //file_put_contents('/web/www/plugins/payment/unionpay/aa.txt',json_encode($text)."\n",8);
            if ($secssUtil->verify($text)&&$_POST['OrderStatus'] == '0000') {
                 checkPay($_POST['MerOrderNo'],$_POST['OrderAmt']/100) && update_pay_status($_POST['MerOrderNo']); // 修改订单支付状态       

              
                echo "success"; // 处理成功
            } else {
                echo "fail"; //验证失败   ;
            }
        }
    }
     



}

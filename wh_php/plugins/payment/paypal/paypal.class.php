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


/**
*paypal 支付方式简化版
*/
class paypal
{
//todo 后期功能许强化,传参 内添加收货地址信息,商品信息,回调增加校验等


    public function get_code($order){


        $paypal = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
            'Ado0ECok_tKrlWB2USi8T7T0iSJnODTfn__U0LBEkmQbK7L5MgBsbMDMftBiVz9TgMAvN4-aDFizWjy_',
            'EDCagTUgo_KyB19ldssneK43PwQ1zX7QUh0Z00UTTcs6dbVF6EmoDnygebBBMKCVcpL5VE-suritRpVA'
            )
        );

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
            ->setCancelUrl(SITE_URL.U('Payment/notifyUrl',array('pay_code'=>'paypal','state'=>'succ')));

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

        $paypal = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
            'Ado0ECok_tKrlWB2USi8T7T0iSJnODTfn__U0LBEkmQbK7L5MgBsbMDMftBiVz9TgMAvN4-aDFizWjy_',
            'EDCagTUgo_KyB19ldssneK43PwQ1zX7QUh0Z00UTTcs6dbVF6EmoDnygebBBMKCVcpL5VE-suritRpVA'
            )
        );

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



        echo 'SUCCESS! Thank You!';

    }








}
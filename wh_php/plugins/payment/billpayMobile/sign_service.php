<?php
/**
 * 块钱签名验签文件发送
 * Author: Ning(nk11@qq.com)
 * Date: 2017/10/23 0005
 * Time: 上午 10:24
 */

class SignService{
    /**
     * 加密签名数据
     * @Authorhtl {Ning<nk11@qq.com>}
     * @DateTime  2017-10-23T11:04:44+0800
     * @param     array  $data 订单信息,支付提交信息
     * @return    array  sendMap 对$data进行排序,加密,生成签名文件
     */
    static public function do_sign($data){
        $kq_all_para='';
        foreach($data as $k=>$v){
            if($v!=='') $kq_all_para .= $k.'='.$v.'&';
        }
        $kq_all_para=substr($kq_all_para,0,-1);


        /////////////  RSA 签名计算 ///////// 开始 //

        $priv_key = file_get_contents("cert1/99bill-rsa.pem",'wohao2017');

        $pkeyid = openssl_get_privatekey($priv_key);

        // compute signature
        openssl_sign($kq_all_para, $signMsg, $pkeyid);

        // free the key from memory
        openssl_free_key($pkeyid);

        return base64_encode($signMsg);


        /////////////  RSA 签名计算 ///////// 结束 //

    }

    /**
     * 加密签名数据
     * @Authorhtl {Ning<nk11@qq.com>}
     * @DateTime  2017-10-23T16:04:44+0800
     * @param     array  $data 订单信息,支付提交信息
     * @return    array  sendMap 对$data进行排序,加密,生成签名文件
     */
    static public function checkRecive($data){

        empty(!$data[merchantAcctId])&& $kq_check_all_para = 'merchantAcctId ='.$data[merchantAcctId].'&';
        empty(!$data[version])&& $kq_check_all_para .= 'version ='.$data[version].'&';
        empty(!$data[language])&& $kq_check_all_para .= 'language ='.$data[language].'&';
        empty(!$data[signType])&& $kq_check_all_para .= 'signType ='.$data[signType].'&';
        empty(!$data[payType])&& $kq_check_all_para .= 'payType ='.$data[payType].'&';
        empty(!$data[bankId])&& $kq_check_all_para .= 'bankId ='.$data[bankId].'&';
        empty(!$data[orderId])&& $kq_check_all_para .= 'orderId ='.$data[orderId].'&';
        empty(!$data[orderTime])&& $kq_check_all_para .= 'orderTime ='.$data[orderTime].'&';
        empty(!$data[orderAmount])&& $kq_check_all_para .= 'orderAmount ='.$data[orderAmount].'&';
        empty(!$data[bindCard])&& $kq_check_all_para .= 'bindCard ='.$data[bindCard].'&';
        empty(!$data[bindMobile])&& $kq_check_all_para .= 'bindMobile ='.$data[bindMobile].'&';
        empty(!$data[dealId])&& $kq_check_all_para .= 'dealId ='.$data[dealId].'&';
        empty(!$data[bankDealId])&& $kq_check_all_para .= 'bankDealId ='.$data[bankDealId].'&';
        empty(!$data[dealTime])&& $kq_check_all_para .= 'dealTime ='.$data[dealTime].'&';
        empty(!$data[payAmount])&& $kq_check_all_para .= 'payAmount ='.$data[payAmount].'&';
        empty(!$data[fee])&& $kq_check_all_para .= 'fee ='.$data[fee].'&';
        empty(!$data[ext1])&& $kq_check_all_para .= 'ext1 ='.$data[ext1].'&';
        empty(!$data[ext2])&& $kq_check_all_para .= 'ext2 ='.$data[ext2].'&';
        empty(!$data[payResult])&& $kq_check_all_para .= 'payResult ='.$data[payResult].'&';
        empty(!$data[errCode])&& $kq_check_all_para .= 'errCode ='.$data[errCode].'&';
        $trans_body=substr($kq_check_all_para,0,-1);
        $MAC=base64_decode($data[signMsg]);
        $cert = file_get_contents("cert1/99bill.cert.rsa.20340630.cer", 8192);
        $pubkeyid = openssl_get_publickey($cert);
        return openssl_verify($trans_body, $MAC, $pubkeyid);

    }


    /**
 * 创建提交页面
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-10-23T13:07:42+0800
 * @param     array  $params 要提交到chinapay的支付信息
 * @param     string $reqUrl chinapay接口
 * @return    string html页面
 */
    static function createAutoFormHtml($params, $reqUrl) {
        
        $encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
	
eot;
        foreach ( $params as $key => $value ) {

                $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";

        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }


}


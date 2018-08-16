<?php
return array(
    'code'=> 'billpay',
    'name' => '块钱',
    'version' => '1.0',
    'author' => 'Ning',
    'desc' => '块钱支付 ',
    'scene' =>0,  // 使用场景 0 PC+手机 1 手机 2 PC
    'icon' => 'logo.jpg',
    'config' => array(

        merchantAcctId => "1020996924601",//人民币网关账号，该账号为11位人民币网关商户编号+01,该参数必填。

        inputCharset => "1",//编码方式，1代表 UTF-8; 2 代表 GBK; 3代表 GB2312 默认为1,该参数必填。

        pageUrl => "",//接收支付结果的页面地址，该参数一般置为空即可。

        bgUrl => "http://118.31.42.205/futao/rmb_demo/recieve.php",//服务器接收支付结果的后台地址，该参数务必填写，不能为空。

        version =>  "v2.0", //网关版本，固定值：v2.0,该参数必填。

        language =>  "1",//语言种类，1代表中文显示，2代表英文显示。默认为1,该参数必填。

        signType =>  "4",//签名类型,该值为4，代表PKI加密方式,该参数必填。

        payerName=> "", //支付人姓名,可以为空。

        payerContactType =>  "1", //支付人联系类型，1 代表电子邮件方式；2 代表手机联系方式。可以为空。

        payerContact => "2532987@qq.com",//支付人联系方式，与payerContactType设置对应，payerContactType为1，则填写邮箱地址；payerContactType为2，则填写手机号码。可以为空。

        orderId => date("YmdHis"), //商户订单号，以下采用时间来定义订单号，商户可以根据自己订单号的定义规则来定义该值，不能为空。

        orderAmount => "1", //订单金额，金额以“分”为单位，商户测试以1分测试即可，切勿以大金额测试。该参数必填。

        orderTime => date("YmdHis"), //订单提交时间，格式：yyyyMMddHHmmss，如：20071117020101，不能为空。

        productName=> "苹果",//商品名称，可以为空。

        productNum => "5",//商品数量，可以为空。

        productId => "55558888",//商品代码，可以为空。

        productDesc => "",//商品描述，可以为空。

        ext1 => "",//扩展字段1，商户可以传递自己需要的参数，支付完快钱会原值返回，可以为空。

        ext2 => "",//扩展自段2，商户可以传递自己需要的参数，支付完快钱会原值返回，可以为空。

        payType => "00",//支付方式，一般为00，代表所有的支付方式。如果是银行直连商户，该值为10，必填。

        bankId => "",//银行代码，如果payType为00，该值可以为空；如果payType为10，该值必须填写，具体请参考银行列表。

        redoFlag => "",//同一订单禁止重复提交标志，实物购物车填1，虚拟产品用0。1代表只能提交一次，0代表在支付不成功情况下可以再提交。可为空。

        pid => "10209969246",//快钱合作伙伴的帐户号，即商户编号，可为空。

        signMsg=>"",// signMsg 签名字符串 不可空，
        //===================================以上为接口默认字段==============
        payment_url=>"https://www.99bill.com/gateway/recvMerchantInfoAction.htm" //接口地址
    )
);

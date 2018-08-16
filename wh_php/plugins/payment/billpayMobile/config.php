<?php
return array(
    'code'=> 'billpayMobile',
    'name' => '快钱Mobile',
    'version' => '1.0',
    'author' => 'Ning',
    'desc' => '快钱手机支付 ',
    'scene' =>1,  // 使用场景 0 PC+手机 1 手机 2 PC
    'icon' => 'logo.jpg',
    'config' => array(
// ======================= 传送参数设置  开始  =====================================
//* 表示 必填写项目.  ( )里的表示字符长度

merchantAcctId => "1020996924601",  //*  商家用户编号		(30)
inputCharset	=> "1",	//   1 ->  UTF-8		2 -> GBK		3 -> GB2312   default: 1	(2)
pageUrl	    => "http://localhost/MobilePort/return.php",	//   直接跳转页面	(256)
bgUrl	        => "http://localhost/MobilePort/return.php",	//   后台通知页面	(256)
version	    => "mobile1.0",	//*	 版本  固定值 v2.0	(10)
language		=> "1",	//*  默认 1 ， 显示 汉语	(2)
signType		=> "4",   //*  固定值 1 表示 MD5 加密方式 , 4 表示 PKI 证书签名方式	(2)
payerName		=> "苹果",	//   英文或者中文字符	(32)
payerContactType => "1",  //  支付人联系类型  固定值： 1  代表电子邮件方式 (2)
payerContact   => "",	//	 支付人联系方式	(50)
orderId		=> date('YmdHis'),	//*  字母数字或者, _ , - ,  并且字母数字开头 并且在自身交易中式唯一	(50)
orderAmount	=> "10",	//*	  字符金额 以 分为单位 比如 10 元， 应写成 1000	(10)
orderTime		=> date('YmdHis'),  //*  交易时间  格式: 20110805110533
productName	=> "test",	//	  商品名称英文或者中文字符串(256)
productNum		=> "",	//	  商品数量	(8)
productId		=> "",   //    商品代码，可以是 字母,数字,-,_   (20)
productDesc	=> "sd",	//	  商品描述， 英文或者中文字符串  (400)
ext1			=> "",   //	  扩展字段， 英文或者中文字符串，支付完成后，按照原样返回给商户。 (128)
ext2			=> "",
payType		=> "21",	//*  固定选择值：00、15、21、21-1、21-2
//00代表显示快钱各支付方式列表；
//15信用卡无卡支付
//21 快捷支付
//21-1 代表储蓄卡快捷；21-2 代表信用卡快捷
//*其中”-”只允许在半角状态下输入。

bankId			=> "",   // 银行代码 银行代码 要在开通银行时 使用， 默认不开通 (8)
redoFlag		=> "1",   // 同一订单禁止重复提交标志  固定值 1 、 0
// 1 表示同一订单只允许提交一次 ； 0 表示在订单没有支付成功状态下 可以重复提交； 默认 0
pid			=> "",   //  合作伙伴在快钱的用户编号 (30)
payerIdType=>"3",	//指定付款人
payerId=>"3315",	//付款人标识
payment_url=>'https://www.99bill.com/mobilegateway/recvMerchantInfoAction.htm'
    )
);

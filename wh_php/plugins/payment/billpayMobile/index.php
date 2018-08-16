<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<?PHP

// ======================= 传送参数设置  开始  =====================================
//* 表示 必填写项目.  ( )里的表示字符长度

$kq_target="https://sandbox.99bill.com/mobilegateway/recvMerchantInfoAction.htm";

$kq_merchantAcctId = "1001213884201";   //*  商家用户编号		(30)


$kq_inputCharset	= "1";	//   1 ->  UTF-8		2 -> GBK		3 -> GB2312   default: 1	(2)
$kq_pageUrl	    = "http://localhost/MobilePort/return.php";	//   直接跳转页面	(256)
$kq_bgUrl	    = "http://localhost/MobilePort/return.php";	//   后台通知页面	(256)
$kq_version	    = "mobile1.0";	//*	 版本  固定值 v2.0	(10)
$kq_language		= "1";	//*  默认 1 ， 显示 汉语	(2)
$kq_signType		= "4";   //*  固定值 1 表示 MD5 加密方式 , 4 表示 PKI 证书签名方式	(2)

$kq_payerName		= "苹果";	//   英文或者中文字符	(32)
$kq_payerContactType = "1";  //  支付人联系类型  固定值： 1  代表电子邮件方式 (2)
$kq_payerContact   = "";	//	 支付人联系方式	(50)
$kq_orderId		= date('YmdHis');	//*  字母数字或者, _ , - ,  并且字母数字开头 并且在自身交易中式唯一	(50)
$kq_orderAmount	= "10";	//*	  字符金额 以 分为单位 比如 10 元， 应写成 1000	(10)
$kq_orderTime		= date('YmdHis');  //*  交易时间  格式: 20110805110533
$kq_productName	= "test";	//	  商品名称英文或者中文字符串(256)
$kq_productNum		= "";	//	  商品数量	(8)
$kq_productId		= "";   //    商品代码，可以是 字母,数字,-,_   (20) 
$kq_productDesc	= "sd";	//	  商品描述， 英文或者中文字符串  (400)
$kq_ext1			= "";   //	  扩展字段， 英文或者中文字符串，支付完成后，按照原样返回给商户。 (128)
$kq_ext2			= "";
$kq_payType		= "21";	//*  固定选择值：00、15、21、21-1、21-2
//00代表显示快钱各支付方式列表；
//15信用卡无卡支付
//21 快捷支付
//21-1 代表储蓄卡快捷；21-2 代表信用卡快捷
//*其中”-”只允许在半角状态下输入。

$kq_bankId			= "";   // 银行代码 银行代码 要在开通银行时 使用， 默认不开通 (8)
$kq_redoFlag		= "1";   // 同一订单禁止重复提交标志  固定值 1 、 0      
						// 1 表示同一订单只允许提交一次 ； 0 表示在订单没有支付成功状态下 可以重复提交； 默认 0 
$kq_pid			= "";   //  合作伙伴在快钱的用户编号 (30)

$kq_payerIdType="3";	//指定付款人

$kq_payerId="3315";	//付款人标识

// ======================= 传送参数设置  结束  =====================================

// ======================= 快钱 封装代码 ! ! 勿随便更改 开始  =====================================

function kq_ck_null($kq_va,$kq_na){if($kq_va == ""){$kq_va="";}else{return $kq_va=$kq_na.'='.$kq_va.'&';}}


	$kq_all_para=kq_ck_null($kq_inputCharset,'inputCharset');
	$kq_all_para.=kq_ck_null($kq_pageUrl,"pageUrl");
	$kq_all_para.=kq_ck_null($kq_bgUrl,'bgUrl');
	$kq_all_para.=kq_ck_null($kq_version,'version');
	$kq_all_para.=kq_ck_null($kq_language,'language');
	$kq_all_para.=kq_ck_null($kq_signType,'signType');
	$kq_all_para.=kq_ck_null($kq_merchantAcctId,'merchantAcctId');
	$kq_all_para.=kq_ck_null($kq_payerName,'payerName');
	$kq_all_para.=kq_ck_null($kq_payerContactType,'payerContactType');
	$kq_all_para.=kq_ck_null($kq_payerContact,'payerContact');
	$kq_all_para.=kq_ck_null($kq_payerIdType,'payerIdType');
	$kq_all_para.=kq_ck_null($kq_payerId,'payerId');
	$kq_all_para.=kq_ck_null($kq_orderId,'orderId');
	$kq_all_para.=kq_ck_null($kq_orderAmount,'orderAmount');
	$kq_all_para.=kq_ck_null($kq_orderTime,'orderTime');
	$kq_all_para.=kq_ck_null($kq_productName,'productName');
	$kq_all_para.=kq_ck_null($kq_productNum,'productNum');
	$kq_all_para.=kq_ck_null($kq_productId,'productId');
	$kq_all_para.=kq_ck_null($kq_productDesc,'productDesc');
	$kq_all_para.=kq_ck_null($kq_ext1,'ext1');
	$kq_all_para.=kq_ck_null($kq_ext2,'ext2');
	$kq_all_para.=kq_ck_null($kq_payType,'payType');
	$kq_all_para.=kq_ck_null($kq_bankId,'bankId');;
	$kq_all_para.=kq_ck_null($kq_redoFlag,'redoFlag');
	$kq_all_para.=kq_ck_null($kq_pid,'pid');


//$kq_all_para=substr($kq_all_para,0,strlen($kq_all_para)-1);
//$kq_all_para=substr($kq_all_para,0,-1);
  $kq_all_para=rtrim($kq_all_para,'&');
  echo $kq_all_para;

//////////////////////////////               lib  start  
////////  私钥加密 生成 MAC


	$priv_key = file_get_contents("./pcarduser.pem");

	$pkeyid = openssl_get_privatekey($priv_key);

	echo '$pkeyid='.$pkeyid.'<br>';

	// compute signature
	openssl_sign($kq_all_para, $signMsg, $pkeyid);

	// free the key from memory
	openssl_free_key($pkeyid);

	 $kq_sign_msg = base64_encode($signMsg);

echo $kq_sign_msg;

///////////

//////////////////////////////               lib  end


$kq_get_url=$kq_target.'?'.$kq_all_para.'&signMsg='.$kq_sign_msg;

// ======================= 快钱 封装代码 ! ! 勿随便更改 结束  =====================================


?>



<form method=get action="<?PHP echo $kq_target; ?>" target="_blank">
	<input type="hidden" name="inputCharset" value="<?PHP echo $kq_inputCharset; ?>">
	<input type="hidden" name="pageUrl" value="<?PHP echo $kq_pageUrl; ?>">
	<input type="hidden" name="bgUrl" value="<?PHP echo $kq_bgUrl; ?>">
	<input type="hidden" name="version" value="<?PHP echo $kq_version; ?>">
	<input type="hidden" name="language" value="<?PHP echo $kq_language; ?>">
	<input type="hidden" name="signType" value="<?PHP echo $kq_signType; ?>">
	<input type="hidden" name="merchantAcctId" value="<?PHP echo $kq_merchantAcctId; ?>">
	<input type="hidden" name="payerName" value="<?PHP echo $kq_payerName; ?>">
	<input type="hidden" name="payerContactType" value="<?PHP echo $kq_payerContactType; ?>">
	<input type="hidden" name="payerContact" value="<?PHP echo $kq_payerContact; ?>">


<input type="hidden" name="payerIdType" value="<?PHP echo $kq_payerIdType; ?>">
<input type="hidden" name="payerId" value="<?PHP echo $kq_payerId; ?>">

	<input type="hidden" name="orderId" value="<?PHP echo $kq_orderId; ?>">
	<input type="hidden" name="orderAmount" value="<?PHP echo $kq_orderAmount; ?>">
	<input type="hidden" name="orderTime" value="<?PHP echo $kq_orderTime; ?>">
	<input type="hidden" name="productName" value="<?PHP echo $kq_productName; ?>">
	<input type="hidden" name="productNum" value="<?PHP echo $kq_productNum; ?>">
	<input type="hidden" name="productId" value="<?PHP echo $kq_productId; ?>">
	<input type="hidden" name="productDesc" value="<?PHP echo $kq_productDesc; ?>">
	<input type="hidden" name="ext1" value="<?PHP echo $kq_ext1; ?>">
	<input type="hidden" name="ext2" value="<?PHP echo $kq_ext2; ?>">
	<input type="hidden" name="payType" value="<?PHP echo $kq_payType; ?>">
	<input type="hidden" name="bankId" value="<?PHP echo $kq_bankId; ?>">
	<input type="hidden" name="redoFlag" value="<?PHP echo $kq_redoFlag; ?>">
	<input type="hidden" name="pid" value="<?PHP echo $kq_pid; ?>">
	<input type="hidden" name="signMsg" value="<?PHP echo $kq_sign_msg; ?>">
	<input type="submit" value="SUBMIT" name="go_pay" style="font-size:32px;padding:10px;font-weight:bold;font-family:arail">
</form>
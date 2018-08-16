<?php


//      核对签名是否正确 ==============  开始 =================

function kq_ck_null($kq_va,$kq_na){if($kq_va == ""){return $kq_va="";}else{return $kq_va=$kq_na.'='.$kq_va.'&';}}

$kq_check_all_para=kq_ck_null($_GET[merchantAcctId],'merchantAcctId').kq_ck_null($_GET[version],'version').kq_ck_null($_GET[language],'language').kq_ck_null($_GET[signType],'signType').kq_ck_null($_GET[payType],'payType').kq_ck_null($_GET[bankId],'bankId').kq_ck_null($_GET[orderId],'orderId').kq_ck_null($_GET[orderTime],'orderTime').kq_ck_null($_GET[orderAmount],'orderAmount').kq_ck_null($_GET[bindCard],'bindCard').kq_ck_null($_GET[bindMobile],'bindMobile').kq_ck_null($_GET[dealId],'dealId').kq_ck_null($_GET[bankDealId],'bankDealId').kq_ck_null($_GET[dealTime],'dealTime').kq_ck_null($_GET[payAmount],'payAmount').kq_ck_null($_GET[fee],'fee').kq_ck_null($_GET[ext1],'ext1').kq_ck_null($_GET[ext2],'ext2').kq_ck_null($_GET[payResult],'payResult').kq_ck_null($_GET[errCode],'errCode');

//      核对签名是否正确 ==============  结束 =================


$trans_body=substr($kq_check_all_para,0,strlen($kq_check_all_para)-1);
$MAC=base64_decode($_GET[signMsg]);

//$fp = fopen("./99bill[1].cert.rsa.20140728.cer", "r"); 
//$cert = fread($fp, 8192); 
//fclose($fp); 

$cert = file_get_contents("./99bill[1].cert.rsa.20140803.cer");
$pubkeyid = openssl_get_publickey($cert); 
$ok = openssl_verify($trans_body, $MAC, $pubkeyid); 


if ($ok == 1) { 
	echo '<result>1</result><redirecturl>http://success.html</redirecturl>';
}else{
	echo '<result>1</result><redirecturl>http://false.html</redirecturl>';
}



?>


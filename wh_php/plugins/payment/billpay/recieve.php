<?php



	function kq_ck_null($kq_va,$kq_na){if($kq_va == ""){return $kq_va="";}else{return $kq_va=$kq_na.'='.$kq_va.'&';}}
	//����������˺ţ����˺�Ϊ11λ����������̻����+01,��ֵ���ύʱ��ͬ��
	$kq_check_all_para=kq_ck_null($_REQUEST[merchantAcctId],'merchantAcctId');
	//���ذ汾���̶�ֵ��v2.0,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[version],'version');
	//�������࣬1����������ʾ��2����Ӣ����ʾ��Ĭ��Ϊ1,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[language],'language');
	//ǩ������,��ֵΪ4������PKI���ܷ�ʽ,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[signType],'signType');
	//֧����ʽ��һ��Ϊ00���������е�֧����ʽ�����������ֱ���̻�����ֵΪ10,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[payType],'payType');
	//���д��룬���payTypeΪ00����ֵΪ�գ����payTypeΪ10,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[bankId],'bankId');
	//�̻������ţ�,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[orderId],'orderId');
	//�����ύʱ�䣬��ʽ��yyyyMMddHHmmss���磺20071117020101,��ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[orderTime],'orderTime');
	//����������ԡ��֡�Ϊ��λ���̻�������1�ֲ��Լ��ɣ������Դ������,��ֵ��֧��ʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[orderAmount],'orderAmount');
        //�Ѱ�̿���,���ÿ����֧���󶨿���Ϣ�󷵻�ǰ������λ���ÿ���
        $kq_check_all_para.=kq_ck_null($_REQUEST[bindCard],'bindCard');
        //�Ѱ���ֻ�β��,���ÿ����֧���󶨿���Ϣ�󷵻�ǰ��λ����λ�ֻ�����
        $kq_check_all_para.=kq_ck_null($_REQUEST[bindMobile],'bindMobile');
	// ��Ǯ���׺ţ��̻�ÿһ�ʽ��׶����ڿ�Ǯ����һ�����׺š�
	$kq_check_all_para.=kq_ck_null($_REQUEST[dealId],'dealId');
	//���н��׺� ����Ǯ����������֧��ʱ��Ӧ�Ľ��׺ţ��������ͨ�����п�֧������Ϊ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[bankDealId],'bankDealId');
	//��Ǯ����ʱ�䣬��Ǯ�Խ��׽��д����ʱ��,��ʽ��yyyyMMddHHmmss���磺20071117020101
	$kq_check_all_para.=kq_ck_null($_REQUEST[dealTime],'dealTime');
	//�̻�ʵ��֧����� �Է�Ϊ��λ���ȷ�10Ԫ���ύʱ���ӦΪ1000���ý������̻���Ǯ�˻������յ��Ľ�
	$kq_check_all_para.=kq_ck_null($_REQUEST[payAmount],'payAmount');
	//���ã���Ǯ��ȡ�̻��������ѣ���λΪ�֡�
	$kq_check_all_para.=kq_ck_null($_REQUEST[fee],'fee');
	//��չ�ֶ�1����ֵ���ύʱ��ͬ
	$kq_check_all_para.=kq_ck_null($_REQUEST[ext1],'ext1');
	//��չ�ֶ�2����ֵ���ύʱ��ͬ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[ext2],'ext2');
	//�������� 10֧���ɹ���11 ֧��ʧ�ܣ�00��������ɹ���01 ��������ʧ��
	$kq_check_all_para.=kq_ck_null($_REQUEST[payResult],'payResult');
	//������� ������ա���������ؽӿ��ĵ�����󲿷ֵ���ϸ���͡�
	$kq_check_all_para.=kq_ck_null($_REQUEST[errCode],'errCode');



	$trans_body=substr($kq_check_all_para,0,strlen($kq_check_all_para)-1);
	$MAC=base64_decode($_REQUEST[signMsg]);

	$fp = fopen("./99bill[1].cert.rsa.20140803.cer", "r"); 
	$cert = fread($fp, 8192); 
	fclose($fp); 
	$pubkeyid = openssl_get_publickey($cert); 
	$ok = openssl_verify($trans_body, $MAC, $pubkeyid); 


	if ($ok == 1) { 
		switch($_REQUEST[payResult]){
				case '10':
						//�˴����̻��߼�����
						$rtnOK=1;
						//���������ǿ�Ǯ���õ�showҳ�棬�̻���Ҫ�Լ������ҳ�档
						$rtnUrl="http://219.233.173.50:8802/futao/rmb_demo/show.php?msg=success";
						break;
				default:
						$rtnOK=0;
						//���������ǿ�Ǯ���õ�showҳ�棬�̻���Ҫ�Լ������ҳ�档
						$rtnUrl="http://219.233.173.50:8802/futao/rmb_demo/show.php?msg=false";
						break;	
		
		}

	}else{
						$rtnOK=0;
						//���������ǿ�Ǯ���õ�showҳ�棬�̻���Ҫ�Լ������ҳ�档
						$rtnUrl="http://219.233.173.50:8802/futao/rmb_demo/show.php?msg=error";
							
	}



?>

<result><?PHP echo $rtnOK; ?></result> <redirecturl><?PHP echo $rtnUrl; ?></redirecturl>
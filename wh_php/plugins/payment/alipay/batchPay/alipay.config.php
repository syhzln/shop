<?php
/* *
 * 配置文件
 * 版本：3.3
 * 日期：2012-07-19
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
	
 * 提示：如何获取安全校验码和合作身份者id
 * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
 * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
 * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”
	
 * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
 * 解决方法：
 * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
 * 2、更换浏览器或电脑，重新登录查询。
 */
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者id，以2088开头的16位纯数字
$batch_alipay_config['partner']		= '2088921983334411';

//安全检验码，以数字和字母组成的32位字符
$batch_alipay_config['key']			= 'zpsfqihohgi41wn9sgccvbet8b7vptg2';


//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


//签名方式 不需修改
$batch_alipay_config['sign_type']    = strtoupper('MD5');

//字符编码格式 目前支持 gbk 或 utf-8
$batch_alipay_config['input_charset']= strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$batch_alipay_config['cacert']    = getcwd().'\\cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$batch_alipay_config['transport']    = 'http';


//服务器异步通知页面路径
$batch_alipay_config['notify_url'] = "http://www.yunjip.com/index.php/Home/Payment/notifyBack";
//需http://格式的完整路径，不允许加?id=123这类自定义参数

//付款账号
$batch_alipay_config['email'] = "lzsk2018@163.com";
//必填

//付款账户名
$batch_alipay_config['account_name'] = "杭州量子时空网络科技有限公司";
//必填，个人支付宝账号是真实姓名公司支付宝账号是公司名称


return $batch_alipay_config;
?>
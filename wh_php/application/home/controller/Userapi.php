<?php
/**
 * Created by PhpStorm.
 * User: ning[nk11@qq.com]
 * Date: 2017/1/3
 * Time: 18:36
 */
namespace app\home\controller;
use think\Controller;
use home\Logic\UsersLogic;
use app\common\logic\SmsLogic;
use think\Jump;
use Psp;
Use Grpc;
class Userapi extends Controller{

/**
 * 接受推送信息入库
 * @return json
 */
public function adduser()
{//接受tp138的用户信息，录入会员库做信息比对
    // exit('脚本暂停,如修复请联系');
    $user_id = I('post.user_id');//接收到的用户id
    $email = I('post.email');//接收到的用户email
    $pwd = I('post.pwd');//接受到的密码  算法：sha1('!#*' . trim(密码原文) . $pwd_token . 'tps')
    $pwd_token = I('post.pwd_token');//接收的token
    $parent_id = I('post.parent_id');//推荐人id
    $languageid = I('post.languageid');//语种id 会员注册语种：1中文，2英文
    $name = I('post.name');//用户姓名
    $mobile = I('post.mobile');//用户手机号
    $country_id = I('post.country_id');//国别id array（）
    $address = I('post.address');//地址
    $store_prefix = I('post.store_prefix');//店铺前缀
    $store_level = I('post.store_level');//店铺等级
    $create_time = I('post.create_time');//会员注册时间
    $status = I('post.status');//账户状态账户状态：0->未激活，1->激活(正常)，2->月费未支付，3->冻结账户,4->公司预留账户（只有状态为1，2时店铺才开启）
    $token = I('post.token');//验证字符串
    $sign = I('post.sign');//签名。sign=sha1(‘tps’.sha1($token).’!#*’)

    /**
     * 接收数据以后查询本地库存不存在信息，不存在录入信息，存在修改信息
     */
    
    $data = I('post.');
    M('adduser')->add($data);

        // file_put_contents('/web/www/Application/Home/Controller/user.log',json_encode($_POST)."\n",8);
     if (sha1('tps'.sha1($token).'!#*')!=$sign){//判断签名信息是否正确
         $info['error_code'] =101;
         $info['data'] = ['error_info'=>'签名验证失败；'];

     }
     else{

//         $usermodel = M('users');
//         $userinfo = $usermodel->where("tp138_user_id = '{$user_id}'")->find();
//         if (!$userinfo){
//
//             // if(empty($pwd)||empty($pwd_token)) exit(json_encode(['error_code'=>101,'msg'=>'新增数据密码为空','data'=>$data]));
//
//             $usermodel->tp138_user_id = $user_id;//tp138用户id
//             $usermodel->store_prefix = $store_prefix;//tp138用户id
//             $usermodel->email = $email;//用户邮箱
//             $usermodel->password = $pwd;//已修改
//             $usermodel->pwd_token = $pwd_token;//密码加密token
//             $usermodel->token = $token;//用来安全验证的串
//             $usermodel->level = $store_level;//会员店铺等级
//             $usermodel->first_leader = $parent_id;//推荐人id
//             $usermodel->reg_time = $create_time;//会员注册时间
//             $usermodel->nickname = $name;//会员名称
//             $usermodel->mobile = $mobile;//会员注册手机号
//             $usermodel->country_id = $country_id;//国别
//             $usermodel->languageid = $languageid;//语种idid
//             $usermodel->status = $status;//会员状态
//             $addid = $usermodel->add();//保存生成主键
//
//             // $sql = "update tp_user_address set user_id = {$addid} where address_id = $addrid";//升级用户地址表的user_id，绑定关联关系
//             // $Model = new \Think\Model();
//             // $Model->query($sql);
//
//             if($addid){
//                 $info['error_code'] =0;
//                 $info['data'] = ['succ_info'=>'用户录入成功！'];
//             }else{
//                 $info['error_code'] =100;
//                 $info['data'] = ['error_info'=>'用户录入失败！'];
//             }
//
//         }else{
             $Model = new \Think\Model();

             $sql = " update tp_users set ";
                if (!empty($store_prefix)) {
                    $sql .= "store_prefix = '{$store_prefix}',";
                        }
                if (!empty($email)) {
                    $sql .= "email='{$email}',";
                        }
                if (!empty($pwd)) {
                    $sql .= "password='{$pwd}',";
                        }
                if (!empty($pwd_token)) {
                    $sql .= "pwd_token='{$pwd_token}',";
                        }
                if (!empty($store_level)) {
                    $sql .= "level='{$store_level}',";
                        }
                if (!empty($parent_id)) {
                    $sql .= "first_leader='{$parent_id}',";
                        }
                if (!empty($create_time)) {
                    $sql .= "reg_time='{$create_time}',";
                        }
                if (!empty($name)) {
                    $sql .= "nickname='{$name}',";
                        }
                if (!empty($mobile)) {
                    $sql .= "mobile='{$mobile}',";
                        }
                if (!empty($country_id)) {
                    $sql .= "country_id='{$country_id}',";
                        }
                if (!empty($languageid)) {
                    $sql .= "languageid='{$languageid}',";
                        }
                if (!empty($status)) {
                    $sql .= "status='{$status}'";
                        }
                $sql = rtrim($sql,',');

            $sql.= " where tp138_user_id ='$user_id'";


             $Model->query($sql);

             $info['error_code'] =0;
             $info['data'] = ['succ_info'=>'数据已更新'];

         }
//     }
     $this->ajaxReturn($info);
     }


/**
 * 商家注册登录页面
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-04-15T16:54:17+0800
 */
    public function login(){

        return $this->fetch();
    }


/**
 * 发送验证短信
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-04-15T16:55:18+0800
 * @return json
 */
    public function sendreg_sms(){
        $mobile = I('mobile');//手机号
        $type = I('type',1);
        if($type == 2){
         //先验证手机号合法性
         $user_name = I('user_name');
         $sellerPhone = new Psp\Seller\SellerPhone();
         $sellerPhone->setUserName($user_name);
         $sellerPhone->setSellerPhone($mobile);
         list($resp,$status) = GRPC('seller')->GetSellerPhone($sellerPhone)->wait();
         $ret = $resp->getRet();
         $msg = $resp->getMsg();
         if($ret == 'fail'){
             $this->ajaxReturn(['status'=>-2,'msg'=>$msg]);
         }

        }

        $code =  rand(100000,999999);//随机一个验证码
        $smsSign = "量子时空";//签名勿随意改动 否则会报错
        if($type == 2){
            $templateCode = 'SMS_128520082'; //模板code
        }else{
            $templateCode ='SMS_128520079';
        }

        $smsParam = "{\"code\":\"$code\"}";  // 验证码类型短信只能有一个变量
        $device_code = getEquipmentSystem(); //获取设备号
        //判断是否存在验证码
        $co = new Psp\Member\SmsSendStatus();
        $co->setMobile($mobile);
        $co->setStatus(1);
        $co->setDeviceCode($device_code);
        list($reply,$status) = GRPC('member')->GetSmsSendStatus($co)->wait();
        $co = $reply->getCode();
        $data['add_time'] = empty($reply->getSendTime()) ? time() : $reply->getSendTime()->getSeconds();
        //获取时间配置
        $sms_time_out = tpCache('sms.sms_time_out');
        if($co && (time() - $data['add_time']) < $sms_time_out){
            $return_arr = array('status'=>-1,'msg'=>$sms_time_out.'秒内不允许重复发送');
            $this->ajaxReturn($return_arr);
        }
        $payload = validate_json_web_token(cookie('token'));
        $org_id =(int)$payload['org_id'];
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        //发送记录存储数据库
        $add =new Psp\User\SendMsg();
        $add->setMobile($mobile);
        $add->setUserId((int)$payload['user_id']);
        $add->setContent($code);
        $add->setOrgId($org_id);
        $add->setSmsType($type);//1 商家注册/会员注册  2商家验证登录
        $add->setSendStatus(0);
        $add->setSendTime($time);
        $add->setDeviceNumber($device_code);//设备号
        list($reply,$status) = GRPC('user')->SendSms($add)->wait();
        $log_id = $reply->getSmsId();
        if($mobile<>'' && check_mobile($mobile)){//如果是正常的手机号码才发送
            $resp= sendSmsByAliyun($mobile, $smsSign, $smsParam , $templateCode);//发送短信
            if ($resp['status'] == 1) {
                $set = new Psp\Member\SetSmsStatus();
                $set->setStatus(1); //成功
                $set->setSmsId($log_id);
                list($reply,$status) = GRPC('member')->SetSmsSendStatus($set)->wait();//修改短信状态
            }
            $this->ajaxReturn($resp);
        }else{

            $this->ajaxReturn(['status' => -1, 'msg' => '接收手机号不正确['.$mobile.']']);
        }

    }


/**
 * 通过验证码临时登录
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-04-15T16:57:12+0800
 */
    public function regLog(){
        //获取设备号
        $device_code = getEquipmentSystem();
        $code = I('code');
        $mobile = I('mobile');
        if(empty($code)||empty($mobile))$this->error('参数不能为空');
        $co = new Psp\Member\SmsSendStatus();
        $co->setMobile($mobile);
        $co->setStatus(1);
        $co->setDeviceCode($device_code);
        list($reply,$status) = GRPC('member')->GetSmsSendStatus($co)->wait();
        $co = $reply->getCode();
        if($code!=$co||empty($code)) $this->error('未通过验证',"U('home/userapi/login')");

        $email="seller.$mobile";
        $var = new Psp\Store\EmailMsg();
        $var->setEmail($email);
        list($res,$status) = GRPC('sellerstore') ->GetUserId($var)->wait();
        $user_id=$res->getValue();
        if($user_id==false){
            $val = new Psp\Store\MobileMsg();
            $val->setPhone($mobile);
            list($result,$status) = GRPC('sellerstore') ->AddMobileMsg($val)->wait();
            $user_id=$result->getUserId();
            cookie('reg_id',$user_id);
            $this->redirect(U('home/newjoin/agreement'));

        }

    }


/**
 * 前段验证手机验证码
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-04-15T16:58:53+0800
 * @return str $code 验证码
 */
    public function checkCode(){
        //获取设备号
        $device_code = getEquipmentSystem();
        $mobile = I('mobile');//手机号
        //判断是否存在验证码
        $code = new Psp\Member\SmsSendStatus();
        $code->setMobile($mobile);
        $code->setStatus(1);
        $code->setDeviceCode($device_code);
        list($reply,$status) = GRPC('member')->GetSmsSendStatus($code)->wait();
        $code = $reply->getCode();
        exit($code);
    }

/**
 * en调用接口获取登录更新数据
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-05-12T09:07:57+0800
 * @return json//登陆成功或者失败的包,以及携带内容
 */
    public function do_login(){

        $username = I('username');
        $password = I('password');
        $logic = new UsersLogic();
        $res = $logic->login($username,$password);
        $this->ajaxreturn($res);
    }

/**
 * tps同步登陆walhao
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-05-03T09:19:42+0800
 * @return   json
 */
    public function tpsLogin(){
        header("Access-Control-Allow-Origin:*");       
        $aes = new \Org\Util\Aes();
        $user_id = I('uid');
        if (empty($user_id)) exit;
        $tp138_user_id = $aes->aes256ecbDecrypt($user_id,C('TPSKEY'));
        if ($tp138_user_id+0 < 1380000000) exit;
//        $user = M('users')->where(['tp138_user_id'=>"$tp138_user_id"])->find();
//        if(!$user) exit;
//        setcookie('user_id',$user['user_id'],null,'/','.walhao.com');
//        setcookie('uname',$user['nickname'],null,'/','.walhao.com');
        setcookie('cn',0,time()-3600,'/','.walhao.com'); 
        setcookie('tpscode',urlencode($user_id),null,'/','.walhao.com');
//        session('user',$user);
        $this->ajaxreturn(['登陆成功']);     
    }


/**
 * tps同步登出接口
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-06-20T08:49:51+0800
 */
    public function tpsLogout(){
        header("Access-Control-Allow-Origin:*");
          foreach ($_COOKIE as $key => $value) {

           setcookie($key,'',time()-3600,'/');
           setcookie($key,'',time()-3600,'/','walhao.com');
           setcookie($key,'',time()-3600,'/','www.walhao.com');
           setcookie($key,'',time()-3600,'/','.walhao.com');
           unset($_COOKIE[$key]);
        }
        session_unset();
        session_destroy();
        $this->ajaxreturn(['登出成功']);
    }

    public function ajaxReturn($data, $type = 'json')
    {
        exit(json_encode($data));
    }

    

    
  public function jm(){
      echo "加密前:1380486666";
      echo "<hr>";
 //     var_dump(include_once  "plugins/Aes/aes.class.php");exit;
 //     include_once  "plugins/Aes/aes.class.php";
     $aes = new \Org\Util\Aes();
      $mw = $aes->aes256ecbEncrypt('1380486666',C('TPSKEY'));
      echo "密文".urlencode($mw);
      echo "<hr>";
      echo "解密".$aes->aes256ecbDecrypt($mw,C('TPSKEY'));
 echo "<hr>";
 echo "key".C('TPSKEY');


  }

  public function clear(){
        setcookie('tps','',time()-3600,'/');
    }


  public function ds(){
        ini_set('max_execution_time',0);
       


//        $pay = M('wxp1')->select();
//        foreach ($pay as $v){
//            $order_sn = substr(trim($v[order],'`'),0,18);
////            $money = trim($v[money],'`');
//            $order = M('order')->where("order_sn='{$order_sn}'or master_order_sn='{$order_sn}'")->find();
//           // dump($order);
//           // file_put_contents('/home/checkOrdwx828.txt',$order_sn.">>>".$order[pay_status]."\n",FILE_APPEND);
//           if($order[pay_status]==0){
//            file_put_contents('/home/checkOrd920wh.txt',$order_sn.">>>".$order[pay_status]."\n",FILE_APPEND);
//
//               M('order')->where("order_sn='{$order_sn}'or master_order_sn='{$order_sn}'")->save(['pay_status'=>1,'order_status'=>'0','pay_time'=>strtotime('-1 day'),'pay_name'=>'银联报表维护']);
//            }
//            // if(!$order)dump($order_sn."未找到订单");
//            // if($order[pay_status]===0) {
//            //     update_pay_status($order_sn);
//            //     file_put_contents('/home/checkOrd.txt',$order_sn,FILE_APPEND);
//            // }
//
//
//        }
}
    
}


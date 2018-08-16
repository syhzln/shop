<?php
/**
 * Created by PhpStorm.
 * User: ning[nk11@qq.com]
 * Date: 2017/1/3
 * Time: 18:36
 */
namespace Home\Controller;
use Think\Controller;
class UserapiController extends Controller{

/**
 * 接受推送信息入库
 * @return json
 */
public function adduser()
{//接受tp138的用户信息，录入会员库做信息比对

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
    $store_prefix = I('post.store_prefix');//沃好的店铺前缀
    $store_level = I('post.store_level');//店铺等级
    $create_time = I('post.create_time');//会员注册时间
    $status = I('post.status');//账户状态账户状态：0->未激活，1->激活(正常)，2->月费未支付，3->冻结账户,4->公司预留账户（只有状态为1，2时店铺才开启）
    $token = I('post.token');//验证字符串
    $sign = I('post.sign');//签名。sign=sha1(‘tps’.sha1($token).’!#*’)

    /**
     * 接收数据以后查询本地库存不存在信息，不存在录入信息，存在修改信息
     */
     if (sha1('tps'.sha1($token).'!#*')!=$sign){//判断签名信息是否正确
         $info['error_code'] =101;
         $info['data'] = ['error_info'=>'签名验证失败；'];

     }
     else{

         $usermodel = M('users');
         // $addrmodel = M('user_address');//因为收货地址存储机制不同,出现大量空地址,暂停接收地址数据
         $userinfo = $usermodel->where("tp138_user_id = '{$user_id}'")->find();
         if (!$userinfo){
             // $addrmodel->address = $address;
             // $addrmodel->tp138_user_id = $user_id;
             // $addrid = $addrmodel->add();//先保存地址

             $usermodel->tp138_user_id = $user_id;//tp138用户id
             $usermodel->store_prefix = $store_prefix;//tp138用户id
             $usermodel->email = $email;//用户邮箱
             $usermodel->password = $pwd;//已修改
             $usermodel->pwd_token = $pwd_token;//密码加密token
             $usermodel->token = $token;//用来安全验证的串
             $usermodel->level = $store_level;//会员店铺等级
             $usermodel->first_leader = $parent_id;//推荐人id
             $usermodel->reg_time = $create_time;//会员注册时间
             $usermodel->nickname = $name;//会员名称
             $usermodel->mobile = $mobile;//会员注册手机号
             $usermodel->country_id = $country_id;//国别
             $usermodel->languageid = $languageid;//语种idid
             $usermodel->status = $status;//会员状态
             // $usermodel->address_id = $addrid;//暂停接收以及更新地址信息
             $addid = $usermodel->add();//保存生成主键

             // $sql = "update tp_user_address set user_id = {$addid} where address_id = $addrid";//升级用户地址表的user_id，绑定关联关系
             // $Model = new \Think\Model();
             // $Model->query($sql);

             if($addid){
                 $info['error_code'] =0;
                 $info['data'] = ['succ_info'=>'用户录入成功！'];
             }else{
                 $info['error_code'] =100;
                 $info['data'] = ['error_info'=>'用户录入失败！'];
             }

         }else{
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

            $sql.= " where tp138_user_id ={$user_id} ";


             $Model->query($sql);//升级操作，因为不确定推送字段所以sql用判断拼接形式生成；

             /*if (!empty($address)){//推送修改地址信息
                 $addr = $addrmodel->find($userinfo["address_id"]);
                 if (!$addr) {//因为有前期51.7w条数据未存储地址信息，故在这里新增
                    $addrmodel->address = $address;
                    $addrmodel->tp138_user_id = $user_id;
                    $addid = $addrmodel->add();
                    $sql = "update tp_users set address_id = '{$addid}'";
                    $Model->query($sql);
                 }else{//修改

                    $sql = "update tp_user_address set address='{$address}' where address_id='{$userinfo["address_id"]}'";
                    $Model->query($sql);

                 }

             }*/

             $info['error_code'] =0;
             $info['data'] = ['succ_info'=>'数据已更新'];

         }
     }
     $this->ajaxReturn($info);
     }

    public function ce()
    {
        $this->display('');
    }



    public function tes()
    {

//       echo sha1('!#*'.trim(admin).'133213'.'tps');
//        exit;
//
//        echo sha1('tps'.sha1('wqwwqedasdasfsasfwe').'!#*');
//        exit;
        echo sha1('!#*' . trim('admin') . 'nk11@qq.com' . 'tps');
        exit;
    }

    public function ret(){

       if(!empty($_POST)){
        $data = ['error_code'=>0];
        
       }else{
        $data = ['error_code'=>101];
       }
       return json_encode($data);
        
    }

 public function can_users(){     

        $data = ['0',['succ'=>'测试ing....']];
        $this->ajaxReturn($data,JSON);
        exit;
        $tp138_user_id = I('user_id');
        $res = M('users')->where("tp138_user_id = $tp138_user_id")->setField('is_delete',1);
        if(!$res) {
            $data['error_code'] = 100;
            $data['data'] = ['取消失败!'];            
        }
        $data['error_code'] = 0;
        $data['data'] = ['取消成功!'];
        $this->ajaxReturn($data,JSON);
    }


}

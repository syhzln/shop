<?php
/**
 * walhao公共接口,站内跳过权限调用,站外接口
 * Author: Ning
 * Date: 2017-03-02
 */
namespace Api\Controller;
use Think\Controller;

class ComController extends Controller { 

//ajax 传递的订单备注
    public function texta(){        
        $model=M('orderbz');
        $note=I("post.text");
        $id=I("post.orderid"); 
        $admin_id = empty(session('admin_id')) ? 0 : session('admin_id');       
        $data['otext']=$note;
        $data['order_id']=$id;
        $data['add_time'] = time();
        $data['admin_id'] = $admin_id;
        $a=$model->add($data);
    }


/**
 * 后台修改物流单号
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-29T17:28:55+0800
 */
    public function upInvoice(){
        $invoice_no = I('invoice_no');
        $order_id = I('order_id');
        $res = M('delivery_doc')->where("order_id=$order_id")->setField('invoice_no',$invoice_no);
        if($res) echo 1;
        else echo 0;
    }


/**
 * 快递订阅返回接口,接受返回值,修改订单状态
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-27T10:29:31+0800
 * @return  json
 */
    public function kuaidi(){
        //订阅成功后，收到首次推送信息是在5~10分钟之间，在能被5分钟整除的时间点上，0分..5分..10分..15分....
    $param=$_POST['param'];
    // $param = json_decode($param,true);

    try{
        // file_put_contents('/home/kd.txt',json_encode($_POST)."\n",FILE_APPEND);
        file_put_contents('/home/kd1.txt',$param."\n",FILE_APPEND);
        //$param包含了文档指定的信息，...这里保存您的快递信息,$param的格式与订阅时指定的格式一致
        $param = json_decode($param,true);
        if ($param['status']=='shutdown'){//判断接收状态//监控状态:polling:监控中，shutdown:结束，abort:中止，updateall：重新推送。

           if($param["lastResult"]['state'] == 3||$param["lastResult"]['is_check']== 1){
               //判断是否签收// state 当前签收状态，包括0在途中、1已揽收、2疑难、3已签收、4退签
               //ischeck string  0 是否签收标记
           
              $order_id = M('delivery_doc')->where('invoice_no="'.$param["lastResult"]['nu'].'"')->getField('order_id',true);
              if($order_id){
                $order_id = array_unique($order_id);
                foreach($order_id as $v){
                    confirm_order($v);
                    file_put_contents('/home/kdc.txt',$v."\n",FILE_APPEND);
                }
              }

            }

        }

        echo  '{"result":"true",    "returnCode":"200","message":"成功"}';
        //要返回成功（格式与订阅时指定的格式一致），不返回成功就代表失败，没有这个30分钟以后会重推
    } catch(Exception $e)
        {
        echo  '{"result":"false",   "returnCode":"500","message":"失败"}';
        //保存失败，返回失败信息，30分钟以后会重推
       }
        
    }


/*
 * 接收支付宝返回的信息
 */
    public function recieve(){
 

    //$data = '{"sign":"0b81523b841b5f47a4c82da95fa15895","notify_time":"2017-03-18 17:46:08","pay_user_id":"2088811214203518","pay_user_name":"\u676d\u5dde\u6c83\u597d\u7535\u5b50\u5546\u52a1\u6709\u9650\u516c\u53f8","sign_type":"MD5","success_details":"53383^15921050479^\u674e\u6d77\u5f3a^1.00^S^^20170318575079018^20170318174608|","notify_type":"batch_trans_notify","pay_account_no":"20888112142035180156","notify_id":"b5169ea79b3af4707f1d87467f51d57m0q","batch_no":"2017031853383"}';
    //$data = json_decode($data,true);
    //$_POST = $data;
        //echo "<pre>";
        //var_dump($_POST);
        file_put_contents('/home/ali.txt', 'post'.json_encode($_POST).'get'.json_encode($_GET).date('Y-m-d')."\n",FILE_APPEND);
        require_once './plugins/payment/batchPay/notify_url.php';
    }


/**
 * 测试接口
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T15:31:08+0800
 */
    Public function du(){
      dump($_COOKIE);
      dump($_SESSION);
    }


/**
 * 快递查询,返回到html页面
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-27T10:30:06+0800
 * @return   str
 */
    public function kuaidicx(){

        $nu = I('nu');

        preg_match('/\d+/',$nu,$num);
        $code = $num[0];

        $info = kdcx($code);  
        $str = "<h5>快递单号:$code</h5>";
        foreach(array_reverse($info['data']) as $k=>$v){
            $str .= "<p style='font-size:14px'>".$v['time'].'  '.$v['context'].'</p>';
        }

        echo $str;
    }


/**
 * admin后台商品审核管理,记录操作日志
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T15:32:46+0800
 */
    public function act(){
        $act = I('post.act', '');
        $goods_ids = I('post.goods_ids');
        $goods_state = I('post.goods_state');
        $reason = I('post.reason');

        $return_success = array('status' => 1, 'msg' => '操作成功', 'data' => '');
        if ($act == 'hot') {
            $hot_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($hot_condition)->save(array('is_hot' => 1));
            goodsact($goods_ids,'标记为热卖',$reason);
            $this->ajaxReturn($return_success);
        }
        if ($act == 'recommend') {
            $recommend_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($recommend_condition)->save(array('is_recommend' => 1));
            goodsact($goods_ids,'标记为推荐',$reason);
            $this->ajaxReturn($return_success);
        }
        if ($act == 'new') {
            $new_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($new_condition)->save(array('is_new' => 1));
            goodsact($goods_ids,'标记为新品',$reason);
            $this->ajaxReturn($return_success);
        }
        if ($act = 'examine') {
            $goods_array = explode(',', $goods_ids);
            $goods_state_cg = C('goods_state');
            if (!array_key_exists($goods_state, $goods_state_cg)) {
                $return_success = array('status' => -1, 'msg' => '操作失败，商品没有这种属性', 'data' => '');
                $this->ajaxReturn($return_success);
            }
            foreach ($goods_array as $key => $val) {
                $update_goods_state = M('goods')->where("goods_id = $val")->save(array('goods_state' => $goods_state));
                if ($update_goods_state) {
                    $update_goods = M('goods')->where(array('goods_id' => $val))->find();
                    // 给商家发站内消息 告诉商家商品被批量操作
                    $store_msg = array(
                        'store_id' => $update_goods['store_id'],
                        'content' => "您的商品\"{$update_goods[goods_name]}\"被{$goods_state_cg[$goods_state]},原因:{$reason}",
                        'addtime' => time(),
                        'admin_id' => session('admin_id'),
                        'goods_id'=>$val,
                        
                    );
                    M('store_msg')->add($store_msg);
                }
            }
            $this->ajaxReturn($return_success);
        }
        $return_fail = array('status' => -1, 'msg' => '没有找到该批量操作', 'data' => '');
        $this->ajaxReturn($return_fail);
    } 
  
}

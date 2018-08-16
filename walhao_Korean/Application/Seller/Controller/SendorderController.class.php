<?php
/**
 * Created by PhpStorm.
 * User: ning
 * Date: 2017/1/2
 * Time: 23:07
 *
 * ============================================
 * Up Date:2017/2/9
 * Up Time:13:33
 * 修改推送取值totle_amount(订单价格)为goods_price(订单商品价格)
 */
namespace Home\Controller;
use Think\Controller;
class SendorderController extends Controller{

//订单同步接口－tps138接收沃好的同步
//
//[描述]
//商城订单付款完成后向此接口post订单相关数据
//
//[url]
//http://www.tps138.net/api/wohao/orderSync  (测试)
//http://www.tps138.com/api/wohao/orderSync (正式)
//
//[数据传递方式]
//POST
//[参数]
//参数名   类型  是否必需    描述
//order_id  char    是   订单号
//order_pay_time    timestamp   是   订单付款时间如：2015-07-09 12:12:30
//customer_id   int 否   顾客id。如果没有，则视为游客。
//shopkeeper_id int 否   店主id。如果没有，则代表是公司商城销售的订单。
//order_amount  decimal 是   订单金额（不含运费）。保留2位小数
//order_profit  decimal 是   订单利润。保留2位小数
//currency  char    是   币种。标准3位国际代码，比如USD,CNY
//token int 是   用来安全验证的串。
//sign  string  是   签名。sign=sha1(‘tps’.sha1($token).’!#*’)
//

//[返回结果]
//类型：json
//{
//"error_code ":0,  ##0(成功),其他数值（失败）
//}
    
    public function autoSend(){
    
        
        $sql = "SELECT order_sn,pay_time,goods_price,tp138_user_id from tp_order LEFT JOIN tp_users ON tp_order.user_id = tp_users.user_id where tp_order.pay_status = 1 and is_send = 0";

        $model = new \Think\Model();
        $order = $model->query($sql);     
        if (empty($order)){
            return;
        }
        foreach ($order as $v){

             // $this->sendord($v['order_sn'],$v['pay_time'],$v['tp138_user_id'],$v['total_amount']);
            $response = $this->sendord($v['order_sn'],$v['pay_time'],$v['tp138_user_id'],$v['goods_price']);
        
            if (json_decode($response,true)['error_code']===0){

                $model->query("update tp_order set is_send = 1 where order_sn = '{$v['order_sn']}'");
                $send = M('sendorder');
                $send->tp138_user_id = $v['tp138_user_id'];
                $send->order_sn = $v['order_sn'];
                $send->add();         
            }
        }
        return;        
    }


    
/**
 * 快速推送订单到tps
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-01-10T18:52:39+0800
 * @param     int      $order_sn 订单编号
 * @param     int      $pay_time 时间戳
 * @param     int      $tp138_user_id tps用户id
 * @param     decimal  $total_amount  订单价格
 * @return    json     返回tps处理结果
 */
    public function sendord($order_sn,$pay_time,$tp138_user_id,$total_amount){

        //$url = "http://91ning.com/home/rev/response11";//测试接口地址

        $url = "https://www.tps138.com/api/wohao/orderSync";//接口地址
        $order_pay_time = date('Y-m-d H:i:s',$pay_time);//支付时间
        $order_profit =  round(($total_amount*0.95-$total_amount/1.6/0.9)/2,2);//订单利润。保留2位小数
        $token =md5($tp138_user_id.$order_sn);//用来安全验证的串。
        $sign = sha1('tps'.sha1($token).'!#*');//签名。

        $post_data = array (//推送的数据
            "order_id" => "{$order_sn}",//订单号
            "order_pay_time" => "{$order_pay_time}",//支付时间
            "customer_id" => "{$tp138_user_id}",//会员id
            "shopkeeper_id" => "{$tp138_user_id}",//会员id
            "order_amount" => "{$total_amount}",//订单金额（不含运费）。保留2位小数
            "order_profit" => "{$order_profit}",//订单利润。保留2位小数--四舍五入
            "currency" => "CNY",//币种
            "token" => "{$token}",//用来安全验证的串。
            "sign" => "{$sign}"//签名。
        );
        // $o = "?";
        // foreach ( $post_data as $k => $v )
        // {
        //     $o.= "$k=" . urlencode( $v ). "&" ;
        // }
        // $post_dat = substr($o,0,-1);
              
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1); // post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);// post的变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($ch);
        curl_close($ch);
//        print_r($output);//打印获得的数据
        return $output;//返回POST方式提交返回数据
    }

    public function gettest(){
        echo memory_get_usage();
    }

    public function orderCancle(){
        $url = "https://www.tps138.com/api/wohao/orderCancel";//接口地址
        $res = M('return_goods')->field('id,order_sn')->where('TYPE = 0 and is_send = 0')->LIMIT(1)->SELECT();
        foreach ($res as $k => $v){
            $postdata = [];
            $postdata['token'] =md5(time().$k);//用来安全验证的串。
            $postdata['sign'] = sha1('tps'.sha1($postdata['token']).'!#*');//签名。
            $postdata['order_id'] = $v['order_sn'];
            $response = $this->curlSend($url,$postdata);
            file_put_contents('/web/www/Application/Home/Controller/res.txt', json_encode($postdata).'///////'.$response."======\n");
            if (json_decode($response,true)['error_code']==0){
                M('return_goods')->where('id = '.$v['id'])->setField('is_send',1);
            }
        }
    }

    public function curlSend( $url,$post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1); // post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);// post的变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }




}




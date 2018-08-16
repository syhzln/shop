<?php
/**
 * walhao自动执行任务//通过shell脚本调用
 * Author: Ning
 * Date: 2017-03-28
 */
namespace Api\Controller;
use Think\Controller;

class AutoexeController extends Controller { 


//=============================================订单收货脚本模块==========================================
/**
 * 快递订阅追踪//4小时执行
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-27T10:26:52+0800
 * @return    bool
 */
    public function kuaidires(){    
        $begin = strtotime('-8 hours')-300;
        $end = strtotime('-4 hours');       
        $orders = M('delivery_doc')->field('invoice_no')->where("create_time between $begin and $end")->select();
        if(empty($orders)) exit('没有数据!');       
       
        $i = 0;       
        foreach ($orders as $k=> $v) {
            preg_match("/\d+/",$v['invoice_no'],$matches);
            $code = $matches[0];
            $this->expressSub($code);
            $i++;
        }

        $dd = date('Y-m-d H:i:s')."订阅".$i."条\n";
        exit($dd);             

    }    


/**
 * 执行订阅快递100快递信息
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-27T10:28:45+0800
 * @param   $kd 快递物流单号
 * @return  json
 */
    public function expressSub($kd){

      $url='http://www.kuaidi100.com/poll?';
      
      $post_data = array();
      $post_data["schema"] = 'json' ;
      //callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
        $arr = array(    
            "company"=> "", 
            "number"=> "$kd", 
            "from"=> "", 
            "to"=> "", 
            "key"=> "ETryLWuv4600", 
            "parameters"=>array(
            "callbackurl"=>"http://www.walhao.com/api/com/kuaidi", 
            "salt"=> "", 
            "resultv2"=> "1", 
            "autoCom"=> "1", 
            "interCom"=> "0", 
            "departureCountry"=> "", 
            "departureCom"=> "", 
            "destinationCountry"=> "", 
            "destinationCom"=> ""    
            )
        );
        
       $post_data["param"] = json_encode($arr);
       $o="";
       foreach ($post_data as $k=>$v)
       {
           $o.= "$k=".urlencode($v)."&";     //默认UTF-8编码格式
       }
       $post_data=substr($o,0,-1);
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_POST, 1);
       curl_setopt($ch, CURLOPT_HEADER, 0);
       curl_setopt($ch, CURLOPT_URL,$url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
       $result = curl_exec($ch); //返回提交结果，格式与指定的格式一致（result=true代表成功）
      
    }


/**
 * 测试接口
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T13:24:33+0800
 * @return    [type]                   [description]
 */
    Public function du(){
      dump($_COOKIE);
      dump($_SESSION);
    }



/**
 * 订单自动确认脚本//每天执行
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T09:55:46+0800
 * 
 */
    public function orderComit(){
      $time = strtotime('-7 day');
      $condition['pay_ststus'] = 1;
      $condition['order_status']=1;
      $condition['shipping_status']=1;
      $condition['pay_time'] = ['lt',$time];
      $orderids = M('order')->where($condition)->getField('order_id',true);   
      $i = 0;
      foreach ($orderids as $v ){
        $invoices = M('delivery_doc')->field('invoice_no,create_time')->where("order_id = $v")->find();
           if(empty($invoices)) continue;
           if( $invoices['create_time'] < strtotime('-7 days')) {
                
                file_put_contents('/home/ordercommitsucc.txt',$v.'发货时间:'.date("Y-m-d H:i:s",$invoices['create_time']).'最迟收货时间:'.date("Y-m-d H:i:s",$time)."超时\n",FILE_APPEND);
                confirm_order($v);
                continue;
           }

        preg_match("/\d+/",$invoices['invoice_no'],$matches);
        $res = kdcx($matches[0]);
        
        if($res['state']==3) 
          {
            $i++;
            $confirm_time = $res['data'][0]['time'];
            $time = strtotime($confirm_time);
            M('order')->where("order_id = $v")->setField(['order_status'=>2,'confirm_time'=>$time]);
            file_put_contents('/home/ordercommitsucc.txt', $v."物流自动确认\n",FILE_APPEND); 
          }        
      }
      exit($i);
    }



//============================================订单推送处理功能模块=======================================
/**
 * User: ning
 * Date: 2017/1/2
 * Time: 23:07
 *
 * ====================
 * Up Date:2017/2/9
 * Up Time:13:33
 * 修改推送取值totle_amount(订单价格)为goods_price(订单商品价格)
 * move Date:2017/3/28
 * 整合迁移到单独的脚本执行模块
 */

/**
 * 执行订单系列操作实际调用执行接口
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T13:29:23+0800
 * @return    [type]                   [description]
 */
    public function amatic(){
        $this->autoSend();
        $this->cancleOrder();
        $this->orderCancle();
        $dd = date('Y-m-d H:i:s').time();
        exit($dd);

    }


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
/**
 * 推送订单到tps//10分钟执行
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-28T13:27:51+0800
 * @return    [type]                   [description]
 */
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
 * 快速推送支付订单到tps
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
        $ordertime = date('Ym',$pay_time);

        $post_data = array (//推送的数据
            "order_id" => "{$order_sn}",//订单号
            "order_year_month" => "{$ordertime}",
            "order_pay_time" => "{$order_pay_time}",//支付时间
            "customer_id" => "{$tp138_user_id}",//会员id
            "shopkeeper_id" => "{$tp138_user_id}",//会员id
            "order_amount" => "{$total_amount}",//订单金额（不含运费）。保留2位小数
            "order_profit" => "{$order_profit}",//订单利润。保留2位小数--四舍五入
            "currency" => "CNY",//币种
            "token" => "{$token}",//用来安全验证的串。
            "sign" => "{$sign}"//签名。
        );
        
        return $this->curlSend( $url,$post_data);
    }


/**
 * 推送退货订单到tps//10分钟执行
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-02-24T09:22:26+0800
 * @return  json
 */
    public function orderCancle(){
        $url = "https://www.tps138.com/api/wohao/orderCancel";//接口地址
        $res = M('return_goods')->field('id,order_sn')->where('TYPE = 0 and is_send = 0')->SELECT();
        foreach ($res as $k => $v){
            $postdata = [];
            $postdata['token'] =md5(time().$k);//用来安全验证的串。
            $postdata['sign'] = sha1('tps'.sha1($postdata['token']).'!#*');//签名。
            $postdata['order_id'] = 'W-'.$v['order_sn'];
            $response = $this->curlSend($url,$postdata);
            file_put_contents('/web/www/Application/Home/Controller/res.txt', json_encode($postdata).'///////'.$response."======\n",FILE_APPEND);
            if (json_decode($response,true)['error_code']==0){
                // M('return_goods')->where('order_sn = "'.$v['order_sn'].'"')->setField('is_send',1);
                M('return_goods')->Execute("update tp_return_goods set is_send = 1 where order_sn = '$v[order_sn]'");
            }
        }
    }


/**
 * 执行动作post方式推送订单信息
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-02-24T09:20:49+0800
 * @param   str  $url  接口地址  
 * @param   array  $post_data 发送数据
 * @return  json接口返回信息
 */
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

/**
 * 修改2天前的未支付订单为取消状态//10分钟执行
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-02-24T09:23:46+0800
 * @return bool||str
 */
    public function cancleOrder(){
        $today = time()-12*60*60;//取消12小时之前的订单
        M('order')->where('pay_status = 0 AND add_time<'.$today)->setField('order_status',3);
    }



  
}
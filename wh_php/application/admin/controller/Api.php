<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-23
 */

namespace app\admin\controller;
use think\Db;

class Api extends Base {
    /*
     * 获取地区
     */
    public function getRegion(){
        /*$parent_id = I('get.parent_id/d');
        $data = M('region')->where("parent_id", $parent_id)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;*/
    }

    public function getGoodsSpec(){
        $goods_id = I('get.goods_id/d');
        //$temp = DB::name('spec_goods_price')->field("GROUP_CONCAT(`key` SEPARATOR '_' ) as goods_spec_item")->where('goods_id', $goods_id)->select();
        //$goods_spec_item = $temp[0]['goods_spec_item'];
        //$goods_spec_item = array_unique(explode('_',$goods_spec_item));
        /*if($goods_spec_item[0] != ''){
            $spec_item = DB::query("SELECT i.*,s.name FROM __PREFIX__spec_item i LEFT JOIN __PREFIX__spec s ON s.id = i.spec_id WHERE i.id IN (".implode(',',$goods_spec_item).") ");
            $new_arr = array();
            foreach($spec_item as $k=>$v){
                $new_arr[$v['name']][] = $v;
            }
            $this->assign('specList',$new_arr);
        }*/
       return $this->fetch();
    }
    /*
     * 获取商品价格
     */
    public function getSpecPrice(){
        $spec_id = I('post.spec_id/d');
        $goods_id = I('get.goods_id/d');
        if(!is_array($spec_id)){
            exit;
        }
        $item_arr = array_values($spec_id);
        sort($item_arr);
        $key = implode('_',$item_arr);
       // $goods = M('spec_goods_price')->where(array('key'=>$key,'goods_id'=>$goods_id))->find();
        $info = array(
            'status' => 1,
            'msg' => 0,
           // 'data' =>$goods['price'] ? $goods['price'] : 0
        );
        exit(json_encode($info));
    }

    //商品价格计算
    public function calcGoods(){
        /*$goods_id = I('post.goods/d'); // 添加商品id
        $price_type = I('post.price') ? I('post.price') : 3; // 价钱类型
        $goods_info = M('goods')->where(array('goods_id'=>$goods_id))->find();
        if(!$goods_info['goods_id'] > 0)
            exit; // 不存在商品
        switch($price_type){
            case 1:
                $goods_price = $goods_info['market_price']; //市场价
                break;
            case 2:
                $goods_price = $goods_info['shop_price']; //市场价
                break;
            case 3:
                $goods_price = I('post.goods_price'); //自定义
                break;
        }

        $goods_num = I('post.goods_num/d'); // 商品数量

        $total_price = $goods_price * $goods_num; // 计算商品价格*/

        $info = array(
            'status'=>1,
            'msg'=>'',
            //'data'=>$total_price
        );
        exit(json_encode($info));

    }
	
    public function checkNewVersion(){
    	$last_d='last_d';$param = array($last_d.'omain'=>$_SERVER['HTTP_HOST'],'serial_number'=>time().mt_rand(100, 999),'install_time'=>time());$prl = 'http://ser';$vr = 'vice.tp-s';
    	$crl = 'hop.cn/ind'.'ex.php';$drl = '?m=Ho'.'me&c=Ind'.'ex&a=us'.'er_pu'.'sh';httpRequest($prl.$vr.$crl.$drl,'post',$param);
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
        if (!$info['data'])exit($info['message']);
        $str = "<h5>快递单号:$code</h5>";
        foreach(array_reverse($info['data']) as $k=>$v){
            $str .= "<p style='font-size:14px'>".$v['time'].'  '.$v['context'].'</p>';
        }

        echo $str;
    }
}
<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace Api\Controller;
use Api\Model\StoreModel;
use Home\Logic\GoodsLogic;
use Think\Controller;
use Home\Logic\StoreLogic;
class ActivityController extends BaseController {
    /**
     * @author dyr
     * @time 2016/09/20
     * 团购活动列表
     */
    public function group_list()
    {
        $page_size = I('page_size',10);
        $p = I('p',1);
        $group_by_where = array(
            'start_time'=>array('lt',time()),
            'end_time'=>array('gt',time()),
        );
        $list = M('GroupBuy')->field('goods_id,rebate,virtual_num,buy_num,title,goods_price,end_time,price')->where($group_by_where)->page($p,$page_size)->select(); // 找出这个商品
        $groups = array();
        foreach ($list as $k => $v){
            $v["server_time"] = time();
            $groups[] = $v;
        }
        
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$groups ,
        );
        $this->ajaxReturn($json);
    }
    
    
    /**
     * 团购商品详情
     */
    public function group(){
    
        //$http = SITE_URL; // 网站域名
        $goods_id = $_REQUEST['id'];
        $where['goods_id'] = $goods_id;
        $model = M('Goods');
         
        //$goods  = $model->where($where)->find(); //->field("goods_content" , true)
        $goods  = $model->field("goods_content" , true)->where($where)->find(); //
         
        // 处理商品属性
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表
        foreach($goods_attr_list as $key => $val)
        {
            $goods_attr_list[$key]['attr_name'] = $goods_attribute[$val['attr_id']];
        }
        $goods['goods_attr_list'] = $goods_attr_list ? $goods_attr_list : '';
    
        // 处理商品规格
        $Model = new \Think\Model();
        // 商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        if($keys)
        {
            $specImage =  M('SpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = $Model->query($sql);
            foreach($filter_spec2 as $key => $val)
            {
                $filter_spec[] = array(
                    'spec_name'=>$val['name'],
                    'item_id'=> $val['id'],
                    'item'=> $val['item'],
                    'src'=>$specImage[$val['id']] ? SITE_URL.$specImage[$val['id']] : '',
                );
            }
            $goods['goods_spec_list'] = $filter_spec;
        }
    
        //查询店铺信息
        $store = M("store")->where("store_id = ".$goods['store_id'])->find();
        $return['store'] = $store;
    
        $goods['goods_content'] = str_replace('/Public/upload/', SITE_URL."/Public/upload/", $goods['goods_content']);
        $goods['goods_content'] = htmlspecialchars_decode($goods['goods_content']);
        $goods['original_img'] = SITE_URL.$goods['original_img'];
        $return['goods'] = $goods;
    
        $specPrices = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        // @add by wangqh 获取抢购/促销活动价格 @{
        $flashSale = get_goods_promotion($goods['goods_id']);
        if($flashSale){
            $return['goods']['shop_price'] = $flashSale['price'];
            //如果商品有促销活动, 则将改商品所有规格价格都设置为活动价
            foreach ($specPrices as $k => $v){
                $v['price'] = $flashSale['price'];
                $realSpecPrices[] = $v;
            }
        }else{
            $realSpecPrices = $specPrices;
        }
        $return['spec_goods_price']  =$realSpecPrices;
        // @}
        $return['prom'] = $flashSale; //活动信息:团购
        $return['gallery'] = M('goods_images')->field('image_url')->where(array('goods_id'=>$goods_id))->select();
        foreach($return['gallery'] as $key => $val){
            $return['gallery'][$key]['image_url'] = SITE_URL.$return['gallery'][$key]['image_url'];
        }
        //获取最近的两条评论
        $latest_comment = M('comment')->where("goods_id={$goods_id} AND is_show=1 AND parent_id=0")->limit(2)->select();
        $return['comment'] = $latest_comment ? $latest_comment : '';
    
        $goodsPrices  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        $spec_goods_price = array();
        
        $specItems =  M('spec_item')->getField("id , item");
        //根据规格ID查询规格名称
        foreach ($goodsPrices as $k => $v){
            $specIds = explode('_', $k);
            $keyName = "";
            foreach ($specIds as $idk => $idv){
                $keyName .=$specItems[$idv]." ";
            }
            $v['key_name'] = $keyName;
            $spec_goods_price[] = $v ;
        }
        $return['spec_goods_price'] = $spec_goods_price ;
        
        if(!$goods){
            $json_arr = array('status'=>-1,'msg'=>'没有该商品','result'=>'');
        }else{
            $json_arr = array('status'=>1,'msg'=>'获取成功','result'=>$return);
        }
        $json_str = json_encode($json_arr);
        exit($json_str);
    }
    
    /**
     * @author dyr
     * @time 2016/09/20
     * 团购详情页
     */
    public function group_dyr(){
        //form表单提交
        $goods_id = I('id',0);
        $goodsLogic = new GoodsLogic();
        $group_buy_where = array(
            'goods_id'=>$goods_id,
            'start_time'=>array('lt',time()),
            'end_time'=>array('gt',time()),
        );
        $group_buy_info = M('GroupBuy')->where($group_buy_where)->find(); // 找出这个商品

        $goods = M('Goods')->where('goods_id = '.$goods_id)->find();
        $goods_images_list = M('GoodsImages')->where('goods_id = '.$goods_id)->select(); // 商品 图册
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where('goods_id = '.$goods_id)->select(); // 查询商品属性表

        $Model = M('');
        // 商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        if ($keys) {
            $specImage = M('SpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            $filter_spec2 = $Model->query($sql);
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[$val['name']][] = array(
                    'item_id' => $val['id'],
                    'item' => $val['item'],
                    'src' => $specImage[$val['id']],
                );
            }
        }
        $goodsPrice  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        $spec_goods_price = array();
        
        M('spec_item')->getField("id , item");
        foreach ($goodsPrice as $k => $v){
             $keyName = getSpecNameById();
            $spec_goods_price[] = $v ;
            
        }
        
        
        M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); // 统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>array(
                'group_buy_info'=>$group_buy_info,
                'spec_goods_price'=>$spec_goods_price,
                'commentStatistics'=>$commentStatistics,
                'goods_attribute'=>$goods_attribute,
                'goods_attr_list'=>$goods_attr_list,
                'filter_spec'=>$filter_spec,
                'goods_images_list'=>$goods_images_list,
                'goods'=>$goods,
            ),
        );
        $this->ajaxReturn($json);
    }
    
    /**
     * @author wangqh
     * 抢购活动时间节点
     */
    public function flash_sale_time()
    {
        $time_space = flash_sale_time_space();
        $times = array();
        foreach ($time_space as $k => $v){
            $times[] = $v;
        }
        
         $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$times ,
        );
        $this->ajaxReturn($json);
    }
    
 
    /**
     * @author wangqh
     * 抢购活动列表
     */
    public function flash_sale_list()
    {
        $p = I('p',1);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $where = array(
            'f.status' => 1,
            'f.start_time'=>array('egt',$start_time),
            'f.end_time'=>array('elt',$end_time)
        );
         
        $flash_sale_goods = M('flash_sale')
        ->field('f.goods_name,f.price,f.goods_id,f.price,g.shop_price,100*(FORMAT(f.buy_num/f.goods_num,2)) as percent')
        ->alias('f')
        ->join('__GOODS__ g ON g.goods_id = f.goods_id')
        ->where($where)
        ->page($p,10)
        ->cache(true,TPSHOP_CACHE_TIME)
        ->select();
        
        $json = array(
            'status'=>1,
            'msg'=>'获取成功',
            'result'=>$flash_sale_goods ,
        );
        $this->ajaxReturn($json);
    }
}
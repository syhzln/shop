<?php
namespace app\admin\model;
use think\Model;
class Goods extends Model {
   
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id)
    {            
         // 商品货号
         $goods_sn = "TP".str_pad($goods_id,7,"0",STR_PAD_LEFT);

        //psp.ItemService.SetItemSn
        /* $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录*/
                 
         // 商品图片相册  图册
         $goods_images = I('goods_images/a');
         if(count($goods_images) > 1)
         {                          
             array_pop($goods_images); // 弹出最后一个
             //   获取图片信息  psp.ItemService.GetItemImages
             /*$goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片*/

             // 删除图片  psp.ItemService.DelItemImages
             /*foreach($goodsImagesArr as $key => $val)
             {
                 if(!in_array($val, $goods_images))

                     M('GoodsImages')->where("img_id = {$key}")->delete();
             }*/

             // 添加图片  psp.ItemService.AddItemImages
             /*foreach($goods_images as $key => $val)
             {
                 if($val == null)  continue;                                  
                 if(!in_array($val, $goodsImagesArr))
                 {                 
                        $data = array(
                            'goods_id' => $goods_id,
                            'image_url' => $val,
                        );
                        M("GoodsImages")->insert($data); // 实例化User对象                     
                 }
             }*/
         }
         // 查看主图是否已经存在相册中
         $original_img = I('original_img');
        //psp.ItemService.GetItemImagesCount
        /* $c = M('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count(); */
          
         //@modify by wangqh fix:删除商品详情的图片(相册图刚好是主图时)删除的图片仍然在相册中显示. 如果主图存物理图片存在才添加到相册 @{
         $deal_orignal_img = str_replace('../','',$original_img);
         $deal_orignal_img= trim($deal_orignal_img,'.');
         $deal_orignal_img= trim($deal_orignal_img,'/');
         if($c == 0 && $original_img && file_exists($deal_orignal_img)) //@}
         {
             // 添加图片  psp.ItemService.AddItemImages
            /* M("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img)); */
         }
         delFile(UPLOAD_PATH."goods/thumb/$goods_id"); // 删除缩略图
         
         // 商品规格价钱处理  psp.ItemService.DelSpecGoodsPrice
         /*M("SpecGoodsPrice")->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象  */
         if(I('item/a'))
         {
             /*
              * psp.IteExtraService.GetSpecKey//规格
                psp.IteExtraService.GetSpecItemKey//规格项
             */

             /*$spec = M('Spec')->getField('id,name'); // 规格表
             $specItem = M('SpecItem')->getField('id,item');//规格项*/

             //组装数据
             foreach(I('item/a') as $k => $v)
             {
                   // 批量添加数据
                   $v['price'] = trim($v['price']);
                   $store_count = $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                   $v['sku'] = trim($v['sku']);
                   $dataList[] = ['goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'sku'=>$v['sku']];

                    //修改购物车中的数据 psp.ItemService.UpdateCartPrice
                   /* // 修改商品后购物车的商品价格也修改一下
                    M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
                            'market_price'=>$v['price'], //市场价
                            'goods_price'=>$v['price'], // 本店价
                            'member_goods_price'=>$v['price'], // 会员折扣价                        
                            ));  */
             }
             //向spec_goods_price表添加数据    psp.ItemService.AddSpecGoodsPrice
             /*M("SpecGoodsPrice")->insertAll($dataList);*/
             
         }   
         
         // 商品规格图片处理
         if(I('item_img/a'))
         {
             //删除图片 psp.ItemService.DelSpecImages
             /*M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入*/

             //添加图片  psp.ItemService.AddSpecImages
             /*foreach (I('item_img/a') as $key => $val)
             {                 
                 M('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val));
             }*/
         }
         refresh_stock($goods_id); // 刷新商品库存
    }
}

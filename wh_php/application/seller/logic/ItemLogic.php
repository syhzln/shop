<?php
/**
 * Author: Ning(nk11@qq.com)
 * Date: 2018/1/8 0008
 * Time: 10:19
 */

namespace app\seller\logic;

use Grpc;
Use Psp;
use Symfony\Component\Yaml\Dumper;
use think\Cache;

class ItemLogic {




/**
* @param $provider 店铺_id
* @param $item_id  商品_id
 * @return mixed
* @throws \Exception
 */
    public function checkItemPermission($provider,$item_id)
    {

        $request = new Psp\Itm\ItemProvRequest();
        $request->setProviderId($provider);
        $request->setItemId($item_id);

        list($reply) = GRPC('itm')->checkItemPermission($request)->wait();
        if($reply) return $reply->getValue();
        return false;
    }


    /**
     * 根据商家id获取商家品牌
     * @return mixed "|array
     * @throws \Exception
     *//*ok*/
    public function getBrandList($store_id)
    {
        list($reply) = GRPC('itm')->getBrandList($this->setProviderId($store_id))->wait();
        if(!$reply) return '';
        foreach ($reply->getBrand() as $k=>$v){
            $data[$k]['brand_id'] = $v->getBrandId();
            $data[$k]['name'] = $v->getName();
        }
        return $data;
    }

/**
 * 获取商家允许经营类目
* @param $provider_id 商家id
 * @return  mixed string|array
* @throws \Exception
 *//*ok*/
    public function getProviderCategory($provider_id)
    {
        $data = [];
        list($reply) = GRPC('itm')->getProviderCategory($this->setProviderId($provider_id))->wait();
        if(!empty($reply)){

            $category = $reply->getCategorys();
            if(!$category) return '';
            foreach ($category as $k => $v){
                $data[$k]['id'] = $v->getId();
                $data[$k]['name'] = $v->getName();
            }
            unset($reply);
        }
        return $data;

    }

/**
 * 获取分类联动菜单
* @param $category_id 分类_id
* @param $provider_id 商家_id
* @param $level 获取层级
 * @return mixed
* @throws \Exception
 */
    public function getCategoryByparendId($category_id,$provider_id,$level){
        $request = new Psp\Itm\ProviderGetCategoryByParentIdRequest();
        $request->setProviderId($provider_id);
        $request->setParentId($category_id);
        $request->setLevel($level);

        list($reply) = GRPC('itm')->providerGetCategoryByParentId($request)->wait();

        if($reply){
            foreach ($reply->getCategorys() as $k => $v){
                $data[$k]['id'] = $v->getId();
                $data[$k]['name']= $v->getName();
            }
        }
        unset($reply);
        return $data;
    }

/**
 * 获取分类下绑定的规格项
* @param $category cat_id
 * @return mixed "|array
* @throws \Exception
 *//*ok*/
    public function getSpecByCategory($category)
    {
        list($reply) = GRPC('itm')->getSpecByCategory($this->setCategory($category))->wait();
        $specdata = $reply->getSpec();
        if(!$specdata) return '';
        foreach ($specdata as $k => $v){
            $data[$k]['spec_id'] = $v->getSpecId();
            $data[$k]['spec_name'] = $v->getSpecName();
        }
        return $data;
    }

/**
 * 获取商家自定义规格值
* @param $provider_id
* @param $spec_id
 * @return string
* @throws \Exception
 *//*ok*/
    public function getSpecOption($provider_id,$spec_id)
    {
        $request = new Psp\Itm\GetSpecOptionRequest();
        $request->setProviderId($provider_id);
        $request->setSpecId($spec_id);


        list($reply) = GRPC('itm')->getSpecOption($request)->wait();

        if(empty($reply)) return '';

        $data['spec_id'] = $reply->getSpecId();
        $data['spec_name'] = $reply->getSpecName();

        if($reply->getOptInfo()){
            foreach($reply->getOptInfo() as $k =>$v){
                $data['opt_info'][$v->getOptIdx()] = $v->getOptName();
//
            }
        }
        unset($reply);
        return $data;

    }

/**
 * 添加规格值
* @param $data
 * @return mixed
* @throws \Exception
 */
    public function addSpecOpt($data)
    {
        $request = new Psp\Itm\AddSpecOptRequest();
        $request->setSpecId($data['spec_id']);
        $request->setSpecName($data['spec_name']);
        $request->setProviderId($data['provider_id']);
        $request->setOptName($data['opt_name']);
        unset($data);
        list($reply) = GRPC('itm')->addSpecOpt($request)->wait();
        return($reply->getStatus());

    }

/**
 * 设定分类对象
* @param $category_id
 * @return $this
 */
    public function setCategory($category_id)
    {
     $res = new Psp\Itm\CategoryId();
     return $res->setCategoryId($category_id);
    }

/**
 * 设定商家对象
* @param $provider_id
 * @return $this
 */
    public function setProviderId($provider_id)
    {
        $data = new Psp\Itm\ProviderId();
        return $data->setProviderId($provider_id);
    }

/**
 * 设定商品对象
* @param $item_id
* @return mixed
*/
    public function setItemId($item_id)
    {
        $request = new Psp\Itm\ItemId();

        return $request->setItemId($item_id);
    }

/**
 * 获取通用信息1
* @param $item_id
 * @return array
* @throws \Exception
 *//*ok*/
    public function getItem($item_id)
    {
        $data =[];
        list($reply) = GRPC('itm')->getItem($this->setItemId($item_id))->wait();
        $data = $this->getItemTable($reply->getItem());
        $parent_path = $reply->getParentIdPath();
        $parent_path = explode('_',$parent_path);
        $data['cat_id1'] = $parent_path[0];
        $data['cat_id2'] = $parent_path[1];
        $data['cat_id3'] = $parent_path[2];
        return $data;
    }

/**
 * 获取商品通用信息2 (详情)
* @param $item_id
 * @return array
* @throws \Exception
 *//*ok*/
    public function getItemDetail($item_id)
    {
        list($reply) = GRPC('itm')->getItemDetial($this->setItemId($item_id))->wait();
        return $this->itemDetail($reply);

    }
/**
 * 获取通用信息3 (图片)
* @param $item_id
 * @return array
* @throws \Exception
 *//*ok*/
    public function getItemImg($item_id)
    {
        list($reply) = GRPC('itm')->getItemImg($this->setItemId($item_id))->wait();
        return $this->itemImg($reply);

    }

/**
 * 获取通用信息4 (价格库存sku复合信息)
* @param $item_id
 * @return array
* @throws \Exception
 */
    public function getSKUPrice($item_id)
    {
        list($reply) = GRPC('itm')->getSKUPrice($this->setItemId($item_id))->wait();
        return $this->SKU_Price_Stock($reply);

    }

    /**
     * 获取通用信息4 (物流模板信息)
     * @param $item_id
     * @return array
     * @throws \Exception
     */
    public function getDeliveryTemp($provider_id)
    {
        //获取物流模板列表
        $provider = new Psp\Itm\ProvId();
        $areas = new \area\area();
        $area = $areas->getProv(); //获取所有省
        $page = grpcPage('temp_id',1,100,false);
        $provider->setPagination($page);
        $provider->setProviderId($provider_id);
        list($res,$status) = GRPC(Itm)->GetTempList($provider)->wait();
        if($res){
            foreach ($res->getTempList() as $k=>$v) {
                $data[$k]['temp_id'] = $v->getTempId();
                $data[$k]['name'] = $v->getName();
                $data[$k]['method'] = $v->getMethod();
                $data[$k]['cost'] = $v->getCost();
                $data[$k]['use_count'] = $v->getUseCount();
                $data[$k]['is_default'] = $v->getIsDefault();
                $data[$k]['is_close'] = $v->getIsClose();
                $data[$k]['create_time'] = $v->getCreateDate()->getSeconds();
                $data[$k]['config'] = $v->getConfig()?unserialize($v->getConfig()):'';
                $data[$k]['provider_id'] = $v->getProviderId();
                $data[$k]['area'] = $v->getAreas();
                foreach (explode(',',$data[$k]['area']) as $kk=>$vv){//拼接物流模板使用范围的字符串
                    foreach ($area as $k2 => $v2){
                        if($vv.'0000' == $k2){
                            $data[$k]['areas'].=$v2.',';
                        }
                    }
                }
            }
            foreach ($data as $k=>$v){
                if($v['provider_id']==0){
                    $admin_data[$k] = $v;
                }else{
                    $seller_data[$k] = $v;
                }
            }
            return $seller_data;
        }

        return;
    }



    /**
* 解析item对象
* @param $obj item_obj
* @return array
*/
    public function getItemTable($v)
    {

        $item = [];
        $item['item_id'] = $v->getItemId();
        $item['title'] = $v->getTitle();
        $item['page_url'] = $v->getPageUrl();
        $item['provider_id'] = $v->getProviderId();
        $item['category_id'] = $v->getCategoryId();
        $item['brand_id'] = $v->getBrandId();
        $item['comment_count'] = $v->getCommentCount();
        $item['state'] = $v->getState();
        $item['onsale_time'] = empty($v->getOnsaleTime())?'':$v->getOnsaleTime()->getSeconds();
        $item['offsale_time'] =empty($v->getOffsaleTime())?'':$v->getOffsaleTime()->getSeconds();
        $item['logistics'] = $v->getLogistics();
        $item['is_free_shipping'] = $v->getIsFreeShipping();
        $item['weight'] = $v->getWeight();
        $item['index_img'] = $v->getIndexImg();
        $item['platform'] = $v->getPlatform();
        $item['language'] = $v->getLanguage();
        unset($v);
        return($item);
    }


/**
 * item详情对象解析
* @param $obj itemDetailObj
 * @return array
 */
    public function itemDetail($v){

        $itemDetail = [];
        if($v){
            $itemDetail['tiem_id'] = $v->getItemId();
            $itemDetail['keywords'] = $v->getKeywords();
            $itemDetail['remark'] = $v->getRemark();
            $itemDetail['producer'] = $v->getProducer();
            $itemDetail['original'] = $v->getOriginal();
            $itemDetail['item_content'] = $v->getItemContent();
            $itemDetail['flags'] = $v->getFlags();

            unset($v);
        }

        return $itemDetail;

    }

/**
 * 解析图片列表
* @param $obj img_obj
 * @return array
 */
    public function itemImg($obj)
    {
        $itemImg = [];
        foreach($obj->getItemImg() as $k => $v){
            $itemImg[$k]['id'] = $v->getId();
            $itemImg[$k]['item_id'] = $v->getItemId();
            $itemImg[$k]['sku_id'] = $v->getSkuId();
            $itemImg[$k]['url'] = $v->getImgUrl();
        }
        unset($obj);
        return $itemImg;
    }

/**
 * 解析价格库存sku为二维数组
* @param $obj MIX_OBJ
 * @return array
 */
    public function SKU_Price_Stock($obj)
    {
        $data = [];
        foreach ($obj->getInfo() as $k =>$v)
            {
                $data[$k]['old_sku'] = $v->getOldSku();
                $data[$k]['provider_shop_price'] = sprintf("%.2f",$v->getProviderShopPrice());
                $data[$k]['provider_price'] = sprintf("%.2f",$v->getProviderPrice());
                $data[$k]['market_price'] = sprintf("%.2f",$v->getMarketPrice());
                $data[$k]['stock'] = $v->getStock();
                $data[$k]['warning_level'] = $v->getWarningLevel();
                $data[$k]['sku_name'] = $v->getSkuName();
                $data[$k]['specs'] = $v->getSpecs();
                $data[$k]['spec1'] = $v->getSpec1();
                $data[$k]['spec2'] = $v->getSpec2();
                $data[$k]['spec3'] = $v->getSpec3();
                $data[$k]['spec4'] = $v->getSpec4();
                $data[$k]['spec5'] = $v->getSpec5();
                $data[$k]['sku_id'] = $v->getSkuId();

            }
        unset($obj);
        return $data;

    }

/**
 * 解析商品管理列表对象数据
* @param $obj
 * @return array
 */
    public function itemInfo($obj){
        $data = [];
        $data['item_id'] = $obj->getItemId();
        $data['title'] = $obj->getTitle();
        $data['category_name'] = $obj->getCategoryName();
        $data['is_on_sale'] = $obj->getIsOnSale();
        foreach ($obj->getPrice() as $k =>$v){
            $data['price'][$k]['sku_id'] = $v->getSkuId();
            $data['price'][$k]['old_sku'] = $v->getOldSku();
            $data['price'][$k]['sku_name'] = $v->getSkuName();
            $data['price'][$k]['provider_shop_price'] = $v->getProviderShopPrice();
            $data['price'][$k]['stock'] = $v->getStock();
            $data['price'][$k]['warning_level'] = $v->getWarningLevel();
        }

        return $data;


    }


     /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr, $store_id = 0)
    {

        // 排序
        if($spec_arr&&!array_key_exists(0,$spec_arr)){


        foreach ($spec_arr as $k => $v)
        {
            $spec_arr_sort[$k] = count($v);

        }

        asort($spec_arr_sort);
        foreach ($spec_arr_sort as $key =>$val)
        {
            $spec_arr2[$key] = $spec_arr[$key];
        }

         $clo_name = array_keys($spec_arr2);

         $specs = implode(',',$clo_name );
         $spec_arr2 = combineDika($spec_arr2); //  获取 规格的 笛卡尔积

            static $optdatas = [];
            foreach ($clo_name as $v){
                $info = $this->getSpecOption($store_id,$v);
                $specdata[$v] = $info['spec_id'];
                $specdata[$v] = $info['spec_name'];
                 foreach($info['opt_info'] as $key=> $val){
                     $optdatas[$key]['spec_id'] = $info['spec_id'];
                     $optdatas[$key]['spec_name'] = $info['spec_name'];
                     $optdatas[$key]['opt_id'] = $key;
                     $optdatas[$key]['opt_name'] = $val;
                 };
                unset($info);
            }

        }else{
            $spec_arr2 = [['0000']];
            $specdata=['0000'=>''];
            $optdatas = ['0000' =>
              ['spec_id' => 0,
              'spec_name' => '' ,
              'opt_id' => '0000',
              'opt_name' => '默认']];

        }
        if($goods_id>0){

            $price = $this->getSKUPrice($goods_id);
            foreach($price as $v){
                $pricedata[$v['old_sku']] = $v;
            }
        }




       $str = "<table class='table table-bordered' id='spec_input_tab'>";
       $str .="<tr>";
       // 显示第一行的数据

       foreach ($specdata as $k => $v)
       {
           $str .=" <td><b>{$v}</b></td>";
       }
        $str .="
               <td><b>售价</b>&nbsp;&nbsp;(平台销售价格)</td>
               <td><b>成本</b>&nbsp;&nbsp;(合同供货价)</td>
               <td><b>市场价</b>&nbsp;&nbsp;(参考标准价)</td>
               <td><b>库存</b></td>
               <td><b>库存预警数</b>&nbsp;&nbsp;(少于多少件预警提示)</td>
             </tr>";
       // 显示第二行开始
       foreach ($spec_arr2 as $k => $v)
       {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$optdatas[$v2]['opt_name']}</td>";


                $item_key_name[$v2] = $optdatas[$v2]['spec_name'].':'.$optdatas[$v2]['opt_name'];
            }
            ksort($item_key_name);

            $item_key_name_key = array_keys($item_key_name);


            $item_key = implode('_', $item_key_name_key);
            $item_name = implode(' ', $item_key_name);

            $pricedata[$item_key][provider_shop_price] ? false : $pricedata[$item_key][provider_shop_price] = 0; // 价格默认为0
            $pricedata[$item_key][provider_price] ? false : $pricedata[$item_key][provider_price] = 0; // 价格默认为0
            $pricedata[$item_key][market_price] ? false : $pricedata[$item_key][market_price] = 0; // 价格默认为0
            $pricedata[$item_key][stock] ? false : $pricedata[$item_key][stock] = 0; //库存默认为0
            $pricedata[$item_key][warning_level] ? false : $pricedata[$item_key][warning_level] = 0; //库存默认为0
            $pricedata[$item_key][sku_id] ? false : $pricedata[$item_key][sku_id] = 0; //库存默认为0


            $str .="<td><input name='item[$item_key][provider_shop_price]' value='{$pricedata[$item_key][provider_shop_price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][provider_price]' value='{$pricedata[$item_key][provider_price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][market_price]' value='{$pricedata[$item_key][market_price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][stock]' value='{$pricedata[$item_key][stock]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";
            $str .="<td><input name='item[$item_key][warning_level]' value='{$pricedata[$item_key][warning_level]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="<input type='hidden' name='item[$item_key][specs]' value='$specs' />";
            $str .="<input type='hidden' name='item[$item_key][sku_id]' value='{$pricedata[$item_key]['sku_id']}' />";
            for($i = 0;$i<count($item_key_name_key);$i++){

                $str .="<input type='hidden' name='item[$item_key][spec$i]' value='$item_key_name_key[$i]' />";
            }
            $str .="</tr>";
       }
        $str .= "</table>";
       return $str;
    }

/**
 * 获取商家规格值
* @param $provider_id
* @param $spec_id
 * @return array
* @throws \Exception
 */
    public function getProviderSpecOption($provider_id,$spec_id){
        $request = new Psp\Itm\GetSpecOptionRequest();
        $request->setProviderId($provider_id);
        $request->setSpecId($spec_id);
        $data = [];
        list($reply) = GRPC('itm')->getProviderSpecOption($request)->wait();
        if($reply&&$reply->getOptInfo()){

            foreach($reply->getOptInfo() as $k =>$v){
                $data[$v->getOptIdx()] = $v->getOptName();

            }

        }
        return $data;
    }

    /**
 * 获取商家所有绑定类目(以三级类目为节点)
* @param $provider_id
 * @return array
* @throws \Exception
 */
    public function getProviderAllowCategoryList($provider_id){

        list($reply) = GRPC('itm')->getProviderAllowCategoryList($this->setProviderId($provider_id))->wait();

        $data = [];
        if($reply){
            foreach ($reply->getCategorys() as $k =>$v){
                $data[$k]['bid'] = $v->getBid();
                $data[$k]['class_1'] = $v->getClass1();
                $data[$k]['class_2'] = $v->getClass2();
                $data[$k]['class_3'] = $v->getClass3();
                $data[$k]['class_1_name'] = $v->getClass1Name();
                $data[$k]['class_2_name'] = $v->getClass2Name();
                $data[$k]['class_3_name'] = $v->getClass3Name();
                $data[$k]['state'] = $v->getState();
            }
        }
        unset($reply);
        return $data;
    }


    /**添加规格项
* @param $category_id
* @param $name
* @throws \Exception
 */
    public function addSpec($category_id,$name){
        $request = new Psp\Itm\AddSpecRequest();
        $request->setCategoryId($category_id);
        $request->setSpec($name);

        list($reply) = GRPC('itm')->addSpec($request)->wait();


    }

/**
 * 通过父id查找子类
* @param $id
 * @return array
* @throws \Exception
 */
    public function getCategoryByParentId($id){

        $request = new Psp\Itm\GetCategoryByParentIdRequest();
        $request->setPlatform(PLATFORM);
        $request->setLanguage(1);
        $request->setParentId($id);

        //todo 接口未通
        $data = [];
        list($reply) = GRPC('itm')->getCategoryByParentId($request)->wait();

        if($reply){
            foreach ($reply->getCategorys() as $k => $v){
                $data[$k]['id'] = $v->getId();
                $data[$k]['name']= $v->getName();
            }
        }
        unset($reply);
        return $data;
    }

    public function  addProviderAllowCategory(){

        $request = new Psp\Itm\ProviderAllowCategory();
        $request->setProviderId($provider);
        $request->setClass1($cat_1);
        $request->setClass2($cat_2);
        $request->setClass3($cat_3);
        $request->setState(0);
        $result = '';
        list($reply) = GRPC('itm')->addProviderAllowCategory($request)->wait();
        if($reply){
            return $result = $reply->getBoolValue();
        }
    }


    //根据商品id获取商品操作记录
    public function  getItemAction($item_id){
        $value = new Psp\UInt32Value();
        $value->setValue($item_id);
        list($reply) = GRPC('itm')->GetItmAction($value)->wait();
        if($reply){
            foreach ($reply->getActionList() as $k=>$v){
                $action[$k]['id'] = $v->getId();
                $action[$k]['provider_id'] = $v->getProviderId();
                $action[$k]['item_id'] = $v->getItemId();
                $action[$k]['msg'] = $v->getMsg();
                $action[$k]['admin_id'] = $v->getAdminId();
                $action[$k]['create_time'] = $v->getCreateDate()?$v->getCreateDate()->getSeconds():0;
            }
        }
        return $action;
    }

}
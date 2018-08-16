<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yxc
 * Date: 2017-10-20
 */
namespace app\seller\controller;
use app\seller\logic\GoodsLogic;
use app\seller\logic\ItemLogic;
use app\seller\logic\SearchWordLogic;
use think\AjaxPage;
use think\Loader;
use think\Page;
use think\Db;
use Psp;
use Grpc;

class Goods extends Base {

    public function __construct() {
        parent::__construct();
        if(STORE_ID==0) $this->success('登录已失效,请重新登陆',U('/Seller/Admin/login'));
    }

    public function stock_list(){
        $stime = strtotime(I('stime',''));
        $etime = strtotime(I('etime',''));
        $type = I('mtype/d',0);
        $item_name = I('item_name','');
        $p = I('p/d',1);
        $request = new Psp\Itm\GetStockLogRequest();
        $request->setType($type);
        empty(!$stime)&&$request->setMinDate(grpcTime($stime));
        empty(!$etime)&&$request->setMaxDate(grpcTime($etime));
        $request->setProviderId(STORE_ID);
        $request->setTitle($item_name);

        $request->setPagination(grpcPage('id',$p,18));

        list($reply) = GRPC('itm')->getStockLog($request)->wait();

        if(!empty($reply)){
            foreach ($reply->getStocks() as $k =>$v){
                $data[$k]['title'] = $v->getTitle();
                $data[$k]['sku_name'] = $v->getSkuName();
                $data[$k]['order_sn'] = $v->getOrderSn();
                $data[$k]['stock'] = $v->getStock();
                $data[$k]['user_id'] = $v->getUserId();
                $data[$k]['create_time'] = empty($v->getCreateTime())?0:$v->getCreateTime()->getSeconds();

            }
            $total_count =$reply->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,20);
            $show =$Page->show();
        }
        //dump($data);die;
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('stock_list',$data);
        return $this->fetch();
    }
/**
 * 商家规格属性列表
* @return mixed
* @throws \Exception
 */
    public function specList(){
        $logic = new ItemLogic();
        $category = $logic->getProviderAllowCategoryList(STORE_ID);
        static $data = [];
        foreach ($category as $k=>$v){
            $spec=$logic->getSpecByCategory($v['class_3']);
            if(is_array($spec) && !empty($spec)){
                foreach ($spec as $k1=>$v1){
                   $data[] = array_merge($v,$v1);
                }
            }
        }

         $this->assign('specList',$data);

        return $this->fetch();
    }

    public function ajaxSpecList(){
        return $this->fetch();

    }

    /**添加规格项
* @return mixed
* @throws \Exception
 */
    public function addEditSpec(){

        $itemLogic = new ItemLogic();
        $categorys = $itemLogic->getProviderCategory(STORE_ID);

        $this->assign('cat_list',$categorys);
        if(IS_POST){
            $itemLogic->addSpec(I('cat_id3/d'),I('name/s'));
            $this->success('添加成功');
        }

        return $this->fetch('_spec');
    }

    public function brandList(){
        $p = I('p/d',1);
        $keyword = I('keyword');
        $request = new \Psp\Itm\BrandListRequest();
        $keyword&&$request->setName($keyword);
        $request->setProviderId(STORE_ID);
        $pagination = grpcPage('id',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getAdminBrandList($request)->wait();
        if($res) {
            foreach ($res->getBrandList() as $k => $v) {
                $brandList[$k]['brand_id'] = $v->getBrandId();
                $brandList[$k]['brand_name'] = $v->getBrandName();
                $brandList[$k]['logo'] = $v->getLogo();
                $brandList[$k]['desc'] = $v->getDesc();
                $brandList[$k]['url'] = $v->getUrl();
                $brandList[$k]['cat1'] = $v->getCat1();
                $brandList[$k]['cat2'] = $v->getCat2();
                $brandList[$k]['cat3'] = $v->getCat3();
                $brandList[$k]['cat1_name'] = $v->getCat1Name();
                $brandList[$k]['cat2_name'] = $v->getCat2Name();
                $brandList[$k]['cat3_name'] = $v->getCat3Name();
                $brandList[$k]['is_hot'] = $v->getIsHot();
                $brandList[$k]['status'] = $v->getStatus();
                $brandList[$k]['sort'] = $v->getSort();
            }

            $total_count =$res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,20);
            $show =$Page->show();

        }
        //dump($brandList);die;
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('keyword',$keyword);
        $this->assign('brandList',$brandList);
        return $this->fetch('brandList');
    }

    public function addEditBrand(){

        $id = I('id/d');
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->getCate();
        if(IS_POST)
        {
            $data = I('post.');
            if($id){//修改
                $update =new \Psp\Itm\UpdateBrandInfo();
                $update->setBrandId($id);
                $update->setBrandName($data['name']);
                $update->setImgUrl($data['logo']);
                $update->setBrandUrl($data['url']);
                $update->setCate1((int)$data['parent_cat_id']);
                $update->setCate2((int)$data['cat_id']);
                $update->setCate3((int)$data['cat_id2']);
                $update->setSort((int)$data['sort']);
                $update->setDesc($data['desc']);
                list($res,$status) =GRPC(itm)->UpdateBrand($update)->wait();
                if($res->getValue()){
                    $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','result'=>'']);
                }
                //M("Brand")->update($data);

            }else{//添加
                $brand_info = new \Psp\Itm\Brand();
                $brand_info->setName($data['name']);
                $brand_info->setImgUrl($data['logo']);
                $brand_info->setBrandUrl($data['url']);
                $brand_info->setSort((int)$data['sort']);
                $brand_info->setDesc($data['desc']);
                $brand_info->setCate1((int)$data['parent_cat_id']);
                $brand_info->setCate2((int)$data['cat_id']);
                $brand_info->setCate3((int)$data['cat_id2']);
                $brand_info->setProviderId(STORE_ID);
                list($res,$status)=GRPC(itm)->AddBrand($brand_info)->wait();
                if($res->getValue()){
                    $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','result'=>'']);
                }

            }

        }
        if($id){
            $brand = $GoodsLogic->getBrandInfoByBrandId($id);
            if($brand['cate1']){ //获取二级分类
                $cate_list2 = $GoodsLogic->getCategoryByParentId($brand['cate1']);
            }
            if($brand['cate2']){//获取三级分类
                $cate_list3 = $GoodsLogic->getCategoryByParentId($brand['cate2']);
            }
            $this->assign('cat_list2',$cate_list2);
            $this->assign('cat_list3',$cate_list3);
            $this->assign('brand',$brand);
        }

        $this->assign('cat_list',$cat_list);
        return $this->fetch('_brand');
    }

    public function categoryList(){
        //todo 分类列表
        $logic = new ItemLogic();
        $catlist = $logic->getProviderAllowCategoryList(STORE_ID);
//        dump($catlist);

        return $this->fetch();
    }


        /**
     *  商品列表
     */
    public function goodsList()
    {
        $logic = new ItemLogic();
        $catlist = $logic->getProviderCategory(STORE_ID);
        $brandList = $logic->getBrandList(STORE_ID);
        $this->assign('categoryList',$catlist);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }


    /**
    *  商品列表
    *
    */
    public function ajaxGoodsList()
    {

        $is_on_sale = I('is_on_sale/d',-1);       //是否在售状态 筛选 0,1
        $keywords = I('keywords');            //搜索关键字,title like搜索
        $state = I('state/d',1); //状态  <必传> 1审核通过, 0 待审核,2审核失败,3违规下架 (此处调用只有2种传参==1 或 != 1)
        $special = I('special/d',-1);
        $p = I('p/d',1);
        $request = new \Psp\Itm\GetItemListRequest();
        $request->setProviderId(STORE_ID);
        $request->setState($state);
        $request->setIsOnSale($is_on_sale);
        $request->setKeywords($keywords);
        $request->setSpecial($special);


        $pagination = grpcPage('item_id',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getItemList($request)->wait();

        if($res){
            foreach ($res->getItemList() as $k=>$v){
                $item_list[$k]['item_id'] = $v->getItemId();
                $item_list[$k]['title'] = $v->getTitle();
                $item_list[$k]['category_name'] = $v->getCategoryName(); //获取完整分类数 ps 一级分类//二级分类//三级分类 (请根据商品category_id查询category表parent_id_path获取完整路径值)
                $item_list[$k]['is_on_sale'] = $v->getIsOnSale();
                foreach ($v->getPrice() as $kk=>$vv){
                    $item_list[$k]['price'][$kk]['old_sku'] = $vv->getOldSku();
                    $item_list[$k]['price'][$kk]['provider_shop_price'] = round($vv->getProviderShopPrice(),2);
                    $item_list[$k]['price'][$kk]['provider_price'] = round($vv->getProviderPrice(),2);
                    $item_list[$k]['price'][$kk]['market_price'] = round($vv->getMarketPrice(),2);
                    $item_list[$k]['price'][$kk]['stock'] = $vv->getStock();
                    $item_list[$k]['price'][$kk]['warning_level'] = $vv->getWarningLevel();
                    $item_list[$k]['price'][$kk]['sku_name'] = $vv->getSkuName();
                    $item_list[$k]['price'][$kk]['specs'] = $vv->getSpecs();
                    $item_list[$k]['price'][$kk]['spec1'] = $vv->getSpec1();
                    $item_list[$k]['price'][$kk]['spec2'] = $vv->getSpec2();
                    $item_list[$k]['price'][$kk]['spec3'] = $vv->getSpec3();
                    $item_list[$k]['price'][$kk]['spec4'] = $vv->getSpec4();
                    $item_list[$k]['price'][$kk]['spec5'] = $vv->getSpec5();
                    $item_list[$k]['price'][$kk]['sku_id'] = $vv->getSkuId();
                }

            }

                $total_count =$res->getPaginationResult()->getTotalRecords();
                $Page = new AjaxPage($total_count,20);
                $show =$Page->show();

            }

            $this->assign('goodsList',$item_list);
//            $this->assign('catList',$catList);
            $this->assign('page',$show);// 赋值分页输出
            return $this->fetch();

        }

    public function addEditGoods(){

        $itemLogic = new ItemLogic();

        $item_id = I('id/d',0);

        if(IS_POST && $_GET['is_ajax']==1){
            C('TOKEN_ON',false);
            $data = I('post.');

            empty($data['goods_id'])?$data['goods_id']=0:$data['goods_id'];
            $addRequest = new Psp\Itm\AddItemRequest();

            $itemTable = new Psp\Itm\ItemTable();
            $itemTable->setItemId($data['goods_id']);//主键
            $itemTable->setTitle($data['title']);
            $itemTable->setProviderId(STORE_ID);
            $itemTable->setCategoryId($data['cat_id3']);
            $itemTable->setBrandId($data['brand_id']);
            $itemTable->setIndexImg($data['original_img']);
            $itemTable->setWeight($data['weight']);
            $itemTable->setPlatform(PLATFORM);
            $itemTable->setIsFreeShipping($data['is_free_shipping']);
            //todo 暂定语种为1,根据实际去cookie,或直接传值
            $itemTable->setLanguage(1);
            $itemTable->setState(0);
            $itemTable->setOnsaleTime(grpcTime(time()));
            //物流模板
            $logictics = $data['temp_id']?implode(',',$data['temp_id']):'';
            $itemTable->setLogistics($logictics);


            $itemDetail = new Psp\Itm\ItemDetial();
            $itemDetail->setItemId($data['goods_id']);
            $itemDetail->setKeywords($data['keywords']);
            $itemDetail->setRemark($data['remark']);
            $itemDetail->setOriginal($data['original']);
            $itemDetail->setProducer($data['producer']);
            $itemDetail->setItemContent($data['goods_content']);
            $itemDetail->setFlags(0);
            $imgdata = [];

            foreach ($data['goods_images'] as $k=>$v){
                if(empty($v)) continue;
                $itemImg = new Psp\Itm\ItemImg();
                $imgdata[$k] = $itemImg->setItemId($data['goods_id']);
                $imgdata[$k] = $itemImg->setImgUrl($v);

            }

            $pricedata = [];
            if($data['item']){
                foreach($data['item'] as $k => $v){
                    if(empty($v)) continue;
                    $skuPriceStock = new Psp\Itm\SKUPriceStock();
                    $pricedata[$k] = $skuPriceStock->setOldSku($k);
                    $pricedata[$k] = $skuPriceStock->setProviderPrice($v['provider_price']);
                    $pricedata[$k] = $skuPriceStock->setProviderShopPrice($v['provider_shop_price']);
                    $pricedata[$k] = $skuPriceStock->setMarketPrice($v['market_price']);
                    $pricedata[$k] = $skuPriceStock->setStock($v['stock']);
                    $pricedata[$k] = $skuPriceStock->setWarningLevel($v['warning_level']);
                    $pricedata[$k] = $skuPriceStock->setSpecs($v['specs']);
                    $pricedata[$k] = $skuPriceStock->setSpec1(empty($v['spec0'])?0:$v['spec0']);
                    $pricedata[$k] = $skuPriceStock->setSpec2(empty($v['spec1'])?0:$v['spec1']);
                    $pricedata[$k] = $skuPriceStock->setSpec3(empty($v['spec2'])?0:$v['spec2']);
                    $pricedata[$k] = $skuPriceStock->setSpec4(empty($v['spec3'])?0:$v['spec3']);
                    $pricedata[$k] = $skuPriceStock->setSpec5(empty($v['spec4'])?0:$v['spec4']);
                    $pricedata[$k] = $skuPriceStock->setSkuName(empty($v['key_name'])?'':$v['key_name']);
                    $pricedata[$k] = $skuPriceStock->setSkuId($v['sku_id']);

                }
            }


            $addRequest->setItem($itemTable);
            $addRequest->setItemdetail($itemDetail);
            $addRequest->setItemimg($imgdata);
            $addRequest->setInfo($pricedata);


            list($reply) = GRPC('itm')->addItem($addRequest)->wait();

            if($reply){
                if($reply->getItemId()>0)  $this->ajaxReturn(['status' => 1,'msg'   => '操作成功','data'  => array('url'=>U('Goods/goodsList'))]);

            }else{
                $this->ajaxReturn(['status' => 0,'msg'   => '操作失败','data'  => array('url'=>U('Goods/goodsList'))]);

            }

        }


        if($item_id > 0){
            // 判断此商品是否属于该商家
            $permise = $itemLogic->checkItemPermission(STORE_ID,$item_id);
            (!$permise) && $this->error("非法操作",U('Goods/goodsList'));

            //获取商品信息
            $item = $itemLogic->getItem($item_id);
            $itemdetail = $itemLogic->getItemDetail($item_id);
            $itemimg = $itemLogic->getItemImg($item_id);
            $itemaction = $itemLogic->getItemAction($item_id);
        }
        $delivery_temp = $itemLogic->getDeliveryTemp(STORE_ID); //获取商家物流模板
        $categorys = $itemLogic->getProviderCategory(STORE_ID);
        $brand = $itemLogic->getBrandList(STORE_ID);
        $this->initEditor(); // 编辑器
        $this->assign('cat_list',$categorys);
        $this->assign('brandList',$brand);
        $this->assign('iteminfo',$item);
        $this->assign('itemdetail',$itemdetail);
        $this->assign('itemimg',$itemimg);
        $this->assign('itemaction',$itemaction);
        $this->assign('plugin_shipping',$delivery_temp);
        return $this->fetch('_goods');
    }


    /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", U('admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }


    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        $this->error('对不起,您无权执行此项操作,详询平台管理员!');


    }


/**
 * 添加规格项
* @throws \Exception
 */
    public function addSpecOpt(){

        $data = $_POST;
        $data['provider_id'] = STORE_ID;
        $logic = new ItemLogic();
        $replydata = $logic->addSpecOpt($data);
        if($replydata =='true'){
            $return_arr = array(
            'status' => 1,
            'msg'   => '添加成功!',
            'data'  =>'',
         );
        }else{
             $return_arr = array(
                'status' => -1,
                'msg'   => '规格已经存在',
                'data'  =>'',
             );

        }
        $this->ajaxReturn($replydata);


    }


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = I('goods_id',0);
        $cat_id3 = I('cat_id3',0);
        empty($cat_id3) && exit('');
        static $specList = [];
        $logic = new ItemLogic();
        $specList = $logic->getSpecByCategory($cat_id3);

        if($specList){
            foreach($specList as $k => $v){
                $specList[$k]['spec_opt'] = $logic->getProviderSpecOption(STORE_ID,$v['spec_id']);
            }
        }

        $specList[] = ['spec_id' => 0,'spec_name'=>'','spec_opt'=>['0000' =>'默认']];//设定无规格选项

        krsort($specList,2);
        $pricedata = $logic->getSKUPrice($goods_id);
        foreach($pricedata as $k=>$v){
            $opt_idx = explode('_', $v['old_sku']);
            foreach ($opt_idx as $v){
                $opt_idxs[] = $v;
            }
        }

        $opt_idxs = empty($opt_idxs)?[]:array_unique($opt_idxs);

//todo 规格项图片列表
//        $this->assign('specImageList',$specImageList);
        $this->assign('items_ids',$opt_idxs);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
         $goods_id = I('goods_id',0);
         $spec_arr = $_POST['spec_arr'];//绝对不能用I,会将数组转换的妈都不认识
         $logic = new ItemLogic();
         $data= $logic->getSpecInput($goods_id,$spec_arr,STORE_ID);
         exit($data);

    }

    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        //todo 未完成
        $img_url = I('filename');
        $img_id = I('img_id');
        if($img_id){
            $imgobj = new Psp\Itm\ImgId();
            $imgobj->setImgId($img_id);
            GRPC('itm')->delItemImg($imgobj)->wait();
        }

    }

    /**
     *js 管理上下架
    * @throws \Exception
    */
    public function setIsOnSale(){
        $item_id = I('item_id',false);
        $logic = new ItemLogic();
        list($res,$status) = GRPC('itm')->setIsOnSale($logic->setItemId($item_id))->wait();
        exit(json_encode($res->getValue()));

    }



    /**
 * storeAllowcategory
* @throws \Exception
 */
     public function get_category(){

        $category_id = I('parent_id');
        $level = I('level');
        $level = str_replace('cat_id2','class_1',$level);
        $level = str_replace('cat_id3','class_2',$level);
        $logic = new ItemLogic();
        $data = $logic->getCategoryByparendId($category_id,STORE_ID,$level);
        $html = '';
        foreach($data as $k=>$v){

             $html .= "<option value='{$v['id']}' >{$v['name']}</option>";

        }
        exit($html);


    }

    /**
    *
    * 更新商品库存
    *
    */

    public function update_stock()
    {

        $item_id = I('item_id/d');
        $sku_id = I('sku_id/d');
        $stock = I('stock/d');
        $payload = validate_json_web_token(cookie('accesstoken'));
        $seller_id = $payload['seller_id']?$payload['seller_id']:1;
        $update_stock = new \Psp\Itm\UpdateStockRequest();
        $update_stock->setItemId($item_id);
        $update_stock->setSkuId($sku_id);
        $update_stock->setStock($stock);
        $update_stock->setAdmin($seller_id);

        list($res,$status) = GRPC(itm)->updateStock($update_stock)->wait();

        exit(json_encode($res->getValue()));

    }




}
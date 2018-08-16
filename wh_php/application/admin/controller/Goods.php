<?php
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\admin\logic\SearchWordLogic;
use app\seller\logic\ItemLogic;
use think\AjaxPage;
use think\Loader;
use think\Page;
use think\Db;
use Grpc;
use Psp;
use think\log;

class Goods extends Base {

    /**
     *  商品分类列表
     */
    public function categoryList(){
        //psp.GetCatList
       $GoodsLogic = new GoodsLogic();
       $cat_list = $GoodsLogic->goods_cat_list();
       //dump($cat_list);exit;
        adminOperateLog('商品分类列表',2);
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
    }

    /**
     * 删除分类
     */
    public function delGoodsCategory()
    {
        $id = $this->request->param('id');
        $cid =new \Psp\Item\ItemCid();
        $cid->setId($id);
        list($res,$status) =GRPC('cat')->DelCat($cid)->wait();
        if($res->getStatus() ==-2){
            $this->error('该分类下还有分类不得删除!',U('Admin/Goods/categoryList'));
        }
        if($res->getStatus() ==-1){
            $this->error('该分类下有商品不得删除!',U('Admin/Goods/categoryList'));
        }
        adminOperateLog('删除商品分裂',2);
        $this->success("操作成功!!!",U('Admin/Goods/categoryList'));
    }

    /**
     * 添加修改商品分类
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'),
     * select * from tp_goods_category where id = 393
        select * from tp_goods_category where parent_id = 393
        update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
        insert into `tp_goods_category` (`parent_id`,`name`) values
        ('393','时尚饰品'),
     */

    public function addEditCategory()
    {
        $GoodsLogic = new GoodsLogic();
        $ParentId1Reply = $GoodsLogic->getCate();
        if(IS_POST){
            $id = I('id/d');
            $name = I('name');
            $mobile_name = I('mobile_name');
            $parent_id1 = I('parent_id_1/d');
            $parent_id2 = I('parent_id_2/d');
            $is_show = I('is_show/d');
            $img_url = I('image');
            $group = I('cat_group/d');
            $sort = I('sort_order/d');
            $language = I('language/d',1);

            if($id){// 修改
                $edit = new Psp\Itm\UpdateCate();
                $edit->setCateId($id);
                $edit->setCateName($name);
                $edit->setMobileName($mobile_name);
                $edit->setParentId1($parent_id1);
                $edit->setParentId2($parent_id2);
                $edit->setIsShow($is_show);
                $edit->setImgUrl($img_url);
                $edit->setSort($sort);
                $edit->setGroup($group);
                $edit->SetLanguage($language);
                list($res,$status) = GRPC(itm)->updateCate($edit)->wait();

                if ($res->getvalue()){
                    $this->success("操作成功",U('Admin/Goods/categoryList'));

                }
            } else{//增
                $add = new Psp\Itm\Cate();
                $add->setName($name);
                $add->setMobileName($mobile_name);
                $add->setIsShow($is_show);
                $add->setGroup($group);
                $add->setImgUrl($img_url);
                $add->setParentId1($parent_id1);
                $add->setParentId2($parent_id2);
                $add->setLanguage($language);
                $add->setPlatform(PLATFORM);
                $add->setProviderId(0);
                list($res,$status) = GRPC(itm)->AddCate($add)->wait();
                //dump($res->getValue());die;
                if ($res->getValue()){

                    $this->success("操作成功",U('Admin/Goods/categoryList'));
                } else {
                    $this->error('操作失败');
                }
            }

        } else{
            if(I('get.id')){

                $catInfo = $GoodsLogic->getCateInfoByCateId(I('get.id'));
                if($catInfo['parent_id_1']){
                    $cate_list2 = $GoodsLogic->getCategoryByParentId($catInfo['parent_id_1']);
                }
            }
        }
        adminOperateLog('添加修改商品分类',2);

        $this->assign('goods_category_info',$catInfo);
        $this->assign('cat_list',$ParentId1Reply);
        $this->assign('cat_list2',$cate_list2);

        return $this->fetch('_category');




    }



    /**
     *  商品列表
     */
    public function goodsList()
    {
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getBrandList();
        $catList = $GoodsLogic->getCate();

        //dump($brandList);die;
        $this->assign('categoryList',$catList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     *  商品列表
     */

    public function ajaxGoodsList()
    {
        $is_on_sale = I('is_on_sale/d',-1);       //是否在售状态 筛选 0,1
        $keywords = I('key_word');            //搜索关键字,title like搜索
        $brand = I('brand_id/d',-1);
        $cate = I('cat_id/d',-1);
        $state = I('state/d',-1); //状态  1审核通过, 0 待审核,2审核失败,3违规下架

        $special = I('special/d',-1);
        $p = I('p/d',1);
        $request = new \Psp\Itm\AdminItemListRequest();
        $request->setCateId($cate);
        $request->setBrandId($brand);
        $request->setState($state);
        $request->setIsOnSale($is_on_sale);
        $request->setKeywords($keywords);
        $request->setSpecial($special);

        $pagination = grpcPage('item_id',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getAdminItemList($request)->wait();

        if($res){
            foreach ($res->getItemList() as $k=>$v){
                $item_list[$k]['item_id'] = $v->getItemId();
                $item_list[$k]['title'] = $v->getTitle();
                $item_list[$k]['cate_name'] = $v->getCateName(); //获取完整分类数 ps 一级分类//二级分类//三级分类 (请根据商品category_id查询category表parent_id_path获取完整路径值)
                $item_list[$k]['is_on_sale'] = $v->getIsOnSale();
                $item_list[$k]['store_name'] = $v->getStoreName();
                $item_list[$k]['state'] = $v->getState();
                $item_list[$k]['is_recommend'] = $v->getIsRecommend();
                $item_list[$k]['is_hot'] = $v->getIsHot();
                $item_list[$k]['special'] = $v->getSpecial();
                $item_list[$k]['stock'] = $v->getStock();
                foreach ($v->getPrice() as $kk=>$vv){
                    $item_list[$k]['price'][$kk]['old_sku'] = $vv->getOldSku();
                    $item_list[$k]['price'][$kk]['provider_shop_price'] = sprintf("%.2f",$vv->getProviderShopPrice());
                    $item_list[$k]['price'][$kk]['provider_price'] = sprintf("%.2f",$vv->getProviderPrice());
                    $item_list[$k]['price'][$kk]['market_price'] = sprintf("%.2f",$vv->getMarketPrice());
                    $item_list[$k]['price'][$kk]['shop_price'] = sprintf("%.2f",$vv->getShopPrice());
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
            adminOperateLog('查看商品列表',2);
            $Page = new AjaxPage($total_count,20);
            $show =$Page->show();

        }


        //dump($item_list);exit;
        $this->assign('goodsList',$item_list);

        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $goods_id = input('id');

        $user = new Psp\Item\ItemId();
        $user->setGoodsId($goods_id);

        list($res,$status) = GRPC('item')->DelItem($user)->wait();

        $er=$res->getStatus();

        if($er==-1){
            $error='此商品有退货记录,不得删除!';
        }
        if($er==-2){
            $error='此商品有团购,不得删除!';
        }
        if($er==-3){
            $error='此商品有订单,不得删除!';
        }
        if($error)
        {
            $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);


        }

        if($er==1){
            $del = new Psp\Item\ItemId();
            $del->setGoodsId($goods_id);
            list($res,$status) = GRPC('item')->DelItemAll($del)->wait();
            adminOperateLog('删除商品',2);
            if($res->getValue()){
                $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);
            }else{
                $return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
            }

        }


        // 删除此商品
        /*
        M("Goods")->where('goods_id ='.$goods_id)->delete();  //商品表
        M("cart")->where('goods_id ='.$goods_id)->delete();  // 购物车
        M("comment")->where('goods_id ='.$goods_id)->delete();  //商品评论
        M("goods_consult")->where('goods_id ='.$goods_id)->delete();  //商品咨询
        M("goods_images")->where('goods_id ='.$goods_id)->delete();  //商品相册
        M("spec_goods_price")->where('goods_id ='.$goods_id)->delete();  //商品规格
        M("spec_image")->where('goods_id ='.$goods_id)->delete();  //商品规格图片
        M("goods_attr")->where('goods_id ='.$goods_id)->delete();  //商品属性
        M("goods_collect")->where('goods_id ='.$goods_id)->delete();  //商品收藏
        */
        $this->ajaxReturn($return_arr);
    }

    //添加编辑商品
    public function addEditGoods()
    {
        $itemLogic = new ItemLogic();

        $item_id = I('id/d',0);


        if($item_id > 0){
            //获取商品信息
            $item = $itemLogic->getItem($item_id);
            $itemdetail = $itemLogic->getItemDetail($item_id);
            $itemimg = $itemLogic->getItemImg($item_id);

        }
        $delivery_temp = $itemLogic->getDeliveryTemp($item['provider_id']); //获取商家物流模板
        $categorys = $itemLogic->getProviderCategory($item['provider_id']);

        $this->initEditor(); // 编辑器
        adminOperateLog('查看商品详情',2);
        $this->assign('cat_list',$categorys);
        $this->assign('iteminfo',$item);
        $this->assign('itemdetail',$itemdetail);
        $this->assign('itemimg',$itemimg);
        return $this->fetch('_goods');
    }

    /**
     * storeAllowcategory
     * @throws \Exception
     */
    public function get_category(){

        $category_id = I('parent_id');
        $provider_id = I('provider_id');
        $level = I('level');
        $level = str_replace('cat_id2','class_1',$level);
        $level = str_replace('cat_id3','class_2',$level);
        $logic = new ItemLogic();
        $data = $logic->getCategoryByparendId($category_id,$provider_id,$level);
        $html = '';
        foreach($data as $k=>$v){

            $html .= "<option value='{$v['id']}' >{$v['name']}</option>";

        }
        exit($html);


    }

    //库存
    public function stock_list()
    {
        $stime = strtotime(I('stime',''));
        $etime = strtotime(I('etime',''));
        $type = I('mtype/d',0);
        $item_name = I('item_name','');
        $p = I('p/d',1);
        $request = new Psp\Itm\GetStockLogRequest();
        $request->setType($type);
        empty(!$stime)&&$request->setMinDate(grpcTime($stime));
        empty(!$etime)&&$request->setMaxDate(grpcTime($etime));
        //$request->setProviderId(STORE_ID);
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
        if($p == 1){
            adminOperateLog('库存列表',2);
        }

        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('stock_list',$data);
        return $this->fetch();
    }


    /**
     * 更改指定表的指定字段
     */
    /*public function updateField()
    {
        $primary = array(
                'goods' => 'goods_id',
                'goods_category' => 'id',
                'brand' => 'id',
                'goods_attribute' => 'attr_id',
        		'ad' =>'ad_id',
        );
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];
        $model->save();
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',
            'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }*/



    /**
     * 品牌列表
     */
    public function brandList()
    {
        $p = I('p/d',1);
        $keyword = I('keyword');
        $request = new \Psp\Itm\BrandListRequest();
        $keyword&&$request->setName($keyword);

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
        if($p == 1){
            adminOperateLog('品牌列表',2);
        }

        $this->assign('page',$show);// 赋值分页输出
        $this->assign('keyword',$keyword);
        $this->assign('brandList',$brandList);
        return $this->fetch('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand()
    {
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
                $brand_info->setProviderId(0);
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
        adminOperateLog('编辑商品品牌',2);
        $this->assign('cat_list',$cat_list);
        return $this->fetch('_brand');
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {

        $brand_id = I('id/d');
        //$client = grpc('Brand');
        /*$client = new Psp\Item\ItemBrandServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/
        $bid = new Psp\Item\DelBrandRequest();
        $bid->setBrandId($brand_id);
        list($res,$status) = GRPC('brand')->DelBrand($bid)->wait();
        $delStatus = $res->getStatus();
        if($delStatus ==1){
            $msg = '操作成功';
        }
        if($delStatus == -1){
            $msg = '此品牌有商品在用不得删除!';
        }
        adminOperateLog('删除商品品牌',2);
        $return_arr = array('status' => $delStatus,'msg' =>$msg,'data'  =>'',);
        $this->ajaxReturn($return_arr);
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
     * 商品规格列表
     */
    public function specList1()
    {
        $logic = new GoodsLogic();
        $category = $logic->getProviderAllowCategoryList(0);
        static $data = [];
        dump($category);
        foreach ($category as $k=>$v){
            if($v['class_3']){
                $spec=$logic->getSpecByCategory($v['class_3']);
                if(is_array($spec) && !empty($spec)){
                    foreach ($spec as $k1=>$v1){
                        $data[] = array_merge($v,$v1);
                    }
                }
            }
        }
        dump($data);die;
        $this->assign('specList',$data);

        return $this->fetch();
    }

    /**
     * 商品规格列表
     */
    public function specList()
    {
        $p = I('p/d',1);
        $cate = I('cate_id/d');
        $request = new \Psp\Itm\AdminSpecListRequest();
        $cate&&$request->setCateId($cate);

        $pagination = grpcPage('id',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getAdminSpecList($request)->wait();

        if($res) {
            foreach ($res->getSpecList() as $k => $v) {
                $SpecList[$k]['spec_id'] = $v->getSpecId();
                $SpecList[$k]['spec_name'] = $v->getSpecName();
                foreach ($v->getCate() as $kk=>$vv){
                    $SpecList[$k]['cate'][$kk]['class1'] = $vv->getClassOne();
                    $SpecList[$k]['cate'][$kk]['class2'] = $vv->getClassTwo();
                    $SpecList[$k]['cate'][$kk]['class3'] = $vv->getClassThree();
                    $SpecList[$k]['cate'][$kk]['class1_name'] = $vv->getClassOneName();
                    $SpecList[$k]['cate'][$kk]['class2_name'] = $vv->getClassTwoName();
                    $SpecList[$k]['cate'][$kk]['class3_name'] = $vv->getClassThreeName();
                }


            }

            $total_count =$res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,20);
            $show =$Page->show();

        }
        if($p == 1){
            adminOperateLog('商品规格列表',2);
        }

        $this->assign('specList',$SpecList);
        $this->assign('page',$show);// 赋值分页输出

        return $this->fetch();
    }



    /**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        $sid = I('id');
        /*$client = new Psp\Item\ItemExtraServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/

        $spec = new Psp\Item\SpecId();
        $spec->setId($sid);

        list($res,$status) = GRPC('extra')->DelSpec($spec)->wait();
        $status = $res->getStatus();
        adminOperateLog('删除商品规格',2);
        if ($status == -1){
            $this->error('操作失败，该规格含有规格项，请清空后尝试',U('Admin/Goods/specList'));
        } else {
            $this->success("操作成功",U('Admin/Goods/specList'));
        }
    }
    /**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec()
    {
        // 点击过来编辑时
//        $id = I('id/d',0); // 判断 edit or add

        $itemLogic = new GoodsLogic();
        $categorys = $itemLogic->getCate();

        $this->assign('cat_list',$categorys);
        if(IS_POST){
            $itemLogic->addSpec(I('cat_id3/d'),I('name/s'));
            adminOperateLog('编辑商品规格',2);
            $this->success('添加成功');
        }


        return $this->fetch('_spec');
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = I('goods_id',0);
        $cat_id3 = I('cat_id3',0);
        $provider_id = I('provider_id');
        empty($cat_id3) && exit('');
        static $specList = [];
        $logic = new ItemLogic();
        $specList = $logic->getSpecByCategory($cat_id3);


        foreach($specList as $k => $v){
            $specList[$k]['spec_opt'] = $logic->getProviderSpecOption($provider_id,$v['spec_id']);
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
        $this->assign('price',$pricedata);
//todo 规格项图片列表
//        $this->assign('specImageList',$specImageList);
        $this->assign('items_ids',$opt_idxs);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');
    }



    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('goods_id',0);
        $spec_arr = $_POST['spec_arr'];//绝对不能用I,会将数组转换的妈都不认识
        $data=  $GoodsLogic->getSpecInput($goods_id,$spec_arr);
        exit($data);
    }

    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $id = I('filename',''); // 实际是 id 值
        /*$client = new Psp\Item\ItemServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/
        $imgId = new Psp\Item\ImgId();
        $imgId->setImgId($id);
        list($res,$status) = GRPC('item')->DelItemImage($imgId)->wait();
        adminOperateLog('删除商品相册',2);
    }

    /**
     * 初始化商品关键词搜索
     */
    public function initGoodsSearchWord()
    {
        $searchWordLogic = new SearchWordLogic();
        $searchWordLogic->initGoodsSearchWord();
    }


    /**
     *
     * 分类审核
     *
     */

    public function check_cate()
    {
        $cate_id = I('cate_id/d');
        $code = I('code');
        $value = I('value/d');

        $check = new \Psp\Itm\CategoryCheck();
        $check->setCateId($cate_id);
        $check->setCode($code);
        $check->setValue($value);


        list($res,$status) = GRPC(itm)->CheckCate($check)->wait();
        adminOperateLog('审核分类',2);
        exit(json_encode($res->getValue()));

    }
    /**
     *
     * 商家经营类目审核
     *
     */

    public function check_allow()
    {
        $id = I('id/d');
        $code = I('code');
        $value = I('value/d');
        $check = new \Psp\Itm\CheckInfo();
        $check->setId($id);
        $check->setValue($value);


        list($res,$status) = GRPC(itm)->checkProviderAllow($check)->wait();
        adminOperateLog('商家经营类目审核',2);
        exit(json_encode($res->getValue()));

    }

    /**
     *
     * 品牌审核
     *
     */

    public function check_brand()
    {
        $brand_id = I('brand_id/d');
        $code = I('code');
        $value = I('value/d');

        $check = new \Psp\Itm\BrandId();
        $check->setBrandId($brand_id);
        $check->setCode($code);
        $check->setValue($value);


        list($res,$status) = GRPC(itm)->CheckBrand($check)->wait();
        adminOperateLog('商家经营品牌审核',2);
        exit(json_encode($res->getValue()));

    }

    /**
     *
     * 商品审核
     *
     */

    public function check_goods()
    {
        $item_id = I('item_id/d');
        $code = I('code');
        $value = I('value/d');
        $note = I('note');
        $check = new \Psp\Itm\stateInfo();
        $check->setItemId($item_id);
        $check->setCode($code);
        $check->setVaule($value);
        $note&&$check->setReason($note);

        list($res,$status) = GRPC(itm)->updateItemState($check)->wait();
        adminOperateLog('审核商品',2);
        exit(json_encode($res->getValue()));

    }


    /**
     *
     * 经营类目列表
     *
     */

    public function providerAllowList()
    {
        $keywords = I('keyword');            //搜索关键字,store_name like搜索
        $cate = I('cat_id/d',-1);
        $p = I('p/d',1);
        $request = new \Psp\Itm\ProviderAllowRequest();
        $request->setCateId($cate);
        $request->setProviderName($keywords);

        $pagination = grpcPage('bid',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getProviderAllowList($request)->wait();

        if($res){
            foreach ($res->getCateList() as $k=>$v){
                $cate_list[$k]['id'] = $v->getBid();
                $cate_list[$k]['provider_id'] = $v->getProviderId();
                $cate_list[$k]['provider_name'] = $v->getProviderName();
                $cate_list[$k]['class_1'] = $v->getClass1();
                $cate_list[$k]['class_2'] = $v->getClass2();
                $cate_list[$k]['class_3'] = $v->getClass3();
                $cate_list[$k]['class_name_1'] = $v->getClassName1();
                $cate_list[$k]['class_name_2'] = $v->getClassName2();
                $cate_list[$k]['class_name_3'] = $v->getClassName3();
                $cate_list[$k]['state'] = $v->getState();

            }

            $total_count =$res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,20);
            $show =$Page->show();

        }
        $GoodsLogic = new GoodsLogic();
        $ParentId1Reply = $GoodsLogic->getCate();
        if($p == 1){
            adminOperateLog('经营类目列表',2);
        }

        $this->assign('cateList',$cate_list);
        $this->assign('cat_list1',$ParentId1Reply);//获取所有的一级分类
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

}
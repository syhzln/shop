<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * 专题管理
 * Date: 2016-03-09
 */

namespace app\seller\controller;

use app\seller\model\GoodsActivity;
use think\AjaxPage;
use think\Page;
use app\seller\logic\GoodsLogic;
use think\Loader;
use think\Db;
use Psp;
use Grpc;

class Store extends Base
{
    //公司信息
    public function store_info()
    {
        $apply_id=I('post.apply_id',STORE_ID);
        $data = new Psp\Store\CompanyBasicInfo();
        $data->setApplyId($apply_id);
        list($result,$status) = GRPC('sellerstore') ->GetCompanyInfo($data)->wait();
        $company['license_no']=$result->getLicenseNo();
        $company['enterprise_name']=$result->getEnterpriseName();
        $company['prov_name']=$result->getProvName();
        $company['company_type']=$result->getCompanyType();
        $company['tax_no']=$result->getTaxNo();
        $company['org_no']=$result->getOrgNo();
        $company['scope']=$result->getScope();
        $company['eastablish_date']=$result->getEastablishDate();
        $company['legal_presentative']=$result->getLegalPresentative();
        $company['residence']=$result->getResidence();
        $company['license_img_url']=$result->getLicenseImgUrl();
        $company['tax_img_url']=$result->getTaxImgUrl();
        $company['org_img_url']=$result->getOrgImgUrl();
        $company['present_front_img_url']=$result->getPresentFrontImgUrl();
        $company['present_back_img_url']=$result->getPresentBackImgUrl();
        $company['prov_name']=$result->getProvName();
        $company['business_categories']=$result->getBusinessCategories();

        $list = new Psp\Store\ApplyId();
        $list->setApplyId($apply_id);
        list($result,$status) = GRPC('sellerstore') ->GetStoreInfo($list)->wait();
//        foreach ($result->getStoreInfo() as $k=>$v){
            $arr['apply_id'] = $result->getApplyId();
            $arr['shop_name'] = $result->getShopName();
            $arr['location'] = $result->getLocation();
            $arr['location_full'] = $result->getLocationFull();
            $arr['apply_state'] = $result->getApplyState();
            $arr['is_closed'] = $result->getIsClosed();
            $arr['close_reason'] = $result->getCloseReason();
            $arr['manager_user_id'] = $result->getManagerUserId();//管理员（负责人）
            $arr['recommender_id'] = $result->getRecommenderId();//引荐人
            $arr['phone'] = $result->getPhone();
            $arr['fax'] = $result->getFax();//传真
            $arr['email'] = $result->getEmail();
//        }

        $da = new Psp\Store\ApplyId();
        $apply_id = I('post.apply_id/d',STORE_ID);//店铺id
        $da->setApplyId($apply_id);
        /*获取店铺id不为0的类目*/
        list($result,$status) = GRPC('sellerstore') ->GetBindClassListBy($da)->wait();
        foreach ($result->getBindClass() as $k=>$v) {
            $crr[$k]['apply_id'] = $v->getApplyId();
            $crr[$k]['category_id'] = $v->getCategoryId();
            $crr[$k]['parent_id'] = $v->getParentId();
            $crr[$k]['class_one'] = $v->getClassOne();
            $crr[$k]['class_two'] = $v->getClassTwo();
            $crr[$k]['class_three'] = $v->getClassThree();
        }
        if($crr==null){
            $crr=array();
        }
        /*获取店铺id为0的类目*/
        $d = new Psp\Store\ApplyId();
        $d->setApplyId(0);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassList($d)->wait();
        foreach ($result->getBindClass() as $j=>$val){
            $drr[$j]['category_id'] = $val->getCategoryId();
            $drr[$j]['category_name'] = $val->getCategoryName();
            $drr[$j]['parent_id'] = $val->getParentId();
        }
        $action=array_column($drr, 'category_name','category_id');
        $act=array_merge($crr);
        for($i = 0, $q = count($act); $i < $q; $i++)
        {
            $act[$i]['class_1'] = $action[$act[$i]['class_one']];
            $act[$i]['class_2'] = $action[$act[$i]['class_two']];
            $act[$i]['class_3'] = $action[$act[$i]['class_three']];
        }
        $this->assign('act',$act);
        $this->assign('arr',$arr);
        $this->assign('company',$company);
        return $this->fetch();
    }


    /*获取经营类目列表*/
    public function store_class_list(){
        return $this->fetch();
    }

    /*ajax获取经营类目列表*/
    public function ajaxStoreClassList()
    {
        $shop_name=I('post.shop_name',null);
        $category_state=I('post.category_state',0);
        $p=I('post.p/d',1);
        $co=14;
        //搜索
        $search=new Psp\Store\StoreClassSearch();
        $search->setShopName($shop_name);
        $search->setCategoryState($category_state);

        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy('provider_id');
        $page->setIndex($p);
        $page->setLimit($co);
        $data = new Psp\Store\ApplyId();

        $apply_id = I('post.apply_id/d',STORE_ID);
        $data->setApplyId($apply_id);
        $data->setPagination($page);
        $data->setStoreClassSearch($search);
        list($res,$status) = GRPC('sellerstore') ->GetBindClassLists($data)->wait();
        foreach ($res->getBindClass() as $k=>$v) {
            $crr[$k]['apply_id'] = $v->getApplyId();
            $crr[$k]['class_one'] = $v->getClassOne();
            $crr[$k]['class_two'] = $v->getClassTwo();
            $crr[$k]['class_three'] = $v->getClassThree();
            $crr[$k]['category_status'] = $v->getCategoryStatus();
        }
        if($crr==null){
            $crr=array();
        }
        $type = new Psp\Store\ApplyId();
        $type->setApplyId(0);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassList($type)->wait();
        foreach ($result->getBindClass() as $j=>$val){
            $drr[$j]['category_id'] = $val->getCategoryId();
            $drr[$j]['category_name'] = $val->getCategoryName();
        }


        $data=array_column($drr, 'category_name','category_id');

        $act=array_merge($crr);
        for($i = 0, $q = count($act); $i < $q; $i++)
        {
            $act[$i]['class_1'] = $data[$act[$i]['class_one']];
            $act[$i]['class_2'] = $data[$act[$i]['class_two']];
            $act[$i]['class_3'] = $data[$act[$i]['class_three']];
            $storeInfo = new Psp\Store\ApplyId();
            $storeInfo->setApplyId($act[$i]['apply_id']);
            list($result,$status) = GRPC('sellerstore') ->GetShopName($storeInfo)->wait();
            $act[$i]['store_name'] = $result->getStoreName();
        }
        $count=$res->getPaginationResult()->getTotalRecords();

        $Page  = new AjaxPage($count,$co);
        $show = $Page->show();
        $this->assign('act',$act);
        $this->assign('page',$show);
        return $this->fetch();
    }

    /*获取三级分类，三级联动*/
    public function store_class_info()
    {
        /*获取三级类目名称*/
        $apply=I('get.apply_id',STORE_ID);
        $this->assign('apply',$apply);

        $da = new Psp\Store\ApplyId();
        $apply_id = I('post.apply_id/d',STORE_ID);//店铺id
        $da->setApplyId($apply_id);
        /*获取店铺id不为0的类目*/
        list($result,$status) = GRPC('sellerstore') ->GetBindClassListBy($da)->wait();
        foreach ($result->getBindClass() as $k=>$v) {
            $crr[$k]['apply_id'] = $v->getApplyId();
            $crr[$k]['class_one'] = $v->getClassOne();
            $crr[$k]['class_two'] = $v->getClassTwo();
            $crr[$k]['class_three'] = $v->getClassThree();
        }
        if($crr==null){
            $crr=array();
        }
        /*获取店铺id为0的类目*/
        $d = new Psp\Store\ApplyId();
        $d->setApplyId(0);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassList($d)->wait();
        foreach ($result->getBindClass() as $j=>$val){
            $drr[$j]['category_id'] = $val->getCategoryId();
            $drr[$j]['category_name'] = $val->getCategoryName();
            $drr[$j]['parent_id'] = $val->getParentId();
        }
        $data=array_column($drr, 'category_name','category_id');
        $act=array_merge($crr);
        for($i = 0, $q = count($act); $i < $q; $i++)
        {
            $act[$i]['class_1'] = $data[$act[$i]['class_one']];
            $act[$i]['class_2'] = $data[$act[$i]['class_two']];
            $act[$i]['class_3'] = $data[$act[$i]['class_three']];
        }
        $this->assign('act',$act);

        /*获取一级类目*/
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId(0);
        $storeInfo->setParentId(0);
        $storeInfo->setLanguage(1);
        $storeInfo->setPlatform(1);
        $storeInfo->setLevel(1);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassOneInfo($storeInfo)->wait();
        foreach ($result->getBindClass() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['category_id'] = $v->getCategoryId();
            $arr[$k]['category_name'] = $v->getCategoryName();
            $arr[$k]['parent_id'] = $v->getParentId();
            $arr[$k]['level'] = $v->getLevel();
            $arr[$k]['class_one'] = $v->getClassOne();
            $arr[$k]['class_two'] = $v->getClassTwo();
            $arr[$k]['class_three'] = $v->getClassThree();
        }
        $this->assign('cat_list',$arr);
        return $this->fetch();
    }

    /*添加店铺经营类目*/
    public function store_class_add(){
        $apply=I('post.apply',STORE_ID);

        $classOne = I('post.class_1',0);
        $classTwo = I('post.class_2',0);
        $classThree = I('post.class_3',0);

        $data=new Psp\Store\BindClass();
        $data->setApplyId($apply);
        $data->setCategoryName(0);
        $data->setParentId($classThree);
        $data->setClassOne($classOne);
        $data->setClassTwo($classTwo);
        $data->setClassThree($classThree);

        list($var,$status) = GRPC('sellerstore') ->AddBindClass($data)->wait();
        if($var!=0 && $var!=null){
            $res = array('stat'=>'ok');
        }else{
            $res = array('stat'=>'fail','msg'=>'操作失败');
        }
        respose($res);

    }

    /*删除经营类目*/
    public function store_class_del(){
        $apply_id=I('post.apply_id',0);
        $category_id=I('post.category_id',0);
        $list = new Psp\Store\ClassId();
        $list->setApplyId($apply_id);
        $list->setClassThree($category_id);
        list($res,$status) = GRPC('sellerstore') ->DelClassState($list)->wait();
        if($res){
            $res = array('stat'=>'ok');
        }else{
            $res = array('stat'=>'fail','msg'=>'操作失败');
        }
        respose($res);
    }


}
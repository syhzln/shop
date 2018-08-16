<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/6 0006
 * Time: 19:19
 */

namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use think\Db;
use Grpc;
use Psp;
use think\Log;

class Store extends Base
{ 

    /*店铺列表*/
    public function storeList()
    {
        return $this->fetch();
    }

    /*ajax店铺列表*/
    public function ajaxStoreList()
    {
        $shop_name=I('post.shop_name',null);
        $mobile =I('post.mobile',null);//店铺联系人电话
        $account_name =I('post.account_name',null);//登录账号
        $is_closed=I('post.is_closed',0);
        $p=I('get.p/d',1);
        $co=16;
        //搜索
        $search=new Psp\Store\StoreSearch();
        $search->setShopName($shop_name);
        $search->setIsClosed($is_closed);
        $search->setMobile($mobile);
        $search->setLoginAccount($account_name);
        //分页
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy('apply_id');
        $page->setIndex($p);  //页码  每页条数
        $page->setLimit($co);
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId(0);
        $storeInfo->setPagination($page);
        $storeInfo->setStoresearch($search);
//        $storeInfo->setApplyState(2);
        list($result,$status) = GRPC('sellerstore') ->GetStoreInfoLists($storeInfo)->wait();
        foreach ($result->getStoreInfo() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['shop_name'] = $v->getShopName();
            $arr[$k]['open_time'] = $v->getOpenTime()->getSeconds();
            $arr[$k]['is_closed'] = $v->getIsClosed();
            $arr[$k]['apply_state'] = $v->getApplyState();
            $arr[$k]['login_username'] = $v->getLoginUsername();
            $arr[$k]['phone'] = $v->getPhone();
            $arr[$k]['qq'] = $v->getQq();
            $arr[$k]['prov_name'] = $v->getProvName();
        }
        $count=$result->getPaginationResult()->getTotalRecords();
        if($p == 1){
            adminOperateLog('店铺列表',1);
        }

        $Page  = new AjaxPage($count,$co);
        $show = $Page->show();
        $this->assign('storeInfo',$arr);
        $this->assign('page',$show);
        return $this->fetch();
    }

    /*添加店铺*/
    public function store_add()
    {
        if(IS_POST){
            $data = new Psp\Store\ItemInfo();
            $data->setShopName(I('post.shop_name'));//店铺名
            $data->setLoginUsername(I('post.login_username'));
            $data->setLoginSecret(encrypt(I('post.login_secret')));
            $data->setName(I('post.name'));//姓名
            $data->setPhone(I('post.phone'));//联系人电话
            $data->setEmail(I('post.email')); //联系人邮箱
            $data->setQq(I('post.user_qq'));//qq
            $data->setPlatformId((int)PLATFORM);//默认为1，根据平台传入不同的默认id
            list($res,$status) = GRPC('sellerstore') ->AddStoreInfoList($data)->wait();
            $ret = $res->getRet();
            $msg = $res->getMsg();
            adminOperateLog('后台添加店铺',1);
            if($ret == 'ok'){
                $this->success('添加成功');exit;
            }else{
                $this->error("{$msg}");exit;
            }
        }
        return $this->fetch();
    }

    /*获取三级分类，三级联动*/
    public function store_class_info()
    {
        /*获取三级类目名称*/
        $apply=I('get.apply_id',0);
        $this->assign('apply',$apply);

        $da = new Psp\Store\ApplyId();
        $apply_id = I('post.apply_id/d',0);//店铺id
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

        adminOperateLog('添加商品类目',2);
        $this->assign('cat_list',$arr);
        return $this->fetch();
    }

    /*添加店铺经营类目*/
    public function store_class_add()
    {
        $apply=I('post.apply',0);
        $classOne = I('post.classOne',0);
        $classTwo = I('post.classTwo',0);
        $classThree = I('post.classThree',0);

        $data=new Psp\Store\BindClass();
        $data->setApplyId($apply);
        $data->setCategoryName(0);
        $data->setParentId($classThree);
        $data->setClassOne($classOne);
        $data->setClassTwo($classTwo);
        $data->setClassThree($classThree);
        list($var,$status) = GRPC('sellerstore')->AddBindClass($data)->wait();
        if($var!=0 && $var!=null && $classThree){
            adminOperateLog('添加经营类目',2);
            $this->success('添加经营类目成功');exit;
        }else{
            $this->error('添加经营类目失败');exit;
        }
    }

    /*删除店铺*/
    public function store_del()
    {
        $apply_id=I('post.apply_id',0);
        $list = new Psp\Store\ApplyId();
        $list->setApplyId($apply_id);
        list($var,$status) = GRPC('sellerstore')->DelStoreInfoList($list)->wait();
        if($var){
            $res = array('stat'=>'ok');
        }else{
            $res = array('stat'=>'fail','msg'=>'操作失败');
        }
        adminOperateLog('删除店铺',1);
        respose($res);
    }


    /*获取经营类目列表*/
    public function store_class_list()
    {
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

        $data->setApplyId(0);
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

        if($p == 1){
            adminOperateLog('经营类目列表',2);
        }

        $Page  = new AjaxPage($count,$co);
        $show = $Page->show();
        $this->assign('act',$act);
        $this->assign('page',$show);
        return $this->fetch();
    }

    /*审核经营类目*/
    public function store_class_save()
    {
        $apply_id=I('post.apply_id',0);
        $category_id=I('post.category_id',0);
        $list = new Psp\Store\ClassId();
        $list->setApplyId($apply_id);
        $list->setClassThree($category_id);
        list($reply,$status) = GRPC('sellerstore')->UpdateClassState($list)->wait();
        $bool = $reply->getValue();
        adminOperateLog('审核经营类目',2);
        if($bool){
            echo '1'; //审核成功
        }
    }

    /*删除经营类目*/
    public function store_class_del()
    {
        $apply_id=I('post.apply_id',0);
        $category_id=I('post.category_id',0);
        $list = new Psp\Store\ClassId();
        $list->setApplyId($apply_id);
        $list->setClassThree($category_id);
        list($res,$status) = GRPC('sellerstore') ->DelClassState($list)->wait();
        adminOperateLog('删除经营类目',2);
        if($res){
            $res = array('stat'=>'ok');
        }else{
            $res = array('stat'=>'fail','msg'=>'操作失败');
        }
        respose($res);
    }

    /*店铺详细信息*/
    public function store_info()
    {
        $apply_id=I('post.apply_id',0);
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

        $da = new Psp\Store\ApplyId();
        $apply_id = I('post.apply_id/d',0);//店铺id
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
        $action=array_column($drr, 'category_name','category_id');
        $act=array_merge($crr);
        for($i = 0, $q = count($act); $i < $q; $i++)
        {
            $act[$i]['class_1'] = $action[$act[$i]['class_one']];
            $act[$i]['class_2'] = $action[$act[$i]['class_two']];
            $act[$i]['class_3'] = $action[$act[$i]['class_three']];
        }
        adminOperateLog('店铺信息详情',1);
        $this->assign('act',$act);
        $this->assign('arr',$arr);
        $this->assign('company',$company);
        return $this->fetch();

    }

    /*编辑店铺信息*/
    public function store_info_edit(){
        $apply_id=I('get.apply_id',0);
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId($apply_id);
        list($result,$status) = GRPC('sellerstore') ->GetStoreInfo($storeInfo)->wait();
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
        $arr['bank_account_number'] = $result->getBankAccountNumber();
        $arr['bank_branch_name'] = $result->getBankBranchName();
        $arr['bank_province'] = $result->getBankProvince();
        $arr['bank_city'] = $result->getBankCity();
        $arr['prov_name'] = $result->getProvName();
        $arr['store_logo'] = $result->getStoreLogo();

        $this->assign('arr',$arr);
        $data = new Psp\Store\CompanyBasicInfo();
        $data->setApplyId((int)$apply_id);
        list($res,$status) = GRPC('sellerstore') ->GetCompanyInfo($data)->wait();
        $company['license_no']=$res->getLicenseNo();
        $company['enterprise_name']=$res->getEnterpriseName();
        $company['prov_name']=$res->getProvName();
        $company['company_type']=$res->getCompanyType();
        $company['tax_no']=$res->getTaxNo();
        $company['org_no']=$res->getOrgNo();
        $company['scope']=$res->getScope();
        $company['eastablish_date']=$res->getEastablishDate();
        $company['legal_presentative']=$res->getLegalPresentative();
        $company['residence']=$res->getResidence();
        $company['license_img_url']=$res->getLicenseImgUrl();
        $company['tax_img_url']=$res->getTaxImgUrl();
        $company['org_img_url']=$res->getOrgImgUrl();
        $company['present_front_img_url']=$res->getPresentFrontImgUrl();
        $company['present_back_img_url']=$res->getPresentBackImgUrl();
        $company['business_categories']=$res->getBusinessCategories();

        adminOperateLog('编辑店铺信息',1);

        $this->assign('company',$company);

        return $this->fetch();
    }


    public function class_edit(){
        if(IS_POST) {
            $val = new Psp\Store\StoreCompany();
            $val->setApplyId((int)I('post.apply_id'));
            $val->setShopName(I('post.arr.shop_name'));
            $val->setEnterpriseName(I('post.enterprise_name'));
            $val->setLicenseNo(I('post.license_no',null));
            $val->setPhone((int)I('post.phone'));
            $val->setFax(I('post.fax'));
            $val->setEmail(I('post.email'));
            $val->setCompanyType((int)I('post.company_type'));
            $val->setTaxNo(I('post.tax_no'));
            $val->setOrgNo(I('post.org_no'));
            $val->setScope(I('post.scope'));
            $val->setLegalPresentative(I('post.legal_presentative'));
            $val->setResidence(I('post.residence'));
            $val->setLicenseImgUrl(I('post.license_img_url'));
            $val->setTaxImgUrl(I('post.tax_img_url'));
            $val->setOrgImgUrl(I('post.org_img_url'));
            $val->setPresentFrontImgUrl(I('post.present_front_img_url'));
            $val->setLocationFull(I('post.location_full'));
            $val->setProvName(I('post.prov_name'));
            $val->setCloseReason(I('post.arr.close_reason'));
            $val->setIsClosed((int)I('post.arr.is_closed'));
            $val->setBankAccountNumber(I('post.bank_account_number',null));
            $val->setBankBranchName(I('post.bank_branch_name'));
            $val->setBankProvince((int)I('post.bank_province'));
            $val->setBankCity((int)I('post.bank_city'));
            $val->setStoreLogo(I('post.store_logo'));
            $val->setState(2);
            list($res,$status) = GRPC('sellerstore') ->UpdateStoreCompany($val)->wait();
            adminOperateLog('分类编辑',1);
            if ($res->getValue()==true) {
                $res = array('stat'=>'ok');
            }else{
                $res = array('stat'=>'fail');
            }
            respose($res);
        }

    }

    /*密码变更*/
    public function pwd(){
        $apply_id = I('post.apply_id/d',0);//店铺id

        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId($apply_id);

        list($result,$status) = GRPC('sellerstore') ->GetPwdInfo($storeInfo)->wait();
        $act['shop_name']=$result->getShopName();
        $act['open_time']=$result->getOpenTime()->getSeconds();
        $act['login_username']=$result->getLoginUsername();

        $this->assign('storeInfo',$act);
        return $this->fetch();
    }

    public function change_pwd(){
        $val = new Psp\Store\ChangePwd();
        $val->setLoginUsername(I('post.login_username'));
        $val->setLoginSecret(encrypt(I('post.login_secret')));
        list($result,$status) = GRPC('sellerstore') ->UpdateChangePwd($val)->wait();
        adminOperateLog('修改商户登录密码',1);
        if ($result->getValue()==true){
            $res = array('stat'=>'ok');
        }else{
            $res = array('stat'=>'fail');
        }
        respose($res);

    }

    /*开店申请*/
    public function shop_application(){
        return $this->fetch();
    }

    /*开店申请列表*/
    public function ajaxShopApplication(){

        $shop_name=I('key_word',null);
        $apply_state=empty(I('apply_state')) ? 1 : I('apply_state');  // 新申请 1
        $p=I('p/d',1);
        $co=14;
        $apply_id = I('apply_id/d',0);//店铺id
        //搜索
        $search=new Psp\Store\StoreSearch();
        $search->setShopName($shop_name);
        $search->setState($apply_state);
        //分页
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy('apply_id');
        $page->setIndex($p);  //页码  每页条数
        $page->setLimit($co);
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId($apply_id);
        $storeInfo->setPagination($page);
        $storeInfo->setStoresearch($search);
        $storeInfo->setApplyState($apply_state);
        list($result,$status) = GRPC('sellerstore') ->GetStoreInfoLists($storeInfo)->wait();
        foreach ($result->getStoreInfo() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['shop_name'] = $v->getShopName();
            $arr[$k]['apply_time'] = $v->getApplyTime()->getSeconds();
            $arr[$k]['apply_state'] = $v->getApplyState();
            $arr[$k]['login_username'] = $v->getLoginUsername();
        }
        $count=$result->getPaginationResult()->getTotalRecords();
        if($p == 1){
            adminOperateLog('店铺申请列表',1);
        }
        $Page  = new AjaxPage($count,$co);
        $show = $Page->show();
        $this->assign('storeInfo',$arr);
        $this->assign('page',$show);

        return $this->fetch();
    }

    /*店铺详细信息审核*/
    public function apply_info(){

        $apply_id=I('post.apply_id',0);
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
        /*经营类目列表*/
        $da = new Psp\Store\ApplyId();
        $apply_id = I('post.apply_id/d',0);//店铺id
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
        $action=array_column($drr, 'category_name','category_id');
        $act=array_merge($crr);
        for($i = 0, $q = count($act); $i < $q; $i++)
        {
            $act[$i]['class_1'] = $action[$act[$i]['class_one']];
            $act[$i]['class_2'] = $action[$act[$i]['class_two']];
            $act[$i]['class_3'] = $action[$act[$i]['class_three']];
        }
        adminOperateLog('店铺详细信息审核',1);
        $this->assign('act',$act);
        $this->assign('arr',$arr);
        $this->assign('company',$company);
        return $this->fetch();

    }

    public function review(){
        if(IS_POST){
            $button = new Psp\Store\StoreInfoReply();
            $button ->setApplyId((int)I('post.apply_id'));
            $button->setApplyState((int)I('post.apply_state'));
            $button->setPlatformId(1);//默认为1，根据平台传入不同的默认id
            list($result,$status) = GRPC('sellerstore') ->AddStoreInfo($button)->wait();
            adminOperateLog('审核店铺',1);

            if($result->getValue()==true){
                $this->success('审核成功');
            }
        }
    }



}
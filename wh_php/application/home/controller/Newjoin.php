<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2016-05-29
 */

namespace app\home\controller;

use think\Jump;
use Psp;
Use Grpc;

class Newjoin extends Base {
	public $user_id;
	public $apply = array(1=>'dfsd');
	
	public function _initialize() {
        parent::_initialize();
        $this->user_id = cookie('reg_id');
        if(empty($this->user_id) && ACTION_NAME !='index'){
            $this->error('请先验证手机登陆',U('/home/Userapi/login'));
//            redirect(U('/home/userapi/login'));
        }else if(!empty($this->user_id)){
            $var = new Psp\Store\UserId();
            $var->setUserId((int)$this->user_id);
            list($res, $status) = GRPC('sellerstore')->GetUserMsgInfo($var)->wait();
            $common['shop_name']=$res->getShopName();
            $common['prov_name']=$res->getProvName();
            $common['location_full']=$res->getLocationFull();
            $common['phone']=$res->getPhone();
            $common['fax']=$res->getFax();
            $common['apply_id']=$res->getApplyId();
            $common['email']=$res->getEmail();
            $common['license_no']=$res->getLicenseNo();
            $common['enterprise_name']=$res->getEnterpriseName();
            $common['company_type']=$res->getCompanyType();
            $common['tax_no']=$res->getTaxNo();
            $common['org_no']=$res->getOrgNo();
            $common['scope']=$res->getScope();
            $common['residence']=$res->getResidence();
            $common['legal_presentative']=$res->getLegalPresentative();
            $common['license_img_url']=$res->getLicenseImgUrl();
            $common['tax_img_url']=$res->getTaxImgUrl();
            $common['org_img_url']=$res->getOrgImgUrl();
            $common['present_front_img_url']=$res->getPresentFrontImgUrl();
            $common['bank_account_name']=$res->getBankAccountName();
            $common['bank_account_number']=$res->getBankAccountNumber();
            $common['bank_branch_name']=$res->getBankBranchName();
            $common['user_phone']=$res->getUserPhone();
            $common['user_email']=$res->getUserEmail();
            $common['apply_type']=$res->getApplyType();
            $common['user_id']=$res->getUserId();
            $common['login_username']=$res->getLoginUsername();
            $common['login_secret']=$res->getLoginSecret();
            $common['qq']=$res->getQq();
            $common['apply_state']=$res->getApplyState();
            $common['store_logo']=$res->getStoreLogo();
            $this->apply=$common;
            $user = get_user_info($this->user_id);
            $this->assign('user',$user);
        }

	}
	
	public function index()
    {
		return $this->fetch();
	}
	
	public function contact()
    {
        if ($this->apply['apply_state'] == 1) redirect(U('Newjoin/apply_info'));
        if (IS_POST) {
            $user_id = cookie('reg_id');
                $var = new Psp\Store\UserContact();
                $var->setProvName(I('post.prov_name'));
                $var->setPhone(I('post.phone'));
                $var->setEmail(I('post.contacts_email'));
                $var->setApplyType((int)I('post.apply_type'));
                $var->setUserId((int)$user_id);
                list($res, $status) = GRPC('sellerstore')->UpdateUserContact($var)->wait();
                $apply_id = $res->getApplyId();
                if (I('post.apply_type') == 2) {
                    $this->redirect(U('Newjoin/basic_info'));
                } else {
                    $this->redirect(U('Newjoin/seller_info', array('apply_type' => 1)));
                }

        }
            $this->assign('apply',$this->apply);
            $this->assign('apply_id',$apply_id);
            return $this->fetch();
    }
	
	public function basic_info(){
		if($this->apply['apply_state'] == 1) redirect(U('Newjoin/apply_info'));
        $data=$this->apply;

        $rate_list = array(0=>0,3=>3,6=>6,7=>7,11=>11,13=>13,17=>17);
        $company_type = array('股份有限公司','个人独立企业','有限责任公司','外资','中外合资','国企','合伙制企业','其它');
        if(IS_POST){
            $var = new Psp\Store\StoreCompany();
            $var->setEnterpriseName(I('post.supplier.company_name'));
            $var->setLicenseNo(I('post.supplier.business_licence_number'));
            $var->setCompanyType((int)I('post.supplier.company_type'));
            $var->setTaxNo(I('post.supplier.attached_tax_number'));
            $var->setOrgNo(I('post.supplier.orgnization_code'));
            $var->setScope(I('post.supplier.business_scope'));
            $var->setLegalPresentative(I('post.supplier.legal_person'));
            $var->setResidence(I('post.supplier.company_address'));
            $var->setApplyId($data['apply_id']);

            list($res,$status) = GRPC('sellerstore')->UpdateCompany($var)->wait();
            $this->redirect(U('Newjoin/seller_info'));
        }

        $this->assign('company_type',$company_type);
		$this->assign('apply',$this->apply);
		$this->assign('rate_list',$rate_list);

        return $this->fetch();
	}
	
	public function agreement(){
		if(!empty($this->apply)){
			if($this->apply['apply_state'] == 2){
				redirect(U('Newjoin/apply_info'));
			}else if($this->apply['apply_state'] == 1 && empty($this->apply['company_name'])){
				redirect(U('Newjoin/basic_info'));
			}else if(empty($this->apply['store_name'])){
				if($this->apply['apply_type'] == 1){
					redirect(U('Newjoin/basic'));
				}else{
					redirect(U('Newjoin/seller_info'));
				}
			}else if($this->apply['apply_state'] == 1 && empty($this->apply['business_licence_cert'])){
				redirect(U('Newjoin/remark'));
			}else{
				redirect(U('Newjoin/apply_info'));
			}
		}
		if(IS_POST){
			$this->redirect(U('Newjoin/contact'));
		}
        return $this->fetch();
	}
	
	public function seller_info()
    {
		if($this->apply['apply_state'] == 1) redirect(U('Newjoin/apply_info'));
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

        /*获取类目列表名称*/
        $list = new Psp\Store\ApplyId();
        $list->setApplyId($this->apply['apply_id']);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassListBy($list)->wait();
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
        $data = new Psp\Store\ApplyId();
        $data->setApplyId(0);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassList($data)->wait();
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
            if ($act[$i]['class_3']==null){
                unset($act[$i]);
            }
        }

        //商家后台地址
        $url = SITE_URL.'/seller';
        $this->assign('url',$url);
        $this->assign('act',$act);
        $this->assign('cat_list',$arr);

        if(IS_POST)
        {
            $list=I('post.');
            $button=$list['store_class_ids'];
            if($button==null){
                $this->error('请选择所属类目',U('Newjoin/seller_info'));
            }else{
                foreach ($button as $val){
                    $cat = explode(',', $val);
                    $bind_class_list[]=array(
                        'class_1'=>$drr[$cat[0]]['category_name'],
                        'class_2'=>$drr[$cat[1]]['category_name'],
                        'class_3'=>$drr[$cat[2]]['category_name'],
                        'value'=>$val,
                        'parent_id'=>(int)$cat[2],
                        'apply_id'=>$this->apply['apply_id'],
                        'class1'=>(int)$cat[0],
                        'class2'=>(int)$cat[1],
                        'class3'=>(int)$cat[2]
                    );
                }


                foreach ($bind_class_list as $k=>$v){
                    $bind[$k]= new Psp\Store\BindClass();
                    $bind[$k]->setParentId($v['parent_id']);
                    $bind[$k]->setClassOne($v['class1']);
                    $bind[$k]->setClassTwo($v['class2']);
                    $bind[$k]->setClassThree($v['class3']);
                    $bind[$k]->setApplyId($v['apply_id']);
                    $bind[$k]->setCategoryName($v['class_3']);
                }
            }


            $data=$this->apply;
            $action = new Psp\Store\StoreCompany();
            $action->setApplyId((int)$data['apply_id']);
            $action->setUserId((int)$data['user_id']);
            $action->setLoginUsername(I('post.sellerName'));
            $action->setLoginSecret(encrypt(I('post.seller_pwd')));
            $action->setQq(I('post.store_person_qq'));
            $action->setShopName(I('post.store_name'));
            $action->setLocationFull(I('post.store_address'));
            $action->setStoreLogo(I('post.store_logo'));

            $action->setBindClass($bind);
            list($res,$status) = GRPC('sellerstore') ->UpdateApply($action)->wait();
            $ret = $res->getRet();
            $msg = $res->getMsg();
            if($ret == 'fail'){
                $this->error("{$msg}");
                exit;
            }

			if($this->apply['apply_type'] == 1){
				redirect(U('Newjoin/apply_info'));
			}else{
				$this->redirect(U('Newjoin/remark'));
			}
		}
//		dump($this->apply);die;
		$this->assign('apply',$this->apply);
		return $this->fetch();
	}
	
	public function query_progress(){
		return $this->fetch();
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
        if($res){
            $res = array('stat'=>'ok',"msg"=>'操作成功');
        }else{
            $res = array('stat'=>'fail','msg'=>'操作失败');
        }

        echo json_encode($res);

    }
	
	public function remark()
    {
		if($this->apply['apply_state'] == 1) redirect(U('Newjoin/apply_info'));
		if(IS_POST)
		{
            $apply['apply_state'] = 1 ;//每次提交资料回到待审核状态
            $data = I('post.');
            $apply=$this->apply;
            $storeInfo = new Psp\Store\StoreCompany();
            $storeInfo->setLicenseImgUrl($data['license_img_url']);//
            $storeInfo->setTaxImgUrl($data['tax_img_url']);//税号图
            $storeInfo->setOrgImgUrl($data['org_img_url']);
            $storeInfo->setPresentFrontImgUrl($data['present_front_img_url']);
            $storeInfo->setEnterpriseName($apply['enterprise_name']);
            $storeInfo->setLicenseNo($apply['license_no']);
            $storeInfo->setCompanyType($apply['company_type']);
            $storeInfo->setTaxNo($apply['tax_no']);
            $storeInfo->setOrgNo($apply['org_no']);
            $storeInfo->setScope($apply['scope']);
            $storeInfo->setLegalPresentative($apply['legal_presentative']);
            $storeInfo->setResidence($apply['residence']);
            $storeInfo->setApplyId($apply['apply_id']);
            list($result,$status) = GRPC('sellerstore') ->updateCompany($storeInfo)->wait();
			$this->success('提交成功',U('Newjoin/apply_info'));
		}

		$this->assign('apply',$this->apply);
		return $this->fetch();
	}
	
	public function apply_info(){
		$this->assign('apply',$this->apply);
		if(IS_POST){
			$paying_amount_cert = I('paying_amount_cert');
			if(empty($paying_amount_cert)){
				$this->error('请上传支付凭证');
			}else{
//				M('store_apply')->where(array('user_id'=>$this->user_id))->save(array('paying_amount_cert'=>$paying_amount_cert));
				$this->success('提交成功');
			}
		}
		return $this->fetch();
	}
	
	public function check_company(){
		$company_name = I('company_name');
		if(empty($company_name)) exit('fail');

        $val = new Psp\Store\CompanyName();
        $val->setCompanyName($company_name);
        list($result,$status) = GRPC('sellerstore') ->GetCompanyName($val)->wait();
       $company['count']=$result->getCount();
       if($company_name && $company['count']>0){
           exit('fail');
       }
//		if($company_name && M('store_apply')->where(array('company_name'=>$company_name,'user_id'=>array('neq',$this->user_id)))->count()>0){
//			exit('fail');
//		}
		exit('success');
	}
	
	public function check_store(){
		$store_name = I('store_name');
		if(empty($store_name)) exit('fail');

        $val = new Psp\Store\StoreName();
        $val->setStoreName($store_name);
        list($result,$status) = GRPC('sellerstore') ->GetStoreName($val)->wait();
        $store['count']=$result->getCount();
        if($store_name && $store['count']>0){
            exit('fail');
        }
//		if(M('store_apply')->where(array('store_name'=>$store_name))->count()>0){
//			exit('fail');
//		}
		exit('success');
	}
	
	public function check_seller(){
		$seller_name = I('seller_name');
		if(empty($seller_name)) exit('fail');

        $val = new Psp\Store\SellerName();
        $val->setSellerName($seller_name);
        list($result,$status) = GRPC('sellerstore') ->GetSellerName($val)->wait();
        $seller['count']=$result->getCount();
        if($seller_name && $seller['count']>0){
            exit('fail');
        }
//		if(M('seller')->where(array('seller_name'=>$seller_name))->count()>0){
//			exit('fail');
//		}
		exit('success');
	}

//	public function question(){
//		$cat_id = I('cat_id');
//	    $article = M('article')->where("cat_id=$cat_id")->select();
//    	if($article){
//    		$parent = M('article_cat')->where(array('cat_id'=>$cat_id))->find();
//    		$this->assign('cat_name',$parent['cat_name']);
//    		$this->assign('article',$article[0]);
//    		$this->assign('article_list',$article);
//    	}
//        return $this->fetch('Article/detail');
//	}
}
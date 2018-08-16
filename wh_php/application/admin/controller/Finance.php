<?php
/**
 * Finance.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/20 0020 下午 1:36
 */

namespace app\admin\controller;

use app\admin\logic\AccountCenterLogic;
use Symfony\Component\Yaml\Tests\A;
use think\Page;
use think\AjaxPage;
use Psp;
use Grpc;
use app\home\logic\UsersLogic;
use think\Db;

class Finance extends Base
{
    public $begin;
    public $end;
    public function _initialize()
    {
        parent::_initialize();

        $payload = validate_json_web_token($_COOKIE['_authtoken']);

        $arr = ['1','2','84','55'];
        if(!in_array($payload['admin_id'],$arr)){
            $this->error('权限不足,请联系管理员');
        }

        if (I('create_time')) {
            $date_ = explode('-',I('create_time'));
            $begin = date('Y-m-d H:i:s',$date_[0]/1000);
            $end = date('Y-m-d H:i:s',$date_[1]/1000);

        } else {
            $begin = date('Y-m-d H:i:s', strtotime("-1 month"));//30天前
            $end = date('Y-m-d H:i:s', strtotime('+1 days'));
        }
        $this->assign('start_time',$begin);
        $this->assign('end_time', $end);
        $this->begin = strtotime($begin);
        $this->end = strtotime($end)+1;
    }

    /**
     * 账户列表
     * @return mixed
     */
    public function account_list(){

        $type = I('type','1');
        $p = I('p',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("account_id");
        $page->setIndex($p);
        $page->setLimit(16);
        $AccountType = new Psp\Account\AccountType();
        $AccountType->setType($type);
        $AccountType->setPagination($page);
        $AccountType->setPlatform(PLATFORM);
        list($res) = GRPC('account')->getAccountByType($AccountType)->wait();
        if (empty($res)) return;

        $accountLogic = new AccountCenterLogic();
        $accountList = $accountLogic->accountList($res->getAccountList());
        //分页处理
        $total_count = $res->getPaginationResult()->getTotalRecords();//每页条数

        $limit_page = $res->getPaginationResult()->getPageSize();//总页数
        if($p == 1){
            adminOperateLog('账户列表',4);
        }
        $this->assign('list', $accountList);
        $Page = new Page($total_count, 8);
        $show = $Page->show();
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 获取子账号列表
     * @return mixed
     */
    public function getAccount(){
        $primary_account = I('primary_account','');
        $p = I('p',1);

        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("account_id");
        $page->setIndex($p);
        $page->setLimit(16);

        $account = new Psp\Account\PrimaryAccount();
        $account->setPrimaryAccount($primary_account);
        $account->setPagination($page);

        list($reply) = GRPC('account')->getAccountByPrimaryAccount($account)->wait();
        if (empty($res)) return;
        $accountLogic = new AccountCenterLogic();
        $accountList = $accountLogic->accountList($reply->getAccountList());
        if($p == 1){
            adminOperateLog('子账户列表',4);
        }
        $this->assign('list', $accountList);
        return $this->fetch('account_list');


    }

    /**
     * 账户转账记录
     * @return mixed
     */
    public function transfer(){

        $out_id = I('TransfereeId',0);
        $in_id = I('TransferorId',0);
        $state = I('state',-1);
        $type = I('type',0);
        $p = I('p',1);
        $limit =16;
        $condition = new Psp\Account\GetTransferCondition();
        $condition->setMinBeginDate(grpcTime($this->begin));
        $condition->setMaxBeginDate(grpcTime($this->end));
        $condition->setPlantformId(PLATFORM);
        $condition->setTransfereeId($out_id);
        $condition->setTransferorId($in_id);
        $condition->setState($state);
        $condition->setType($type);

        $condition->setPagination(grpcPage('transfer_id',$p,$limit,true));

        list($res) = GRPC('account')->getTransferByCondition($condition)->wait();
        if (empty($res)) return;
        $logic = new AccountCenterLogic();
        $transferlist = $logic->transferList($res->getTransferList());
        if($p == 1){
            adminOperateLog('账户转账记录',4);
        }
//        dump($res);exit;
        $this->assign('list',$transferlist);
        if(!empty($transferlist)){
            //分页处理
            $total_count = $res->getPaginationResult()->getTotalRecords();//每页条数
            $Page = new Page($total_count,$limit);
            $show = $Page->show();
            $this->assign('page', $show);
        }
        return $this->fetch();
    }

    /**
     * 商家结算记录列表
     * @return mixed
     */
    public function providerSetlement(){
        $p = I('p',1);//当前页
        $provider_name = I("provider_name",false);

        $condition = new Psp\Account\GetSettlementCondition();
        $condition->setMinBeginDate(grpcTime($this->begin));
        $condition->setMaxBeginDate(grpcTime($this->end));
        $condition->setPlantformId(PLATFORM);

        if($provider_name){//检测到根据店铺名称查找店铺id
            $provider = 1;//===============================需要查询
            $condition->ProviderId($provider);
        }

        $condition->setPagination(grpcPage('settlement_id',$p,10,true));

        list($reply) = GRPC(account)->getAccountSettlementList($condition)->wait();
        if($p == 1){
            adminOperateLog('商家结算记录',4);
        }
        if (empty($reply)) return;
        return $this->fetch();
    }



    public function withdrawals(){
        $p = I('p',1);
        $status = I('status','-1');
        $tps138id = I('tps138_user_id','');
        $ex_account_name = I('ex_account_name','');
        $ex_account_info = I('ex_account_info','');
        $logic = new AccountCenterLogic();

//        if(!IS_POST){//默认页面
//            list($reply) = GRPC('account')->getMemberWithdrawList(grpcPage('withdraw_id',$p,'20',false))->wait();
//
//        }else{//条件筛选
        if($tps138id) $uid = $logic->getUid($tps138id);
        $request = new Psp\Account\MemberWithdrawListByConditionRequest();
        $request->setStatus($status);
        $request->setMinBeginDate(grpcTime($this->begin));
        $request->setMaxBeginDate(grpcTime($this->end));
        $request->setExAccountName($ex_account_name);
        $request->setExAccountInfo($ex_account_info);
        $request->setReceiptorId($uid);
        $request->setPagination(grpcPage('withdraw_id',$p,'20',true));
        list($reply) = GRPC('account')-> getMemberWithdrawListByCondition($request)->wait();

//        }
        if (empty($reply)) return;
        $res = $logic->withdrawList($reply->getWithdrawList());

        if($p == 1){
            adminOperateLog('提现记录列表',4);
        }

        if(!empty($reply->getPaginationResult())){

            //分页处理
            $total_count = $reply->getPaginationResult()->getTotalRecords();//每页条数

            $Page = new Page($total_count,20);
            $show = $Page->show();
            $this->assign('page', $show);
        }
        $this->assign('list', $res);
        return $this->fetch();

    }



    /**
     * 商家提现申请列表
     * @return mixed
     */
    public function store_withdrawals(){

        $p = I('p',1);
        $status = I('status');
        $store_name = I('store_name','');
        $ex_account_name = I('ex_account_name','');
        $ex_account_info = I('ex_account_info','');

        $logic = new AccountCenterLogic();
        //默认页面
        if(!IS_POST){
            list($reply) = GRPC('account')->getAccountWithdrawList(grpcPage('withdraw_id',$p,20))->wait();

            //条件筛选
        }else{
            $request = new Psp\Account\AccountWithdrawListByConditionRequest();
            $request->setStatus($status);
            $request->setStoreName($store_name);
            $request->setExAccountName($ex_account_name);
            $request->setExAccountInfo($ex_account_info);
            $request->setMinBeginDate(grpcTime($this->begin));
            $request->setMaxBeginDate(grpcTime($this->end));
            $request->setPagination(grpcPage('withdraw_id',$p,20));
            list($reply) = GRPC('account')->getAccountWithdrawListByCondition($request)->wait();

        }
        if (empty($reply)) return;
        $res = $logic->withdrawList($reply->getWithdrawList());
        if(!empty($reply->getPaginationResult())){
            //分页处理
            $total_count = $reply->getPaginationResult()->getTotalRecords();//每页条数
            $limit_page = $reply->getPaginationResult()->getPageSize();//总页数
            $Page = new Page($total_count,20);
            $show = $Page->show();
            $this->assign('page', $show);

        }
        if($p == 1){
            adminOperateLog('商家提现申请',4);
        }

        $this->assign('list', $res);

        return $this->fetch();
    }

    /**
     * 审核结算订单
     * @return mixed
     */
    public function checkWithdrawals(){

        $provider_id = I('provider_id');
        $period_id = I('period_id');
        $check[withdraw_id] = I('withdraw_id');

        $p = I('p',1);
        $request = new Psp\Account\GetWithdraw();
        $request->setProviderId($provider_id);
        $request->setPeriodId($period_id);
        $request->setPagination(grpcPage('id',$p,20));

        list($reply) = GRPC('account')->getWithdrawOrder($request)->wait();
        if (empty($reply)) return;
        $logic = new AccountCenterLogic();
        $data = $logic->settlementDetail($reply->getProviderSettlementDetial());

        if($data){
            //分页处理

            $total_count = $reply->getPaginationResult()->getTotalRecords();//总条数

            $limit_page = $reply->getPaginationResult()->getPageSize();//总页数

            $Page = new Page($total_count,20);
            $show = $Page->show();


            $this->assign('page', $show);
        }

        if($p == 1){
            adminOperateLog('审核结算订单',4);
        }

        if($p == $Page->totalPages)  $check[end] = 1;
        $this->assign('check', $check);
        $this->assign('list',$data);
        return $this->fetch();

    }

    public function updatewithdrawstatus(){

        $payload = validate_json_web_token(cookie('_authtoken'));

        $status = I('status');
        $note = I('admin_note');
        $withdraw_id = I('withdraw_id');

        $admin = $payload[admin_id];
        $logic = new AccountCenterLogic();
        $res = $logic->checkWithdraw($withdraw_id,$status,$admin,$note);

        adminOperateLog('审核提现',4);

        if($res) $this->success('操作成功',U('store_withdrawals'));
    }

    public function withdrawals_update(){
        $payload = validate_json_web_token(cookie('_authtoken'));
        $admin = $payload[admin_id];
        $withdraw_ids = explode(',',I('id'));
        $status = I('status');
        $logic = new AccountCenterLogic();
        foreach ($withdraw_ids as $v){
            $res = $logic->checkWithdraw($v,$status,$admin,'同意申请');
            if(!$res) exit(json_encode(array('status'=>2,'msg'=>'批量审核失败,只能操作状态为申请中的提现记录')));
        }
        adminOperateLog('批量审核提现',4);
        echo json_encode(array('status'=>1,'msg'=>'批量审核成功'));
    }

    public function store_remittance(){
        //搜索条件
        $shop_name = I('store_name');  //店铺名称
        $account_no = I('account_bank'); //收款账号
        $account_name = I('account_name'); //收款人
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));  //转账完成的时间段

        //分页页码
        $p=I('p/d',1);

        $client = GRPC('account');

        $page = grpcPage('receipt_id',$p,20,false);


        $receipt = new Psp\Account\StoreRemittanceCondition();

        $receipt->setPagination($page);
        $shop_name&&$receipt->setStoreName($shop_name);
        $account_no&&$receipt->setAccountNo($account_no);
        $account_name&&$receipt->setAccountName($account_name);
        $start_time&&$receipt->setBeginDate(grpcTime($start_time));
        $end_time&&$receipt->setEndDate(grpcTime($end_time));

        list($res,$status) = $client->getStoreRemitanceList($receipt)->wait();

        if($res){
            foreach ($res->getStoreRemittanceList() as $k=>$v) {

                $data[$k]['receipt_id'] = $v->getRemittanceId();
                $data[$k]['provider_id'] = $v->getProviderId();
                $data[$k]['shop_name'] = $v->getShopname();
                $data[$k]['bank_name']= $v->getBankName();
                $data[$k]['account_no']=$v->getAccountNo();
                $data[$k]['account_name'] = $v->getAccountName();
                $data[$k]['money'] = $v->getMoney();

                $data[$k]['date'] = $v->getDate()?$v->getDate()->getSeconds():0;
                $data[$k]['note'] = $v->getNote();

            }
            //dump($data);die;

            //获得总条数
            $count=$res->getPaginationResult()->getTotalRecords();
            $Page  = new Page($count,20);
            $show = $Page->show();
        }
        if($p == 1){
            adminOperateLog('商家汇款记录',4);
        }


        $this->assign('list',$data);
        $this->assign('pager',$page);
        $this->assign('page',$show);// 赋值分页输出


        return $this->fetch();
    }

    public function remittance(){
        return $this->fetch();
    }


    public function editMemberWithdrawals(){

        $ownerId= I('id',1);
        $money = I('withdraw_money',0);

        $data = I('get.');

        $obj = new Psp\Account\OwnerId();
        $obj->setOwnerId($ownerId);
        $obj->setPlatform(PLATFORM);
        $obj->setType(-1);
        $obj->setBizType(-1);
        $obj->setPagination(grpcPage('account_id',1,10));

        list($reply) = GRPC('account')->getAccountBywnerOwner($obj)->wait();
        if (empty($reply)) return;
        $accountLogic = new AccountCenterLogic();
        $accountList = $accountLogic->accountList($reply->getAccountList());
        $this->assign('list',$accountList);

        foreach ($accountList as $key =>$val){
            if($val[type]==4&&$val[biz_type]){
                $data[sum_amount] +=$val['balance'];
                $data[extractable_amount]+=$val[extractable_balance];
                if($val['biz_type']==3&&$val['balance']!=$money){
                    $data['msg'] = "该笔提现有问题,请谨慎处理";
                }

            }
        }
        //取出会员详情
        $params = new Psp\User\Mid();
        $params->setOrgId(1);//鸡肋
        $params->setMemberId($ownerId);
        list($reply,$status) = GRPC('user')->GetMemberInfo($params)->wait();
        $data['user_id'] = $reply->getUserInfo()->getUserId();
        $data['nick_name'] = $reply->getUserInfo()->getName();
        $data['tps138_id'] = $reply->getUserInfo()->getTps138Id();
        $data['store_prefix'] = $reply->getUserInfo()->getStorePrefix();
        $data['email'] = $reply->getUserInfo()->getEmail();
        $data['mobile'] = $reply->getUserInfo()->getPhone();
        $data['qq'] = $reply->getUserInfo()->getQq();
        $data['status'] = $reply->getUserInfo()->getStatus();
        $data['sex'] = $reply->getUserInfo()->getSex();
        $data['token'] = $reply->getUserInfo()->getToken();
        $data['upgrade_time'] = $reply->getUserInfo()->getUpdateTime()->getSeconds();//等级达成时间
        $data['user_title'] = $reply->getUserInfo()->getUserTitle();//会员职称
        $data['region_id'] = $reply->getUserInfo()->getRegionId();//国别
        $data['role_id'] = $reply->getUserInfo()->getRoleId();//角色id
        $data['org_id'] = $reply->getUserInfo()->getOrgId();//组织id
        $data['user_create_time'] = $reply->getUserInfo()->getCreateTime()->getSeconds();//创建时间
        $data['email_validated'] = $reply->getUserInfo()->getIsVerifiedEmail();//是否验证邮箱
        $user['mobile_validated'] = $reply->getUserInfo()->getIsVerifiedMobile();//是否验证手机
        adminOperateLog('编辑会员提现',4);
        $this->assign('data',$data);

        return $this->fetch();
    }

    public function editStoreWithdrawals(){
        $withdraw_id = I('id');
        $logic = new AccountCenterLogic();
        $info = $logic ->getWithdraw($withdraw_id);
        adminOperateLog('编辑商家提现',4);
        $this->assign('data',$info);
        return $this->fetch();
    }



    public function ajax_remittance(){
        //搜索条件
        $wh_id = I('user_id');  //wh_id
        $account_no = I('account_bank'); //收款账号
        $account_name = I('account_name'); //收款人
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));  //转账完成的时间段

        //分页页码
        $p=I('p/d',1);

        $client = GRPC('account');

        $page = grpcPage('receipt_id',$p,20,false);


        $receipt = new Psp\Account\RemitanceCondition();

        $receipt->setType(1);
        $receipt->setPagination($page);
        $wh_id&&$receipt->setWhId($wh_id);
        $account_no&&$receipt->setAccountNo($account_no);
        $account_name&&$receipt->setAccountName($account_name);
        $start_time&&$receipt->setBeginDate(grpcTime($start_time));
        $end_time&&$receipt->setEndDate(grpcTime($end_time));

        list($res,$status) = $client->getRemitanceList($receipt)->wait();


        foreach ($res->getRemittanceList() as $k=>$v) {

            $data[$k]['receipt_id'] = $v->getRemittanceId();
            $data[$k]['wh_id'] = $v->getWhId();
            $data[$k]['nickname'] = $v->getNickname();
            $data[$k]['bank_name']= $v->getBankName();
            $data[$k]['account_no']=$v->getAccountNo();
            $data[$k]['account_name'] = $v->getAccountName();
            $data[$k]['money'] = $v->getMoney();

            $data[$k]['date'] = $v->getDate()?$v->getDate()->getSeconds():0;
            $data[$k]['note'] = $v->getNote();

        }
        //dump($data);die;

        //获得总条数
        $count=$res->getPaginationResult()->getTotalRecords();

        if($p == 1){
            adminOperateLog('会员汇款记录',4);
        }
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();
        $this->assign('list',$data);
        $this->assign('pager',$page);
        $this->assign('page',$show);// 赋值分页输出




        return $this->fetch();
    }

    public function pay_out(){
        $withdrawids = I('withdrawids');
        $withdrawids = explode(',',$withdrawids);
        $logic = new AccountCenterLogic();

        foreach ($withdrawids as $k=>$v){
            $withdraw = $logic->getWithdraw($v);
            if($withdraw['status']==1)$withdrawdata[$k]=$withdraw;//只操作审核通过的记录
            if($withdraw['type']==1 || $withdraw['type']==3)$url =U("withdrawals"); //跳转到会员提现列表
            if($withdraw['type']==2)$url =U("store_withdrawals"); //跳转到商家提现列表
            $result = $logic->getReceiptOut($withdraw['withdraw_id']);
            if($result['status'] == 1)//所选记录中存在已转账的记录
            {
                $this->error("$result[msg]",$url);
                exit;
            }
            if($withdraw['type']==1)$withdraw['WithdrawMoney']=round($withdraw['WithdrawMoney']*0.99,2); //会员收益提现
            if($withdraw['type']==3){
                $accountData = $logic->getAccount($withdraw['receiptor_id'],1,0,(int)PLATFORM);  //会员余额提现
                $extra_banlance = $accountData['extractable_balance'];
                if($extra_banlance<$withdraw['WithdrawMoney']){
                    $withdraw['WithdrawMoney'] = round($extra_banlance + ($withdraw['WithdrawMoney']-$extra_banlance)*0.99,2);
                }
            }
            $withdrawdata[$k]['WithdrawMoney'] = $withdraw['WithdrawMoney'];
            if($result['status'] == 2)//所选记录中存在该记录 但是未转账
            {
                continue;
            }
            if($withdraw['status']==1){

                $logic->addPayOut($withdraw['type'],$withdraw['WithdrawMoney'],$withdraw['receiptor_id'],$withdraw['withdraw_id']);//往支出表里添加数据 (判断数据库中是否添加过这条数据,以免重复添加)
            }

        }

        if(empty($withdrawdata)){//所选记录审核状态不符
            $this->error("所选记录审核状态不符",$url);
            exit;
        }
        adminOperateLog('发起转账',4);

        $param =$this->build_param($withdrawdata);
        include_once  "plugins/payment/alipay/alipay.class.php"; //引入支付类
        $payment = new \alipay();
        $payment->transfer($param); //发起批量转账请求
    }

    //拼接批量转账的信息
    public function build_param($message){
        /******************构建请求需要的参数******************/
        $time = date("Ymd", time());//付款当天日期
        $rand = rand(1,100000);
        $number = sprintf("%'.05d", $rand);
        $data['batch_number'] = $time.$number; //付款批次号
        foreach ($message as $k=>$v){
            //$v['money']=$v['money']*(1-$v['deduct_point']/100); //扣点
            //$v['money']=round($v['WithdrawMoney']*(1-0.03),2);//保留两位小数
            $data['detail_data'] .= "{$v['withdraw_id']}^{$v['ex_account_info']}^{$v['ex_account_name']}^{$v['WithdrawMoney']}^商家提现{$v['withdraw_id']}|";//将提现申请的id传递进去
            $data['batch_fee'] += $v['WithdrawMoney']; //转账总金额
        }
        $data['detail_data'] = substr($data['detail_data'], 0,-1); //去除最后一个/

        $data['batch_num']= count($message); //转账的笔数


        return $data;
    }

    public function receipt_out(){
        $p = I('p',1);
        $type = I('type',-1);
        $status = I('status');
        $platform_id = I('platform_id',1);
        $operator_id = I('operator_id',0);
        $receiptor_id = I('receiptor_id',0);
        $time_select_type = I('time_select_type',1);

        $req = new Psp\Account\getPayOutListRequest();
        $req->setOperatorId($operator_id);
        $req->setStatus($status);
        $req->setType($type);
        $req->getPlatformId($platform_id);
        $req->getReceiptorId($receiptor_id);
        $req->setTimeSelectType($time_select_type);
        $req->setMinDate(grpcTime($this->begin));
        $req->setMaxDate(grpcTime($this->end));
        $req->setPagination(grpcPage('issue_date',$p,20));

        list($reply) = GRPC('account')->getPayOutList($req)->wait();
        if (empty($reply)) return;
        $logic = new AccountCenterLogic();
        foreach ($reply as $k => $v){
            $data[$k] = $logic->payOutTable($v->getPayOutList());
        }
//        dump($data);
        if($p == 1){
            adminOperateLog('对外支出列表',4);
        }

        return $this->fetch();
    }

    public function providerPeriodic(){

        $logic = new AccountCenterLogic();
        $data = $logic->getProviderPeriodic(I('get.apply_id'));
        $data['provider_id'] = I('get.apply_id');
        $data['store_name'] = I('get.store_name');
        $data['seller_name'] = I('get.seller_name');
        $data['name'] = I('get.name');

        $this->assign('data',$data);
        if(IS_POST){

            $res = $logic->addEditProviderPeriodic($_POST);
            adminOperateLog('商家结算',4);
            if($res){
                $this->success('提交成功');
            }else{
                $this->error('提交失败');
            }
        }
        return $this->fetch();

    }

    public function test(){

        return $this->fetch();
    }

    //获取会员信息
    public function ajaxGetMemberInfo(){
        $uid = I('post.user_id');
        $logic = new UsersLogic();
        $user = $logic->get_info($uid);
        $user['result']['user_money'] = sprintf("%.2f",$user['result']['user_money']);
        $user['result']['add_time'] = date('Y-m-d H:i',$user['result']['add_time']);
        $user['result']['birthday'] = date('Y-m-d H:i',$user['result']['birthday']);
        $title = array(0=>'乡镇代理(LZ0)',1=>'县级代理(LZ1)',2=>'市级代理(LZ2)',3=>'省级代理(LZ3)',4=>'大区代理(LZ4)',5=>'全国代理(LZ5)',6=>'全球总代理(LZ6)',-1=>'乡镇代理(LZ0)');
        $level = array(0=>'普通会员',1=>'青铜会员',2=>'白银会员',3=>'铂金会员',4=>'钻石会员',-1=>'普通会员');
        $status = array(0=>'禁用',1=>'启用');
        $sex = array(0=>'保密',1=>'男',2=>'女');
        $user['result']['status'] = $status[$user['result']['status']];
        $user['result']['user_title'] =$title[$user['result']['user_title']];
        $user['result']['user_level'] =$level[$user['result']['user_level']];
        $user['result']['sex'] =$sex[$user['result']['sex']];
        $this->ajaxReturn($user['result']);
    }

    //获取提现汇总信息
    public function  getWithdrawTotal(){
        if(I('start_time')){
            $start_time = strtotime(I('start_time'));
            $end_time = strtotime(I('end_time'));
        }else{
            $start_time = strtotime(date('Y-m-d H:i:s', strtotime("-1 month")));//30天前
            $end_time = strtotime(date('Y-m-d H:i:s', strtotime('+1 days')));
        }

        $type = I('type',0);
        $req = new Psp\Account\receiptOutCondition();

        $req->setType($type);
        $req->setStartTime(grpcTime($start_time));
        $req->setEndTime(grpcTime($end_time));


        list($reply) = GRPC('account')->getReceiptOutTotal($req)->wait();
        if($reply){
            $data['total_amount'] = $reply->getTotalAmount();
            $data['total_sum'] = $reply->getTotalSum();
            $data['total_fail'] = $reply->getTotalFail();
            $data['fail_sum'] = $reply->getFailSum();
            $data['today_total'] = $reply->getTodayTotal();
            $data['today_sum'] = $reply->getTodaySum();
            $data['today_fail'] = $reply->getTodayFail();
            $data['today_fail_sum'] = $reply->getTodayFailSum();
            $data['shenqing'] = $reply->getShenqing();
            $data['shenqing_amount'] = $reply->getShenqingAmount();
            $data['today_shenqing'] = $reply->getTodaySheniqng();
        }

//饼状图数据
        $AllFund = new Psp\Account\TransfersCondition();
        $AllFund->setStartTime(grpcTime($start_time));
        $AllFund->setEndTime(grpcTime($end_time));
        list($reply) = GRPC('account')->getAllFundStatus($AllFund)->wait();
        if ($reply){
            $user_sum_profit = $reply->getMoney();
            $user_profit_sum = $reply->getBalance();
            $user_money_sum = $reply->getWallet();
            $receipt_out_sum = $reply->getAmount();
            $order_sum = $reply->getTotalAmount();
            $return_sum = $reply->getReturnAmount();
        }

        $info1 = array('in_profit'=>$user_sum_profit,//分红总金额
            'profit_surplus'=>$user_profit_sum,//账户总额的总和
            'wallet'=>$user_money_sum,//钱包金额
            'out_withdraw'=>$receipt_out_sum,//提款总额
            'out_pay_order'=>$order_sum,//订单总金额的总和
            'in_order_return'=>$return_sum,//退换货金额的总和
            );
        $this->assign('info1',$info1);
        //柱状图数据
        $Transfer = new Psp\Account\TransfersCondition();
        $Transfer->setStartTime(grpcTime($start_time));
        $Transfer->setEndTime(grpcTime($end_time));
        list($reply) = GRPC('account')->getFundTransfers($Transfer)->wait();
        if($reply){
            foreach ($reply->getFundTransfer() as $k=>$v) {
                $transfer_info[$k]['income_withdrawals'] = $v->getIncomeWithdrawals();//总收益
                $transfer_info[$k]['balance_withdrawals'] = $v->getBalanceWithdrawals();//总余额
                $transfer_info[$k]['transfer_amount'] = $v->getTransferAmount();//总转出金额
                $transfer_info[$k]['failure_amount'] = $v->getFailureAmount();//总失败金额
                $transfer_info[$k]['unprocessed_amount'] = $v->getUnprocessedAmount();//未处理金额
                $transfer_info[$k]['fail_sum'] = $v->getFailSum();//转出失败总笔数
                $transfer_info[$k]['success_sum'] = $v->getSuccessSum();//转出成功总笔数
                $transfer_info[$k]['sum'] = $v->getSum();//总笔数
                $transfer_info[$k]['tody_time'] = $v->getTodyTime();//今日时间
            }
        }
        if ($transfer_info){
            foreach ($transfer_info as $val){//时间对应数据
                $income_withdrawals[$val['tody_time']] = $val['income_withdrawals'];
                $balance_withdrawals[$val['tody_time']] = $val['balance_withdrawals'];
                $transfer_amount[$val['tody_time']] = $val['transfer_amount'];
                $failure_amount[$val['tody_time']] = $val['failure_amount'];
                $unprocessed_amount[$val['tody_time']] = $val['unprocessed_amount'];
                $fail_sum[$val['tody_time']] = $val['fail_sum'];
                $success_sum[$val['tody_time']] = $val['success_sum'];
                $sum[$val['tody_time']] = $val['sum'];
            }
        }

        for($i=$start_time;$i<=$end_time;$i=$i+24*3600){//时间段缺少的天数及数据自动补充为0
            $income = empty($income_withdrawals[date('Y-m-d',$i)]) ? 0 : $income_withdrawals[date('Y-m-d',$i)];
            $balance = empty($balance_withdrawals[date('Y-m-d',$i)]) ? 0 : $balance_withdrawals[date('Y-m-d',$i)];
            $transfer = empty($transfer_amount[date('Y-m-d',$i)]) ? 0 : $transfer_amount[date('Y-m-d',$i)];
            $failure = empty($failure_amount[date('Y-m-d',$i)]) ? 0 : $failure_amount[date('Y-m-d',$i)];
            $unprocessed = empty($unprocessed_amount[date('Y-m-d',$i)]) ? 0 : $unprocessed_amount[date('Y-m-d',$i)];
            $fail = empty($fail_sum[date('Y-m-d',$i)]) ? 0 : $fail_sum[date('Y-m-d',$i)];
            $success = empty($success_sum[date('Y-m-d',$i)]) ? 0 : $success_sum[date('Y-m-d',$i)];
            $all = empty($sum[date('Y-m-d',$i)]) ? 0 : $sum[date('Y-m-d',$i)];
            $income_withdrawals_arr[] = $income;
            $balance_withdrawals_arr[] = $balance;
            $transfer_amount_arr[] = $transfer;
            $failure_amount_arr[] = $failure;
            $unprocessed_amount_arr[] = $unprocessed;
            $fail_sum_arr[] = $fail;
            $success_sum_arr[] = $success;
            $sum_arr[] = $all;
            $date = date('Y-m-d',$i);
            $day[] = $date;
        }
        $result = array('income_withdrawals'=>$income_withdrawals_arr,
            'balance_withdrawals'=>$balance_withdrawals_arr,
            'transfer_amount'=>$transfer_amount_arr,
            'failure_amount'=>$failure_amount_arr,
            'unprocessed_amount'=>$unprocessed_amount_arr,
            'fail_sum'=>$fail_sum_arr,
            'success_sum'=>$success_sum_arr,
            'sum'=>$sum_arr,
            'time'=>$day);

        adminOperateLog('提现汇总信息',4);
        $this->assign('result',json_encode($result));
        $this->assign('data',$data);
        return $this->fetch();

    }

}

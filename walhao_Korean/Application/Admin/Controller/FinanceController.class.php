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
 * Author: IT宇宙人
 *
 * Date: 2016-03-09
 */

namespace Admin\Controller;
use Think\Page;

class FinanceController extends BaseController {

    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
    }


    public function user_transfer(){
        $model = M("");
        $tr_138_id = I('tr_138_id');
        $ac_138_id = I('ac_138_id');

        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['u.create_time'] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1])));

        $tr_138_id && $where['s1.tp138_user_id'] = $tr_138_id;
        $ac_138_id && $where['s2.tp138_user_id'] = $ac_138_id;



        $count = $model->table(C('DB_PREFIX').'user_transfer_log u')
            ->field("u.*,s1.tp138_user_id tr_138,s1.nickname tr_nick,s2.tp138_user_id ac_138,s2.nickname ac_nick")
            ->join('INNER JOIN __USERS__ s1 ON u.transfer_id = s1.user_id INNER JOIN __USERS__ s2 on u.acceptor_id=s2.user_id')->where($where)->count();
        $Page  = new Page($count,10);
        $list = $model->table(C('DB_PREFIX').'user_transfer_log u')
            ->field("u.*,s1.tp138_user_id tr_138,s1.nickname tr_nick,s1.wh_id tr_wh,s2.tp138_user_id ac_138,s2.nickname ac_nick,s2.wh_id ac_wh")
            ->join('INNER JOIN __USERS__ s1 ON u.transfer_id = s1.user_id INNER JOIN __USERS__ s2 on u.acceptor_id=s2.user_id')->where($where)
            ->order("u.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
//dump($list);die;
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }


    /**
     *  店家转账汇款记录
     */
    public function store_remittance(){
        $model = M("");
        $store_id = I('store_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');

        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['sr.create_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
        $store_id && $where['s.store_id'] = $store_id;
        $account_bank && $where['sr.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['sr.account_name'] = array('like','%'.$account_name.'%');

        $where['sr.status'] = 1;

        $count = $model->table(C('DB_PREFIX').'store_remittance sr')->join('INNER JOIN __STORE__ s ON s.store_id = sr.store_id')->where($where)->count();
        $Page  = new Page($count,10);
        $list = $model->table(C('DB_PREFIX').'store_remittance sr')->join('INNER JOIN __STORE__ s ON s.store_id = sr.store_id')->where($where)->order("sr.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }
    /**
     *  转账汇款记录
     */
    public function remittance(){
        $this->display();
    }

    public function ajax_remittance(){
        $model = M();
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y/m/d',strtotime('-6 day')).'-'.date('Y/m/d',strtotime('+1 day'));

        $create_time2 = explode('-',$create_time);
        //$where['w.create_time'] =  array(array('gt', strtotime($create_time2[0]), array('lt', strtotime($create_time2[1]))));
        $user_id && $where['u.user_id'] = $user_id;
        $account_bank && $where['w.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['w.account_name'] = array('like','%'.$account_name.'%');

        if(isset($_GET['act']) && $_GET['act']=='export'){
            $remittanceList = $model->table(C('DB_PREFIX').'remittance w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->order("w.id desc")->select();
            $strTable ='<table width="500" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">申请人</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="100">提现金额</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行账号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户人姓名</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请时间</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">提现备注</td>';
            $strTable .= '</tr>';
            if(is_array($remittanceList)){
                foreach($remittanceList as $k=>$val){
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['nickname'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['money'].' </td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].'</td>';
                    $strTable .= '<td style="vnd.ms-excel.numberformat:@">'.$val['account_bank'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['account_name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val['create_time']).'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['remark'].'</td>';
                    $strTable .= '</tr>';
                }
            }
            $strTable .='</table>';
            unset($remittanceList);
            downloadExcel($strTable,'remittance');
            exit();
        }
        $count = $model->table(C('DB_PREFIX').'remittance w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->count();
        $Page  = new Page($count,20);
        $list = $model->table(C('DB_PREFIX').'remittance w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }

    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
        $model = M();
        $status = I('status');
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);

        $where['w.create_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
        if($status === '0' || $status > 0)
            $where['w.status'] = $status;
        $user_id && $where['u.user_id'] = $user_id;
        $account_bank && $where['w.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['w.account_name'] = array('like','%'.$account_name.'%');

        $count = $model->table(C('DB_PREFIX').'withdrawals w')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->count();
        $Page  = new Page($count,20);
        $list = $model->table(C('DB_PREFIX').'withdrawals w')->field('u.user_id,u.nickname,w.*')->join('INNER JOIN __USERS__ u ON u.user_id = w.user_id')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }
    /**
     * 商家提现申请记录
     */
    public function store_withdrawals()
    {
        $model = M("store_withdrawals");
        $store_id = I('store_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');

        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['sw.create_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
        $store_id && $where['s.store_id'] = $store_id;
        $account_bank && $where['sw.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['sw.account_name'] = array('like','%'.$account_name.'%');

        $count = $model->table(C('DB_PREFIX').'store_withdrawals sw')->join('INNER JOIN __STORE__ s ON s.store_id = sw.store_id')->where($where)->count();
        $Page  = new Page($count,4);
        $list = $model->table(C('DB_PREFIX').'store_withdrawals sw')->join('INNER JOIN __STORE__ s ON s.store_id = sw.store_id')->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }

    /**
     * 删除申请记录
     */
    public function delStoreWithdrawals()
    {
        // $model = M("store_withdrawals");
        // $model->where('id ='.$_GET['id'])->delete();
        // M('settlement_order')->where(array('store_withdraws_id'=>$_GET['id'],'status'=>0))->delete();
        // $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $return_arr = array('status' => 1,'msg' => '删除失败，暂停删除，请联系技术人员','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 修改编辑商家 申请提现
     */
    public  function editStoreWithdrawals(){
        $id = I('id');
        $model = M("store_withdrawals");
        $withdrawals = $model->find($id);
        $store = M('store')->where("store_id = {$withdrawals[store_id]}")->find();

        M('settlement_order so')->where(array("so.store_id"=>$store['store_id'],"so.is_withdraw"=>0,'so.status'=>1))->save(array('store_withdraws_id'=>$id));//每笔结算金额和订单关联

        if(IS_POST)
        {
            $model->create();

            // 如果是已经给店家转账 则生成转账流水记录
            if($model->status == 1 && $withdrawals['status'] != 1)
            {
                if($store['store_money'] < $withdrawals['money'])
                {
                    $this->error("店家余额不足{$withdrawals['money']}，不够提现");
                    exit;
                }

                //申请成功 更新settlement_order表里的提现状态
                M('settlement_order')->where(array(
                    'store_withdraws_id'=>$id,
                    'status'  => 1
                ))->save(array(
                    'is_withdraw'=> 1
                ));

                storeAccountLog($withdrawals['store_id'], ($withdrawals['money'] * -1),0,$desc = '平台提现');
                $remittance = array(
                    'store_id' => $withdrawals['store_id'],
                    'bank_name' => $withdrawals['bank_name'],
                    'account_bank' => $withdrawals['account_bank'],
                    'account_name' => $withdrawals['account_name'],
                    'money' => $withdrawals['money'],
                    'status' => 0,
                    'create_time' => time(),
                    'admin_id' => session('admin_id'),
                    'withdrawals_id' => $withdrawals['id'],
                    'remark'=>$model->remark,
                );
                M('store_remittance')->add($remittance);
            }else{
                //拒绝体现 更新settlement_order表里的申请状态
                M('settlement_order')->where(array(
                    'store_withdraws_id'=>$id
                ))->save(array(
                    'status'=> 0
                ));
                $data = M('settlement_order')->where(array('store_withdraws_id'=>$id))->group('order_id')->select();
                foreach ($data as $k=>$v){
                    unset($v['rec_id']);
                    unset($v['status']);
                    M('settlement_order')->add($v);
                }
            }
            $model->save();
            $this->success("操作成功!",U('store_remittance'),3);
            exit;
        }
        $this->assign('store',$store);
        $this->assign('data',$withdrawals);
        $this->display();
    }

    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {
        $model = M("withdrawals");
        $model->where('id ='.$_GET['id'])->delete();
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 修改编辑 申请提现
     */
    public  function editWithdrawals(){
        $id = I('id');
        $model = M("withdrawals");
        $withdrawals = $model->find($id);
        $user = M('users')->where("user_id = {$withdrawals[user_id]}")->find();
        if(IS_POST)
        {
            $model->create();

            // 如果是已经给用户转账 则生成转账流水记录
            //accountLog($withdrawals['user_id'], ($withdrawals['money'] * -1), 0,"平台提现");
            if($model->status == 1 && $withdrawals['status'] != 1)
            {
                if($user['user_money']+$withdrawals['money'] < $withdrawals['money'])
                {
                    $this->error("用户余额不足{$withdrawals['money']}，不够提现");
                    exit;
                }
                $remittance = array(
                    'user_id' => $withdrawals['user_id'],
                    'wh_id' => $user['wh_id'],
                    'bank_name' => $withdrawals['bank_name'],
                    'account_bank' => $withdrawals['account_bank'],
                    'account_name' => $withdrawals['account_name'],
                    'money' => $withdrawals['money'],
                    'status' => 1,
                    'create_time' => time(),
                    'admin_id' => session('admin_id'),
                    'withdrawals_id' => $withdrawals['id'],
                    'remark'=>$model->remark,
                );
                M('remittance')->add($remittance);
            }
            $model->save();
            $this->success("操作成功!",U('remittance'),3);
            exit;
        }
        if($user['nickname'])
            $withdrawals['user_name'] = $user['nickname'];
        elseif($user['email'])
            $withdrawals['user_name'] = $user['email'];
        elseif($user['mobile'])
            $withdrawals['user_name'] = $user['mobile'];

        $this->assign('user',$user);
        $this->assign('data',$withdrawals);
        $this->assign('id',$id);
        $this->display();
    }

    /**
     *  商家结算记录
     */
    public function order_statis(){
        $model = M("order_statis");
        $store_id = I('store_id');
        $create_date = I('create_date');
        $create_date = str_replace("+"," ",$create_date);
        $create_date2 = $create_date  ? $create_date  : date('Y-m-d',strtotime('-1 month')).' - '.date('Y-m-d',strtotime('+1 month'));
        $create_date3 = explode(' - ',$create_date2);
        $where = " create_date >= '".strtotime($create_date3[0])."' and create_date <= '".strtotime($create_date3[1])."' ";
        $store_id && $where .= " and store_id = $store_id ";

        $count = $model->where($where)->count();
        $Page  = new Page($count,16);
        $list = $model->join('INNER JOIN __STORE__ ON __STORE__.store_id = __ORDER_STATIS__.store_id')->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_date',$create_date2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }


    public function withdrawals_update(){
        $id = I('id');
        $status = I('status');
        $withdrawals = M('withdrawals')->where('id in ('.implode(',', $id).')')->select();
        foreach ($withdrawals as $val){
            if($status == 1 && $val['status'] != 1){
                $user = M('users')->where(array('user_id'=>$val['user_id']))->find();
                if($user['user_money'] < $val['money'])
                {
                    $data['status'] = 2;
                    $data['remark'] = '账户余额不足';
                    M('withdrawals')->where(array('id'=>$val['id']))->save($data);
                    //$this->ajaxReturn(array('status'=>0,'msg'=>"用户余额不足{$withdrawals['money']}，不够提现"),'JSON');
                }else{
                    accountLog($val['user_id'], ($val['money'] * -1), 0,"平台处理用户提现申请");
                    $remittance = array(
                        'user_id' => $val['user_id'],
                        'bank_name' => $val['bank_name'],
                        'account_bank' => $val['account_bank'],
                        'account_name' => $val['account_name'],
                        'money' => $val['money'],
                        'status' => 1,
                        'create_time' => time(),
                        'admin_id' => session('admin_id'),
                        'withdrawals_id' => $val['id']
                    );
                    $tid[] = $val['id'];
                    if(M('remittance')->where(array('withdrawals_id'=>$val['id']))->count() == 0){
                        M('remittance')->add($remittance);
                    }
                }
            }
        }
        if($status == 3){
            M('withdrawals')->where('id in ('.implode(',', $id).')')->delete();
        }else{
            M('withdrawals')->where('id in ('.implode(',', $tid).')')->save(array('status'=>$status));
        }
        $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
    }


    /**
     *
     *提现金额关联的订单信息
     *
     */
    public function withdrawals_order(){
        $store_id =I('get.store_id');
        $order_withdrawals_id = I('get.id');
        $store_name = M('store')->field('store_name')->where(array('store_id'=>$store_id))->find();
        $this->assign('store_name',$store_name['store_name']);
        //M('settlement_order so')->where(array("so.store_id"=>$store_id,"so.is_withdraw"=>0,'so.status'=>1))->save(array('store_withdraws_id'=>$order_withdrawals_id));//每笔结算金额和订单关联
        $status = (I('get.status')==2)?0:1; //提现申请的状态
        $order_info = M('settlement_order so')->field("so.*,tp_order.order_sn,tp_order.confirm_time")
            ->where(array(
                "so.store_id"=>$store_id,
                "so.store_withdraws_id"=>$order_withdrawals_id,
                "so.is_withdraw"=>1,
                'so.status' => $status
            ))->join("inner join tp_order on so.order_id=tp_order.order_id")->select();
        if(!$order_info)
            $order_info = M('settlement_order so')->field("so.*,tp_order.order_sn,tp_order.confirm_time")
                ->where(array(
                    "so.store_id"=>$store_id,
                    "so.store_withdraws_id"=>$order_withdrawals_id,
                    //"so.store_id"=>$store_id,
                    "so.is_withdraw"=>0,
                    'so.status' => $status
                ))->join("inner join tp_order on so.order_id=tp_order.order_id")->select();
        //echo M()->getLastSql();
        //获取快递的状态信息
        foreach ($order_info as $k=>$v){
            $logistics_info = M('delivery_doc')->field('shipping_code,invoice_no')->where(array('order_id'=>$v['order_id']))->find();
            $info = queryExpress($logistics_info['shipping_code'] ,$logistics_info['invoice_no']);
            //$info = queryExpress('zhongtong' , '429496182487');
            //var_dump($info);
            //if($info['status'] == 1){
            $order_info[$k]['state'] = ($info['state']==3)?"已签收":"未签收";
            //}

        }
        //var_dump($order_info);
        $this->assign('order_info',$order_info);
        $this->display();
    }

    /**
     *获取物流信息
     */
    public function getlogistics(){
        $order_id = I('get.id');
        //var_dump($order_id);die;
        $logistics_info = M('delivery_doc')->field('shipping_code,invoice_no')->where(array('order_id'=>"$order_id"))->find();
        //var_dump($kuaidi_info);
        $info = queryExpress($logistics_info['shipping_code'] ,$logistics_info['invoice_no']);
        //$info = queryExpress('shunfeng' , '962610833964');
        var_dump($info);
        foreach(array_reverse($info['data']) as $k=>$v){
            echo "<p style='font-size:14'>".$v['time'].'  '.$v['context'].'</p>';
        }
    }


    /**
     * 申请成功 但未转账的提现列表
     */
    public function to_transfer(){
        $model = M("store_withdrawals");
        $where['r.status'] = 0;
        $where['sw.status'] = 1;
        $store_id = I('store_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');

        $create_time = str_replace("+"," ",$create_time);
        $create_time2 = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time3 = explode(' - ',$create_time2);
        $where['sw.create_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
        $store_id && $where['sw.store_id'] = $store_id;
        $account_bank && $where['sw.account_bank'] = array('like','%'.$account_bank.'%');
        $account_name && $where['sw.account_name'] = array('like','%'.$account_name.'%');

        $count = $model->table(C('DB_PREFIX').'store_withdrawals sw')->join('inner join tp_store_remittance r on r.withdrawals_id = sw.id')->where($where)->count();

        $Page  = new Page($count,1000);
        $list = $model->table(C('DB_PREFIX').'store_withdrawals sw')->field("sw.*,r.status statuss")->join('inner join tp_store_remittance r on r.withdrawals_id = sw.id')->where($where)->order("sw.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        //var_dump($list);
        $this->assign('create_time',$create_time2);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }


    /*
     * 获取转账的按钮 发出转账请求
     */
    public function batchPay(){
        //  $id = I('id');
        //  $model = M("store_withdrawals");
        //  $withdrawals = $model->find($id);
        //  var_dump($withdrawals);
        //  /******************构建请求需要的参数******************/
        //  $time = date("Ymd", time());//付款当天日期
        //  $rand=rand(1,100000);
        //  $number = sprintf("%'.05d", $rand);
        //  $batch_number = $time.$number; //付款批次号
        //  echo  $batch_number;
        //  $detail = "{$number}^{$withdrawals[account_bank]}^{$withdrawals[account_name]}^{$withdrawals[money]}^商家提现";//付款详细数据
        //  //格式：流水号1^收款方帐号1^真实姓名^付款金额1^备注说明1|流水号2^收款方帐号2^真实姓名^付款金额2^备注说明2....

        //   //往支付宝流水记录表里添加记录
        //  $store_remittance_id = M('store_remittance')->field('id')->where(array('withdrawals_id'=>$id))->find();
        //  $store_remittance_id = $store_remittance_id['id'];
        //  $result = M('admin_payment')->where(array('store_remittance_id'=>$id))->find();  //防止相同提现申请重复提交
        //  if(!$result){
        //       M('admin_payment')->add(array(
        //              "store_remittance_id"=>$store_remittance_id,
        //              'batch_no' => $batch_number
        //      ));
        //  }
        if(empty($_POST)) exit("请选择转账的商家信息");
        $id = implode(',', $_POST['id_list']);
        $withdrawals = M('store_withdrawals')->where("id in($id)")->select();
        $time = date("Ymd", time());//付款当天日期
        $rand = rand(1,100000);
        $number = sprintf("%'.05d", $rand);
        $batch_number = $time.$number; //付款批次号
        foreach ($withdrawals as $k=>$v){
            $rand=rand(1,100000);
            $number = sprintf("%'.05d", $rand);
            $detail .= "{$number}^{$v[account_bank]}^{$v[account_name]}^{$v[money]}^商家提现{$v[id]}|";//将提现申请的id放在备注中传递进去
            $total_money += $v['money'];
        }
        $detail = substr($detail, 0,-1); //去除最后一个/
        $count = count($withdrawals); //转账的笔数
        //往记支付宝流水记录表里添加记录
        $result = M('admin_payment')->where(array('store_remittance_id'=>$id))->find();
        if(!$result){
            M('admin_payment')->add(array(
                "store_withdrawals_id"=>$id,
                'batch_no' => $batch_number,
                'count'    =>$count,
                'total_money' => $total_money
            ));
        }

        require_once './plugins/payment/batchPay/alipayapi.php';

    }
    //拒绝提现申请 接触冻结
    public function refuseWithdrawals(){
        $data = I('post.');
        $money['user_money'] = $data['usermoney'];
        M('users')->where(array('user_id' => $data['with_id']))->save($money);
        $end['status'] = 2;
        $end['remark'] = $data['remark'];
        M('withdrawals')->where(array('id' => $data['id']))->save($end);
    }
}
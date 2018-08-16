<?php
/**
 * DIS : 账务中心逻辑方法
 * User: Ning<NK11@qq.com>
 * Date: 2017/11/21 0021
 * Time: 13:47
 */

namespace app\admin\logic;

use Grpc;
Use Psp;
use think\Cache;

class AccountCenterLogic
{
    /**
     * 账务中心增加帐号
     * @param $acc_info array
     * @return mixed error string succ bool
     */
    public function addEditAccount($acc_info)
    {
        $AccountTable = new Psp\Account\AccountTable();
        $AccountTable->setPlatformId(PLATFORM);
        $AccountTable->setType(isset($acc_info[type])?$acc_info[type]:exit('帐号类型不能为空'));
        $AccountTable->setBizType(isset($acc_info[biz_type])?$acc_info[biz_type]:0);
        $AccountTable->setPrimaryAccount(isset($acc_info[primary_account])?$acc_info[primary_account]:0);
        $AccountTable->setOwnerId(isset($acc_info[owner_id])?$acc_info[owner_id]:exit('帐号所有者不能为空'));
        $AccountTable->setName(isset($acc_info[name])?$acc_info[name]:'');
        $AccountTable->setFlags(isset($acc_info[flags])?$acc_info[flags]:0);
        $AccountTable->setCurrency(isset($acc_info[currency])?$acc_info[currency]:1);
        $AccountTable->setBalance(isset($acc_info[balance])?$acc_info[balance]:0);
        $AccountTable->setExtractableBalance(isset($acc_info[extractable_balance])?$acc_info[extractable_balance]:0);
        $AccountTable->setIncommingDaily(isset($acc_info[incomming_daily])?$acc_info[incomming_daily]:0);
        $AccountTable->setOutcommingDaily(isset($acc_info[outcomming_daily])?$acc_info[outcomming_daily]:0);
        $AccountTable->setSubAccounts(isset($acc_info[sub_accounts])?$acc_info[sub_accounts]:'');
        $AccountTable->setPayments(isset($acc_info[payments])?$acc_info[payments]:0);
        $AccountTable->setCreateDate(isset($acc_info[create_date])?grpcTime($acc_info[create_date]):grpcTime());
        $AccountTable->setExtraInfo(isset($acc_info[extra_info])?$acc_info[extra_info]:'');
        
        if(isset($acc_info[account_id]))
        {//如果检测到主键则执行update
            $AccountTable->setAccountId($acc_info[account_id]);
            list($res)= GRPC('account')->updateAccount($AccountTable)->wait();
           return $res->getValue();
        }

        list($res)= GRPC('account')->addAccount($AccountTable)->wait();
        return $res->getValue();
    }


    /**
     * 账户列表数据类型转换
     * @param $ojb object
     * @return array
     */
    public function accountList($ojb)
    {
        $res = [];
        foreach($ojb as $key => $val){

            $res[$key][account_id] = $val->getAccountId();
            $res[$key][platform_id] = $val->getPlatformId();
            $res[$key][type] = $val->getType();
            $res[$key][biz_type] = $val->getBizType();
            $res[$key][primary_account] = $val->getPrimaryAccount();
            $res[$key][owner_id] = $val->getOwnerId();
            $res[$key][name] = $val->getName();
            $res[$key][flags] = $val->getFlags();
            $res[$key][currency] = $val->getCurrency();
            $res[$key][balance] = $val->getBalance();
            $res[$key][extractable_balance] = $val->getExtractableBalance();
            $res[$key][incomming_daily] = $val->getIncommingDaily();
            $res[$key][outcomming_daily] = $val->getOutcommingDaily();
            $res[$key][sub_accounts] = $val->getSubAccounts();
            $res[$key][payments] = $val->getPayments();
            $res[$key][create_date] = $val->getCreateDate()->getSeconds();
            $res[$key][extra_info] = $val->getExtraInfo();
        }
        return $res;
    }

    /**
     * @param $out 转出方账户
     * @param $in  转入方帐号
     * @param $amount 转账金额
     * @param int $type 转账类型
     * @param int $currency_pair 货币对
     * @param int $exchange_rate 即时汇率
     * @return array 包含状态信息的结果
     */
    public function addTransfer($out,$in,$amount,$type=1,$currency_pair=0,$exchange_rate=1)
    {
        $transfer_info = new Psp\Account\AddTransfer();
        $transfer_info->setTransfereeId($out);
        $transfer_info->setTransferorId($in);
        $transfer_info->setAmount($amount);
        $transfer_info->setType($type);
        $transfer_info->setCurrencyPair($currency_pair);
        $transfer_info->setExchangeRate($exchange_rate);
        $transfer_info->setBeginDate(grpcTime());

        list($reply) = GRPC('account')->addTransfer($transfer_info)->wait();
        $res[transfer_id]=$reply->getTransferId()->getTransferId();
        $res[state]=$reply->getState();
        $res[end_date]=$reply->getEndDate()->getSeconds();
        $res[reject_reason]=$reply->getRejectReason();

        return $res;

    }

    /**
     * 转账表数据类型转换
     * @param $obj
     * @return mixed
     */
    public function transferList($obj){

        foreach($obj as $key =>$val){
            $res[$key][transfer_id] = $val->getTransferId();
            $res[$key][transferee_id] = $val->getTransfereeId();
            $res[$key][transferor_id] = $val->getTransferorId();
            $res[$key][state] = $val->getState();
            $res[$key][amount] = $val->getAmount();
            $res[$key][type] = $val->getType();
            $res[$key][currency_pair] = $val->getCurrencyPair();
            $res[$key][exchange_rate] = $val->getExchangeRate();
            $res[$key][begin_date] = $val->getBeginDate()->getSeconds();
            $res[$key][end_date] = $val->getEndDate()->getSeconds();
            $res[$key][reject_reason] = $val->getRejectReason();
        }

        return $res;

    }


    public function getProviderPeriodic($apply_id)
    {
        //todo 未完成接口,等待完善
        $provider = new Psp\Account\ProviderId();
        $provider->setProviderId($apply_id);
        list($reply) = GRPC('account')->getProviderPeriodic($provider)->wait();
        if(empty($reply)) return false;
        $data['account_id'] = $reply->getAccountId();//周期性结算子账户id
        $data['period'] = $reply->getPeriod();//周期（天数）
        $data['by_month'] = $reply->getByMonth();//true表示按自然月结算
        $data['start_date'] = empty($reply->getStartDate())?'':$reply->getStartDate()->getSeconds(); //结算起始时间
        $data['lock_periods'] = $reply->getLockPeriods();//提款冻结周期
        $data['transfer_method'] = $reply->getTransferMethod();//提款方式
        $data['ex_account_name'] = $reply->getExAccountId();//第三方账户
        $data['ex_account_id'] = $reply->getExAccountName();//第三方账户名
        $data['bank'] = $reply->getBank();//开户行
        $data['currency'] = $reply->getCurrency();//币种
        $data['provider_id'] = $reply->getProviderId();//商家账户id
        $data['points'] = $reply->getPoints();//商家结算扣点
        return $data;
    }


    /**
     * 添加记录
     */
    public function addEditProviderPeriodic($postdata){

        $obj = new Psp\Account\ProviderPeriodics();
        $obj->setAccountId(empty($postdata['account_id'])?0:$postdata['account_id']);//周期性结算子账户id
        $obj->setPeriod($postdata['period']);//周期（天数）
        $obj->setByMonth(0);//true表示按自然月结算
        $obj->setStartDate(grpcTime(strtotime($postdata['start_date']))); //结算起始时间
        $obj->setLockPeriods($postdata['lock_periods']);//提款冻结周期
        $obj->setTransferMethod($postdata['transfer_method']);//提款方式
        $obj->setExAccountId($postdata['ex_account_name']);//第三方账户
        $obj->setExAccountName($postdata['ex_account_id']);//第三方账户名
        $obj->setBank($postdata['bank']);//开户行
        $obj->setCurrency($postdata['currency']);//币种
        $obj->setProviderId($postdata['provider_id']);//商家账户id
        $obj->setPoints($postdata['points']);//商家结算扣点

        list($reply) = GRPC('account')->updateProviderPeriodic($obj)->wait();

        if($reply) return $reply->getBoolValue();
        return false;

    }

    /**
     * @param $obj
     * @return mixed
     */
    public function Settlement($obj){
        foreach($obj as $key =>$val){
            $res[$key][provider_id] = $val->getProviderId();
            $res[$key][account_id] = $val->getAccountId();
            $res[$key][period_id] = $val->getPeriodId();
            $res[$key][begin_date] = $val->getBeginDate()->getSeconds();
            $res[$key][end_date] = $val->getEndDate()->getSeconds();
            $res[$key][status] = $val->getStatus();
            $res[$key][locked] = $val->getLocked();
            $res[$key][withdraw_status] = $val->getWithdrawStatus();
            $res[$key][amount] = $val->getAmount();
            $res[$key][currency] = $val->getCurrency();
        }

        return $res;
    }


    public function SettlementRecord(){
        $obj = new Psp\Account\SettlementRecordTable();
        $obj->setOrderId();


    }

    /**
     * 添加一筆提現
     * @param $type 类型 1会员&2商家
     * @param $receipt 用户id
     * @param $money 金额
     * @param $transfer 方式 1支付宝,2银行卡
     * @param $name  收款方名字
     * @param $info 附加信息
     * @param int $peridor 周期结算id
     */
    public function addWithdraw($type,$receipt,$money,$transfer,$name,$info,$peridor=0)
    {
        $obj = new Psp\Account\WithdrawTable();
        $withdraw = new Psp\Account\Withdraw();
        $withdraw->setType($type);
        $withdraw->setPlatformId(PLATFORM);
        $withdraw->setReceiptorId($receipt);
        $withdraw->setTransferMethod($transfer);
        $withdraw->setExAccountName($name);
        $withdraw->setExAccountInfo($info);
        $withdraw->setIssueDate(grpcTime());
        $withdraw->setWithdrawMoney($money);
        $withdraw->setPeriodId($peridor);
        $obj->setWithdraw($withdraw);
        list($reply) = GRPC('account')->addWithdraw($obj)->wait();
//        return $reply->getValue();
        return ['ret'=>$reply->getRet(),'msg'=>$reply->getMsg()];

    }

    /**
     * @param $withdraw_id 提现id
     * @param $status 状态0/申请中;1/审核通过/2审核失败/3转账成功/4转账失败
     * @param $admin
     * @param $reason
     */
    public function checkWithdraw($withdraw_id,$status,$admin,$reason)
    {
        $obj = new Psp\Account\WithdrawStatus();
        $obj->setWithdrawId($withdraw_id);
        $obj->setStatus($status);
        $obj->setAuditorId($admin);
        $obj->setTransferDate(grpcTime());
        $obj->setAuditFailureReason($reason);
        list($reply) = GRPC(account)->updateWithdrawStatus($obj)->wait();
        return $reply->getValue();

    }


    /**
     * 转账操作后记录
     * @param $withdraw_id 提现id
     * @param $status 状态
     * @param $time 完成时间
     * @param $reason 失败原因
     */
    public function updateWithdrawStatus($withdraw_id,$status,$time,$reason){
        $obj = new Psp\Account\WithdrawStatus();
        $obj->setWithdrawId($withdraw_id);
        $obj->setStatus($status);
        $obj->setFinishDate(grpcTime($time));
        $obj->setFailureReason($reason);
        list($reply) = GRPC(account)->updateWithdrawStatus($obj)->wait();
        return $reply->getValue();

    }

    /**
     * 解析提现列表
     * @param $obj
     * @return mixed
     */
    public function withdrawList($obj)
    {
        foreach($obj as $key =>$val){
            $res[$key][withdraw_id] = $val->getWithdrawStatus()->getWithdrawId();

            $res[$key][type] = $type = $val->getWithdraw()->getType();
            $res[$key][receiptor_id] = $owner = $val->getWithdraw()->getReceiptorId();

            if($type==2){

                //获取店铺信息
                $storeInfo = new Psp\Store\ApplyId();
                $storeInfo->setApplyId($owner);
                list($result) = GRPC('sellerstore') ->GetStoreInfo($storeInfo)->wait();
                $res[$key][store_name] = $result->getShopName();
            }

            if($type==1||$type==3){
                //用户信息
                $user_info = new Psp\Member\Uid();
                $user_info->setUid($owner);

                list($reply,$status) = GRPC('member')->GetUserInfo($user_info)->wait();
                if(empty($reply)) continue;
                $res[$key]['tps138_id'] = $reply->getUserinfo()->getTps138Id();
                $res[$key]['nickname']=$reply->getUserinfo()->getName();
                $res[$key]['wh_id'] = $reply->getUserinfo()->getWh181Id();
                $res[$key]['user_id'] = $reply->getUserinfo()->getId();
            }
            if($type==3){
                //用户信息余额免手续费金额
                $account_info =$this->getAccount($owner,1,0,1);
                $res[$key]['extractable_balance'] = $account_info['extractable_balance'];
            }


            $res[$key][transfer_method] = $val->getWithdraw()->getTransferMethod();
            $res[$key][ex_account_info] = $val->getWithdraw()->getExAccountInfo();
            $res[$key][ex_account_name] = $val->getWithdraw()->getExAccountName();
            $res[$key][issue_date] = empty($val->getWithdraw()->getIssueDate())?'':$val->getWithdraw()->getIssueDate()->getSeconds();
            $res[$key][WithdrawMoney] =sprintf("%.2f",$val->getWithdraw()->getWithdrawMoney());
            $res[$key][period_id] = $val->getWithdraw()->getPeriodId();
            $res[$key][status] = $val->getWithdrawStatus()->getStatus();
            $res[$key][auditor_id] = $val->getWithdrawStatus()->getAuditorId();
            $res[$key][transfer_date] = empty($val->getWithdrawStatus()->getTransferDate())?0:$val->getWithdrawStatus()->getTransferDate()->getSeconds();
            $res[$key][finish_date] = empty($val->getWithdrawStatus()->getFinishDate())?0:$val->getWithdrawStatus()->getFinishDate()->getSeconds();
            $res[$key][failure_reason] = $val->getWithdrawStatus()->getFailureReason();
            $res[$key][audit_failure_reason] = $val->getWithdrawStatus()->getAuditFailureReason();
        }

        return $res;
    }

    /**
     * 解析GRPC返回结算详情信息
     * @param $obj
     * @return mixed
     */
    public function settlementDetail($obj){

        foreach($obj as $key =>$val){

            $res[$key][id] = $val->getId();
            $res[$key][settlement_type] = $val->getType();
            $res[$key][order_id] = $val->getOrderId();
            $orderId = new Psp\Trade\OrderId();
            $orderId->setOrderId($val->getOrderId());
            list($reply,$status) = GRPC(trade)->OrderDetails($orderId)->wait();
//            dump($reply->getLogictics());
            $res[$key]['order_id'] = $reply->getOrderId();
            $res[$key]['state'] = $reply->getState();
            $res[$key]['type'] = $reply->getType();
            $res[$key]['money'] = $reply->getMoney();
            $res[$key]['order_date'] = empty($reply->getOrderDate())?0:$reply->getOrderDate()->getSecondS();
            $res[$key]['order_sn'] = $reply->getOrderSn();
            $res[$key]['shipping_price'] = $reply->getShippingPrice();
            $res[$key]['delivery_status'] = $reply->getDeliveryStatus();
            $res[$key]['receiver'] = $reply->getReceiver();
            $res[$key]['receiver_address'] = $reply->getReceiverAddress();
            $res[$key]['receiver_phone'] = $reply->getReceiverPhone();
            $res[$key]['buyer_message'] = $reply->getBuyerMessage();
            $res[$key]['shop_name'] = $reply->getShopname();
            $deliverydata=[];
            $delivery = $reply->getLogictics();
            foreach($delivery as $k=>$v){
                $deliverydata[$k][delivery_sn] = $v->getDeliverySn();
                $deliverydata[$k][shipping_date] = $v->getShippingDate()->getSeconds();
                $deliverydata[$k][receipted_date] = $v->getReceiptedDate()->getSeconds();
            }
            $res[$key][delivery] = $deliverydata;

            $res[$key][transfer_id] = $val->getTransferId();
            $res[$key][settlement_amount] = sprintf('%.2f', $val->getOrderAmount());
            $res[$key][currency] = $val->getCurrency();
            $res[$key][settlemet_id] = $val->getSettlemetId();
        }

        return $res;
}

    /**
     * 获取提现信息
     * @param $withdraw_id
     * @return mixed
     */
    public function getWithdraw($withdraw_id){

        $request = new Psp\Account\WithdrawId();
        $request->setWithdrawId($withdraw_id);
        list($reply) = GRPC('account')->getWithdrawInfo($request)->wait();

        $res[withdraw_id] = $reply->getWithdrawStatus()->getWithdrawId();
        $res[type] = $reply->getWithdraw()->getType();
        $res[receiptor_id] = $reply->getWithdraw()->getReceiptorId();
        $res[transfer_method] = $reply->getWithdraw()->getTransferMethod();
        $res[ex_account_info] = $reply->getWithdraw()->getExAccountInfo();
        $res[ex_account_name] = $reply->getWithdraw()->getExAccountName();
        $res[issue_date] = empty($reply->getWithdraw()->getIssueDate())?'':$reply->getWithdraw()->getIssueDate()->getSeconds();
        $res[WithdrawMoney] = $reply->getWithdraw()->getWithdrawMoney();
        $res[period_id] = $reply->getWithdraw()->getPeriodId();
        $res[status] = $reply->getWithdrawStatus()->getStatus();
        $res[auditor_id] = $reply->getWithdrawStatus()->getAuditorId();
        $res[transfer_date] = empty($reply->getWithdrawStatus()->getTransferDate())?0:$reply->getWithdrawStatus()->getTransferDate()->getSeconds();
        $res[finish_date] = empty($reply->getWithdrawStatus()->getFinishDate())?0:$reply->getWithdrawStatus()->getFinishDate()->getSeconds();
        $res[failure_reason] = $reply->getWithdrawStatus()->getFailureReason();
        $res[audit_failure_reason] = $reply->getWithdrawStatus()->getAuditFailureReason();

        return $res;

    }

    /**
     * 根据提现id查询记录转出记录表是否存在
     * @param $withdraw_id
     * @return mixed
     */
    public function getReceiptOut($withdraw_id){

        $request = new Psp\Account\WithdrawId();
        $request->setWithdrawId($withdraw_id);
        list($reply) = GRPC('account')->getReceiptOut($request)->wait();

        if($reply->getIsExists() == 'yes'){
            if($reply->getStatus()==1){
                return array('msg'=>'所选记录中存在已转账的记录','status'=>'1');
            }else{
                return array('msg'=>'记录已存在','status'=>'2');
            }
        }else{
            return array('msg'=>'记录不存在','status'=>'3');
        }

    }


    /**
     * 通过138id找用户id
     * @param $tps138id
     * @return mixed uid
     */
    public function getUid($tps138id){
        $tps138request = new Psp\Member\Tps138Id();
        $tps138request->setTps138Id($tps138id);
        list($userid) = GRPC('member')->GetUser138Id($tps138request)->wait();
        $receiptor = $userid->getUid();
        if(!$receiptor) return;
        return $receiptor;
    }

    /**
     * 添加资金流出信息
     * @param $type 类型 1支付到会员 2支付到商家
     * @param $money 金额
     * @param $receiptor_id 接收账户信息
     * @param $withdraw_id 提现id
     * @return mixed bool
     */
    public function addPayOut($type,$money,$receiptor_id,$withdraw_id){
        $payload = validate_json_web_token(cookie('_authtoken'));
        $req = new Psp\Account\AddPayOut();
        $req->setType($type);
        $req->setAmount($money);
        $req->setCurrency(1);
        $req->setWhthdrawId($withdraw_id);
        $req->setReceiptorId($receiptor_id);
        $req->setPlatformId(PLATFORM);
        $req->setOperatorId($payload[admin_id]);
        $req->setIssueDate(grpcTime());
        list($reply) = GRPC('account')->addPayOut($req)->wait();
        return $reply->getReceiptId();

    }

    /**
     * 修改资金流出状态
     * @param $receipt_id 支出码
     * @param $status 支出状态 0 default 1 succ 2 false
     * @param $note 失败原因
     */
    public function editPayOut($receipt_id,$status,$note){
        $payload = validate_json_web_token(cookie('_authtoken'));
        $req = new Psp\Account\UpdatePayOutStatus();
        $req->setReceiptId($receipt_id);
        $req->setStatus($status);
        $req->setOperatorId($payload[admin_id]);
        $req->setFinishDate(grpcTime());
        $req->setFailureReason($note);
        list($reply) = GRPC('account')->updatePayOutStatus()->wait();
        return $reply->getValue();
    }

    /**
     * 通过提现receipt_id查找详情
     * @param $receipt_id receipt_id
     * @return mixed
     */
    public function payOutInfo($receipt_id)
    {
        $req = new Psp\Account\ReceiptId();
        $req->setReceiptId($receipt_id);
        list($reply) = GRPC('account')->getPayInfo($req)->wait();

        return $this->payOutTable($reply->getPayOutTable());

    }

    /**
     * GRPC对象解析
     * @param $obj
     * @return mixed
     */
    public function payOutTable($obj)
    {
         $data[receipt_id] = $obj->getReceiptId();
         $data[type] = $obj->getType();
         $data[amount] = $obj->getAmount();
         $data[currency] = $obj->getCurrency();
         $data[status] = $obj->getStatus();
         $data[receiptor_id] = $obj->getReceiptorId();
         $data[platform_id] = $obj->getPlatformId();
         $data[operator_id] = $obj->getOperatorId();
         $data[issue_date] = empty($obj->getIssueDate())?0:$obj->getIssueDate()->getSeconds();
         $data[finish_date] = empty($obj->getFinishDate())?0:$obj->getFinishDate()->getSeconds();
         $data[failure_reason] = $obj->getFailureReason();
         return $data;
    }


    /**
     * 获取账户
     * @param $owner 所有者
     * @param $type 账户类型
     * @param $biztype 业务类型
     * @param $plantform 平台id
     * @return mixed
     */
    public function getAccount($owner,$type,$biztype,$plantform){
        $req = new Psp\Account\GetAccountInfoCondition();
        $req->setType($type);
        $req->setPlatformId($plantform);
        $req->setBizType($biztype);
        $req->setOwnerId($owner);

        $res = [];
        list($reply) = GRPC('account')->getAccountInfoByCondition($req)->wait();

        if($reply){

            $res['account_id'] = $reply->getAccountId();
            $res['platform_id'] = $reply->getPlatformId();
            $res['type'] = $reply->getType();
            $res['biz_type'] = $reply->getBizType();
            $res['primary_account'] = $reply->getPrimaryAccount();
            $res['owner_id'] = $reply->getOwnerId();
            $res['name'] = $reply->getName();
            $res['flags'] = $reply->getFlags();
            $res['currency'] = $reply->getCurrency();
            $res['balance'] = $reply->getBalance();
            $res['extractable_balance'] = $reply->getExtractableBalance();
            $res['incomming_daily'] = $reply->getIncommingDaily();
            $res['outcomming_daily'] = $reply->getOutcommingDaily();
            $res['sub_accounts'] = $reply->getSubAccounts();
            $res['payments'] = $reply->getPayments();
            $res['create_date'] = $reply->getCreateDate()->getSeconds();
            $res['extra_info'] = $reply->getExtraInfo();
        }


        return $res;
    }




    /**
     * 查找支出表中是否存在该记录
     * @param int withdraw_id
     * @return bool
     */
    public function checkReceipt($withdraw_id){
        $req = new Psp\Account\Withdraw();
        $req->setWithdraw($withdraw_id);
        list($res) = GRPC('account')->getReceipt($req)->wait();
        return $res->getValue();
    }

    /**
     * 缓冲账户添加金额(账户资金源)
     * @param $money 收到的金额
     * @return mixed
     */
    public function addBuffer($money){
            //todo 方案1 定时保存
            if(C('Buffer')){
                Cache::store('redis')->inc('buffer'.PLATFORM,$money); //每次收到的钱累加进缓存
                if(Cache::store('redis')->get('time')+60*C('Buffer') > time()){
                    $amount = Cache::store('redis')->get('buffer'.PLATFORM); //获取缓存的金额
                    $this->bufferTran($amount); //转账
                    Cache::store('redis')->dec('buffer'.PLATFORM,$amount); //缓存扣除相对应的金额
                    Cache::store('redis')->set('time'.time()); //更新操作时间               }
                }
            else{
                $this->bufferTran($money);
            }

            //todo 方案2 同步
              //同步方案直接改动执行资金迁移接口(转账)为同步资金账户功能即可
            }
    }


    public function bufferTran($money)
    {
        $req = new Psp\Account\UpdateAccountRequest();
        $req->setPlantformId(PLATFORM);
        $req->setType(3);
        $req->setBizType(31);
        $req->setBalance($money);
        $reply = GRPC('account')->updateAccountBalance($req)->wait();
        return $reply->getValue();
    }

    /**
     * 操作某类型账户金额(谨慎使用)
     * @param $money 金额
     * @param $type 账户类型
     * @param $biz_type 业务类型
     * @return mixed
     */
    public function updateAccountBalance($money,$type,$biz_type){
        $req = new Psp\Account\UpdateAccountRequest();
        $req->setPlantformId(PLATFORM);
        $req->setType($type);
        $req->setBizType($biz_type);
        $req->setBalance($money);
        $reply = GRPC('account')->updateAccountBalance($req)->wait();
        return $reply->getValue();
    }


    /**
     * 订单完成,资金拆分后,利润转账到商家,利润转至平台利润
     * @param $order_id 订单
     */
    public function cashDivisionTransfer($order_id)
    {
        if(empty( S('BA'))){//平台缓冲账户BufferAccount
            $account = $this->getAccount(0,3,31,PLATFORM);
            cache::set('BA',$account['account_id']);
        };
        if(empty( S('PA'))){//平台盈利账户ProfitAccount
            $RAaccount = $this->getAccount(0,3,33,PLATFORM);
            cache::set('PA',$RAaccount['account_id']);
        };

        $order = $this->getOrderMoney($order_id);

        $in  = $this->getAccount($order['provider_id'],5,21,PLATFORM);
        $inAccount = $in['account_id'];
        $this->addTransfer(S('BA'),$inAccount,$order[cost_price]+$order[shipping_price],2);
        $this->addTransfer(S('BA'),S('PA'),$order[total_amount]-$order[cost_price]-$order[shipping_price],4);
    }

    /**
     * 退货资金处理
     * @param $order 订单
     * @param bool $type 处理类型 0 未发货订单 1已发货订单
     */
    public function returnOrderTransfer($order_id,$type=false,$takeAway=0)
    {
       $order = $this->getOrderMoney($order_id);

        if(empty( S('BA'))){//平台缓冲账户BufferAccount
            $account = $this->getAccount(0,3,31,PLATFORM);
            cache::set('BA',$account['account_id']);
        };
        if(empty(S('RA'))){//平台退款账户ReturnAccount
            $account = $this->getAccount(0,3,32,PLATFORM);
            cache::set('RA',$account['account_id']);
        }

        $userAccountInfo  = $this->getAccount($order['member_id'],4,1,PLATFORM);
        $userAccountId = $userAccountInfo['account_id'];

        if(!$type) {//未发货全额退款订单作废订单
            $this->addTransfer(S('BA'),$userAccountId,$order['total_amount'],3);
        }
        else {//已发货已拆分资金原路返回订单
            $this->addTransfer(S('RA'),$userAccountId,$order['total_amount']-$takeAway,3);

            $this->addTransfer(S('PA'),S('RA'),$order['total_amount']-$order['cost_price']-$order['shipping_price'],4);
        }
    }


    /**
     * 获取订单资金
     * @param $order_id
     * @return mixed
     * @throws \Exception
     */
    public function getOrderMoney($order_id){
        $orderId = new Psp\Trade\OrderId();
        $orderId->setOrderId($order_id);
        list($reply) = GRPC(trade)->OrderDetails($orderId)->wait();

        $res['total_amount'] = $reply->getMoney();
        $res['shipping_price'] = $reply->getShippingPrice();
        $res['cost_price'] = $reply->getCost();
        $res['provider_id'] = $reply->getProviderId();
        $res['member_id'] = $reply->getMemberId();

        return $res;

    }
    /*获取会员总收益
    ** $user_id 会员id
     * platform_id 平台id
     * **/
    public function getMemberTotalProfit($user_id){
        $uid = new Psp\Member\UserId();
        $uid->setUid($user_id);
        $uid->setPlatformId(PLATFORM);
        list($reply) = GRPC('Asset')->getMemberTotalProfit($uid)->wait();
        $res['total_profit'] = $reply->getTotalProfit();

        return $res;
    }




}



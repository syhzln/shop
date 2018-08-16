<?php
namespace Home\Logic;

use Think\Model\RelationModel;

/**
 * Class OrderLogic 订单分红拆分逻辑类
 * 支付成功修改支付状态 调用orderProfitSplit()
 * 退货需抽回相关利润 调用 orderProfitRecovery
 * 分红方案使用3.23日确定方案
 * @package Home\Logic
 */
class ProfitLogic extends RelationModel
{

    /**
     * orderProfitSplit 订单收益拆分,执行
     * @param $order_id 订单id
     * @param int $type 订单类型 type1 普通 type2 专区
     * @return array
     * @createdate 2018/3/20 002011:23
     * @author Ning <nk11@qq.com>
     */
    public function orderProfitSplit($order_id,$type=1)
    {

        $orderProfitSplit = M('orderProfitSplit')->where(['order_id'=>$order_id])->count();//检索该订单是否已分隔收益
        if($orderProfitSplit) return ['status'=>0,'code'=>105,'msg'=>'订单已分红处理,请勿在次提交'];

        $order = M('order')->find($order_id);//订单信息
        $user = M('users')->find($order['user_id']);//订单购买者信息
        $first_leader = M('users')->where(['tp138_user_id'=>"$user[first_leader]"])->find();//分销上级信息
        $warnings = $point = 0;//预警
        $goods_price = M('order_goods')->where(['order_id'=>$order_id])->sum('goods_price*goods_num');//商品价
        $cost_price = M('order_goods')->where(['order_id'=>$order_id])->sum('cost_price*goods_num');//成本价
        $profit = $goods_price - $cost_price;//订单总利润

        if($type==2)
        {//专区订单

            $user_profit = $order['total_amount']*C('USER_PROFIT_TYPE2')['level'.$user['level']];//会员自身利润
            $first_leader_profit = $order['total_amount'] * C('FIRST_LEADER_PROFIT_TYPE2')['level'.$user['level']];//分销上级推广利润
            $platform_cost = $order['total_amount']*C('PLATFORM_COST'); //平台运营成本

            $split_profit =  $user_profit + $first_leader_profit + $platform_cost; //定向分配金额

            if($profit<$split_profit){//风控检测,并重新赋值(原则:保证当前收益,保证平台运营成本,不保证资金池资金)
                $warnings = 1;
                $point = floor($profit/$split_profit*10000)/10000; //万分位取百分比 0.1234%
                $user_profit = sprintf("%.2f",$user_profit*$point); //风控后会员自身利润
                $first_leader_profit = sprintf("%.2f",$first_leader_profit*$point);//风控后分销上级推广利润
                $platform_cost = sprintf("%.2f",$platform_cost*$point); //风控后平台运营成本
                $split_profit =  $user_profit + $first_leader_profit + $platform_cost ;//风控后定向分配金额
            }

            $surplus_profit = sprintf("%.2f",$profit - $split_profit);//剩余利润金额




//            echo "要写入数据库的文件:订单id:$order_id,订单类型:$type,订单分红预警:$warnings,
//            风控百分比:$point,会员自身利润:$user_profit,订单关联自身id:$user[tp138_user_id]
//            ,分销上级利润:$first_leader_profit,分销上级138_id:$first_leader[tp138_user_id],
//            平台运营成本:$platform_cost,平台管理费:$management_fees,订单利润:$profit,
//            发放总金额:$split_profit,剩余分配资金池:$profit-$split_profit,
//            订单商品成本价,$goods_price,订单商品成本价:$cost_price,订单总价$order[total_amount]";
//            echo "============";
//            //todo 要执行的操作 写入各项数据到每张表 注意点:开启事务

        }

        if($type==1)
        {//普通订单

//            $platform_profit = ($goods_price - $cost_price)*(1-C('ORDER_PROFIT'));//平台利润

            $order_profit = sprintf("%.2f",($goods_price - $cost_price)*C('ORDER_PROFIT'));//获取分红基准金额(订单利润分红)
            $user_profit = sprintf("%.2f",$order_profit*C('USER_PROFIT_TYPE1')['level'.$user['level']]);
            $first_leader_profit = sprintf("%.2f",$order_profit * C('FIRST_LEADER_PROFIT_TYPE1')['level'.$first_leader['level']]);
            $platform_cost = sprintf("%.2f",$order_profit*C('PLATFORM_COST')); //平台运营成本
            $split_profit =  $user_profit + $first_leader_profit + $platform_cost;//定向分配金额
            $surplus_profit = $order_profit - $split_profit;//剩余利润金额

//            echo "要写入数据库的文件:订单id:$order_id,
//            订单类型:$type,订单分红预警:$warnings,风控百分比:$point,
//            会员自身利润:$user_profit,订单关联自身id:$user[tp138_user_id],
//            分销上级利润:$first_leader_profit
//            ,分销上级138_id:$first_leader[tp138_user_id],
//            平台运营成本:$platform_cost,平台管理费:$management_fees,
//            平台利润:$platform_profit,订单利润:$profit,
//            发放总金额:$split_profit,剩余分配资金池:$surplus_profit,
//            订单商品价,$goods_price,订单商品成本价:$cost_price,订单总价$order[total_amount]";
//            echo "============";
        }

        //从剩余利润里面取手续费,管理费,调控资金,之后剩余为 === 团队分红====
        $service_fees = sprintf("%.2f",$surplus_profit*C('SERVICE_FEES'));//服务费
        $management_fees = sprintf("%.2f",$surplus_profit*C('MANAGEMENT_FEES'));//管理费
        $platform_macro_control = sprintf("%.2f",$surplus_profit*C('PLATFORM_MACRO_CONTROL'));//调控资金
        $team_profit = $surplus_profit - $service_fees - $management_fees - $platform_macro_control;//剩余资金为团队分红资金





        $data = [];
        $data['pay_to_user']=$data['pay_to_first_leader'] = 0;//初始化实际支付金额为0
        M()->startTrans();//开启事务

        if($user_profit>0){//记录自身收益,并在账户增加金额
            $res = $this->userAddAddBonus($user['user_id'],$user['tp138_user_id'],$user_profit,$order_id,$type);
            if($res['status']!=1) {
                M()->rollback();//回滚节点1
                return $res;
            }
            $data['pay_to_user'] = $user_profit;
        }

        if($first_leader_profit>0){//记录分销上级收益 并在账户增加金额
            $res1 = $this->userAddAddBonus($first_leader['user_id'],$first_leader['tp138_user_id'],$first_leader_profit,$order_id,$type);
            if($res1['status']!=1) {
                M()->rollback();//回滚节点2
                return $res1;
            }
            $data['pay_to_first_leader'] = $first_leader_profit;
        }

        $data['order_id'] = $order_id;
        $data['type'] = $type;
        $data['order_amount'] = $goods_price;
        $data['order_cost'] = $cost_price;
        $data['warnings'] = $warnings;
        $data['point'] = $point;
        $data['uuid']= $user['tp138_user_id'];//需根据实际修改
        $data['user_profit'] = $user_profit;
        $data['first_leader'] = $user['first_leader'];
        $data['first_leader_profit'] = $first_leader_profit;
        $data['platform_cost'] = $platform_cost;
        $data['service_fees'] = $service_fees;
        $data['management_fees'] = $management_fees;
        $data['platform_macro_control'] = $platform_macro_control;
        $data['team_profit'] = $team_profit;


        if(M('orderProfitSplit')->add($data)){
            M()->commit();
            return ['status'=>1,'code'=>200,'msg'=>'操作成功'];
        }
        else {
            M()->rollback();//回滚节点3
            return ['status'=>0,'code'=>109,'msg'=>'操作失败'];
        }

    }

    /**
     * userAddAddBonus 会员分红写入
     * @param $user_id 用户id
     * @param $money 金额
     * @param $order 关联订单
     * @param $type 类型
     * @return array
     * @createdate 2018/3/21 002114:25
     * @author Ning <nk11@qq.com>
     */
    public function userAddAddBonus($user_id,$uuid,$money,$order,$type)
    {
        $table = $this->getUserProfitTable();
        $data['uuid'] = $uuid;
        $data['money'] = $money;
        $data['type'] = $type;
        $data['order_ids'] = $order;
        $data['add_time'] = time();
        if(!M($table)->add($data)) return ['status'=>0,'code'=>106,'msg'=>'操作失败'];

        $user = M('user_account');
        $user_account = $user->where(['user_id'=>$user_id])->find();
        if(!$user_account){
            $user->user_id = $user_id;
            $user->uuid = $uuid;
            $user->user_money = $money;
            $user->total_income_amount = $money;
            if(!$user->add())  return ['status'=>0,'code'=>107,'msg'=>'操作失败'];
        }else{
            $user->user_money += $money;
            $user->total_income_amount +=$money ;
            if(!$user->save()) return ['status'=>0,'code'=>108,'msg'=>'操作失败'];
        }
        return ['status'=>1,'code'=>200,'msg'=>'操作成功'];


    }

    /**
     * getUserProfitTable 会员分红分表
     * @return mixed|string 当前该使用的表
     * @createdate 2018/3/22 002213:34
     * @author Ning <nk11@qq.com>
     */
    public function getUserProfitTable()
    {
        $suffix = date('ym');// 1 格式化时间 表后缀  按月分表

        $table = F($suffix); // 6 取缓存文件表名称,提高效率,避免每次都取查询数据库表是否存在

        if(!$table||$table!='user_profit_'.$suffix){// 7 是否存在缓存&&缓存表是否与当前时间对应

            $tableFullName = C('DB_PREFIX').$table = 'user_profit_'.$suffix; // 2 设定表名

            $isTable = M()->query("SHOW TABLES LIKE '{$tableFullName}'"); // 3 查询表是否存在

            if( !$isTable ){// 4 不存在建表
                $sql = "CREATE TABLE $tableFullName (
                    `id`  int NOT NULL AUTO_INCREMENT ,
                    `uuid`  char(10) NOT NULL DEFAULT '' COMMENT '唯一标识 138id等' ,
                    `money`  decimal(10,2) NOT NULL DEFAULT 0 COMMENT '金额' ,
                    `type`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '收益类型 1,普通区订单收益,2专区订单收益 -1分红回收',
                    `order_ids`  char(18) NOT NULL DEFAULT '' COMMENT '关联订单',
                    `add_time`  char(11) NOT NULL COMMENT '时间' ,
                    `status`  tinyint NOT NULL DEFAULT 0 COMMENT '状态' ,
                    PRIMARY KEY (`id`),
                    INDEX (`uuid`) ,
                    INDEX (`type`) ,
                    INDEX (`order_ids`) 
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
                M()->execute($sql);
            }
            F($suffix,$table);// 5 将表放进缓存里
        }
        return $table;
    }

    /**
     * orderProfitRecovery 订单退货回收分红
     * @param $order_id
     * @return array
     * @createdate 2018/3/23 002310:32
     * @author Ning <nk11@qq.com>
     */
    public function orderProfitRecovery($order_id)
    {
        $order_profit_split = M('order_profit_split');
        $recoveryInfo = $order_profit_split->where("order_id = $order_id and status >= 0")->find();
        if(!$recoveryInfo) return ['status'=>0,'code'=>100,'msg'=>'未找到拆分订单,不能退货,请联系平台处理'];
        else{
            M()->startTrans();
            if ($recoveryInfo['pay_to_user']>0) $userRecover =  $this->operateAccount($recoveryInfo['uuid'],$recoveryInfo['pay_to_user']*-1,$order_id);
            if ($recoveryInfo['pay_to_first_leader']>0) $firstleaderRecover = $this->operateAccount($recoveryInfo['first_leader'],$recoveryInfo['pay_to_first_leader']*-1,$order_id);
            $order_profit_split->status = -1;
            $order_profit_split->pay_to_user = 0;
            $order_profit_split->pay_to_first_leader = 0;
            $res = $order_profit_split->save();

            if(isset($userRecover)&&$userRecover['status']!=1){
                M()->rollback();
                return $userRecover;
            }
            if(isset($firstleaderRecover)&&$firstleaderRecover['status']!=1){
                M()->rollback();
                return $firstleaderRecover;
            }
            if(!$res){
                M()->rollback();
                return ['status'=>0,'code'=>104,'msg'=>"修改发放信息失败!"];
            }
            M()->commit();
            return ['status'=>1,'code'=>200,'msg'=>'操作成功'];

        }

    }

    /**
     * operateAccount 撤回收益,从用户账户减钱
     * @param $uuid
     * @param $money
     * @param $order_id
     * @return array|bool
     * @createdate 2018/3/23 002313:14
     * @author Ning <nk11@qq.com>
     */
    public function operateAccount($uuid,$money,$order_id)
    {

        $table = $this->getUserProfitTable();
        $data['uuid'] = $uuid;
        $data['money'] = $money;
        $data['type'] = -1;
        $data['order_ids'] = $order_id;
        $data['add_time'] = time();
        if(!M($table)->add($data)) return ['status'=>0,'code'=>102,'msg'=>'写入分红数据失败,请联系平台处理'];

        $user = M('user_account');
        $user_account = $user->where(['uuid'=>$uuid])->find();
        if(!$user_account){
            return ['status'=>0,'code'=>101,'msg'=>'未找相关账户,请联系平台处理'];
        }else{
            $user->user_money += $money;
            $user->total_income_amount +=$money ;
            if(!$user->save()) return ['status'=>0,'code'=>103,'msg'=>'写入账户数据失败,请联系平台处理'];
        }
        return ['status'=>1,'code'=>200,'msg'=>''];
    }

    /**
     * statisticsProfitSplit 获取分红统计
     * @param $time_begin 开始时间
     * @param $time_end 结束时间
     * @param bool $type 订单类型
     * @return mixed array 统计数据
     * @createdate 2018/3/27 002714:49
     * @author Ning <nk11@qq.com>
     */
    public function statisticsProfitSplit($time_begin,$time_end,$type=false)
    {
        //@todo 统计orderprofitsplit表即可
        empty($time_begin) && $time_begin = strtotime('-1 weeks');
        empty($time_end) && $time_end = time();

        $where['o.order_time']=['between',[$time_begin,$time_end]];
        $type&&$where['op.type'] = $type;

        $res =  M('order_profit_split')
           ->alias('op')
           ->join('left join __ORDER__ o on o.order_id = op.order_id')
           ->field('SUM(op.team_profit) as total_team_profit,
           SUM(op.user_profit) as total_user_profit, 
           SUM(op.pay_to_user) as total_pay_to_user,
           SUM(op.first_leader_profit) as total_leader_profit, 
           SUM(op.pay_to_first_leader) as total_pay_to_leader,
           SUM(op.platform_cost) as total_platform_cost, 
           SUM(op.service_fees) as total_service_fees,
           SUM(op.management_fees) as total_management_fees,
           SUM(op.platform_macro_control) as total_platform_macro_control,
           SUM(op.order_amount-op.order_cost) as total_profit
           ')
           ->where($where)
           ->find();

       return $res;
    }

    /**
     * riskManagement 风控参数预警
     * @createdate 2018/3/23 002316:45
     * @author Ning <nk11@qq.com>
     */
    public function riskManagement()
    {


    }

    /**
     * teamProfitSplit 新人
     * @createdate 2018/3/24 002413:55
     * @author Ning <nk11@qq.com>
     */
    public function newJoin()
    {

        $begin_time = strtotime(date('Y-m-01', strtotime('last month')));//上月第一天0时0分0秒
        $end_time =  strtotime(date('Y-m-01'))-1;//上月最后一天23:59:59
        //1获取新升级用户 id,当前等级,(可能存在一个月内先升级到铜,后又升到钻),当前等级影响最终此项奖金
        //2获取获取奖项金额
        //3 计算每个用户该获取的金额
        $userWhere['u_up.previous_user_level'] = 0;//条件前提1 之前等级为1
        $userWhere['u_up.ctime'] = ['between',[$begin_time,$end_time]];//条件前提2 在上月区间内

        $users = M('user_update')
            ->alias('u_up')
            ->join('left join __USERS__ u on u.user_id=u_up.uid')
            ->field('u.user_id,u.level,u_up.uuid')
            ->where($userWhere)
            ->select();

        return $users;

    }


    /**
     * createNewJoinBounts创建会员本月日收益
     * @createdate 2018/3/28 002816:09
     * @author Ning <nk11@qq.com>
     */
    public function createUserBonusTable()
    {

        //@todo 暂停;算至团队分红后,人工介入
        $suffix = date('ym');// 1 格式化时间 表后缀  按月分表
        $tableFullName = C('DB_PREFIX').$table = 'user_bonus_'.$suffix; // 2 设定表名

        $isTable = M()->query("SHOW TABLES LIKE '{$tableFullName}'"); // 3 查询表是否存在


            if( !$isTable ){// 4 不存在建表
                $sql = "CREATE TABLE $tableFullName (
                    `id`  int NOT NULL AUTO_INCREMENT ,
                    `uuid`  char(10) NOT NULL DEFAULT '' COMMENT '唯一标识 138id等' ,
                    `money`  decimal(10,2) NOT NULL DEFAULT 0 COMMENT '金额' ,
                    `type`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '收益类型 1,普通区订单收益,2专区订单收益 -1分红回收',
                    `order_ids`  char(18) NOT NULL DEFAULT '' COMMENT '关联订单',
                    `add_time`  char(11) NOT NULL COMMENT '时间' ,
                    `status`  tinyint NOT NULL DEFAULT 0 COMMENT '状态' ,
                    PRIMARY KEY (`id`),
                    INDEX (`uuid`) ,
                    INDEX (`type`) ,
                    INDEX (`order_ids`) 
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
                M()->execute($sql);
            }else{
                $this->error('当前月会员分红以创建,禁止重新创建!!');
            }

    }

    /**
     * getGlobalProfitUsers 全球利润合格用户
     * @createdate 2018/3/28 002810:57
     * @author Ning <nk11@qq.com>
     */
    public function getGlobalProfitUsers()
    {
        $begin_time = strtotime(date('Y-m-01', strtotime('last month')));//上月第一天0时0分0秒
        $end_time =  strtotime(date('Y-m-01'))-1;//上月最后一天23:59:59
        $where['o.order_time'] = ['between',[$begin_time,$end_time]];//条件前提2 在上月区间内
        $where['o.order_status']=['in','0,1,2,4'];//订单状态 没取消.没作废.没退货

        return $result = M('order')
            ->alias('o')
            ->join('left join __USERS__ u on o.user_id = u.user_id')
            ->field('u.tp138_user_id,u.user_id,SUM(o.total_amount) as sum_amount,u.level')
            ->group('o.user_id')
            ->where($where)
            ->fetchsql()
            ->select();

        //获得 数据类型 [['tp138_user_id=>1380101278,user_id=>1163,sum_amount=>1507.00,level=>1],['tp138_user_id=>1380101329,user_id=>1210,sum_amount=>39.00,level=>1]]
        //根据获取类型数据集判断用户是否达标  等级,消费金额


    }




    public function getVariousBonuses()
    {



    }

    /**
     * monthlyAccountsStatistics
     * @createdate 2018/3/27 002717:00
     * @author Ning <nk11@qq.com>
     */
    public function monthlyAccountsStatistics(){



    }







}

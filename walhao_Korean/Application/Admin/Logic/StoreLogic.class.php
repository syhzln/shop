<?php

/**
 * WALHAO.COM
 * ============================================================================
 * 
 * 
 * Author: ning[nk11@qq.com]
 * Date: 2017/01/06
 * 编辑修改添加店铺时加随机数为第三方推送主键id
 * 
 */
 

namespace Admin\Logic;

use Think\Model;

class StoreLogic extends Model
{    
    
    /**
     * 获取指定店铺信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     *
     * @return mixed 找到返回数组
     */
    public function detail($store_id, $relation = true)
    {
        $user = D('Store')->where(array('store_id' => $store_id))->relation($relation)->find();
        return $user;
    }
    
    /**
     * 修改店铺信息
     * @param int $uid
     * @param array $data
     * @return array
     */
    public function update($store_id = 0, $data = array())
    {
        $db_res = D('User')->where(array("user_id" => $store_id))->data($data)->save();
        if ($db_res) {
            return array(1, "用户信息修改成功");
        } else {
            return array(0, "用户信息修改失败");
        }
    }
    
    
    /**
     * 添加店铺
     * @param $user
     * @return array
     */
    public function addStore($store)
    {
    	//添加前台登陆账号
    	$model = new Model();
    	$model->startTrans();
    	$db_prefix = C('DB_PREFIX');
    	$utype = check_email($store['user_name']) ? 'email' : 'mobile';
        //取出最后一条 wh_id值
        $wh_id = M('users')->field('wh_id')->order('user_id desc')->limit(1)->select();
        $last_wh_id = ((int)$wh_id[0]['wh_id']) + 1; //取出原值 +1
		$user = array($utype=>$store['user_name'],'password'=>encrypt($store['password']),'reg_time'=>time(),'wh_id'=>$last_wh_id,'tp138_user_id'=>mt_rand(1,99999999));
		$user_id = $model->table($db_prefix.'users')->add($user);
		$store['user_id'] = $user_id;
		unset($store['password']);
		//添加店铺信息
		$store_id = $model->table($db_prefix.'store')->add($store);
		$model->table($db_prefix.'store_extend')->add(array('store_id'=>$store_id));
		if($store['is_own_shop'] == 1){
			//添加驻外店铺
			$apply = array('seller_name'=>$store['seller_name'],'user_id'=>$user_id,
					'store_name'=>$store['store_name'],'company_province'=>0,'sc_bail'=>0,'apply_state'=>1,
			);
			M('store_apply')->add($apply);
		}
		//添加店铺管理员
		$seller = array('seller_name'=>$store['seller_name'],'store_id'=>$store_id,'user_id'=>$user_id,'is_admin'=>1);
		$seller_id = $model->table($db_prefix.'seller')->add($seller);
		if($user_id && $store_id && $seller_id){
			$model->commit();
			$pay_points = tpCache('basic.reg_integral'); // 会员注册赠送积分
			if($pay_points > 0){
				accountLog($user_id, 0,$pay_points, '会员注册赠送积分'); // 记录日志流水
			}
            $this->store_init_shipping($store_id);//初始化店铺物流
			adminLog('新增店铺：'.$store['store_name']);
			return true;
		}else{
			$model->rollback();
			return false;
		}	
    }
    
    /**
     * 改变用户密码
     * @param $uid
     * @param $oldPassword
     * @param $newPassword
     * @return string
     */
    public function changePassword($store_id, $oldPassword, $newPassword)
    {
    
        $user = $this->detail($store_id);
        if ($user['user_pass'] != encrypt($oldPassword)) {
            return array(0, "原用户密码不正确");
        }
        $data['user_id'] = $store_id;
        $data['user_pass'] = encrypt($newPassword);
    
        if (D('User')->where(array("user_id" => $store_id))->data($data)->save()) {
            return array(1, "密码修改成功", U("Admin/login/logout"));
        } else {
            return array(0, "密码修改失败");
        }
    
    }
    
    
    /**
     * 生成新的Hash
     * @param $authInfo
     * @return string
     */
    public function genHash(&$authInfo)
    {
        $User = D('User', 'Logic');    
        $condition['user_id'] = $authInfo['user_id'];
        $session_code = encrypt($authInfo['user_id'] . $authInfo['user_pass'] . time());
        $User->where($condition)->setField('user_session', $session_code);
    
        return $session_code;
    }
    
    public function getAuth($role_id)
    {
    	return $role_id;
    }
    
    /**
     *  自动给商家结算
     */
    public function auto_transfer($store_id){
        // 确认收货多少天后 自动结算给 商家
        $today_time = time();
        //$auto_transfer_date = tpCache('shopping.auto_transfer_date');
       // $auto_transfer_date = $auto_transfer_date * (60 * 60 * 24); // 1天的时间戳        
                 $auto_transfer_date=strtotime('+7 day');       
        $sql = "select order_id from __PREFIX__order where store_id = $store_id and order_status in(2,4) and (($today_time - confirm_time) > $auto_transfer_date) and is_checkout = 0";
        //and order_status in(2,4) and (($today_time - confirm_time) >  $auto_transfer_date)
        $Model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $list = $Model->query($sql);
        
        if(empty($list)) // 没有数据直接跳出
            return false;
        
        $data = array(
            'start_date' => $today_time - $auto_transfer_date, // 结算开始时间
            'end_date'   => time(), // 结算截止时间
            'create_date'=>  time(), // 记录创建时间            
            'store_id'   => $store_id, // 店铺id
        );      
        foreach($list as $key => $val)
        {               
            $order_settlement = order_settlement($val['order_id']); // 调用全局结算方法
            $data['order_totals'] += $order_settlement[0]['goods_amount'];// 订单商品金额    
            $data['shipping_totals'] += $order_settlement[0]['shipping_price'];// 运费    
            $data['return_integral'] +=  $order_settlement[0]['return_integral'];// 退还积分    
            $data['commis_totals'] +=  $order_settlement[0]['settlement'];// 平台抽成
            $data['give_integral'] +=  $order_settlement[0]['give_integral'];// 送出积分金额
            $data['result_totals'] +=  $order_settlement[0]['store_settlement'];// 本期应结
            $data['order_prom_amount'] +=  $order_settlement[0]['order_prom_amount'];// 优惠价
            $data['coupon_price'] +=  $order_settlement[0]['coupon_price'];// 优惠券抵扣
            $data['distribut'] +=  $order_settlement[0]['distribut'];// 分销金额
                         
            M('order')->where("order_id = {$val['order_id']}")->save(array('is_checkout' =>1)); // 标识为已经结算 
        $da=array(
                'store_id'=>$store_id,
                'order_id'=>$val['order_id'],
                'settlement_money'=>$order_settlement[0]['store_settlement']
            );           
        }  
     M('settlement_order')->add($da);//在页面上显示的订单记录            
        M('order_statis')->add($data); // 添加一笔结算统计 
                   
        // 给商家加钱 记录日志
        storeAccountLog($store_id,$data['result_totals'],$data['result_totals'] * -1,'平台订单结算');        
    }

    /**
     * 添加店铺时，默认安装一个物流插件
     * @param $store_id
     */
    public function store_init_shipping($store_id){
        $default_shipping_code = 'shunfeng';
        $store_shipping_is_on = M('shipping_area')->where(['store_id'=>$store_id,'is_close'=>1])->find();
        if(empty($store_shipping_is_on)){
            //平台规定店铺初始，物流默认顺丰物流
            $store_shipping_is_shunfen = M('shipping_area')->where(['store_id'=>$store_id,'shipping_code'=>$default_shipping_code])->find();
            if(empty($store_shipping_is_shunfen)){
                M('shipping_area')->where(['store_id'=>$store_id])->save(['is_default'=>0]);
                $config['first_weight'] = '1000'; // 首重
                $config['second_weight'] = '2000'; // 续重
                $config['money'] = '12';
                $config['add_money'] = '2';
                $add['shipping_area_name'] = '全国其他地区';
                $add['shipping_code'] = $default_shipping_code;
                $add['config'] = serialize($config);
                $add['is_default'] = 1;
                $add['is_close'] = 1;
                $add['store_id'] = $store_id;
                M('shipping_area')->add($add);
            }else{
                M('shipping_area')->where(['shipping_area_id'=>$store_id])->data(array('is_close' => 1,'is_default'=>1))->save();
            }
        }
    }
}
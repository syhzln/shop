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
 * Date: 2015-12-21
 */
/**
 * Author: Ning
 * Date: 2017-2-6
 * 修改显示为所需的内容,主要修改订单查询提取 
 */
/**
 * 订单推送,&&推送收益
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-02-20T12:07:57+0800 
 */
/**
 * 报表页面修改,增加功能缓存,避免出现峰值访问
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-02-27T17:35:49+0800
 */

namespace Admin\Controller;
use Admin\Logic\GoodsLogic;

class ReportController extends BaseController{
	public $begin;
	public $end;
	public function _initialize(){
        parent::_initialize();
		$timegap = I('timegap');
		if($timegap){
			$gap = explode(' - ', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y-m-d',strtotime("-1 month"));//30天前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y-m-d'));
		}
		$this->begin = strtotime($begin);
		$this->end = strtotime($end)+86399;
		$this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));
	}
	
	public function index(){
		$now = strtotime(date('Y-m-d'));
		$today['today_amount'] = M('order')->cache(true,60)->where("pay_time>$now AND pay_status=1")->sum('total_amount');//今日销售总额
		$today['today_order'] = M('order')->cache(true,60)->where("pay_time>$now and pay_status=1 ")->count();//今日订单数
		$today['cancel_order'] = M('order')->cache(true,60)->where("add_time>$now AND order_status=3")->count();//今日取消订单
		$today['cancel_money'] = M('order')->cache(true,60)->where("add_time>$now AND order_status=3")->sum('total_amount');;//今日取消订单
		$today['sign'] = round($today['today_amount']/$today['today_order'],2);
		$this->assign('today',$today);
		$map['pay_time'] = array(array('gt',$this->begin),array('lt',$this->end));
		$map['pay_status'] = 1;
		$res = M('order')->field("COUNT(*) as tnum,sum(total_amount) as amount, FROM_UNIXTIME(pay_time,'%Y-%m-%d') as gap")->cache(true,60)->where($map)->group('gap')->select();//订单数,交易额
		$res1 = M('order')->where($map)->cache(true,60)->distinct(true)->field('user_id')->select();//查询有单会员
		// $sql = "SELECT COUNT(*) as tnum,sum(total_amount) as amount, FROM_UNIXTIME(pay_time,'%Y-%m-%d') as gap from __PREFIX__order ";
		// $sql .= " where pay_time>$this->begin and pay_time<$this->end AND pay_status=1 group by gap ";
		// $res = M()->query($sql);//订单数,交易额	
//		$where['type'] = 0;
		$where['addtime'] = array(array('gt',$this->begin),array('lt',$this->end));
//		$cancle['num'] = M('return_goods')->where($where)->count('distinct(order_sn)');
//		$cancle['money']= M('return_goods')->join("inner join tp_goods on tp_return_goods.goods_id = tp_goods.goods_id")->where($where)->sum('shop_price');
		$cancle= M('return_goods')->field("count(distinct(tp_return_goods.order_sn)) as count,sum(goods_price) as money")
			->join("left join tp_order on tp_return_goods.order_id = tp_order.order_id")->where($where)->cache(true,60)->find();	
		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}
		$sum['tnum'] = $tnum;
		$sum['amount'] =$tamount;
		$sum['users'] = count($res1);;

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);						
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;			
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		$list = array_reverse($list);
		$this->assign('cancle',$cancle);
		$this->assign('list',$list);
		$this->assign('sum',$sum);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}

	public function saleTop(){
		$sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods ";
		$sql .=" where is_send = 1 group by goods_id order by sale_amount DESC limit 100";
		$res = M()->cache(true,3600)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}
	
	public function userTop(){
		$p = I('p',1);
		$start = ($p-1)*20;		
		$mobile = I('mobile');
		$email = I('email');
		if($mobile){
			$where =  "and b.mobile='$mobile'";
		}
		if($email){
			$where = "and b.email='$email'";
		}
		$sql = "select count(a.order_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.tp138_user_id,b.mobile,b.email from __PREFIX__order as a left join __PREFIX__users as b ";
		$sql .= " on a.user_id = b.user_id where a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 $where group by user_id order by amount DESC limit $start,20";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);
		if(empty($where)){
			$count = M('order')->where("add_time>$this->begin and add_time<$this->end and pay_status=1")->group('user_id')->count();
			$Page = new \Think\Page($count,20);
			$show = $Page->show();
			$this->assign('page',$show);
		}
		$this->display();
	}
	
	public function saleList(){
		$p = I('p',1);
		$start = ($p-1)*20;
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		$where = "where b.add_time>$this->begin and b.add_time<$this->end ";
		if($cat_id>0){
			$where .= " and g.cat_id=$cat_id";
			$this->assign('cat_id',$cat_id);
		}
		if($brand_id>0){
			$where .= " and g.brand_id=$brand_id";
			$this->assign('brand_id',$brand_id);
		}
		$sql = "select a.*,b.order_sn,b.shipping_name,b.pay_name,b.add_time from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where ";
		$sql .= "  order by add_time limit $start,20";
		$res = M()->query($sql);
		$this->assign('list',$res);
		
		$sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		$sql2 .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where";
		$total = M()->query($sql2);
		$count =  $total[0]['tnum'];
		$Page = new \Think\Page($count,20);
		$show = $Page->show();
		$this->assign('page',$show);
		
        $GoodsLogic = new GoodsLogic();        
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
		$this->display();
	}
	
		public function user(){
			$this->display();
			exit;
		$today = date('Y-m-d');
		$month = date('Y-m-01');
		$user['today'] = D('users')->where("reg_time>$today")->count();//今日新增会员
		$user['month'] = D('users')->where("reg_time>$month")->count();//本月新增会员
		$user['total'] = D('users')->count();//会员总数
		$user['user_money'] = D('users')->sum('user_money');//会员余额总额
		$res = M('order')->cache(true)->distinct(true)->field('user_id')->select();
		$user['hasorder'] = count($res);
		$this->assign('user',$user);
		$start = date('Y-m-d',$this->begin);
		$end = date('Y-m-d',$this->end);
		$sql = "SELECT COUNT(*) as num,left(reg_time,11) as gap from __PREFIX__users where  reg_time> $start and reg_time< $end group by gap";
		$new = M()->query($sql);//新增会员趋势

		foreach ($new as $k=>$val){
//			$arr[$val['gap']] = $val['num'];
			$brr[$k] = $val['num'];
			$day[$k] = $val['gap'];
		}

//		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
//			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
//			$day[] = date('Y-m-d',$i);
////		}
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		$this->display();
	}
	
	//财务统计
	public function finance(){
		$this->display();
		exit;
		$sql = "SELECT sum(b.goods_num*b.member_goods_price) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= "sum(a.coupon_price) as coupon_amount,FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.shipping_status=1 and b.is_send=1 group by gap order by a.add_time";
		$res = M()->cache(true)->query($sql);//物流费,交易额,成本价
		
		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
			$drr[$val['gap']] = $val['coupon_amount'];
		}
			
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$date = $day[] = date('Y-m-d',$i);
			$tmp_goods_amount = empty($arr[$date]) ? 0 : $arr[$date];
			$tmp_cost_amount = empty($brr[$date]) ? 0 : $brr[$date];
			$tmp_shipping_amount = empty($crr[$date]) ? 0 : $crr[$date];
			$tmp_coupon_amount = empty($drr[$date]) ? 0 : $drr[$date];
			
			$goods_arr[] = $tmp_goods_amount;
			$cost_arr[] = $tmp_cost_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$coupon_arr[] = $tmp_coupon_amount;
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_cost_amount,
					'shipping_amount'=>$tmp_shipping_amount,'coupon_amount'=>$tmp_coupon_amount,'end'=>date('Y-m-d',$i+24*60*60));
		}
		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	

	/**
	 * 订单推送,&&推送收益
	 * @Authorhtl {Ning<nk11@qq.com>}
	 * @DateTime  2017-02-20T12:07:57+0800
	 */
	public function profit(){
		$now = strtotime(date('Y-m-d'));
        $today['today_amount'] = M('order')->where("pay_time>$now AND pay_status=1")->sum('total_amount');//今日销售总额
		$today['goods_price'] = M('order')->where("pay_time>$now AND pay_status=1 and is_send = 1")->sum('goods_price');//今日销售总额/不含运费
		$today['today_order'] = M('order')->where("pay_time>$now and pay_status=1 and is_send = 1")->count();//今日推送订单数
		$today['today_profit'] =  round(($today['goods_price']*0.95-$today['goods_price']/1.6/0.9)/2,2);//今日订单利润。保留2位小数
		$this->assign('today',$today);
		$sql = "SELECT COUNT(*) as tnum,sum(goods_price) as amount, FROM_UNIXTIME(pay_time,'%Y-%m-%d') as gap from __PREFIX__order ";
		$sql .= " where pay_time>$this->begin and pay_time<$this->end AND pay_status=1 AND is_send = 1 group by gap ";
		$res = M()->query($sql);//订单数,交易额
		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}
		$sum['tnum'] = $tnum;
		$sum['price'] =$tamount;
		$sum['profit'] = round(($sum['price']*0.95-$sum['price']/1.6/0.9)/2,2);

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round(($tmp_amount*0.95-$tmp_amount/1.6/0.9)/2,2);//订单利润。保留2位小数
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		$list = array_reverse($list);
		$this->assign('list',$list);
		$this->assign('sum',$sum);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
}
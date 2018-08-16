<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb      
 * Date: 2015-12-21
 */

namespace app\seller\controller;
use app\seller\logic\GoodsLogic;
use think\Db;
use think\Page;

use Psp;
use Grpc;

class Report extends Base{
	public $begin;
	public $end;
	public function _initialize(){
        parent::_initialize();
		
		if(I('start_time')){
                        $begin = I('start_time');
                        $end = I('end_time');
		}else{
                        $begin = date('Y-m-d', strtotime("-3 month"));//30天前
                        $end = date('Y-m-d', strtotime('+1 days'));
		}
		$this->assign('start_time',$begin);
		$this->assign('end_time',$end);
		$this->begin = strtotime($begin);
		$this->end = strtotime($end)+86399;
	}
	
	public function index(){
		// $now = strtotime(date('Y-m-d'));
		// $today['today_amount'] = M('order')->where("add_time>$now AND (pay_status=1 or pay_code='cod') and order_status in(1,2,4)")->sum('order_amount');//今日销售总额
		// $today['today_order'] = M('order')->where("add_time>$now and (pay_status=1 or pay_code='cod')")->count();//今日订单数
		// $today['cancel_order'] = M('order')->where("add_time>$now AND order_status=3")->count();//今日取消订单
		// if ($today['today_order'] == 0) {
		// 	$today['sign'] = round(0, 2);
		// } else {
		// 	$today['sign'] = round($today['today_amount'] / $today['today_order'], 2);
		// }
		//销售概况
		$GetReportList=array("today_amount"=>5,//今日销售总额
              "sign"=>10,//人均客单价
               "today_order"=>15,//今日订单数
               "cancel_order"=>20,//今日取消订单
			);

		$this->assign('today',$GetReportList);
		// $sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from  __PREFIX__order ";
		// $sql .= " where add_time>$this->begin and add_time<$this->end AND (pay_status=1 or pay_code='cod') and order_status in(1,2,4) group by gap ";
		// $res = DB::query($sql);//订单数,交易额
		
		// var_dump($res);
		// die;
		$GetReportList=array('0'=>array('tnum'=>22,
			                             'amount'=>4168,
			                             'gap'=>"2017-10-15"
			                             ),

							'1'=>array('tnum'=>30,
			                             'amount'=>7300,
			                             'gap'=>"2017-10-30"
			                             )
		);
			
		foreach ($GetReportList as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$tnum += $val['tnum'];
			
			$tamount += $val['amount'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
						$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
						$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);				
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;			
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			//$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}
		//rsort($list);
		$GetReportList=array('1'=>array("day"=>'2017-10-1',//时间
                                        "order_num"=>5,//订单数量
                                        'sign'=>20,//销售总价
                                        'amount'=>4,//人均单价
			));
		$this->assign('list',$GetReportList);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}

	public function saleTop(){
		// $sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods ";
		// $sql .=" where is_send = 1 group by goods_id order by sale_amount DESC limit 100";
		// $res = DB::cache(true,3600)->query($sql);
		// echo "<pre>";
		// echo print_r($res);
		// die;
		$GetSaleTopList=array('0'=>array('goods_name'=>"小米手机5,十余项黑科技，很轻狠快",
										 'goods_sn'=>TP0000104,
										 'sale_num'=>18,
										 'sale_amount'=>39498.05,
			),
   							  '1'=>array('goods_name'=>"海力（Horion）55A1华数TV版55英寸 4K轻薄智能网络平板液晶电视",
										 'goods_sn'=>TP000063,
										 'sale_num'=>6,
										 'sale_amount'=>22194.00,

   			),
   							  '2'=>array('goods_name'=>"三星 Galaxy A9高配版 (A9100) 精灵黑 全网通4G手机 双卡双待",
										 'goods_sn'=>TP000000,
										 'sale_num'=>4,
										 'sale_amount'=>11194,

   							  	),
			);
		$this->assign('list',$GetSaleTopList);
		return $this->fetch();
	}
	
	public function saleList(){		 
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		// $where = "where b.add_time>$this->begin and b.add_time<$this->end ";
		// if($cat_id>0){
		// 	$where .= " and g.cat_id=$cat_id";
		// 	$this->assign('cat_id',$cat_id);
		// }
		// if($brand_id>0){
		// 	$where .= " and g.brand_id=$brand_id";
		// 	$this->assign('brand_id',$brand_id);
		// }
                
		// $sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		// $sql2 .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where";
		// $total = DB::query($sql2);
		// $count =  $total[0]['tnum'];
		// $Page = new Page($count,20);
		// $show = $Page->show();                
                
		// $sql = "select a.*,b.order_sn,b.shipping_name,b.pay_name,b.add_time from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id ";
		// $sql .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where ";
		// $sql .= "  order by add_time desc limit {$Page->firstRow},{$Page->listRows}";
		// $res = DB::query($sql);
		// echo "<pre>";
		// echo print_r($res);
		// die;
		$GetSaleList=array('0'=>array('order_id'=>1502,
										 'goods_name'=>"华为 HUAWEI C199S 麦芒3S 电信4G智能手机FDD-LTE /TD-LTE/CDMA2000/GSM（麦芒金）",
										 'goods_sn'=>TP0000045,
										 'goods_num'=>1,
										 'goods_price'=>1999.00,
										 'add_time'=>1507723207,
			),
   							  '1'=>array('order_id'=>1501,
										 'goods_name'=>"小米手机5,十余项黑科技，很轻狠快",
										 'goods_sn'=>TP0000104,
										 'goods_num'=>1,
										 'goods_price'=>6000.00,
										 'add_time'=>1507723207,

   			),
   							  '2'=>array('order_id'=>1500,
										 'goods_name'=>"原封国行【优惠套餐】Apple/苹果 iPhone 6s 4.7英寸 4G手机",
										 'goods_sn'=>TP0000105,
										 'goods_num'=>1,
										 'goods_price'=>5500.00,
										 'add_time'=>1507723207,

   							  	),
			);
		$this->assign('list',$GetSaleList);		
		//$this->assign('page',$show);
		
                $GoodsLogic = new GoodsLogic();        
                $brandList = $GoodsLogic->getSortBrands();
                $categoryList = $GoodsLogic->getSortCategory();
                $this->assign('categoryList',$categoryList);
                $this->assign('brandList',$brandList);
                return $this->fetch();
	}

	//财务统计
	public function finance(){
		// $sql = "SELECT sum(b.goods_num*b.member_goods_price) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		// $sql .= "sum(a.coupon_price) as coupon_amount,FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		// $sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.shipping_status=1 and b.is_send=1 group by gap order by a.add_time";
		// $res = DB::cache(true)->query($sql);//物流费,交易额,成本价
		$OperationReplay=array('0'=>array('goods_amount'=>72081.37,
										  'shipping_amount'=>5594.00,
										  'cost_price'=>520,
										  'coupon_amount'=>10.00,
										  'gap'=>"2017-10-11"),
		                      '1'=>array('goods_amount'=>62034.37,
										  'shipping_amount'=>3594.00,
										  'cost_price'=>520,
										  'coupon_amount'=>10.00,
										  'gap'=>"2017-10-10"),
		);
		foreach ($OperationReplay as $val){
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
                rsort($list);

		$this->assign('list',$list);

		$result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}

	public function withdraw(){
	    die;
	    //todo 提现申请
        //搜索条件
        $status = I('status');  //申请状态
        $account_no = I('account_bank'); //收款账号
        $account_name = I('account_name'); //收款人
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));  //转账完成的时间段

        //分页页码
        $p=I('p/d',1);

        $client = GRPC('account');

        $page = grpcPage('withdraw_id',$p,20,false);


        $withdraw = new Psp\Account\StoreWithdrawCondition();

        $withdraw->setPagination($page);
        $status&&$withdraw->setStatus($status);
        $account_no&&$withdraw->setAccountNo($account_no);
        $account_name&&$withdraw->setAccountName($account_name);
        $start_time&&$withdraw->setBeginDate(grpcTime($start_time));
        $end_time&&$withdraw->setEndDate(grpcTime($end_time));

        list($res,$status) = $client->getStoreWithdraw($withdraw)->wait();

        if($res){
            foreach ($res->getStoreWithdrawList() as $k=>$v) {

                $data[$k]['withdraw_id'] = $v->getWithdrawId();
                $data[$k]['provider_id'] = $v->getSproviderId();
                $data[$k]['shop_name'] = $v->getStorename();
                $data[$k]['bank_name']= $v->getTransferMethod();
                $data[$k]['account_no']=$v->getExAccountInfo();
                $data[$k]['account_name'] = $v->getExAccountName();
                $data[$k]['money'] = $v->getWithdrawMoney();
                $data[$k]['status'] = $v->getWithdrawMoney();
                $data[$k]['failure_reason'] = $v->getWithdrawMoney();
                $data[$k]['audit_failure_reason'] = $v->getAuditFailureReason();

                $data[$k]['transfer_date'] = $v->getTransferDate()?$v->getTransferDate()->getSeconds():0;
                $data[$k]['finish_date'] = $v->getFinishDate()?$v->getFinishDate()->getSeconds():0;
                $data[$k]['period_id'] = $v->getPeriodId();

            }


            //获得总条数
            $count=$res->getPaginationResult()->getTotalRecords();
            $Page  = new Page($count,20);
            $show = $Page->show();
        }
        //dump($data);die;

        $this->assign('list',$data);
        $this->assign('pager',$page);
        $this->assign('page',$show);// 赋值分页输出


        return $this->fetch('withdrawals');
	}

	public function remittance(){
        die;
	    //todo 汇款记录
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
        $receipt->setProviderId(STORE_ID);
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


            //获得总条数
            $count=$res->getPaginationResult()->getTotalRecords();
            $Page  = new Page($count,20);
            $show = $Page->show();
        }
        dump($data);die;

        $this->assign('list',$data);
        $this->assign('pager',$page);
        $this->assign('page',$show);// 赋值分页输出


        return $this->fetch();
	}


	public function settlement(){
	    //todo 结算记录
        return $this->fetch();
	}

	public function unsettledOrder(){
	    //todo 未结算订单
        return $this->fetch();
	}
	
}
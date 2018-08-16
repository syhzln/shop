<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb      
 * Date: 2015-12-21
 */

namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use think\Db;
use think\Page;
use Psp;
use Grpc;
class Report extends Base{
	public $begin;
	public $end;
	public function _initialize(){
        parent::_initialize();

        $payload = validate_json_web_token($_COOKIE['_authtoken']);
        $arr = ['1','2','84'];
        if(!in_array($payload['admin_id'],$arr)){
            $this->error('权限不足,请联系管理员');
        }

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
/**
 * 销售概况
 */
	public function index(){
		
		
		$p=I('get.p/d',1);//分页页码
		//销售概况
		 $client = GRPC('Report');
		 
		$co=100;
            $page = new Psp\Pagination();
            $page->setSortAsc(false);
            $page->setSortBy("ad_id");
            $page->setIndex(1);
            $page->setLimit($co);

            $time = new Psp\Timestamp();
            $time->setSeconds($this->begin);
            $begin=$time->setNanos(1);

            $time = new Psp\Timestamp();
            $time->setSeconds($this->end);
            $end=$time->setNanos(1);
            
            
            $user = new Psp\Content\TotalSales();
            $user->setPagination($page);//传入分页
            $user->setBeginTime($begin);
            $user->setEndTime($end);
            //echo date("Y-m-d,H-i-s",time());
            list($res,$status) = $client->GetReportList($user)->wait();

		$today=array("today_amount"=>$res->getTodyAmount(),//今日销售总额
                             "sign"=>$res->getAveragePrice(),//人均客单价
                             "today_order"=>$res->getTodayOrder(),//今日订单数
                             "cancel_order"=>$res->getCancelOrder(),//今日取消订单
			);
		
		$this->assign('today',$today);
		foreach ($res->getTotaleArr() as $k=>$v)
            {
            	$GetReportList[$k]['gap']=$v->getTodyTime();//今日支付时间
                $GetReportList[$k]['amount']=$v->getTotalAmount();//今日订单总金额
                $GetReportList[$k]['tnum']=$v->getTodayOrder();//今日订单数
                $GetReportList[$k]['sign']=round($v->getAveragePrice(),2);//今日客单价
            }
           // echo"<pre>";
           // var_dump($GetReportList);die;
            $this->assign('list',$GetReportList);
		
			// echo "<br>";
   //          echo date("Y-m-d,H-i-s",time());die;
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
			
			$day[] = $date;
		}
		
		
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		//$count=$res->getPaginationResult()->getTotalRecords();
        //$lim=$res->getPaginationResult()->getPageSize();
        //$Page  = new Page($count,$co);
        //$show = $Page->show();
        //$this->assign('page',$show);// 赋值分页输出
        if($p == 1){
            adminOperateLog('销售概况',5);
        }

		$this->assign('result',json_encode($result));
		return $this->fetch();
	}
/**
 * 销售排行
 */
	public function saleTop(){
		$client = GRPC('Report');
		 
		$co=100;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("ad_id");
        $page->setIndex(1);
        $page->setLimit($co);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->begin);
        $begin=$time->setNanos(1);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->end);
        $end=$time->setNanos(1);


        $user = new Psp\Content\ClassList();
        $user->setPagination($page);//传入分页
        //$user->setBeginTime($begin);
        //$user->setEndTime($end);
        list($res,$status) = $client->GetSaleTopList($user)->wait();
        foreach($res->getClassReplay() as $k=>$v){

            $GetSaleTopList[$k]['goods_name']=$v->getGoodsName();//商品名
            $GetSaleTopList[$k]['sale_num']=$v->getSaleNum();//销量
            $GetSaleTopList[$k]['sale_amount']=$v->getSaleAmount();//销售额
        }
        adminOperateLog('销售排行',5);
		
		$this->assign('list',$GetSaleTopList);
		return $this->fetch();
	}
	/**
	*会员排行
	*/
	public function userTop(){		
		 $mobile = I('mobile');
		 $email = I('email');
		
		$p=I('get.p/d',1);//分页页码
		//销售概况
		 $client = GRPC('Report');
		 
		$co=100;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("ad_id");
        $page->setIndex(1);
        $page->setLimit($co);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->begin);
        $begin=$time->setNanos(1);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->end);
        $end=$time->setNanos(1);
        $user = new Psp\Content\UserList();
        $user->setPagination($page);//传入分页
        $user->setBeginTime($begin);//传入开始时间
        $user->setEndTime($end);//传入结束时间
        $user->setEmail($email);//传入邮箱
        $user->setPhoneNum($mobile);//传入手机号
        list($res,$status) = $client->GetUserTopList($user)->wait();
        foreach($res->getUserReplay() as $k=>$v){
            $GetUserTopList[$k]['order_num']=$v->getOrderNum();//显示订单数
            $GetUserTopList[$k]['amount']=$v->getAmount();//显示购物金额
            $GetUserTopList[$k]['user_id']=$v->getUserId();//显示用户ID
            $GetUserTopList[$k]['nickname']=$v->getUserName();//会员名称可以是手机号和邮箱
        }

		$this->assign('list',$GetUserTopList);
		
		$count=$res->getPaginationResult()->getTotalRecords();
        //$lim=$res->getPaginationResult()->getPageSize();
        if($p == 1){
            adminOperateLog('会员消费排行',5);
        }
        $Page  = new Page($count,$co);
        $show = $Page->show();
        $this->assign('page',$show);// 赋值分页输出
		return $this->fetch();
	}
/**
 * 销售明细
 */
	public function saleList(){		 
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		$p=I('get.p/d',1);//分页页码
		//销售概况
		 $client = GRPC('Report');
		 
		$co=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("ad_id");
        $page->setIndex($p);
        $page->setLimit($co);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->begin);
        $begin=$time->setNanos(1);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->end);
        $end=$time->setNanos(1);

        $user = new Psp\Content\SalesDetails();
        $user->setPagination($page);//传入分页
        $user->setBeginTime($begin);
        $user->setEndTime($end);
        $user->setCatId($cat_id);//分类ID
        $user->setBrandId($brand_id);//品牌ID
        list($res,$status) = $client->GetSaleList($user)->wait();
        foreach($res->getSalesReplay() as $k=>$v){
            $GetSaleList[$k]['order_id']=$v->getOrderId();//商品ID
            $GetSaleList[$k]['goods_name']=$v->getGoodsName();//商品名称
            $GetSaleList[$k]['goods_num']=$v->getGoodsNum();//商品数量
            $GetSaleList[$k]['goods_price']=$v->getGoodsPrice();//售价
            $GetSaleList[$k]['add_time']=$v->getAddTime();//出售日期
        }

		$this->assign('list',$GetSaleList);		

        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getBrandList();
        $categoryList = $GoodsLogic->getCate();

        $count=$res->getPaginationResult()->getTotalRecords();
        //$lim=$res->getPaginationResult()->getPageSize();
        if($p == 1){
            adminOperateLog('销售明细列表',5);
        }
        $Page  = new Page($count,$co);
        $show = $Page->show();
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
	}
/**
 会员统计
 */
	public function user(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$client = GRPC('Report');
		$time = new Psp\Timestamp();
        $time->setSeconds($this->begin);
        $begin=$time->setNanos(1);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->end);
        $end=$time->setNanos(1);


        $user = new Psp\Content\UserStatistics();
        $user->setBeginTime($begin);
        $user->setEndTime($end);
        list($res,$status) = $client->GetUserList($user)->wait();
        //var_dump($res->getUserToday());die;

        $user=array("today"=>$res->getUserToday(),
              "month"=>$res->getUserMonth(),
               "total"=>$res->getUserTotal(),
               //"money"=>$res->getUserTotal(),
               "hasorder"=>$res->getUserHasorder(),
            );
		// $user['today'] = D('users')->where("reg_time>$today")->count();//今日新增会员
		// $user['month'] = D('users')->where("reg_time>$month")->count();//本月新增会员
		// $user['total'] = D('users')->count();//会员总数
		// $user['user_money'] = D('users')->sum('user_money');//会员余额总额
		// $res = M('order')->cache(true)->distinct(true)->field('user_id')->select();
		// $user['hasorder'] = count($res);
		 $this->assign('user',$user);
		 foreach($res->getUserAdd() as $k=>$v){
		 	$GetUserList[$k]['num']=$v->getUserToday();//新增会员数
		 	$GetUserList[$k]['gap']=$v->getRegTime();//注册时间
		 }
		 
		// $sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where reg_time>$this->begin and reg_time<$this->end group by gap";
		// $new = DB::query($sql);//新增会员趋势
		// $GetUserList=array('0'=>array('num'=>1,
			                             
		// 	                             'gap'=>"2017-12-15"
		// 	                             ),

		// 					'1'=>array('num'=>5,
			                             
		// 	                             'gap'=>"2017-12-30"
		// 	                             )
		// );
		foreach ($GetUserList as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}

        adminOperateLog('会员统计',5);

		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		return $this->fetch();
	}
	
	//财务统计
	public function finance(){
		$client = GRPC('Report');
		$co=30;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("ad_id");
        $page->setIndex(1);
        $page->setLimit($co);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->begin);
        $begin=$time->setNanos(1);

        $time = new Psp\Timestamp();
        $time->setSeconds($this->end);
        $end=$time->setNanos(1);

        $user = new Psp\Content\Operation();
        $user->setPagination($page);//传入分页
        $user->setBeginTime($begin);
        $user->setEndTime($end);
        list($res,$status) = $client->GetFinanceList($user)->wait();
        foreach($res->getOperationReplay() as $k=>$v){
            $OperationReplay[$k]['gap']=date('Y-m-d',$v->getPayTime()->getSeconds());//支付时间
            $OperationReplay[$k]['goods_amount']=$v->getTotalMoney();//商品总额
            $OperationReplay[$k]['shipping_amount']=$v->getShippingArr();//物流总金额
            $OperationReplay[$k]['cost_price']=$v->getCostPrice();//商品成本
            $OperationReplay[$k]['coupon_amount']=$v->getCouponArr();//商品成本
        }
		
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

        adminOperateLog('财务统计',5);

		$this->assign('list',$list);

		$result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}


	public function target(){
	    return 	$this->fetch();
	}
	
}
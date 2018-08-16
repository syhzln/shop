<?php
/**
 * 商城
 * ============================================================================
 */

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
//function adminLog($log_info,$admin_id){
//    $add['log_time'] = time();
//    $add['admin_id'] =$admin_id;
//    $add['log_info'] = $log_info;
//    $add['log_ip'] = request()->ip();
//    $add['log_url'] = request()->baseUrl();
//    M('admin_log')->add($add);
//}

/**
 * 管理员操作记录
 * @param $log_info 记录信息
 *  @param $operate_type 操作类型   1会员中心 2商品中心 3交易中心 4账务中心 5文章报表 6配置中心 7其它
 */
function adminOperateLog($log_info,$type){
    $payload = validate_json_web_token($_COOKIE['_authtoken']);
    $ip = request()->ip();
    $log_time = grpcTime(time());
    $admin_id = $payload['admin_id'];
    $log = new Psp\User\OperateLog();
    $log->setAdminId($admin_id);
    $log->setLogInfo($log_info);
    $log->setPlatformId(PLATFORM);
    $log->setIp($ip);
    $log->setAddTime($log_time);
    $log->setOperateType($type);
    list($resp,$status) = GRPC('user')->AddOperateLog($log)->wait();
}



//获取管理员绑定的商户
function getRightParam(){

    $payload = validate_json_web_token($_COOKIE['_authtoken']);
    $act_list = base64_decode($_COOKIE['act_list']);

    $arr =['55'];//指定管理员id
    if($act_list == 'all' || in_array($payload['admin_id'],$arr)){
        $rightParams = 'all';
    }else{
        //获取账户绑定的商户id
        $admin = new Psp\User\AId();
        $admin->setAdminId($payload['admin_id']);
        $admin->setOrgId($payload['org_id']);
        list($resp,$status) = GRPC('user')->GetAdminBindSellerId($admin)->wait();
        if(empty($resp)){
            $rightParams = 'none';
        }else{
            $rightParams = $resp->getBindIds();
        }

    }

    return $rightParams;

}

function getAdminInfo($admin_id){
	return D('admin')->where("admin_id", $admin_id)->find();
}

/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{            
    $navigate = include APP_PATH.'admin/conf/navigate.php';
    $location = strtolower('Admin/'.CONTROLLER_NAME);
    $arr = array(
        '后台首页'=>'javascript:void();',
        $navigate[$location]['name']=>'javascript:void();',
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();',
    );
    return $arr;
}

/**
 * 导出excel
 * @param $strTable	表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable,$filename)
{
	header("Content-type: application/vnd.ms-excel");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=".$filename."_".date('Y-m-d').".xls");
	header('Expires:0');
	header('Pragma:public');
	echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$strTable.'</html>';
}

/**
 * 数组转xls格式的excel文件
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-31T15:20:01+0800
 * @param  array  $data 需要生成excel文件的数组
 * @param  string $filename  生成的excel文件名
 * @param  string $style  生成的表格样式
 */
function create_xls($data,$filename='simple.xls',$style){
    ini_set('max_execution_time', '0');
    vendor('phpexcel.phpexcel.Classes.PHPExcel');
    $filename=str_replace('.xls', '', $filename).'.xls';
    $phpexcel = new PHPExcel();
    $phpexcel->getProperties()
        ->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("$filename")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");

    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    $objActSheet = $phpexcel->getActiveSheet();
    $objActSheet->getDefaultStyle()->getFont()->setSize(9);
    $objActSheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objActSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objActSheet->getDefaultRowDimension()->setRowHeight(30);
    if($style ==1){//导出订单样式
        $objActSheet->getColumnDimension( 'A')->setWidth(20);
        $objActSheet->getColumnDimension( 'B')->setWidth(12);
        $objActSheet->getColumnDimension( 'C')->setWidth(12);
        $objActSheet->getColumnDimension( 'D')->setWidth(60);
        $objActSheet->getColumnDimension( 'E')->setWidth(15);
        $objActSheet->getColumnDimension( 'F')->setWidth(8);
        $objActSheet->getColumnDimension( 'G')->setWidth(8);
        $objActSheet->getColumnDimension( 'H')->setWidth(10);
        $objActSheet->getColumnDimension( 'I')->setWidth(10);
        $objActSheet->getColumnDimension( 'J')->setWidth(10);
        $objActSheet->getColumnDimension( 'K')->setWidth(80);
        $objActSheet->getColumnDimension( 'L')->setWidth(8);
        $objActSheet->getColumnDimension( 'M')->setWidth(20);
        $objActSheet->getColumnDimension( 'N')->setWidth(20);
    }
    elseif($style==2){//导出对账单样式
        $objActSheet->getColumnDimension( 'A')->setWidth(20);
        $objActSheet->getColumnDimension( 'B')->setWidth(12);
        $objActSheet->getColumnDimension( 'C')->setWidth(12);
        $objActSheet->getColumnDimension( 'D')->setWidth(50);
        $objActSheet->getColumnDimension( 'E')->setWidth(15);
        $objActSheet->getColumnDimension( 'F')->setWidth(12);
        $objActSheet->getColumnDimension( 'G')->setWidth(12);
        $objActSheet->getColumnDimension( 'H')->setWidth(15);
        $objActSheet->getColumnDimension( 'I')->setWidth(12);
        $objActSheet->getColumnDimension( 'J')->setWidth(80);
        $objActSheet->getColumnDimension( 'K')->setWidth(8);
        $objActSheet->getColumnDimension( 'L')->setWidth(15);
    }
    elseif($style ==3){//导出退货处理单样式
        $objActSheet->getColumnDimension( 'A')->setWidth(20);
        $objActSheet->getColumnDimension( 'B')->setWidth(60);
        $objActSheet->getColumnDimension( 'C')->setWidth(10);
        $objActSheet->getColumnDimension( 'D')->setWidth(10);
        $objActSheet->getColumnDimension( 'E')->setWidth(10);
        $objActSheet->getColumnDimension( 'F')->setWidth(10);
        $objActSheet->getColumnDimension( 'G')->setWidth(8);
        $objActSheet->getColumnDimension( 'H')->setWidth(60);
        $objActSheet->getColumnDimension( 'I')->setWidth(60);
        $objActSheet->getColumnDimension( 'J')->setWidth(15);
        $objActSheet->getColumnDimension( 'K')->setWidth(10);
        $objActSheet->getColumnDimension( 'L')->setWidth(30);

    }

    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=$filename");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
}

/**
 * @creator fzq
 * @data 2018/1/05
 * @desc 数据导出到excel(csv文件)
 * @param $filename 导出的csv文件名称 如date("Y年m月j日").'-test.csv'
 * @param array $tileArray 所有列名称
 * @param array $dataArray 所有列数据
 */
function exportToExcel($filename, $tileArray=[], $dataArray=[]){
    ini_set('memory_limit','512M');
    ini_set('max_execution_time',0);
    ob_end_clean();
    ob_start();
    header("Content-Type: text/csv");
    header("Content-Disposition:filename=".$filename);
    $fp=fopen('php://output','w');
    fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));//转码 防止乱码(比如微信昵称(乱七八糟的))
    fputcsv($fp,$tileArray);
    $index = 0;
    foreach ($dataArray as $item) {
        if($index==1000){
            $index=0;
            ob_flush();
            flush();
        }
        $index++;
        fputcsv($fp,$item);
    }

    ob_flush();
    flush();
    ob_end_clean();
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 根据id获取地区名字
 * @param $regionId id
 */
//function getRegionName($regionId){
//    $data = M('region')->where(array('id'=>$regionId))->field('name')->find();
//    return $data['name'];
//}

//function getMenuList($act_list){
//	//根据角色权限过滤菜单
//	$menu_list = getAllMenu();
//	if($act_list != 'all'){
//		$right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right',true);
//		foreach ($right as $val){
//			$role_right .= $val.',';
//		}
//		$role_right = explode(',', $role_right);
//		foreach($menu_list as $k=>$mrr){
//			foreach ($mrr['sub_menu'] as $j=>$v){
//				if(!in_array($v['control'].'@'.$v['act'], $role_right)){
//					unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
//				}
//			}
//		}
//	}
//	return $menu_list;
//}

function getAllMenu(){
	return	array(
			'system' => array('name'=>'系统设置','icon'=>'fa-cog','sub_menu'=>array(
					array('name'=>'网站设置','act'=>'index','control'=>'System'),
					array('name'=>'友情链接','act'=>'linkList','control'=>'Article'),
					array('name'=>'自定义导航','act'=>'navigationList','control'=>'System'),
					array('name'=>'区域管理','act'=>'region','control'=>'Tools'),
					array('name'=>'短信模板','act'=>'index','control'=>'SmsTemplate'),
					
			)),
			'access' => array('name' => '权限管理', 'icon'=>'fa-gears', 'sub_menu' => array(
					array('name'=>'权限资源列表','act'=>'right_list','control'=>'System'),
					array('name' => '管理员列表', 'act'=>'index', 'control'=>'Admin'),
					array('name' => '角色管理', 'act'=>'role', 'control'=>'Admin'),
					array('name' => '供应商管理', 'act'=>'supplier', 'control'=>'Admin'),
					array('name' => '管理员日志', 'act'=>'log', 'control'=>'Admin'),
			)),
			'member' => array('name'=>'会员管理','icon'=>'fa-user','sub_menu'=>array(
					array('name'=>'会员列表','act'=>'index','control'=>'User'),
					array('name'=>'会员等级','act'=>'levelList','control'=>'User'),
					array('name'=>'充值记录','act'=>'recharge','control'=>'User'),
					array('name' => '提现申请', 'act'=>'withdrawals', 'control'=>'User'),
					array('name' => '汇款记录', 'act'=>'remittance', 'control'=>'User'),
					//array('name'=>'会员整合','act'=>'integrate','control'=>'User'),
			)),
			'goods' => array('name' => '商品管理', 'icon'=>'fa-book', 'sub_menu' => array(
					array('name' => '商品分类', 'act'=>'categoryList', 'control'=>'Goods'),
					array('name' => '商品列表', 'act'=>'goodsList', 'control'=>'Goods'),
					array('name' => '商品模型', 'act'=>'goodsTypeList', 'control'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'control' => 'Goods'),
					array('name' => '商品属性', 'act'=>'goodsAttributeList', 'control'=>'Goods'),
					array('name' => '品牌列表', 'act'=>'brandList', 'control'=>'Goods'),
					array('name' => '商品评论','act'=>'index','control'=>'Comment'),
					array('name' => '商品咨询','act'=>'ask_list','control'=>'Comment'),
			)),
			'order' => array('name' => '订单管理', 'icon'=>'fa-money', 'sub_menu' => array(
					array('name' => '订单列表', 'act'=>'index', 'control'=>'Order'),
					array('name' => '发货单', 'act'=>'delivery_list', 'control'=>'Order'),
					//array('name' => '快递单', 'act'=>'express_list', 'control'=>'Order'),
					array('name' => '退货单', 'act'=>'return_list', 'control'=>'Order'),
					array('name' => '添加订单', 'act'=>'add_order', 'control'=>'Order'),
					array('name' => '订单日志', 'act'=>'order_log', 'control'=>'Order'),
			)),
			'promotion' => array('name' => '促销管理', 'icon'=>'fa-bell', 'sub_menu' => array(
					array('name' => '抢购管理', 'act'=>'flash_sale', 'control'=>'Promotion'),
					array('name' => '团购管理', 'act'=>'group_buy_list', 'control'=>'Promotion'),
					array('name' => '商品促销', 'act'=>'prom_goods_list', 'control'=>'Promotion'),
					array('name' => '订单促销', 'act'=>'prom_order_list', 'control'=>'Promotion'),
					array('name' => '代金券管理','act'=>'index', 'control'=>'Coupon'),
					array('name' => '预售管理','act'=>'pre_sell_list', 'control'=>'Promotion'),
			)),
			'Ad' => array('name' => '广告管理', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '广告列表', 'act'=>'adList', 'control'=>'Ad'),
					array('name' => '广告位置', 'act'=>'positionList', 'control'=>'Ad'),
			)),
			'content' => array('name' => '内容管理', 'icon'=>'fa-comments', 'sub_menu' => array(
					array('name' => '文章列表', 'act'=>'articleList', 'control'=>'Article'),
					array('name' => '文章分类', 'act'=>'categoryList', 'control'=>'Article'),
					//array('name' => '帮助管理', 'act'=>'help_list', 'control'=>'Article'),
					//array('name' => '公告管理', 'act'=>'notice_list', 'control'=>'Article'),
					array('name' => '专题列表', 'act'=>'topicList', 'control'=>'Topic'),
			)),
			'weixin' => array('name' => '微信管理', 'icon'=>'fa-weixin', 'sub_menu' => array(
					array('name' => '公众号管理', 'act'=>'index', 'control'=>'Wechat'),
					array('name' => '微信菜单管理', 'act'=>'menu', 'control'=>'Wechat'),
					array('name' => '文本回复', 'act'=>'text', 'control'=>'Wechat'),
					array('name' => '图文回复', 'act'=>'img', 'control'=>'Wechat'),
					//array('name' => '组合回复', 'act'=>'nes', 'control'=>'Wechat'),
					//array('name' => '消息推送', 'act'=>'news', 'control'=>'Wechat'),
			)),
			'theme' => array('name' => '模板管理', 'icon'=>'fa-adjust', 'sub_menu' => array(
					array('name' => 'PC端模板', 'act'=>'templateList?t=pc', 'control'=>'Template'),
					array('name' => '手机端模板', 'act'=>'templateList?t=mobile', 'control'=>'Template'),
			)),
 
			'distribut' => array('name' => '分销管理', 'icon'=>'fa-cubes', 'sub_menu' => array(
					array('name' => '分销商品列表', 'act'=>'goods_list', 'control'=>'Distribut'),
					array('name' => '分销商列表', 'act'=>'distributor_list', 'control'=>'Distribut'),
					array('name' => '分销关系', 'act'=>'tree', 'control'=>'Distribut'),
					array('name' => '分销设置', 'act'=>'set', 'control'=>'Distribut'),
					array('name' => '分成日志', 'act'=>'rebate_log', 'control'=>'Distribut'),
			)),

			'tools' => array('name' => '插件工具', 'icon'=>'fa-plug', 'sub_menu' => array(
					array('name' => '插件列表', 'act'=>'index', 'control'=>'Plugin'),
					array('name' => '数据备份', 'act'=>'index', 'control'=>'Tools'),
					array('name' => '数据还原', 'act'=>'restore', 'control'=>'Tools'),
			)),
			'count' => array('name' => '统计报表', 'icon'=>'fa-signal', 'sub_menu' => array(
					array('name' => '销售概况', 'act'=>'index', 'control'=>'Report'),
					array('name' => '销售排行', 'act'=>'saleTop', 'control'=>'Report'),
					array('name' => '会员排行', 'act'=>'userTop', 'control'=>'Report'),
					array('name' => '销售明细', 'act'=>'saleList', 'control'=>'Report'),
					array('name' => '会员统计', 'act'=>'user', 'control'=>'Report'),
					array('name' => '财务统计', 'act'=>'finance', 'control'=>'Report'),
			)),
			'pickup' => array('name' => '自提点管理', 'icon'=>'fa-anchor', 'sub_menu' => array(
					array('name' => '自提点列表', 'act'=>'index', 'control'=>'Pickup'),
					array('name' => '添加自提点', 'act'=>'add', 'control'=>'Pickup'),
			))
	);
}

function getMenuArr(){
	$menuArr = include APP_PATH.'admin/conf/menu.php';
	$act_list = base64_decode(cookie('act_list'));
	$act_list ='all'; //接口测试
	if($act_list != 'all' && !empty($act_list)){
        $right = getRightCode($act_list,1);
		foreach ($right as $val){
			$role_right .= $val.',';
		}
		foreach($menuArr as $k=>$val){
			foreach ($val['child'] as $j=>$v){
				foreach ($v['child'] as $s=>$son){
					if(strpos($role_right,$son['op'].'@'.$son['act']) === false){
						unset($menuArr[$k]['child'][$j]['child'][$s]);//过滤菜单
					}
				}
			}
		}
		foreach ($menuArr as $mk=>$mr){
			foreach ($mr['child'] as $nk=>$nrr){
				if(empty($nrr['child'])){
					unset($menuArr[$mk]['child'][$nk]);
				}
			}
		}
	}
	return $menuArr;
}


function respose($res){
	exit(json_encode($res));
}


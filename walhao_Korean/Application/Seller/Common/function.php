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
 * Date: 2015-09-09
 */

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function sellerLog($log_info){
    $seller = session('seller');
    $add['log_time'] = time();
    $add['log_seller_id'] = $seller['seller_id'];
    $add['log_seller_name'] = $seller['seller_name'];
    $add['log_content'] = $log_info;
    $add['log_seller_ip'] = getIP();
    $add['log_store_id'] = $seller['store_id'];
    $add['log_url'] = __ACTION__;
    M('seller_log')->add($add);
}


function getAdminInfo($admin_id){
	return D('admin')->where("admin_id=$admin_id")->find();
}

function tpversion()
{     
    if(!empty($_SESSION['isset_push']))
        return false;    
    $_SESSION['isset_push'] = 1;    
    error_reporting(0);//关闭所有错误报告
    $app_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
    $version_txt_path = $app_path.'/Application/Admin/Conf/version.txt';
    $curent_version = file_get_contents($version_txt_path);
    
    $vaules = array(            
            'domain'=>$_SERVER['HTTP_HOST'], 
            'last_domain'=>$_SERVER['HTTP_HOST'], 
            'key_num'=>$curent_version, 
            'install_time'=>INSTALL_DATE, 
            'cpu'=>'0001',
            'mac'=>'0002',
            'serial_number'=>SERIALNUMBER,
            );     
     $url = "http://service.tp-shop.cn/index.php?m=Home&c=Index&a=user_push&".http_build_query($vaules); // 检测版本升级
     stream_context_set_default(array('http' => array('timeout' => 3)));
     file_get_contents($url);       
}
 
/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{        
    $navigate = include APP_PATH.'Common/Conf/navigate.php';    
    $location = strtolower('Seller/'.CONTROLLER_NAME);
    $arr = array(
        '홈'=>'javascript:void();',
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
function getRegionName($regionId){
    $data = M('region')->where(array('id'=>$regionId))->field('name')->find();
    return $data['name'];
}

function respose($res){
	header('Content-type:text/json');
	exit(json_encode($res));
}

function getMenuList($act_list){
	//根据角色权限过滤菜单
	$menu_list = getAllMenu();
	if($act_list != 'all' && !empty($act_list)){
		$right = M('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
		foreach ($right as $val){
			$role_right .= $val.',';
		}
		$role_right = explode(',' , $role_right);
		foreach($menu_list as $k=>$mrr){
			foreach ($mrr['child'] as $j=>$v){
				if(!in_array($v['op'].'Controller@'.$v['act'], $role_right)){
					unset($menu_list[$k]['child'][$j]);//过滤菜单
				}
			}
		}
		foreach ($menu_list as $mk=>$mr){
			if(empty($mr['child'])){
				unset($menu_list[$mk]);
			}
		}
	}
	return $menu_list;
}

function getAllMenu() {
	$menu_list = array(
			'goods' => array('name' => '상품 관리', 'icon'=>'fa-tasks', 'child' => array(
					array('name' => '제품 출시', 'act'=>'addEditGoods', 'op'=>'Goods'), ///index.php/Seller/goods/addEditGoods.html'
					//array('name' => '淘宝导入', 'act'=>'import', 'op'=>'index'),             //临时屏蔽淘宝商品导入
					array('name' => '제품의 판매', 'act'=>'goodsList?goods_state=1', 'op'=>'Goods'),
					array('name' => '상품 재고', 'act'=>'goodsList?goods_state=0,2,3', 'op'=>'Goods'),
					array('name' => '재고 레코드', 'act'=>'stock_list', 'op'=>'Goods'),
					array('name' => '제품 사양', 'act' => 'specList', 'op'=>'Goods'),
                    array('name' => '브랜드', 'act'=>'brandList', 'op'=>'Goods'),
					//array('name' => '图片空间', 'act'=>'store_album', 'op'=>'album_cate'),
			)),
			'order' => array('name' => '쇼핑 주문', 'icon'=>'fa-money', 'child' => array(
					array('name' => '주문 목록', 'act'=>'index', 'op'=>'Order'),
					array('name' => '물품 배달', 'act'=>'delivery_list', 'op'=>'Order'),
					array('name' => '배송 설정', 'act'=>'index', 'op'=>'Plugin'),
					//array('name' => '运单模板', 'act'=>'store_waybill', 'op'=>'waybill_manage'),
					array('name' => '제품 리뷰','act'=>'index','op'=>'Comment'),
					array('name' => '제품 자문','act'=>'ask_list','op'=>'Comment'),
			)),
			'promotion' => array('name' => '판촉 관리', 'icon'=>'fa-bell', 'child' => array(
					array('name' => '관리 구매', 'act'=>'flash_sale', 'op'=>'Promotion'),
					array('name' => '그룹 구매 관리', 'act'=>'group_buy_list', 'op'=>'Promotion'),
					array('name' => '제품 홍보', 'act'=>'prom_goods_list', 'op'=>'Promotion'),
					array('name' => '주문 프로모션', 'act'=>'prom_order_list', 'op'=>'Promotion'),
					array('name' => '바우처 관리','act'=>'index', 'op'=>'Coupon'),
					//array('name' => '分销管理', 'act'=>'store_activity', 'op'=>'promotion'),
			)),
			'store' => array('name' => '매장 관리', 'icon'=>'fa-cog', 'child' => array(
					array('name' => '가게 설정', 'act'=>'store_setting', 'op'=>'Store'),
					array('name' => '가게 장식', 'act'=>'store_decoration', 'op'=>'Store'),
					array('name' => '숍 탐색', 'act'=>'navigation_list', 'op'=>'Store'),
					array('name' => '비즈니스 분류', 'act'=>'bind_class_list', 'op'=>'Store'),
					array('name' => '매장 정보', 'act'=>'store_info', 'op'=>'Store'),
					array('name' => '상점 분류', 'act'=>'goods_class', 'op'=>'Store'),
					array('name' => '상점 관심', 'act'=>'store_collect', 'op'=>'Store'),
			)),
			'consult' => array('name' => '애프터 서비스', 'icon'=>'fa-flag', 'child' => array(
					array('name' => '컨설팅 경영', 'act'=>'ask_list', 'op'=>'Comment'),
					//array('name' => '退款记录', 'act'=>'store_refund', 'op'=>'Order'),
					array('name' => '환불', 'act'=>'return_list', 'op'=>'Order'),
					array('name' => '불만 사항 관리', 'act'=>'complain_list', 'op'=>'Comment'),
			)),
			'statistics' => array('name' => '통계 보고서', 'icon'=>'fa-signal', 'child' => array(
					array('name' => '매장 개요', 'act'=>'index', 'op'=>'Report'),
					array('name' => '제품 분석', 'act'=>'saleTop', 'op'=>'Report'),
					array('name' => '운영 보고서', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '판매 순위', 'act'=>'saleTop', 'op'=>'Report'),
					array('name' => '교통 통계', 'act'=>'visit', 'op'=>'Report'),
			)),
			'message' => array('name' => '고객 서비스 정보', 'icon'=>'fa-comments', 'child' => array(
					array('name' => '고객 서비스 설정', 'act'=>'store_service', 'op'=>'Index'),
					array('name' => '시스템 메시지', 'act'=>'store_msg', 'op'=>'Index'),
					//array('name' => '聊天记录查询', 'act'=>'store_im', 'op'=>'store'),
			)),
			'account' => array('name' => '계정 관리', 'icon'=>'fa-home', 'child' => array(
					array('name' => '계정 목록', 'act'=>'index', 'op'=>'Admin'),
					array('name' => '계정 그룹', 'act'=>'role', 'op'=>'Admin'),
					array('name' => '계정 로그', 'act'=>'log', 'op'=>'Admin'),
					//array('name' => '店铺消费', 'act'=>'store_cost', 'op'=>'cost_list'),
			)),
                        // http://www.tpshop.com/Admin/Distribut/remittance
			'finance' => array('name' => '재무 관리', 'icon'=>'fa-book', 'child' => array(
					array('name' => '탈퇴 신청서 기록', 'act'=>'withdrawals', 'op'=>'Finance'),
					array('name' => '송금 기록', 'act'=>'remittance', 'op'=>'Finance'),
                    array('name' => '사업 정산 기록', 'act'=>'order_statis', 'op'=>'Finance'),
					array('name' => '해결되지 않은 주문', 'act'=>'order_no_statis', 'op'=>'Finance'),
		    )),
            /*  
                        // http://www.tp-shop.cn/Admin/Distribut/rebate_log     /index.php/Seller/Store/distribut
			'distribut' => array('name' => '分销管理', 'icon'=>'fa-cubes', 'child' => array(
					array('name' => '分销商品', 'act'=>'goods_list', 'op'=>'Distribut'),
					array('name' => '分销设置', 'act'=>'distribut', 'op'=>'Store'),
                    array('name' => '分成记录', 'act'=>'rebate_log', 'op'=>'Distribut'),
			)),                                
              */
	);
	return $menu_list;
}


/**
 * 导入excel文件
 * @param  string $file excel文件路径
 * @return array        excel文件内容数组
 */
function import_excel($file){
	// 判断文件是什么格式
	$type = pathinfo($file);
	$type = strtolower($type["extension"]);
	$type=$type==='xlsx' ? 'Excel2007' : 'Excel5';
	ini_set('max_execution_time', '0');
	Vendor('PHPExcel.PHPExcel');
	// 判断使用哪种格式
	$objReader = PHPExcel_IOFactory::createReader($type);
	$objPHPExcel = $objReader->load($file,$encode='utf-8');
	$sheet = $objPHPExcel->getSheet(0);
	// 取得总行数 
	$highestRow = $sheet->getHighestRow();
	// 取得总列数      
	$highestColumn = $sheet->getHighestColumn();
	//循环读取excel文件,读取一条,插入一条
	$data=array();
	//从第一行开始读取数据
	for($j=1;$j<=$highestRow;$j++){
		//从A列读取数据
		for($k='A';$k<=$highestColumn;$k++){
			// 读取单元格
			$data[$j][]=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
		}
	}
	return $data;
}


/**
 * excle表格导入2
 * @param $file
 * @return array
 */
function importExecl($file){
	if(!file_exists($file)){
		return array("error"=>0,'message'=>'file not found!');
	}
	$type = pathinfo($file);
	$type = strtolower($type["extension"]);
	$type=$type==='xlsx' ? 'Excel2007' : 'Excel5';
	Vendor("PHPExcel.PHPExcel.IOFactory");
	$objReader = PHPExcel_IOFactory::createReader($type);
	try{
		$PHPReader = $objReader->load($file);
	}catch(Exception $e){}
	if(!isset($PHPReader)) return array("error"=>0,'message'=>'文件格式错误');
	$allWorksheets = $PHPReader->getAllSheets();
	$i = 0;
	foreach($allWorksheets as $objWorksheet){
		$sheetname=$objWorksheet->getTitle();
		$allRow = $objWorksheet->getHighestRow();//how many rows
		$highestColumn = $objWorksheet->getHighestColumn();//how many columns
		$allColumn = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$array[$i]["Title"] = $sheetname;
		$array[$i]["Cols"] = $allColumn;
		$array[$i]["Rows"] = $allRow;
		$arr = array();
		$isMergeCell = array();
		foreach ($objWorksheet->getMergeCells() as $cells) {//merge cells
			foreach (PHPExcel_Cell::extractAllCellReferencesInRange($cells) as $cellReference) {
				$isMergeCell[$cellReference] = true;
			}
		}
		for($currentRow = 1 ;$currentRow<=$allRow;$currentRow++){
			$row = array();
			for($currentColumn=0;$currentColumn<$allColumn;$currentColumn++){;
				$cell =$objWorksheet->getCellByColumnAndRow($currentColumn, $currentRow);
				$afCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn+1);
				$bfCol = PHPExcel_Cell::stringFromColumnIndex($currentColumn-1);
				$col = PHPExcel_Cell::stringFromColumnIndex($currentColumn);
				$address = $col.$currentRow;
				$value = $objWorksheet->getCell($address)->getValue();
				if(substr($value,0,1)=='='){
					return array("error"=>0,'message'=>'can not use the formula!');
					exit;
				}
				if($cell->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){
					$cellstyleformat=$cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat();
					$formatcode=$cellstyleformat->getFormatCode();
					if (preg_match('/^([$[A-Z]*-[0-9A-F]*])*[hmsdy]/i', $formatcode)) {
						$value=gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
					}else{
						$value=PHPExcel_Style_NumberFormat::toFormattedString($value,$formatcode);
					}
				}
				if($isMergeCell[$col.$currentRow]&&$isMergeCell[$afCol.$currentRow]&&!empty($value)){
					$temp = $value;
				}elseif($isMergeCell[$col.$currentRow]&&$isMergeCell[$col.($currentRow-1)]&&empty($value)){
					$value=$arr[$currentRow-1][$currentColumn];
				}elseif($isMergeCell[$col.$currentRow]&&$isMergeCell[$bfCol.$currentRow]&&empty($value)){
					$value=$temp;
				}
				$row[$currentColumn] = $value;
			}
			$arr[$currentRow] = $row;
		}
		$array[$i]["Content"] = $arr;
		$i++;
	}
//	spl_autoload_register(array('Think','autoload'));//must, resolve ThinkPHP and PHPExcel conflicts
	unset($objWorksheet);
	unset($PHPReader);
	unset($PHPExcel);
	unlink($file);
	return $arr;
}
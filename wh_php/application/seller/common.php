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
function adminLog($log_info,$admin_id){
//    $add['log_time'] = time();
//    $add['admin_id'] =$admin_id;
//    $add['log_info'] = $log_info;
//    $add['log_ip'] = request()->ip();
//    $add['log_url'] = request()->baseUrl();
//    M('admin_log')->add($add);
}


function getAdminInfo($admin_id){
//    return D('admin')->where("admin_id", $admin_id)->find();
}


/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{
    $navigate = include APP_PATH.'seller/conf/navigate.php';
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
    vendor('phpexcel.phpexcel.Classes.PHPExcel');
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
//    $data = M('region')->where(array('id'=>$regionId))->field('name')->find();
    return $data['name'];
}

function getMenuList($act_list){
    //根据角色权限过滤菜单
    $menu_list = getAllMenu();
    if($act_list != 'all'){
//        $right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right',true);
//        foreach ($right as $val){
//            $role_right .= $val.',';
//        }
//        $role_right = explode(',', $role_right);
        foreach($menu_list as $k=>$mrr){
            foreach ($mrr['sub_menu'] as $j=>$v){
//                if(!in_array($v['control'].'@'.$v['act'], $role_right)){
//                    unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
//                }
            }
        }
    }
    return $menu_list;
}


function getMenuArr(){
    $menuArr = include APP_PATH.'seller/conf/menu.php';
    $act_list = base64_decode(cookie('right_list'));
    $act_list = 'all';//接口测试
    if($act_list != 'all' && !empty($act_list)){
//		$right = M('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
        $right = getRightCode($act_list,2);
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


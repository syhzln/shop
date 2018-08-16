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
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-03-14T11:05:17+0800
 * @新增记录操作日志
 */
namespace Admin\Controller;


class IndexController extends BaseController {

    public function index(){
        $this->pushVersion();
        $act_list = session('act_list');
        $menu_list = getMenuList($act_list);
        $this->assign('menu_list',$menu_list);
        $admin_info = getAdminInfo(session('admin_id'));
        $order_amount = M('order')->where("order_status=0 and pay_status=1")->count();
        $goods_amount = M('goods')->where("goods_state=1 and shop_price < (cost_price*1.44) and is_on_sale = 1 and store_id != 82")->count();
        $this->assign('goods_amount',$goods_amount);
        $this->assign('order_amount',$order_amount);
        $this->assign('admin_info',$admin_info);
        $menuArr = include APP_PATH.'Admin/Conf/menu.php';
        $this->assign('menu',$menuArr);
        $this->display();
    }
   
    public function welcome(){
        $this->assign('sys_info',$this->get_sys_info());
        $today = strtotime(date('Y-m-d'));
        $count['handle_order'] = M('order')->where("order_status=0 and pay_status=1")->count();//待处理订单
        $count['new_order'] = M('order')->where("add_time>$today")->count();//今天新增订单
        $count['goods'] =  M('goods')->where("1=1")->count();//商品总数
        $count['article'] =  M('article')->where("1=1")->count();//文章总数
        $count['users'] = M('users')->where("1=1")->count();//会员总数
        // $count['today_login'] = M('users')->where("last_login>$today")->count();//今日访问
        // $count['new_users'] = M('users')->where("UNIX_TIMESTAMP(reg_time)>$today")->count();//新增会员
        $count['comment'] = M('comment')->where("is_show=0")->count();//最新评论
        $count['store'] = M('store_apply')->where("apply_state=0")->count();//店铺审核
        $count['bind_class'] = M('store_bind_class')->where("state=0")->count();//申请经营类目
        $count['brand'] = M('brand')->where("status=0 and store_id>0")->count();//申请品牌
        $this->assign('count',$count);
        $this->display();
    }
    
    
    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');		
        $sys_info['version']   	    = file_get_contents('./Application/Admin/Conf/version.txt');
		$mysqlinfo = M()->query("SELECT VERSION() as version");
		$sys_info['mysql_version']  = $mysqlinfo[0]['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
    
    
    public function pushVersion()
    {            
        if(!empty($_SESSION['isset_push']))
            return false;    
        $_SESSION['isset_push'] = 1;    
        error_reporting(0);//关闭所有错误报告
        $app_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $version_txt_path = $app_path.'/Application/Admin/Conf/version.txt';
        $curent_version = file_get_contents($version_txt_path);

        $vaules = array(            
                'domain'=>$_SERVER['SERVER_NAME'], 
                'last_domain'=>$_SERVER['SERVER_NAME'], 
                'key_num'=>$curent_version, 
                'install_time'=>INSTALL_DATE,
                'serial_number'=>SERIALNUMBER,
         );     
         $url = "http://service.tp-shop.cn/index.php?m=Home&c=Index&a=user_push&".http_build_query($vaules);
         stream_context_set_default(array('http' => array('timeout' => 3)));
         file_get_contents($url);         
    }
    
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table = I('table'); // 表名
            $id_name = I('id_name'); // 表主键id名
            $id_value = I('id_value'); // 表主键id值
            $field  = I('field'); // 修改哪个字段
            $value  = I('value'); // 修改字段值                        
            M($table)->where("$id_name = $id_value")->save(array($field=>$value)); // 根据条件保存修改的数据

            switch ($table == 'goods') {
                case $field == 'is_hot':
                    if ($value==1) $goods_state = '标记为热卖产品';
                    else $goods_state = '跌出热卖榜';
                    break;
                case $field == 'is_recommend':
                    if ($value==1) $goods_state = '标记为推荐产品';
                    else $goods_state = '跌出站点推荐榜';
                    break;
                case $field == 'is_new':
                    if ($value==1) $goods_state = '标记为新品推荐';
                    else $goods_state = '退出推荐榜';
                    break;

            default:break;
            }        
            goodsact($id_value,$goods_state);
    }	

    public function get_category(){
    	$parent_id = I('get.parent_id',0); // 商品分类 父id
    	empty($parent_id) && exit('');
    	$list = M('goods_category')->where(array('parent_id'=>$parent_id))->select();
    	// 店铺id
    	$store_id = session('store_id');
    	//如果店铺登录了
    	if($store_id)
    	{
    		$store = M('store')->where("store_id = $store_id")->find();
    		 
    		if($store['bind_all_gc'] == 0)
    		{
    			$class_id1 = M('store_bind_class')->where("store_id = $store_id and state = 1")->getField('class_1',true);
    			$class_id2 = M('store_bind_class')->where("store_id = $store_id and state = 1")->getField('class_2',true);
    			$class_id3 = M('store_bind_class')->where("store_id = $store_id and state = 1")->getField('class_3',true);
    			$class_id = array_merge($class_id1,$class_id2,$class_id3);
    			$class_id = array_unique($class_id);
    		}
    	}
    	foreach($list as $k => $v)
    	{
    		// 如果是某个店铺登录的, 那么这个店铺只能看到自己申请的分类,其余的看不到
    		if($class_id && !in_array($v['id'],$class_id))
    			continue;
    		$html .= "<option value='{$v['id']}' rel='{$v['commission']}'>{$v['name']}</option>";
    	}
    
    	exit($html);
    }

}
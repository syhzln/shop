<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb      
 * Date: 2015-09-09
 */
namespace app\admin\controller; 
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Loader;
use Grpc;
use Psp;

class Index extends Base {

    public function index(){

        // $act_list = session('act_list');
        // $menu_list = getMenuList($act_list);         
        // $this->assign('menu_list',$menu_list);//view
//        $admin_info = getAdminInfo(session('admin_id'));
        $admin_info = validate_json_web_token(cookie('_authtoken'));
        //$order_amount = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();
        //$this->assign('order_amount',$order_amount);
        adminOperateLog('后台首页',6);
        $this->assign('admin_info',$admin_info);             
        $this->assign('menu',getMenuArr());   //view2
        return $this->fetch();
    }
   
    public function welcome(){
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId(0);
        list($result,$status) = GRPC('sellerstore') ->GetAdminStatistics($storeInfo)->wait();
        foreach($result->getInfo() as $k=>$v){
            $arr[$k]['name'] = $v->getName();
            $arr[$k]['count'] = $v->getCount();
        }
        if($arr==null){
            $arr=array();
        }
        $count=array_column($arr,'count','name');
        adminOperateLog('后台欢迎页面',6);
    	$this->assign('count',$count);
        return $this->fetch();
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
        $sys_info['version']   	    = file_get_contents(APP_PATH.'admin/conf/version.php');
		//$mysqlinfo = Db::query("SELECT VERSION() as version");
		//$sys_info['mysql_version']  = $mysqlinfo[0]['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
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
        //规格
        if($table =='spec' ) {
            // 判断是否筛选选项还是排序
            if ($field == 'search_index') {

                if ($value == 1){
                    $bool = true;
                } else {
                    $bool = false;
                }
                $specIndex = new Psp\Item\SpecSeachIndex();
                $spec_id = new Psp\Item\SpecId();
                $spec_id->setId($id_value);
                $specIndex->setSpecId($spec_id);
                $specIndex->setSearchIndex($bool);

                list($res,$status) = GRPC('extra')->SetSpecSeach($specIndex)->wait();
                if ($res->getValue()){
                    return '1';
                }

            } else {// 排序

                $specOrder = new Psp\Item\SpecOrder();
                $spec_id = new Psp\Item\SpecId();
                $spec_id->setId($id_value);
                $specOrder->setSpecId($spec_id);
                $specOrder->setOrder($value);

                list($res,$status) = GRPC('extra')->SetSpecOrder($specOrder)->wait();
                if ($res->getValue()){
                    return '1';
                }

            }
        }
        //评论
        if($table == 'comment'){
//                    return '2';
            /*$client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
                'credentials' => Grpc\ChannelCredentials::createInsecure()
            ]);*/

            $cid = new Psp\Item\CommentId();
            $cid->setCommentId($id_value);

            $IsShow = new Psp\Item\CommentIsShow();
            $IsShow->setCommentId($cid);
            $IsShow->setIsShow($value);

            list($res,$status) = GRPC('comment')->SetCommentIsShow($IsShow)->wait();
            if ($res->getValue()){
                return '1';
            }


        }
        //属性
        if($table == 'goods_attribute'){
            // 判断是否筛选选项还是排序
            if ($field == 'search_index') { //
                /*$client = new Psp\Item\ItemExtraServiceClient('127.0.0.1:9300', [
                    'credentials' => Grpc\ChannelCredentials::createInsecure()
                ]);*/

                $attr_id = new Psp\Item\ItemAttributeId();
                $attr_id->setAttrId($id_value);

                $attrIndex = new Psp\Item\ItemAttributeSeachIndex();
                $attrIndex->setAttributeId($attr_id);
                $attrIndex->setSearchIndex($value);

                list($res,$status) = GRPC('extra')->SetItemAttributeSeach($attrIndex)->wait();
                if ($res->getValue()){
                    return '1';
                }

            }
            if($field == 'order') {// 排序
                /*$client = new Psp\Item\ItemExtraServiceClient('127.0.0.1:9300', [
                    'credentials' => Grpc\ChannelCredentials::createInsecure()
                ]);*/


                $attr_id = new Psp\Item\ItemAttributeId();
                $attr_id->setAttrId($id_value);

                $attrOrder = new Psp\Item\ItemAttributeOrder();
                $attrOrder->setAttributeId($attr_id);
                $attrOrder->setOrder($value);
                list($res,$status) = GRPC('extra')->SetItemAttributeOrder($attrOrder)->wait();
                if ($res->getValue()){
                    return '1';
                }

            }
        }
        //品牌
        if($table == 'brand'){
            /*$client = new Psp\Item\ItemBrandServiceClient('127.0.0.1:9300', [
                'credentials' => Grpc\ChannelCredentials::createInsecure()]);*/
            //是否推荐
            if($field == 'is_recommend'){
                $recommend = new \Psp\Item\BrandRecommendInfo();
                $recommend ->setBrandId($id_value);
                $recommend ->setIsRecommend($value);

                list($res,$status) =GRPC('extra')->SetBrandRecommend($recommend)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            //排序
            if($field =='sort' ){
                $sort = new \Psp\Item\BrandSortInfo();
                $sort->setBrandId($id_value);
                $sort->setSort($value);
                list($res,$status) =GRPC('extra')->SetBrandSort($sort)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }

        }
        //分类
        if($table =='category') {
            /*$client = new Psp\Item\ItemCatServiceClient('127.0.0.1:9300', [
                'credentials' => Grpc\ChannelCredentials::createInsecure()
            ]);*/
            if ($field == 'name') {
                $name = new \Psp\Item\CatName();
                $name->setId($id_value);
                $name->setName($value);
                list($res,$status) =GRPC('cat')->SetCatName($name)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if ($field == 'mobile_name') {
                $mobile_name = new \Psp\Item\CatMobileName();
                $mobile_name->setId($id_value);
                $mobile_name->setMobileName($value);
                list($res,$status) =GRPC('cat')->SetCatMobileName($mobile_name)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if ($field == 'is_recommend') {
                $is_recommend = new \Psp\Item\CatIsRecommend();
                $is_recommend->setId($id_value);
                $is_recommend->setIsRecommend($value);
                list($res,$status) =GRPC('cat')->SetCatIsRecommend($is_recommend)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if ($field == 'is_show') {
                $is_show = new \Psp\Item\CatIsShow();
                $is_show->setId($id_value);
                $is_show->setIsShow($value);
                list($res,$status) =GRPC('cat')->SetCatIsShow($is_show)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }

            if ($field == 'cat_group') {
                $cat_group = new \Psp\Item\CatGroup();
                $cat_group->setId($id_value);
                $cat_group->setCatGroup($value);
                list($res,$status) =GRPC('cat')->SetCatGroup($cat_group)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if ($field == 'sort_order') {
                $sort_order = new \Psp\Item\CatSort();
                $sort_order->setId($id_value);
                $sort_order->setSortOrder($value);
                list($res,$status) =GRPC('cat')->SetCatSort($sort_order)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
        }
        //商品
        if($table == 'item'){
            /*$client = new Psp\Item\ItemServiceClient('127.0.0.1:9300', [
                'credentials' => Grpc\ChannelCredentials::createInsecure()
            ]);*/
            if($field == 'is_recommend'){
                $is_recommend =new \Psp\Item\ItemIsRecommend();
                $is_recommend->setItemId($id_value);
                $is_recommend->setIsRecommend($value);
                list($res,$status) =GRPC('item')->SetItemIsRecommend($is_recommend)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if($field == 'is_new'){
                $is_new =new \Psp\Item\ItemIsNew();
                $is_new->setItemId($id_value);
                $is_new->setIsNew($value);
                list($res,$status) =GRPC('item')->SetItemNew($is_new)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if($field == 'is_hot'){
                $is_hot =new \Psp\Item\ItemIsHot();
                $is_hot->setItemId($id_value);
                $is_hot->setIsHot($value);
                list($res,$status) =GRPC('item')->SetItemHot($is_hot)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if($field == 'is_on_sale'){
                $is_on_sale =new \Psp\Item\ItemIsOnSale();
                $is_on_sale->setItemId($id_value);
                $is_on_sale->setIsOnSale($value);
                list($res,$status) =GRPC('item')->SetItemIsOnSale($is_on_sale)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
            if($field == 'sort'){
                $sort = new \Psp\Item\ItemSort();
                $sort->setItemId($id_value);
                $sort->setSort($value);
                list($res,$status) =GRPC('item')->SetItemSort($sort)->wait();
                if ($res->getValue()){
                    return '1';
                }
            }
        }
        //店铺
        if($table == 'store'){
            if($value == -1){
                $value =2 ;// 关闭
            }
            $status = new Psp\Store\ShopStatus();
            $status->setApplyId($id_value);
            $status->setShopStatus($value);
            list($reply,$status) = GRPC('sellerstore')->SetShopStatus($status)->wait();
            if($reply->getValue()){
                return '1';
            }

        }
        adminOperateLog('修改指定表数据字段',5);
    }

    public function about(){
    	return $this->fetch();
    }

}
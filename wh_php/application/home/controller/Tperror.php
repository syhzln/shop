<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */ 
namespace app\home\controller;
use think\Controller;
class Tperror extends Controller {

	public function tp404($msg='',$url=''){
		$msg = empty($msg) ? '您可能输入了错误的网址，或者该页面已经不存在了哦。' : $msg;
		$this->assign('error',$msg);		
		$walhao_config = array();
//		$tp_config = M('config')->cache(true,WALHAO_CACHE_TIME)->select();
//		foreach($tp_config as $k => $v)
//		{
//			if($v['name'] == 'hot_keywords'){
//				$walhao_config['hot_keywords'] = explode('|', $v['value']);
//			}
//			$walhao_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
//		}
		$this->assign('goods_category_tree', get_goods_category_tree());
//		$brand_list = M('brand')->cache(true,WALHAO_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
//		$this->assign('brand_list', $brand_list);
		$this->assign('walhao_config', $walhao_config);
		return $this->fetch('public/tp404');
	}
	
}
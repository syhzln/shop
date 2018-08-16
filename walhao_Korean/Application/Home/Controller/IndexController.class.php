<?php
namespace Home\Controller;
use Home\Logic\StoreLogic;
use Think\Page;
use Think\Verify;

class IndexController extends BaseController {

    public function index(){
        // 如果是手机跳转到 手机模块
        if(true == isMobile()){
            header("Location: ".U('Mobile/Index/index'));
        }
        //会员登录跟新职称等级
        if ($user_id = session('user')['user_id']){
            update_user_level($user_id);
        }

        $hot_goods = $hot_cate = $cateList = array();
        $sql = "select a.goods_name,a.goods_id,a.shop_price,a.market_price,a.cat_id1,b.parent_id_path,b.name from __PREFIX__goods as a left join ";
        $sql .= " __PREFIX__goods_category as b on a.cat_id1=b.id where  a.is_on_sale=1 and a.goods_state = 1 and a.is_hot = 1 order by a.sort";//二级分类下热卖商品
        $index_hot_goods = M()->query($sql);//首页热卖商品

		if($index_hot_goods){
			foreach($index_hot_goods as $val){
				$cat_path = explode('_', $val['parent_id_path']);
				$hot_goods[$cat_path[1]][] = $val;
			}
		}

        $hot_category = M('goods_category')->where("is_hot=1 and level=3 and is_show=1")->select();//热门三级分类
        foreach ($hot_category as $v){
        	$cat_path = explode('_', $v['parent_id_path']);
        	$hot_cate[$cat_path[1]][] = $v;
        }

        foreach ($this->cateTrre as $k=>$v){
            if($v['is_hot']==1){
        		$v['hot_goods'] = empty($hot_goods[$k]) ? '' : $hot_goods[$k];
        		$v['hot_cate'] = empty($hot_cate[$k]) ? '' : $hot_cate[$k];
        		$cateList[] = $v;
        	}
        }

        $this->assign('cateList',$cateList);
        $this->display();
    }

    /**
     *  公告详情页
     */
    public function notice(){
        $this->display();
    }

    // 二维码
    public function qr_code(){
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.tp-shop.cn/Home/Index/erweima/data/www.99soubao.com
         require_once 'ThinkPHP/Library/Vendor/phpqrcode/phpqrcode.php';
          //import('Vendor.phpqrcode.phpqrcode');
            error_reporting(E_ERROR);
            $url = urldecode($_GET["data"]);
            \QRcode::png($url);
    }

    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';

        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }


    /**
     * 店铺街
     * @author dyr
     * @time 2016/08/26
     */
    public function street()
    {
        $sc_id = I('get.sc_id');
        $province = I('get.province', 0);
        $city = I('get.city', 0);
        $order = I('order',0);
        if (empty($province) && empty($city)) {
            //header("Content-type:text/html;charset=utf-8");
            $address = GetIpLookup();
            if (!empty($address)) {
                $address = M('region')->where("`level` = 2 and `name` like  '%{$address['city']}%'")->find();
                $province = $address['parent_id'];
                $city = $address['id'];
                $location = U('street', array('province' => $province, 'city' => $city));
                header("Location: $location");// 根据城市来帅选
            }
        }
        $store_class = M('store_class')->field('sc_id,sc_name')->where('')->select();
        $store_logic = new StoreLogic();
        $store_list = $store_logic->getStoreList($sc_id, $province, $city, $order, 10);
        $region = M('region')->where("`level` = 1")->getField("id,name");
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('region', $region);
        $this->assign('page', $store_list['show']);// 赋值分页输出
        $this->assign('pages', $store_list['pages']);
        $this->assign('store_list', $store_list['result']);
        $this->assign('store_class', $store_class);//店铺分类
        $this->display();
    }

    public function store_qrcode(){
    	require_once 'ThinkPHP/Library/Vendor/phpqrcode/phpqrcode.php';
    	error_reporting(E_ERROR);
    	$store_id = I('store_id',1);
    	\QRcode::png(U('Mobile/Store/index',array('store_id'=>$store_id),true,true));
    }

    /**
     * 猜你喜欢
     * @author dyr
     */
    public function ajax_favorite()
    {
        $p = I('p', 1);
        $item = I('i',5);
        $tpl = I('tpl');
        $goods_where = array('g.is_recommend' => 1, 'g.is_on_sale' => 1, 'g.goods_state' => 1);
        $favourite_goods = M('goods')
            ->alias('g')
            ->join('__STORE__ s')
            ->field('g.*,s.store_name')
            ->where($goods_where)
            ->order('sort DESC')
            ->page($p,$item)
            ->cache(true, TPSHOP_CACHE_TIME)
            ->select();
        $this->assign('favourite_goods', $favourite_goods);
        if($tpl){
            $this->display($tpl);
        }else{
            $this->display();
        }
    }

    //语言切换
    public function languageSwitch(){
        del_dir(HTML_PATH);
        del_dir(HTML_PATH_HOME);
        $lang = $_GET['lang'];
        cookie('lang',$lang);
        echo '1';
    }
}

<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\logic;
use think\Model;
use app\home\logic\CartLogic;
use think\Db;
use Psp;
use Grpc;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class GoodsLogic extends Model
{

    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选品牌
     */
    public function get_filter_brand($brand_list,$filter_param, $action)
    {
        if (!empty($filter_param['brand_id'])) {
            return array();
        }
        if (empty($brand_list)){
            return [];
        }
        foreach ($brand_list as $k => $v) {
            // 帅选参数
            $filter_param['brand_id'] = $v['brand_id'];
            $brand_list[$k]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        return $brand_list;
    }


    /**
     * @param $q
     * @param $filter_param
     * @param $action
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品搜索列表页帅选品牌
     */
    public function get_search_brand($brand_list,$filter_param, $action)
    {
        if (!empty($filter_param['brand_id'])) {
            return array();
        }
        if (empty($brand_list)){
            return [];
        }
        foreach ($brand_list as $k => $v) {
            // 帅选参数
            $filter_param['brand_id'] = $v['brand_id'];
            $brand_list[$k]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        return $brand_list;
    }


    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode  0  返回数组形式  1 直接返回result
     * @return array 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选规格
     */
    public function get_filter_spec($spec_list, $filter_param, $action, $mode = 0)
    {
        $old_spec = $filter_param['spec'];
        foreach (explode('@',$old_spec) as $k =>$v){
            unset($spec_list[explode('_',$v)[0]]);
        }

        if (empty($spec_list)){
            return[];
        }
        foreach ($spec_list as $k2 =>$v2){
            foreach ($v2['item'] as $k3 =>$v3){
                if (!empty($old_spec))
                    $filter_param['spec'] = $old_spec . '@' . $v3['key'] . '_' . $v3['val'];
                else
                    $filter_param['spec'] = $v3['key'] . '_' . $v3['val'];

                $spec_list[$k2]['item'][$k3]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
            }
        }
        return $spec_list;
    }

    /**
     * @param array $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array
     * 获取商品列表页帅选属性
     */
    public function get_filter_attr($price,$brand_ids,$filter_param, $action, $mode = 0)
    {
        $condition = new Psp\Home\ItemCondition();
        $condition->setBrandId($brand_ids);
        $condition->setAttr($filter_param['attr']);
        $condition->setSkuId($filter_param['spec']);
        $condition->setIsOnSale(true);
        $condition->setCategoryId($filter_param['id']);
        $condition->setStartPrice($price[0]);
        $condition->setEndPrice($price[1]);
        list($res,$status) = GRPC('GoodsList')->GetAllCondition($condition)->wait();
        foreach ($res->getAttr() as $k=>$v) {
            $k = $v->getAttrId();
            $attr_list[$k]['attr_id'] = $v->getAttrId();
            $attr_list[$k]['attr_name'] = $v->getAttrName();
            foreach($v->getAttrValue() as $k1=>$v1){
                $attr_list[$k]['attr_value'][$k1]['attr_value']= $v1->getAttrValue();
                $attr_list[$k]['attr_value'][$k1]['key'] = $v1->getKey();
            }
        }

        $old_attr = $filter_param['attr'];
        if (empty($attr_list)){
            return[];
        }
        foreach ($attr_list as $k1 =>$v1){
            foreach ($v1['attr_value'] as $k2 =>$v3){
                if (!empty($old_attr))
                    $filter_param['attr'] = $old_attr . '@' . $v3['key'] . '_' . $v3['attr_value'];
                else
                    $filter_param['attr'] = $v3['key'] . '_' . $v3['attr_value'];

                $attr_list[$k1]['attr_value'][$k2]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
            }
        }
        return $attr_list;
    }

    /**
     * 获取某个商品的评论统计
     * c0:全部评论数  c1:好评数 c2:中评数  c3差评数
     * rate1:好评率 rate2:中评率  c3差评率
     * @param $goods_id
     * @return array
     */
    public function commentStatistics($goods_id)
    {
        $commentWhere = ['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>0];
//        $c1 = M('comment')->where($commentWhere)->where('ceil((deliver_rank + goods_rank + service_rank) / 3) in (4,5)')->count();
//        $c2 = M('comment')->where($commentWhere)->where('ceil((deliver_rank + goods_rank + service_rank) / 3) in (3)')->count();
//        $c3 = M('comment')->where($commentWhere)->where('ceil((deliver_rank + goods_rank + service_rank) / 3) in (0,1,2)')->count();
//        $c4 = M('comment')->where($commentWhere)->where("img !='' and img NOT LIKE 'N;%'")->count(); // 晒图
//        $c0 = $c1 + $c2 + $c3; // 所有评论
//        if($c0 <= 0){
//            $rate1 = 100;
//            $rate2 = 0;
//            $rate3 = 0;
//        }else{
//            $rate1 = ceil($c1 / $c0 * 100); // 好评率
//            $rate2 = ceil($c2 / $c0 * 100); // 中评率
//            $rate3 = ceil($c3 / $c0 * 100); // 差评率
//        }
//        return array('c0' => $c0, 'c1' => $c1, 'c2' => $c2, 'c3' => $c3, 'c4'=>$c4,'rate1' => $rate1, 'rate2' => $rate2, 'rate3' => $rate3);
    }

    /**
     * 商品收藏
     * @param $user_id|用户id
     * @param $goods_id|商品id
     * @return array
     */
    public function collect_goods($goods_id)
    {
        $payload =validate_json_web_token(cookie('token'));
        $user_id = $payload['user_id']?$payload['user_id']:0;
        if (!is_numeric($user_id) || $user_id <= 0) return array('status' => -1, 'msg' => '必须登录后才能收藏', 'result' => array());
        $coll = new Psp\Store\CollectionCondition();
        $coll->setUserId($user_id);
        $coll->setItemId($goods_id);
        list($res,$status) =  GRPC('sellerstore')->UserCollection($coll)->wait();
        if ($res->getRet() == "ok"){
            return array('status' => 1, 'msg' => '收藏成功!请到个人中心查看', 'result' => array());
        } else {
            if ($res->getMsg() == "不能再次收藏"){
                return array('status' => -3, 'msg' => '商品已收藏,请勿重复收藏', 'result' => array());
            } else{
                return array('status' => -2, 'msg' => '收藏失败，请重试！', 'result' => array());
            }
        }

    }

    /**
     * 获取商品规格
     * @param $goods_id|商品id
     * @return array
     */
    public function get_spec($goods_id)
    {
        //商品规格 价钱 库存表 找出 所有 规格项id
//        $keys = M('SpecGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
//        if ($keys) {
//            $specImage = M('SpecImage')->where(['goods_id'=>$goods_id,'src'=>['<>','']])->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
//            $keys = str_replace('_', ',', $keys);
//            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.id";
//            $filter_spec2 = \think\Db::query($sql);
//            foreach ($filter_spec2 as $key => $val) {
//                $filter_spec[$val['name']][] = array(
//                    'item_id' => $val['id'],
//                    'item' => $val['item'],
//                    'src' => $specImage[$val['id']],
//                );
//            }
//        }
        return $filter_spec;
    }

    /**
     * 获取相关分类
     * @param $cat_id|分类id
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     */
    public function get_siblings_cate($cat_id)
    {
        if (empty($cat_id))
            return array();
//        $cate_info = M('goods_category')->where("id", $cat_id)->find();
//        $siblings_cate = M('goods_category')->where(['id'=>['<>',$cat_id],'parent_id'=>$cate_info['parent_id']])->select();
//        return empty($siblings_cate) ? array() : $siblings_cate;
    }

    /**
     * 看了又看
     */
    public function get_look_see($goods)
    {
//        return M('goods')->where(['goods_id'=>['<>',$goods['goods_id']],'cat_id'=>['<>',$goods['cat_id']],'is_on_sale'=>1])->limit(12)->select();
    }


    /**
     * 筛选的价格期间
     * @param $goods_id_arr|帅选的分类id
     * @param $filter_param
     * @param $action
     * @param int $c 分几段 默认分5 段
     * @return array
     */
    function get_filter_price($price_range,$filter_param, $action, $c = 5)
    {
        if (!empty($filter_param['price']))
            return array();
        if (empty($price_range)){
            return [];
        }
        foreach($price_range as $k1 => $v1){
            $filter_param['price'] = $price_range[$k1]['price'];
            $price_range[$k1]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        return $price_range;
    }


    /**
     * 筛选的价格期间
     * @param $goods_id_arr|帅选的分类id
     * @param $filter_param
     * @param $action
     * @param int $c 分几段 默认分3 段
     * @return array
     */
    function get_search_price($price_range,$filter_param, $action, $c = 5)
    {
        if (!empty($filter_param['price']))
            return array();
        if (empty($price_range)){
            return [];
        }
        foreach($price_range as $k1 => $v1){
            $filter_param['price'] = $price_range[$k1]['price'];
            $price_range[$k1]['href'] = urldecode(U("Goods/$action", $filter_param, ''));
        }
        return $price_range;
    }

    /**
     * 筛选条件菜单
     * @param $filter_param
     * @param $action
     * @return array
     */
    function get_filter_menu($filter_param, $action)
    {
        $menu_list = array();
        // 品牌
        if (!empty($filter_param['brand_id'])) {
            $brand = new Psp\Newhome\ItemBrand();
            $brand->setBrandId(implode(',',explode('_',$filter_param['brand_id'])));
            list($res,$status) = GRPC('NewGoodsList')->GetBrand($brand)->wait();
            foreach($res->getParamList() as $v){
                $b['text'] = $v->getText();
            }
            $tmp = $filter_param;
            unset($tmp['brand_id']); // 当前的参数不再带入
            $b['href'] = urldecode(U("Goods/$action", $tmp, ''));
            $menu_list[] = $b;
        }
        // 规格
        if (!empty($filter_param['spec'])) {
            $spec_group = explode('@', $filter_param['spec']);
            $spec = new Psp\Newhome\ItemSpec();
            $spec->setSpec($filter_param['spec']);
            list($res, $status) = GRPC('NewGoodsList')->GetSpec($spec)->wait();
            foreach ($res->getParamList() as $k1 => $v1) {
                $s[$k1]['text'] = $v1->getText();
            }
            foreach ($spec_group as $k => $v) {
                $tmp = $spec_group;
                $tmp2 = $filter_param;
                unset($tmp[$k]);
                $tmp2['spec'] = implode('@', $tmp); // 当前的参数不再带入
                $s[$k1]['href'] = urldecode(U("Goods/$action", $tmp2, ''));
            }
            foreach ($s as $k2 =>$v2){
                $menu_list[] = $s[$k2];
            }
        }
        // 价格
        if (!empty($filter_param['price'])) {
            $price_menu['text'] = "价格:" . $filter_param['price'];
            unset($filter_param['price']);
            $price_menu['href'] = urldecode(U("Goods/$action", $filter_param, ''));
            $menu_list[] = $price_menu;
        }

        return $menu_list;
    }

    /**
     * 传入当前分类 如果当前是 2级 找一级
     * 如果当前是 3级 找2 级 和 一级
     * @param  $goodsCate
     */
    function get_goods_cate(&$goodsCate)
    {
        if (empty($goodsCate)) return array();
        $cateAll = get_goods_category_tree();
        if ($goodsCate['level'] == 1) {
            $cateArr = $cateAll[$goodsCate['id']]['tmenu'];
            $goodsCate['parent_name'] = $goodsCate['name'];
            $goodsCate['select_id'] = 0;
        } elseif ($goodsCate['level'] == 2) {
            $cateArr = $cateAll[$goodsCate['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$goodsCate['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $goodsCate['id'];//默认展开分类
            $goodsCate['select_id'] = 0;
        } else {
            $page = new Psp\Pagination();
            $page->setSortAsc(true);
            $page->setSortBy("goods_id");
            $page->setIndex(1);
            $page->setLimit(5);

            $cate = new Psp\Home\CurrentId();
            $cate->setId($goodsCate['parent_id']);
            $cate->setPagination($page);
            list($res,$status) = GRPC('GoodsList')->GetCurrentId($cate)->wait();
            foreach ($res->getCatInfo() as $k=>$v) {
                $arrd[$k]['parent_id'] = $v->getParentId();
            }
            $parent = $arrd;//M('GoodsCategory')->where("id", $goodsCate['parent_id'])->order('`sort_order` desc')->find();//父类
            $cateArr = $cateAll[$parent['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$parent['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $arrd['parent_id'];//$parent['id'];
            $goodsCate['select_id'] = $goodsCate['id'];//默认选中分类
        }
//        var_dump($cateArr);
        return $cateArr;

    }

    /**
     * @param  $brand_id|帅选品牌id
     * @param  $price|帅选价格
     * @return array|mixed
     */
    function getGoodsIdByBrandPrice($brand_id, $price)
    {
        if (empty($brand_id) && empty($price))
            return array();
        $brand_select_goods=$price_select_goods=array();
        if ($brand_id) // 品牌查询
        {
            $brand_id_arr = explode('_', $brand_id);
            $brand_ids = implode(',',$brand_id_arr);
            $page = new Psp\Pagination();
            $page->setSortAsc(true);
            $page->setSortBy("goods_id");
            $page->setIndex(1);
            $page->setLimit(5);
            $brand = new Psp\Home\ItemBrandId();
            $brand->setBrandId($brand_ids);
            $brand->setPagination($page);
            list($res,$status) = GRPC('GoodsList')->GetItemBrand($brand)->wait();
            foreach ($res->getItemId() as $k => $v) {
                $goods_brand_ids[$k]['goods_id'] = $v->getGoodsId();
            }
            $goods_brand_ids = get_arr_column($goods_brand_ids, 'goods_id');
            $brand_select_goods = $goods_brand_ids;
        }
        if ($price)// 价格查询
        {
            $price = explode('-', $price);
            $page = new Psp\Pagination();
            $page->setSortAsc(true);
            $page->setSortBy("goods_id");
            $page->setIndex(1);
            $page->setLimit(5);
            $price_goods = new Psp\Home\ItemPrice();
            $price_goods->setStartPrice($price[0]);
            $price_goods->setEndPrice($price[1]);
            $price_goods->setPagination($page);
            list($res,$status) = GRPC('GoodsList')->GetItemPrice($price_goods)->wait();
            foreach ($res->getItemId() as $k => $v) {
                $goods_price_ids[$k]['goods_id'] = $v->getGoodsId();
            }
            $goods_price_ids = get_arr_column($goods_price_ids, 'goods_id');
            $price_select_goods = $goods_price_ids;
        }
        if($brand_select_goods && $price_select_goods)
            $arr = array_intersect($brand_select_goods,$price_select_goods);
        else
            $arr = array_merge($brand_select_goods,$price_select_goods);
        return $arr ? $arr : array();
    }

    /**
     * 根据规格 查找 商品id
     * @param $spec|规格
     * @return array|\type
     */
    function getGoodsIdBySpec($spec)
    {
        if (empty($spec))
            return array();
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("itemId");
        $page->setIndex(1);
        $page->setLimit(5);
        $spec_goods = new Psp\Home\ItemSpecId();
        $spec_goods->setSkuId($spec);
        $spec_goods->setPagination($page);
        list($res,$status) = GRPC('GoodsList')->GetItemSpec($spec_goods)->wait();
        foreach ($res->getItemId() as $k => $v) {
            $goods_spec_ids[$k]['goods_id'] = $v->getGoodsId();
        }

        $arr = get_arr_column( $goods_spec_ids, 'goods_id');  // 只获取商品id 那一列
        return ($arr ? $arr : array_unique($arr));
    }

    /**
     * @param $attr|属性
     * @return array|mixed
     * 根据属性 查找 商品id
     * 59_直板_翻盖
     * 80_BT4.0_BT4.1
     */
    function getGoodsIdByAttr($attr)
    {
        if (empty($attr))
            return array();
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("itemId");
        $page->setIndex(1);
        $page->setLimit(5);
        $attr_goods = new Psp\Home\ItemAttr();
        $attr_goods->setAttr($attr);
        $attr_goods->setPagination($page);
        list($res,$status) = GRPC('GoodsList')->GetItemAttr($attr_goods)->wait();
        foreach ($res->getItemId() as $k => $v) {
            $goods_attr_ids[$k]['goods_id'] = $v->getGoodsId();
        }
        $arr =get_arr_column( $goods_attr_ids, 'goods_id');
        return ($arr ? $arr : array_unique($arr));
    }

    /**
     * 获取地址
     * @return array
     */
    function getRegionList()
    {
        $res = S('getRegionList');
        if(!empty($res))
            return $res;

        //获取全部省份
        $areamap= new \area\area();
        $prov = $areamap->getProv();

        $ip_location = array();
        $city_location = array();
        $c = array();
        $exclude =array();

        foreach ($prov as $k => $v){
            $c[$k] = $areamap->getCity($k); // 获取全部城市
        }

        // 获取城市
        foreach ($c as $k=>$v){
            if ($v == ''){
                $exclude[$k] = ''; // 排除香港澳门台湾
                continue;
            }

            foreach ($v as $a=>$b){
                $ci[$k][$a]['id'] = $a;
                $ci[$k][$a]['name'] = $b;
            }
        }

        $proList = array_diff_key($prov,$exclude); // 省份
        foreach ($proList as $k => $v){
             $parent_region[$k]['id'] = $k;
             $parent_region[$k]['name'] = $v;
        }


        foreach ($ci as $k =>$v){
            foreach ($v as $ke => $va){
                $city_location[$k][] =$va;
            }
        } // 编辑数组

        foreach ( $proList as $k => $v){
            $ip_location[$parent_region[$k]['name']] = array('id'=>$k,'root'=>0,'djd'=>1,'c'=>$city_location[0]['id']);
        }

        $res = array(
            'ip_location'=>$ip_location,
            'city_location'=>$city_location
        );

        S('getRegionList',$res);
        return $res;
    }

    /**
     * 寻找Region_id的父级id
     * @param $cid
     * @return array
     */
    function getParentRegionList($cid){
        //$pids = '';
        $pids = array();
//        $parent_id =  M('region')->where(array('id'=>$cid))->getField('parent_id');
        if($parent_id != 0){
            //$pids .= $parent_id;
            array_push($pids,$parent_id);
            $npids = $this->getParentRegionList($parent_id);
            if(!empty($npids)){
                //$pids .= ','.$npids;
                $pids = array_merge($pids,$npids);
            }

        }
        return $pids;
    }

    /**
     * 商品物流配送和运费
     * @param $goods_id
     * @param $region_id
     * @return array
     */

    function getGoodsDispatching($goods_id,$region_id,$weight,$is_free_shipping)
    {
        $return_data = array('status' => 1, 'msg' => '');

        $goodsInfo['weight'] = $weight;// 商品重量
        $goodsInfo['is_free_shipping'] = $is_free_shipping;// 是否包邮

        //检查商品是否包邮  是 直接返回  不是就判断物流
        if ($goodsInfo['is_free_shipping'] == 1) {
            $return_data['msg'] = '有货';
            $return_data['result'] = array(array('shipping_name' => '包邮', 'freight' => 0));
            return $return_data;
        }

        // 物流信息
        $goodsLogic = new GoodsLogic();
        $prov = substr($region_id, 4, 2); // 获取省级区域编码

        $dev = new Psp\Store\DeliverCondition();
        $dev->setGoodsId($goods_id);
        $dev->setRegion($prov);
        list($res, $status) = GRPC('sellerstore')->GetDeliver($dev)->wait();

        // 判断该地区 是否可发货
        if(empty($res->getDeliver())){
            // 不可发货 直接返回
            $return_data['status'] = -1;
            $return_data['msg'] = '无货';
            return $return_data;
        }

        // 获取的发货的物流信息
        foreach ($res->getDeliver() as $k => $v) {
            if ($v->getConfig()){
                $goods_shipping[$k]['freight'] = $goodsLogic->getFreight($v->getConfig(),$goodsInfo['weight']);
                $goods_shipping[$k]['shipping_name'] = $v->getName();
            }
        }
        $return_data = array(
            'status'=>1,
            'msg'=>'可发货',
            'result'=>$goods_shipping
        );
        return $return_data;

    }

    /**
     *网站自营,入驻商家,货到付款,仅看有货,促销商品
     * @param $sel|筛选条件
     * @param array $cat_id|分类ID
     * @return mixed
     */
    function getFilterSelected($sel ,$cat_id = array(1)){
        $where = " 1 = 1 ";
//        $Goods = M('goods')->where("cat_id" ,"in" ,implode(',', $cat_id));
        //查看全部
        if($sel == 'selall'){
            $where .= '';
        }
        //促销商品
        if($sel == 'prom_type'){
            $where .= ' and prom_type = 3';
        }
        //看有货
        if($sel == 'store_count'){
            $where .= ' and store_count > 0';
        }
        //看包邮
        if($sel == 'free_post'){
            $where .= ' and is_free_shipping=1';
        }
        //看全部
//        if($sel == 'all'){
//            $arrid = $Goods->getField('goods_id', true);
//        }else{
//            $arrid = $Goods->where($where)->getField('goods_id', true);
//        }
//        return $arrid;
    }

    /**
     * 用户浏览记录
     * @author lxl
     * @time  17-4-20
     */
    public function add_visit_log($user_id,$goods){
//        $record = M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->find();
//        if($record){
//            M('goods_visit')->where(array('user_id'=>$user_id,'goods_id'=>$goods['goods_id']))->save(array('visittime'=>time()));
//        }else{
//            $visit = array('user_id'=>$user_id,'goods_id'=>$goods['goods_id'],'visittime'=>time(),'cat_id'=>$goods['cat_id'],'extend_cat_id'=>$goods['extend_cat_id']);
//            M('goods_visit')->add($visit);
//        }
    }

    /**
     * 在有价格阶梯的情况下，根据商品数量，获取商品价格
     * @param $goods_num|购买的商品数
     * @param $goods_price|商品默认单价
     * @param $price_ladder|价格阶梯数组
     * @return mixed
     */
    public function getGoodsPriceByLadder($goods_num, $goods_price, $price_ladder)
    {
        $price_ladder = array_values(array_sort($price_ladder,'amount','asc'));
        $price_ladder_count = count($price_ladder);
        for ($i = 0; $i < $price_ladder_count; $i++) {
            if($i == 0 && $goods_num < $price_ladder[$i]['amount']){
                return $goods_price;
            }
            if($goods_num >= $price_ladder[$i]['amount'] && $goods_num < $price_ladder[$i+1]['amount']){
                return $price_ladder[$i]['price'];
            }
            if($i == ($price_ladder_count - 1)){
                return $price_ladder[$i]['price'];
            }
        }
    }

    /**
     * 计算根据商品重量计算物流运费
     * @param  $shipping_config|物流配置
     * @param  $weight|重量
     * @return int
     */

    public function getFreight($shipping_config, $weight)
    {
        $shipping_config = unserialize($shipping_config);
        $shipping_config['money'] = $shipping_config['money'] ? $shipping_config['money'] : 0;

        // 1000 克以内的 只算个首重费
        if ($weight < $shipping_config['first_weight']) {
            return $shipping_config['money'];
        }
        // 超过 1000 克的计算方法
        $weight = $weight - $shipping_config['first_weight']; // 续重
        $weight = ceil($weight / $shipping_config['second_weight']); // 续重不够取整
        $freight = $shipping_config['money'] + $weight * $shipping_config['add_money']; // 首重 + 续重 * 续重费

        return $freight;
    }
    
    /**
     * 是否收藏商品
     * @param type $user_id
     * @param type $goods_id
     * @return type
     */
    public function isCollectGoods($user_id, $goods_id)
    {
//        $collect = M('goods_collect')->where(['user_id' => $user_id, 'goods_id' => $goods_id])->find();
//        return $collect ? 1 : 0;
    }


}

 
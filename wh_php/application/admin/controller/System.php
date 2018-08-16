<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-10-09
 */

namespace app\admin\controller;

use app\admin\logic\GoodsLogic;
use think\db;
use think\Page;
use think\Cache;
use Psp;
use Grpc;
use think\Log;

class System extends Base
{
    /*
     * 配置入口
     */
    public function index()
    {
        /*配置列表*/
        $group_list = [
            'shop_info' => '网站信息',
            'basic' => '基本设置',
            'sms' => '短信设置',
            'shopping' => '购物流程设置',
            'smtp' => '邮件设置',
            'water' => '水印设置',
            'push' => '推送设置',
            'oss' => '对象存储'
        ];
        $this->assign('group_list', $group_list);
        $inc_type = I('get.inc_type', 'shop_info');
        $this->assign('inc_type', $inc_type);
        $config = tpCache($inc_type);
        if ($inc_type == 'shop_info') {
            /*$province = M('region')->where(array('parent_id' => 0))->select();
            $city = M('region')->where(array('parent_id' => $config['province']))->select();
            $area = M('region')->where(array('parent_id' => $config['city']))->select();
            $this->assign('province', $province);
            $this->assign('city', $city);
            $this->assign('area', $area);*/
        }
        adminOperateLog('配置列表',6);
        $this->assign('config', $config);//当前配置项
        //C('TOKEN_ON',false);
        return $this->fetch($inc_type);
    }

    /*
     * 新增修改配置
     */
    public function handle()
    {
        $param = I('post.');
        $inc_type = $param['inc_type'];
        //unset($param['__hash__']);
        tpCache($inc_type, $param);
        adminOperateLog('新增修改配置',6);

        $this->success("操作成功", U('System/index', array('inc_type' => $inc_type)));
    }

    /**
     * 自定义导航
     */
    public function navigationList()
    {
//        $model = M("Navigation");
//        $navigationList = $model->order("id desc")->select();
//        $this->assign('navigationList', $navigationList);
//        return $this->fetch('navigationList');
    }

    /**
     * 添加修改编辑 前台导航
     */
    public function addEditNav()
    {
        //$model = D("Navigation");
        //dump($_POST);
        //exit;
        /*if (IS_POST) {
            if (I('id')) {
                $model->update(I('post.'));
            } else {
                $model->add(I('post.'));
            }
            $this->success("操作成功!!!", U('Admin/System/navigationList'));
            exit;
        }*/
        // 点击过来编辑时
        $id = I('id', 0);
        //$navigation = DB::name('navigation')->where('id', $id)->find();
        // 系统菜单
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $select_option = array();
        if (!empty($cat_list)) {
            foreach ($cat_list as $key => $value) {
                $strpad_count = $value['level'] * 4;
                $select_val = U("/Home/Goods/goodsList", array('id' => $key));
                $select_option[$select_val] = str_pad('', $strpad_count, "-", STR_PAD_LEFT) . $value['name'];
            }
        }
        $system_nav = array(
            'http://www.walhao.com' => '量子时空商城官网',
            '/index.php?m=Home&c=Activity&a=promoteList' => '促销',
            '/index.php?m=Home&c=Activity&a=flash_sale_list' => '抢购',
            '/index.php?m=Home&c=Activity&a=group_list' => '团购',
            '/index.php?m=Home&c=Activity&a=pre_sell_list' => '预售',
            '/index.php?m=Home&c=Goods&a=integralMall' => '积分商城',
        );
        $system_nav = array_merge($system_nav, $select_option);
        $this->assign('system_nav', $system_nav);

        //$this->assign('navigation', $navigation);
        return $this->fetch('_navigation');
    }

    /**
     * 删除前台 自定义 导航
     */
    public function delNav()
    {
        // 删除导航
        //M('Navigation')->where("id", I('id'))->delete();
        //$this->success("操作成功!!!", U('Admin/System/navigationList'));
    }

    public function refreshMenu()
    {
        $pmenu = $arr = array();
        //$rs = M('system_module')->where('level>1 AND visible=1')->order('mod_id ASC')->select();
        /*foreach ($rs as $row) {
            if ($row['level'] == 2) {
                $pmenu[$row['mod_id']] = $row['title'];//父菜单
            }
        }

        foreach ($rs as $val) {
            if ($row['level'] == 2) {
                $arr[$val['mod_id']] = $val['title'];
            }
            if ($row['level'] == 3) {
                $arr[$val['mod_id']] = $pmenu[$val['parent_id']] . '/' . $val['title'];
            }
        }*/
        return $arr;
    }


    /**
     * 清空系统缓存
     */
    public function cleanCache()
    {
        if (IS_POST) {

//                 in_array('cache',$_POST['clear']) && delFile('./Application/Runtime/Cache');// 模板缓存
//                 in_array('data',$_POST['clear'])  && delFile('./Application/Runtime/Data');// 项目数据
//                 in_array('logs',$_POST['clear'])  && delFile('./Application/Runtime/Logs');// logs日志
//                 in_array('temp',$_POST['clear'])  && delFile('./Application/Runtime/Temp');// 临时数据
//                 in_array('cacheAll',$_POST['clear'])  && delFile('./Application/Runtime');// 清除所有
//                 //in_array('goods_thumb',$_POST['clear'])  && delFile('./public/upload/goods/thumb'); // 删除缩略图
//
//                // 删除静态文件
//                $html_arr = glob("./Application/Runtime/Html/*.html");
//                foreach ($html_arr as $key => $val)
//                {
//
//                    in_array('index',$_POST['clear']) && strstr($val,'Home_Index_index.html') && unlink($val); // 首页
//                    in_array('goodsList',$_POST['clear']) && strstr($val,'Home_Goods_goodsList') && unlink($val); // 列表页
//                    in_array('channel',$_POST['clear']) && strstr($val,'Home_Channel_index') && unlink($val);  // 频道页
//
//                    in_array('articleList',$_POST['clear']) && strstr($val,'Index_Article_articleList') && unlink($val);  // 文章列表页
//                    in_array('detail',$_POST['clear']) && strstr($val,'Index_Article_detail') && unlink($val);  // 文章详情
//                    in_array('articleList',$_POST['clear']) && strstr($val,'Doc_Index_index_') && unlink($val);  // 文章列表页
//                    in_array('detail',$_POST['clear']) && strstr($val,'Doc_Index_article_') && unlink($val);  // 文章详情
//
//                    // 详情页
//                    if(in_array('goodsInfo',$_POST['clear']))
//                    {
//                        if(strstr($val,'Home_Goods_goodsInfo') || strstr($val,'Home_Goods_ajaxComment') || strstr($val,'Home_Goods_ajax_consult'))
//                            unlink($val);
//                    }
//                }
//                $this->error("操作完成!!!");
//                exit;
        }
        delFile(RUNTIME_PATH);
        Cache::clear();
        $this->success("操作完成!!!", U('Admin/Admin/index'));
        exit();
        return $this->fetch();
    }

    /**
     * 清空静态商品页面缓存
     */
    public function ClearGoodsHtml()
    {
        $goods_id = I('goods_id');
        if (unlink("./Application/Runtime/Html/Home_Goods_goodsInfo_{$goods_id}.html")) {
            // 删除静态文件
            $html_arr = glob("./Application/Runtime/Html/Home_Goods*.html");
            foreach ($html_arr as $key => $val) {
                strstr($val, "Home_Goods_ajax_consult_{$goods_id}") && unlink($val); // 商品咨询缓存
                strstr($val, "Home_Goods_ajaxComment_{$goods_id}") && unlink($val); // 商品评论缓存
            }
            $json_arr = array('status' => 1, 'msg' => '清除成功', 'result' => '');
        } else {
            $json_arr = array('status' => -1, 'msg' => '未能清除缓存', 'result' => '');
        }
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 商品静态页面缓存清理
     */
    public function ClearGoodsThumb()
    {
        $goods_id = I('goods_id');
        delFile(UPLOAD_PATH . "goods/thumb/" . $goods_id); // 删除缩略图
        $json_arr = array('status' => 1, 'msg' => '清除成功,请清除对应的静态页面', 'result' => '');
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    /**
     * 清空 文章静态页面缓存
     */
    public function ClearAritcleHtml()
    {
        $article_id = I('article_id');
        unlink("./Application/Runtime/Html/Index_Article_detail_{$article_id}.html"); // 清除文章静态缓存
        unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_api.html"); // 清除文章静态缓存
        unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_phper.html"); // 清除文章静态缓存
        unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_android.html"); // 清除文章静态缓存
        unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_ios.html"); // 清除文章静态缓存
        $json_arr = array('status' => 1, 'msg' => '操作完成', 'result' => '');
        $json_str = json_encode($json_arr);
        exit($json_str);
    }

    //发送测试邮件
    public function send_email()
    {
        $param = I('post.');
        tpCache($param['inc_type'], $param);
        $res = send_email($param['test_eamil'], '后台测试', '测试发送验证码:' . mt_rand(1000, 9999));
        exit(json_encode($res));
    }

    /**
     *  管理员登录后 处理相关操作
     */
    public function login_task()
    {

        /*** 随机清空购物车的垃圾数据*/
        $time = time() - 3600; // 删除购物车数据  1小时以前的
        //M("Cart")->where("user_id = 0 and  add_time < $time")->delete();
        $today_time = time();

        // 删除 cart表垃圾数据 删除一个月以前的
        $time = time() - 2592000;
        //M("cart")->where("add_time < $time")->delete();
        // 删除 tp_sms_log表垃圾数据 删除一个月以前的短信
        //M("sms_log")->where("add_time < $time")->delete();

        // 发货后满多少天自动收货确认
        $auto_confirm_date = tpCache('shopping.auto_confirm_date');
        $auto_confirm_date = $auto_confirm_date * (60 * 60 * 24); // 7天的时间戳
        $time = time() - $auto_confirm_date; // 比如7天以前的可用自动确认收货
        //$order_id_arr = M('order')->where("order_status = 1 and shipping_status = 1 and shipping_time < $time")->getField('order_id', true);
       /* foreach ($order_id_arr as $k => $v) {
            confirm_order($v);
        }*/

        // 多少天后自动分销记录自动分成
        $switch = tpCache('distribut.switch');
        if ($switch == 1 && file_exists(APP_PATH . 'common/logic/DistributLogic.php')) {
            $distributLogic = new \app\common\logic\DistributLogic();
            $distributLogic->auto_confirm(); // 自动确认分成
        }
    }

    public function ajax_get_action()
    {
        $control = I('controller');
        $type = I('type',1);
        $advContrl = get_class_methods("app\\admin\\controller\\" . str_replace('.php', '', $control));
        $baseContrl = get_class_methods('app\admin\controller\Base');
        //
        if($type > 1){
            $advContrl = get_class_methods("app\\seller\\controller\\" . str_replace('.php', '', $control));
            $baseContrl = get_class_methods('app\seller\controller\Base');
        }

        $diffArray = array_diff($advContrl, $baseContrl);
        $html = '';
        foreach ($diffArray as $val) {
            $html .= "<option value='" . $val . "'>" . $val . "</option>";
        }
        exit($html);
    }

    public function right_list()
    {
        $type = I('type/d', 1);
        $p = I('p', 1);//页码
        $name = I('name');
        $name = trim($name);
        $page = new Psp\Pagination();
        $page->setSortAsc(false); //正序
        $page->setSortBy("permission_id");
        $page->setIndex($p);
        $page->setLimit(40);
        if ($name) {
            $search = new Psp\User\SearchPower();
            $search->setPage($page);
            $search->setRightType($type);
            $search->setRightName($name);

            list($reply, $status) = GRPC('user')->SearchRight($search)->wait();
            foreach ($reply->getRightList() as $k=>$v) {
                $right_list[$k]['pri_id'] = $v->getPrivId();
                $pri_code = getRightCode($right_list[$k]['pri_id'], 1);
                $right_list[$k]['pri_code'] = $pri_code[$right_list[$k]['pri_id']];
                $right_list[$k]['pri_name'] = $v->getPrivName();
                $right_list[$k]['pri_comment'] = $v->getPrivComment();
                $right_list[$k]['group_id'] = $v->getGroupId();
                $right_list[$k]['type'] = $v->getPrivType();
                $right_list[$k]['is_group'] = $v->getIsGroup();
            }
        } else {
            $list = new Psp\User\PlatformType();
            $list->setRightType($type);
            $list->setPage($page);
            list($reply, $status) = GRPC('user')->GetPowerResource($list)->wait();

            foreach ($reply->getRightList() as $k=>$v) {
                $right_list[$k]['pri_id'] = $v->getPrivId();
                $pri_code = getRightCode($right_list[$k]['pri_id'], 1);
                $right_list[$k]['pri_code'] =$pri_code[$right_list[$k]['pri_id']];
                $right_list[$k]['pri_name'] = $v->getPrivName();
                $right_list[$k]['pri_comment'] = $v->getPrivComment();
                $right_list[$k]['group_id'] = $v->getGroupId();
                $right_list[$k]['type'] = $v->getPrivType();
                $right_list[$k]['is_group'] = $v->getIsGroup();
            }
        }
        //总条数
        $total_count = $reply->getPageResult()->getTotalRecords();
        //每页条数
        $limit_page =$reply->getPageResult()->getPageSize();
        $group = array('100' => '会员中心', '200' => '商品中心', '300' => '订单物流', '400' => '内容管理','450'=>'店铺管理', '500' => '营销推广', '600' => '插件工具', '700' => '系统设置', '800' => '统计报表', '900' => '财务管理');
        if ($type > 1) {
            //商家组
            $group = array('1000' => '商品管理','2000' => '订单物流','3000' => '店铺设置','4000' => '营销推广','5000' => '统计报表','6000'=>'售后服务','7000'=>'账号管理','8000'=>'财务管理','9000' => '客服消息',);
        }

        if($p == 1){
            adminOperateLog('权限资源列表',1);
        }

        $this->assign('right_list', $right_list);
        $this->assign('group', $group);
        $Page = new Page($total_count, $limit_page);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('pager', $Page);
        return $this->fetch();
    }

    //编辑权限
    public function edit_right()
    {
        if (IS_POST) {
            $data = I('post.');
            $priv = new Psp\User\RightInfo();
            $priv->setPrivId((int)$data['id']);
            $priv->setPrivName($data['name']);
            $priv->setPrivComment($data['desc']);
            $priv->setIsGroup((int)$data['is_group']);//是否为组权限
            $priv->setPrivType((int)$data['type']);//权限类型 1平台  2商家
            $priv->setGroupId((int)$data['group']);//组id
            $save = new Psp\User\UpdatePower();
            $save->setRightInfo($priv);
            list($res, $status) = GRPC('user')->UpdateRight($save)->wait();
            $ret = $res->getRet();
            $msg = $res->getMsg();
            if ($ret == 'ok') {
                $this->success("{$msg}", U('System/right_list'));
            } else {
                $this->error("{$msg}", U('System/right_list'));
                exit;
            }
        }
        $id = I('pri_id/d');
        if ($id) {
            //取出权限信息
            $r_id = new Psp\User\Rid();
            $r_id->setRid($id);
            //读取结果
            list($reply, $status) = GRPC('user')->GetRightInfo($r_id)->wait();
            $info['pri_id'] = $reply->getRightInfo()->getPrivId();
            $info['pri_name'] = $reply->getRightInfo()->getPrivName();
            $info['desc'] = $reply->getRightInfo()->getPrivComment();
            $info['group_id'] = $reply->getRightInfo()->getGroupId();
            $info['is_group'] = $reply->getRightInfo()->getIsGroup();
            $info['type'] = $reply->getRightInfo()->getPrivType();
            $code = getRightCode($info['pri_id'], $info['type']);
            $info['priv_code'] = $code[$info['pri_id']];
            $priv_code = explode('@', $info['priv_code']);
            $info['controller'] = $priv_code[0];
            $info['action'] = $priv_code[1];
            $this->assign('info', $info);
        }
        $type = I('type', 1);
        $group = array('100' => '会员中心', '200' => '商品中心', '300' => '订单物流', '400' => '内容管理','450'=>'店铺管理', '500' => '营销推广', '600' => '插件工具', '700' => '系统设置', '800' => '统计报表', '900' => '财务管理');
        $planPath = APP_PATH . 'admin/controller';
        if ($type > 1) {
            //商家组
            $group = array('1000' => '商品管理','2000' => '订单物流','3000' => '店铺设置','4000' => '营销推广','5000' => '统计报表','6000'=>'售后服务','7000'=>'账号管理','8000'=>'财务管理','9000' => '客服消息',);
            $planPath = APP_PATH . 'seller/controller';
        }
        $planList = array();
        $dirRes = opendir($planPath);
        while ($dir = readdir($dirRes)) {
            if (!in_array($dir, array('.', '..', '.svn'))) {
                $planList[] = basename($dir, '.php');
            }
        }
        adminOperateLog('编辑权限',1);
        $this->assign('planList', $planList);
        $this->assign('group', $group);
        return $this->fetch();
    }

    //添加权限
    public function add_right()
    {

        $type = I('get.type', 1);
        $group = array('100' => '会员中心', '200' => '商品中心', '300' => '订单物流', '400' => '内容管理', '450'=>'店铺管理','500' => '营销推广', '600' => '插件工具', '700' => '系统设置', '800' => '统计报表', '900' => '财务管理');
        $planPath = APP_PATH . 'admin/controller';
        if ($type > 1) {
            //商家组
            $group = array('1000' => '商品管理','2000' => '订单物流','3000' => '店铺设置','4000' => '营销推广','5000' => '统计报表','6000'=>'售后服务','7000'=>'账号管理','8000'=>'财务管理','9000' => '客服消息',);
            $planPath = APP_PATH . 'seller/controller';
        }
        $planList = array();
        $dirRes = opendir($planPath);
        while ($dir = readdir($dirRes)) {
            if (!in_array($dir, array('.', '..', '.svn'))) {
                $planList[] = basename($dir, '.php');
            }
        }
        if (IS_POST) {
            $data = I('post.');
            $right_info = getRightStr($data['right'], $data['type']);
            if ($right_info['status'] == -1) {
                $this->error("{$right_info['msg']}", U('System/add_right'));
                exit;
            }
            $data['right'] = $right_info['result'];
            $priv = new Psp\User\Privileges();
            $priv->setPrivInfo($data['right']);
            $priv->setPrivComment($data['desc']);
            $priv->setIsGroup((int)$data['is_group']);
            $priv->setPrivType((int)$data['type']);//权限类型 1平台  2商家
            $priv->setGroupId((int)$data['group']);//组id
            $add = new Psp\User\AddPower();
            $add->setRight($priv);
            list($reply, $status) = GRPC('user')->AddRight($add)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if ($ret == 'ok') {
                $this->success("{$msg}", U('System/right_list'));
            } else {
                $this->error("{$msg}", U('System/right_list'));
                exit;
            }
        }
        adminOperateLog('添加权限',1);
        $this->assign('type',$type);
        $this->assign('planList', $planList);
        $this->assign('group', $group);
        return $this->fetch();
    }


    public function right_del()
    {
        $id = I('del_id/d');
        if ($id) {
            $rid = new Psp\User\Rid();
            $rid->setRid($id);
            list($reply, $status) = GRPC('user')->DelRight($rid)->wait();
//            $ret = $reply->getret();
            adminOperateLog('删除权限',1);
            respose(1);
//            if ($ret == 'ok') {
//                respose(1);
//            } else {
//                respose('删除失败');
//            }
        } else {
            respose('参数有误');
        }
    }
}

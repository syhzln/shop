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

use app\admin\logic\UpgradeLogic;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
use Psp;
use Grpc;

class Base extends Controller
{

    /**
     * 析构函数
     */
    public function __construct()
    {
//        $this->error('页面错误...');
        Session::start();
        header("Cache-control: private");
        parent::__construct();
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin', $navigate_admin);
    }

    /*
     * 初始化操作
     */
    public function _initialize()
    {
//        //过滤不需要登陆的行为
        if (in_array(ACTION_NAME, array('login','logout','vertify')) || in_array(CONTROLLER_NAME, array('Ueditor','Uploadify'))) {
            //return;
        } else {
            $payload =validate_json_web_token(cookie('_authtoken'));//解码token
            $admin_id =$payload['admin_id'];
            if ($admin_id >0) {
                //检测token合法性
                if (!$this->checkLogin()) {
                    $this->error('登录超时', U('Admin/Admin/login'), 1);
                    exit;
                }
                //每次调用重新签发token
                $payload =array('admin_id'=>$admin_id,
                    'user_name'=>$payload['user_name'],
                    'org_id'=>$payload['org_id']
                );
                $jwt = create_json_web_token($payload);
                setrawcookie('_authtoken', $jwt, 0, '/', get_host(), false, true); //重新设置token
                $this->check_priv();//检查管理员菜单操作权限
            } else {
                $this->error('请先登陆', U('Admin/Admin/login'), 1);
            }
        }
        $this->public_assign();
    }

    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {
        /*$walhao_config = array();
        $tp_config = M('config')->cache(true)->select();
        foreach ($tp_config as $k => $v) {
            $walhao_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
        }
        
        $this->assign('walhao_config', $walhao_config);*/
    }

    public function check_priv()
    {
        $ctl = CONTROLLER_NAME;
        $act = ACTION_NAME;
        $act_list =base64_decode(cookie('act_list'));
//        var_dump($act_list);die;
        //无需验证的操作
        $uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
        if ($ctl == 'Index' || $act_list == 'all') {
            //后台首页控制器无需验证,超级管理员无需验证
            return true;
        } elseif (request()->isAjax() || strpos($act, 'ajax')!== false || in_array($act, $uneed_check)) {
            //所有ajax请求不需要验证权限
            return true;
        } else {
            $right = getRightCode($act_list,1);
//            $right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right', true);
            foreach ($right as $val) {
                $role_right .= $val.',';
            }
            $role_right = explode(',', $role_right);
            //检查是否拥有此操作权限
            if (!in_array($ctl.'@'.$act, $role_right)) {
                $this->error('您没有操作权限['.($ctl.'@'.$act).'],请联系超级管理员分配权限', U('Admin/Index/welcome'));
            }
        }
    }

    public function ajaxReturn($data, $type = 'json')
    {
        exit(json_encode($data));
    }

    /**
     * 检测是否登录
     **/
    protected function checkLogin()
    {
        //未设置cookie返回false
        if (!isset($_COOKIE['_authtoken'])) {
            return false;
        }
        $payload = validate_json_web_token(cookie('_authtoken'));
        // 如果$payload有效负载是假的，那么有问题
        if ($payload == false) {
            return false;
        }
        if (!is_array($payload)) {
            return false;
        }
        if (!array_key_exists('exp', $payload)) {
            return false;
        }
        if (!array_key_exists('iss', $payload)) {
            return false;
        }
        if ($payload['iss'] != get_host()) {
            return false;
        }
        // 超时
        if (date("m/d/Y H:i:s") > date('m/d/Y H:i:s', $payload['exp'])) {
            return false;
        }

        return true;
    }

}

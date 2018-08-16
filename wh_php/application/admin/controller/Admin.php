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

use think\Log;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use Psp;
use Grpc;
use think\AjaxPage;
use think\captcha\Captcha;

class Admin extends Base
{
    public function index()
    {
        $list = array();
        $p = I('p/d', 1);
        $keywords = trim(I('keywords/s'));
        $payload =validate_json_web_token(cookie('_authtoken'));
        $org_id =$payload['org_id']= 1;//取出org_id
        if (empty($keywords)) {
            $page = new Psp\Pagination();
            $page->setSortAsc(false); //正序
            $page->setSortBy("admin_id");
            $page->setIndex($p);
            $page->setLimit(20);
            $params =new Psp\User\OrgPage();
            $params->setOrgId($org_id);//组织id
            $params->setPageInfo($page);//分页
            //获取数据
            list($reply, $status) = GRPC('user')->GetAdminList($params)->wait();
            foreach ($reply->getAdminList() as $k=>$v) {
                $list[$k]['admin_id'] = $v->getUid();
                $list[$k]['user_name'] = $v->getName();
                $list[$k]['role_name'] = $v->getRolName();
                $list[$k]['phone'] = $v->getPhone();
                $list[$k]['email'] = $v->getEmail();
                $list[$k]['qq'] = $v->getQq();
                $list[$k]['dep_name'] = $v->getDepName();
                $list[$k]['status'] = $v->getStatus();
                $list[$k]['real_name'] = $v->getRealName();
                $list[$k]['add_time'] = $v->getCreateTime()->getSeconds();
            }
        } else {
            $page = new Psp\Pagination();
            $page->setSortAsc(false); //正序
            $page->setSortBy("admin_id");
            $page->setIndex(1);
            $page->setLimit(100);
            $keyWords = new Psp\User\AdminKeywords();
            $keyWords->setAdminName($keywords);
            $keyWords->setOrgId($org_id);
            $keyWords->setPageInfo($page);
            list($reply, $status) = GRPC('user')->SearchAdminRequest($keyWords)->wait();
            foreach ($reply->getAdminList() as $key=>$val) {
                $list[$key]['admin_id'] = $val->getUid();
                $list[$key]['user_name'] = $val->getName();
                $list[$key]['role_name'] = $val->getRolName();
                $list[$key]['phone'] = $val->getPhone();
                $list[$key]['email'] = $val->getEmail();
                $list[$key]['qq'] = $val->getQq();
                $list[$key]['dep_name'] = $val->getdepName();
                $list[$key]['status'] = $val->getStatus();
                $list[$key]['real_name']  = $val->getRealName();
                $list[$key]['add_time'] = $val->getCreateTime()->getSeconds();
            }
        }

        //总条数
        $total_count = $reply->getPageResult()->getTotalRecords();
        //每页条数
        $limit_page =$reply->getPageResult()->getPageSize();
        if($p == 1){
            adminOperateLog('管理员列表',1);
        }

        $this->assign('list', $list);
        $this->assign('admin_id',$payload['admin_id']);
        $Page = new Page($total_count, $limit_page);
        $show = $Page->show();
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 修改管理员密码 修改自己的
     * @return
     */
    public function modify_pwd()
    {
        $admin_id = I('admin_id/d', 0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');

        if ($admin_id) {
            //获取详情
            $aId =new Psp\User\AdminId();
            $aId->setAdminId($admin_id);
            list($reply, $status) = GRPC('user')->GetAdminInfo($aId)->wait();
            $info['admin_id'] = $reply->getUid();
            $info['password'] =  "";
            $this->assign('info', $info);
        }

        if (IS_POST) {
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $payload = validate_json_web_token(cookie('_authtoken'));
            $a_id = $payload['admin_id'];
            $pwd = new Psp\User\AdminPwd();
            $pwd->setAdminId((int)$a_id);
            $pwd->setOldPwd($enOldPwd);
            $pwd->setNewPwd($enNewPwd);
            list($reply, $status) = GRPC('user')->UpdateAdminPwd($pwd)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('修改登陆密码',1);

            if ($ret == 'ok') {
                exit(json_encode(array('status'=>1,'msg'=>"{$msg}")));
            } else {
                exit(json_encode(array('status'=>-1,'msg'=>"$msg")));
            }
        }
        return $this->fetch();
    }

    public function admin_info()
    {
        $admin_id = I('get.admin_id/d', 0);
        if ($admin_id) {
            $aId =new Psp\User\AdminId();
            $aId->setAdminId($admin_id);
            list($reply, $status) = GRPC('user')->GetAdminInfo($aId)->wait();
            $info['admin_id'] = $reply->getUid();
            $info['name'] = $reply->getName();
            $info['org_id'] = $reply->getOrgId();
            $info['role_id'] = $reply->getRolId();
            $info['role_name'] = $reply->getRolName();
            $info['phone'] = $reply->getPhone();
            $info['email'] = $reply->getEmail();
            $info['qq'] = $reply->getQq();
            $info['sex'] = $reply->getSex(); //1男 2女 0保密
            $info['address'] = $reply->getAddress();
            $info['age'] = $reply->getAge();
            $info['real_name'] = $reply->getRealName();
            $info['password'] = $reply->getPwd();
            $info['dep_id'] = $reply->getDepId();
            $info['dep_name'] = $reply->getDepName();
            $info['status'] = $reply->getStatus(); //管理员状态   1启用  0禁用
//            $info['create_time'] = $reply->getCreateTime()->getSeconds();
            $info['password'] =  "";
            $this->assign('info', $info);
        }
        $act = empty($admin_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        //取出组织id
        $payload = validate_json_web_token(cookie('_authtoken'));
        $o_id = $payload['org_id'] = 1; //取出org_id
        $org_id = new Psp\User\OrgId();
        $org_id->setOrgId($o_id);
        //获取所有角色
        list($reply, $status) = GRPC('user')->GetRoleList($org_id)->wait();
        foreach ($reply->getRoleList() as $key=>$val) {
            $role[$key]['rol_id'] = $val->getRolId();
            $role[$key]['rol_name'] = $val->getRoleName();
        }
        //获取所有部门
        list($reply, $status) = GRPC('user')->GetDepList($org_id)->wait();
        foreach ($reply->getDepList() as $k=>$v) {
            $depart[$k]['dep_id'] = $v->getDepId();
            $depart[$k]['dep_name'] = $v->getDepName();
        }

        adminOperateLog('管理员详情',1);

        $this->assign('role', $role);
        $this->assign('depart', $depart);
        return $this->fetch();
    }
    /*
     *管理员增删改
     * */
    public function adminHandle()
    {
        $data = I('post.');
        if (empty($data['password'])) {
            $data['password'] = '';
        } else {
            $data['password'] = encrypt($data['password']);
        }
        $payload =validate_json_web_token(cookie('_authtoken'));
        $org_id =$payload['org_id'] = 1;//取出org_id
        $r = 0;
        //添加
        if ($data['act'] == 'add') {
            unset($data['admin_id']);
            $time = new Psp\Timestamp();
            $time->setSeconds(time());
            $time->setNanos(1);
            $data['add_time'] =$time;//创建时间
            $params = new Psp\User\AddAdmini();
            $params->setName($data['user_name']);
            $params->setOrgId((int)$org_id);
            $params->setRolId((int)$data['role_id']);
            $params->setDepId((int)$data['dep_id']);
            $params->setPhone($data['phone']);
            $params->setEmail($data['email']);
            $params->setQq($data['qq']);
            $params->setSex((int)$data['sex']);
            $params->setAddress($data['address']);
            $params->setAge((int)$data['age']);
            $params->setRealName($data['real_name']);
            $params->setStatus((int)$data['status']);
            $params->setPwd($data['password']);
            $params->setCreateTime($data['add_time']);
            list($res, $status) = GRPC('user')->AddAdmin($params)->wait();
            $ret = $res->getRet();
            $msg = $res->getMsg();
            adminOperateLog('新增管理员',1);
            if ($ret == 'ok') {
                $r = 1;
            } else {
                $this->error("{$msg}", U('Admin/Admin/index'));
                exit;
            }
        }
        //修改
        if ($data['act'] == 'edit') {
            //发送参数
            $params = new Psp\User\UpdateAdmini();
            $params->setName($data['user_name']);
            $params->setUid((int)$data['admin_id']);
            $params->setOrgId((int)$org_id);
            $params->setRolId((int)$data['role_id']);
            $params->setDepId((int)$data['dep_id']);
            $params->setPhone($data['phone']);
            $params->setEmail($data['email']);
            $params->setQq($data['qq']);
            $params->setSex((int)$data['sex']);
            $params->setAddress($data['address']);
            $params->setAge((int)$data['age']);
            $params->setRealName($data['real_name']);
            $params->setStatus((int)$data['status']);
            $params->setPwd($data['password']);
            list($res, $status) = GRPC('user')->UpdateAdmin($params)->wait();
            $ret = $res->getRet();
            $msg =$res->getMsg();
            adminOperateLog('修改管理员',1);
            if ($ret == 'ok') {
                $r = 1;
            } else {
                $this->error("{$msg}", U('Admin/Admin/index'));
                exit;
            }
        }
        //删除
        if ($data['act'] == 'del' && $data['admin_id']>1) {
            $admin_id =I('post.admin_id/d');
            $aId = new Psp\User\AdminId();
            $aId->setAdminId($admin_id);
            list($res, $status) = GRPC('user')->DelAdmin($aId)->wait();
            adminOperateLog('删除管理员',1);
            exit(json_encode(1));
        }

        if ($r) {
            $this->success("操作成功", U('Admin/Admin/index'));
        } else {
            $this->error("操作失败", U('Admin/Admin/index'));
        }
    }

    /*
     * 管理员登陆
     */
    public function login()
    {
        if ($this->checkLogin()) {
            $this->error("您已登录", U('Admin/Index/index'));
        }

        if (IS_POST) {
            $verify = new Captcha();
            if (!$verify->check(I('post.vertify'), "admin_login")) {
                exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }
            $condition['user_name'] = I('post.username/s');
            $condition['password'] = I('post.password/s');
            if (!empty($condition['user_name']) && !empty($condition['password'])) {
                $condition['password'] = encrypt($condition['password']);
                // Loading GrpcService Start
                $time = new Psp\Timestamp();
                $time->setSeconds(time());
                $time->setNanos(1);
                $request = new Psp\User\LoginRequest();
                $request->setUsername($condition['user_name']);
                $request->setPassword($condition['password']);
                $request->setIp(request()->ip());
                $request->setLoginTime($time);
                $login_time =$time->getSeconds();//获取登录时间
                $ip = $request->getIp();
                list($reply, $status) = GRPC('user')->Login($request)->wait();
                //获取管理员状态
                $status = $reply->getStatus();
                if ($status == '1') {
                    //获取数据
                    $right_id = $reply->getRightId(); //权限
                    $role_id = $reply->getRoleId();//角色id
                    $admin_id = $reply->getAdminId(); //管理员ID
                    $org_id = $reply->getOrgId(); //组织ID
                    $user_name = $reply->getUserName(); //账户名
                    // Loading GrpcService end
                    $payload = array('admin_id'=>$admin_id,
                        'org_id'=>$org_id,
                        'user_name'=>$user_name,
                    );
                    $jwt = create_json_web_token($payload);
                    //设置access token
                    setrawcookie('_authtoken', $jwt, 0, '/', get_host(), false, true);
                    $arr =['1','2','3','15','84'];
                    if(in_array($admin_id,$arr)){
                        $right_id ='all'; //超级管理员
                    }
                    $act_list =base64_encode($right_id);//加密权限
                    setcookie('act_list',$act_list,0,'/',get_host());
                    setcookie('last_login_time',$login_time,0,'/',get_host());
                    setcookie('last_login_ip',$ip,0,'/',get_host());
                    $url = cookie('from_url') ? cookie('from_url') : U('Admin/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                } elseif ($status == '-1') {
                    exit(json_encode(array('status'=>-1,'msg'=>'账号不存在')));
                } elseif ($status == '-2') {
                    exit(json_encode(array('status'=>-2,'msg'=>'密码错误')));
                } else {
                    exit(json_encode(array('status'=>0,'msg'=>'账号已冻结')));
                }
            } else {
                exit(json_encode(array('status'=>-3,'msg'=>'请填写账号密码')));
            }
        }

        return $this->fetch();
    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        setcookie("_authtoken", null, time() - 3600, "/", get_host());
        setcookie("act_list", null, time() - 3600, "/", get_host());
        setcookie("last_login_time", null, time() - 3600, "/", get_host());
        setcookie("last_login_ip", null, time() - 3600, "/", get_host());
        /*cookie('_authtoken', null);
        cookie('act_list', null);
        cookie('last_login_time', null);
        cookie('last_login_ip', null);*/
        $this->success("退出成功", U('Admin/Admin/login'));
    }

    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 35,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => true,
            'reset' => false,
            'fontttf' => '4.ttf',
            'codeSet' => '0123456789'
        );
        $Verify = new Captcha($config);
        return $Verify->entry("admin_login");
    }

    public function role()
    {
        $payload =validate_json_web_token(cookie('_authtoken'));
        $org_id =$payload['org_id'] = 1;//取出org_id
        $o_id = new Psp\User\OrgId();
        $o_id->setOrgId((int)$org_id);
        list($reply, $status) = GRPC('user')->GetRoleList($o_id)->wait();
        foreach ($reply->getRoleList() as $k=>$v) {
            $list[$k]['role_id'] = $v->getRolId();
            $list[$k]['role_name'] =$v->getRoleName();
            $list[$k]['role_desc'] = $v->getRoleDesc();
            $list[$k]['create_time'] = $v->getCreateTime()->getSeconds();
        }
        adminOperateLog('角色列表',1);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**角色详情*/
    public function role_info()
    {
        $role_id = I('get.role_id/d');
        $type = I('type/d', 1);
        $detail = array();
        $allRight =array();
        //获取所有权限
        $platType = new Psp\User\Type();
        $platType->setRightType($type);
        list($reply, $status) = GRPC('user')->GetRightList($platType)->wait();
        foreach ($reply->getRightList() as $k=>$v) {
            $allRight[$k]['pri_id'] = $v->getPrivId();
            $allRight[$k]['pri_name'] = $v->getPrivName();
            $allRight[$k]['group_id'] = $v->getGroupId();
            $allRight[$k]['is_group'] = $v->getIsGroup();
        }
        if ($role_id) {
            //获取角色详情
            $rol_id = new Psp\User\RolId();
            $rol_id->setRolId($role_id);
            list($reply, $status) = GRPC('user')->GetRoleInfo($rol_id)->wait();
            $detail['role_id'] = $reply->getRolInfo()->getRolId(); //角色id
            $detail['role_name'] = $reply->getRolInfo()->getRoleName();//角色名称
            $detail['org_id'] = $reply->getRolInfo()->getOrgId();//组织id
            $detail['role_type'] = $reply->getRolInfo()->getRoleType();//角色类型
            $detail['role_desc'] = $reply->getRolInfo()->getRoleDesc();
            $detail['create_time'] = $reply->getRolInfo()->getCreateTime()->getSeconds();//创建时间
            $detail['act_list'] = $reply->getRightIds();
            $detail['act_list'] = explode(',', $detail['act_list']);
            $this->assign('detail', $detail);
        }
        foreach ($allRight as $key=>$val) {
            if (!empty($detail)) {
                $val['enable'] = in_array($val['pri_id'], $detail['act_list']);
            }
            $modules[$val['group_id']][] = $val;
        }
        $group = array('100' => '会员中心', '200' => '商品中心', '300' => '订单物流', '400' => '内容管理', '450' => '店铺管理','500' => '营销推广', '600' => '插件工具', '700' => '系统设置', '800' => '统计报表', '900' => '财务管理');
        if ($type > 1) {
            //商家组
            $group = array('1000' => '商品管理','2000' => '订单物流','3000' => '店铺设置','4000' => '售后服务','5000' => '营销推广','6000'=>'系统设置','7000'=>'统计报表','8000'=>'财务管理');
        }
        adminOperateLog('角色详情',1);
        $this->assign('group', $group);
        $this->assign('modules', $modules);
        return $this->fetch();
    }

    /*添加/修改角色*/
    public function roleSave()
    {
        $data = I('post.');
        $res = $data['data'];
        $res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if (empty($res['act_list'])) {
            $this->error("请选择权限!");
        }
        $payload = validate_json_web_token(cookie('_authtoken'));
        $org_id = $payload['org_id'] = 1;//取出org_id
        $role = new Psp\User\Roles();
        $role->setRoleName($res['role_name']);
        $role->setRoleType((int)$res['role_type']);
        $role->setRoleDesc($data['role_desc']);
        $role->setOrgId((int)$org_id);
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $role->setCreateTime($time);
        $r = 0;
        if (empty($data['role_id'])) {
            //添加
            $add = new Psp\User\AddRoler();
            $add->setRightIds($res['act_list']);
            $add->setRoleInfo($role);
            list($reply, $status) = GRPC('user')->AddRole($add)->wait();
            $r = 1;
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('新增角色',1);
            if ($ret == 'fail') {
                $this->error("{$msg}");
            }
        } else {
            //修改
            $role->setRolId((int)$data['role_id']);
            $save = new Psp\User\SaveRole();
            $save->setRoleInfo($role);
            $save->setRightIds($res['act_list']);
            list($reply, $status) = GRPC('user')->UpdateRole($save)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('编辑角色',1);
            $r =1;
            if ($ret == 'fail') {
                $this->error("{$msg}");
            }
        }
        if ($r) {
            //写入操作日志
           /* $log = new Psp\User\AdminLog();
            $log->setAdminId((int)$payload['admin_id']);
            $log->setLogInfo('管理角色');
            $log->setLogIp(request()->ip());
            $log->setOrgId((int)$org_id);
            $log->setName($payload['user_name']);
            $log->setLogType(2);
            $time = new Psp\Timestamp();
            $time->setSeconds(time());
            $time->setNanos(1);
            $log->setOperateTime($time); //操作时间
            list($reply, $status) = GRPC('user')->AddAdminLog($log)->wait();*/
            $this->success("操作成功!", U('Admin/Admin/role'));
        } else {
            $this->error("操作失败!", U('Admin/Admin/role'));
        }
    }

    /*删除角色*/
    public function roleDel()
    {
        $rol_id = I('post.role_id/d', 0);
        if ($rol_id) {
            $role_id = new Psp\User\RolId();
            $role_id->setRolId($rol_id);
            list($reply, $status) = GRPC('user')->DelRole($role_id)->wait();
            adminOperateLog('删除角色',1);
            exit(json_encode(1));//删除成功
        } else {
            exit(json_encode("参数错误!!!"));
        }
    }

    /*管理员日志*/
    public function log()
    {
        $p = I('p/d', 1);
        //取出org_id
        $payload =validate_json_web_token(cookie('_authtoken'));
        $org_id = $payload['org_id'] = 1;//取出org_id

        // Loading GrpcService Start
        //分页信息
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("log_id");
        $page->setIndex($p);
        $page->setLimit(30);
        //传入参数
        $params = new Psp\User\OrgPage();
        $params->setOrgId($org_id);
        $params->setPageInfo($page);
        list($reply, $status) = GRPC('user')->GetAdminLogList($params)->wait();
        foreach ($reply->getAdminLogList() as $k=>$v) {
            $logs[$k]['log_id'] = $v->getLogId();
            $logs[$k]['log_info'] = $v->getLogInfo();
            $logs[$k]['log_ip'] = $v->getLogIp();
            $logs[$k]['name'] = $v->getName();//账户名
            $logs[$k]['log_time'] = $v->getOperateTime()->getSeconds();
        }
        //总条数
        $total_count = $reply->getPageResult()->getTotalRecords();
        //每页条数
        $limit_page =$reply->getPageResult()->getPageSize();
        // Loading GrpcService end
//        //总页数
        $this->assign('list', $logs);
        $Page = new Page($total_count, $limit_page);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('pager', $Page);
        return $this->fetch();
    }

    /**部门列表*/
    public function department()
    {
        //获取组织id
        $payload = validate_json_web_token(cookie('_authtoken'));
        $o_id = $payload['org_id'] = 1;//取出org_id
        $o_id = $payload['org_id'] = 1;//取出org_id
        $org_id = new Psp\User\OrgId();
        $org_id->setOrgId((int)($o_id));
        list($reply, $status) = GRPC('user')->GetDepList($org_id)->wait();
        foreach ($reply->getDeplist() as $k=>$v) {
            $list[$k]['dep_id'] = $v->getDepId();
            $list[$k]['org_id'] = $v->getOrgId();
            $list[$k]['dep_level'] = $v->getDepLevel();
            $list[$k]['dep_name'] = $v->getDepName();
            $list[$k]['parent_id'] = $v->getParentDepId();
            $list[$k]['dep_desc'] = $v->getDepDesc();
        }
        adminOperateLog('查看部门列表',1);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**部门详情*/
    public function depart_info()
    {
        $dep_id =I('get.dep_id/d', 0);
        if ($dep_id) {
            $d_id = new Psp\User\DepId();
            $d_id->setDepId($dep_id);
            list($reply, $status) = GRPC('user')->GetDepInfo($d_id)->wait();
            //读取结果集
            $info['dep_id'] = $reply->getDepInfo()->getDepId();
            $info['org_id'] = $reply->getDepInfo()->getOrgId();
            $info['dep_name'] = $reply->getDepInfo()->getDepName();
            $info['dep_level'] = $reply->getDepInfo()->getDepLevel();
            $info['dep_desc'] = $reply->getDepInfo()->getDepDesc();
            $info['parent_dep_id'] = $reply->getDepInfo()->getParentDepId();
        }
        adminOperateLog('部门详情',1);
        $act = empty($dep_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        $this->assign('info', $info);
        return $this->fetch();
    }

    /**部门增删改*/
    public function departHandle()
    {
        $data = I('post.');
        //获取组织id
        $payload = validate_json_web_token(cookie('_authtoken'));
        $o_id = $payload['org_id'] = 1;//取出org_id
        //添加
        if ($data['act'] == 'add') {
            $data['dep_id'] = '';
//            unset($data['dep_id']);
            $params = new Psp\User\Departments();
            $params->setDepId((int)$data['dep_id']);
            $params->setDepName(trim($data['dep_name']));
            $params->setOrgId((int)$o_id);
            $params->setParentdepId(1);//这里默认为1
            $params->setDepLevel((int)$data['dep_level']);
            $params->setDepDesc($data['dep_desc']);
            $add = new Psp\User\AddDepart();
            $add->setDepInfo($params);
            list($reply, $status) = GRPC('user')->AddDep($add)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('新增部门',1);
            if ($ret == 'ok') {
                $this->success("{$msg}", U('Admin/Admin/department'));
            } else {
                $this->error("{$msg}", U('Admin/Admin/department'));
                exit;
            }
        }
        //修改
        if ($data['act'] == 'edit') {
            //设置值
            $params = new Psp\User\Departments();
            $params->setDepId((int)$data['dep_id']);
            $params->setDepName($data['dep_name']);
            $params->setOrgId((int)$o_id);
            $params->setParentdepId(1);//这里默认为1
            $params->setDepLevel((int)$data['dep_level']);
            $params->setDepDesc($data['dep_desc']);
            $edit = new Psp\User\UpdateDepart();
            $edit->setDepInfo($params);
            list($reply, $status) = GRPC('user')->UpdateDep($edit)->wait();
            adminOperateLog('修改部门',1);
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if ($ret == 'ok') {
                $this->success("{$msg}", U('Admin/Admin/department'));
            } else {
                $this->error("{$msg}", U('Admin/Admin/department'));
                exit;
            }
        }
        //删除
        if ($data['act'] =='del' && $data['dep_id'] > 1) {
            $dep_id = I('post.dep_id/d');
            $d_id = new Psp\User\DepId();
            $d_id->setDepId($dep_id);
            list($reply, $status) = GRPC('user')->DelDep($d_id)->wait();
            adminOperateLog('删除部门',1);
            exit(json_encode(1));
        }
    }

    /**组织列表*/
    public function organization()
    {
        /*组织列表默认传1 获取所有组织*/
        $org_id = new Psp\User\OrgId();
        $org_id->setOrgId(1);
        list($reply, $status) = GRPC('user')->GetOrgList($org_id)->wait();
        foreach ($reply->getOrgList() as $key=>$val) {
            $list[$key]['org_id'] = $val->getOrgId();
            $list[$key]['org_name'] = $val->getOrgName();
            $list[$key]['org_info'] = $val->getOrgInfo();
            $list[$key]['org_level'] = $val->getOrgLevel();
            $list[$key]['org_type'] = $val->getOrgType();
            $list[$key]['add_time'] = $val->getCreateTime()->getSeconds();
        }
        adminOperateLog('组织列表',1);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**组织详情*/
    public function organize_info()
    {
        $org_id = I('get.org_id/d', 0);
        if ($org_id) {
            $o_id = new Psp\User\OrgId();
            $o_id->setOrgId($org_id);
            list($reply, $status) = GRPC('user')->GetOrginfo($o_id)->wait();
            $info['org_id'] = $reply->getOrgInfo()->getOrgId();
            $info['org_name'] = $reply->getOrgInfo()->getOrgName();
            $info['org_desc'] = $reply->getOrgInfo()->getOrgInfo();
            $info['org_level'] = $reply->getOrgInfo()->getOrgLevel();
            $info['org_type'] = $reply->getOrgInfo()->getOrgType();
            $info['add_time'] = $reply->getOrgInfo()->getCreateTime()->getSeconds();
        }
        adminOperateLog('组织详情',1);
        $act = empty($org_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        $this->assign('info', $info);
        return $this->fetch();
    }

    /**组织增删改*/
    public function organizeHandle()
    {
        $data = I('post.', 0);
        //增
        if ($data['act'] == 'add') {
            $org_id = 0;//默认传 0
            $time =new Psp\Timestamp();
            $time->setSeconds(time());
            $time->setNanos(1);
            $params = new Psp\User\Organizations();
            $params->setOrgId($org_id);
            $params->setOrgName($data['org_name']);
            $params->setOrgLevel((int)$data['org_level']);
            $params->setOrgType((int)$data['org_type']);
            $params->setOrgInfo($data['org_desc']);
            $params->setCreateTime($time);
            $add = new Psp\User\AddOrgn();
            $add->setOrgInfo($params);
            list($reply, $status) = GRPC('user')->AddOrg($add)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('新增组织',1);
            if ($ret == 'ok') {
                $this->success("{$msg}", U('Admin/Admin/organization'));
            } else {
                $this->error("{$msg}", U('Admin/Admin/organize_info'));
                exit;
            }
        }
        //改
        if ($data['act'] == 'edit') {
            $time =new Psp\Timestamp();
            $time->setSeconds(time());//java还取原时间 不传报错
            $time->setNanos(1);
            $params = new Psp\User\Organizations();
            $params->setOrgId((int)$data['org_id']);
            $params->setOrgName($data['org_name']);
            $params->setOrgLevel((int)$data['org_level']);
            $params->setOrgType((int)$data['org_type']);
            $params->setOrgInfo($data['org_desc']);
            $params->setCreateTime($time);
            $add = new Psp\User\UpdateOrgn();
            $add->setOrgInfo($params);
            list($reply, $status) = GRPC('user')->UpdateOrg($add)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            adminOperateLog('修改组织',1);
            if ($ret == 'ok') {
                $this->success("{$msg}", U('Admin/Admin/organization'));
            } else {
                $this->error("{$msg}", U('Admin/Admin/organization'));
                exit;
            }
        }
        //删
        if ($data['act'] == 'del' && $data['org_id'] > 1) {
            $org_id = I('post.org_id/d');
            $oid = new Psp\User\OrgId();
            $oid->setOrgId($org_id);
            list($reply, $status) = GRPC('user')->DelOrd($oid)->wait();
            adminOperateLog('删除组织',1);
            exit(json_encode(1));
        }
    }

    //添加/修改 管理员绑定商家
    public function bindProviderList(){
        $admin_id = I('get.admin_id/d', 0);
        //获取管理员详情
        $admin_info = new Psp\User\AdminId();
        $admin_info->setAdminId($admin_id);
        list($resp,$status) = GRPC('user')->GetAdminInfo($admin_info)->wait();
        $admin_name = $resp->getName();//账户名

        $shop_name=I('post.shop_name',null);
        $mobile =I('post.mobile',null);//店铺联系人电话
        $account_name =I('post.account_name',null);//登录账号
        $is_closed=I('post.is_closed',0);
        $p=I('get.p/d',1);
        $co=15;
        //搜索
        $search=new Psp\Store\StoreSearch();
        $search->setShopName($shop_name);
        $search->setIsClosed($is_closed);
        $search->setMobile($mobile);
        $search->setLoginAccount($account_name);
        //分页
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy('apply_id');
        $page->setIndex($p);  //页码  每页条数
        $page->setLimit($co);
        $storeInfo = new Psp\Store\ApplyId();
        $storeInfo->setApplyId(0);
        $storeInfo->setPagination($page);
        $storeInfo->setStoresearch($search);
        //        $storeInfo->setApplyState(2);
        list($result,$status) = GRPC('sellerstore') ->GetStoreInfoLists($storeInfo)->wait();
        foreach ($result->getStoreInfo() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['shop_name'] = $v->getShopName();
            $arr[$k]['open_time'] = $v->getOpenTime()->getSeconds();
            $arr[$k]['is_closed'] = $v->getIsClosed();
            $arr[$k]['login_username'] = $v->getLoginUsername();

            $arr[$k]['prov_name'] = $v->getProvName();
        }
        $count=$result->getPaginationResult()->getTotalRecords();
        if($p == 1){
            adminOperateLog('绑定商家列表',1);
        }
        $Page = new Page($count, $co);
        $show = $Page->show();
        $this->assign('storeInfo',$arr);
        $this->assign('page',$show);
        $this->assign('admin_id',$admin_id);
        $this->assign('user_name',$admin_name);
        return $this->fetch();
    }

    /*查看已绑定商家*/
    public function viewBindProvider(){
        $admin_id = I('get.admin_id/d');
        $admin = new Psp\User\AId();
        $admin->setAdminId($admin_id);
        $admin->setOrgId(1);
        list($resp,$status) = GRPC('user')->GetAdminBindSeller($admin)->wait();

        if(!empty($resp)){
            $data['admin_id'] = $resp->getAdminId();
            $data['user_name'] = $resp->getUserName();
            foreach ($resp->getAdminProviderList() as $k=>$v){
                $arr[$k]['provider_id'] = $v->getProviderId();
                $arr[$k]['provider_name'] = $v->getProviderName();
            }

        }
        adminOperateLog('查看绑定商家',1);
        $this->assign('data',$data);
        $this->assign('store',$arr);
        return $this->fetch();
    }

    //绑定/修改 商家
    public function addBindProvider(){
        if(IS_PSOT){
            $act = I('post.act');
            $admin_id = I('post.admin_id/d');

            if($act == 'edit'){
                //修改
                $store_id =$_POST['store_id'];
                if(!empty($store_id)){
                    $store_ids = implode(',',$store_id);
                }else{
                    $store_ids = '';
                }
                $edit = new Psp\User\BindOrderRights();
                $edit->setAdminId($admin_id);
                $edit->setBindIds($store_ids);

                list($resp,$stauts) = GRPC('user')->UpdateAdminBindSeller($edit)->wait();
                $ret = $resp->getRet();
                $msg = $resp->getMsg();
                adminOperateLog('修改绑定商家',1);
                if($ret == 'ok'){
                    $this->success('修改成功',U('admin/bindProviderList',array('admin_id'=>$admin_id)));
                }else{
                    $this->error("{$msg}");
                }
                exit;

            }else{
                //添加
                $apply_id = I('post.apply_id/d');
                $bind = new Psp\User\BindOrderRight();
                $bind->setAdminId($admin_id);
                $bind->setBindId($apply_id);
                list($reply,$status) = GRPC('user')->AddAdminBindSeller($bind)->wait();
                $ret = $reply->getRet();
                $msg = $reply->getMsg();
                adminOperateLog('添加绑定商家',1);
                if($ret == 'ok'){
                    exit(json_encode(['status'=>1,'msg'=>'绑定成功']));
                }else{
                    exit(json_encode(['status'=>-1,'msg'=>$msg]));
                }
            }

        }
    }


}

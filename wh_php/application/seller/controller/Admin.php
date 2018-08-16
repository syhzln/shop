<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-09
 */

namespace app\seller\controller;

use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use Grpc;
use Psp;
use think\captcha\Captcha;

class Admin extends Base
{
    public function index()
    {
        $list = array();
        $store_id = new Psp\Seller\ProvOrgId();
        $store_id->setProOrgId(STORE_ID);
        //获取数据
        list($reply, $status) = GRPC('seller')->GetSellerList($store_id)->wait();
        if($reply){
            foreach ($reply->getManageInfo() as $k=>$v) {
            $list[$k]['seller_id'] = $v->getProUser()->getUserId();
            $list[$k]['real_name'] = $v->getProUser()->getName();//真实姓名
            $list[$k]['user_name'] = $v->getProUser()->getLoginUsername(); //账户名
            $list[$k]['is_enabled'] = $v->getProUser()->getIsenabled();//是否启用
            $list[$k]['email'] = $v->getProUser()->getEmail();
            $list[$k]['qq'] = $v->getProUser()->getQq();
            $list[$k]['is_manager'] = $v->getProUser()->getIsManager();
            $list[$k]['role_name'] = $v->getProUser()->getRoleName();
            $list[$k]['phone'] = $v->getProUser()->getPhone();
            $list[$k]['add_time'] = $v->getProUser()->getRegisterDate()->getSeconds();
            }
        }
//        var_dump($list);die;
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd()
    {
        $admin_id = I('admin_id/d', 0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');

        if ($admin_id) {
            //获取详情
            $aId =new Psp\Seller\AccountId();
            $aId->setAccountId($admin_id);
            list($reply, $status) = GRPC('seller')->GetSellerAccount($aId)->wait();
            $info['seller_id'] = $reply->getProUser()->getUserId();
            $info['password'] =  "";
            $this->assign('info', $info);
        }

        if (IS_POST) {
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $payload = validate_json_web_token(cookie('accesstoken'));
            $a_id = $payload['seller_id'];
            $pwd = new Psp\Seller\SaveOwnPwd();
            $pwd->setUid((int)$a_id);
            $pwd->setOldPwd($enOldPwd);
            $pwd->setNewPwd($enNewPwd);
            list($reply, $status) = GRPC('seller')->UpdateSellerPwd($pwd)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
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
        $seller_id = I('get.seller_id/d', 0);
        if ($seller_id) {
            $s_id = new Psp\Seller\AccountId();
            $s_id->setAccountId($seller_id);
            list($reply, $status) =GRPC('seller')->GetSellerAccount($s_id)->wait();
            $info['seller_id'] = $reply->getProUser()->getUserId();
            $info['real_name'] = $reply->getProUser()->getName();//真实姓名
            $info['user_name'] = $reply->getProUser()->getLoginUsername(); //账户名
            $info['is_enabled'] = $reply->getProUser()->getIsenabled();//是否启用
            $info['email'] = $reply->getProUser()->getEmail();
            $info['qq'] = $reply->getProUser()->getQq();
            $info['is_manager'] = $reply->getProUser()->getIsManager();
            $info['role_name'] = $reply->getProUser()->getRoleName();
            $info['role_id'] = $reply->getProUser()->getRoleId();
            $info['nick_name'] = $reply->getProUser()->getNickname();
            $info['phone'] = $reply->getProUser()->getPhone();
            $info['password'] =  "";
            $this->assign('info', $info);
        }
        $act = empty($seller_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        //取出商家角色
        $store_id = new Psp\Seller\ProvOrgId();
        $store_id->setProOrgId(STORE_ID);
        $store_id->setRoleType(2);
        list($reply, $status) = GRPC('seller')->GetRoleList($store_id)->wait();
        foreach ($reply->getRoleList() as $key=>$val) {
            $role[$key]['role_id'] = $val->getRolId();
            $role[$key]['role_name'] = $val->getRoleName();
        }
        $this->assign('role', $role);
        return $this->fetch();
    }

    public function adminHandle()
    {
        $data = I('post.');
//        var_dump($data);die;
        if (empty($data['password'])) {
            $data['password'] = '';
        } else {
            $data['password'] = encrypt($data['password']);
        }
        $r = 0;
        if ($data['act'] == 'add') {
//    	    var_dump($data);die;
            if (empty($data['password'])) {
                $this->error('密码不能为空!');
                eixt;
            }
            $add = new Psp\Seller\ManageInfo();
            $add_info = new Psp\Seller\InsertManage();
//            $add_info->setUserId((int)$data['user_id']);
            $add_info->setProviderOrgId(STORE_ID);
            $add_info->setName($data['real_name']);
            $add_info->setLoginUsername($data['user_name']); //一旦创建 不能修改
            $add_info->setIsEnabled((int)$data['is_enabled']);
            $add_info->setEmail($data['email']);
            $add_info->setRoleId((int)$data['role_id']);
            $add_info->setIsManager((int)$data['is_manager']);
            $add_info->setPhone($data['phone']);
            $add_info->setQq($data['qq']);
            $add_info->setLoginSecret($data['password']);
            $add_info->setNickname($data['nick_name']);
            list($reply, $status) = GRPC('seller')->AddSeller($add_info)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if ($ret == 'ok') {
                $r = 1;
            } else {
                $this->error("{$msg}", U('Seller/Admin/index'));
                exit;
            }
        }

        if ($data['act'] == 'edit') {
//    	    var_dump($data);die;
            $edit_info = new Psp\Seller\InsertManage();
            $edit_info->setUserId((int)$data['seller_id']);
            $edit_info->setProviderOrgId(STORE_ID);
            $edit_info->setName($data['real_name']);
            $edit_info->setLoginUsername($data['user_name']); //一旦创建 不能修改
            $edit_info->setIsEnabled((int)$data['is_enabled']);
            $edit_info->setEmail($data['email']);
            $edit_info->setRoleId((int)$data['role_id']);
            $edit_info->setIsManager((int)$data['is_manager']);
            $edit_info->setPhone($data['phone']);
            $edit_info->setQq($data['qq']);
            $edit_info->setLoginSecret($data['password']);
            $edit_info->setNickname($data['nick_name']);
            list($reply, $status) = GRPC('seller')->UpdateManageAccount($edit_info)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if ($ret == 'ok') {
                $r = 1;
            } else {
                $this->error("{$msg}", U('Seller/Admin/index'));
                exit;
            }
        }
        if ($data['act'] == 'del' && $data['is_manager'] == 0) {
            $id =I('post.seller_id/d');
//    	    var_dump($id);die;
            $seller_id = new Psp\Seller\AccountId();
            $seller_id->setAccountId($id);
            list($reply, $status) = GRPC('seller')->DelSeller($seller_id)->wait();
            exit(json_encode(1));
        }

        if ($r) {
            $this->success("操作成功", U('Seller/Admin/index'));
        } else {
            $this->error("操作失败", U('Seller/Admin/index'));
        }
    }


    /*
     * 商家后台登陆
     */
    public function login()
    {
        if ($this->checkLogin()) {
            $this->error("您已登录", U('Seller/Index/index'));
        }

        if (IS_POST) {
            $verify = new Captcha();
            if (!$verify->check(I('post.vertify'), "seller_login")) {
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
                $request = new Psp\Seller\LoginRequest();
                $request->setSellerName($condition['user_name']);
                $request->setPassword($condition['password']);
                $request->setIp(request()->ip());
                $request->setLoginTime($time);
                $login_time =$time->getSeconds();//获取登录时间
                $ip = $request->getIp();
                list($reply, $status) = GRPC('seller')->Login($request)->wait();
                //获取管理员状态
                $status = $reply->getStatus();
                if ($status == '1') {
                    $is_verify = $reply->getIsVerify();
                    if($is_verify == 1){
                        exit(json_encode(array('status'=>-2,'msg'=>'请先验证手机号','url'=>U('Seller/Admin/verify_login'))));
                    }
                    //获取数据
                    $right_id = $reply->getRightId(); //权限
                    $role_id = $reply->getRoleId();//角色id
                    $seller_id = $reply->getSellerId(); //管理员ID
                    $store_id = $reply->getStoreId(); //店铺ID
                    $seller_name = $reply->getUserName(); //账户名
                    $is_manager = $reply->getIsManager();//是否为管理员
                    // Loading GrpcService end
                    $payload = array('seller_id'=>$seller_id,
                        'store_id'=>$store_id,
                        'seller_name'=>$seller_name,
                    );
                    $jwt = create_json_web_token($payload);
                    //设置access token
                    setrawcookie('accesstoken', $jwt, 0, '/', get_host(), false, true);
                    if ($is_manager =='1') {
                        $right_id ='all';
                    }
                    $right_list =base64_encode($right_id);//加密权限
                    setcookie('right_list',$right_list,0,'/',get_host());
                    setcookie('prov_login_time',$login_time,0,'/',get_host());
                    setcookie('prov_login_ip',$ip,0,'/',get_host());
                    $url = cookie('from_url') ? cookie('from_url') : U('Seller/Index/index');

                    exit(json_encode(array('status'=>1,'url'=>$url)));//登录成功
                } elseif ($status =='2') {
                    exit(json_encode(array('status'=>2,'msg'=>'账号不存在!')));
                } elseif ($status == '3') {
                    exit(json_encode(array('status'=>3,'msg'=>'密码错误!')));
                } else {
                    // 0
                    exit(json_encode(array('status'=>0,'msg'=>'账号已禁用,请联系管理员!')));
                }
            } else {
                exit(json_encode(array('status'=>-1,'msg'=>'请填写账号密码!')));
            }
        }

        return $this->fetch();
    }

    //验证码登录
    public function verify_login(){
        if(IS_POST){
            $user_name = I('post.user_name');
            $mobile = I('post.mobile');
            $sellerPhone = new Psp\Seller\SellerPhone();
            $sellerPhone->setUserName($user_name);
            $sellerPhone->setSellerPhone($mobile);
            list($resp,$status) = GRPC('seller')->UpdateSellerLoginTime($sellerPhone)->wait();//更新登录时间
            if($resp->getValue()){
                $this->success("验证成功,正在跳转登录页", U('Seller/Admin/login'));
                exit;
            }else{
                $this->error('系统繁忙,请重试');
                exit;
            }

        }
        return $this->fetch();
    }


    /**
     * 退出登陆
     */
    public function logout()
    {
        setcookie("accesstoken", null, time() - 3600, "/", get_host());
        setcookie("right_list", null, time() - 3600, "/", get_host());
        setcookie("prov_login_time", null, time() - 3600, "/", get_host());
        setcookie("prov_login_ip", null, time() - 3600, "/", get_host());
        /*cookie('accesstoken', null);
        cookie('right_list', null);
        cookie('seller_type', null);
        cookie('last_login_time', null);
        cookie('last_login_ip', null);*/
        $this->success("退出成功", U('Seller/Admin/login'));
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
        return $Verify->entry("seller_login");
//        exit();
    }

    public function role()
    {
        $store_id = new Psp\Seller\ProvOrgId();
        $store_id->setProOrgId(STORE_ID);
        $store_id->setRoleType(2);//商家 2  平台 1
        list($reply, $status) = GRPC('seller')->GetRoleList($store_id)->wait();
        foreach ($reply->getRoleList() as $k=>$v) {
            $list[$k]['role_id'] = $v->getRolId();
            $list[$k]['role_name'] =$v->getRoleName();
            $list[$k]['role_desc'] = $v->getRoleDesc();
            $list[$k]['create_time'] = $v->getCreateTime()->getSeconds();
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
    /*商家角色详情*/
    public function role_info()
    {
        $role_id = I('get.role_id/d');
        $type = I('type/d', 2); // 2商家  1平台
        $detail = array();
        $allRight =array();
        //获取所有权限
        $platType = new Psp\Seller\PlatformType();
        $platType->setRightType($type);
        list($reply, $status) = GRPC('seller')->GetRightList($platType)->wait();
        foreach ($reply->getRightList() as $k=>$v) {
            $allRight[$k]['pri_id'] = $v->getPrivId();
            $allRight[$k]['pri_name'] = $v->getPrivName();
            $allRight[$k]['group_id'] = $v->getGroupId();
            $allRight[$k]['is_group'] = $v->getIsGroup();
        }
        if ($role_id) {
//            var_dump($role_id);die;
            //获取角色详情
            $rol_id = new Psp\Seller\RolId();
            $rol_id->setRolId($role_id);
            list($reply, $status) = GRPC('seller')->GetRoleInfo($rol_id)->wait();
            $detail['role_id'] = $reply->getRolInfo()->getRolId(); //角色id
            $detail['role_name'] = $reply->getRolInfo()->getRoleName();//角色名称
            $detail['org_id'] = $reply->getRolInfo()->getOrgId();//组织id
            $detail['role_type'] = $reply->getRolInfo()->getRoleType();//角色类型
            $detail['role_desc'] = $reply->getRolInfo()->getRoleDesc();//角色描述
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
        $group = array('100' => '会员中心', '200' => '商品中心', '300' => '订单物流', '400' => '内容管理', '500' => '营销推广', '600' => '插件工具', '700' => '系统设置', '800' => '统计报表', '900' => '财务管理');
        if ($type > 1) {
            //商家组
            $group = array('1000' => '商品管理','2000' => '订单物流','3000' => '店铺设置','4000' => '售后服务','5000' => '营销推广','6000'=>'系统设置','7000'=>'统计报表','8000'=>'财务管理',);
        }

        $this->assign('group', $group);
        $this->assign('modules', $modules);
        return $this->fetch();
    }

    /*添加/修改角色*/
    public function roleSave()
    {
        $data = I('post.');
//        $res = $data['data'];
        $res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if (empty($res['act_list'])) {
            $this->error("请选择权限!");
        }
        $payload = validate_json_web_token(cookie('accesstoken'));
        $role = new Psp\Seller\Roles();
        $role->setRoleName($data['role_name']);
        $role->setRoleType(2);//商家角色
        $role->setRoleDesc($data['role_desc']);
        $role->setOrgId(STORE_ID);
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $role->setCreateTime($time);
        $r = 0;
        if (empty($data['role_id'])) {
            //添加
            $add = new Psp\Seller\InsertRole();
            $add->setRightIds($res['act_list']);
            $add->setRoleInfo($role);
            list($reply, $status) = GRPC('seller')->AddRole($add)->wait();
            $r = 1;
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if ($ret == 'fail') {
                $this->error("{$msg}");
            }
        } else {
            //修改
            $role->setRolId((int)$data['role_id']);
            $save = new Psp\Seller\SaveRole();
            $save->setRoleInfo($role);
            $save->setRightIds($res['act_list']);
            list($reply, $status) = GRPC('seller')->UpdateRole($save)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            $r =1;
            if ($ret == 'fail') {
                $this->error("{$msg}");
            }
        }
        if ($r) {
            //写入操作日志
            $log = new Psp\Seller\SellerLog();
            $log->setSellerId((int)$payload['seller_id']);
            $log->setLogInfo('管理角色');
            $log->setLogIp(request()->ip());
            $log->setPalformId(STORE_ID);
            $log->setSellerName($payload['seller_name']);
//            $log->setLogType(2);
//            $log->getAccountType()
            $time = new Psp\Timestamp();
            $time->setSeconds(time());
            $time->setNanos(1);
            $log->setOperateTime($time); //操作时间
            list($reply, $status) = GRPC('seller')->AddSellerLoginLog($log)->wait();
            $this->success("操作成功!", U('Seller/Admin/role'));
        } else {
            $this->error("操作失败!", U('SellerAdmin/role'));
        }
    }

    public function roleDel()
    {
        $rol_id = I('post.role_id/d', 0);
        if ($rol_id) {
            $role_id = new Psp\Seller\RolId();
            $role_id->setRolId($rol_id);
            list($reply, $status) = GRPC('seller')->DelRole($role_id)->wait();
            exit(json_encode(1));//删除成功
        } else {
            exit(json_encode("参数错误!!!"));
        }
    }

    /*商家日志*/
    public function log()
    {
        $p = I('p/d', 1);
        //取出org_id
        $payload =validate_json_web_token(cookie('accesstoken'));
        // Loading GrpcService Start
        //分页信息
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("log_time");
        $page->setIndex($p);
        $page->setLimit(30);
        //传入参数
        $params = new Psp\Seller\OrgPage();
        $params->setOrgId(STORE_ID);
        $params->setPageInfo($page);
        list($reply, $status) = GRPC('seller')->GetSellerLogginLog($params)->wait();
        foreach ($reply->getSellerLogList() as $k=>$v) {
            $logs[$k]['log_id'] = $v->getLogId();
            $logs[$k]['log_info'] = $v->getLogInfo();
            $logs[$k]['log_ip'] = $v->getLogIp();
            $logs[$k]['user_name'] = $v->getSellerName();//账户名
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
        return $this->fetch();
    }

    //客服详情
    public function set_service(){
        //获取详情
        $provider = new Psp\Seller\ProvOrgId();
        $provider->setRoleType(2);
        $provider->setProOrgId(STORE_ID);
        list($resp,$status) = GRPC('seller')->GetSellerServiceInfo($provider)->wait();
        $data['store_name'] = $resp->getStoreName();
        $data['service_phone'] = $resp->getServicePhone();
        $data['service_qq'] = $resp->getServiceQq();
        if(IS_POST){
            $data = I('post.');
            $provider_info = new Psp\Seller\ProviderContactInfo();
            $provider_info->setServicePhone($data['service_phone']);
            $provider_info->setServiceQq($data['service_qq']);
            $provider_info->setStoreId(STORE_ID);
            list($resp,$status) = GRPC('seller')->UpdateSellerServiceInfo($provider_info)->wait();
            $ret = $resp->getRet();
            $msg = $resp->getMsg();
            if($ret == 'ok'){
                exit(json_encode(['status'=>1,'msg'=>'修改成功']));
            }{
                exit(json_encode(['status'=>-1,'msg'=>"{$msg}"]));
            }
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    //系统消息
    public function system_message(){
        $p = I('p',1);
        $is_read = I('is_read',-1); //默认传 -1 表示取所有
        $limit = 16;
        $pageInfo = new Psp\Pagination();
        $pageInfo->setLimit($limit);
        $pageInfo->setSortAsc(false);
        $pageInfo->setIndex($p);
        $pageInfo->setSortBy('id');
        $message = new Psp\Seller\OrgPageSearch();
        $message->setOrgId(STORE_ID);
        $message->setIsRead($is_read);
        $message->setPageInfo($pageInfo);

        list($resp,$status) = GRPC('seller')->GetSellerMessageList($message)->wait();

        if(!empty($resp)){
            foreach ($resp->getMessageList() as $k=>$v){
                $data[$k]['msg_content'] = $v->getMessageContent();
                $data[$k]['add_time'] = $v->getAddTime();
                $data[$k]['msg_id'] = $v->getMsgId();
                $data[$k]['is_read'] = $v->getIsRead();
            }
            //总条数
            $total_num = $resp->getPageResult()->getTotalRecords();
            $Page = new Page($total_num,$limit);
            $show = $Page->show();

        }
        $this->assign('total_num',$total_num);
        $this->assign('page',$show);
        $this->assign('list',$data);
        return $this->fetch();
    }

    //设置消息已读
    public function set_read(){
        $is_read = I('post.msg_id/d');
        if($is_read){
            $read = new Psp\Seller\SetRead();
            $read->setIsRead(1);
            $read->setMsgId($is_read);
            $read->setProviderId(STORE_ID);
            list($resp,$status) = GRPC('seller')->SetSellerMessageRead($read)->wait();

            if($resp->getValue()){
                exit(json_encode(['status'=>1,'msg'=>'设置成功']));
            }else{
                exit(json_encode(['status'=>1,'msg'=>'设置失败']));
            }

        }



    }




}

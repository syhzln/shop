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
use app\admin\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use app\admin\logic\UsersLogic;
use app\home\logic\UsersLogic as UserLogic;
use think\Loader;
use Psp;
use Grpc;

class User extends Base {

    public function index(){
        return $this->fetch();
    }

    /**
     * 会员列表
     */
    public function ajaxindex(){
        $p =I('p',1);
        /*I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('email') ? $condition['email'] = I('email') : false;
          搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }*/
        // 搜索条件
        $mobile = trim(I('post.mobile',''));
        $email = trim(I('post.email',''));
        $tps138_id =trim(I('post.tps138_id',''));
        //GRPC SERVICE
        $payload = validate_json_web_token(cookie('_authtoken'));
        $org_id =(int)$payload['org_id'];
        $page = new Psp\Pagination();
        $page->setSortAsc(false); //倒叙
        $page->setSortBy("uid");
        $page->setIndex((int)$p);
        $page->setLimit(20);
        //获取会员列表
        $org_page = new Psp\User\OrgPage();
        $org_page->setOrgId($org_id);
        $org_page->setPageInfo($page);
        list($reply,$status) = GRPC('user')->GetMemberList($org_page)->wait();
        /****搜索***/
        if(trim($mobile) || trim($email) || trim($tps138_id)){
            $keywords = new Psp\User\MemberKeywords();
            $keywords->setEmail($email);
            $keywords->setPhone($mobile);
            $keywords->setUserName($tps138_id);// 此处改为id
            $keywords->setPageInfo($page);
            list($reply,$status) = GRPC('user')->SearchMemberRequest($keywords)->wait();
        }
        //取出数据
        foreach ($reply->getUserInfo() as $k=>$v){
            $userList[$k]['user_id'] = $v->getUserId();
            $userList[$k]['nick_name'] = $v->getName();//会员昵称
            $userList[$k]['tps138_id'] = $v->getTps138Id();
            $userList[$k]['wh_id'] = $v->getWh181Id();//id
            $userList[$k]['store_prefix'] = $v->getStorePrefix();
            $userList[$k]['email'] = $v->getEmail();
            $userList[$k]['mobile'] = $v->getPhone();
            $userList[$k]['qq'] = $v->getQq();
            $userList[$k]['status'] = $v->getStatus();
            $userList[$k]['sex'] = $v->getSex();
            //$userList[$k]['upgrade_time'] = $v->getUpgradeTime()->getSeconds();//等级达成时间
            $userList[$k]['user_title'] = $v->getUserTitle();//会员职称
            $userList[$k]['region_id'] = $v->getRegionId(); // 区/县id
            //$userList[$k]['enable_time'] = $v->getEnableTime()->getSeconds();//账号激活时间
            //$userList[$k]['update_time'] = $v->getUpdateTime()->getSeconds();//账号更新时间
            $userList[$k]['user_level'] = $v->getUserLevel();
            $userList[$k]['user_title'] = $v->getUserTitle();
            $userList[$k]['role_id'] = $v->getRoleId();
            $userList[$k]['org_id'] = $v->getOrgId();
            $userList[$k]['total_amount'] = $v->getTotalConsumption();//累计消费
            $userList[$k]['user_money'] = $v->getUserBalance();//会员余额
            //$userList[$k]['children_nums'] = $v->getChildrenNums();//会员下线数
//            $childNums = explode(',',$userList[$k]['children_nums']);
//            $userList[$k]['children_nums'] = count($childNums);
            $userList[$k]['email_validated'] = $v->getIsVerifiedEmail();//是否验证邮箱
            $userList[$k]['mobile_validated'] = $v->getIsVerifiedMobile();//是否手机
            $userList[$k]['create_time'] = $v->getCreateTime()->getSeconds();//注册时间
        }

        //总条数
        $total_count = $reply->getPageResult()->getTotalRecords();
        //每页条数
        $limit_page =$reply->getPageResult()->getPageSize();
        if($p ==1){
            adminOperateLog('会员列表',1);
        }

        $Page  = new AjaxPage($total_count,$limit_page);
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * 会员详细信息查看
     */
    public function detail(){
        $uid = I('get.id/d');
        $org_id= I('get.org_id/d');
        if(IS_POST){
            //  会员信息编辑
            $password = trim(I('post.password'));
            $password2 = trim(I('post.password2'));
            if($password != '' && $password != $password2){
                exit($this->error('两次输入密码不同'));
            }
            if($password){
                $token =$_POST['token'];
                $password = sha1('!#*'.trim(I('post.password')).$token.'tps');
            }
            $params = new Psp\User\SaveMember();
            $params->setUserId((int)$_POST['user_id']);
            $params->setName($_POST['nick_name']);
            $params->setParentId(empty($_POST['parent_id']) ? 0 : $_POST['parent_id']); //推荐人id
            $params->setEmail(empty($_POST['email']) ? '' : $_POST['email']);
            $params->setPhone(empty($_POST['mobile']) ? '' : $_POST['mobile']);
            $params->setPwd($password);
            $params->setSex((int)$_POST['sex']);
            $params->setStatus((int)$_POST['status']); // 1正常 0禁用
            $params->setQq(empty($_POST['qq']) ? '' : $_POST['qq']);
            $params->setUpdateTime(grpcTime());
            list($reply,$status) = GRPC('user')->UpdateMember($params)->wait();
            $ret = $reply->getRet();
            $msg = $reply->getMsg();
            if($ret == 'ok'){
                //写入操作日志
                $log = new Psp\User\AdminLog();
                $payload = validate_json_web_token(cookie('_authtoken'));
                $log->setAdminId((int)$payload['admin_id']);
                $log->setLogInfo('修改会员');
                $log->setLogIp(request()->ip());
                $log->setOrgId((int)$org_id);
                $log->setName($payload['user_name']);
                $log->setLogType(3); //修改会员
                $log->setOperateTime(grpcTime()); //操作时间
                list($reply, $status) = GRPC('user')->AddAdminLog($log)->wait();
                $this->success("{$msg}",U('Admin/User/index'));
                exit;
            }else{
                $this->error("{$msg}");
            }
        }
        //取出会员详情
        $params = new Psp\User\Mid();
        $params->setOrgId($org_id);//鸡肋
        $params->setMemberId($uid);
        list($reply,$status) = GRPC('user')->GetMemberInfo($params)->wait();
        $user['user_id'] = $reply->getUserInfo()->getUserId();
        $user['nick_name'] = $reply->getUserInfo()->getName();
        $user['wh181_id'] = $reply->getUserInfo()->getWh181Id();
        $user['store_prefix'] = $reply->getUserInfo()->getStorePrefix();
        $user['email'] = $reply->getUserInfo()->getEmail();
        $user['mobile'] = $reply->getUserInfo()->getPhone();
        $user['qq'] = $reply->getUserInfo()->getQq();
        $user['status'] = $reply->getUserInfo()->getStatus();
        $user['sex'] = $reply->getUserInfo()->getSex();
        $user['token'] = $reply->getUserInfo()->getToken();
        $user['upgrade_time'] = $reply->getUserInfo()->getUpdateTime()->getSeconds();//等级达成时间
        $user['user_title'] = $reply->getUserInfo()->getUserTitle();//会员职称
        $user['region_id'] = $reply->getUserInfo()->getRegionId();//国别
        $user['role_id'] = $reply->getUserInfo()->getRoleId();//角色id
        $user['org_id'] = $reply->getUserInfo()->getOrgId();//组织id
        $user['create_time'] = $reply->getUserInfo()->getCreateTime()->getSeconds();//创建时间
        $user['email_validated'] = $reply->getUserInfo()->getIsVerifiedEmail();//是否验证邮箱
        $user['mobile_validated'] = $reply->getUserInfo()->getIsVerifiedMobile();//是否验证手机
//        $user['id_card'] = $reply->getUserInfo()->getIdCard();
        $user['real_name'] = $reply->getUserInfo()->getRealName();
        $user['recommond_id'] = $reply->getUserInfo()->getParentId();
//        $user['card_url'] = $reply->getUserInfo()->getCardUrl();
//        $user['alipay_code'] = $reply->getUserInfo()->getAlipayCode();
//        $user['bank_code'] = $reply->getUserInfo()->getBankCode();
//        $user['bank_name'] = $reply->getUserInfo()->getBankName();
        adminOperateLog('查看会员',1);
        $this->assign('user',$user);
        return $this->fetch();
    }



    public function userFrameWork()
    {
//        return 123123;
        $id = I('id',false);
        if (!$id) return ;
        $data = json_encode($this->getUserSons($id,7));
        $this->assign('data',$data);
        return $this->fetch('framework');

    }

    public function getUserSons($id ,$lv)
    {
        if ($lv > 0) {
            $user_son = $this->getData($id);

            $user_framework = [];
            foreach ($user_son as $k => $v) {

                $user_framework[$k]['value'] = implode(' | ', $v);
//                $user_framework[$k]['value'] = $v['wh_id'];
                $user_framework[$k]['name'] = $v['wh181_id'];
                $user_framework[$k]['children'] = $this->getUserSons($v['wh181_id'], $lv - 1);

            }

            return $user_framework;


        }
    }

    public function getData($id)
    {
        $db = Db::connect([ 'type'           => 'mysql',
            // 服务器地址
            'hostname'       =>'47.96.170.247',
            // 数据库名
            'database'       => 'user_center',
            // 用户名
            'username'       => 'admin',
            // 密码
            'password'       => 'admin',
            // 端口
            'hostport'       => '3306',]);
        return $db->table('users')->field('wh181_id,name,user_level,user_title')->where('parent_id',$id)->select();

    }

    /*public function add_user(){
    	if(IS_POST){
    		$data = I('post.');
			$user_obj = new UsersLogic();
			$res = $user_obj->addUser($data);
			if($res['status'] == 1){
				$this->success('添加成功',U('User/index'));exit;
			}else{
				$this->error('添加失败,'.$res['msg'],U('User/index'));
			}
    	}
    	return $this->fetch();
    }*/

    /*public function export_user(){
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">会员ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">会员昵称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">会员等级</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">邮箱</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">注册时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">最后登陆</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">余额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">积分</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">累计消费</td>';
    	$strTable .= '</tr>';
    	$count = M('users')->count();
    	$p = ceil($count/5000);
    	for($i=0;$i<$p;$i++){
    		$start = $i*5000;
    		$end = ($i+1)*5000;
    		$userList = M('users')->order('user_id')->limit($start.','.$end)->select();
    		if(is_array($userList)){
    			foreach($userList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_id'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['level'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['email'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['reg_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['last_login']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_points'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_amount'].' </td>';
    				$strTable .= '</tr>';
    			}
    			unset($userList);
    		}
    	}
    	$strTable .='</table>';
    	downloadExcel($strTable,'users_'.$i);
    	exit();
    }*/

    /**
     * 用户收货地址查看
     */
    public function address(){
        $uid = I('get.id/d');
        $params = new Psp\Member\Uid();
        $params->setUid($uid);
        list($reply,$status) = GRPC('member')->GetUserAddress($params)->wait();
        $areamap= new \area\area();
        foreach($reply->getAddressList() as $k=>$v){
            $lists[$k]['address_id'] = $v->getAddressId();
            $lists[$k]['uid'] = $v->getUid();
            $lists[$k]['location_code'] = $v->getLocationCode();
            $lists[$k]['address_info'] =$areamap->getAddrstr($lists[$k]['location_code']);
            $address =explode(',',$lists[$k]['address_info']);
            $lists[$k]['province'] = $address[0];
            $lists[$k]['city'] = $address[1];
            $lists[$k]['area'] = $address[2];
            $lists[$k]['address'] = $v->getAddress();
            $lists[$k]['consignee'] = $v->getName();
            $lists[$k]['zipcode'] = $v->getPostCode();
            $lists[$k]['mobile'] = $v->getPhone();
            $lists[$k]['is_default'] = $v->getIsDefault();

        }
        adminOperateLog('用户收货地址查看',1);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 删除会员
     */
    public function delete(){
//        $uid = I('get.id/d');
//        $org_id = I('get.org_id/d');
//        if($uid){
//            $params = new Psp\User\Mid();
//            $params->setOrgId($org_id);//鸡肋
//            $params->setMemberId($uid);
//            list($reply,$status) = GRPC('user')->DelMember($params)->wait();
//            $ret = $reply->getRet();
//            $msg = $reply->getMsg();
//            if($ret == 'ok'){
//                $this->success("{$msg}",U('Admin/User/index'));
//            }else{
//                $this->error("{$msg}");
//                exit;
//            }
//        }else{
        $this->error('参数错误');
//        }
    }
    /**
     * 删除会员
     */
    /*public function ajax_delete(){
        $uid = I('id');
        if($uid){
            $row = M('users')->where(array('user_id'=>$uid))->delete();
            if($row !== false){
                $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'data' => ''));
            }else{
                $this->ajaxReturn(array('status' => 0, 'msg' => '删除失败', 'data' => ''));
            }
        }else{
            $this->ajaxReturn(array('status' => 0, 'msg' => '参数错误', 'data' => ''));
        }
    }*/

    /**
     * 账户资金记录
     */
    public function account_log(){
        $user_id = I('get.id');
        //获取类型
        $type = I('get.type');
        //获取记录总数
        //$count = M('account_log')->where(array('user_id'=>$user_id))->count();
        //$page = new Page($count);
        //$lists  = M('account_log')->where(array('user_id'=>$user_id))->order('change_time desc')->limit($page->firstRow.','.$page->listRows)->select();
        adminOperateLog('账户资金记录',1);
        $this->assign('user_id',$user_id);
        //$this->assign('page',$page->show());
        //$this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 账户资金调节
     */
    public function account_edit(){
        $order_info = I('get.');
        $user_id = $order_info['user_id'];
        if(!$user_id > 0)
            $this->error("参数有误");
        //$user = M('users')->field('user_id,user_money,frozen_money,pay_points,is_lock')->where('user_id',$user_id)->find();
        if(IS_POST){
            $return_info = I('post.');
            $return_id   = $return_info['return_id'];
            if(!$return_info['desc'])
                $this->error("请填写操作说明");
            //加减用户资金
            $m_op_type = I('post.money_act_type');
            $user_money = I('post.user_money/f');
            $user_money =  $m_op_type ? $user_money : 0-$user_money;
            //加减用户积分
            $p_op_type = I('post.point_act_type');
            $pay_points = I('post.pay_points/d');
            $pay_points =  $p_op_type ? $pay_points : 0-$pay_points;
            //加减冻结资金
            $f_op_type = I('post.frozen_act_type');
            $revision_frozen_money = I('post.frozen_money/f');
            if( $revision_frozen_money != 0){    //有加减冻结资金的时候
                $frozen_money =  $f_op_type ? $revision_frozen_money : 0-$revision_frozen_money;
                /*$frozen_money = $user['frozen_money']+$frozen_money;    //计算用户被冻结的资金
                if($f_op_type==1 and $revision_frozen_money > $user['user_money']){ $this->error("用户剩余资金不足！！");}
                if($f_op_type==0 and $revision_frozen_money > $user['frozen_money']){$this->error("冻结的资金不足！！");}*/
                $user_money = $f_op_type ? 0-$revision_frozen_money : $revision_frozen_money ;    //计算用户剩余资金
                //M('users')->where('user_id',$user_id)->update(['frozen_money' => $frozen_money]);
            }

            if(accountLog($user_id,$user_money,$pay_points,$return_info['desc'],0,$return_info['order_id'],$return_info['order_sn'])){
                if($return_id>0){  //有退货id,是订单退款，要更新退货单状态
                    $orderLogic = new OrderLogic();
                    $res = $orderLogic->alterReturnGoodsStatus($return_id,$return_info['order_id']);
                    $orderLogic->closeOrderByReturn($return_info['order_id']);
                    if($res)
                        $this->success("操作成功", U("Admin/order/return_info", array('id' => $return_id)));
                    $this->error("操作失败");
                }
                $this->success("操作成功",U("Admin/User/account_log",array('id'=>$user_id)));
            }else{
                $this->error("操作失败");
            }
            exit;
        }
        if($order_info['return_id']){  //有退货id,是订单退款
            /*$return_info = M('return_goods')->field('order_sn,order_id,goods_id,spec_key')->where('id',$order_info['return_id'])->find(); //查找退货商品信息
            $order_info=array_merge($return_info,$order_info);  //合并数值
            $order_goods= M('order_goods')->where(array_splice($return_info ,1))->find();  //去掉order_sn 后作为条件去查找
            $order_info['user_money']  =$order_goods['member_goods_price']*$order_goods['goods_num'];  //计算默认退款*/
        }
        adminOperateLog('账户资金调节',1);
        $this->assign('user_id',$user_id);
        //$this->assign('user',$user);

        $this->assign('order_info',$order_info);
        return $this->fetch();
    }

    public function recharge(){
        $timegap = I('timegap');
        $nickname = I('nickname');
        $map = array();
        if($timegap){
            $gap = explode(' - ', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $map['ctime'] = array('between',array(strtotime($begin),strtotime($end)));
        }
        if($nickname){
            $map['nickname'] = array('like',"%$nickname%");
        }
        //$count = M('recharge')->where($map)->count();
        //$page = new Page($count);
        //$lists  = M('recharge')->where($map)->order('ctime desc')->limit($page->firstRow.','.$page->listRows)->select();
        //$this->assign('page',$page->show());
        //$this->assign('pager',$page);
        //$this->assign('lists',$lists);
        adminOperateLog('会员充值记录列表',1);
        return $this->fetch();
    }

    /*public function level(){
    	$act = I('get.act','add');
    	$this->assign('act',$act);
    	$level_id = I('get.level_id');
    	if($level_id){
    		$level_info = D('user_level')->where('level_id='.$level_id)->find();
    		$this->assign('info',$level_info);
    	}
    	return $this->fetch();
    }

    public function levelList(){
    	$Ad =  M('user_level');
        $p = $this->request->param('p');
    	$res = $Ad->order('level_id')->page($p.',10')->select();
    	if($res){
    		foreach ($res as $val){
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
    	$count = $Ad->count();
    	$Page = new Page($count,10);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	return $this->fetch();
    }*/

    /**
     * 会员等级添加编辑删除
     */
    /*public function levelHandle()
    {
        $data = I('post.');
        $userLevelValidate = Loader::validate('UserLevel');
        $return = ['status' => 0, 'msg' => '参数错误', 'result' => ''];//初始化返回信息
        if ($data['act'] == 'add') {
            if (!$userLevelValidate->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '添加失败', 'result' => $userLevelValidate->getError()];
            } else {
                $r = D('user_level')->add($data);
                if ($r !== false) {
                    $return = ['status' => 1, 'msg' => '添加成功', 'result' => $userLevelValidate->getError()];
                } else {
                    $return = ['status' => 0, 'msg' => '添加失败，数据库未响应', 'result' => ''];
                }
            }
        }
        if ($data['act'] == 'edit') {
            if (!$userLevelValidate->scene('edit')->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '编辑失败', 'result' => $userLevelValidate->getError()];
            } else {
                $r = D('user_level')->where('level_id=' . $data['level_id'])->save($data);
                if ($r !== false) {
                    $return = ['status' => 1, 'msg' => '编辑成功', 'result' => $userLevelValidate->getError()];
                } else {
                    $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
                }
            }
        }
        if ($data['act'] == 'del') {
            $r = D('user_level')->where('level_id=' . $data['level_id'])->delete();
            if ($r !== false) {
                $return = ['status' => 1, 'msg' => '删除成功', 'result' => ''];
            } else {
                $return = ['status' => 0, 'msg' => '删除失败，数据库未响应', 'result' => ''];
            }
        }
        $this->ajaxReturn($return);
    }*/

    /**
     * 搜索用户名
     */
//    public function search_user()
//    {
//        $search_key = trim(I('search_key'));
//        if(strstr($search_key,'@'))
//        {
//            $list = M('users')->where(" email like '%$search_key%' ")->select();
//            foreach($list as $key => $val)
//            {
//                echo "<option value='{$val['user_id']}'>{$val['email']}</option>";
//            }
//        }
//        else
//        {
//            $list = M('users')->where(" mobile like '%$search_key%' ")->select();
//            foreach($list as $key => $val)
//            {
//                echo "<option value='{$val['user_id']}'>{$val['mobile']}</option>";
//            }
//        }
//        exit;
//    }

    /**
     *
     * @time 2017/11/22
     * @author fzq
     * 发送站内信
     */
    public function sendMessage()
    {
        $user_id_array = trim(I('get.user_id_array'),',');
        $users = array();
        if (!empty($user_id_array)) {
            //获取会员 138id/沃好id 昵称
            $ids = new Psp\User\MemberId();
            $ids->setMemberIds($user_id_array);
            list($reply,$status) = GRPC('user')->GetMemberIds($ids)->wait();
            foreach ($reply->getMemberIds() as $k=>$v){
                $users[$k]['tps138_id'] = $v->getTps138Id();  //此处取 沃好id
                $users[$k]['nick_name'] = $v->getNickName();
                $users[$k]['user_id'] = $v->getUserId();
            }
        }
        adminOperateLog('发送站内信',1);
        $this->assign('users',$users);
        return $this->fetch();
    }

    /**
     * 发送系统消息
     * @author fzq
     * @time  2017/11/221
     */
    public function doSendMessage()
    {
        $call_back = I('call_back');//回调方法
        $text= I('post.text');//内容
        //$type = I('post.type', 0);//个体or全体
        $payload =validate_json_web_token(cookie('_authtoken'));
        $admin_id =$payload['admin_id'];
        $users = I('post.user/a');//个体id
        $users = implode(',',$users);
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $message = new Psp\User\SendSmsToInbox();
        $message->setMemberIds($users);
        $message->setSendId((int)$admin_id); //发送方
        $message->setPiority(1);//默认一级
        $message->setTemplateId(1);//1站内信  2邮件  3系统消息  4 活动消息
        $message->setType(1);//1会员信息  2平台信息  3 商家信息
        $message->setParams($text);//内容
        $message->setSendTime($time);//发送时间
        list($reply,$status) = GRPC('user')->SendSmsInbox($message)->wait();
        adminOperateLog('发送系统消息',1);
        echo "<script>parent.{$call_back}(1);</script>";
        exit();
    }

    /**
     *
     * @time 2016/09/03
     * @author dyr
     * 发送邮件
     */
    public function sendMail()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $user_where = array(
                'user_id' => array('IN', $user_id_array),
                'email' => array('neq', '')
            );
            //$users = M('users')->field('user_id,nickname,email')->where($user_where)->select();
        }
        adminOperateLog('发送邮件',1);
        $this->assign('smtp', tpCache('smtp'));
        $this->assign('users', $users);
        return $this->fetch();
    }

    /**
     * 发送邮箱
     * @author dyr
     * @time  2016/09/03
     */
    public function doSendMail()
    {
        $call_back = I('call_back');//回调方法
        $message = I('post.text');//内容
        $title = I('post.title');//标题
        $users = I('post.user/a');
        $email= I('post.email');
        if (!empty($users)) {
            $user_id_array = implode(',', $users);
            //$users = M('users')->field('email')->where(array('user_id' => array('IN', $user_id_array)))->select();
            $to = array();
            foreach ($users as $user) {
                if (check_email($user['email'])) {
                    $to[] = $user['email'];
                }
            }
            $res = send_email($to, $title, $message);
            echo "<script>parent.{$call_back}({$res['status']});</script>";
            exit();
        }
        if($email){
            $res = send_email($email, $title, $message);
            echo "<script>parent.{$call_back}({$res['status']});</script>";
            adminOperateLog('发送邮箱',1);
            exit();
        }
    }

    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
        //$model = M("withdrawals");
        $_GET = array_merge($_GET,$_POST);
        unset($_GET['create_time']);

        $status = I('status');
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y/m/d',strtotime('-1 year')).'-'.date('Y/m/d',strtotime('+1 day'));
        $create_time2 = explode('-',$create_time);
        $this->assign('start_time', $create_time2[0]);
        $this->assign('end_time', $create_time2[1]);
        $where = " create_time >= '".strtotime($create_time2[0])."' and create_time <= '".strtotime($create_time2[1])."' ";

        if($status === '0' || $status > 0)
            $where .= " and status = $status ";
        $user_id && $where .= " and user_id = $user_id ";
        $account_bank && $where .= " and account_bank like '%$account_bank%' ";
        $account_name && $where .= " and account_name like '%$account_name%' ";

        //$count = $model->where($where)->count();
        //$Page  = new Page($count,16);
        //$list = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        adminOperateLog('提现申请记录',1);
        $this->assign('create_time',$create_time);
        //$show  = $Page->show();
        //$this->assign('show',$show);
        //$this->assign('pager',$Page);
        //$this->assign('list',$list);
        C('TOKEN_ON',false);
        return $this->fetch();
    }
    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {
        //$model = M("withdrawals");
        //$model->where('id ='.$_GET['id'])->delete();
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        adminOperateLog('删除申请记录',1);
        $this->ajaxReturn($return_arr);
    }

    /**
     * 修改编辑 申请提现
     */
    public function editWithdrawals()
    {
        $id = I('id');
        //$withdrawals = DB::name('withdrawals')->where('id',$id)->find();
        //$user = M('users')->where("user_id = {$withdrawals[user_id]}")->find();
        if (IS_POST) {
            $data = I('post.');
            // 如果是已经给用户转账 则生成转账流水记录
            //if ($data['status'] == 1 && $withdrawals['status'] != 1) {
            //if ($user['user_money'] < $withdrawals['money']) {
            /*$this->error("用户余额不足{$withdrawals['money']}，不够提现");
            exit;*/
            //}
            //accountLog($withdrawals['user_id'], ($withdrawals['money'] * -1), 0, "平台提现");
            $remittance = array(
                /*'user_id' => $withdrawals['user_id'],
                'bank_name' => $withdrawals['bank_name'],
                'account_bank' => $withdrawals['account_bank'],
                'account_name' => $withdrawals['account_name'],
                'money' => $withdrawals['money'],
                'status' => 1,
                'create_time' => time(),
                'admin_id' => session('admin_id'),
                'withdrawals_id' => $withdrawals['id'],
                'remark' => $data['remark'],*/
            );
            //M('remittance')->add($remittance);
            //}
            //DB::name('withdrawals')->update($data);
            $this->success("操作成功!", U('Admin/User/remittance'), 3);
            exit;
        }

        /*if ($user['nickname'])
            $withdrawals['user_name'] = $user['nickname'];
        elseif ($user['email'])
            $withdrawals['user_name'] = $user['email'];
        elseif ($user['mobile'])
            $withdrawals['user_name'] = $user['mobile'];
        $this->assign('user', $user);*/
        //$this->assign('data', $withdrawals);
        adminOperateLog('修改申请提现',1);
        return $this->fetch();
    }

    public function withdrawals_update(){
        $id = I('id/a');
        $status = I('status');
        //$withdrawals = M('withdrawals')->where('id','in', $id)->select();
        if($status == 1){
            //$r = M('withdrawals')->where('id','in', $id)->save(array('status'=>$status,'check_time'=>time()));
        }else if($status == -1){
            //$r = M('withdrawals')->where('id','in', $id)->save(array('status'=>$status,'refuse_time'=>time()));
        }else if($status == 2){
            /*foreach($withdrawals as $val){
                $user = M('users')->where(array('user_id'=>$val['user_id']))->find();
                if($user['user_money'] < $val['money'])
                {
                    $data['status'] = -2;
                    $data['remark'] = '账户余额不足';
                    M('withdrawals')->where(array('id'=>$val['id']))->save($data);
                }else{
                    if($val['bank_name'] == '支付宝 '){
                        //流水号1^收款方账号1^收款账号姓名1^付款金额1^备注说明1|流水号2^收款方账号2^收款账号姓名2^付款金额2^备注说明2
                        $alipay['batch_no'] = time();
                        $alipay['batch_fee'] += $val['money'];
                        $alipay['batch_num'] += 1;
                        $str = isset($alipay['detail_data']) ? '|' : '';
                        $alipay['detail_data'] .= $str.$val['pay_code'].'^'.$val['account_bank'].'^'.$val['realname'].'^'.$val['money'].'^'.$val['remark'];
                    }
                    if($val['bank_name'] == '微信'){
                        $wxpay = array(
                            'userid' => $val['user_id'],//用户ID做更新状态使用
                            'openid' => $val['account_bank'],//收钱的人微信 OPENID
                            'pay_code'=>$val['pay_code'],//提现申请ID
                            'money' => $val['money'],//金额
                            'desc' => '恭喜您提现申请成功!'
                        );
                        $res = $this->transfer('weixin',$wxpay);//微信在线付款转账
                        if($res['partner_trade_no']){
                            accountLog($val['user_id'], ($val['money'] * -1), 0,"平台处理用户提现申请");
                            $r = M('withdrawals')->where(array('id'=>$val['id']))->save(array('status'=>$status,'pay_time'=>time()));
                        }else{
                            $this->ajaxReturn(array('status'=>0,'msg'=>$res['msg']),'JSON');
                        }
                    }
                }
            }*/
            if(!empty($alipay)){
                $this->transfer('alipay',$alipay);
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
        }else if($status == 3){
            //$r = M('withdrawals')->where('id in ('.implode(',', $id).')')->delete();
        }else{
            //accountLog($val['user_id'], ($val['money'] * -1), 0,"管理员处理用户提现申请");//手动转账，默认视为已通过线下转方式处理了该笔提现申请
            //$r = M('withdrawals')->where('id in ('.implode(',', $id).')')->save(array('status'=>2,'pay_time'=>time()));
        }
        /*if($r){
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }*/
        adminOperateLog('编辑申请提现',1);
    }

    public function transfer($atype,$data){
        if($atype == 'weixin'){
            include_once  PLUGIN_PATH."payment/weixin/weixin.class.php";
            $wxpay_obj = new \weixin();
            return $wxpay_obj->transfer($data);
        }else{
            //支付宝在线批量付款
            include_once  PLUGIN_PATH."payment/alipay/alipay.class.php";
            $alipay_obj = new \alipay();
            return $alipay_obj->transfer($data);
        }
        adminOperateLog('转账',1);
    }
    /**
     *  转账汇款记录
     */
    public function remittance(){
        //$model = M("remittance");
        $_GET = array_merge($_GET,$_POST);
        unset($_GET['create_time']);

        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');

        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y-m-d',strtotime('-1 year')).' - '.date('Y-m-d',strtotime('+1 day'));
        $create_time2 = explode(' - ',$create_time);
        $this->assign('start_time',$create_time2[0]);
        $this->assign('end_time',$create_time2[1]);
        $where = " create_time >= '".strtotime($create_time2[0])."' and create_time <= '".strtotime($create_time2[1])."' ";
        $user_id && $where .= " and user_id = $user_id ";
        $account_bank && $where .= " and account_bank like '%$account_bank%' ";
        $account_name && $where .= " and account_name like '%$account_name%' ";

        //$count = $model->where($where)->count();
        //$Page  = new Page($count,16);
        //$list = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        //$this->assign('pager',$Page);
        adminOperateLog('转账汇款记录',1);
        $this->assign('create_time',$create_time);
        //$show  = $Page->show();
        //$this->assign('show',$show);
        //$this->assign('list',$list);
        C('TOKEN_ON',false);
        return $this->fetch();
    }

    /*
    *消息中心 列表
    */
    public function message()
    {
        $p =I('p/d',1);
        $limit_page = 18;
        $payload = validate_json_web_token(cookie('_authtoken'));
        $org_id =(int)$payload['org_id'];
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("msg_id");
        $page->setIndex($p);
        $page->setLimit($limit_page);
        //获取会员列表
        $org_page = new Psp\User\OrgPage();
        $org_page->setOrgId($org_id);
        $org_page->setPageInfo($page);
        list($reply,$status) = GRPC('user')->GetMessageList($org_page)->wait();
        foreach ($reply->getMessageList() as $k=>$v){
            $list[$k]['msg_id'] = $v->getMsgId();
            $list[$k]['member_id'] = $v->getMemberId();
            $list[$k]['type'] = $v->getType(); // 1
            $list[$k]['params'] = $v->getParams();
            $list[$k]['send_id'] = $v->getSendId();
            $list[$k]['send_time'] = $v->getSendTime()->getSeconds();
        }

        $total_num =  $total_count = $reply->getPageResult()->getTotalRecords();
        $this->assign('total_num',$total_num);
        if($p ==1){
            adminOperateLog('消息中心列表',1);
        }
        $Page = new Page($total_num, $limit_page);
        $show = $Page->show();
        $this->assign('page', $show);
        $this->assign('lists',$list);
        return $this->fetch();
    }

    /**短信日志记录*/
    public function shortMsg()
    {
        $p =I('p/d',1);
        $limit_page = 18;
        $keywords = trim(I('phone'));//搜索
        $payload = validate_json_web_token(cookie('_authtoken'));
        $org_id =(int)$payload['org_id'];
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("sms_id");
        $page->setIndex($p);
        $page->setLimit($limit_page);
        $org = new Psp\User\OrgPage();
        $org->setOrgId($org_id);
        $org->setPageInfo($page);
        list($reply,$status) = GRPC('user')->GetSmsLogList($org)->wait();
        /**搜索*/
        if($keywords){
            $words = new Psp\User\SearchMsg();
            $words->setPageInfo($page);
            $words->setMobile($keywords);
            list($reply,$status) = GRPC('user')->SearchMsgRequest($words)->wait();
        }
        foreach ($reply->getSmsLog() as $k=>$v){
            $list[$k]['sms_id'] = $v->getSmsId();
            $list[$k]['user_id'] = $v->getUid();
            $list[$k]['mobile'] = $v->getMobile();
            $list[$k]['content'] = $v->getContent();
            $list[$k]['sms_type'] = $v->getSmsType();
            $list[$k]['sms_status'] = $v->getSendStatus();
            $list[$k]['device_number'] = $v->getDeviceNumber();
            $list[$k]['send_time'] = $v->getSendTime()->getSeconds();
        }
        //总条数
        $total_count = $reply->getPageResult()->getTotalRecords();
        //每页条数
//        $limit_page =$reply->getPageResult()->getPageSize();
        $this->assign('total_num',$total_count);
        $this->assign('lists',$list);
        if($p ==1){
            adminOperateLog('短信日志记录',1);
        }
        $Page = new Page($total_count, $limit_page);
        $show = $Page->show();
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function relation(){
        $empty = new Psp\PBEmpty();
        list($reply,$status) = GRPC('member')->GetUserRelation($empty)->wait();

        $info['user_id'] = $reply->getUserId();
        $info['wh_id'] = $reply->getWhId();
        $info['name'] = $reply->getName();
        $info['title'] = 'LZ'.$reply->getTitle();
        switch ($reply->getLevel()){
            case 0:
                $info['level'] = '免费店铺';
                break;
            case 1:
                $info['level'] = '铜级';
                break;
            case 2:
                $info['level'] = '银级';
                break;
            case 3:
                $info['level'] = '白金级';
                break;
            case 4:
                $info['level'] = '钻石级';
                break;
        }
        //$info['level'] = $reply->getLevel();
        $info['grade'] = 1;
        $info['next'] = $this->next($reply->getSon(),1,$reply->getUserId(),$reply->getUserId());

        //dump($info);die;
        $this->assign('user_info',$info);
        return $this->fetch();
    }


    public function next($son,$grade,$user_id,$parent_id){
        if($son){
            foreach ($son as $k=>$v){
                $next[$k]['user_id'] = $v->getUserId();
                $next[$k]['wh_id'] = $v->getWhId();
                $next[$k]['name'] = $v->getName();
                $next[$k]['title'] = 'LZ'.$v->getTitle();
                switch ($v->getLevel()){
                    case 0:
                        $next[$k]['level'] = '免费店铺';
                        break;
                    case 1:
                        $next[$k]['level'] = '铜级';
                        break;
                    case 2:
                        $next[$k]['level'] = '银级';
                        break;
                    case 3:
                        $next[$k]['level'] = '白金级';
                        break;
                    case 4:
                        $next[$k]['level'] = '钻石级';
                        break;
                }
                //$next[$k]['level'] = $v->getLevel();
                $next[$k]['grade'] = $grade+1;
                $next[$k]['mark'] = str_repeat('*',$grade);
                $next[$k]['parent_user_id'] = $user_id;
                $next[$k]['parent_id_path'] = $parent_id.'_'.$v->getUserId();
                $next[$k]['next'] = $this->next($v->getSon(),$grade+1,$v->getUserId(),$next[$k]['parent_id_path']);

            }
        }else{
            return;
        }

        return $next;
    }

    public function relation1(){

        return $this->fetch();
    }

    public function relation2(){
        $empty = new Psp\PBEmpty();
        list($reply,$status) = GRPC('member')->GetUserRelation($empty)->wait();

        $info['name'] = $reply->getWhId().'(LZ'.$reply->getTitle().')';
        $info['value'] = "wh_id";
        if($this->next1($reply->getSon())){
            $info['children'] = $this->next1($reply->getSon());
        }
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($info,0));
    }

    public function next1($son){
        if($son){
            foreach ($son as $k=>$v){
                $next[$k]['name'] = $v->getWhId().'(LZ'.$v->getTitle().')';
                $next[$k]['value'] = "wh_id";
                if($this->next1($v->getSon())){
                    $next[$k]['children'] = $this->next1($v->getSon());
                }
            }
        }else{
            return;
        }

        return $next;
    }

    //会员工单
    public function user_message(){

        $text=I('post.text');
        $client = GRPC('user');
        $p =I('p/d',1);
        $limit=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("id");
        $page->setIndex($p);
        $page->setLimit($limit);
        $talk=new Psp\User\Wh181Id();
        $talk->setWh181Id($text);
        $talk->setPageInfo($page);
        list($res,$status) = $client->GetUserMessageList($talk)->wait();

        foreach($res->getUserMessageList() as $k=>$v){
            $talklist[$k]['uid']=$v->getUId();
           $talklist[$k]['wh181_id']=$v->getWh181Id();  
           $talklist[$k]['name']=$v->getName();                               
           $talklist[$k]['u_message']=$v->getUmessage();
           $talklist[$k]['message_time']=$v->getMessageTime();
           $talklist[$k]['message_number']=$v->getMessageNumber();
           $talklist[$k]['message_title']=$v->getMessageTitle();
        }
        //var_dump($talklist);die;

        $total_count = $res->getPaginationResult()->getTotalRecords();
        if($p ==1){
            adminOperateLog('会员工单',1);
        }
        $Page = new Page($total_count,$limit) ;
        $show = $Page->show();

        $this->assign('talklist',$talklist);
        $this->assign('page',$show);
        return $this->fetch();
    }
 //回复会员工单
    public function replyMessage(){
        // $uid=I('get.uid');
        // $uid=(int)trim($uid);
        $whid=I('get.whid');
        $message_number=I('get.message_number');

       $client = GRPC('user');
            $talk = new Psp\User\GetLeavingTalks();
            $talk->setWh181Id($whid);
            $talk->setMessageNumber($message_number);
            //$talk->setPageInfo($page);
            list($res,$status) = $client->GetTalkLists($talk)->wait();
            
        foreach($res->getTalkLi() as $k=>$v){
            $talks[$k]['uid']=$v->getUId();
            $talks[$k]['u_message']=$v->getUmessage();
            $talks[$k]['k_message']=$v->getKmessage();
            $talks[$k]['message_time']=$v->getMessageTime();
             }
             if(IS_POST){
                $id=I('post.mID');
                $texts=I('post.texts');
                $client = GRPC('user');
            $user = new Psp\User\CustomerService();

            $user->setUid($id);//会员id
            $user->setKMessage($texts);//客服回复
            $user->setIsTalk(1);//是否回复

            list($res,$status) = $client->SetTalkMessage($user)->wait();
            if($res){
                return (1);
             }
            }


            adminOperateLog('回复会员工单',1);

         $this->assign('talks',$talks);
        return $this->fetch();
    }
//已完结工单
    public function finished_message(){
       $text=I('post.text');
        $client = GRPC('user');
        $p =I('p/d',1);
        $limit=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(true); //倒叙
        $page->setSortBy("id");
        $page->setIndex($p);
        $page->setLimit($limit);
        $talk=new Psp\User\Wh181Id();
        $talk->setWh181Id($text);
        $talk->setPageInfo($page);
        $talk->setIsTalk(1);
        list($res,$status) = $client->GetUserMessageList($talk)->wait();

        foreach($res->getUserMessageList() as $k=>$v){
            $talklist[$k]['uid']=$v->getUId();
           $talklist[$k]['wh181_id']=$v->getWh181Id();  
           $talklist[$k]['name']=$v->getName();                               
           $talklist[$k]['u_message']=$v->getUmessage();
           $talklist[$k]['message_time']=$v->getMessageTime();
           $talklist[$k]['message_number']=$v->getMessageNumber();
           $talklist[$k]['message_title']=$v->getMessageTitle();
        }
        //var_dump($talklist);die;

        $total_count = $res->getPaginationResult()->getTotalRecords();
        if($p ==1){
            adminOperateLog('已完结工单',1);
        }
        $Page = new Page($total_count,$limit) ;
        $show = $Page->show();

        $this->assign('talklist',$talklist);
        $this->assign('page',$show);
        return $this->fetch();
    }
    public function message_delete(){
        $uid = I('get.id/d');
        
        $client = GRPC('user');
        $user = new Psp\User\Uid();
        $user->setUid($uid);//会员id

        list($res,$status) = $client->DeletMessage($user)->wait();
        adminOperateLog('删除工单',1);
        $this->success("操作成功");
    }
    //短信通告
    public function message_notice(){
//        exit('暂未开通');
        if(IS_POST){
            $data =I('post.');
            $type = $data['type'];
            $level = empty($data['mlevel']) ? '' : $data['mlevel'];//等级
            $title = empty($data['mtitle']) ? '' : $data['mtitle'];//职称
            if($type == 1){
                //会员
                if(empty($level) && empty($title)){
                    $this->ajaxReturn(['msg'=>'请先选择发送的对象','status'=>-1]);
                }
                $level = implode(',',empty($data['mlevel']) ? array() : $level); //店铺等级
                $title = implode(',',empty($data['mtitle']) ? array() : $title); //会员职称
                $content = $data['msg_content'];
                $msg_type = 11;//会员公告

            }

            if($type == 2){
                //供应商
                $content = $data['seller_msg_desc'];
                $msg_type =12;//商家公告
            }
            //执行发送
            $params = new Psp\User\MessageNotice();
            $params->setBusinessType($msg_type);
            $params->setMsgContent($content);
            $params->setUserLevel($level);
            $params->setUserTitle($title);
            $params->setMsgType($type); //1会员  2 商家
            list($resp,$status) = GRPC('user')->SendMessageForUser($params)->wait();
            adminOperateLog('短信通告',1);
            $total_num = $resp->getMemberNums();//总人数
            $send_num = $resp->getSendMembers();//可发送人数
            $this->ajaxReturn(['status'=>1,'total_num'=>$total_num,'send_num'=>$send_num]);
        }

        return $this->fetch();
    }

    //买呗列表
    public function memberBuyList(){
        $p = I('p',1);

        $limit =20;
        $page = new Psp\Pagination();
        $page->setSortBy('id');
        $page->setSortAsc(false);
        $page->setLimit($limit);
        $page->setIndex($p);
        //搜索
        $search = new Psp\User\SearchApplyBuy();
        $search->setUserName(I('real_name'));
        $search->setApplyStatus(I('apply_status',0));
        $search->setWhId(I('wh_id'));
        $search->setPageInfo($page);
        list($resp,$staus) = GRPC('user')->GetMemberBuyList($search)->wait();
        if(!empty($resp)){
            foreach ($resp->getBuyList() as $k=>$v){
                $list[$k]['apply_id'] = $v->getApplyId();
                $list[$k]['user_id'] = $v->getUserId();
                $list[$k]['wh_id']  = $v->getWhId();
                $list[$k]['real_name'] = $v->getUserName();
                $list[$k]['phone'] = $v->getPhone();
                $list[$k]['status'] = $v->getStatus();
                $list[$k]['add_time'] = $v->getApplyTime();
                $list[$k]['mark'] = $v->getMark();

            }

            $total_page = $resp->getPaginationResult()->getTotalRecords();
            $page  = new Page($total_page,$limit);
            $show = $page->show();
        }

        if($p ==1){
            adminOperateLog('买呗列表',1);
        }
        $this->assign('page',$show);
        $this->assign('total_num',$total_page);
        $this->assign('list',$list);
        return $this->fetch();
    }

    //买呗详情
    public function memberBuyDetail(){
        $apply_id = I('get.apply_id/d',0);

        if(IS_POST){
            $data = I('post.');
            $info = new Psp\User\ApplyBuyAudit();
            $info->setApplyId($data['apply_id']);
            $info->setUserId($data['user_id']);
            $info->setLimitMoney((float)$data['limit_money']);
            $info->setMark($data['mark']);
            $info->setStatus($data['status']);
            list($resp,$stauts) = GRPC('user')->SetMemberBuyApply($info)->wait();
            $ret = $resp->getRet();
            $msg = $resp->getMsg();
            if($ret == 'ok'){
                //写入操作日志
                $payload = validate_json_web_token(cookie('_authtoken'));
                $org_id = $payload['org_id'];
                $log = new Psp\User\AdminLog();
                $log->setAdminId((int)$payload['admin_id']);
                $log->setLogInfo('审核买呗');
                $log->setLogIp(request()->ip());
                $log->setOrgId((int)$org_id);
                $log->setName($payload['user_name']);
                $log->setLogType(5);//审核买呗
                $time = new Psp\Timestamp();
                $time->setSeconds(time());
                $time->setNanos(1);
                $log->setOperateTime($time); //操作时间
                list($reply, $status) = GRPC('user')->AddAdminLog($log)->wait();
                $this->success('操作成功',U('User/memberBuyList'));
                exit;
            }else{
                $this->error('操作失败');
                exit;
            }

        }
        $app_id = new Psp\User\ApplyId();
        $app_id->setApplyId($apply_id);
        list($resp,$status) = GRPC('user')->GetMemberBuyDetail($app_id)->wait();
        $detail['apply_id'] = $resp->getApplyId();
        $detail['user_id'] = $resp->getUserId();
        $detail['wh_id']  = $resp->getWhId();
        $detail['real_name'] = $resp->getUserName();
        $detail['phone'] = $resp->getPhone();
        $detail['status'] = $resp->getStatus();
        $detail['add_time'] = $resp->getApplyTime();
        $detail['card_num'] = $resp->getCardNum();
        $detail['front_url'] = $resp->getFrontUrl();
        $detail['back_url'] = $resp->getBackUrl();
        $detail['limit_money'] = $resp->getLimitMoney();
        $detail['mark'] = $resp->getMark();
        $household_register = $resp->getHouseholdRegister();
        $detail['household_register'] = explode(',',$household_register);
        adminOperateLog('买呗详情',1);
        $this->assign('detail',$detail);
        return $this->fetch();
        //此处要获取会员信息进行 数据比对
    }

    //删除买呗申请
    public function delMemberBuy(){
        $apply_id = I('post.del_id/d',0);
        $app_id = new Psp\User\ApplyId();
        $app_id->setApplyId($apply_id);
        list($resp,$status) = GRPC('user')->DelMemberBuyApply($app_id)->wait();
        if($resp->getValue()){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'删除失败']);
        }
        adminOperateLog('删除买呗申请',1);

    }

    //获取会员信息
    public function ajaxGetMemberInfo()
    {
        $uid = I('post.user_id');
        $logic = new UserLogic();
        $user = $logic->get_info($uid);
        $user['result']['user_money'] = sprintf("%.2f",$user['result']['user_money']);
        $user['result']['add_time'] = date('Y-m-d H:i',$user['result']['add_time']);
        $user['result']['birthday'] = date('Y-m-d H:i',$user['result']['birthday']);
        $title = array(0=>'乡镇代理(LZ0)',1=>'县级代理(LZ1)',2=>'市级代理(LZ2)',3=>'省级代理(LZ3)',4=>'大区代理(LZ4)',5=>'全国代理(LZ5)',6=>'全球总代理(LZ6)',-1=>'乡镇代理(LZ0)');
        $level = array(0=>'普通会员',1=>'青铜会员',2=>'白银会员',3=>'铂金会员',4=>'钻石会员',-1=>'普通会员');
        $status = array(0=>'禁用',1=>'启用');
        $sex = array(0=>'保密',1=>'男',2=>'女');
        $user['result']['status'] = $status[$user['result']['status']];
        $user['result']['user_title'] =$title[$user['result']['user_title']];
        $user['result']['user_level'] =$level[$user['result']['user_level']];
        $user['result']['sex'] =$sex[$user['result']['sex']];
        adminOperateLog('获取会员信息',1);
        $this->ajaxReturn($user['result']);
    }

    //还款记录
    public function member_repayment_log()
    {
        $user_id = I('user_id');
        $p = I('p',1);
        $logic = new UserLogic();
        $data = $logic->MemberBuyUseList($user_id,$p,2,20);
        //获取买呗账户详情
        $uid = new Psp\Member\Uid();
        $uid->setUid($user_id);
        list($resp) = GRPC('member')->GetMemberBuyDetail($uid)->wait();
        $info['limit_money'] = $resp->getLimitMoney();//我的额度
        $info['now_money'] = $resp->getNowMonthMoney();//本月花费
        $info['last_money'] = $resp->getLastMonthMoney();//上月花费
        $info['last_month'] = $resp->getLastMonth();//上月时间
        $info['now_month'] = $resp->getNowMonth();// 当月时间
        //获取当前月份
        $now_month = date('Ym',time());
        $last_month = date('Ym',strtotime("-1 months",strtotime($now_month)));
        if(empty($info['last_month'])){
            $info['last_month'] = $last_month;
        }
        if(empty($info['now_month'])){
            $info['now_month'] = $now_month;
        }
        if($p ==1){
            adminOperateLog('还款记录',1);
        }
        $this->assign('info',$info);
        $this->assign('page',$data['show']);
        $this->assign('lists',$data['result']);
        return $this->fetch();
    }


    //通过量子ID登录会员个人中心
    public function login_to_usercenter(){

        $payload = validate_json_web_token($_COOKIE['_authtoken']);

        $arr =['1','2','3','15','84'];
        if(!in_array($payload['admin_id'],$arr)){
            $result = array('status'=>2,'msg'=>'权限不足,请联系管理员分配权限');
            $this->ajaxReturn($result);
        }

        $username = I('post.username');
        $time = new Psp\Timestamp();
        $time->setSeconds(time());
        $time->setNanos(1);
        $request = new Psp\Member\LoginRequest();
        $request->setUsername($username);
        $request->setIp(request()->ip());
        $request->setTime($time);
        list($reply,$status) = GRPC('member')->Login($request)->wait();

        //如果账号存在取得用户信息
        $status = $reply->getStatus();

        if($status == '3'){
            $result = array('status'=>3,'msg'=>'账号不存在');
        }elseif($status == '4'){
            $result = array('status'=>4,'msg'=>'账号异常！！！');
        }else{
            $user_id = $reply->getUserId();
            $org_id = $reply->getOrgId();
            //$tps138_id = $reply->getTps138id();
            $wh_id = $reply->getWh181Id();
            $mobile = $reply->getPhone();
            $nick_name = empty($reply->getNickname()) ? $wh_id : $reply->getNickname(); //姓名
            $payload = array(
                'user_id'=>$user_id,
                'org_id'=>$org_id,
                'tps138_id'=>$wh_id, //沃好id
                'nickname'=>$nick_name,
                'mobile'=>$mobile,
            );
            $jwt = create_json_web_token($payload);
            //设置token
            setrawcookie('token', $jwt, 0, '/', get_host(), false, true);
            //登录识别  解决js脚本读取不到token的bug
            setcookie('curLogin','1',0,'/',get_host()); //登录成功
            setcookie('uname',urlencode($nick_name),0,'/',get_host());
            setcookie('cn','0',time()-3600,'/',get_host());
            $url = SITE_URL.U("Home/User/index");
            $result = array('status'=>1,'msg'=>'登陆成功','url'=>$url);
        }
        adminOperateLog('量子ID登录会员',1);
         $this->ajaxReturn($result);
    }





}

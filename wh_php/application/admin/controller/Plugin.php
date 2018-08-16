<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * 插件管理类
 * Date: 2015-10-20
 */

namespace app\admin\controller;

use think\AjaxPage;
use think\Db;
use Psp;
use Grpc;
use think\validate\ValidateRule;

class Plugin extends Base {

    public function _initialize()
    {
        parent::_initialize();
        //  更新插件
        //$this->insertPlugin($this->scanPlugin());
    }

    public function index(){
        //获取物流模板列表
        $client = GRPC(itm);
        $provider = new Psp\Itm\ProvId();
        $areas = new \area\area();
        $area = $areas->getProv(); //获取所有省
        $p = I('p/d',1);

        $page = grpcPage('temp_id',$p,20,false);

        $provider->setPagination($page);
        list($res,$status) = $client->GetTempList($provider)->wait();
        foreach ($res->getTempList() as $k=>$v) {
            $data[$k]['temp_id'] = $v->getTempId();
            $data[$k]['name'] = $v->getName();
            $data[$k]['method'] = $v->getMethod();
            $data[$k]['cost'] = $v->getCost();
            $data[$k]['use_count'] = $v->getUseCount();
            $data[$k]['is_default'] = $v->getIsDefault();
            $data[$k]['is_close'] = $v->getIsClose();
            $data[$k]['create_time'] = $v->getCreateDate()->getSeconds();
            $data[$k]['config'] = $v->getConfig()?unserialize($v->getConfig()):'';
            $data[$k]['provider_id'] = $v->getProviderId();
            $data[$k]['store_name'] = $v->getStoreName();
            $data[$k]['area'] = $v->getAreas();
            foreach (explode(',',$data[$k]['area']) as $kk=>$vv){
                foreach ($area as $k2 => $v2){
                    if($vv.'0000' == $k2){
                        $data[$k]['areas'].=$v2.',';
                    }
                }
            }

        }

        $count=$res->getPaginationResult()->getTotalRecords();
        if($p == 1){
            adminOperateLog('物流模板列表',2);
        }


        $Page  = new AjaxPage($count,20);
        $show = $Page->show();
        //$plugin_list = M('plugin')->select();
        //$plugin_list = group_same_key($plugin_list,'type');
        //$this->assign('payment',$plugin_list['payment']);
        //$this->assign('login',$plugin_list['login']);
        $this->assign('shipping',$data);
        $this->assign('page',$show);
        //$this->assign('function',$plugin_list['function']);
        $this->assign('type',I('type'));
        return $this->fetch();
    }

    /**
     * 插件安装卸载
     */
//    public function install(){
//        $condition['type'] = I('get.type');
//        $condition['code'] = I('get.code');
//        $update['status'] = I('get.install');
//        $model = M('plugin');
//
//        //如果是功能插件
//        if($condition['type'] == 'function')
//        {
//            include_once  "plugins/function/{$condition['code']}/plugins.class.php";
//            $plugin = new \plugins();
//            if($update['status'] == 1) // 安装
//            {
//                $execute_sql = $plugin->install_sql(); // 执行安装sql 语句
//                $info = $plugin->install();  // 执行 插件安装代码
//            }
//            else // 卸载
//            {
//                $execute_sql = $plugin->uninstall_sql(); // 执行卸载sql 语句
//                $info = $plugin->uninstall(); // 执行插件卸载代码
//            }
//            // 如果安装卸载 有误则不再往下 执行
//            if($info['status'] === 0)
//                exit(json_encode($info));
//            // 程序安装没错了, 再执行 sql
//            DB::execute($execute_sql);
//        }
//        //如果是物流插件，物流卸载先判断是否有订单使用该物流公司插件
//        if($condition['type'] == 'shipping' && $update['status'] == 0){
//            $order_shipping = M('order')->where(array('shipping_code' => $condition['code']))->count();
//            if ($order_shipping > 0) {
//                $res = array('status' => 0, 'msg' => '已有订单使用该物流公司，不能卸载该物流插件');
//                exit(json_encode($res));
//            }
//        }
//        //卸载插件时 删除配置信息
//        if($update['status']==0){
//            $row = DB::name('plugin')->where($condition)->delete();
//        }else{
//            $row = $model->where($condition)->save($update);
//        }
////        $row = $model->where($condition)->save($update);
//        //安装时更新配置信息(读取最新的配置)
//        if($condition['type'] == 'payment' && $update['status']){
//            $file = PLUGIN_PATH.$condition['type'].'/'.$condition['code'].'/config.php';
//            $config = include $file;
//            $add['bank_code'] = serialize($config['bank_code']);
//            $add['config'] = serialize($config['config']);
//            $add['config_value'] = '';
//            $model->where($condition)->save($add);
//        }
//
//        if($row){
//            //如果是物流插件 记录一条默认信息
//            if($condition['type'] == 'shipping'){
//                $config['first_weight'] = '1000'; // 首重
//                $config['second_weight'] = '2000'; // 续重
//                $config['money'] = '12';
//                $config['add_money'] = '2';
//                $add['shipping_area_name'] ='全国其他地区';
//                $add['shipping_code'] =$condition['code'];
//                $add['config'] =serialize($config);
//                $add['is_default'] =1;
//                if($update['status']){
//                    M('shipping_area')->add($add);
//                }else{
//                    M('shipping_area')->where(array('shipping_code'=>$condition['code']))->delete();
//                }
//            }
//            $info['status'] = 1;
//            $info['msg'] = $update['status'] ? '安装成功!' : '卸载成功!';
//        }else{
//            $info['status'] = 0;
//            $info['msg'] = $update['status'] ? '安装失败' : '卸载失败';
//        }
//        exit(json_encode($info));
//    }


    /**
     * 插件目录扫描
     * @return array 返回目录数组
     */
//    private function scanPlugin(){
//        $plugin_list = array();
//        $plugin_list['payment'] = $this->dirscan(C('PAYMENT_PLUGIN_PATH'));
//        $plugin_list['login'] = $this->dirscan(C('LOGIN_PLUGIN_PATH'));
//        $plugin_list['shipping'] = $this->dirscan(C('SHIPPING_PLUGIN_PATH'));
//        $plugin_list['function'] = $this->dirscan(C('FUNCTION_PLUGIN_PATH'));
//
//        foreach($plugin_list as $k=>$v){
//            foreach($v as $k2=>$v2){
//
//                if(!file_exists(PLUGIN_PATH.$k.'/'.$v2.'/config.php'))
//                    unset($plugin_list[$k][$k2]);
//                else
//                {
//                    $plugin_list[$k][$v2] = include(PLUGIN_PATH.$k.'/'.$v2.'/config.php');
//                    unset($plugin_list[$k][$k2]);
//                }
//            }
//        }
//        return $plugin_list;
//    }

    /**
     * 获取插件目录列表
     * @param $dir
     * @return array
     */
//    private function dirscan($dir){
//        $dirArray = array();
//        if (false != ($handle = opendir ( $dir ))) {
//            $i=0;
//            while ( false !== ($file = readdir ( $handle )) ) {
//                //去掉"“.”、“..”以及带“.xxx”后缀的文件
//                if ($file != "." && $file != ".."&&!strpos($file,".")) {
//                    $dirArray[$i]=$file;
//                    $i++;
//                }
//            }
//            //关闭句柄
//            closedir ( $handle );
//        }
//        return $dirArray;
//    }

    /**
     * 更新插件到数据库
     * @param $plugin_list 本地插件数组
     */
//    private function insertPlugin($plugin_list){
//        $d_list =  M('plugin')->field('code,type')->select(); // 数据库
//
//        $new_arr = array(); // 本地
//        //插件类型
//        foreach($plugin_list as $pt=>$pv){
//            //  本地对比数据库
//            foreach($pv as $t=>$v){
//                $tmp['code'] = $v['code'];
//                $tmp['type'] = $pt;
//                $new_arr[] = $tmp;
//                // 对比数据库 本地有 数据库没有
//                $is_exit = M('plugin')->where(array('type'=>$pt,'code'=>$v['code']))->find();
//                if(empty($is_exit)){
//                    $add['code'] = $v['code'];
//                    $add['name'] = $v['name'];
//                    $add['version'] = $v['version'];
//                    $add['icon'] = $v['icon'];
//                    $add['author'] = $v['author'];
//                    $add['desc'] = $v['desc'];
//                    $add['bank_code'] = serialize($v['bank_code']);
//                    $add['type'] = $pt;
//                    $add['scene'] = $v['scene'];
//                    $add['config'] = empty($v['config']) ? '' : serialize($v['config']);
//                    M('plugin')->add($add);
//                }
//            }
//
//        }
//        //数据库有 本地没有
//        foreach($d_list as $k=>$v){
//            if(!in_array($v,$new_arr)){
//                M('plugin')->where($v)->delete();
//            }
//        }
//
//    }

    /*
     * 插件信息配置
     */
//    public function setting(){
//
//        $condition['type'] = I('get.type');
//        $condition['code'] = I('get.code');
//        $model = M('plugin');
//        $row = $model->where($condition)->find();
//        if(!$row){
//            exit($this->error("不存在该插件"));
//        }
//
//        $row['config'] = unserialize($row['config']);
//
//        if(IS_POST){
//            $config = I('post.config/a');
//            //空格过滤
//            $config = trim_array_element($config);
//            if($config){
//                $config = serialize($config);
//            }
//            $row = $model->where($condition)->save(array('config_value'=>$config));
//            if($row){
//                exit($this->success("操作成功"));
//            }
//            exit($this->error("操作失败"));
//        }
//
//        $this->assign('plugin',$row);
//        $this->assign('config_value',unserialize($row['config_value']));
//
//        return $this->fetch();
//    }

    /*
     * 物流配送列表
//     */
//    public function shipping_list(){
//        $row = $this->checkExist();
//        $sql = "SELECT a.is_default,a.shipping_area_name,a.shipping_area_id AS shipping_area_id,".
//            "(SELECT GROUP_CONCAT(c.name SEPARATOR ',') FROM __PREFIX__area_region b LEFT JOIN __PREFIX__region c ON c.id = b.region_id WHERE b.shipping_area_id = a.shipping_area_id) AS region_list ".
//            "FROM __PREFIX__shipping_area a WHERE shipping_code = '{$row['code']}'";
//        //2016-01-11 获取插件信息
//        $shipping_info = M('plugin')->where(array('code'=>$row['code'],'type'=>'shipping'))->find();
//        $result = DB::query($sql);
//        //获取配送名称
//        $this->assign('plugin',$row);
//        $this->assign('shipping_list',$result);
//        $this->assign('shipping_info',$shipping_info);
//        return $this->fetch();
//    }
    /*
     * 物流描述信息
     */
//    public function shipping_desc(){
//        $desc = I('post.desc');
//        $code = I('post.code');
//        $row = M('plugin')->where(array('code'=>$code,'type'=>'shipping'))->save(array('desc'=>$desc));
//        if(!$row)
//            exit(json_encode(array('status'=>0)));
//        exit(json_encode(array('status'=>1)));
//    }

    /**
     * 物流信息打印
     */
    public function shipping_print(){
        if(IS_POST){
            $config = array(
                'background'=>I('background'),
                'width'=>I('width'),'height'=>I('height'),
                'offset_x'=>I('offset_x'),'offset_y'=>I('offset_y')
            );
            $data['config'] = serialize($config);
            $data['config_value'] = I('content');
            $code = I('code');
            //$r = M('plugin')->where(array('code'=>$code,'type'=>'shipping'))->save($data);
            /*if($r !== false){
                $this->success('编辑成功',U('Plugin/index')); exit;
            }else{
                $this->success('编辑失败',U('Plugin/index'));
            }*/
        }
        //$shipping = $this->checkExist();
        if(empty($shipping['config'])){
            $config = array('width'=>840,'height'=>480,'offset_x'=>0,'offset_y'=>0);
            $this->assign('config',$config);
        }else{
            $this->assign('config',unserialize($shipping['config']));
        }
        $this->assign('plugin',$shipping);
        return $this->fetch("shipping_print");

    }

    //配送区域编辑
    public function shipping_list_edit(){
        //$shipping = $this->checkExist();
        if(IS_POST){
            $area_list = I('area_list/a');

            $type = I('type');
            if($type=='part'){
                $area_list = array_map(function($v){
                    return substr($v,0,2);
                },$area_list);
                $area_list = implode(',',$area_list);
            }

            $id = I('id/d');
            $config = serialize(I('post.config/a'));

            //$cost = I('shipping_price/d');
            $is_default = I('is_default/d');
            $is_close = I('is_close/d');
            $client = GRPC(Itm);
            $update = new Psp\Itm\UpdateTemp();
            $update->setTempId($id);
            //$update->setCost($cost);
            $update->setAreas($area_list);
            $update->setIsDefault($is_default);
            $update->setIsClose($is_close);
            $update->setConfig($config);
            list($res,$status) = $client->UpdateTempInfo($update)->wait();
            adminOperateLog('配送区域编辑',2);
            if(!$res->getValue()){
                exit($this->error("配置失败",U('Admin/Plugin/index',array('type'=>'shipping'))));
            }
            exit($this->success("配置成功",U('Admin/Plugin/index',array('type'=>'shipping'))));
        }

        $shipping_area_id = I('get.id');

        //根据模板id获取模板详情
        $client = GRPC(Itm);
        $temp_id = new Psp\Itm\TempId();
        $temp_id->setTempId($shipping_area_id);
        list($res,$status) = $client->GetTempDetail($temp_id)->wait();
        $data['name'] = $res->getName();
        $data['cost'] = $res->getCost();
        $data['areas'] = $res->getAreas();
        $data['is_default'] = $res->getIsDefault();
        $data['is_close'] = $res->getIsClose();
        $data['config'] = $res->getConfig()?unserialize($res->getConfig()):array('first_weight'=>1000,'money'=>'','second_weight'=>1000,'add_money'=>'');
        $data['areas']&&$data['areas'] = array_map(function($v){
            return $v.'0000';
        },explode(',',$data['areas']));
        //$data['areas'] = implode(',',$data['areas']);
        //dump($data);die;
        $areas = new \area\area();
        $area = $areas->getProv(); //获取所有省
        $this->assign('id',I('id'));
        $this->assign('areas',$area);
        $this->assign('temp',$data);
        //$this->assign('province',$province);
        //$this->assign('plugin',$shipping);

        if(I('get.default') == 1){
            //默认配置
            return $this->fetch('shipping_list_default');
        }else{
            return $this->fetch();
        }
    }

    /**
     * 删除配送区域
     */
//    public function del_area(){
//        $shipping = $this->checkExist();
//        $shipping_area_id = I('get.id');
//        $row = M('shipping_area')->where(array('shipping_area_id'=>$shipping_area_id))->delete(); // 删除配送地区表信息
//        if($row){
//            M('area_region')->where(array('shipping_area_id'=>$shipping_area_id))->delete();
//            $this->success("删除成功");
//        }else{
//            $this->error("删除失败");
//        }
//
//    }

    /**
     * 检查插件是否存在
     * @return mixed
     */
    private function checkExist(){
        $condition['type'] = I('get.type');
        $condition['code'] = I('get.code');

        //$model = M('plugin');
        /*$row = $model->where($condition)->find();
        if(!$row && false){
            exit($this->error("不存在该插件"));
        }
        return $row;*/
    }

    /**
     * 添加物流插件
     */
    public function add_shipping()
    {

        if (IS_GET) {
            return $this->fetch('_shipping');
            exit;
        }

        $code = I('code'); // 编码
        $name = I('name'); // 物流名字
        $img_url = I('original_img'); //图片地址
        $payload =validate_json_web_token(cookie('_authtoken'));
        $org_id =$payload['org_id']?$payload['org_id']:0;//取出org_id
//        $client = new Psp\Item\ItemServiceClient('192.168.1.116:9300', [
//            'credentials' => Grpc\ChannelCredentials::createInsecure()
//        ]);
        $client = GRPC(Itm);
        $temp = new Psp\Itm\DeliveryTemp();
        $temp->setCreatorId($org_id);
        $temp->setName($name);
        $temp->setMethod($code);
        $id&&$temp->setProviderId($id);
        list($res,$status) = $client->AddDeliveryTemp($temp)->wait();
        //var_dump($res);die;
        adminOperateLog('添加物流插件',2);
        if($res->getRet()=='ok'){
            $this->success("添加成功", U("Plugin/index"));
        }else{
            $this->error($res->getMsg(), U("Plugin/index"));
        }


//        $code = I('code'); // 编码
//        $code = strtolower($code);
//        $name = I('name'); // 物流名字
//        $desc = I('desc', '');// 插件描述
//        $dir = "./plugins/shipping/$code";
//
//        if (!preg_match("/[a-zA-Z]{2,20}/", $code))
//            $this->error("物流编码必须 2-20位小写字母组成");
//        $shipping = M('plugin')->where("code", $code)->find();
//        $shipping && $this->error("编码 $code 已存在");
//
//        if (!file_exists($dir))
//            mkdir($dir);
//        // icon图片
//        if ($_FILES['shipping_img']['tmp_name']) {
//            $upload = $this->request->file('shipping_img');
//            $info = $upload->rule('logo.jpg')->validate(['size'=>1024 * 1024 * 3,'ext'=>'jpg,png,gif,jpeg'])->move($dir.'/','logo.jpg');
//            if ($info) {// 上传错误提示错误信息
//                $file_name = $info->getFilename();
//                $old_name = $dir.'/'.$file_name;
//                $new_name = $dir . '/logo.jpg';
//                //rename($old_name, $new_name);
//            } else {
//                $this->error($upload->getError());
//            }
//        } else {
//            $this->error("物流图片图标必传");
//        }
//        $config_html = "<?php
//                        return array(
//                            'code'=> '$code',
//                            'name' => '$name',
//                            'version' => '1.0',
//                            'author' => '管理员',
//                            'desc' => '$desc ',
//                            'icon' => 'logo.jpg',
//                        );";
//        file_put_contents(PLUGIN_PATH . "shipping/$code/config.php", $config_html);
//        $this->success("添加成功", U("Plugin/index"));

    }

    /**
     * 删除物流
     */
    public function del_shipping($code){
        $client = GRPC(Itm);
        $temp = new Psp\Itm\TempId();
        $temp->setTempId($code);
        list($res,$status) = $client->DeleteTemp($temp)->wait();
        if($res->getValue()){
            adminOperateLog('删除物流插件',2);
            exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
        }

    }

}
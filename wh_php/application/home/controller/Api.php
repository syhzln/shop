<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\controller;
use app\home\logic\UsersLogic;
use think\Db;
use think\Session;
use think\Controller;
use think\Verify;
use think\Cookie;
use Psp;
use Grpc;

class Api extends Base {
    public  $send_scene;

    public function _initialize() {
        parent::_initialize();
    }
    /*
     * 获取地区
     */
    public function getRegion(){
        $parent_id = I('get.parent_id/d');
        $selected = I('get.selected',0);
        $areamap= new \area\area();
        $data = $areamap->getRegion($parent_id);
        $html = '';
        if($data){
            foreach($data as $h){
                if($h['id'] == $selected){
                    $html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
                }
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }

    /**
     * getCity js联动菜单获取城市
     * Author: Ning
     */
    public function getCity()
    {
        $code = I('code');
        $areamap= new \area\area();
        $data = $areamap->getCity($code);
        $html = '';
        if($data){
            foreach($data as$k=>$v){
                $html .= "<option value='{$k}'>{$v}</option>";
            }
        }
        echo $html;

    }


    public function getArea(){
        $code = I('code');
        $areamap= new \area\area();
        $data = $areamap->getRegion($code);
        $html = '';
        if($data){
            foreach($data as$k=>$v){
                $html .= "<option value='{$k}'>{$v}</option>";
            }
        }
        echo $html;
    }

//    public function getTwon(){
//        $parent_id = I('get.parent_id/d');
//        $data = M('region')->where("parent_id",$parent_id)->select();
//        $html = '';
//        if($data){
//            foreach($data as $h){
//                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
//            }
//        }
//        if(empty($html)){
//            echo '0';
//        }else{
//            echo $html;
//        }
//    }

    /**
     * 获取省
     */
    public function getProvince()
    {

        $areamap= new \area\area();
        $p = $areamap->getProv();
        foreach ($p as $k=>$v){
            $arr['id'] =$k;
            $arr['name'] =$v;
            $newArr[] =$arr;
        }
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $newArr);
        exit(json_encode($res));
    }

    /**
     * 获取市或者区
     */
    public function getRegionByParentId()
    {
        $parent_id = input('parent_id');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
//            $region_list = Db::name('region')->field('id,name')->where(['parent_id'=>$parent_id])->select();
            $areamap= new \area\area();
            $c = $areamap->getCity($parent_id);
            foreach ($c as $k=>$v){
                $arr['id'] =$k;
                $arr['name'] =$v;
                $newArr[] =$arr;
            }
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $newArr);
        }
        exit(json_encode($res));
    }
    /**
     * 获取市或者区
     */
    public function getDistrictParentId()
    {
        $parent_id = input('parent_id');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
//            $region_list = Db::name('region')->field('id,name')->where(['parent_id'=>$parent_id])->select();
            $areamap= new \area\area();
            $c = $areamap->getRegion($parent_id);
            foreach ($c as $k=>$v){
                $arr['id'] =$k;
                $arr['name'] =$v;
                $newArr[] =$arr;
            }
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $newArr);
        }
        exit(json_encode($res));
    }


    /*
     * 获取地区
     */
    public function get_category(){

        if(I('get.parent_id/d') !=0){
            $id = I('get.parent_id/d'); // 商品分类 父id
            $request = new Psp\Itm\GetCategoryByParentIdRequest();
            $request->setPlatform(PLATFORM);
            $request->setLanguage(1);
            $request->setParentId($id);

            //todo 接口未通
            $data = [];
            list($reply) = GRPC('itm')->getCategoryByParentId($request)->wait();

            if($reply){
                foreach ($reply->getCategorys() as $k => $v){
                    $data[$k]['id'] = $v->getId();
                    $data[$k]['name']= $v->getName();
                }
            }

            if (!empty($data)) {
                foreach($data as $k => $v)
                    $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
                exit($html);
            }
        }
    }
    //获取三级分类
    public function get_category_three(){
        if(I('get.parent_id/d') !=0){
            $parent_id = I('get.parent_id/d'); // 商品分类 父id
            $level2 = new Psp\Item\Level2Id();
            $level2->setId($parent_id);
            list($res,$status) = GRPC('cat')->GetLevel3Id($level2)->wait();
            if ($res) {
                foreach ($res->getLevel3Id() as $k=>$v) {
                    $arr[$k]['id'] = $v->getId();
                    $arr[$k]['name'] = $v->getName();
                }
            }
            if (!empty($arr)) {
                foreach($arr as $k => $v)
                    $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
                exit($html);
            }
        }
    }

    //获取二级分类
    public function get_storeCategory()
    {
        $storeInfo = new Psp\Store\ApplyId();
        $cate_id = I('get.parent_id/d', 0);//分类id

        $storeInfo->setApplyId(0);
        $storeInfo->setParentId($cate_id);
        $storeInfo->setLanguage(1);
        $storeInfo->setPlatform(1);
        $storeInfo->setLevel(2);
        list($result, $status) = GRPC('sellerstore')->GetBindClassOneInfo($storeInfo)->wait();
        foreach ($result->getBindClass() as $k => $v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['category_id'] = $v->getCategoryId();
            $arr[$k]['category_name'] = $v->getCategoryName();
            $arr[$k]['parent_id'] = $v->getParentId();
            $arr[$k]['level'] = $v->getLevel();
        }

        foreach ($arr as $k => $v)
            $html .= "<option value='{$v['category_id']}'>{$v['category_name']}</option>";
        exit($html);
    }


    //获取三级分类
    public function get_storeCategoryThree(){
        $storeInfo = new Psp\Store\ApplyId();
        $cate_id =I('get.parent_id/d',0);//分类id
        $storeInfo->setApplyId(0);
        $storeInfo->setParentId($cate_id);
        $storeInfo->setLanguage(1);
        $storeInfo->setPlatform(1);
        $storeInfo->setLevel(3);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassOneInfo($storeInfo)->wait();
        foreach ($result->getBindClass() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['category_id'] = $v->getCategoryId();
            $arr[$k]['category_name'] = $v->getCategoryName();
            $arr[$k]['parent_id'] = $v->getParentId();
            $arr[$k]['level'] = $v->getLevel();
        }
        foreach($arr as $k => $v)
            $html .= "<option value='{$v['category_id']}'>{$v['category_name']}</option>";
        exit($html);
    }

    /*商家入驻获取三级*/
    public function get_cates(){
        $storeInfo = new Psp\Store\ApplyId();
        $cate_id =I('get.parent_id/d',0);//分类id
        $storeInfo->setApplyId(0);
        $storeInfo->setParentId($cate_id);
        $storeInfo->setLanguage(1);
        $storeInfo->setPlatform(1);
        $storeInfo->setLevel(3);
        list($result,$status) = GRPC('sellerstore') ->GetBindClassOneInfo($storeInfo)->wait();
        foreach ($result->getBindClass() as $k=>$v) {
            $arr[$k]['apply_id'] = $v->getApplyId();
            $arr[$k]['category_id'] = $v->getCategoryId();
            $arr[$k]['category_name'] = $v->getCategoryName();
            $arr[$k]['parent_id'] = $v->getParentId();
            $arr[$k]['level'] = $v->getLevel();
        }
        foreach($arr as $k => $v)
        {
            $html .= "<input type='checkbox' name='subcate[]' data-name='{$v
['category_name']}' value='{$v['category_id']}'>".$v['category_name'];
        }
        exit($html);
    }


    /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     */
    public function send_validate_code(){

        $this->send_scene = C('SEND_SCENE');

        $type = I('type');
        $scene = I('scene');    //发送短信验证码使用场景
        $mobile = I('mobile');
        $sender = I('send');
        $verify_code = I('verify_code');
        $mobile = !empty($mobile) ?  $mobile : $sender ;
        $session_id = I('unique_id' , session_id());
        cookie("scene",$scene);

        //注册
        if($scene == 1 && !empty($verify_code)){
            $verify = new Verify();
            if (!$verify->check($verify_code, 'user_reg')) {
                ajaxReturn(array('status'=>-1,'msg'=>'图像验证码错误'));
            }
        }
        if($type == 'email'){
            //发送邮件验证码
            $logic = new UsersLogic();
            $res = $logic->send_email_code($sender);
            ajaxReturn($res);
        }else{
            //发送短信验证码
            $res = checkEnableSendSms($scene);
            if($res['status'] != 1){
                ajaxReturn($res);
            }
            //获取设备号
            $device_code = getEquipmentSystem();
            //判断是否存在验证码
            $code = new Psp\Member\SmsSendStatus();
            $code->setMobile($mobile);
            $code->setStatus(1);
            $code->setDeviceCode($device_code);
            list($reply,$status) = GRPC('member')->GetSmsSendStatus($code)->wait();
            $code = $reply->getCode();
            $data['add_time'] = empty($reply->getSendTime()) ? '' : $reply->getSendTime()->getSeconds();
            //获取时间配置
            $sms_time_out = tpCache('sms.sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 120;
            //120秒以内不可重复发送
            if($code && (time() - $data['add_time']) < $sms_time_out){
                $return_arr = array('status'=>-1,'msg'=>$sms_time_out.'秒内不允许重复发送');
                ajaxReturn($return_arr);
            }
            //随机一个验证码
            $code = rand(100000, 999999);

            $user = validate_json_web_token(cookie('token'));
            if ($scene == 6){

                if(!$user['user_id']){
                    //登录超时
                    ajaxReturn(array('status'=>-1,'msg'=>'登录超时'));
                }
                $params = array('code'=>$code);

                if($user['nickname']){
                    $params['user_name'] = $user['nickname'];
                }
            }
            $params['code'] =$code;
            //发送短信
            $resp = sendSms($scene , $mobile , $params, $session_id);


            if($resp['status'] == 1){

                //发送成功, 修改发送状态位成功
//                M('sms_log')->where(array('mobile'=>$mobile,'code'=>$code,'session_id'=>$session_id , 'status' => 0))->save(array('status' => 1));
                $return_arr = array('status'=>1,'msg'=>'发送成功,请注意查收');
            }else{
                $return_arr = array('status'=>-1,'msg'=>'发送失败'.$resp['msg']);
            }
            ajaxReturn($return_arr);
        }
    }

    /**
     * 验证短信验证码: APP/WAP/PC 共用发送方法
     */
    public function check_validate_code(){

        $code = I('post.code');
        $mobile = I('mobile');
        $send = I('send');
        $sender = empty($mobile) ? $send : $mobile;
        $type = I('type');
        $session_id = I('unique_id', session_id());
        $scene = I('scene', -1);
        $logic = new UsersLogic();
        $res = $logic->check_validate_code($code, $sender, $type ,$session_id, $scene);
        ajaxReturn($res);
    }

    /**
     * 检测手机号是否已经存在
     */
    public function issetMobile()
    {
//        $mobile = I("mobile",'0');
//        $users = M('users')->where('mobile',$mobile)->find();
//        if($users)
//            exit ('1');
//        else
//            exit ('0');
    }

    public function issetMobileOrEmail()
    {
//        $mobile = I("mobile",'0');
//        $users = M('users')->where("email",$mobile)->whereOr('mobile',$mobile)->find();
//        if($users)
//            exit ('1');
//        else
//            exit ('0');
    }
    /**
     * 查询物流
     */
    public function queryExpress()
    {
        $shipping_code = input('shipping_code');
        $invoice_no = input('invoice_no');
        if(empty($shipping_code) || empty($invoice_no)){
            return json(['status'=>0,'message'=>'参数有误','result'=>'']);
        }
        return json(queryExpress($shipping_code,$invoice_no));
    }

    /**
     * 检查订单状态
     */
    public function check_order_pay_status()
    {
        $order_id = I('order_id/d');
        if(empty($order_id)){
            $res = ['message'=>'参数错误','status'=>-1,'result'=>''];
            $this->AjaxReturn($res);
        }
//        $order = M('order')->field('pay_status')->where(['order_id'=>$order_id])->find();
//        if($order['pay_status'] != 0){
//            $res = ['message'=>'已支付','status'=>1,'result'=>$order];
//        }else{
//            $res = ['message'=>'未支付','status'=>0,'result'=>$order];
//        }
//        $this->AjaxReturn($res);
    }

    /**
     * 广告位js
     */
    public function ad_show()
    {
        $pid = I('pid/d',1);
        $where = array(
            'pid'=>$pid,
            'enable'=>1,
            'start_time'=>array('lt',strtotime(date('Y-m-d H:00:00'))),
            'end_time'=>array('gt',strtotime(date('Y-m-d H:00:00'))),
        );
//        $ad = D("ad")->where($where)->order("orderby desc")->cache(true,WALHAO_CACHE_TIME)->find();
//        $this->assign('ad',$ad);
        return $this->fetch();
    }
    /**
     *  搜索关键字
     * @return array
     */
    public function searchKey(){
        $searchKey = input('key');
//        $searchKeyList = Db::name('search_word')
//            ->where('keywords','like',$searchKey.'%')
//            ->whereOr('pinyin_full','like',$searchKey.'%')
//            ->whereOr('pinyin_simple','like',$searchKey.'%')
//            ->limit(10)
//            ->select();
//        if($searchKeyList){
//            return ['status'=>1,'msg'=>'搜索成功','result'=>$searchKeyList];
//        }else{
//            return ['status'=>0,'msg'=>'没记录','result'=>$searchKeyList];
//        }
    }

    /**
     * 根据ip设置获取的地区来设置地区缓存
     */
    public function doCookieArea()
    {
//        $ip = '183.147.30.238';//测试ip
        $address = input('address/a',[]);
        if(empty($address) || empty($address['province'])){
            $this->setCookieArea();
            return;
        }
//        $province_id = Db::name('region')->where(['level' => 1, 'name' => ['like', '%' . $address['province'] . '%']])->limit('1')->value('id');
//        if(empty($province_id)){
//            $this->setCookieArea();
//            return;
//        }
//        if (empty($address['city'])) {
//            $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id])->limit('1')->order('id')->value('id');
//        } else {
//            $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id, 'name' => ['like', '%' . $address['city'] . '%']])->limit('1')->value('id');
//        }
//        if (empty($address['district'])) {
//            $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id])->limit('1')->order('id')->value('id');
//        } else {
//            $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id, 'name' => ['like', '%' . $address['district'] . '%']])->limit('1')->value('id');
//        }
//        $this->setCookieArea($province_id, $city_id, $district_id);
    }

    /**
     * 设置地区缓存
     * @param $province_id
     * @param $city_id
     * @param $district_id
     */
    private function setCookieArea($province_id = 1, $city_id = 2, $district_id = 3)
    {
        Cookie::set('province_id', $province_id);
        Cookie::set('city_id', $city_id);
        Cookie::set('district_id', $district_id);
    }

    /**
    *实名认证
    * @param $url
     **/
    public function authentication($path_url){

        $url = "https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json";
        $appcode = "804444202f9543c89691871683fcbe73";
        $file = file_get_contents($path_url); //读取图片
        //如果输入带有inputs, 设置为True，否则设为False
        $is_old_format = true;
        //如果没有configure字段，config设为空
        $config = array(
            "side" => "face"
        );
        $base64 = base64_encode($file);
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $querys = "";
        if($is_old_format == TRUE){
            $request = array();
            $request["image"] = array(
                "dataType" => 50,
                "dataValue" => "$base64"
            );

            if(count($config) > 0){
                $request["configure"] = array(
                    "dataType" => 50,
                    "dataValue" => json_encode($config)
                );
            }
            $body = json_encode(array("inputs" => array($request)));
        }else{
            $request = array(
                "image" => "$base64"
            );
            if(count($config) > 0){
                $request["configure"] = json_encode($config);
            }
            $body = json_encode($request);
        }
        $method = "POST";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $rheader = substr($result, 0, $header_size);
        $rbody = substr($result, $header_size);

        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($httpCode == 200){
            if($is_old_format){
                $output = json_decode($rbody, true);
                $result_str = $output["outputs"][0]["outputValue"]["dataValue"];
            }else{
                $result_str = $rbody;
            }

            $data = $result_str;
            exit($data); //json object
        }else{

            $data =array('code'=>$httpCode,'msg'=>$rbody,'header'=>$rheader);
            exit(json_encode($data));

        }
    }


    /**
    *扫码 获取推荐人id
     *@param $tps138_id 推荐人id
     */
    public function qr_code($tps138_id ='1383891023')
    {
        vendor('phpqrcode.phpqrcode');
        $value = SITE_URL.'/mobile/User/reg&key='.$tps138_id;  //二维码内容
        var_dump($value);die;
        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 6;           //生成图片大小
        $user_id =1;
        //生成二维码图片
        $filename = './public/images/qrcode/qrcode.png'; //定义文件名 生成路径
        \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = './public/images/logo.png';  //准备好的logo图片
        $QR = $filename;   //已经生成的原始二维码图
        if (file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($QR));        //目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));    //源图象连接资源。
            $QR_width = imagesx($QR);           //二维码图片宽度
            $QR_height = imagesy($QR);          //二维码图片高度
            $logo_width = imagesx($logo);       //logo图片宽度
            $logo_height = imagesy($logo);      //logo图片高度
            $logo_qr_width = $QR_width / 4;     //组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width;    //logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale;  //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点

            //重新组合图片并调整大小
            /*
             *  imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
             */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
        }

        //输出图片
        imagepng($QR, './public/images/qrcode/'.$tps138_id.'.png');
        imagedestroy($QR);
        imagedestroy($logo);
//        $path =SITE_URL.'/public/images/qrcode/'.$tps138_id.'.png';
//        return $path;
    }

    /**
     * 快递查询,返回到html页面
     * @Authorhtl {Ning<nk11@qq.com>}
     * @DateTime  2017-03-27T10:30:06+0800
     * @return   str
     */
    public function kuaidicx(){

        $nu = I('nu');

        preg_match('/\d+/',$nu,$num);
        $code = $num[0];
        $info = kdcx($code);
        if (!$info['data'])exit($info['message']);
        $str = "<h5>快递单号:$code</h5>";
        foreach(array_reverse($info['data']) as $k=>$v){
            $str .= "<p style='font-size:14px'>".$v['time'].'  '.$v['context'].'</p>';
        }

        echo $str;
    }

}
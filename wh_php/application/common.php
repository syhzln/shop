<?php
/**
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * $Author: fzq 2017-09-3
 * 为兼容以前的Thinkphp3.2老用户习惯, 用TP5助手函数实现 M( ) D( ) U( ) S( )等单字母函数
 */
use think\Db;


/**
 * 检验登陆
 * @param
 * @return bool
 */
function is_login(){
    if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0){
        return $_SESSION['admin_id'];
    }else{
        return false;
    }
}
/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0)
        $map['user_id'] = $user_id_or_name;
    if($type == 1)
        $map['email'] = $user_id_or_name;
    if($type == 2)
        $map['mobile'] = $user_id_or_name;
    if($type == 3){
        $map['openid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    if($type == 4){
        $map['unionid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    $user_id=implode('',$map);

    $var = new Psp\Store\UserId();
    $var->setUserId($user_id);
    list($res, $status) = GRPC('sellerstore')->GetUserMsgInfo($var)->wait();
    $user['shop_name']=$res->getShopName();
    $user['prov_name']=$res->getProvName();
    $user['location_full']=$res->getLocationFull();
    $user['phone']=$res->getPhone();
    $user['fax']=$res->getFax();
    $user['apply_id']=$res->getApplyId();
    $user['email']=$res->getEmail();
    $user['license_no']=$res->getLicenseNo();
    $user['enterprise_name']=$res->getEnterpriseName();
    $user['company_type']=$res->getCompanyType();
    $user['tax_no']=$res->getTaxNo();
    $user['org_no']=$res->getOrgNo();
    $user['scope']=$res->getScope();
    $user['residence']=$res->getResidence();
    $user['legal_presentative']=$res->getLegalPresentative();
    $user['license_img_url']=$res->getLicenseImgUrl();
    $user['tax_img_url']=$res->getTaxImgUrl();
    $user['org_img_url']=$res->getOrgImgUrl();
    $user['present_front_img_url']=$res->getPresentFrontImgUrl();
    $user['bank_account_name']=$res->getBankAccountName();
    $user['bank_account_number']=$res->getBankAccountNumber();
    $user['bank_branch_name']=$res->getBankBranchName();
    $user['user_phone']=$res->getUserPhone();
    $user['user_email']=$res->getUserEmail();
    $user['apply_type']=$res->getApplyType();
    $user['user_id']=$res->getUserId();
    $user['login_username']=$res->getLoginUsername();
    $user['login_secret']=$res->getLoginSecret();
    $user['qq']=$res->getQq();
    $user['apply_state']=$res->getApplyState();

    return $user;
}

/**
 * 获取导航栏
 *
 */
function get_navigation($lang,$platform){
    $navigation = new Psp\Newhome\IsShow();
    $navigation->setIsShow(true);
    $navigation->setLanguage($lang);
    $navigation->setPlatform($platform);
    list($res, $status) = GRPC('NewHome')->GetNavigation($navigation)->wait();
    if ($res){
        foreach ($res->getNavigation() as $k => $v) {
            $navigation_list[$k]['name'] = $v->getName();
            $navigation_list[$k]['url'] = $v->getUrl();
            $navigation_list[$k]['is_new'] = $v->getIsNew();
        }
    }
    return $navigation_list;
}


/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
//function update_user_level($user_id){
//    $level_info = M('user_level')->order('level_id')->select();
//    $total_amount = M('order')->master()->where("user_id=:user_id AND pay_status=1 and order_status not in (3,5)")->bind(['user_id'=>$user_id])->sum('order_amount+user_money');
//    if($level_info){
//        foreach($level_info as $k=>$v){
//            if($total_amount >= $v['amount']){
//                $level = $level_info[$k]['level_id'];
//                $discount = $level_info[$k]['discount']/100;
//            }
//        }
//        $user = session('user');
//        $updata['total_amount'] = $total_amount;//更新累计修复额度
//        //累计额度达到新等级，更新会员折扣
//        if(isset($level) && $level>$user['level']){
//            $updata['level'] = $level;
//            $updata['discount'] = $discount;
//        }
//        M('users')->where("user_id", $user_id)->save($updata);
//    }
//}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */

function goods_thum_images($goods_id,$width, $height)
{
    if (empty($goods_id)) return '';

    //判断缩略图是否存在
    $path = "public/upload/goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_thumb_{$goods_id}_{$width}_{$height}";

    // 这个商品 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';


    $goods = new Psp\Newhome\GoodsId;
    $goods->setItemId($goods_id);
    list($res,$status) = GRPC('NewHome')->GetGoodsThumImages($goods)->wait();
    if ($res){
        $goodsimgs['original_img'] = $res->getOriginalImg();
        $original_img = $goodsimgs['original_img'];
    $url = $original_img."?x-oss-process=image/resize,m_pad,h_$height,w_$width";
    return $url;
    }else{
        return[];
    }
}
function goods_images($image, $width, $height)
{
    if (empty($image)) return '';
        $url = $image."?x-oss-process=image/resize,m_pad,h_$height,w_$width";
        return $url;

    if (empty($original_img)){
        return '/public/images/timg.gif';
    }

    $ossClient = new \app\common\logic\OssLogic;
    if (($ossUrl = $ossClient->getGoodsThumbImageUrl($original_img, $width, $height))) {
        return $ossUrl;
    }

    $original_img = '.' . $original_img; // 相对路径
    if (!is_file($original_img)) {
        return '/public/images/timg.gif';
    }

    try {
        vendor('topthink.think-image.src.Image');
        if(strstr(strtolower($original_img),'.gif'))
        {
            vendor('topthink.think-image.src.image.gif.Encoder');
            vendor('topthink.think-image.src.image.gif.Decoder');
            vendor('topthink.think-image.src.image.gif.Gif');
        }

        $image = \think\Image::open($original_img);

        $goods_thumb_name = $goods_thumb_name . '.' . $image->type();
        // 生成缩略图
        !is_dir($path) && mkdir($path, 0777, true);
        // 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
        $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        //图片水印处理
        $water = tpCache('water');
        if ($water['is_mark'] == 1) {
            $imgresource = './' . $path . $goods_thumb_name;
            if ($width > $water['mark_width'] && $height > $water['mark_height']) {
                if ($water['mark_type'] == 'img') {
                    //检查水印图片是否存在
                    $waterPath = "." . $water['mark_img'];
                    if (is_file($waterPath)) {
                        $quality = $water['mark_quality'] ?: 80;
                        $waterTempPath = dirname($waterPath).'/temp_'.basename($waterPath);
                        $image->open($waterPath)->save($waterTempPath, null, $quality);
                        $image->open($imgresource)->water($waterTempPath, $water['sel'], $water['mark_degree'])->save($imgresource);
                        @unlink($waterTempPath);
                    }
                } else {
                    //检查字体文件是否存在,注意是否有字体文件
                    $ttf = './hgzb.ttf';
                    if (file_exists($ttf)) {
                        $size = $water['mark_txt_size'] ?: 30;
                        $color = $water['mark_txt_color'] ?: '#000000';
                        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                            $color = '#000000';
                        }
                        $transparency = intval((100 - $water['mark_degree']) * (127/100));
                        $color .= dechex($transparency);
                        $image->open($imgresource)->text($water['mark_txt'], $ttf, $size, $color, $water['sel'])->save($imgresource);
                    }
                }
            }
        }
        $img_url = '/' . $path . $goods_thumb_name;

        return $img_url;
    } catch (think\Exception $e) {

        return $original_img;
    }
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img, $goods_id, $width, $height)
{
    //判断缩略图是否存在
    $path = "public/upload/goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";

    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';

    $ossClient = new \app\common\logic\OssLogic;
    if (($ossUrl = $ossClient->getGoodsAlbumThumbUrl($sub_img['image_url'], $width, $height))) {
        return $ossUrl;
    }

    return $sub_img['image_url']."?x-oss-process=image/resize,m_pad,h_$height,w_$width";
    
    $original_img = '.' . $sub_img['image_url']; //相对路径
    if (!is_file($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }

    vendor('topthink.think-image.src.Image');
    if(strstr(strtolower($original_img),'.gif'))
    {
        vendor('topthink.think-image.src.image.gif.Encoder');
        vendor('topthink.think-image.src.image.gif.Decoder');
        vendor('topthink.think-image.src.image.gif.Gif');
    }
    $image = \think\Image::open($original_img);

    !is_dir($path) && mkdir($path, 0777, true);
    $goods_thumb_name = $goods_thumb_name . '.' . $image->type();

    // 生成缩略图
    $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    return '/' . $path . $goods_thumb_name;
}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
//function refresh_stock($goods_id){
//    $count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->count();
//    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存
//
//    $store_count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
//    M("Goods")->where("goods_id", $goods_id)->save(array('store_count'=>$store_count)); // 更新商品的总库存
//}

/**
 * 根据 order_goods 表扣除商品库存
 * @param type $order_id  订单id
 */
//function minus_stock($order_id){
//    $orderGoodsArr = M('OrderGoods')->master()->where("order_id", $order_id)->select();
//    foreach($orderGoodsArr as $key => $val)
//    {
//        // 有选择规格的商品
//        if(!empty($val['spec_key']))
//        {   // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
//            M('SpecGoodsPrice')->where(['goods_id'=>$val['goods_id'],'key'=>$val['spec_key']])->setDec('store_count',$val['goods_num']);
//            refresh_stock($val['goods_id']);
//        }else{
//            M('Goods')->where("goods_id", $val['goods_id'])->setDec('store_count',$val['goods_num']); // 直接扣除商品总数量
//        }
//        M('Goods')->where("goods_id", $val['goods_id'])->setInc('sales_sum',$val['goods_num']); // 增加商品销售量
//        //更新活动商品购买量
//        if($val['prom_type']==1 || $val['prom_type']==2){
//            $prom = get_goods_promotion($val['goods_id']);
//            if($prom['is_end']==0){
//                $tb = $val['prom_type']==1 ? 'flash_sale' : 'group_buy';
//                M($tb)->where("id", $val['prom_id'])->setInc('buy_num',$val['goods_num']);
//                M($tb)->where("id", $val['prom_id'])->setInc('order_num');
//            }
//        }
//    }
//}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content=''){
    vendor('phpmailer.PHPMailerAutoload'); ////require_once vendor/phpmailer/PHPMailerAutoload.php';
    //判断openssl是否开启
    $openssl_funcs = get_extension_funcs('openssl');
    if(!$openssl_funcs){
        return array('status'=>-1 , 'msg'=>'请先开启openssl扩展');
    }
    $mail = new PHPMailer;
    $config = tpCache('smtp');
    $mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];

    if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if(is_array($to)){
        foreach ($to as $v){
            $mail->addAddress($v);
        }
    }else{
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    if (!$mail->send()) {
        return array('status'=>-1 , 'msg'=>'发送失败: '.$mail->ErrorInfo);
    } else {
        return array('status'=>1 , 'msg'=>'发送成功');
    }
}

/**
 * 检测是否能够发送短信
 * @param unknown $scene
 * @return multitype:number string
 */
function checkEnableSendSms($scene)
{
    $scenes = C('SEND_SCENE');
    $sceneItem = $scenes[$scene];
    if (!$sceneItem) {
        return array("status" => -1, "msg" => "场景参数'scene'错误!");
    }
    $key = $sceneItem[2];
    $sceneName = $sceneItem[0];
    $config = tpCache('sms');
    $smsEnable = $config[$key];
    if (!$smsEnable) {
        return array("status" => -1, "msg" => "['$sceneName']发送短信被关闭'");
    }
//    //判断是否添加"注册模板"
//    $size = M('sms_template')->where("send_scene", $scene)->count('tpl_id');
//    if (!$size) {
//        return array("status" => -1, "msg" => "请先添加['$sceneName']短信模板");
//    }

    return array("status"=>1,"msg"=>"可以发送短信");
}

/**
 * 发送短信逻辑
 * @param unknown $scene
 */
function sendSms($scene, $sender, $params,$unique_id=0)
{
    $smsLogic = new \app\common\logic\SmsLogic;
    return $smsLogic->sendSms($scene, $sender, $params, $unique_id);
}

/**
 * 查询快递
 * @param $postcom  快递公司编码
 * @param $getNu  快递单号
 * @return array  物流跟踪信息数组
 */
function queryExpress($postcom , $getNu) {
    /*    $url = "http://wap.kuaidi100.com/wap_result.jsp?rand=".time()."&id={$postcom}&fromWeb=null&postid={$getNu}";
        //$resp = httpRequest($url,'GET');
        $resp = file_get_contents($url);
        if (empty($resp)) {
            return array('status'=>0, 'message'=>'物流公司网络异常，请稍后查询');
        }
        preg_match_all('/\\<p\\>&middot;(.*)\\<\\/p\\>/U', $resp, $arr);
        if (!isset($arr[1])) {
            return array( 'status'=>0, 'message'=>'查询失败，参数有误' );
        }else{
            foreach ($arr[1] as $key => $value) {
                $a = array();
                $a = explode('<br /> ', $value);
                $data[$key]['time'] = $a[0];
                $data[$key]['context'] = $a[1];
            }
            return array( 'status'=>1, 'message'=>'1','data'=> array_reverse($data));
        }*/
    $url = "https://m.kuaidi100.com/query?type=".$postcom."&postid=".$getNu."&id=1&valicode=&temp=0.49738534969422676";
    $resp = httpRequest($url,"GET");
    return json_decode($resp,true);
}

/**
 * 获取某个商品分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
//function getCatGrandson ($cat_id)
//{
//    $GLOBALS['catGrandson'] = array();
//    $GLOBALS['category_id_arr'] = array();
//    // 先把自己的id 保存起来
//    $GLOBALS['catGrandson'][] = $cat_id;
//    // 把整张表找出来
//    $GLOBALS['category_id_arr'] = M('GoodsCategory')->cache(true,TPSHOP_CACHE_TIME)->getField('id,parent_id');
//    // 先把所有儿子找出来
//    $son_id_arr = M('GoodsCategory')->where("parent_id", $cat_id)->cache(true,TPSHOP_CACHE_TIME)->getField('id',true);
//    foreach($son_id_arr as $k => $v)
//    {
//        getCatGrandson2($v);
//    }
//    return $GLOBALS['catGrandson'];
//}

/**
 * 获取某个文章分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
//function getArticleCatGrandson ($cat_id)
//{
//    $GLOBALS['ArticleCatGrandson'] = array();
//    $GLOBALS['cat_id_arr'] = array();
//    // 先把自己的id 保存起来
//    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
//    // 把整张表找出来
//    $GLOBALS['cat_id_arr'] = M('ArticleCat')->getField('cat_id,parent_id');
//    // 先把所有儿子找出来
//    $son_id_arr = M('ArticleCat')->where("parent_id", $cat_id)->getField('cat_id',true);
//    foreach($son_id_arr as $k => $v)
//    {
//        getArticleCatGrandson2($v);
//    }
//    return $GLOBALS['ArticleCatGrandson'];
//}

/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach($GLOBALS['category_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}


/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getArticleCatGrandson2($cat_id)
{
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    foreach($GLOBALS['cat_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getArticleCatGrandson2($k); // 继续找孙子
        }
    }
}

/**
 * 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cart_goods_num($user_id = 0,$session_id = '')
{
//    $where = " session_id = '$session_id' ";
//    $user_id && $where .= " or user_id = $user_id ";
    // 查找购物车数量
//    $cart_count =  M('Cart')->where($where)->sum('goods_num');
//    $cart_count = Db::name('cart')->where(function ($query) use ($user_id, $session_id) {
//        $query->where('session_id', $session_id);
//        if ($user_id) {
//            $query->whereOr('user_id', $user_id);
//        }
//    })->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}

/**
 * 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
//function getGoodNum($goods_id,$key)
//{
//    if(!empty($key))
//        return M("SpecGoodsPrice")->where(['goods_id' => $goods_id, 'key' => $key])->getField('store_count');
//    else
//        return  M("Goods")->where("goods_id", $goods_id)->getField('store_count');
//}

/**
 * 获取缓存或者更新缓存并生成静态配置文件
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function tpCache($config_key,$data = array()){
    $param = explode('.', $config_key);
    if(empty($data)){
        //如$config_key=shop_info则获取网站信息数组
        //如$config_key=shop_info.logo则获取网站logo字符串
        $config = F($param[0],'',TEMP_PATH);//直接获取缓存文件
        if(empty($config)){
            //缓存文件不存在就读取数据库
            if(!is_file(APP_PATH."conf/$param[0].php")) file_put_contents(APP_PATH."conf/$param[0].php",'');
//            $res = include_once APP_PATH."conf/$param[0].php";
            $res = include APP_PATH."conf/$param[0].php"; //此处使用 include 不能使用 include_once

            F($param[0],$res,TEMP_PATH);

        }
        if(count($param)>1){
            return $config[$param[1]];
        }else{
            return $config;
        }
    }else{
        //更新缓存
        //rename(APP_PATH."conf/$param[0].php",APP_PATH."conf/$param[0].php".time());

        $str = "<?php\n/*\n * ".C('platform_id')." 号平台$param[0]配置文件;\n * ".date('Y/m/d H:i:s').";\n * Author:Ning;\n */\n\nreturn  [\n";
        foreach ($data as $k => $v){
            $str .= "    '{$k}' => '{$v}',\n";
        }
        $str .= "\n];";
        file_put_contents(APP_PATH."conf/$param[0].php",$str);
//        $newData = include_once APP_PATH."conf/$param[0].php";
        $newData = include APP_PATH."conf/$param[0].php";
        return F($param[0],$newData,TEMP_PATH);
    }
}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $desc    变动说明
 * @param   float   distribut_money 分佣金额
 * @param int $order_id 订单id
 * @param string $order_sn 订单sn
 * @return  bool
 */
function accountLog($user_id, $user_money = 0,$pay_points = 0, $desc = '',$distribut_money = 0,$order_id = 0 ,$order_sn = ''){
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'pay_points'    => $pay_points,
        'change_time'   => time(),
        'desc'   => $desc,
        'order_id' => $order_id,
        'order_sn' => $order_sn
    );
    /* 更新用户信息 */
//    $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money," .
//        " pay_points = pay_points + $pay_points, distribut_money = distribut_money + $distribut_money WHERE user_id = $user_id";
    $update_data = array(
        'user_money'        => ['exp','user_money+'.$user_money],
        'pay_points'        => ['exp','pay_points+'.$pay_points],
        'distribut_money'   => ['exp','distribut_money+'.$distribut_money],
    );
    if(($user_money+$pay_points+$distribut_money) == 0)
        return false;
//    $update = Db::name('users')->where('user_id',$user_id)->update($update_data);
//    if($update){
//        M('account_log')->add($account_log);
//        return true;
//    }else{
//        return false;
//    }
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
//function logOrder($order_id,$action_note,$status_desc,$user_id = 0)
//{
//    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
//    // if(!in_array($status_desc, $status_desc_arr))
//    // return false;
//
////    $order = M('order')->master()->where("order_id", $order_id)->find();
//    $action_info = array(
//        'order_id'        =>$order_id,
//        'action_user'     =>0,
//        'order_status'    =>$order['order_status'],
//        'shipping_status' =>$order['shipping_status'],
//        'pay_status'      =>$order['pay_status'],
//        'action_note'     => $action_note,
//        'status_desc'     =>$status_desc, //''
//        'log_time'        =>time(),
//    );
//    return M('order_action')->add($action_info);
//}

/*
 * 获取地区列表
 */
//function get_region_list(){
//    return M('region')->cache(true)->getField('id,name');
//}
/*
 * 获取用户地址列表
 */
//function get_user_address_list($user_id){
//    $lists = M('user_address')->where(array('user_id'=>$user_id))->select();
//    return $lists;
//}

/*
 * 获取指定地址信息
 */
//function get_user_address_info($user_id,$address_id){
//    $data = M('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->find();
//    return $data;
//}
/*
 * 获取用户默认收货地址
 */
//function get_user_default_address($user_id){
//    $data = M('user_address')->where(array('user_id'=>$user_id,'is_default'=>1))->find();
//    return $data;
//}
/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return string
 */
function orderStatusDesc($order_id = 0, $order = array())
{
    if(empty($order))
//        $order = M('Order')->where("order_id", $order_id)->find();

    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0)
            return 'WAITSEND'; //'待发货',
    }
    else // 非货到付款
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0)
            return 'WAITPAY'; //'待支付',
        if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0)
            return 'WAITSEND'; //'待发货',
        if($order['pay_status'] == 1 &&  $order['shipping_status'] == 2 && $order['order_status'] == 1)
            return 'PORTIONSEND'; //'部分发货',
    }
    if(($order['shipping_status'] == 1) && ($order['order_status'] == 1))
        return 'WAITRECEIVE'; //'待收货',
    if($order['order_status'] == 2)
        return 'WAITCCOMMENT'; //'待评价',
    if($order['order_status'] == 3)
        return 'CANCEL'; //'已取消',
    if($order['order_status'] == 4)
        return 'FINISH'; //'已完成',
    if($order['order_status'] == 5)
        return 'CANCELLED'; //'已作废',
    return 'OTHER';
}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return array()
 */
function orderBtn($order_id = 0, $order = array())
{
    if(empty($order))
//        $order = M('Order')->where("order_id", $order_id)->find();
    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
     */
    $btn_arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );


    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(($order['order_status']==0 || $order['order_status']==1) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
        }
        if($order['shipping_status'] == 1 && $order['order_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }
    // 非货到付款
    else
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if($order['pay_status'] == 1 && in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if($order['pay_status'] == 1 && $order['order_status'] == 1  && $order['shipping_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }
    if($order['order_status'] == 2)
    {
        $btn_arr['comment_btn'] = 1;  // 评价按钮
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['shipping_status'] != 0 && $order['order_status'] == 1)
    {
        $btn_arr['shipping_btn'] = 1; // 查看物流
    }
    if($order['shipping_status'] == 2 && in_array($order['order_status'], [1,2,4])) // 部分发货
    {
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($data['order_status'] == 3 && ($data['pay_status'] == 1 || $data['pay_status'] == 4)){
        $btn_arr['cancel_info'] = 1; // 取消订单详情
    }

    return $btn_arr;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function set_btn_order_status($order)
{
    $order_status_arr = C('ORDER_STATUS_DESC');
    $order['order_status_code'] = $order_status_code = orderStatusDesc(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order,$orderBtnArr); // 订单该显示的按钮
}


/**
 * 生成支付号
 * @param $amount 订单总金额
 * @param $order_ids 订单id列表(订单id连成的字符串)
 * @param $type 账户类型
 * @param $currency 币种
 * @param $biz_type 业务类型
 * @param $user_id 支付者id
 * @param $org_id 组织id
 * @param $platform_id 平台id
 * @param $extra 附加信息
 * @param $extra 支付方式
 * @param $pay_type 支付方式
 * @return string|支付号
 */
function create_paycode($type,$amount,$currency=1,$biz_type,$user_id,$org_id,$platform_id,$order_ids,$extra,$pay_type){
    $Receiptinfo = new Psp\Account\GetReceiptIdRequest();
    $Receiptinfo->setType($type);
    $Receiptinfo->setAmount($amount);
    $Receiptinfo->setCurrency($currency);
    $Receiptinfo->setBizType($biz_type);
    $Receiptinfo->setUserId($user_id);
    $Receiptinfo->setOrgId($org_id);
    $Receiptinfo->setPlatformId($platform_id);
    $Receiptinfo->setOrderIds($order_ids);
    $Receiptinfo->setExtraInfo($extra);
    $Receiptinfo->setIssueDate(grpcTime(time()));
    list($receipt_id_res) = GRPC('account')->getReceiptId($Receiptinfo)->wait();
    $paycode = $receipt_id_res->getReceiptId();
    $orderids = array_map(function($v){
        return (int)$v;
    },explode(',',$order_ids));
    //更新订单的支付号和支付方式
    $pay_code = Trade(PayCodeInfo);
    $pay_code->setPayCode($paycode);
    $pay_code->setPayType($pay_type);
    //$pay_code->setOrderIds(array(29,30));

    $order_ids = Trade(OrderIds);
    $order_ids->setOrderId($orderids);
    $pay_code->setOrderIds($order_ids);
    list($res,$status) = GRPC(Trade)->UpdateOrderPaycode($pay_code)->wait();
    if(!$res->getValue()){
        return;
    }
    return $paycode;
}

/**
 * 支付完成修改订单
 * @param $pay_code 支付号
 * @parm $order_amount 订单总金额  第三方支付返回金额
 * @param array $ext 额外参数
 * @return bool|void
 */
function update_pay_status($pay_code,$order_amount,$ext=array())
{
        $client = GRPC(Trade);
        $pay = Trade(OrderPayInfo);
        $pay->setTradeNo($pay_code);
        $pay->setMoney($order_amount);
        list($res,$status) = $client->UpdatePayStatus($pay)->wait();


    /*
    if(stripos($order_sn,'recharge') !== false){
        //用户在线充值
        $count = M('recharge')->where(['order_sn'=>$order_sn,'pay_status'=>0])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        $order = M('recharge')->where("order_sn", $order_sn)->find();
        M('recharge')->where("order_sn",$order_sn)->save(array('pay_status'=>1,'pay_time'=>time()));
        accountLog($order['user_id'],$order['account'],0,'会员在线充值');
    }else{
        // 如果这笔订单已经处理过了
        $count = M('order')->master()->where("order_sn = :order_sn and pay_status = 0 OR pay_status = 2")->bind(['order_sn'=>$order_sn])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        // 找出对应的订单
        $order = M('order')->master()->where("order_sn",$order_sn)->find();
        //预售订单
        if ($order['order_prom_type'] == 4) {
            $orderGoodsArr = M('OrderGoods')->where(array('order_id'=>$order['order_id']))->find();
            // 预付款支付 有订金支付 修改支付状态  部分支付
            if($order['total_amount'] != $order['order_amount'] && $order['pay_status'] == 0){
                //支付订金
                M('order')->where("order_sn", $order_sn)->save(array('order_sn'=> date('YmdHis').mt_rand(1000,9999) ,'pay_status' => 2, 'pay_time' => time(),'paid_money'=>$order['order_amount']));
                M('goods_activity')->where(array('act_id'=>$order['order_prom_id']))->setInc('act_count',$orderGoodsArr['goods_num']);
            }else{
                //全额支付 无订金支付 支付尾款
                M('order')->where("order_sn", $order_sn)->save(array('pay_status' => 1, 'pay_time' => time()));
                $pre_sell = M('goods_activity')->where(array('act_id'=>$order['order_prom_id']))->find();
                $ext_info = unserialize($pre_sell['ext_info']);
                //全额支付 活动人数加一
                if(empty($ext_info['deposit'])){
                    M('goods_activity')->where(array('act_id'=>$order['order_prom_id']))->setInc('act_count',$orderGoodsArr['goods_num']);
                }
            }
        } else {
            // 修改支付状态  已支付
            $updata = array('pay_status'=>1,'pay_time'=>time());
            if(isset($ext['transaction_id'])) $updata['transaction_id'] = $ext['transaction_id'];
            M('order')->where("order_sn", $order_sn)->save($updata);
        }
        // 减少对应商品的库存
        minus_stock($order['order_id']);
        // 给他升级, 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
        update_user_level($order['user_id']);
        // 记录订单操作日志
        if(array_key_exists('admin_id',$ext)){
            logOrder($order['order_id'],$ext['note'],'付款成功',$ext['admin_id']);
        }else{
            logOrder($order['order_id'],'订单付款成功','付款成功',$order['user_id']);
        }
        //分销设置
        M('rebate_log')->where("order_id" ,$order['order_id'])->save(array('status'=>1));
        // 成为分销商条件
        $distribut_condition = tpCache('distribut.condition');
        if($distribut_condition == 1)  // 购买商品付款才可以成为分销商
            M('users')->where("user_id", $order['user_id'])->save(array('is_distribut'=>1));

        //用户支付, 发送短信给商家
        $res = checkEnableSendSms("4");
        if(!$res || $res['status'] !=1) return ;

        $sender = tpCache("shop_info.mobile");
        if(empty($sender))return;
        $params = array('order_sn'=>$order_sn);
        sendSms("4", $sender, $params);
    }*/

}

/*
 *会员还款
 *@param $pay_code 支付号
 * @parm $order_amount 还款金额  第三方支付返回金额
 * @param status 1未支付 2已支付
 */
function update_recharge_status($pay_code,$order_amount){
    $params = new Psp\Member\MemberThirdRepayment();
    $params->setPayNumber($pay_code);
    $params->setRepaymentMoney($order_amount);
    $params->setStatus(2);//支付成功
    list($resp) = GRPC('member')->SaveMemberRepaymentOperate($params)->wait();
}

/**
 * 提现批量转账成功后修改转账状态
 * @param $withdraw_id 提现id
 * @parm $status 转账状态(转账成功/转账失败)
 * @parm $time 完成时间
 * @param array $note 转账失败的原因
 * @return bool|void
 */
function update_withdraw_status($withdraw_id,$status,$time,$reason)
{
    $obj = new Psp\Account\WithdrawStatus();
    $obj->setWithdrawId($withdraw_id);
    $obj->setStatus($status);
    $obj->setFinishDate(grpcTime($time));
    $obj->setFailureReason($reason);
    list($reply) = GRPC(account)->updateWithdrawStatus($obj)->wait();
    return $reply->getValue();

}

/**
 * 订单确认收货
 * @param $id 订单id
 * @param int $user_id
 * @return array
 */
function confirm_order($id,$user_id = 0){

    $client = GRPC(Trade);
    $order = Trade(OrderId);
    $order->setOrderId($id);
    list($res,$status) = $client->ConfirmOrder($order)->wait();
    if(empty($res)) return [];
    $result = $res->getValue();
    if(!$result)
        return array('status'=>-3,'msg'=>'操作失败');
    return array('status'=>1,'msg'=>'操作成功');
//    $where['order_id'] = $id;
//    if($user_id){
//        $where['user_id'] = $user_id;
//    }
//    $order = M('order')->where($where)->find();
//    if($order['order_status'] != 1)
//        return array('status'=>-1,'msg'=>'该订单不能收货确认');
//
//    $data['order_status'] = 2; // 已收货
//    $data['pay_status'] = 1; // 已付款
//    $data['confirm_time'] = time(); // 收货确认时间
//    if($order['pay_code'] == 'cod'){
//        $data['pay_time'] = time();
//    }
//    $row = M('order')->where(array('order_id'=>$id))->save($data);
//    if(!$row)
//        return array('status'=>-3,'msg'=>'操作失败');
//
//    order_give($order);// 调用送礼物方法, 给下单这个人赠送相应的礼物
//    //分销设置
//    M('rebate_log')->where("order_id", $id)->save(array('status'=>2,'confirm'=>time()));
//    return array('status'=>1,'msg'=>'操作成功');
}

/**
 * 下单赠送活动：优惠券，积分
 * @param $order|订单数组
 */
//function order_give($order)
//{
//    //促销优惠订单商品
////    $prom_order_goods = M('order_goods')->where(['order_id' => $order['order_id'], 'prom_type' => 3])->select();
//    //获取用户会员等级
////    $user_level = Db::name('users')->where(['user_id' => $order['user_id']])->getField('level');
//    foreach ($prom_order_goods as $goods) {
//        //查找购买商品送优惠券活动
////        $prom_goods = M('prom_goods')->where(['id' => $goods['prom_id'], 'type' => 3])->find();
//        if ($prom_goods) {
//            //查找购买商品送优惠券模板
//            $goods_coupon = M('coupon')->where(['id' => $prom_goods['expression']])->find();
//            if ($goods_coupon && !empty($prom_goods['group'])) {
//                // 用户会员等级是否符合送优惠券活动
//                if (array_key_exists($user_level, array_flip(explode(',', $prom_goods['group'])))) {
//                    //优惠券发放数量验证，0为无限制。发放数量-已领取数量>0
//                    if ($goods_coupon['createnum'] == 0 ||
//                        ($goods_coupon['createnum'] > 0 && ($goods_coupon['createnum'] - $goods_coupon['send_num']) > 0)
//                    ) {
//                        $data = array('cid' => $goods_coupon['id'], 'type' => $goods_coupon['type'], 'uid' => $order['user_id'], 'send_time' => time());
//                        M('coupon_list')->add($data);
//                        // 优惠券领取数量加一
//                        M('Coupon')->where("id", $goods_coupon['id'])->setInc('send_num');
//                    }
//                }
//            }
//        }
//    }
//    //查找订单满额促销活动
//    $prom_order_where = [
//        'type' => ['gt', 1],
//        'end_time' => ['gt', $order['pay_time']],
//        'start_time' => ['lt', $order['pay_time']],
//        'money' => ['elt', $order['order_amount']]
//    ];
//    $prom_orders = M('prom_order')->where($prom_order_where)->order('money desc')->select();
//    $prom_order_count = count($prom_orders);
//    // 用户会员等级是否符合送优惠券活动
//    for ($i = 0; $i < $prom_order_count; $i++) {
//        if (array_key_exists($user_level, array_flip(explode(',', $prom_orders[$i]['group'])))) {
//            $prom_order = $prom_orders[$i];
//            if ($prom_order['type'] == 3) {
//                //查找订单送优惠券模板
//                $order_coupon = M('coupon')->where("id", $prom_order['expression'])->find();
//                if ($order_coupon) {
//                    //优惠券发放数量验证，0为无限制。发放数量-已领取数量>0
//                    if ($order_coupon['createnum'] == 0 ||
//                        ($order_coupon['createnum'] > 0 && ($order_coupon['createnum'] - $order_coupon['send_num']) > 0)
//                    ) {
//                        $data = array('cid' => $order_coupon['id'], 'type' => $order_coupon['type'], 'uid' => $order['user_id'], 'send_time' => time());
//                        M('coupon_list')->add($data);
//                        M('Coupon')->where("id", $order_coupon['id'])->setInc('send_num'); // 优惠券领取数量加一
//                    }
//                }
//            }
//            //购买商品送积分
//            if ($prom_order['type'] == 2) {
//                accountLog($order['user_id'], 0, $prom_order['expression'], "订单活动赠送积分");
//            }
//            break;
//        }
//    }
//    $points = M('order_goods')->where("order_id", $order['order_id'])->sum("give_integral * goods_num");
//    $points && accountLog($order['user_id'], 0, $points, "下单赠送积分", 0, $order['order_id'], $order['order_sn']);
//}


/**
 * 查看商品是否有活动
 * @param goods_id 商品ID
 */

//function get_goods_promotion2($goods_id,$user_id=0){
//    $now = time();
//    $goods = M('goods')->where("goods_id", $goods_id)->find();
//    $where = [
//        'end_time' => ['gt', $now],
//        'start_time' => ['lt', $now],
//        'id' => $goods['prom_id'],
//    ];
//
//    $prom['price'] = $goods['shop_price'];
//    $prom['prom_type'] = $goods['prom_type'];
//    $prom['prom_id'] = $goods['prom_id'];
//    $prom['is_end'] = 0;
//
//    if($goods['prom_type'] == 1){//抢购
//        $prominfo = M('flash_sale')->where($where)->find();
//        if(!empty($prominfo)){
//            $prom['store_count'] = $prominfo['goods_num'] - $prominfo['buy_num'];
//            if($prominfo['goods_num'] == $prominfo['buy_num']){
//                $prom['is_end'] = 2;//已售馨
//            }else{
//                //核查用户购买数量
//                $where = "user_id = :user_id and order_status!=3 and  add_time>".$prominfo['start_time']." and add_time<".$prominfo['end_time'];
//                $order_id_arr = M('order')->where($where)->bind(['user_id'=>$user_id])->getField('order_id',true);
//                if($order_id_arr){
//                    $goods_num = M('order_goods')->where("prom_id={$goods['prom_id']} and prom_type={$goods['prom_type']} and order_id in (".implode(',', $order_id_arr).")")->sum('goods_num');
//                    if($goods_num < $prominfo['buy_limit']){
//                        $prom['price'] = $prominfo['price'];
//                    }
//                }else{
//                    $prom['price'] = $prominfo['price'];
//                }
//            }
//        }
//    }
//
//    if($goods['prom_type']==2){//团购
//        $prominfo = M('group_buy')->where($where)->find();
//        if(!empty($prominfo)){
//            if($prominfo['goods_num'] == $prominfo['buy_num']){
//                $prom['is_end'] = 2;//已售馨
//            }else{
//                $prom['price'] = $prominfo['price'];
//            }
//        }
//    }
//    if($goods['prom_type'] == 3){//优惠促销
//        $parse_type = array('0'=>'直接打折','1'=>'减价优惠','2'=>'固定金额出售','3'=>'买就赠优惠券','4'=>'买M件送N件');
//        $prominfo = M('prom_goods')->where($where)->find();
//        if(!empty($prominfo)){
//            if($prominfo['type'] == 0){
//                $prom['price'] = $goods['shop_price']*$prominfo['expression']/100;//打折优惠
//            }elseif($prominfo['type'] == 1){
//                $prom['price'] = $goods['shop_price']-$prominfo['expression'];//减价优惠
//            }elseif($prominfo['type']==2){
//                $prom['price'] = $prominfo['expression'];//固定金额优惠
//            }
//        }
//    }
//
//    if(!empty($prominfo)){
//        $prom['start_time'] = $prominfo['start_time'];
//        $prom['end_time'] = $prominfo['end_time'];
//    }else{
//        $prom['prom_type'] = $prom['prom_id'] = 0 ;//活动已过期
//        $prom['is_end'] = 1;//已结束
//    }
//
//    if($prom['prom_id'] == 0){
//        M('goods')->where("goods_id", $goods_id)->save($prom);
//    }
//    return $prom;
//}

/**
 * 查看商品是否有活动
 * @param $goods_id |商品ID
 * @param int $user_id |活动ID
 * @return mixed
 */
function get_goods_promotion($goods_id, $user_id = 0)
{
    $goodsModel = new \app\admin\model\Goods();
    $goodsPromFactory = new \app\admin\logic\GoodsPromFactory();
    $goods = $goodsModel::get($goods_id);
    if ($goodsPromFactory->checkPromType($goods['prom_type'])) {
        $goodsProm = $goodsPromFactory->makeModule($goods['prom_type'], $goods['prom_id']);
        $prom = $goodsProm->getPromotionInfo($user_id, $goods_id);
    } else {
        $prom['price'] = $goods['shop_price'];
        $prom['prom_type'] = $prom['prom_id'] = 0;//活动已过期
        $prom['is_end'] = 1;//已结束
    }
    return $prom;
}

/**
 * 查看订单是否满足条件参加活动
 * @param $order_amount
 * @return array
 */
//function get_order_promotion($order_amount)
//{
////    $parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送倍数积分','3'=>'满额送优惠券','4'=>'满额免运费');
//    $now = time();
//    $prom = M('prom_order')->where("type<2 and end_time>$now and start_time<$now and money<=$order_amount")->order('money desc')->find();
//    $res = array('order_amount' => $order_amount, 'order_prom_id' => 0, 'order_prom_amount' => 0);
//    if ($prom) {
//        if ($prom['type'] == 0) {
//            $res['order_amount'] = round($order_amount * $prom['expression'] / 100, 2);//满额打折
//            $res['order_prom_amount'] = $order_amount - $res['order_amount'];
//            $res['order_prom_id'] = $prom['id'];
//        } elseif ($prom['type'] == 1) {
//            $res['order_amount'] = $order_amount - $prom['expression'];//满额优惠金额
//            $res['order_prom_amount'] = $prom['expression'];
//            $res['order_prom_id'] = $prom['id'];
//        }
//    }
//    return $res;
//}

/**
 * 计算订单金额
 * @param type $user_id  用户id
 * @param type $order_goods  购买的商品
 * @param type $shipping  物流code
 * @param type $shipping_price 物流费用, 如果传递了物流费用 就不在计算物流费
 * @param type $province  省份
 * @param type $city 城市
 * @param type $district 县
 * @param type $pay_points 积分
 * @param type $user_money 余额
 * @param type $coupon_id  优惠券
 * @param type $couponCode  优惠码
 */

//function calculate_price($user_id = 0, $order_goods, $shipping_code = '', $shipping_price = 0, $province = 0, $city = 0, $district = 0, $pay_points = 0, $user_money = 0, $coupon_id = 0, $couponCode = '')
//{
//    $couponLogic = new app\home\logic\CouponLogic();
//    $goodsLogic = new app\home\logic\GoodsLogic();
//    $user = M('users')->where("user_id", $user_id)->find();// 找出这个用户
//    $result=[];
//    if (empty($order_goods)){
//        return array('status' => -9, 'msg' => '商品列表不能为空', 'result' => '');
//    }
//    $use_percent_point = tpCache('shopping.point_use_percent') / 100;     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
//    $goods_id_arr = get_arr_column($order_goods, 'goods_id');
//    $goods_arr = M('goods')->where("goods_id in(" . implode(',', $goods_id_arr) . ")")->cache(true,TPSHOP_CACHE_TIME)->getField('goods_id,weight,market_price,is_free_shipping,exchange_integral,shop_price'); // 商品id 和重量对应的键值对
//    foreach ($order_goods as $key => $val) {
//        // 如果传递过来的商品列表没有定义会员价
//        if (!array_key_exists('member_goods_price', $val)) {
//            $user['discount'] = $user['discount'] ? $user['discount'] : 1; // 会员折扣 不能为 0
//            $order_goods[$key]['member_goods_price'] = $val['member_goods_price'] = $val['goods_price'] * $user['discount'];
//        }
//        //如果商品不是包邮的
//        if ($goods_arr[$val['goods_id']]['is_free_shipping'] == 0)
//            $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量 每种商品的重量 * 数量
//        //计算订单可用积分
//        if($goods_arr[$val['goods_id']]['exchange_integral']>0){
//            //商品设置了积分兑换就用商品本身的积分。
//            $result['order_integral'] +=  $goods_arr[$val['goods_id']]['exchange_integral'];
//        }else{
//            //没有就按照会员价与平台设置的比例来计算。
//            $result['order_integral'] +=  ceil($order_goods[$key]['member_goods_price'] * $use_percent_point);
//        }
//
//        $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计
//        $order_goods[$key]['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']); // 最多可购买的库存数量
//        if ($order_goods[$key]['store_count'] <= 0)
//            return array('status' => -10, 'msg' => $order_goods[$key]['goods_name'] . "库存不足,请重新下单", 'result' => '');
//
//        $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
//        $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; // 共节约
//        $anum += $val['goods_num']; // 购买数量
//    }
//    // 优惠券处理操作
//    $coupon_price = 0;
//    if ($coupon_id && $user_id) {
//        $coupon_price = $couponLogic->getCouponMoney($user_id, $coupon_id); // 下拉框方式选择优惠券
//    }
//    if ($couponCode && $user_id) {
//        $coupon_result = $couponLogic->getCouponMoneyByCode($couponCode, $goods_price); // 根据 优惠券 号码获取的优惠券
//        if ($coupon_result['status'] < 0)
//            return $coupon_result;
//        $coupon_price = $coupon_result['result'];
//    }
//    // 处理物流
//    if ($shipping_price == 0) {
//        $freight_free = tpCache('shopping.freight_free'); // 全场满多少免运费
//        if ($freight_free > 0 && $goods_price >= $freight_free) {
//            $shipping_price = 0;
//        } else {
//            $shipping_price = $goodsLogic->getFreight($shipping_code, $province, $city, $district, $goods_weight);
//        }
//    }
//
//    if(($pay_points > 0 && $use_percent_point == 0) ||  ($pay_points >0 && $result['order_integral']==0)){
//        return array('status' => -1, 'msg' => "该笔订单不能使用积分", 'result' => '积分'); // 返回结果状态
//    }
//
//    if ($pay_points && ($pay_points > $user['pay_points']))
//        return array('status' => -5, 'msg' => "你的账户可用积分为:" . $user['pay_points'], 'result' => ''); // 返回结果状态
//    if ($user_money && ($user_money > $user['user_money']))
//        return array('status' => -6, 'msg' => "你的账户可用余额为:" . $user['user_money'], 'result' => ''); // 返回结果状态
//
//    $order_amount = $goods_price + $shipping_price - $coupon_price; // 应付金额 = 商品价格 + 物流费 - 优惠券
//
//    $user_money = ($user_money > $order_amount) ? $order_amount : $user_money;  // 余额支付原理等同于积分
//    $order_amount = $order_amount - $user_money; //  余额支付抵应付金额
//
//    /*判断能否使用积分
//     1..积分低于point_min_limit时,不可使用
//     2.在不使用积分的情况下, 计算商品应付金额
//     3.原则上, 积分支付不能超过商品应付金额的50%, 该值可在平台设置
//     @{ */
//    $point_rate = tpCache('shopping.point_rate'); //兑换比例: 如果拥有的积分小于该值, 不可使用
//    $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
//
//
//    if ($min_use_limit_point > 0 && $pay_points > 0 && $pay_points < $min_use_limit_point) {
//        return array('status' => -1, 'msg' => "您使用的积分必须大于{$min_use_limit_point}才可以使用", 'result' => ''); // 返回结果状态
//    }
//    // 计算该笔订单最多使用多少积分
//    if(($use_percent_point !=1 ) && $pay_points > $result['order_integral']) {
//        return array('status'=>-1,'msg'=>"该笔订单, 您使用的积分不能大于{$result['order_integral']}",'result'=>'积分'); // 返回结果状态
//    }
//
//
//    $pay_points = ($pay_points / tpCache('shopping.point_rate')); // 积分支付 100 积分等于 1块钱
//    $pay_points = ($pay_points > $order_amount) ? $order_amount : $pay_points; // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱
//    $order_amount = $order_amount - $pay_points; //  积分抵消应付金额
//
//    $total_amount = $goods_price + $shipping_price;
//    //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
//    $result = array(
//        'total_amount' => $total_amount, // 商品总价
//        'order_amount' => $order_amount, // 应付金额
//        'shipping_price' => $shipping_price, // 物流费
//        'goods_price' => $goods_price, // 商品总价
//        'cut_fee' => $cut_fee, // 共节约多少钱
//        'anum' => $anum, // 商品总共数量
//        'integral_money' => $pay_points,  // 积分抵消金额
//        'user_money' => $user_money, // 使用余额
//        'coupon_price' => $coupon_price,// 优惠券抵消金额
//        'order_goods' => $order_goods, // 商品列表 多加几个字段原样返回
//    );
//    return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
//}

/**
 * 获取商品一二三级分类
 * @return type
 */
function get_goods_category_tree($lang,$platform){
    $tree = $arr = $result = array();
    //    获取全部分类
    $category = new Psp\Newhome\ItemCatAllRequest();
    $category->setIsShow(true);
    $category->setPlatform($platform);
    $category->setLanguage($lang);
    list($res,$status) = GRPC('NewGoodsList')->GetItemCatAll($category)->wait();
    if ($res){
        foreach ($res->getCatInfo2() as $k=>$v) {
            $arr1[$k]['parent_id'] = $v->getParentId();
            $arr1[$k]['level'] = $v->getLevel();
            $arr1[$k]['id'] = $v->getId();
            $arr1[$k]['name'] = $v->getName();
            $arr1[$k]['is_hot'] = $v->getIsHot();
            $arr1[$k]['mobile_name'] = $v->getMobileName();
            $arr1[$k]['image'] = $v->getImage();
        }
    }

    $cat_list = $arr1;//M('goods_category')->cache(true)->where("is_show = 1")->order('sort_order')->select();//所有分类
    if($cat_list){
        foreach ($cat_list as $val){
            if($val['level'] == 2){
                $arr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 3){
                $crr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 1){
                $tree[] = $val;
            }
        }

        foreach ($arr as $k=>$v){
            foreach ($v as $kk=>$vv){
                $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
            }
        }

        foreach ($tree as $val){
            $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
            $result[$val['id']] = $val;
        }
    }
    return $result;
}

/**
 * 写入静态页面缓存
 */
function write_html_cache($html){
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('write_html_cache写入缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;

        //if(!is_dir(RUNTIME_PATH.'html'))
        //mkdir(RUNTIME_PATH.'html');
        //$filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        $filename =  $m_c_a_str;
        // 组合参数
        if(isset($val['p']))
        {
            foreach($val['p'] as $k=>$v)
                $filename.='_'.$_GET[$v];
        }
        $filename.= '.html';
        \think\Cache::set($filename,$html);
        //file_put_contents($filename, $html);
    }
}

/**
 * 读取静态页面缓存
 */
function read_html_cache(){
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('read_html_cache读取缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;

        //$filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        $filename =  $m_c_a_str;
        // 组合参数
        if(isset($val['p']))
        {
            foreach($val['p'] as $k=>$v)
                $filename.='_'.$_GET[$v];
        }
        $filename.= '.html';
        $html = \think\Cache::get($filename);
        if($html)
        {
            //echo file_get_contents($filename);
            echo \think\Cache::get($filename);
            exit();
        }
    }

    /**
     * 统一JSON规范返回数据API
     *
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return \think\response\Json
     */
    function echoJson($data = [], $msg = "OK", $code = 200)
    {
        if (empty($data['code'])) {
            $data['code'] = 1;
        }
        $resp['data'] = $data;
        $resp['ret'] = $code;
        $resp['msg'] = $msg;
        return json($resp, 200);
    }
}

/**
 * author fzq
 * 添加权限时调用
 *传入权限信息   goods@index-商品列表   1位平台权限  2商家权限
 *return string   101-商品列表
 */
function getRightStr(array $data,$type=1)
{

    $allRight = include APP_PATH.'admin/conf/right.php';
    if ($type ==2) {
        $allRight = include APP_PATH.'admin/conf/sellerright.php';
    }
    foreach ($data as $key => $val) {
        //处理data
        $arr =explode('-',$val);
        $k =array_search($arr[0],$allRight);
        if($k){
            $newArr[$key]=$k.'-'.$arr[1];
        }
    }

    //如果未添加对应的权限id 报错
    if(!$newArr){
        return array('status'=>'-1','msg'=>'请先添加权限id','result'=>'');
    }
    //处理新数组 转化为字符串
    $newData =implode(',',$newArr);
    return array('status'=>'1','msg'=>'获取成功','result'=>$newData);

}


/**
 * author fzq
 *根据权限id字符串 获取权限码  1平台权限  2商家权限
 *return array
 */
function getRightCode($str,$type =1)
{
    $allRight = include APP_PATH.'admin/conf/right.php';
    if ($type ==2) {
        $allRight = include APP_PATH.'admin/conf/sellerright.php';
    }
    //根绝 权限id获取 权限码 Goods@editGoods
    $right_code =array();
    foreach ($allRight as $k => $v) {
        $k ="$k";
        if(strpos($str,$k) !==false){
            $right_code[$k] =$v;
        }
    }
    return $right_code;
}

/**根据短信场景type返回对应模板code**/
function getSmsTplCode($type)
{
    switch ($type){
        case 1:
            $arr = array('sms_sign'=>'量子时空会员注册','sms_tpl_code'=>'SMS_128520079');//用户注册
            break;
        case 2:
            $arr = array('sms_sign'=>'量子时空','sms_tpl_code'=>'SMS_128520078');//找回密码
            break;
        case 3:
            $arr = array('sms_sign'=>'客户下单','sms_tpl_code'=>'SMS_352210151'); //客户下单
            break;
        case 4:
            $arr = array('sms_sign'=>'客户支付','sms_tpl_code'=>'SMS_352210151'); //客户支付
            break;
        case 5:
//            $arr = array('sms_sign'=>SITE_URL.'商城','sms_tpl_code'=>'SMS_127169396'); //商家发货
            $arr = array('sms_sign'=>'量子时空','sms_tpl_code'=>'SMS_127169396'); //商家发货
            break;
        default:
            $arr = array('sms_sign'=>'量子时空身份认证','sms_tpl_code'=>'SMS_128520077');  //信息变更验证码
            break;
    }
    return $arr;
}

/***获取登录设备号***/
function getEquipmentSystem(){

    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if(stristr($agent,'iPad')) {
        $fb_fs = "iPad";
    }else if(preg_match('/Android (([0-9_.]{1,3})+)/i',$agent,$version)) {
        $fb_fs = "Cellphone(Android ".$version[1].")";
    }else if(stristr($agent,'Linux')){
        $fb_fs = "Computer(Linux)";
    }else if(preg_match('/iPhone OS (([0-9_.]{1,3})+)/i',$agent,$version)){
        $fb_fs = "Cellphone(iPhone ".$version[1].")";
    }else if(preg_match('/Mac OS X (([0-9_.]{1,5})+)/i',$agent,$version)){
        $fb_fs = "Computer(OS X ".$version[1].")";
    }else if(preg_match('/unix/i',$agent)){
        $fb_fs = "Unix";
    }else if(preg_match('/windows/i',$agent)){
        $fb_fs = "Windows";
    }else{
        $fb_fs = "Unknown";
    }
    $now_time = date('YmdH',time());

    return $fb_fs.'_'.$now_time;
}


/******************** JSON WEB TOKEN **************************/

//随机token
function random_token($length)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length));
    }
    if (function_exists('mcrypt_create_iv')) {
        return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

//创建jwt
function create_json_web_token($payload)
{
//    date_default_timezone_set('Asia/Shanghai');
    $payload['iat'] = time(); // 生成时间
    $payload['exp'] = time() +3600; // 过期时间  默认1小时
    $payload['iss'] = get_host(); // 该JWT的签发者
    // $payload['aud'] = 'qiang_ge';//接收该JWT的一方
    //$payload['jti'] = 'some id'; // jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击
    //$payload['nbf'] = // 定义在什么时间之前，该jwt都是不可用的
    // 头部
    $header = array("alg"=>"HS256","typ"=>"JWT");
    // json编码头
    $json_encoded_header = json_encode($header);
    // base64编码头json
    $encoded_header = base64_encode($json_encoded_header);
    // json编码的有效载荷
    $json_encoded_payload = json_encode($payload);
    // base64编码有效负载json
    $encoded_payload = base64_encode($json_encoded_payload);
    // base64字符串连接到一个这样的字符串
    $header_payload = $encoded_header . '.' . $encoded_payload;
    //设置秘钥
    //$rb = random_token(8);
    //$secret_key = bin2hex($rb);
    ////echo $secret_key;
    //创建签名，使用s256算法和秘密密钥进行散列。签名也是base64编码
    $signature = base64_encode(hash_hmac('sha256', $header_payload, C('SECRET_KEY'), true));
    // 通过将签名与标题和有效载荷连接起来创建JWT令牌
    $jwt_token = $header_payload . '.' . $signature;
    //确保对令牌进行编码。它可能有“+”号。在读取$ _COOKIE ['value']时，使用rawurldecode再次解码，否则'+'将成为“空格”。
    return rawurlencode($jwt_token);

}

//验证jwt
function validate_json_web_token($jwt_token)
{
    rawurldecode($jwt_token);
    $jwt_values = explode('.', $jwt_token);
    // 从最初的jwt中提取签名
    $recieved_signature = $jwt_values[2];
    // 连接的前两个参数$ jwt_values数组,代表头和负载
    $recievedHeaderAndPayload = $jwt_values[0] . '.' . $jwt_values[1];
    // 解码
    $payload = json_decode(base64_decode($jwt_values[1]), true);
    // 创建base64编码的新生成的签名应用了HMAC方法连接头和有效载荷值
    $resultedsignature = base64_encode(hash_hmac('sha256', $recievedHeaderAndPayload,C('SECRET_KEY'), true));
    // 检查如果创建签名等于收到签名
    if($resultedsignature == $recieved_signature) {
        return $payload;
    }

    return false;

}

 /**
 * 阿里大于 发送短信
  * @param $mobile  手机号码
  * @param $code    验证码
  * @return bool    短信发送成功返回true失败返回false
  */
function realSendregSMS($mobile, $smsSign, $smsParam , $templateCode)
{
    //时区设置：亚洲/上海
    date_default_timezone_set('Asia/Shanghai');
//    //这个是你下面实例化的类
//    vendor('Alidayu.TopClient');
//    //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
//    vendor('Alidayu.ResultSet');
//    //这个是成功后返回的信息文件
//    vendor('Alidayu.RequestCheckUtil');
//    //这个是错误信息返回的一个php文件
//    vendor('Alidayu.TopLogger');
//    //这个也是你下面示例的类
//    vendor('Alidayu.AlibabaAliqinFcSmsNumSendRequest');

    $c = new \alidayu\TopClient;
    $config = F('sms','',TEMP_PATH);
    //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
    $c->appkey = $config['sms_appkey'];
    //App Secret的值也是在哪里一起的 你点击查看就有了
    $c->secretKey =$config['sms_secretKey'];
    //这个是用户名记录那个用户操作
    $req = new \alidayu\AlibabaAliqinFcSmsNumSendRequest;
    //代理人编号 可选
    $req->setExtend("123456");
    //短信类型 此处默认 不用修改
    $req->setSmsType("normal");
    //短信签名 必须
    $req->setSmsFreeSignName($smsSign);
    //短信模板 必须
    $req->setSmsParam($smsParam);
    //短信接收号码 支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，
    $req->setRecNum("$mobile");
    //短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。
    $req->setSmsTemplateCode($templateCode); // templateCode

    $c->format='json';
    //发送短信

    $resp = $c->execute($req);
    //短信发送成功返回True，失败返回false


    if ($resp && $resp->result)
    {
        return array('status'=>1 ,'msg'=>$resp->sub_msg.',msg:'.$resp->msg.'subcode:'.$resp->sub_code);
    }
    else
    {
        return array('status'=>-1 , 'msg'=>$resp->sub_msg);
    }
}


//阿里云通信 发送短信
function sendSmsByAliyun($mobile, $smsSign, $smsParam, $templateCode)
{

    $config = F('sms','',TEMP_PATH);
    $accessKeyId = $config['sms_appkey'];
    $accessKeySecret = $config['sms_secretKey'];
    //短信API产品名
    $product = "Dysmsapi";
    //短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    //暂时不支持多Region
    $region = "cn-hangzhou";

    //初始化访问的acsCleint
    $profile = \DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
    \DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient= new \DefaultAcsClient($profile);
    $request = new Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
    //必填-短信接收号码
    $request->setPhoneNumbers($mobile);
    //必填-短信签名
    $request->setSignName($smsSign);
    //必填-短信模板Code
    $request->setTemplateCode($templateCode);
    //选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam($smsParam);
    //选填-发送短信流水号*/
    //$request->setOutId("1234");

    //发起访问请求
    $resp = $acsClient->getAcsResponse($request);

    //短信发送成功返回True，失败返回false
    if ($resp && $resp->Code == 'OK') {
        return array('status' => 1, 'msg' => $resp->Code);
    } else {
        return array('status' => -1, 'msg' => $resp->Message . '. Code: ' . $resp->Code);
    }
}


/**
 *  调用接口返回快递编码
 * @param $code
 * @return mixed
 */
function getcode($code){
    $url= "http://www.kuaidi100.com/autonumber/auto?num=[$code]&key=[ETryLWuv4600]";
    $res= file_get_contents($url);
    return(json_decode($res,true)[0]['comCode']);
}

//根据订单单号查询快递信息
function kdcx($code){
    //参数设置
    $param = array("com"=>getcode($code),"num"=>$code);
    $post_data = array();
    $post_data["customer"] = '88328F284768FCCB61F4989D9BEC1A97';
    $key= 'ETryLWuv4600';
    $post_data["param"] = json_encode($param);

    $url='http://poll.kuaidi100.com/poll/query.do';
    $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
    $post_data["sign"] = strtoupper($post_data["sign"]);
    $o="";
    foreach ($post_data as $k=>$v)
    {
        $o.= "$k=".urlencode($v)."&";       //默认UTF-8编码格式
    }
    $post_data=substr($o,0,-1);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $result = curl_exec($ch);

    $data = str_replace("\"",'"',$result );
    return json_decode($data,true);
}

//获取顶级域名
function get_host(){
    $url   = $_SERVER['HTTP_HOST'];

    //如果为ip地址直接返回
    if(preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/',$url)){
        return $url;
    }

    $data = explode('.', $url);
    $co_ta = count($data);
    //判断是否是双后缀
    $zi_tow = true;
    $host_cn = 'com.cn,net.cn,org.cn,gov.cn';
    $host_cn = explode(',', $host_cn);
    foreach($host_cn as $host){
        if(strpos($url,$host)){
            $zi_tow = false;
        }
    }
    //如果是返回FALSE ，如果不是返回true
    if($zi_tow == true){
        $host = '.'.$data[$co_ta-2].'.'.$data[$co_ta-1];
    }else{
        $host = '.'.$data[$co_ta-3].'.'.$data[$co_ta-2].'.'.$data[$co_ta-1];
    }
    return $host;
}

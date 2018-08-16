<?php
/**
 * Created by PhpStorm
 * User: ${fzq}
 * Date: 2018/1/28 0028
 * Time: 21:45
 * Description:
 */

namespace client;

use Grpc;
use Psp;

class GrpcClient
{
    //保存例实例在此属性中
    private static $_instance;

    //构造函数声明为private,防止直接创建对象
    private function __construct()
    {
        parent::__construct();
    }

    //单例方法
    public static function singleton()
    {

        if(!isset(self::$_instance))
        {
            $c = __CLASS__;

            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    //阻止用户复制对象实例
    public function __clone()
    {
        trigger_error('Clone is not allow' ,E_USER_ERROR);
    }

    //账务
    public function getAccount($host){
        $host = empty($host) ? C('Account') : $host;
        $client = new Psp\Account\AccountServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }

    //交易
    public function getTrade($host){
        $host = empty($host) ? C('Trade') : $host;
        $client = new Psp\Trade\TradeServicceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }

    //商家订单
    public function getSellerOrder($host){
        $host = empty($host) ? C('SellerOrder') : $host;
        $client = new Psp\Trade\SellerOrderServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }

    /**
     * 后台订单
     */
    public static function getAdminOrder($host)
    {
        $host = empty($host) ? C('AdminOrder') : $host;
        $client = new Psp\Trade\AdminOrderServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);;
        return $client;
    }

    //原商品中心
    public function getItem($host){
        $host = empty($host) ? C('Item') : $host;
        $client = new Psp\Item\ItemServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }

    //分类
    public function getCat($host){
        $host = empty($host) ? C('Cat') : $host;
        $client = new Psp\Item\ItemCatServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }

    //后台用户
    public function getUser($host){
        $host = empty($host) ? C('User') : $host;
        $client = new Psp\User\PlatfromUserServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        return $client;
    }


}

// 下面将得到GrpcClient类的单例对象
$test = GrpcClient::singleton();
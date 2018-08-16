<?php
/**
 * itemClient.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/19 0019 下午 1:21
 */

namespace client;

use Grpc;
use Psp;

class itemClient
{
    private static $_Item;

//构造函数声明为private,防止直接创建对象
    private function __construct()
    {
        parent::__construct();
    }



    public static function itemConn()
    {
        if(!isset(self::$_Item))
        {
            self::$_Item= new Psp\Item\ItemServiceClient(C('Item'), ['credentials' => Grpc\ChannelCredentials::createInsecure()]);

        }
        return self::$_Item;
    }



//阻止用户复制对象实例
    public function __clone()
    {
        trigger_error('Clone is not allow' ,E_USER_ERROR);
    }
}
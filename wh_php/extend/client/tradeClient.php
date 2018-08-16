<?php
/**
 * tradeClient.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/19 0019 下午 1:22
 */

namespace client;

use Grpc;
use Psp;


class tradeClient
{
    private static $_Trade;

//构造函数声明为private,防止直接创建对象
    private function __construct()
    {
        parent::__construct();
    }


    public static function tradeConn()
    {
        if(!isset(self::$_Trade))
        {
            self::$_Trade= new Psp\Trade\TradeServiceClient(C('Trade'),['credentials' => Grpc\ChannelCredentials::createInsecure()]);

        }
        return self::$_Trade;
    }



//阻止用户复制对象实例
    public function __clone()
    {
        trigger_error('Clone is not allow' ,E_USER_ERROR);
    }

}
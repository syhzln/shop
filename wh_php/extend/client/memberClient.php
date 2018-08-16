<?php
/**
 * memberClient.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/19 0019 下午 2:18
 */

namespace client;
use Grpc;
use Psp;

class memberClient
{
    private static $_User;

//构造函数声明为private,防止直接创建对象
    private function __construct()
    {
        parent::__construct();
    }


    public static function userConn()
    {
        if(!isset(self::$_User))
        {
            self::$_User= new Psp\Member\UserServiceClient(C('Member'), ['credentials' => Grpc\ChannelCredentials::createInsecure()]);

        }
        return self::$_User;
    }



//阻止用户复制对象实例
    public function __clone()
    {
        trigger_error('Clone is not allow' ,E_USER_ERROR);
    }

}
<?php
/**
 * grpcdriver.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/17 0017 下午 5:14
 */

namespace client;
use Grpc;
use Psp;

class grpcHelper
{

//保存例实例在此属性中
    private static $_conn;
    private static $globalGrpcConnectionMap=[];
    private static $globalGrpcConnectionMapLock;


    public static function GetGrpcConnection($name) {


        if(!self::$_conn){
//            Mutex::lock(self::$globalGrpcConnectionMapLock);
            if (!in_array(getmypid(),self::$globalGrpcConnectionMap)) {

                self::$globalGrpcConnectionMap[getmypid()] = new \client\GrpcClientMap();
            }
            self::$_conn = self::$globalGrpcConnectionMap[getmypid()]->getConn($name);
//            Mutex::unlock(self::$globalGrpcConnectionMapLock);
        }
        return self::$_conn;
    }



//构造函数声明为private,防止直接创建对象
//    private function __construct()
//    {
//        parent::__construct();
//    }


//阻止用户复制对象实例
    public function __clone()
    {
        trigger_error('Clone is not allow' ,E_USER_ERROR);
    }


}
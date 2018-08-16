<?php
/**
 * 部署初始化内容
 * Author: Ning<nk11@qq.com>
 * Date: 2018/2/7 0007
 * Time: 11:05
 */


//@todo 此接口定义为安装校验,所有初始化,步骤在此完成后,方介入主站

namespace app\home\controller;

use Grpc;
use Psp;
use app\admin\logic\AccountCenterLogic;
use think\facade\Cache;


class Initinstall
{
    public function index(){
        $accountLogic = new AccountCenterLogic();
        //@todo 一,先从index.php 定义常量PALTFORM

        //@todo 二,初始化账户
            //@todo 1先查询有没有相关主账户
            //@todo 2批量查询是否有相关子账号 PS.商家,会员,有弹出提示
            //@todo========================================
            //@todo  如果以上任何一个存在,则弹出提示,之后根据勾选项删除或者做保留迁移
            //(正常情况下不应该存在该类型数据)

            //@todo next->初始化生成平台账户(必须)
            //@todo next->初始化生成商家账户
            //@todo next->初始化生成会员数据
            //@todo PS. 可将以上信息作为勾选形式,一次性比对生成


        //@todo PS.
        //平台缓冲账户
        $P31 = $accountLogic->getAccount(PLATFORM,3,31,PLATFORM);
        //平台退换货账户
        $P32 = $accountLogic->getAccount(PLATFORM,3,32,PLATFORM);
        //平台收益账户
        $P33 = $accountLogic->getAccount(PLATFORM,3,33,PLATFORM);
        //平台分红账户
        $P34 = $accountLogic->getAccount(PLATFORM,3,34,PLATFORM);

        if(!$P31&&!$P32&&!$P33&&!$P34){
            //todo 调用初始化接口,初始化生成相关账户

        }
        else{
            //todo 递出错误

        }

    }

}
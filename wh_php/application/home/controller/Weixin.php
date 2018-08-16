<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\controller;

use app\common\logic\WechatLogic;

class Weixin
{
    /**
     * 处理接收推送消息
     */
    public function index()
    {
        $logic = new WechatLogic;
        $logic->handleMessage();
    }
    
}
<?php
/**
 * Testarea.php
 * Description:
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/10/30 0030 下午 3:54
 */

namespace app\home\controller;


class Testarea
{
    public function index()
    {
        $ar = new \area\area();
        dump($ar->getProv());
    }
}
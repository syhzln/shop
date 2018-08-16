<?php
/**
 * WALHAO商城
 * @Authorhtl {Ning<nk11@qq.com>}
 * @DateTime  2017-01-07T12:28:30+0800
 */

// 应用入口文件
// 应用入口文件
if (extension_loaded('zlib')){
    ob_end_clean();
    ob_start('ob_gzhandler');
}

error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);
// 定义应用目录
define('APP_PATH','./Application/');
//  定义插件目录
define('PLUGIN_PATH','plugins/');

define('UPLOAD_PATH','Public/upload/'); // 编辑器图片上传路径
define('TPSHOP_CACHE_TIME',86400); // TPshop 缓存时间  31104000
define('SITE_URL','http://'.$_SERVER['HTTP_HOST']); // 网站域名

define('HTML_PATH','./Application/Runtime/Html/'); //静态缓存文件目录，HTML_PATH可任意设置，此处设为当前项目下新建的html目录
define('HTML_PATH_HOME','./Application/Runtime/Cache/Home/'); //前台静态缓存文件目录，

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';


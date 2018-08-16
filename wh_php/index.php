<?php
/**
 * ============================================================================
 * 版权所有 杭州电子商务有限公司
 * 网站地址: https://www.walhao.com
 * ============================================================================
 * $Author: fzq 2017-10-12
 */
// [ 应用入口文件 ]
if (extension_loaded('zlib')){
    ob_end_clean();
    ob_start('ob_gzhandler');
}

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.4.0','<'))  die('require PHP > 5.4.0 !');
//error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
error_reporting(E_ERROR | E_WARNING | E_PARSE);//报告运行时错误

//  定义插件目录
define('PLUGIN_PATH', __DIR__ . '/plugins/');
define('UPLOAD_PATH','public/upload/'); // 编辑器图片上传路径
define('WALHAO_CACHE_TIME',86400); //  缓存时间  31104000
define('SITE_URL','http://'.$_SERVER['HTTP_HOST']); // 网站域名
//define('SITE_URL','http://118.31.42.205'); // 测试使用ip
//define('HTML_PATH','./Application/Runtime/Html/'); //静态缓存文件目录，HTML_PATH可任意设置，此处设为当前项目下新建的html目录
// 定义应用目录
define('APP_PATH', __DIR__ . '/application/');
define('PLATFORM', '1');//预定义平台id为常量

// 定义时间
define('NOW_TIME',$_SERVER['REQUEST_TIME']);
// 加载框架引导文件

define('APP_DEBUG', true);
define('LOG_RECORD', true);
define('LOG_LEVEL', 'EMERG,ALERT,CRIT,ERR,WARN');

require __DIR__ . '/thinkphp/start.php';

<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-09
 */
namespace app\admin\controller;

use think\Page;
use app\admin\logic\ArticleCatLogic;
use think\Db;

class Article extends Base
{

    private $article_system_id = array(1, 2, 3, 4, 5);//系统默认的文章分类id，不可删除
    private $article_main_system_id = array(1, 2);//系统保留分类，不允许在该分类添加文章
    private $article_top_system_id = array(1);//系统分类，不允许在该分类添加新的分类
    private $article_able_id = array(1);//系统预定义文章id，不能删除。此文章用于商品详情售后服务

    public function _initialize()
    {
        parent::_initialize();
        $this->assign('article_top_system_id', $this->article_top_system_id);
        $this->assign('article_system_id', $this->article_system_id);
        $this->assign('article_main_system_id', $this->article_main_system_id);
        $this->assign('article_able_id',$this->article_able_id);
    }

    public function categoryList()
    {
//        $ArticleCat = new ArticleCatLogic();
//        $cat_list = $ArticleCat->article_cat_list(0, 0, false);
//        dump($cat_list);exit;
        $GetArticleCategoryList=include_once APP_PATH."conf/categoryList.php";
        
        
        $type_arr = array('系统默认','系统帮助','系统公告');
        adminOperateLog('文章分类列表',5);
        $this->assign('type_arr',$type_arr);
        $this->assign('cat_list',$GetArticleCategoryList);
        return $this->fetch('categoryList');
    }

    public function category()
    {
        $ArticleCat = new ArticleCatLogic();
//        $act = I('get.act', 'add');
        $cat_id = I('get.cat_id/d');
//        dump($cat_id);exit;
//        $parent_id = I('get.parent_id/d');
        if ($cat_id) {
            $GetArticleCategoryInfo = array(
                "cat_id" =>"2",
                "cat_name" =>  "商品管理",
                "parent_id" =>"1",
                "name" =>  "bbb",
                "level" => "1",
                "id" => "2",
                "show_in_nav" => "1",
                "sort_order" => "50",
                "keywords" => "xxx",
                "cat_desc" => "qqq",
            );
//            $cat_info = D('article_cat')->where('cat_id=' . $cat_id)->find();
            $parent_id = $GetArticleCategoryInfo['parent_id'];
            $this->assign('cat_info', $GetArticleCategoryInfo);
        }
        adminOperateLog('文章分类',5);
        $cats = $ArticleCat->article_cat_list(0, $parent_id, true);
//        $this->assign('act', $act);
        $this->assign('cat_select', $cats);
        return $this->fetch();
    }

    public function articleList()
    {
//        
        $GetArticleCategoryName=include_once APP_PATH."conf/categoryList.php";
        
        $this->assign('cats',$GetArticleCategoryName);
       
        $GetArticleList=include_once APP_PATH."conf/articleList.php";

    
        $count = count($GetArticleList);
        adminOperateLog('文章列表',5);
        $this->assign('count',$count);
        $this->assign('list',$GetArticleList);// 赋值数据集
        // $this->assign('page',$page);// 赋值分页输出
        // $this->assign('pager',$pager);
        return $this->fetch('articleList');
    }

    public function article()
    {
        
        $act = I('get.');
       
        $arr=include APP_PATH."conf/articleList.php";
       
       // echo "<pre>";
       // echo print_r($arr);die;
       foreach($arr as $k =>$val){
       //  echo "<pre>";
       // echo print_r($val);
            if($val['article_id']==$act['article_id']){
                $GetArticleInfo=$val;
                continue;
                }

            }
       
            $cats=include_once APP_PATH."conf/categoryList.php";
        adminOperateLog('新增文章',5);
            $this->assign('cats',$cats);
        $this->assign('cat_select',$cats);
//        $this->assign('cat_select2',$cats2);
//

        $this->assign('act',$act);
        $this->assign('info',$GetArticleInfo);
        $this->initEditor();
        return $this->fetch();
    }

    /**
     * 初始化编辑器链接
     * @param $post_id post_id
     */

    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'article')));
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'article')));
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'article')));
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'article')));
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'article')));
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'article')));
        $this->assign("URL_Home", "");
    }

    public function categoryHandle()
    {
        
        $data = I('post.');//收传递数据
        //判断是否是删除
        if($data['act'] == 'del'){
        $arr=include_once APP_PATH."conf/categoryList.php";
        foreach($arr as $k =>$val){
    
            if($val['cat_id']==$data['cat_id']){
                array_splice($arr,$k,1);
                }

            }
        $str = "<?php\n/*\n * "." 号平台categoryList配置文件;\n * ".date('Y/m/d H:i:s').";\n * Author:Zhang;\n */\n\nreturn  [\n";
        
            foreach ($arr as $k => $val){
                foreach($val as $j=>$v ){
                    if($j=="name"){
                        $str.="\n[";
                    }
                    if($j=="cat_id"){
                     $str .= "    '{$j}' => '{$v}',\n";
                        $str.="\n],";
                        continue;
                    }
                    $str .= "    '{$j}' => '{$v}',\n";
                }
        };
        
        $str .= "\n];";
        file_put_contents(APP_PATH."conf/categoryList.php", $str);
            return 1;
        }
        
       $numbers = range (1,100);
    //shuffle 将数组顺序随即打乱
    shuffle ($numbers);
    //array_slice 取该数组中的某一段
    //创建不重复的随机数作为ID
    $result = array_slice($numbers,0,1);
    $data['cat_id']=$result['0'];
       
       $arr=include_once APP_PATH."conf/categoryList.php";
       $arr[]=$data;
       

       $str = "<?php\n/*\n * "." 号平台categoryList配置文件;\n * ".date('Y/m/d H:i:s').";\n * Author:Zhang;\n */\n\nreturn  [\n";
        
            foreach ($arr as $k => $val){
                foreach($val as $j=>$v ){
                    if($j=="name"){
                        $str.="\n[";
                    }
                    if($j=="cat_id"){
                     $str .= "    '{$j}' => '{$v}',\n";
                        $str.="\n],";
                        continue;
                    }
                    $str .= "    '{$j}' => '{$v}',\n";
                }
        };
        adminOperateLog('添加文章',5);
        $str .= "\n];";
        file_put_contents(APP_PATH."conf/categoryList.php", $str);
        $this->success("添加成功",url('Admin/Article/categoryList'));
        
    }

    public function aticleHandle()
    {
       $data = I('post.');//收传递数据
       // echo "<pre>";
       // var_dump($data);die;
       $arr=include_once APP_PATH."conf/articleList.php";
       foreach($arr as $k=>$v){
             if($v['article_id']==$data['article_id']){
                array_splice($arr,$k,1);
             }
       }//删除原来数组中的元素
       $numbers = range (1,500);
    //shuffle 将数组顺序随即打乱
    shuffle ($numbers);
    //array_slice 取该数组中的某一段
    //创建不重复的随机数作为ID
    $result = array_slice($numbers,0,1);
    $data['article_id']=$result['0'];
       
       
       $arr[]=$data;
       

       $str = "<?php\n/*\n * "." 号平台articleList配置文件;\n * ".date('Y/m/d H:i:s').";\n * Author:Zhang;\n */\n\nreturn  [\n";
        
            foreach ($arr as $k => $val){
                foreach($val as $j=>$v ){
                    if($j=="title"){
                        $str.="\n[";
                    }
                    if($j=="article_id"){
                     $str .= "    '{$j}' => '{$v}',\n";
                        $str.="\n],";
                        continue;
                    }
                    $str .= "    '{$j}' => '{$v}',\n";
                }
        };
        adminOperateLog('文章编辑',5);
        $str .= "\n];";
        file_put_contents(APP_PATH."conf/articleList.php", $str);
        $this->success("添加成功",url('Admin/Article/articleList'));


        
        
    }
    public function aticleDelete()
    {
        
           $data = I('post.');
         
        if($data['act'] == 'del'){
        $arr=include_once APP_PATH."conf/articleList.php";
       
       // echo "<pre>";
       // echo print_r($arr);die;
       foreach($arr as $k =>$val){
       //  echo "<pre>";
       // echo print_r($val);
            if($val['article_id']==$data['article_id']){
                array_splice($arr,$k,1);
                }

            }
        }
       
        $str = "<?php\n/*\n * "." 号平台articleList配置文件;\n * ".date('Y/m/d H:i:s').";\n * Author:Zhang;\n */\n\nreturn  [\n";
        
            foreach ($arr as $k => $val){
                foreach($val as $j=>$v ){
                    if($j=="title"){
                        $str.="\n[";
                    }
                    if($j=="article_id"){
                     $str .= "    '{$j}' => '{$v}',\n";
                        $str.="\n],";
                        continue;
                    }
                    $str .= "    '{$j}' => '{$v}',\n";
                }
        };
        
        $str .= "\n];";
        file_put_contents(APP_PATH."conf/articleList.php", $str);

        adminOperateLog('删除文章',5);
        exit(json_encode(1));
    }

    public function link()
    {
        $act = I('get.act','add');
        $this->assign('act',$act);
        $link_id = I('get.link_id/d');
        if($link_id){
            $GetLinkInfo = array(
                "link_name" => "qqq",
                "link_id" => "1",
                "link_url" => "www.walhao.com",
                "orderby" => "20",
                "target" => "1",
                "is_show" => "1",
                "link_logo" => "a/b/c.jpg"
            );
        }
        $this->assign('info',$GetLinkInfo);
        return $this->fetch();
    }

    public function linkList()
    {
//        $Ad =  M('friend_link');
//        $p = $this->request->param('p');
//        $res = $Ad->order('orderby')->page($p.',10')->select();
//        if($res){
//            foreach ($res as $val){
//                $val['target'] = $val['target']>0 ? '开启' : '关闭';
//                $list[] = $val;
//            }
//        }
        $GetLinkList = array(
            array(
                "link_name" => "qqq",
                "link_id" => "1",
                "link_url" => "www.walhao.com",
                "orderby" => "20",
                "target" => "开启",
                "is_show" => "1",
            )
        );
        $count = count($GetLinkList);//友情链接条数
        adminOperateLog('友情链接列表',5);
        $this->assign('count',$count);
        $this->assign('list',$GetLinkList);// 赋值数据集
//        $count = $Ad->count();// 查询满足要求的总记录数
//        $Page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//        $show = $Page->show();// 分页显示输出
//        $this->assign('pager',$Page);// 赋值分页输出
//        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    public function linkHandle()
    {
//        $data = I('post.');
        $data = array(
            "link_name" => "qqq",
            "link_id" => "1",
            "link_url" => "www.walhao.com",
            "orderby" => "20",
            "target" => "1",
            "is_show" => "1",
            "link_logo" => "a/b/c.jpg",
            "act" => "add", // edit | add | del
        );
        if($data['act'] == 'add'){
            stream_context_set_default(array('http'=>array('timeout' =>2)));
//            send_http_status('311');
            $AddLink = $data;
        }
        if($data['act'] == 'edit'){
            $UpdateLink = $data;
        }

        if($data['act'] == 'del'){
            $DelLink =  $data['link_id'];
            if($DelLink) exit(json_encode(1));
        }
        adminOperateLog('编辑友情链接',5);

//        if($r){
//            $this->success("操作成功",U('Admin/Article/linkList'));
//        }else{
//            $this->error("操作失败",U('Admin/Article/linkList'));
//        }
    }
}
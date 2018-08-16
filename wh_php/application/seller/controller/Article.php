<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * Date: 2015-09-09
 */
namespace app\seller\controller;

use think\Page;
use app\seller\logic\ArticleCatLogic;
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
        $GetArticleCategoryList = array(
            array(
                "cat_id" => "0",
                "cat_name" => "推荐管理",
                "name" =>  "推荐管理",
                "parent_id" => "0",
                "level" => "0",
                "id" => "0",
                "sort_order" => "50",
            ),
            array(
                "cat_id" => "1",
                "cat_name" => "xxxx",
                "parent_id" => "0",
                "name" =>  "aaa",
                "level" => "0",
                "id" => "1",
                "sort_order" => "50",
             ),
            array(
                "cat_id" =>"2",
                "cat_name" =>  "商品管理",
                "parent_id" =>"1",
                "name" =>  "bbb",
                "level" => "1",
                "id" => "2",
                "sort_order" => "50",
             )
        );
        $type_arr = array('系统默认','系统帮助','系统公告');
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
        $cats = $ArticleCat->article_cat_list(0, $parent_id, true);
//        $this->assign('act', $act);
        $this->assign('cat_select', $cats);
        return $this->fetch();
    }

    public function articleList()
    {
//        $Article =  M('Article');
//        $list = array();
//        $p = input('p/d', 1);
//        $size = input('size/d', 20);
//        $where = array();
//        $keywords = trim(I('keywords'));
//        $keywords && $where['title'] = array('like', '%' . $keywords . '%');
//        $cat_id = I('cat_id/d',0);
//        $cat_id && $where['cat_id'] = $cat_id;
//        $res = $Article->where($where)->order('article_id desc')->page("$p,$size")->select();
//        $count = $Article->where($where)->count();// 查询满足要求的总记录数
//        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
//        $page = $pager->show();//分页显示输出
//
//        $ArticleCat = new ArticleCatLogic();
//        $cats = $ArticleCat->article_cat_list(0,0,false);
//        if($res){
//          foreach ($res as $val){
//              $val['category'] = $cats[$val['cat_id']]['cat_name'];
//              $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
//              $list[] = $val;
//          }
//        }
        $GetArticleCategoryName = array(
            array(
                "cat_id" => "40",
                "cat_name" => "产品相关"
            ),
            array(
                "cat_id" => "39",
                "cat_name" =>  "xxx"
            ),
            array(
                "cat_id" => "222",
                "cat_name" => "sss"
            )
        );
        $this->assign('cats',$GetArticleCategoryName);
//        $this->assign('cat_id',$cat_id);
        $GetArticleList = array(
            array(

                "title" => "添加后台菜单",
                "category" => $GetArticleCategoryName[0]['cat_name'],
                "is_open" => "1",
                "add_time" => "11111",
                "article_id" => "10"
            )
        );
        $this->assign('list',$GetArticleList);// 赋值数据集
//        $this->assign('page',$page);// 赋值分页输出
//        $this->assign('pager',$pager);
        return $this->fetch('articleList');
    }

    public function article()
    {
        $ArticleCat = new ArticleCatLogic();
        $act = I('get.act','add');
//        $info = array();
//        $info['publish_time'] = time()+3600*24;
//        $article_id = I('get.article_id/d');
        if( input('param.article_id')){
//           $info = D('article')->where('article_id', $article_id)->find();
            $GetArticleInfo = array(
                "keywords" => "xx",
                "title" => "添加后台菜单",
                "category" => "云服务",
                "is_open" => "1",
                "publish_time" => "11111",
                "article_id" => "1",
                "link" => "www.baidu.com",
                "description" => "bbbb",
                "content" => "cccc",
                "act" => "edit"
            );
            $this->assign('cate',$GetArticleInfo['category']);
        }

        $cats = $ArticleCat->article_cat_list(0,$GetArticleInfo['cat_id']);    //第一个分类
//        $cats2 = $ArticleCat->article_cat_list(0,$info['cat_id2']); //第二个分类

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
//        $data = I('post.');
        $data = array(
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
            "act" => "del", //del | edit |add
        );
        if($data['act'] == 'add'){
            $AddArticleCategory = $data;
        }

        if($data['act'] == 'edit')
        {
            if(array_key_exists($data['cat_id'], $this->article_system_id) && $data['parent_id'] > 1){
                $this->error("不可更改系统预定义分类的上级分类",U('Admin/Article/category',array('cat_id'=>$data['cat_id'])));
            }
            if ($data['cat_id'] == $data['parent_id'])
            {
                $this->error("所选分类的上级分类不能是当前分类",U('Admin/Article/category',array('cat_id'=>$data['cat_id'])));
            }
            $ArticleCat = new ArticleCatLogic();
            $children = array_keys($ArticleCat->article_cat_list($data['cat_id'], 0, false)); // 获得当前分类的所有下级分类
            if (in_array($data['parent_id'], $children))
            {
                $this->error("所选分类的上级分类不能是当前分类的子分类",U('Admin/Article/category',array('cat_id'=>$data['cat_id'])));
            }
            $data = array(
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

            $UpdateArticleCategory = $data;
        }

        if($data['act'] == 'del'){
            if(array_key_exists($data['cat_id'],$this->article_system_id)){
                exit(json_encode('系统预定义的分类不能删除'));
            }
            $res = D('article_cat')->where('parent_id', $data['cat_id'])->select();
            if ($res)
            {
                exit(json_encode('还有子分类，不能删除'));
            }
            $res = D('article')->where('cat_id', $data['cat_id'])->select();
            if ($res)
            {
                exit(json_encode('该分类下有文章，不允许删除，请先删除该分类下的文章.'));
            }
            $r = D('article_cat')->where('cat_id', $data['cat_id'])->delete();
            if($r) exit(json_encode(1));
        }
//        if($d){
//            $this->success("操作成功",U('Admin/Article/categoryList'));
//        }else{
//            $this->error("操作失败",U('Admin/Article/categoryList'));
//        }
    }

    public function aticleHandle()
    {
//        $data = I('post.');
        $data = array(
            "keywords" => "xx",
            "title" => "添加后台菜单",
            "category" => "云服务",
            "is_open" => "1",
            "publish_time" => "11111",
            "article_id" => "1",
            "link" => "www.baidu.com",
            "description" => "bbbb",
            "content" => "cccc",
            "act" => "edit" , // edit | del | add
        );
        $data['content'] = I('content'); // 文章内容单独过滤
        $data['publish_time'] = strtotime($data['publish_time']);
        $url = $this->request->server('HTTP_REFERER');
        $referurl = !empty($url) ? $url : U('Admin/Article/articleList');
        $data['content'] = htmlspecialchars(stripslashes($_POST['content']));
        if($data['act'] == 'add'){
            if(array_key_exists($data['cat_id'],$this->article_main_system_id)){
                $this->error("不能在系统保留分类下添加文章,操作失败",$referurl);
            }
            $data['click'] = mt_rand(1000,1300);
            $data['add_time'] = time();
            $AddArticle = $data;
        }

        if($data['act'] == 'edit'){
            $UpdateArticle = $data;
        }

        if($data['act'] == 'del'){

//            if(array_key_exists($data['article_id'],$this->article_able_id)){
//                exit(json_encode('系统预定义的文章不能删除'));
//            }
//          $r = D('article')->where('article_id', $data['article_id'])->delete();
//          if($r) exit(json_encode(1));
            $DelArticle = $data['article_id'];
            return $DelArticle;
        }
//        if($r){
//            $this->success("操作成功",$referurl);
//        }else{
//            $this->error("操作失败",$referurl);
//        }
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

//        if($r){
//            $this->success("操作成功",U('Admin/Article/linkList'));
//        }else{
//            $this->error("操作失败",U('Admin/Article/linkList'));
//        }
    }
}
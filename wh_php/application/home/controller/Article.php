<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller;
use app\home\logic\CartLogic;
use app\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;

class Article extends Base {
    
    public function index(){
        $article_id = I('article_id/d',38);
//    	$article = D('article')->where("article_id", $article_id)->find();
//    	$this->assign('article',$article);
        return $this->fetch();
    }

    /**
     * 文章内列表页
     */
    public function articleList(){
//        $article_cat = M('ArticleCat')->where("parent_id  = 0")->select();
//        $this->assign('article_cat',$article_cat);
        return $this->fetch();
    }
    /**
     * 文章内容页
     */
    public function detail(){
    	$article_id = I('get.');

//    	$article = D('article')->where("article_id", $article_id)->find();
      $arr=include APP_PATH."conf/articleList.php";
       
       // echo "<pre>";
       // echo print_r($arr);die;
       foreach($arr as $k =>$val){
       //  echo "<pre>";
       // echo print_r($val);
       //var_dump($val['article_id']);
            if($val['article_id']==$article_id['article_id']){
                $GetArticleInfo=$val;
                continue;
                }

            }
            
        
    	if($GetArticleInfo){
            $GetArticleCategoryName = '111';
//            $parent = D('article_cat')->where("cat_id",$article['cat_id'])->find();
//            $this->assign('cat_name',$parent['cat_name']);
            $GetArticleCategoryParentInfo =include APP_PATH."conf/categoryList.php";
//            foreach ($GetArticleCategoryParentInfo as $k => $v){
//                $article[] = D('article')->where("cat_id",$v['cat_id'])->select();
////                $article[] = $GetArticleInfo;
//            }
            $article = include APP_PATH."conf/articleList.php";
            $this->assign('article1',$article);
            $this->assign('cat_name',$GetArticleCategoryName);
            $this->assign('par',$GetArticleCategoryParentInfo);
            $this->assign('article',$GetArticleInfo);
        }

        return $this->fetch();
    }

}
<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb
 * 专题管理
 * Date: 2015-09-09
 */
namespace app\admin\controller;

use think\Page;

class Topic extends Base
{

    public function index()
    {
        return $this->fetch();
    }

    public function topic()
    {
        $act = I('get.act','add');
        $this->assign('act',$act);
        $topic_id = I('get.topic_id');
//    	dump($topic_id);exit;
        $topic_info = array();
        if($topic_id){
            $GetTopicInfo = array(
                "topic_title" => "aaa",
                "topic_state" => "bbb",
                "ctime" => "ccc",
                "topic_image" => "ddd",
                "topic_id" => "1",
                "topic_margin_top" => "10",
                "topic_content" => "eee"

            );
            $this->assign('info',$GetTopicInfo);
        }

        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'topic')));
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'topic')));
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'topic')));
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'topic')));
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'topic')));
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'topic')));
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'topic')));
        $this->assign("URL_Home", "");
        return $this->fetch();
    }

    public function topicList()
    {
        $GetTopic = array(
            array(
                "topic_title" => "aaa",
                "topic_state" => "2",
                "ctime" => "1508727018",
                "topic_image" => "ddd",
                "topic_id" => "1"
            )

        );
        $count = count($GetTopic);//统计专题条数
//    	$Ad =  M('topic');
//	    $p = $this->request->param('p');
//    	$res = $Ad->order('ctime')->page($p.',10')->select();
    	if($GetTopic){
    		foreach ($GetTopic as $val){
    			$val['topic_state'] = $val['topic_state']>1 ? '已发布' : '未发布';
    			$val['ctime'] = date('Y-m-d H:i',$val['ctime']);
                $GetTopicList[] = $val;
    		}
    	}
        $this->assign('list',$GetTopicList);// 赋值数据集
        $this->assign('count',$count);//
//    	$count = $Ad->count();// 查询满足要求的总记录数
//    	$Page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//    	$show = $Page->show();// 分页显示输出
//	    $this->assign('pager',$Page);
//    	$this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    public function topicHandle()
    {
        $data = I('post.');
        $data['topic_content'] = $_POST['topic_content']; // 这个内容不做转义
        if($data['act'] == 'add'){
            $data['ctime'] = time();
            $AddTopic = $data;
        }
        if($data['act'] == 'edit'){
            $UpdateTopic=$data;

        }

        if($data['act'] == 'del'){
            $DelTopic = $data['topic_id'];
            if($DelTopic) exit(json_encode(1));
        }

//        if($r){
//            $this->success("操作成功",U('Admin/Topic/topicList'));
//        }else{
//            $this->error("操作失败",U('Admin/Topic/topicList'));
//        }
    }
}
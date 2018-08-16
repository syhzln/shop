<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * 评论管理控制器
 * Date: 2015-10-20
 */

namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use Grpc;
use Psp;

class Comment extends Base {


    public function index()
    {
        $username = htmlspecialchars(stripslashes(I('nickname','','trim')));
        $content = htmlspecialchars(stripslashes(I('content','','trim')));
        $title = htmlspecialchars(stripslashes(I('title','','trim')));

        $p = I('p/d',1);
        $request = new \Psp\Itm\CommentListRequest();
        $username&&$request->setUserName($username);
        $content&&$request->setContent($content);
        $title&&$request->setTitle($title);
        $pagination = grpcPage('comment_id',$p,20,false); //分页
        $request->setPagination($pagination);

        list($res,$status) = GRPC('itm')->getCommentList($request)->wait();

        if($res) {
            foreach ($res->getCommentList() as $k => $v) {
                $commentList[$k]['user_id'] = $v->getUserId();
                $commentList[$k]['user_name'] = $v->getUserName();
                $commentList[$k]['item_id'] = $v->getItemId();
                $commentList[$k]['item_name'] = $v->getItemName();
                $commentList[$k]['note'] = $v->getNote();
                $commentList[$k]['is_show'] = $v->getIsShow();
                $commentList[$k]['ip'] = $v->getIp();
                $commentList[$k]['order_id'] = $v->getOrderId();
                $commentList[$k]['start'] = $v->getStart();
                $commentList[$k]['service_start'] = $v->getServiceStart();
                $commentList[$k]['delivery_start'] = $v->getDeliveryStart();
                $commentList[$k]['thumb_ups'] = $v->getThumbUps();
                $commentList[$k]['comment_date'] = $v->getCommentDate()?$v->getCommentDate()->getSeconds():'';
                $commentList[$k]['comment_id'] = $v->getId();

            }
            //dump($commentList);
            $total_count =$res->getPaginationResult()->getTotalRecords();
            $Page = new Page($total_count,20);
            $show =$Page->show();

        }if($p ==1){
        adminOperateLog('商品评价列表',2);
    }
        $this->assign('page',$show);
        $this->assign('comment_list',$commentList);
        return $this->fetch();
    }

    public function detail()
    {
        $id = I('get.id/d');
        $ids = array();
        // 新增回复
        $data  = I('post.');
        if (IS_POST) {
            /*$client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
                'credentials' => Grpc\ChannelCredentials::createInsecure()
            ]);*/
            $time = new Psp\Timestamp();
            $time->setSeconds(time());
            $addReply = new Psp\Item\ReplyList();
            $addReply->setUserName('admin'); // 角色名称
            $addReply->setCommentId($id);//$id
            $addReply->setContent($data['content']);//ok
            $addReply->setGoodsId($data['goods_id']);//ok
            $addReply->setReplyTime($time);// ok
            $addReply->setToName($data['username']); //getMemberName();
            $addReply->setReplyToId('1'); //getMemberId();
            $addReply->setUserId('1'); //admin 的 id
            $addReply->setType('2'); //先取固定值

            list($res, $status) = GRPC('comment')->AddCommentReply($addReply)->wait();
            if($res->getValue()){
                $this->success('回复成功');
            } else {
                $this->error('回复失败，请重试');
            }

        }
        // 获得单个评论
        /*$client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/
        $cid = new Psp\Item\CommentId();
        $cid->setCommentId($id);
        list($res,$status) = GRPC('comment')->GetItemComment($cid)->wait();
        $comment['content'] = $res->getComment();
        $comment['user_id'] = $res->getMemberId();
        $comment['username'] = $res->getMemberName();
        $comment['goods_id'] = $res->getGoodsId();
        $comment['add_time'] = $res->getCommentDate()->getSecondS();
        $this->assign('comment',$comment);

        //获取回复
//        $client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
//            'credentials' => Grpc\ChannelCredentials::createInsecure()
//        ]);
        $cid = new Psp\Item\CommentId();
        $cid->setCommentId($id);
        list($res,$status) = GRPC('comment')->GetCommentReply($cid)->wait();
        if ($res){
            foreach ($res->getReply() as $k=>$v) {
                $reply[$k]['content'] = $v->getContent();
                $reply[$k]['add_time'] = $v->getReplyDate()->getSecondS();
                $reply[$k]['reply_id'] = $v->getReplyId();
                $reply[$k]['user_name'] = $v->getUserName();
                $ids[] = $v->getReplyId();
            }

        } else {
            $reply = 'aa'; //占位
        }

        if(Empty($ids)){
            $reply_to_id = '0';
        } else {
            $reply_to_id=array_search(max($ids),$ids);
        }
        adminOperateLog('商品评价详情',2);
        $this->assign('reply',$reply);
        $this->assign('reply_to_id',$reply_to_id);
        return $this->fetch();
    }


    public function del(){
        $id = I('get.id/d');
        /*$client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/
        $del = new Psp\Item\CommentId();
        $del->setCommentId($id);
        list($res,$status) = GRPC('comment')->DelSingleComment($del)->wait();
        if($res->getValue()){
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
        adminOperateLog('删除评价',2);
    }

    public function op(){
        $type = I('post.type');
        $selected_id = I('post.selected/a');
        if(!in_array($type,array('del','show','hide')) || !$selected_id)
            $this->error('非法操作');
        /*$client = new Psp\Item\ItemCommentServiceClient('127.0.0.1:9300', [
            'credentials' => Grpc\ChannelCredentials::createInsecure()
        ]);*/
        $op = new Psp\Item\CommentOption();
        $op->setAct($type);
        $op->setCommentId($selected_id[0]);
        list($res,$status) = GRPC('comment')->ItemCommentOption($op)->wait();
        if($res->getValue()){

            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }

    }

    public function ajaxIndex()
    {
    }
    
    public function ask_list(){
    	return $this->fetch();
    }

    public function consult_info(){
        // $id = I('get.id/d');
        // $res = M('goods_consult')->where(array('id'=>$id))->find();
        // if(!$res){
        //  exit($this->error('不存在该咨询'));
        // }
        // if(IS_POST){
        //  $add['parent_id'] = $id;
        //  $add['content'] = I('post.content');
        //  $add['goods_id'] = $res['goods_id'];
     //        $add['consult_type'] = $res['consult_type'];
        //  $add['add_time'] = time();
        //  $add['is_show'] = 1;
        //  $row =  M('goods_consult')->add($add);

        //  if($row){
        //      $this->success('添加成功');
        //  }else{
        //      $this->error('添加失败');
        //  }
        //  exit;
        // }
        // $reply = M('goods_consult')->where(array('parent_id'=>$id))->select();
        $AddAskReply=array(
                        "0" => Array
                            (
                                "id"=>'3045',
                                "goods_id" => '110',
                                "username"=>'yhb',
                                "content"=>'22222222222222222',
                                "consult_type"=>'1',
                                "add_time"=>'1464794764',
                                'is_show'=>'1',
                                "parent_id"=>'3045',
                            ),
                            "1" => Array
                            (
                                "id"=>'3045',
                                "goods_id" => '110',
                                "username"=>'zz',
                                "content"=>'989898',
                                "consult_type"=>'1',
                                "add_time"=>'1464794364',
                                'is_show'=>'1',
                                "parent_id"=>'3045',
                            ),
        );

        $this->assign('comment',$res);
        $this->assign('reply',$AddAskReply);
        return $this->fetch();
    }

    public function ask_handle()
    {
        $type = I('post.type');
        $selected_id = I('post.selected/a');
        if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id)
            $this->error('操作失败');
        $row = false;
        $selected_id = implode(',', $selected_id);
        /*if ($type == 'del') {
            //删除咨询
            $row = M('goods_consult')->where('id', 'IN', $selected_id)->whereOr('parent_id', 'IN', $selected_id)->delete();
        }
        if ($type == 'show') {
            $row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 1));
        }
        if ($type == 'hide') {
            $row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 0));
        }*/
        if($row !== false){
            $this->success('操作完成');
        }else{
            $this->error('操作失败');
        }
    }
}
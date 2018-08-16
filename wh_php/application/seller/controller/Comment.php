<?php
/**
 * ============================================================================
 *
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * 评论管理控制器
 * Date: 2015-10-20
 */

namespace app\seller\controller;

use think\AjaxPage;
use think\Page;
use Psp;
use Grpc;

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
        $request->setProviderId(STORE_ID);
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

            }
            //dump($commentList);
            $total_count =$res->getPaginationResult()->getTotalRecords();
            $Page = new AjaxPage($total_count,20);
            $show =$Page->show();

        }
        $this->assign('page',$show);
        $this->assign('comment_list',$commentList);
        return $this->fetch();
    }

    public function detail()
    {
        $this->error('暂不支持该功能');
//        $id = I('get.id/d');
//        $res = M('comment')->where(array('comment_id'=>$id))->find();
//        if(!$res){
//            exit($this->error('不存在该评论'));
//        }
//        if(IS_POST){
//            $add['parent_id'] = $id;
//            $add['content'] = I('post.content');
//            $add['goods_id'] = $res['goods_id'];
//            $add['add_time'] = time();
//            $add['username'] = 'admin';
//
//            $add['is_show'] = 1;
//
//            $row =  M('comment')->add($add);
//            if($row){
//                $this->success('添加成功');
//            }else{
//                $this->error('添加失败');
//            }
//            exit;
//
//        }
//        $AddCommentReply=array(
//            "0" => Array
//            (
//                "comment_id"=>'12228',
//                'goods_id'=>'110',
//                "username" => 'admin',
//                "content"=>'111111',
//                "is_show"=>'1',
//                "parent_id"=>'12228',
//                "add_time"=>'1486716688',
//
//            )
//        );
////        $reply = M('comment')->where(array('parent_id'=>$id))->select(); // 评论回复列表
//        $GetCommentInfo = array(
//            'res' => array(
//                array(
//                    "username" => 'jack',
//                    "content"=>'can u stop angry now',
//                    "add_time"=>'1486716688',
//                ),
//                array(
//                    "username" => 'jack',
//                    "content"=>'can u stop angry now~~~',
//                    "add_time"=>'1486716688',
//                ),
//            ),
//            'reply' => array(
//                array(
//                    "username" => 'admin',
//                    "content"=>'hao de',
//                    "add_time"=>'1486716688',
//                ),
//                array(
//                    "username" => 'admin',
//                    "content"=>'i m sorry',
//                    "add_time"=>'1486716688',
//                ),
//            )
//        );
//
//        $this->assign('comment',$GetCommentInfo['res']);
//        $this->assign('reply',$GetCommentInfo['reply']);
////        $this->assign('reply',$reply);
//        return $this->fetch();
    }


    public function del()
    {
        $id = I('get.id/d');
        $del = new Psp\Item\CommentId();
        $del->setCommentId($id);
        list($res,$status) = GRPC('comment')->DelSingleComment($del)->wait();
        if($res->getValue()){
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function op()
    {
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

    public function ask_list(){
        return $this->fetch();
    }

    public function ajax_ask_list(){
        /*$model = M('goods_consult');
    	$username = I('username','','trim');
    	$content = I('content','','trim');
    	$where=' parent_id = 0';
    	if($username){
    		$where .= " AND username='$username'";
    	}
    	if($content){
    		$where .= " AND content like '%{$content}%'";
    	}
        $count = $model->where($where)->count();
        $Page  = $pager = new AjaxPage($count,10);
        $show  = $Page->show();
        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	if(!empty($comment_list))
    	{
    		$goods_id_arr = get_arr_column($comment_list, 'goods_id');
    		$goods_list = M('Goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField("goods_id,goods_name");
    	}*/
        $GetSellerAskList=array(
            "0" => Array
            (
                "id"=>'3045',
                "goods_id" => '110',
                "goods_name"=>'Ronshen/容声 BCD-228D11SY 3/三门式电冰箱/三开门家用电脑温控',
                "username"=>'Addrienne',
                "content"=>'哈哈A simple and ineleligtnt point, well made. Thanks',
                "consult_type"=>'1',
                "add_time"=>'1464794764',
                'is_show'=>'1',
                "parent_id"=>'0',
            ),
        );
        $consult_type = array(0=>'默认咨询',1=>'商品咨询',2=>'支付咨询',3=>'配送',4=>'售后');
        $this->assign('consult_type',$consult_type);
        //$this->assign('goods_list',$goods_list);
        $this->assign('comment_list',$GetSellerAskList);
        //$this->assign('page',$show);// 赋值分页输出
        //$this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }

    public function consult_info(){
        //$id = I('get.id/d');
        /*$res = M('goods_consult')->where(array('id'=>$id))->find();
        if(!$res){
            exit($this->error('不存在该咨询'));
        }
        if(IS_POST){
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['consult_type'] = $res['consult_type'];
            $add['add_time'] = time();
            $add['is_show'] = 1;
            $row =  M('goods_consult')->add($add);
            if($row){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;
        }
        $reply = M('goods_consult')->where(array('parent_id'=>$id))->select(); // 咨询回复列表*/
        $AddSellerAskReply=array(
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
        $this->assign('reply',$AddSellerAskReply);
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
        if ($type == 'del') {
            //删除咨询
//            $row = M('goods_consult')->where('id', 'IN', $selected_id)->whereOr('parent_id', 'IN', $selected_id)->delete();
        }
        if ($type == 'show') {
//            $row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 1));
        }
        if ($type == 'hide') {
//            $row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 0));
        }
        if($row !== false){
            $this->success('操作完成');
        }else{
            $this->error('操作失败');
        }
    }
}
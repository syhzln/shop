<?php
/**
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: dyr
 * Date: 2016-08-09
 */

namespace app\common\logic;

use think\Model;
use app\common\logic\UsersLogic;
use Grpc;
use Psp;
use think\Page;

/**
 * 评论
 * Class CatsLogic
 * @package common\Logic
 */
class CommentLogic extends Model
{
	
	public function getCommentInfo($comment_id)
	{
		//$comment_info = M('comment')->where(array('comment_id'=>$comment_id))->find();
		//$reply = $this->getReplyPage($comment_id);
		//return array('comment_info'=>$comment_info,'reply'=>$reply);
	}
    
	/**
	 * 添加商品评论
	 * @param $order_id  订单id
	 * @param $goods_id  商品id
	 * @param $user_email用户邮箱地址
	 * @param $username  用户名
	 * @return bool
	 */
    public function addGoodsComment($add)
    {
        if (!$add['order_id'] || !$add['goods_id']) {
            return array('status'=>-1, 'msg'=>'非法操作');
        }

        //检查订单是否已完成
        //$order = M('order')->where(['order_id' => $add['order_id'], 'user_id' => $add['user_id']])->find();
        /*if ($order['order_status'] != 2) {
            return ['status'=>-1, 'msg'=>'该笔订单还未完成'];
        }*/

        //检查是否已评论过
       // $goods = M('comment')->where(['order_id' => $add['order_id'], 'goods_id' => $add['goods_id']])->find();
        /*if ($goods) {
            return ['status'=>-1, 'msg'=>'您已经评论过该商品'];
        }*/

        //$row = M('comment')->add($add);
        /*if (!$row) {
            return ['status'=>-1,'msg'=>'评论失败'];
        }*/
        
        //更新订单商品表状态
        //M('order_goods')->where(['goods_id'=>$add['goods_id'],'order_id'=>$add['order_id']])->save(['is_comment'=>1]);
        //M('goods')->where(['goods_id'=>$add['goods_id']])->setInc('comment_count',1); // 评论数加一
        //
        // 查看这个订单是否全部已经评论,如果全部评论了 修改整个订单评论状态
        /*$comment_count = M('order_goods')->where(['order_id' => $add['order_id'], 'is_comment' => 0])->count();
        if ($comment_count == 0) {
            // 如果所有的商品都已经评价了 订单状态改成已评价
            M('order')->where("order_id ='{$add['order_id']}'")->save(['order_status' => 4]);
        }*/

        return ['status'=>1,'msg'=>'评论成功'];
    }

    /**
     * 添加图片评论
     * @param type $order_id
     * @param type $goods_id
     * @param type $content
     * @param type $is_anonymous
     * @param type $goods_score
     * @param type $service_rank
     * @param type $deliver_rank
     * @param type $goods_rank
     */
    public function addGoodsCommentWithImages($user_id, $order_id, $goods_id, $content = '', 
            $is_anonymous=1, $service_rank=0, $deliver_rank=0, $goods_rank=0)
    { 
        // 晒图片        
        $img = $this->uploadCommentImgFile('comment_img_file');
        if ($img['status'] !== 1) {
            return $img;
        }
        
        $add['goods_rank']  = $goods_rank;
        $add['service_rank'] = $service_rank;
        $add['deliver_rank'] = $deliver_rank;
        $add['goods_id']    = $goods_id;
        $add['order_id']    = $order_id;
        $add['user_id']     = $user_id;
        $add['content']     = $content;//$add['content'] = htmlspecialchars(I('post.content'));
        $add['img']         = $img['result'];
        $add['add_time']    = time();
        $add['ip_address']  = getIP();
        $add['is_anonymous'] = $is_anonymous ? 1 : 0;
        $add['zan_num']     = 0;
        $add['parent_id']   = 0;

        //添加评论
        return $this->addGoodsComment($add);
    }  
    
    /**
     * 获取评论列表
     * @param $user_id 用户id
     * @param $status  状态 0 未评论 1 已评论 ,其他 全部
     * @return mixed
     */
    public function getComment($user_id,$state=2)
    {
//        var_dump($status);die;
//        if ($status == 1) {
            //已评论
            /*$query = M('comment')->alias('c')
                ->join('__ORDER__ o', 'o.order_id = c.order_id')
                ->join('__ORDER_GOODS__ og','c.goods_id = og.goods_id AND c.order_id = og.order_id AND og.is_comment=1')
                ->where('c.user_id', $user_id);*/
            $p = I('p/d', 1);


            $page = new Psp\Pagination();
            $page->setSortAsc(true);
            $page->setSortBy("order_date");
            $page->setIndex($p);
            $page->setLimit(3);
            $comment = new Psp\Member\GetPage();
            $comment->setUid($user_id);
            $comment->setPagination($page);

            list($res,$status) = GRPC('member')->GetUserComment($comment)->wait();
//            var_dump($res);die;
            if(!empty($res)){
                foreach ($res->getUserCommentList() as $k=>$v)
                {
                    if($state==2){
                        $arr[$k]['order_id'] = $v->getOrderId();
                        $arr[$k]['order_sn'] = $v->getOrderSn();
                        $arr[$k]['goods_name'] = $v->getGoodsName();
                        $arr[$k]['goods_price'] = round($v->getGoodsPrice(),2);
                        $arr[$k]['goods_img'] = $v->getGoodsImg();
                        $arr[$k]['order_status'] = $v->getOrderStatus();
                        $arr[$k]['add_time'] = $v->getAddTime()->getSeconds();
                        $arr[$k]['is_comment'] = $v->getStatus();
                        $arr[$k]['goods_id'] = $v->getGoodsId();
                        $arr[$k]['goods_num'] = $v->getGoodsNum();

                    }elseif($state==$v->getStatus()){
                        $arr[$k]['order_id'] = $v->getOrderId();
                        $arr[$k]['order_sn'] = $v->getOrderSn();
                        $arr[$k]['goods_name'] = $v->getGoodsName();
                        $arr[$k]['goods_price'] = round($v->getGoodsPrice(),2);
                        $arr[$k]['goods_img'] = $v->getGoodsImg();
                        $arr[$k]['order_status'] = $v->getOrderStatus();
                        $arr[$k]['add_time'] = $v->getAddTime()->getSeconds();
                        $arr[$k]['is_comment'] = $v->getStatus();
                        $arr[$k]['goods_id'] = $v->getGoodsId();
                        $arr[$k]['goods_num'] = $v->getGoodsNum();
                     }

                }
                //总条数
                $total_count = $res->getPageResult()->getTotalRecords();
            }
//            var_dump($arr);die;

            $Page = new Page($total_count, 3);
            $show = $Page->show();

//                        var_dump($arr2);die;
            /*$query2 = clone($arr);
            $commented_count = $arr->count();
            $page = new \think\Page($commented_count, 10);
            $comment_list = $query2->field('og.*,o.*')
                ->order('c.add_time', 'desc')
                ->limit($page->firstRow, $page->listRows)
                ->select();*/


      /*  } else {
            $comment_where = ['og.is_send'=>1];
            if ($status == 0) {
                $comment_where['og.is_comment'] = 0;
            }*/


            /*$query = M('order_goods')->alias('og')
                ->join('__ORDER__ o',"o.order_id = og.order_id AND o.user_id=$user_id AND o.order_status IN (2,4)")
                ->where($comment_where);*/

            /*$query2 = clone($query);
            $comment_count = $query->count();
            $page = new \think\Page($comment_count,10);
            $comment_list = $query2->field('og.*,o.*')
                ->order('o.order_id', 'desc')
                ->limit($page->firstRow,$page->listRows)
                ->select();*/

        /*$show = $page->show();*/
        $return['result'] = $arr;
        $return['show'] = $show; //分页
        $return['page'] = $page; //分页*/
        return $return;

    }
    
    /**
     * 把回复树状数组转换成二维数组
     * @param $comment_id 回复id
     * @param int $item_num 条数
     * @return array
     */
    public function getReplyListToArray($comment_id, $item_num = 0)
    {
        $reply_tree = $this->getReplyList($comment_id);
        if (empty($reply_tree)) {
            return $reply_tree;
        }
        $reply_flat_list = $this->treeToArray($reply_tree);
        if ($item_num == 0 || count($reply_flat_list) <= $item_num) {
            $res = $reply_flat_list;
        } else {
            $res = array_slice($reply_flat_list, 0, $item_num);
        }
        return $res;
    }

    /**
     * 回复分页
     * @param $comment_id
     * @param int $page
     * @param int $item_num
     * @return mixed
     */
    public function getReplyPage($comment_id, $page = 0, $item_num = 20)
    {
        $reply_tree = $this->getReplyList($comment_id);
        $reply_flat_list = $this->treeToArray($reply_tree);
        $count = count($reply_flat_list);
        $list['list'] = array_slice($reply_flat_list, $page * $item_num, $item_num);
        $list['count'] = $count;
        return $list;
    }

    /**
     * 将树状数组转换二维数组
     * @param $tree
     * @return array
     */
    public function treeToArray($tree)
    {
        $list = array();
        foreach ($tree as $key) {
            $node = $key['children'];
            unset($key['children']);
            $list[] = $key;
            if ($node) $list = array_merge($list, $this->treeToArray($node));
        }
        return $list;
    }

    /**
     * 根据评论id获取评论下的所有回复
     * @param $comment_id
     * @param int $parent_id
     * @param array $result
     * @return array
     */
    private function getReplyList($comment_id, $parent_id = 0, &$result = array())
    {
        $reply_where = array(
            'comment_id' => $comment_id,
            'parent_id' => $parent_id
        );
        /*$arr = M('reply')->where($reply_where)->order('reply_time desc')->select();
        if (empty($arr)) {
            return array();
        }
        foreach ($arr as $cm) {
            $thisArr =& $result[];
            $cm['children'] = $this->getReplylist($comment_id, $cm['reply_id'], $thisArr);
            $thisArr = $cm;
        }*/
        return $result;
    }
    
    /**
     * 获取已评论数
     * @param type $user_id
     * @return type
     */
    public function getHadCommentNum($user_id)
    {
        /*$num = M('comment')->alias('c')
                ->join('__ORDER__ o', 'o.order_id = c.order_id')
                ->join('__ORDER_GOODS__ g','c.goods_id = g.goods_id AND c.order_id = g.order_id AND g.is_comment=1')
                ->where('c.user_id', $user_id)
                ->count();
        return $num;*/
    }
    
    /**
     * 获取未(待)评论数
     */
    public function getWaitCommentNum($user_id)
    {
        (!$user_id) && $user_id = 0;
        
        /*$num = M('order_goods')->alias('og')
            ->join('__ORDER__ o',"o.order_id = og.order_id AND o.user_id=$user_id AND o.order_status IN (2,4)",'inner')
            ->where(['og.is_send' => 1, 'og.is_comment' => 0])
            ->count();
        return $num;*/
    }

    /**
     * 获取评论数
     * @param type $user_id
     * @return type
     */
    public function getCommentNum($user_id)
    {
        //已评价
        $data['had'] = $this->getHadCommentNum($user_id);

        //待评价
        $data['no'] = $this->getWaitCommentNum($user_id);

        return $data;
    }
    
    /**
     * 上传评论图片
     * @return type
     */
    public function uploadCommentImgFile($name)
    {
        $comments = '';
        if ($_FILES[$name]['tmp_name'][0]) {
            $files = request()->file($name);
            $validate = ['size'=>1024 * 1024 * 3,'ext'=>'jpg,png,gif,jpeg'];
            $dir = 'public/upload/comment/';
            if (!($_exists = file_exists($dir))) {
                mkdir($dir);
            }
            $parentDir = date('Ymd');
            foreach($files as $file){
                $info = $file->validate($validate)->move($dir, true);
                if($info) {
                    $filename = $info->getFilename();
                    $new_name = '/'.$dir.$parentDir.'/'.$filename;
                    $comment_img[] = $new_name;
                } else {
                    return ['status' => -1, 'msg' => $info->getError()];
                }
            }
            $comments = serialize($comment_img); // 上传的图片文件
        }

        return ['status' => 1, 'msg' => '上传成功', 'result' => $comments];
    }
}
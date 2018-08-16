<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\model;

use think\Model;
use think\Db;
use think\Page;
use Psp;
use Grpc;

/**
 * @package Home\Model
 */
class Message extends Model
{
    protected $tableName = 'message';
    protected $_validate = array();

    /**
     * 获取用户的消息个数
     * @return array
     */
    public function getUserMessageCount(){
       /* $user_info = session('user');
        $user_system_message_no_read_where = array(
            'um.user_id' => $user_info['user_id'],
            'um.status' => 0,
        );
        $user_system_message_no_read = DB::name('user_message')
            ->alias('um')
            ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
            ->where($user_system_message_no_read_where)
            ->count();
        return $user_system_message_no_read;*/
        $payload =validate_json_web_token(cookie('token'));
        $uid =$payload['user_id'];

        $user_id = new Psp\Member\Uid();
        $user_id->setUid($uid);
        list($res,$status) = GRPC('member')->GetUserUnreadMessageCount($user_id)->wait();
        $count = $res->getCount();
        return $count;

    }
    /**
     * 获取用户的系统消息
     * @return array
     */
    public function getUserMessageNotice()
    {
        $this->checkPublicMessage();
        $payload =validate_json_web_token(cookie('token'));
        $user_id =$payload['user_id'];
       /* $user_system_message_no_read_where = array(
                'um.user_id' => $user_id,
                'um.status' => 0,
                'um.category' => 0
        );*/
        $p = I('p/d', 1);

        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("issue_date");
        $page->setIndex($p);
        $page->setLimit(5);
        $message = new Psp\Member\GetPage();
        $message->setUid($user_id);
        $message->setPagination($page);
        list($res,$status)=GRPC('member')->GetUserMessageList($message)->wait();

        foreach ($res->getUserMessageList() as $k=>$v)
        {
            $arr[$k]['msg_id'] = $v->getMsgId();
            $arr[$k]['piority'] = $v->getPiority();
            $arr[$k]['issue_date'] = $v->getIssueDate()->getSeconds();
            $arr[$k]['template_id'] = $v->getTemplateId();
            $arr[$k]['flags'] = $v->getFlags();
            $arr[$k]['params'] = $v->getParams();
        }

        //总条数
        $total_count = $res->getPageResult()->getTotalRecords();

        //总页数
        $Page = new Page($total_count, 3);
        $show = $Page->show();

        /*$user_system_message_no_read = DB::name('user_message')
            ->alias('um')
            ->comment('为啥查不了')
            ->field('um.rec_id, um.message_id, m.message, m.send_time')
            ->join('__MESSAGE__ m','um.message_id = m.message_id','LEFT')
            ->where($user_system_message_no_read_where)
            ->select();*/
        $return['result'] = $arr;
        $return['show'] = $show;

        return $return;
    }

    /**
     * 查询系统全体消息，如有将其插入用户信息表
     * @author dyr
     * @time 2016/09/01
     */
    public function checkPublicMessage()
    {
        $user_info = session('user');

//        $user_message = DB::name('user_message')->where(array('user_id' => $user_info['user_id'], 'category' => 0))->select();
        $message_where = array(
            'category' => 0,
            'type' => 1,
            'send_time' => array('gt', $user_info['reg_time']),
        );
        if (!empty($user_message)) {
            $user_id_array = get_arr_column($user_message, 'message_id');
            $message_where['message_id'] = array('NOT IN', $user_id_array);
        }
//        $user_system_public_no_read = DB::name('message')->field('message_id')->where($message_where)->select();
//        foreach($user_system_public_no_read as $key){
//            DB::name('user_message')->comment('插入了没')->add(['user_id'=>$user_info['user_id'],'message_id'=>$key['message_id'],'category'=>0,'status'=>0]);
//        }
    }
}
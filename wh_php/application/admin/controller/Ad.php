<?php
/**
 * ============================================================================
 * 
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: yhb     
 * Date: 2015-09-21
 */

namespace app\admin\controller;
use think\Page;
use think\Db;
use Psp;
use Grpc;
class Ad extends Base{
  /*广告新增及编辑*/
    public function ad()
    {       
        $act = I('get.act','add');

        $ad_id = I('get.ad_id/d');
        //如果是编辑
        if($ad_id)
        {
            $client = GRPC('Advertisement');
            $co=20;
            $page = new Psp\Pagination();
            $page->setSortAsc(false);
            $page->setSortBy("ad_id");
            $page->setIndex(1);
            $page->setLimit($co);
            $user = new Psp\Trade\AdCondition();
            $user->setPagination($page);//传入分页
            $user->setAdName($keywords);//传入广告名称
            list($res,$status) = $client->GetAdList($user)->wait();
            foreach ($res->getAdList() as $k=>$v)
            {
                $data[$k]['ad_id'] = $v->getAdId();//广告id
                $data[$k]['pid'] = $v->getPid();//广告位置id
                $data[$k]['ad_name'] = $v->getAdName();//广告名称
                $data[$k]['ad_code'] = $v->getAdCode();//图片地址
                $data[$k]['ad_link'] = $v->getAdLink();//链接地址
                $data[$k]['start_time'] =$v->getStartTime()->getSecondS();//开始时间
                $data[$k]['end_time'] = $v->getEndTime()->getSecondS();//结束时间
                $data[$k]['target'] = $v->getTarget();//是否开启浏览器新窗口
                $data[$k]['enabled'] = $v->getEnabled();//是否显示
                $data[$k]['orderby'] = $v->getOrderby();//排序
            }
            
            foreach($data as $k =>$val)
            {
                
                if($val['ad_id']==$ad_id)
                {
                    $po=$val;
                    continue;
                }

            }

            adminOperateLog('编辑广告',5);
           
        }
       //如果是新增
        $cli = GRPC('AdPosition');
        $c=100;
        $pag = new Psp\Pagination();
        $pag->setSortAsc(false);
        $pag->setSortBy("position_id");
        $pag->setIndex(1);
        $pag->setLimit($c);
        $use = new Psp\Trade\AdConditions();
        $use->setPagination($pag);//传入分页

        list($re,$status) = $cli->GetPositionList($use)->wait();

        foreach ($re->getPositionList() as $k=>$v)
        {
            
            $da[$k]['position_id'] = $v->getPositionId();//广告位置id

            $da[$k]['position_name'] = $v->getPositionName();//广告位置名称
            $da[$k]['ad_width'] = $v->getAdWidth();//广告位宽度
            $da[$k]['ad_height'] = $v->getAdHeight();//广告位高度
            $da[$k]['position_desc'] = $v->getPositionDesc();//广告描述
            $da[$k]['is_open'] = $v->getIsOpen();//是否显示
        }

        adminOperateLog('新增广告',5);
        $this->assign('info',$po);
        $this->assign('act',$act);
        $this->assign('position',$da);
        return $this->fetch();
    }
    /*获取广告列表*/
    public function adList(){
        
        delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
                    
        $pid = I('pid',0);//查询广告ID
        $keywords=   I('post.keywords');//查询广告名称
        $p=I('get.p/d',1);//分页页码
         $client = GRPC('Advertisement');
         $co=20;
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("ad_id");
        $page->setIndex($p);
        $page->setLimit($co);
        $user = new Psp\Trade\AdCondition();
        $user->setPagination($page);//传入分页
        $user->setAdName($keywords);//传入广告名称
        $user->setPid($pid);//传入广告位置ID
        list($res,$status) = $client->GetAdList($user)->wait();
        foreach ($res->getAdList() as $k=>$v)
        {
            
            $data[$k]['ad_id'] = $v->getAdId();//广告id
            $data[$k]['pid'] = $v->getPid();//广告位置id
            $data[$k]['ad_name'] = $v->getAdName();//广告名称
            $data[$k]['ad_code'] = $v->getAdCode();//图片地址
            $data[$k]['ad_link'] = $v->getAdLink();//链接地址

            $data[$k]['target'] = $v->getTarget();//是否开启浏览器新窗口
            $data[$k]['enabled'] = $v->getEnabled();//是否显示
            $data[$k]['orderby'] = $v->getOrderby();//排序          
        }
    //需要获取广告位置列表
        $cli = GRPC('AdPosition');

        $c=100;
        
        $pag = new Psp\Pagination();
        $pag->setSortAsc(false);
        $pag->setSortBy("position_id");
        $pag->setIndex(1);
        $pag->setLimit($c);
        $use = new Psp\Trade\AdConditions();
        $use->setPagination($pag);//传入分页

        list($re,$status) = $cli->GetPositionList($use)->wait();

        foreach ($re->getPositionList() as $k=>$v)
        {
            
            $da[$k]['position_id'] = $v->getPositionId();//广告位置id
            $da[$k]['position_name'] = $v->getPositionName();//广告位置名称
            $da[$k]['ad_width'] = $v->getAdWidth();//广告位宽度
            $da[$k]['ad_height'] = $v->getAdHeight();//广告位高度
            $da[$k]['position_desc'] = $v->getPositionDesc();//广告描述
            $da[$k]['is_open'] = $v->getIsOpen();//是否显示
        }
        $arr2=array();
        foreach($da as $ke=>$value){
            $arr2[$value['position_id']]=$value['position_name'];
        }


        $this->assign('ad_position_list',$da);
        $this->assign('ad_position',$arr2);
        $this->assign('list',$data);// 赋值数据集
        
        $count=$res->getPaginationResult()->getTotalRecords();
        if($p == 1){
            adminOperateLog('广告列表',5);
        }

        $Page  = new Page($count,$co);
        $show = $Page->show(); 
        $this->assign('page',$show);// 赋值分页输出
               
        return $this->fetch();
    }

    //新增/编辑 广告位页面
    public function position()
    {
        $act = I('get.act','add');
        $position_id=I('get.position_id');
        $cli = GRPC('AdPosition');

        $c=20;
        
        $pag = new Psp\Pagination();
        $pag->setSortAsc(false);
        $pag->setSortBy("position_id");
        $pag->setIndex(1);
        $pag->setLimit($c);
        $use = new Psp\Trade\AdConditions();
        $use->setPagination($pag);//传入分页

        list($re,$status) = $cli->GetPositionList($use)->wait();

        foreach ($re->getPositionList() as $k=>$v)
        {
            
            $da[$k]['position_id'] = $v->getPositionId();//广告位置id
            $da[$k]['position_name'] = $v->getPositionName();//广告位置名称
            $da[$k]['ad_width'] = $v->getAdWidth();//广告位宽度
            $da[$k]['ad_height'] = $v->getAdHeight();//广告位高度
            $da[$k]['position_desc'] = $v->getPositionDesc();//广告描述
            $da[$k]['is_open'] = $v->getIsOpen();//是否显示
        }
        foreach($da as $k =>$val)
        {
                
                if($val['position_id']==$position_id)
                {
                    $po=$val;
                    continue;
                }

        }
        adminOperateLog('广告位查看',5);
        $this->assign('info',$po);
        $this->assign('act',$act);
        return $this->fetch();
    }
    //广告位置列表
    public function positionList()
    {
        $p=I('get.p/d',1);//分页页码
        $client = GRPC('AdPosition');

        $co=20;
        
        $page = new Psp\Pagination();
        $page->setSortAsc(false);
        $page->setSortBy("position_id");
        $page->setIndex($p);
        $page->setLimit($co);
        $user = new Psp\Trade\AdConditions();
        $user->setPagination($page);//传入分页

        list($res,$status) = $client->GetPositionList($user)->wait();

        foreach ($res->getPositionList() as $k=>$v)
        {
            //var_dump($v->getLogictics());
            $data[$k]['position_id'] = $v->getPositionId();//广告位置id
            $data[$k]['position_name'] = $v->getPositionName();//广告位置名称
            $data[$k]['ad_width'] = $v->getAdWidth();//广告位宽度
            $data[$k]['ad_height'] = $v->getAdHeight();//广告位高度
            $data[$k]['position_desc'] = $v->getPositionDesc();//广告描述
            $data[$k]['is_open'] = $v->getIsOpen();//是否显示
        }
        $count=$res->getPaginationResult()->getTotalRecords();
        //$lim=$res->getPaginationResult()->getPageSize();
        if($p == 1){
            adminOperateLog('广告位置列表',5);
        }
        $Page  = new Page($count,$co);
        $show = $Page->show();
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('list', $data);
        $this->assign('pager', $Page);
        return $this->fetch();
    }
    /*添加编辑广告*/
    public function adHandle()
    {
    	$data = I('post.');
        
        
        $client = GRPC('Advertisement');
        if($data['act'] == 'del')
        {
            $user = new Psp\Trade\AdminAdId();
            $user->setAdId($data['del_id']);//传入广告ID
            list($res,$status) = $client->DelAd($user)->wait();
            $r=$res->getValue();
            adminOperateLog('删除广告',5);
            if($r) exit(json_encode(1));
        }
        //编辑广告
        if($data['act'] == 'edit')
        {
            $time = new Psp\Timestamp();
             $begin=strtotime($data['begin']);

            $time->setSeconds($begin);
            $begin=$time->setNanos(1);

            $tim = new Psp\Timestamp();
            $end=strtotime($data['end']);
            $tim->setSeconds($end);
            $end=$tim->setNanos(1);
            $user = new Psp\Trade\AdminAd();
            $user->setAdId($data['ad_id']);//广告id
            $user->setPid($data['pid']);//广告位置id
            $user->setAdName($data['ad_name']);//广告名称
            $user->setMediaType($data['media_type']);//广告类型
            $user->setStartTime($begin);//开始时间
            $user->setEndTime($end);//结束时间
            $user->setAdLink($data['ad_link']);//链接地址
            $user->setAdCode($data['ad_code']);//图片地址
            $user->setOrderby($data['orderby']);//排序
            $user->setBgColor($data['bgcolor']);//背景颜色
            list($res,$status) = $client->EditAd($user)->wait();

            $r=$res->getValue();
            adminOperateLog('编辑广告',5);

            if($r){
                $this->success("修改成功",U('admin/Ad/adList'));
            }else{
                $this->success("修改失败");
            }
        }

    	if($data['act'] == 'add')
        {

             $time = new Psp\Timestamp();
             $begin=strtotime($data['begin']);

            $time->setSeconds($begin);
            $begin=$time->setNanos(1);

            $tim = new Psp\Timestamp();
            $end=strtotime($data['end']);
            $tim->setSeconds($end);
            $end=$tim->setNanos(1);
            
            $user = new Psp\Trade\NewAd();
            
            $user->setPid($data['pid']);//广告位置id
            $user->setAdName($data['ad_name']);//广告名称
            $user->setMediaType($data['media_type']);//广告类型
            $user->setStartTime($begin);//开始时间
            $user->setEndTime($end);//结束时间
            $user->setAdLink($data['ad_link']);//链接地址
            $user->setAdCode($data['ad_code']);//图片地址
           // $user->setOrderby($data['orderby']);//排序

            list($res,$status) = $client->JoinAd($user)->wait();
            
            $r=$res->getValue();
            adminOperateLog('添加广告',5);

            if($r){
                $this->success("添加成功",U('admin/Ad/adList'));
            }else{
                $this->success("添加失败");
            }
    		
    	}

    }
    /*添加和编辑广告位置*/
    public function positionHandle()
    {
        $data = I('post.');
//        echo"<pre>";
//        var_dump($data);die;
        $client = GRPC('AdPosition');

        if($data['act'] == 'add')
        {
            $user = new Psp\Trade\NewPosition();
            $user->setPositionName($data['position_name']);//广告位置名称
            $user->setAdWidth($data['ad_width']);//广告位宽度
            $user->setAdHeight($data['ad_height']);//广告位高度
            $user->setPositionDesc($data['position_desc']);//广告描述
            $user->setIsOpen($data['is_open']);//是否显示
            list($res,$status) = $client->JoinPosition($user)->wait();
            $r=$res->getValue();
            adminOperateLog('添加广告位',5);
            if($r){
                $this->success("添加成功",U('admin/Ad/positionList'));
            }else{
                $this->success("添加失败");
            }
        }
        
        if($data['act'] == 'edit')
        {
            $user = new Psp\Trade\AdminPosition();
            $user->setPositionId($data['position_id']);//广告位置ID
            $user->setPositionName($data['position_name']);//广告位置名称
            $user->setAdWidth($data['ad_width']);//广告位宽度
            $user->setAdHeight($data['ad_height']);//广告位高度
            $user->setPositionDesc($data['position_desc']);//广告描述
            $user->setIsOpen($data['is_open']);//是否显示
            list($res,$status) = $client->EditPosition($user)->wait();

            $r=$res->getValue();
            adminOperateLog('编辑广告位',5);
            if($r){
                $this->success("修改成功",U('admin/Ad/positionList'));
            }else{
                $this->success("修改失败");
            }

        }
                
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/positionList');
        if($r){
        	$this->success("操作成功",$referurl);
        }else{
        	$this->error("操作失败",$referurl);
        }
    }
    
//    public function changeAdField()
//    {
//        $field = $this->request->request('field');
//    	$data[$field] = I('get.value');
//    	$data['ad_id'] = I('get.ad_id');
//    	M('ad')->save($data); // 根据条件保存修改的数据
//    }
    
    /**
     * 编辑广告中转方法
     */
    public function editAd()
    {
        \think\Cache::clear();        
        $request_url = urldecode(I('request_url'));
        $request_url = U($request_url,array('edit_ad'=>1));
        echo "<script>location.href='".$request_url."';</script>";
        exit;                
    }        
}
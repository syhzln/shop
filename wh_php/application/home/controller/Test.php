<?php
 /**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */
namespace app\home\controller; 
use Symfony\Component\Yaml\Tests\A;
use Think\Action;use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Cache;
use Grpc;
Use Psp;
Use app\admin\logic\AccountCenterLogic;
Use app\seller\logic\ItemLogic;

class Test extends Controller {


    public function index(){



//        dump($_SERVER[REQUEST_URI]);
//        exit;

        $cli = GRPC('itm');
//        $data = new ItemLogic();
//        $res = $data->getProviderCategory(3);
//        $res =  $data->getCategoryByparendId(5,19,'class_2');//err

//          $res = $data->getSpecOption(765,35);
           $logic = new ItemLogic();
        $data  = ['opt_name'=>998899,spec_id=>38,'spec_name'=>"尺码",'provider_id'=> 22];
        $res = $logic->addSpecOpt($data);
        dump($res);
//        dump($data->setItemId(123));
//
//        dump($cli);
        exit;
        $type = I('type','1');
        $p = I('p',1);
        $page = new Psp\Pagination();
        $page->setSortAsc(true);
        $page->setSortBy("account_id");
        $page->setIndex($p);
        $page->setLimit(8);

        $AccountType = new Psp\Account\AccountType();
        $AccountType->setType($type);
        $AccountType->setPagination($page);
        $AccountType->setPlatform(PLATFORM);
        dump($AccountType);

        list($res) =$cli->getAccountByType($AccountType)->wait();
        dump($res);

        $accountLogic = new AccountCenterLogic();
        $accountList = $accountLogic->accountList($res->getAccountList());

        dump($accountList);

        exit();

        //分页处理
        $total_count = $res->getPaginationResult()->getTotalRecords();//每页条数

        $limit_page = $res->getPaginationResult()->getPageSize();//总页数

        $this->assign('list', $accountList);
        $Page = new Page($total_count, 8);
        $show = $Page->show();
        $this->assign('page', $show);



        exit;

        config::set("BA",112);
        dump(C('BA'));
        exit;
//        dump(GRPC(account));
        ini_set('max_execution_time',0);
        for ($fuck = 0; $fuck < 10; $fuck ++) {

            $type = 1;
            $p = 1;

            $page = new Psp\Pagination();
            $page->setSortAsc(true);
            $page->setSortBy("account_id");
            $page->setIndex($p);
            $page->setLimit(8);


            $AccountType = new Psp\Account\AccountType();
            $AccountType->setType($type);
            $AccountType->setPagination($page);

            $client = ACC();
            list($res) = $client->getAccountByType($AccountType)->wait();


            $accountLogic = new AccountCenterLogic();
            $accountList = $accountLogic->accountList($res->getAccountList());

            dump(sprintf("%d %s", $fuck, date("H:i:s", time())));


        }

        exit();
        $acc = new AccountCenterLogic();
        $acc->addTransfer('10004','1','200',1);
        exit;



        $addAcc = new AccountCenterLogic();
        $info = $addAcc->addEditAccount([
//             account_id =>'20030',
             platform_id =>  1,
             type =>  2,
             biz_type =>1  ,
             primary_account => 52999 ,
             owner_id => 666555 ,
             name => '大龙11' ,
             create_date =>strtotime('-1 day'),
             balance => 0.00 ,
             extractable_balance => 0 ,
             currency => 1 ,
             incomming_daily => 0.00 ,
             outcomming_daily => 0.00 ,
             flags => 1 ,
             sub_accounts =>  '',
             payments =>  '',
             extra_info =>  '说点书名号'
        ]);
        dump($info);
        exit;


//        $addAccount = new Psp\Account\AccountTable();
//
//        $addAccount->setPlatformId(PLATFORM);
//        $addAccount->setType(1);
//        $addAccount->setBizType(1);
//        $addAccount->setPrimaryAccount(0);
//        $addAccount->setOwnerId(52995);
//        $addAccount->setName("会员帐号");
//        $addAccount->setCurrency(1);
//        $addAccount->setBalance(1.00);
//        $addAccount->setExtractableBalance(1.00);
//        $addAccount->setIncommingDaily(1.00);
//        $addAccount->setOutcommingDaily(1.00);
//        $addAccount->setFlags(1);
//        $addAccount->setExtraInfo(1);
//        $addAccount->setSubAccounts(1);
//        $addAccount->setPayments(1);
//        $addAccount->setCreateDate(grpcTime());


//        list($res)= GRPC(account)->updateAccount($addAcc)->wait();
//        dump($res);
//        exit();


    $addAccount = new Psp\Account\AccountTable();
    $addAccount->setPlatformId(PLATFORM);
    $addAccount->setType(1);
    $addAccount->setBizType(1);
    $addAccount->setPrimaryAccount(grpcEmpty());
    $addAccount->setOwnerId(52999);
    $addAccount->setName("会员帐号");
    $addAccount->setCurrency(1);
    $addAccount->set(1);

    GRPC(account)->addAccount($addAccount)->wait();
    exit;



//        dump(grpctime());
//        dump(EXT);
//        exit;

//        header("Content-Type: text/html; charset=utf-8");

        // 这个写法会出错，因为构造方法被声明为private
        //$test = new Example;

// 下面将得到Example类的单例对象
        //$test = Example::singleton();
        //$test->test();

// 复制对象将导致一个E_USER_ERROR.
       // $test_clone = clone $test;
//        dump(C('GRPC')['Account']);exit;
//        $client = TRA();
//        dump($client );exit;
//        $type = I('type');
//        $client = new Psp\Trade\TradeServiceClient('192.168.1.111:9300',[
//            'credentials' => Grpc\ChannelCredentials::createInsecure()
//        ]);

//        $user = new Psp\Trade\OrderCondition();
//        $user->setMemberId(1010101);
//        $user->setCondition('all');
//        list($res,$status) = GRPC('trade')->GetUserOrder($user)->wait();
//
//        foreach ($res->getUserOrder() as $k=>$v) {
//            //var_dump($v->getLogictics());
//            $data[$k]['order_id'] = $v->getOrderId();
//            $data[$k]['order_sn'] = $v->getOrderSn();
//            $data[$k]['status'] = $v->getState();
//            $data[$k]['money'] = $v->getMoney();
//            $data[$k]['add_time'] = $v->getOrderDate()->getSeconds();
//            foreach($v->getItems() as $kk=>$vv){
//                $data[$k]['order_item'][$kk]['title'] = $vv->getName();
//                $data[$k]['order_item'][$kk]['price'] = $vv->getPrice();
//                $data[$k]['order_item'][$kk]['cost'] = $vv->getCost();
//                $data[$k]['order_item'][$kk]['amount'] = $vv->getAmount();
//                $data[$k]['order_item'][$kk]['thumb_img_url']= $vv->getThumbImageUrl();
//            }
//            foreach ($v->getLogictics() as $k3=>$v3){
//                $data[$k]['shipping_price'][$k3]['price'] = $v3->getPrice();
//            }
//
//
//
//        }
//        echo "<pre>";
//        var_dump($data);
//exit;
//        $accountClient =  new Psp\Account\AccountServiceClient(C('Account'), ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
//        dump(GRPC('account')->waitForReady(5));
//        dump(GRPC('trade')->waitForReady(5));
        //      dump(new Psp\Account\AccountServiceClient(C('Account'), ['credentials' => Grpc\ChannelCredentials::createInsecure()])
//    );
//        dump($accountClient);exit;
//        $accountClient1 = ACC();
//        if($accountClient===$accountClient1) echo "同一个实例对象";
//        else echo "不是一个对象";
//        exit;

        $Receiptinfo = new Psp\Account\GetReceiptIdRequest();
        $Receiptinfo->setType('1');
        $Receiptinfo->setAmount('88888');
        $Receiptinfo->setCurrency('1');
        $Receiptinfo->setBizType('1');
        $Receiptinfo->setUserId('52999');
        $Receiptinfo->setOrgId('1001');
        $Receiptinfo->setPlatformId(PLATFORM);
        $Receiptinfo->setOrderIds('123321');
        $Receiptinfo->setExtraInfo('测试信息111');
        $Receiptinfo->setIssueDate(grpctime());

        list($receipt_id_res) = GRPC('account')->getReceiptId($Receiptinfo)->wait();

        $id = $receipt_id_res->getReceiptId();
        dump($id);

//        dump($accountClient);
        exit('123321');
        dump(tpCache(oss));
        echo UPLOAD_PATH;
        dump(UPLOAD_PATH);

        exit;
	   $mid = 'hello'.date('H:i:s');
       //echo "测试分布式数据库$mid";
       //echo "<br/>";
       //echo $_GET['aaa'];       
       //  M('config')->master()->where("id",1)->value('value');
       //echo M('config')->cache(true)->where("id",1)->value('value');
       //echo M('config')->cache(false)->where("id",1)->value('name');
//       echo $config = M('config')->cache(false)->where("id",1)->value('value');
        // $config = DB::name('config')->cache(true)->query("select * from __PREFIX__config where id = :id",['id'=>2]);
         print_r($config);
       /*
       //DB::name('member')->insert(['mid'=>$mid,'name'=>'hello5']);
       $member = DB::name('member')->master()->where('mid',$mid)->select();
	   echo "<br/>";
       print_r($member);
       $member = DB::name('member')->where('mid',$mid)->select();
	   echo "<br/>";
       print_r($member);
	*/   
//	   echo "<br/>";
//	   echo DB::name('member')->master()->where('mid','111')->value('name');
//	   echo "<br/>";
//	   echo DB::name('member')->where('mid','111')->value('name');
         echo C('cache.type');
    }  
    
    public function redis(){
        Cache::clear();
        $cache = ['type'=>'redis','host'=>'192.168.0.201'];        
        Cache::set('cache',$cache);
        $cache = Cache::get('cache');
        print_r($cache);         
        S('aaa','ccccccccccccccccccccccc');
        echo S('aaa');
    }
    
    public function table(){
//        $t = Db::query("show tables like '%tp_goods_2017%'");
//        print_r($t);
    }
    
        public function t(){
                
         //echo $queue = \think\Cache::get('queue');
         //\think\Cache::inc('queue',1);
         //\think\Cache::dec('queue');
//        $res = DB::name('config')->cache(true)->find();
//        print_r($res);
//              DB::name('config')->update(['id'=>1,'name'=>'http://www.walhao.com']);
//        $res = DB::name('config')->cache(true)->find();
//        print_r($res);
        
        
    }

    //写入支付日志
    public function writePaylog(){
        $url =SITE_URL.U('Payment/notifyUrl',array('pay_code'=>'alipay'));
        $order_amount = 100;
        $order_sn =20170304420;
        $time =date('YmdHis',time());
        $info = "回调地址".$url."\r\n"."订单号: ".$order_sn."\r\n"."订单金额: ".$order_amount."\r\n"."支付时间: ".$time;
        //打开文件
        $myfile= fopen('./paylog.txt','r+');
        fwrite($myfile, $info);
        fclose($myfile);

    }
}
<?php

/**
 * func_grpc.php
 * Description: GRPC拓展公共函数类
 * Author: Ning<nono0903@gmial.com>
 * Date: 2017/11/17 0017 下午 4:33
 */

function grpcTime($timestamp=null)
{
    $timestamp = empty($timestamp)?time():$timestamp;
    $time = new Psp\Timestamp();
    $time->setSeconds($timestamp);
    $time->setNanos(1);
    return $time;
}

/**
 * GRPC 创建连接
 * @param $conn 连接名
 * @param null $class
 * @param null $host
 * @param null $opt
 * @param null $channel
 * Author: Ning
 */
function GRPC($conn,$host=null,$opt=null,$channel=null,$class=null)
{
    $conn = strtolower($conn);
//    $client = new client\Grpc\ClientMap();
//     return $client->getConn($conn);

    if (!$class && !$opt) {
        switch ($conn) {
            case 'account':
                $host = empty($host) ? C('Account') : $host;
                $client = new Psp\Account\AccountServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $client;
                break;

            case 'trade':
                $host = empty($host) ? C('Trade') : $host;
                $client = new Psp\Trade\TradeServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $client;
                break;

            case 'sellerorder':
                $host = empty($host) ? C('SellerOrder') : $host;
                $client = new Psp\Trade\SellerOrderServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'adminorder':
                $host = empty($host) ? C('AdminOrder') : $host;
                $client = new Psp\Trade\AdminOrderServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);;
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'item':
                $host = empty($host) ? C('Item') : $host;
                $client = new Psp\Item\ItemServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'cat':
                $host = empty($host) ? C('Cat') : $host;
                $client = new Psp\Item\ItemCatServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'brand':
                $host = empty($host) ? C('Brand') : $host;
                $client = new Psp\Item\ItemBrandServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'comment':
                $host = empty($host) ? C('Comment') : $host;
                $client = new Psp\Item\ItemCommentServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'extra':
                $host = empty($host) ? C('Extra') : $host;
                $client = new Psp\Item\ItemExtraServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);;
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'user':
                $host = empty($host) ? C('User') : $host;
                $client = new Psp\User\PlatfromUserServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
//                if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $client;
                break;

            case 'member':
                $host = empty($host) ? C('Member') : $host;
                $client = new Psp\Member\UserServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
//                if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $client;
                break;

            case 'mobile':
                $host = empty($host) ? C('Mobile') : $host;
                $client = new Psp\Mobile\MobileIndexServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'search':
                $host = empty($host) ? C('Search') : $host;
                $client = new Psp\Search\SearchServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'seller':
                $host = empty($host) ? C('Seller') : $host;
                $client = new Psp\Seller\SellerUserServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
//                if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $client;
                break;

            case 'sellerstore':
                $host = empty($host) ? C('SellerStore') : $host;
                return new Psp\Store\SellerStoreServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
//                return $client;
                break;

            case 'article':
                $host = empty($host) ? C('Article') : $host;
                $client = new Psp\Content\ArticleServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'report':
                $host = empty($host) ? C('Report') : $host;
                $client = new Psp\Content\ReportServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'home':
                $host = empty($host) ? C('Home') : $host;
                $client = new Psp\Home\HomeIndexServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'newhome':
                $host = empty($host) ? C('NewHome') : $host;
                $client = new Psp\Newhome\HomeIndexServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'goodslist':
                $host = empty($host) ? C('GoodsList') : $host;
                $client = new Psp\Home\GoodsListServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'newgoodslist':
                $host = empty($host) ? C('NewGoodsList') : $host;
                $client = new Psp\Newhome\GoodsListServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'advertisement':
                $host = empty($host) ? C('Advertisement') : $host;
                $client = new Psp\Trade\AdminAdvertisementServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'adposition':
                $host = empty($host) ? C('AdPosition') : $host;
                $client = new Psp\Trade\AdvertisingPositionServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                return $client;
                break;

            case 'itm':
                $host = empty($host) ? C('Itm') : $host;
                return new Psp\Itm\ItemServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                break;

            case 'asset':
                $host = empty($host) ? C('asset') : $host;
                return new Psp\Member\UserAssetServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                //if (!$client->waitForReady(3)) throw new Exception($conn . "连接" . $host . "断开,请开启服务后重试");
                break;
            default:
                throw new Exception($conn."方法不存在-_-!!");
                break;

        }
    }
}

/**
 * ACC 账务中心连接(单例)//若不需要长连接,修改此处为grpc下的client即可
 * @return \Psp\Account\AccountServiceClient
 * Author: Ning
 */
function ACC()
{
    return \client\accClient::accountConn();
}

/**
 * TRA 交易中心连接
 * @return \Psp\Account\AccountServiceClient
 * Author: Ning
 */
function TRA()
{
    return \client\tradeClient::tradeConn();
}

/**
 * USR 平台用户中心连接
 * @return \Psp\Account\AccountServiceClient
 * Author: Ning
 */
function PUSR()
{
    return \client\userClient::userConn();
}

/**
 * USR 前台用户中心连接
 * @return \Psp\Account\AccountServiceClient
 * Author: Ning
 */
function USR()
{
    return \client\userClient::userConn();
}

/**
 * ITEM 商品中心连接
 * @return \Psp\Account\AccountServiceClient
 * Author: Ning
 */
function ITEM()
{
    return \client\itemClient::itemConn();
}

function grpcEmpty()
{

    return new Psp\PBEmpty();
}


function grpcBool($bool)
{
    $boolObj = new Psp\BoolValue();
    $boolObj->setValue($bool);
    return $boolObj;
}

function Trade($message){
    $class = "Psp\\Trade\\".$message;
    return new $class();
}

/**
 * @param $sort_by 排序
 * @param $index 当前页
 * @param $limit 限定
 * @param bool $asc 是否正序
 * @return \Psp\Pagination
 */
function grpcPage($sort_by,$index,$limit,$asc=true)
{
    $page = new Psp\Pagination();
    $page->setSortAsc($asc); //倒叙
    $page->setSortBy($sort_by);
    $page->setIndex($index);
    $page->setLimit($limit);
    return $page;
}

/**
 * 添加金额到缓冲账户
 * @param $money
 */
function addBuffer($money)
{
    $logic  = new \app\admin\logic\AccountCenterLogic();
    $logic->addBuffer($money);
}

/**
 * @param $order 订单id
 */
function  transferSplit($order)
{
    $logic  = new \app\admin\logic\AccountCenterLogic();
    $logic->cashDivisionTransfer($order);
}

/**
 * @param $order 订单id
 * @param $type 类型
 */
function returnOrderTransfer($order,$type)
{
    $logic  = new \app\admin\logic\AccountCenterLogic();
    $logic->returnOrderTransfer($order,$type);
}


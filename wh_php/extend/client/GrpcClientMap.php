<?php

namespace client;

use Grpc;
use Psp;

class GrpcClientMap {
   public $AccountClient;
   public $TradeClient;
   public $SellerOrderClient;
   public $AdminOrderClient;
   public $ItemClient;
   public $ItemCatClient;
   public $BrandClient;
   public $CommentClient;
   public $ExtraClient;
   public $UserClient;
   public $MemberClient;
   public $MobileClient;
   public $SearchClient;
   public $SellerClient;
   public $SellerStoreClient;
   public $ArticleClient;
   public $ReportClient;
   public $HomeClient;
   public $GoodsListClient;


    public function getConn($name) {
        switch ($name) {
            case 'account':
                if ($this->AccountClient == null) {
                    $host = empty($host)?C('Account'):$host;
                    // FIXME: create grpc client for ACC here
                    $this->AccountClient = new Psp\Account\AccountServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                }
                return $this->AccountClient;
                break;

            case 'trade':
                if($this->TradeClient == null){
                    $host = empty($host)?C('Trade'):$host;
                    $this->TradeClient =  new Psp\Trade\TradeServiceClient($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                    //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                }
                return $this->TradeClient;
                break;

            case 'sellerorder':
                if($this->SellerOrderClient == null){
                    $host = empty($host)?C('SellerOrder'):$host;
                    $this->SellerOrderClient =  new Psp\Trade\SellerOrderServiceClient($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                    //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                }
                return $this->SellerOrderClient;
                break;

            case 'adminorder':
                if($this->AdminOrderClient == null){
                    $host = empty($host)?C('AdminOrder'):$host;
                    $this->AdminOrderClient =  new Psp\Trade\AdminOrderServiceClient($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);;
                    //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                }
                return $this->AdminOrderClient;
                break;

            case 'item':
                if($this->ItemClient == null){
                    $host = empty($host)?C('Item'):$host;
                    $this->ItemClient =  new Psp\Item\ItemServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                    //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");

                }
               return $this->ItemClient;
                break;

            case 'itemcat':
                if($this->ItemCatClientem == null){
                    $host = empty($host)?C('ItemCat'):$host;
                    $this->ItemCatClient =  new Psp\Item\CatServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->ItemCatClient;
                break;

            case 'brand':
                if($this->BrandClient==null){
                    $host = empty($host)?C('Brand'):$host;
                    $this->BrandClient =  new Psp\Item\ItemBrandServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);
                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->BrandClient;
                break;

            case 'comment':
                if($this->CommentClient==null){
                    $host = empty($host)?C('Comment'):$host;
                    $this->CommentClient =  new Psp\Item\ItemcommentServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->CommentClient ;
                break;

            case 'extra':
                if($this->ExtraClient == null){
                    $host = empty($host)?C('Extra'):$host;
                    $this->ExtraClient =  new Psp\Item\ItemExtraServiceClient($host, ['credentials' => Grpc\ChannelCredentials::createInsecure()]);;

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->ExtraClient;
                break;

            case 'user':
                if($this->UserClient == null){
                    $host = empty($host)?C('User'):$host;
                    $this->UserClient =  new Psp\User\PlatfromUserServiceClient($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->UserClient;
                break;

            case 'member':
                if($this->MemberClient==null){
                    $host = empty($host)?C('Member'):$host;
                    $this->MemberClient =  new Psp\Member\UserServiceClient($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->MemberClient;
                break;

            case 'mobile':
                if($this->MobileClient == null){
                    $host = empty($host)?C('Mobile'):$host;
                    $this->MobileClient =  new Psp\Mobile\MobileIndexServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->MobileClient;
                break;

            case 'search':
                if($this->SearchClient == null){
                    $host = empty($host)?C('Search'):$host;
                    $this->SearchClient =  new Psp\Search\SearchServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->SearchClient;
                break;

            case 'seller':
                if($this->SellerClient == null){
                    $host = empty($host)?C('Seller'):$host;
                    $this->SellerClient =  new Psp\Seller\SellerUserServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->SellerClient;
                break;

            case 'sellerstore':
                if($this->SellerStoreClient == null){
                    $host = empty($host)?C('SellerStore'):$host;
                    $this->SellerClient =  new Psp\Store\SellerStoreServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->SellerClient;
                break;

            case 'article':
                if($this->ArticleClient == null){
                    $host = empty($host)?C('Article'):$host;
                    $this->ArticleClient=  new Psp\Content\ArticleServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->ArticleClient;
                break;

            case 'report':
                if($this->ReportClient == null)
                {
                    $host = empty($host)?C('Report'):$host;
                    $this->ReportClient =  Psp\Content\ReportServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->ReportClient ;
                break;

            case 'home':
                if($this->HomeClient == null)
                {
                    $host = empty($host)?C('Home'):$host;
                    $this->HomeClient =  new Psp\Home\HomeIndexServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->HomeClient ;
                break;

            case 'goodslist':
                if($this->GoodsListClient == null)
                {
                    $host = empty($host)?C('GoodsList'):$host;
                    $this->GoodsListClient =  new Psp\Home\GoodsListServiceClient($host,['credentials' => GrpcChannelCredentials::createInsecure()]);

                }
                //if(!$client->waitForReady(3)) throw new Exception($conn."连接".$host."断开,请开启服务后重试");
                return $this->GoodsListClient;
                break;

            default:
                throw new Exception($name."方法不存在-_-!!");
                break;
        }
    }
}
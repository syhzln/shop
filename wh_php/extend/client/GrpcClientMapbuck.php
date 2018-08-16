<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/5 0005
 * Time: 16:42
 */

namespace client;

use Grpc;
use Psp;

class GrpcClientMapbuck {

    public  $AccountClient;
    public  $TradeClient;
    public  $SellerOrderClient;
    public  $AdminOrderClient;
    public  $ItemClient;
    public  $ItemCatClient;
    public  $BrandClient;
    public  $CommentClient;
    public  $ExtraClient;
    public  $UserClient;
    public  $MemberClient;
    public  $MobileClient;
    public  $SearchClient;
    public  $SellerClient;
    public  $SellerStoreClient;
    public  $ArticleClient;
    public  $ReportClient;
    public  $HomeClient;
    public  $GoodsListClient;

    public function __construct($name){
        $name = strtolower($name);
        switch ($name) {
            case 'account':
                $this->AccountClient=self::ArrangementCode($this->AccountClient,'Account','Account\AccountServiceClient');
                break;

            case 'trade':
                $this->TradeClient=self::ArrangementCode($this->TradeClient,'Trade','Trade\TradeServiceClient');
                break;

            case 'sellerorder':
                return $this->SellerOrderClient=self::ArrangementCode($this->SellerOrderClient,'SellerOrder','Trade\SellerOrderServiceClient');
                break;

            case 'adminorder':
                return $this->AdminOrderClient=self::ArrangementCode($this->AdminOrderClient,'AdminOrder','Trade\AdminOrderServiceClient');
                break;

            case 'item':
                return $this->ItemClient=self::ArrangementCode($this->ItemClient,'Item','Item\ItemServiceClient');
                break;

            case 'itemcat':
                return $this->ItemCatClient=self::ArrangementCode($this->ItemCatClient,'ItemCat','Item\ItemCatServiceClient');
                break;

            case 'brand':
                return $this->BrandClient=self::ArrangementCode($this->BrandClient,'Brand','Item\ItemBrandServiceClient');
                break;

            case 'comment':
                return $this->CommentClient=self::ArrangementCode($this->CommentClient,'Comment','Item\ItemcommentServiceClient');
                break;

            case 'extra':
                return $this->ExtraClient=self::ArrangementCode($this->ExtraClient,'Extra','Item\ItemExtraServiceClient');
                break;

            case 'user':
                return $this->UserClient=self::ArrangementCode($this->UserClient,'User','User\PlatfromUserServiceClient');
                break;

            case 'member':
                return $this->MemberClient=self::ArrangementCode($this->MemberClient,'Member','Member\UserServiceClient');
                break;

            case 'mobile':
                return $this->MobileClient=self::ArrangementCode($this->MobileClient,'Mobile','Mobile\MobileIndexServiceClient');
                break;

            case 'search':
                return $this->SearchClient=self::ArrangementCode($this->SearchClient,'Search','Search\SearchServiceClient');
                break;

            case 'seller':
                return $this->SellerClient=self::ArrangementCode($this->SellerClient,'Seller','Seller\SellerUserServiceClient');
                break;

            case 'sellerstore':
                return $this->SellerClient=self::ArrangementCode($this->SellerClient,'SellerStore','Store\SellerStoreServiceClient');
                break;

            case 'article':
                return $this->ArticleClient=self::ArrangementCode($this->ArticleClient,'Article','Content\ArticleServiceClient');
                break;

            case 'report':
                return $this->ReportClient=self::ArrangementCode($this->ReportClient,'Report','Content\ReportServiceClient');
                break;
            case 'home':
                return $this->HomeClient=self::ArrangementCode($this->HomeClient,'Home','Home\HomeIndexServiceClient');
                break;
            case 'goodslist':
                return $this->GoodsListClient=self::ArrangementCode($this->GoodsListClient,'GoodsList','Home\GoodsListServiceClient');
                break;
            default:
                throw new Exception($name."方法不存在-_-!!");
                break;
        }
    }


    /**
     * 整理代码使用
     */
    private function ArrangementCode($Client,$Modular,$Psp,$prefix='Psp\\'){
        $host = empty($host)?C($Modular):$host;
        if($Client == null)
        {
            $Class=$prefix.$Psp;
            $Client =  new $Class($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        }

        return $Client;
    }


}
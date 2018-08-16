<?php
namespace client;

use Grpc;

class GrpcClientWrapper {
    public $client;

    public function __construct($name)
    {
        $name = strtolower($name);
        switch ($name) {
            case 'account':
                self::ArrangementCode('Account','Account\AccountServiceClient');
                break;

            case 'trade':
                self::ArrangementCode('Trade','Trade\TradeServiceClient');
                break;

            case 'sellerorder':
                self::ArrangementCode('SellerOrder','Trade\SellerOrderServiceClient');
                break;

            case 'adminorder':
                self::ArrangementCode('AdminOrder','Trade\AdminOrderServiceClient');
                break;

            case 'item':
                self::ArrangementCode('Item','Item\ItemServiceClient');
                break;

            case 'itemcat':
                self::ArrangementCode('ItemCat','Item\ItemCatServiceClient');
                break;

            case 'brand':
                self::ArrangementCode('Brand','Item\ItemBrandServiceClient');
                break;

            case 'comment':
                self::ArrangementCode('Comment','Item\ItemcommentServiceClient');
                break;

            case 'extra':
                self::ArrangementCode('Extra','Item\ItemExtraServiceClient');
                break;

            case 'user':
                self::ArrangementCode('User','User\PlatfromUserServiceClient');
                break;

            case 'member':
                self::ArrangementCode('Member','Member\UserServiceClient');
                break;

            case 'mobile':
                self::ArrangementCode('Mobile','Mobile\MobileIndexServiceClient');
                break;

            case 'search':
                self::ArrangementCode('Search','Search\SearchServiceClient');
                break;

            case 'seller':
                self::ArrangementCode('Seller','Seller\SellerUserServiceClient');
                break;

            case 'sellerstore':
                self::ArrangementCode('SellerStore','Store\SellerStoreServiceClient');
                break;

            case 'article':
                self::ArrangementCode('Article','Content\ArticleServiceClient');
                break;

            case 'report':
                self::ArrangementCode('Report','Content\ReportServiceClient');
                break;
            case 'home':
                self::ArrangementCode('Home','Home\HomeIndexServiceClient');
                break;
            case 'goodslist':
                self::ArrangementCode('GoodsList','Home\GoodsListServiceClient');
                break;
            default:
                throw new Exception($name."方法不存在-_-!!");
                break;
        }
    }



    /**
     * 整理代码使用
     */
    private function ArrangementCode($Modular,$Psp,$prefix='Psp\\'){
        $host = empty($host)?C($Modular):$host;
        if($this->client == null)
        {
            $Class=$prefix.$Psp;
            $this->client = new $Class($host,['credentials' => Grpc\ChannelCredentials::createInsecure()]);
        }
    }

    public function __destruct()
    {
        if ($this->client != null) {
            $this->client->close();
            $this->client = null;
        }
    }
}
?>
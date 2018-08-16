<?php
/**
 * Author: fzq
 * Date: 2017-10-10
 * desc: 平台权限中心
 * 新权限必须每一个 function对应一个权限id  例如 '10001'=>'User@editUser';
 * ajax请求不验证权限,故可不添加 例如 ( request()->isAjax() || strpos($act,'ajax')!== false )  Index控制器不验证权限;
 * 格式:  '10001'=>'控制器名@方法名'  ****权限名称,权限id不能重复,包括商家权限和平台权限  1平台 2 商家
 * 警告: 权限id一旦录入 严禁修改
 */

return array(
    /*会员中心*/
    '101'=>'User@index','102'=>'User@editUser','103'=>'User@delete','104'=>'User@detail','105'=>'User@address',
    '106'=>'User@account_log','107'=>'User@account_edit','108'=>'User@recharge','109'=>'User@search_user',
    '110'=>'User@sendMessage','111'=>'User@doSendMessage','112'=>'User@sendMail','113'=>'User@doSendMail',
    '114'=>'User@withdrawals','115'=>'User@delWithdrawals','116'=>'User@editWithdrawals','117'=>'User@withdrawals_update',
    '118'=>'User@transfer','119'=>'User@remittance','120'=>'User@message','121'=>'User@shortMsg','122'=>'User@memberOperate',
    '125'=>'User@user_message','126'=>'User@replyMessage','127'=>'User@message_notice','128'=>'User@memberBuyList','129'=>'User@memberBuyDetail','130'=>'User@delMemberBuy','131'=>'User@finished_message','132'=>'User@message_delete','133'=>'User@member_repayment_log','134'=>'User@login_to_usercenter',
    /*商品中心*/
    '201'=>'Goods@goodsList','202'=>'Goods@delGoods','203'=>'Comment@index','204'=>'Comment@detail','205'=>'Comment@del',
    '206'=>'Comment@op','207'=>'Comment@ask_list','208'=>'Comment@consult_info','209'=>'Comment@ask_handle',
    '210'=>'Goods@categoryList','211'=>'Goods@delGoodsCategory','212'=>'Goods@addEditCategory','216'=>'Goods@stock_list',
    '217'=>'Goods@addEditGoods','218'=>'Goods@goodsTypeList','219'=>'Goods@addEditGoodsType','220'=>'Goods@delGoodsType','221'=>'goodsAttributeList','222'=>'addEditGoodsAttribute',
    '223'=>'Goods@updateField','224'=>'Goods@delGoodsAttribute','226'=>'Goods@brandList','227'=>'Goods@addEditBrand',
    '228'=>'Goods@delBrand','229'=>'Goods@specList','230'=>'Goods@delGoodsSpec','231'=>'Goods@addEditSpec',
    '232'=>'Goods@del_goods_images','233'=>'Goods@initGoodsSearchWord','234'=>'Goods@providerAllowList',

    /*订单中心*/
    '301'=>'Order@index','302'=>'Order@delivery_list','303'=>'Order@refund_order_list','304'=>'Order@refund_order_info',
    '306'=>'Order@refund_order','307'=>'Order@detail','308'=>'Order@edit_order','309'=>'Order@delete_order',
    '310'=>'Order@split_order','311'=>'Order@editprice','312'=>'Order@pay_cancel', '313'=>'Order@order_print',
    '314'=>'Order@shipping_print','315'=>'Order@deliveryHandle','316'=>'Order@delivery_info','317'=>'Order@return_del',
    '318'=>'Order@return_info','319'=>'Order@refund_back','320'=>'Order@add_return_goods','321'=>'Order@order_action',
    '322'=>'Order@order_log','323'=>'Order@export_order','333'=>'Order@return_list','334'=>'Order@add_order',
    '335'=>'Order@search_goods','336'=>'Order@order_total','337'=>'Order@add_note','338'=>'Api@kuaidicx','339'=>'Order@account_edit',

    /*内容管理*/
    '401'=>'Article@articleList','402'=>'Article@categoryList','403'=>'Article@category','404'=>'Article@article',
    '405'=>'Article@categoryHandle','406'=>'Article@aticleHandle','407'=>'Article@link','408'=>'Article@linkList',
    '409'=>'Article@linkHandle',

    /*店铺管理*/
    '451'=>'Store@storeList','452'=>'Store@store_add','453'=>'Store@store_class_info','454'=>'Store@store_class_add','455'=>'Store@store_del','456'=>'Store@store_class_save','457'=>'Store@store_class_del','458'=>'Store@store_class_list','459'=>'Store@store_info','460'=>'Store@store_info_edit','461'=>'Store@shop_application','462'=>'Store@review','463'=>'Store@apply_info',
 '464'=>'Store@pwd','465'=>'Store@change_pwd',

    /*营销推广*/
    '501'=>'Promotion@promotionList','502'=>'Ad@ad','503'=>'Ad@adList','504'=>'Ad@position','505'=>'Ad@positionList',
    '506'=>'Ad@adHandle','507'=>'Ad@positionHandle','508'=>'Ad@editAd','509'=>'Article@aticleDelete',
    '510'=>'Promotion@prom_goods_list','511'=>'Promotion@prom_goods_info','512'=>'Promotion@prom_goods_save',
    '513'=>'Promotion@prom_goods_del','514'=>'Promotion@prom_order_list','515'=>'Promotion@prom_order_info',
    '516'=>'Promotion@prom_order_save','517'=>'Promotion@prom_order_del','518'=>'Promotion@group_buy_list',
    '519'=>'Promotion@group_buy','520'=>'Promotion@groupbuyHandle','521'=>'Promotion@get_goods','522'=>'Promotion@search_goods',
    '523'=>'Promotion@flash_sale','524'=>'Promotion@flash_sale_info','525'=>'Promotion@flash_sale_del',

    /*插件工具*/
    '601'=>'Resource@resourceList','602'=>'Plugin@index','603'=>'Plugin@install','604'=>'Plugin@setting',
    '605'=>'Plugin@shipping_list','606'=>'Plugin@shipping_desc','607'=>'Plugin@shipping_print','608'=>'Plugin@shipping_list_edit',
    '609'=>'Plugin@del_area','610'=>'Plugin@add_shipping','611'=>'Plugin@del_shipping',

    /*系统设置 权限管理*/
    '701'=>'Admin@index','702'=>'Admin@modify_pwd','703'=>'Admin@admin_info','704'=>'Admin@adminHandle','705'=>'Admin@role',
    '706'=>'Admin@role_info', '707'=>'Admin@roleSave','708'=>'Admin@roleDel','709'=>'Admin@log','710'=>'Admin@departHandle',
    '711'=>'Admin@organization','712'=>'Admin@organize_info','713'=>'Admin@organizeHandle', '714'=>'Admin@depart_info',
    '715'=>'Admin@department', '716'=>'Admin@supplier','717'=>'Admin@supplier_info','718'=>'Admin@supplierHandle',
    '719'=>'Pickup@index','720'=>'Pickup@add','721'=>'Pickup@edit_address','722'=>'Pickup@del','723'=>'SmsTemplate@index',
    '725'=>'SmsTemplate@addEditSmsTemplate','726'=>'SmsTemplate@delTemplate','727'=>'Template@templateList',
    '728'=>'Template@changeTemplate','729'=>'System@index','731'=>'System@navigationList',
    '732'=>'System@addEditNav', '733'=>'System@delNav','734'=>'System@handle','735'=>'System@ClearGoodsHtml',
    '736'=>'System@ClearGoodsThumb', '737'=>'System@ClearAritcleHtml','738'=>'System@send_email','739'=>'System@right_list',
    '740'=>'System@edit_right','741'=>'System@add_right','742'=>'System@right_del','743'=>'Tools@index','744'=>'Tools@export-sql',
    '745'=>'Tools@restore-sql','746'=>'Tools@import','747'=>'Tools@optimize','748'=>'Tools@repair','749'=>'Tools@restoreUpload-sql',
    '750'=>'Tools@downFile','751'=>'Tools@del','752'=>'Tools@downFile','754'=>'Tools@region','755'=>'Tools@getParentRegionList',
    '756'=>'Tools@regionHandle','757'=>'System@cleanCache','758'=>'Admin@bindProviderList','759'=>'Admin@viewBindProvider','760'=>'Admin@addBindProvider','761'=>'System@index',

    /*统计报表*/
    '801'=>'Report@reportIndex','802'=>'Report@saleTop','803'=>'Report@userTop','804'=>'Report@saleList','805'=>'Report@user',
    '806'=>'Report@finance',

    /*财务管理*/
    '901'=>'Finance@index','902'=>'Finance@withdrawals','903'=>'Finance@remittance','904'=>'Finance@editMemberWithdrawals',
    '905'=>'Finance@getWithdrawTotal',


);

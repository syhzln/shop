<?php
/**
 * Created by PhpStorm
 * User: ${fzq}
 * Date: 2017/10/16
 * Time: 9:41
 * Description:商户权限中心
 * 新权限必须每一个 function对应一个权限id  例如 '1001'=>'User@editUser';
 * ajax请求不验证权限,故可不添加 例如 ( request()->isAjax() || strpos($act,'ajax')!== false )  Index控制器不验证权限 无需添加;
 * 格式:  '1001'=>'控制器名@方法名'  ****权限名称,权限id不能;重复包括商家权限和平台权限
 * 警告: 权限id一旦录入 严禁修改
 */
return array(
    /*商品管理*/
    '1001'=>'Goods@goodsList','1002'=>'Goods@delGoods','1003'=>'Comment@index','1004'=>'Comment@detail','1005'=>'Comment@del',
    '1006'=>'Comment@op','1007'=>'Comment@ask_list','1008'=>'Comment@consult_info','1009'=>'Comment@ask_handle',
    '1010'=>'Goods@categoryList','1011'=>'Goods@delGoodsCategory','1012'=>'Goods@addEditCategory','1016'=>'Goods@stock_list',
    '1017'=>'Goods@addEditGoods','1018'=>'Goods@goodsTypeList','1019'=>'Goods@addEditGoodsType','1020'=>'Goods@delGoodsType',
    '1021'=>'Goods@goodsAttributeList','1022'=>'Goods@addEditGoodsAttribute','1023'=>'Goods@updateField','1024'=>'Goods@delGoodsAttribute',
    '1026'=>'Goods@brandList','1027'=>'Goods@addEditBrand','1028'=>'Goods@delBrand','1029'=>'Goods@specList',
    '1030'=>'Goods@delGoodsSpec','1031'=>'Goods@addEditSpec','1032'=>'Goods@del_goods_images','1033'=>'Goods@initGoodsSearchWord',


    /*订单物流*/
    '2001'=>'Order@index','2002'=>'Order@delivery_list','2003'=>'Order@refund_order_list','2004'=>'Order@refund_order_info',
    '2006'=>'Order@refund_order','2007'=>'Order@detail','2008'=>'Order@edit_order','2009'=>'Order@delete_order',
    '2010'=>'Order@split_order','2011'=>'Order@editprice','2012'=>'Order@pay_cancel', '2013'=>'Order@order_print',
    '2014'=>'Order@shipping_print','2015'=>'Order@deliveryHandle','2016'=>'Order@delivery_info','2017'=>'Order@return_del',
    '2018'=>'Order@return_info','2019'=>'Order@refund_back','2020'=>'Order@add_return_goods','2021'=>'Order@order_action',
    '2022'=>'Order@order_log','2023'=>'Order@export_order','2033'=>'Order@return_list','2034'=>'Order@add_order',
    '2035'=>'Order@search_goods',

    /*店铺管理*/
    '3001'=>'Content@contentList',

    /*统计报表*/
    '5001'=>'Report@reportIndex','5002'=>'Report@saleTop','5003'=>'Report@saleList','5004'=>'Report@finance','5005'=>'Report@withdraw','5006'=>'Report@remittance','5007'=>'Report@settlement','5008'=>'Report@unsettledOrder',


    /*系统设置*/
    '6001'=>'Admin@index','6002'=>'Admin@modify_pwd','6003'=>'Admin@admin_info','6004'=>'Admin@adminHandle','6005'=>'Admin@role',
    '6006'=>'Admin@role_info', '6007'=>'Admin@roleSave','6008'=>'Admin@roleDel','6009'=>'Admin@log','6010'=>'Admin@departHandle',
    '6011'=>'Admin@organization','6012'=>'Admin@organize_info','6013'=>'Admin@organizeHandle', '6014'=>'Admin@depart_info',
    '6015'=>'Admin@department', '6016'=>'Admin@supplier','6017'=>'Admin@supplier_info','6018'=>'Admin@supplierHandle',
    '6019'=>'Pickup@index','6020'=>'Pickup@add','6021'=>'Pickup@edit_address','6022'=>'Pickup@del','6023'=>'SmsTemplate@index',
    '6025'=>'SmsTemplate@addEditSmsTemplate','6026'=>'SmsTemplate@delTemplate','6027'=>'Template@templateList',
    '6028'=>'Template@changeTemplate','6029'=>'System@index','6031'=>'System@navigationList',

    /*财务管理*/
    '8001'=>'Financial@financialIndex',

    /*客服消息*/
    '9056'=>'Admin@set_service','9057'=>'Admin@system_message','9058'=>'Admin@set_read',

);
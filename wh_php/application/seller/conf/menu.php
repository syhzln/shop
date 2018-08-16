<?php
return	array(

	'shop'=>array('name'=>'商家中心','child'=>array(
				array('name' => '商品管理','child' => array(
					array('name' => '商品列表', 'act'=>'goodsList', 'op'=>'Goods'),
					array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'op' => 'Goods'),
					array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),
//                    array('name' => '自定义分类', 'act'=>'categoryList', 'op'=>'Goods'),
					array('name' => '评论列表', 'act'=>'index', 'op'=>'Comment'),
					array('name' => '商品咨询', 'act'=>'ask_list', 'op'=>'Comment'),
                                    
			)),
			array('name' => '订单管理','child'=>array(
					array('name' => '订单列表', 'act'=>'index', 'op'=>'Order'),
					array('name' => '发货单', 'act'=>'delivery_list', 'op'=>'Order'),
					//array('name' => '退款单', 'act'=>'refund_order_list', 'op'=>'Order'),
					array('name' => '退换货', 'act'=>'return_list', 'op'=>'Order'),
                    array('name'=>'发货设置','act'=>'index','op'=>'Plugin'),
			        array('name' => '订单日志','act'=>'order_log','op'=>'Order'),
			)),
			array('name' => '店铺管理','child' => array(
//					array('name' => '店铺设置', 'act'=>'store_setting', 'op'=>'Store'),
//					array('name' => '店铺装修', 'act'=>'group_buy_list', 'op'=>'Store'),
//					array('name' => '店铺导航', 'act'=>'navigation', 'op'=>'Store'),
                    array('name' => '经营类目', 'act'=>'store_class_list', 'op'=>'Store'),
                    array('name' => '店铺信息', 'act'=>'store_info', 'op'=>'Store'),
//					array('name' => '店铺分类', 'act'=>'category', 'op'=>'Store'),
//					array('name' => '店铺关注', 'act'=>'store_collect', 'op'=>'Store'),
			)),

			array('name' => '统计','child' => array(
					array('name' => '店铺概况', 'act'=>'index', 'op'=>'Report'),
					array('name' => '销售排行', 'act'=>'saleTop', 'op'=>'Report'),
					array('name' => '运营报告', 'act'=>'finance', 'op'=>'Report'),

			)),
            array('name' => '账号组','child'=>array(
                array('name' => '账号列表', 'act'=>'index', 'op'=>'Admin'),
                array('name' => '角色列表', 'act'=>'role', 'op'=>'Admin'),
                array('name' => '账号日志', 'act'=>'log', 'op'=>'Admin'),
            )),

            array('name' => '财务管理','child' => array(
                array('name' => '提现申请', 'act'=>'withdraw', 'op'=>'Report'),
                array('name' => '汇款记录', 'act'=>'remittance', 'op'=>'Report'),
                array('name' => '商家结算记录', 'act'=>'settlement', 'op'=>'Report'),
                array('name' => '未结算订单', 'act'=>'unsettledOrder', 'op'=>'Report'),
            )),



            //array('name' => '售后服务','child'=>array(
            //      array('name' => '咨询管理', 'act'=>'articleList', 'op'=>'Article'),
            //        array('name' => '退换货管理', 'act'=>'categoryList', 'op'=>'Article'),
            //        array('name' => '投诉管理', 'act'=>'categoryList', 'op'=>'Article'),

            //    )),

            array('name' => '客服消息','child'=>array(
                array('name' => '客服设置', 'act'=>'set_service', 'op'=>'Admin'),
                array('name' => '系统消息', 'act'=>'system_message', 'op'=>'Admin'),
            )),

	)),

);
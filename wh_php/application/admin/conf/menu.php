
<?php
return	array(	
	'system'=>array('name'=>'系统','child'=>array(
				array('name' => '设置','child' => array(
						array('name'=>'商城设置','act'=>'index','op'=>'System'),
						//array('name'=>'支付方式','act'=>'index1','op'=>'System'),
						array('name'=>'地区&配送','act'=>'region','op'=>'Tools'),
						array('name'=>'短信模板','act'=>'index','op'=>'SmsTemplate'),
						//array('name'=>'接口对接','act'=>'index3','op'=>'System'),
						//array('name'=>'验证码设置','act'=>'index4','op'=>'System'),
						array('name'=>'自定义导航栏','act'=>'navigationList','op'=>'System'),
						array('name'=>'友情链接','act'=>'linkList','op'=>'Article'),
						array('name'=>'清除缓存','act'=>'cleanCache','op'=>'System'),
						array('name'=>'自提点','act'=>'index','op'=>'Pickup'),
				)),
				array('name' => '会员','child'=>array(
						array('name'=>'会员列表','act'=>'index','op'=>'User'),
						array('name'=>'买呗列表','act'=>'memberBuyList','op'=>'User'),
						array('name'=>'充值记录','act'=>'recharge','op'=>'User'),
//						array('name'=>'提现申请','act'=>'withdrawals','op'=>'User'),
//						array('name'=>'会员操作','act'=>'memberOperate','op'=>'User'),
						array('name'=>'消息中心','act'=>'message','op'=>'User'),
                        array('name'=>'短信记录','act'=>'shortMsg','op'=>'User'),
                        //array('name'=>'层级关系','act'=>'relation','op'=>'User'),
                        //array('name'=>'树形图','act'=>'relation1','op'=>'User'),
                        array('name'=>'会员工单','act'=>'user_message','op'=>'User'),
                        array('name'=>'已完结工单','act'=>'finished_message','op'=>'User'),
                        array('name'=>'短信通告','act'=>'message_notice','op'=>'User'),

                )),
				array('name' => '广告','child' => array(
						array('name'=>'广告列表','act'=>'adList','op'=>'Ad'),
						array('name'=>'广告位置','act'=>'positionList','op'=>'Ad'),
				)),
				array('name' => '文章','child'=>array(
						array('name' => '文章列表', 'act'=>'articleList', 'op'=>'Article'),
						array('name' => '文章分类', 'act'=>'categoryList', 'op'=>'Article'),
						//array('name' => '帮助管理', 'act'=>'help_list', 'op'=>'Article'),
						//array('name'=>'友情链接','act'=>'linkList','op'=>'Article'),
						//array('name' => '公告管理', 'act'=>'notice_list', 'op'=>'Article'),
						array('name' => '专题列表', 'act'=>'topicList', 'op'=>'Topic'),
				)),
				array('name' => '权限','child'=>array(
						array('name' => '管理员列表', 'act'=>'index', 'op'=>'Admin'),
                        array('name' => '组织管理','act'=>'organization','op'=>'Admin'),
                        array('name' =>'部门管理','act'=>'department','op'=>'Admin'),
                        array('name' => '角色管理', 'act'=>'role', 'op'=>'Admin'),
						array('name'=>'权限资源列表','act'=>'right_list','op'=>'System'),
						array('name' => '管理员日志', 'act'=>'log', 'op'=>'Admin'),
//						array('name' => '供应商列表', 'act'=>'supplier', 'op'=>'Admin'),
				)),
			
				array('name' => '模板','child'=>array(
						array('name' => '模板设置', 'act'=>'templateList', 'op'=>'Template'),
				)),
				array('name' => '数据','child'=>array(
						array('name' => '数据备份', 'act'=>'index', 'op'=>'Tools'),
						array('name' => '数据还原', 'act'=>'restore', 'op'=>'Tools'),
						//array('name' => '数据恢复', 'act'=>'log', 'op'=>'Admin'),
						//array('name' => 'SQL查询', 'act'=>'log', 'op'=>'Admin'),
				))
	)),
		
	'shop'=>array('name'=>'商城','child'=>array(
				array('name' => '商品','child' => array(
					array('name' => '商品列表', 'act'=>'goodsList', 'op'=>'Goods'),
                    array('name' => '商品分类', 'act'=>'categoryList', 'op'=>'Goods'),
					array('name' => '经营类目审核', 'act'=>'providerAllowList', 'op'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'op' => 'Goods'),
					array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),
					//array('name' => '商品属性', 'act'=>'goodsAttributeList', 'op'=>'Goods'),
					array('name' => '评论列表', 'act'=>'index', 'op'=>'Comment'),
                    array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),
					//array('name' => '商品咨询', 'act'=>'ask_list', 'op'=>'Comment'),
                                    
			)),
                array('name' => '店铺','child' => array(
                    array('name' => '店铺列表', 'act'=>'storeList', 'op'=>'Store'),
                    array('name' => '申请类目审核', 'act'=>'store_class_list', 'op'=>'Store'),
                    array('name' => '开店申请', 'act'=>'shop_application', 'op'=>'Store'),


			)),
			array('name' => '订单','child'=>array(
					array('name' => '订单列表', 'act'=>'index', 'op'=>'Order'),
					//array('name' => '发货单', 'act'=>'delivery_list', 'op'=>'Order'),
					//array('name' => '退款单', 'act'=>'refund_order_list', 'op'=>'Order'),
					array('name' => '退换货', 'act'=>'return_list', 'op'=>'Order'),
					array('name' => '添加订单', 'act'=>'add_order', 'op'=>'Order'),
			        array('name' => '订单日志','act'=>'order_log','op'=>'Order'),
                    array('name' => '订单数据','act'=>'order_total','op'=>'Order'),
			)),
			/*array('name' => '促销','child' => array(
					array('name' => '抢购管理', 'act'=>'flash_sale', 'op'=>'Promotion'),
					array('name' => '团购管理', 'act'=>'group_buy_list', 'op'=>'Promotion'),
					array('name' => '优惠促销', 'act'=>'prom_goods_list', 'op'=>'Promotion'),
					array('name' => '订单促销', 'act'=>'prom_order_list', 'op'=>'Promotion'),
					array('name' => '优惠券','act'=>'index', 'op'=>'Coupon'),
//					array('name' => '预售管理','act'=>'pre_sell_list', 'op'=>'Promotion'),
			)),*/

	     
    	    array('name' => '微信','child' => array(
    	        array('name' => '公众号配置', 'act'=>'index', 'op'=>'Wechat'),
    	        array('name' => '微信菜单管理', 'act'=>'menu', 'op'=>'Wechat'),
    	        array('name' => '文本回复', 'act'=>'text', 'op'=>'Wechat'),
    	        //array('name' => '图文回复', 'act'=>'img', 'op'=>'Wechat'),
    	    )),

            array('name' => '账务','child' => array(
                array('name' => '账户列表', 'act'=>'account_list', 'op'=>'Finance'),
                array('name' => '账户转账', 'act'=>'transfer', 'op'=>'Finance'),
                array('name' => '商家提现', 'act'=>'store_withdrawals', 'op'=>'Finance'),
                array('name' => '商家结算', 'act'=>'providerSetlement', 'op'=>'Finance'),
                array('name' => '商家汇款', 'act'=>'store_remittance', 'op'=>'Finance'),
                array('name' => '会员提现', 'act'=>'withdrawals', 'op'=>'Finance'),
                array('name' => '会员汇款', 'act'=>'remittance', 'op'=>'Finance'),
                array('name' => '会员提现汇总', 'act'=>'getWithdrawTotal', 'op'=>'Finance'),
                array('name' => '会员提现申请详情', 'act'=>'editMemberWithdrawals', 'op'=>'Finance'),
                array('name' => '商家提现申请详情', 'act'=>'editStoreWithdrawals', 'op'=>'Finance'),
                array('name' => '支出', 'act'=>'pay_out', 'op'=>'Finance'),

            )),


			
			array('name' => '统计','child' => array(
					array('name' => '销售概况', 'act'=>'index', 'op'=>'Report'),
					array('name' => '销售排行', 'act'=>'saleTop', 'op'=>'Report'),
					array('name' => '会员排行', 'act'=>'userTop', 'op'=>'Report'),
					array('name' => '销售明细', 'act'=>'saleList', 'op'=>'Report'),
					array('name' => '会员统计', 'act'=>'user', 'op'=>'Report'),
					array('name' => '运营概览', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '财务指标', 'act'=>'target', 'op'=>'Report'),
			)),
	)),
		
	'mobile'=>array('name'=>'模板','child'=>array(
			array('name' => '设置','child' => array(
					array('name' => '模板设置', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '手机支付', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '微信二维码', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '第三方登录', 'act'=>'templateList', 'op'=>'Template'),
					array('name' => '导航管理', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '广告管理', 'act'=>'finance', 'op'=>'Report'),
					array('name' => '广告位管理', 'act'=>'finance', 'op'=>'Report'),
			)),
	)),
		
	'resource'=>array('name'=>'插件','child'=>array(
			array('name' => '云服务','child' => array(
				array('name' => '插件库', 'act'=>'index', 'op'=>'Plugin'),
				//array('name' => '数据备份', 'act'=>'index', 'op'=>'Tools'),
				//array('name' => '数据还原', 'act'=>'restore', 'op'=>'Tools'),
			)),
            array('name' => 'App','child' => array(
				array('name' => '系统更新', 'act'=>'index', 'op'=>'MobileApp'),
			))
	)),


);
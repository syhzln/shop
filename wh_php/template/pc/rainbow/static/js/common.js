/**
 *  该公共js文件需要放在public/global.js下面，因为需要getCookie方法
 */
$(function(){
    var sUrl =  location.href;
    var param = sUrl.substr(sUrl.indexOf('flags'),7);
    if(param == 'flags/1'){
    	var flag = 1;
	}else{
    	flag = '';
	}
    get_cart_num(flag);
	user_login_or_no();
})


/****购物车 start****/
function get_cart_num(flag) {
	var cart_cn = getCookie('cn');
	var curLogin = getCookie('curLogin');
	if (curLogin.length==0) {
        $('#cart_quantity').html('0');
        $('#tab_cart_num').html('0');
	}else{
        $.ajax({
            type: "GET",
            url: "/index.php?m=Home&c=Cart&a=header_cart_list&flag="+flag,//+tab,
            success: function (data) {
                cart_cn = getCookie('cn');
                $('#cart_quantity').html(cart_cn);
                $('#tab_cart_num').html(cart_cn);
            }
        });
	}
	// $('#tab_cart_num').html(cart_cn);
	// $('#cart_quantity').html(cart_cn);
	$('#miniCartRightQty').html(cart_cn);
}


var header_cart_list_over = 0;
$('#hd-my-cart').hover(function () {
	var special = $('#hd-my-cart').attr('value');
	$('#show_minicart').show();
	if (header_cart_list_over == 1)
		return false;
	header_cart_list_over = 1;
    var sUrl =  location.href;
    var param = sUrl.substr(sUrl.indexOf('flags'),7);
    if(param == 'flags/1'){
       var flag = 1;
    }else{
    	flag = special;
	}
	$.ajax({
		type: "GET",
		url: "/index.php?m=Home&c=Cart&a=header_cart_list&flag="+flag,//+tab,
		success: function (data) {
			$("#hd-my-cart > #show_minicart").html(data);
			get_cart_num(flag);
		}
	});
}, function () {
	$('#show_minicart').hide();
	(typeof(t) == "undefined") || clearTimeout(t);
	t = setTimeout(function () {
		header_cart_list_over = 0; /// 标识鼠标已经离开
	}, 2000);
});


// ajax 刷新购物车的商品
function header_cart_del(cart_id) {
    var curLogin = getCookie('curLogin');
    if(curLogin.length == 0){
        return;
    }
	$.ajax({
		type: "POST",
		url: "/index.php?m=Home&c=Cart&a=ajaxDelCart",
		data: {cart_id:cart_id},
		dataType: 'json',
		success: function (data) {
			if (data.status == 1) {
				header_cart_list_over = 0; /// 标识鼠标已经离开
				$("#hd-my-cart").trigger('mouseenter');	 // 无法触发 hover 改为 trigger('mouseenter');
                ajax_side_cart_list()  //解决侧边栏购物车点击删除后不能立即更新 lxl
			}
		}
	});
}
//侧边栏购物车
function ajax_side_cart_list() {
	var flag = getCookie('flag');
        $.ajax({
            type: "GET",
            url: "/index.php?m=Home&c=Cart&a=header_cart_list&template=ajax_side_cart_list&flag="+flag,//+tab,
            success: function (data) {
                cart_cn = getCookie('cn');
                console.log(cart_cn);
                if(cart_cn ==null || cart_cn ==undefined || cart_cn ==""){
					cart_cn =0;
                }
                $('#cart_quantity').html(cart_cn);
                $('#tab_cart_num').html(cart_cn);
                $('.shop-car-sider').html(data);
            }
        });

}
/*******购物车 end********/

/*******用户登录变化class****/
function user_login_or_no()
{
	var uname = getCookie('uname');
	if (uname == '') {
		$('.islogin').remove();
		$('.nologin').show();
	} else {
		$('.nologin').remove();
		$('.islogin').show();
		$('.userinfo').html(decodeURIComponent(uname).substring(0,10));
	}
}

/*******ajax 图片懒加载****/
function lazy_ajax()
{
	$(".lazy").lazyload({
		placeholder : "images/white.gif",
		effect: "fadeIn",
		threshold: 20,
		vertical_only: false,
		no_fake_img_loader:true
	});
}

/*******鼠标滑过产品列表效果****/
// $(".hoste_ri li a").mouseenter(function(){
// 	$(this).children("div:even").animate({
// 		"width":"100%",
// 	},500).css("background","#e23435");
// 	$(this).children("div:odd").animate({
// 		"height":"100%",
// 	},500).css("background","#e23435");
// });

// $(".hoste_ri li a").mouseleave(function(){
// 	$(this).children("div:even").animate({
// 		"width":"0%",
// 	},500).css("background","#e23435");
// 	$(this).children("div:odd").animate({
// 		"height":"0%",
// 	},500).css("background","#e23435");

// });
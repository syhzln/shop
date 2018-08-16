
/**
 * 获取城市
 * @param t  省份select对象
 */
function get_city(t){
    var code = $(t).val();

    $('#district').empty().css('display','none');
    $('#twon').empty().css('display','none');
    var url = '/index.php?m=Home&c=Api&a=getCity&code='+ code;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option value="0">选择城市</option>'+ v;
            $('#city').empty().html(v);
        }
    });
}

/**
 * 获取地区
 * @param t  城市select对象
 */
function get_area(t){
    var code = $(t).val();

    $('#district').empty().css('display','inline');
    $('#twon').empty().css('display','none');
    var url = '/index.php?m=Home&c=Api&a=getArea&code='+ code;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option>选择区域</option>'+ v;
            $('#district').empty().html(v);
        }
    });
}
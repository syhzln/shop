<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <script src='http://code.jquery.com/jquery-latest.js'></script>
</head>
<body>
<form method="post">
    姓名：<input type="text" id="name"><br><br>
    年龄：<input type="text" id="age"><br><br>
    <button type="button" onclick="useradd()">提交</button>
</form>
<script>
    var name = $("#name").val();
    var age = $("#age").val();
    function useradd() {
        $.ajax({
            type : "post",
            url  :  "<?php echo U('home/index/add');?>",
            data : {'name':name,'age':age},
            success :function (data) {
                if (data){
                    alert("添加成功");
                }else{
                    alert("添加失败");
                }
            }
        })
    }
</script>
</body>
</html>
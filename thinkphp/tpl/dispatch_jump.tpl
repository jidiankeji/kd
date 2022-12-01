{__NOLAYOUT__}

{php}
   $title = $code == 1 ? '操作提示' :'操作提示';
   $style = $code == 1 ? 'background-color:#b9914c;color:#fff;' :'background-color:#b9914c;color:#fff;';
{/php}


<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta charset="utf-8">
<title>跳转提示</title>
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<script src="/static/default/wap/other/layer.js"></script>
 
  
    <style type="text/css">
        *{padding:0;margin: 0; }
        body{background:#fff;font-family: "Microsoft Yahei","Helvetica Neue",Helvetica,Arial,sans-serif;color:#333;font-size:16px;}
        .jump{display:none}
        .system-message{padding: 24px 48px;}
        .system-message h1{font-size: 100px;font-weight: normal;line-height:120px; margin-bottom:12px;}
        .system-message .jump{padding-top: 10px;}
        .system-message .jump a{color: #333;}
        .system-message .success,.system-message .error{line-height:1.8em;font-size:36px;}
        .system-message .detail{font-size: 12px; line-height:20px;margin-top: 12px;display:none;}
    </style>
</head>
<body>


<p class="jump">
   <a id="href" href="<?php echo($url);?>">跳转</a> 等待时间： <b id="wait"><?php echo($wait);?></b>
</p> 

       
  
    
<script type="text/javascript">
	(function(){
		
		var code = "{$title}";
		var style = "{$style}";
		var content = "{$msg}<br>{$wait}秒中后自动跳转";
		layer.open({
			title:[code,style],
			content:content,
			anim: 'up',
			btn: ['返回', '确认'],
			yes: function(index){
			  location.href = "{:url('wap/index/index')}";
			  layer.close(index);
			}
		});
		
		var wait = document.getElementById('wait'),
			href = document.getElementById('href').href;
		var interval = setInterval(function(){
			var time = --wait.innerHTML;
			if(time <= 0){
				location.href = href;
				clearInterval(interval);
			};
		}, 1000);
	})();
</script>
</body>
</html>

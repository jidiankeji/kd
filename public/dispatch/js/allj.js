///$("body").append($("p:first").clone(true)); 复制节点包含属性

//
 

function loading(){ 
	$('#loading').remove(); 
	var htmls = '<div id="loading" style="width: 100%;height: 100%;position: fixed;left: 0px;top: 0px;background: rgba(164, 156, 156, 0.23);z-index: 1;"><div id="loaingimg" style="position: absolute;"> </div></div>';
	$('body').append(htmls);
	 var kuandu =($(window).width())/2;
	  var topheight =$(document).scrollTop()+($(window).height() - $('#loaingimg').height())/2;
	  $('#loading').css("height",$('body').height());
	$('#loaingimg').css('left',kuandu);
    $('#loaingimg').css('top',topheight); 
}
function hideloading(){
	$('#loading').remove(); 
}

$(document).ready(function(){  
	/*
	$(window).scroll(function(){
		if($(this).scrollTop()!=0){			
			if($('.hc_content').html() != undefined){
			var offset = $('.hc_content').offset();						
			$('#toTop').css('left',offset.left+1001);
			$('#toTop').fadeIn();
		 }
		}else{
			if($('.hc_content').html() != undefined){
			$('#toTop').fadeOut();
		}
		} 
	});
	
	$('#toTop').click(function(){
		//back to top
		$('body,html').animate({scrollTop:0},400);
	});*/
	$('.top1_menu li').bind("hover", function() {   
		$(this).addClass('on').siblings().removeClass('on');
	});
  var setonobj = $('.top1_menu').find('li');
   $.each(setonobj, function(i,val){   
   	var checkstr = $(val).attr('data');
   	if(checkstr.indexOf(controllername) != -1){
   	  $(val).addClass('on');
   	}

  });
  
  
  var windowHeight = $(window).height();
  $('.admin_conter').css({'height':(windowHeight-56)+'px','overflow':'auto'});
  $('.admin_left').css({'overflow-y':'auto'});
   
}) 
 
function freshcode()
{ 	
	var radom = 1+Math.round(Math.random()*1000); 
	$('#captchaimg').attr('src',siteurl+'/'+mainindex+'?ctrl=site&action=getCaptcha&random='+radom); 
}
function outputphp(info)
{
	alert(info);
}
function myajax(url,info)
{
	$.ajax({
       type: 'POST',
       async:false,
       url: url.replace('@random@', 1+Math.round(Math.random()*1000)),
       data: info,
      dataType: 'json',success: function(content) { 
      	if(content.error == true)
      	{
      		alert(content.msg);
      	}else if(content.error == false)
      	{
      		location.reload();  
      	}else{
      	  alert(content);
      	} 
		  },
      error: function(content) { 
      	alert('传送数据失败');
	   }
   });  
}
function ajaxback(url,info)
{
	var backmessage = {'flag':true,'content':''};
	$.ajax({
       type: 'POST',
       async:false,
       url: url.replace('@random@', 1+Math.round(Math.random()*1000)),
       data: info,
      dataType: 'json',success: function(content) { 
      	if(content.error == false)
      	{
      	 
      	   backmessage['flag'] = false;
      	   backmessage['content'] = content.msg;
      	  // alert(backmessage['flag']);
      	}else{
      		if(content.error == true)
      	  { 
      	  	backmessage['content'] = content.msg;
      	  }else{
      	   backmessage['content'] = content;
      	  }
        }
      	
		  },
      error: function(content) { 
      backmessage['content'] = '数据获取失败';
	   }
   });  
   return backmessage;
}
function htmlback(url,info)
{
	var backmessage = {'flag':true,'content':''};
	$.ajax({
       type: 'POST',
       async:false,
       url: url.replace('@random@', 1+Math.round(Math.random()*1000)),
       data: info,
      dataType: 'html',success: function(content) {  
	   backmessage['flag'] = false;
      	   backmessage['content'] = content;
       
      	
		  },
      error: function(content) { 
      backmessage['content'] = '数据获取失败';
	   }
   });  
   return backmessage;
}
function subform(newurl,obj)
{
	var url = $(obj).attr('action'); 
	$.ajax({
     type: 'post',
     async:false,
     data:$(obj).serialize(),
     url: url.replace('@random@', 1+Math.round(Math.random()*1000)), 
     dataType: 'json',success: function(content) {   
     	if(content.error == false){
     		diasucces('操作成功',newurl);
     	}else{
     		if(content.error == true)
     		{
     			diaerror(content.msg); 
     		}else{
     			diaerror(content); 
     		}
     	} 
		},
    error: function(content) { 
    	diaerror('数据获取失败'); 
	  }
   });   
   
	return false;
}
function diaerror(msg)
{
	var dialog =  art.dialog({
    time: 2,
    content: msg
  });
}
function artsucces(msg)
{
	var dialog =  art.dialog({
    time: 1,
    content: msg
  });
}
function showop(msg)
{
	$('#tipInfo').show();
}
function hideop()
{
	$('#tipInfo').hide();
}
function diasucces(msg,linkurl)
{
	    alert(msg);
    	if(linkurl == null || linkurl== '' || linkurl == undefined)
	    { 
	    	window.location.reload();
	    }else{
	    	 
	    	window.location.href=linkurl;
	    } 
} 
function remind(obj){
  if(confirm('确定操作吗？')){
    var url = $(obj).attr('href'); 
	 $.ajax({
     type: 'get',
     async:false,
     data:$(obj).serialize(),
     url: url.replace('@random@', 1+Math.round(Math.random()*1000)), 
     dataType: 'json',success: function(content) {  
     	if(content.error == false){
     		diasucces('操作成功','');
     	}else{
     		if(content.error == true)
     		{
     			diaerror(content.msg); 
     		}else{
     			diaerror(content); 
     		}
     	} 
		},
    error: function(content) { 
    	diaerror('数据获取失败'); 
	  }
   });   
  }
  return false;
}
function collect(obj){
	 var url = $(obj).attr('href'); 
	 $.ajax({
     type: 'get',
     async:false, 
     url: url.replace('@random@', 1+Math.round(Math.random()*1000)), 
     dataType: 'json',success: function(content) {  
     	if(content.error == false){ 
     		   freshshopcllect(); 
     	}else{
     		if(content.error == true)
     		{
     			diaerror(content.msg); 
     		}else{
     			diaerror(content); 
     		}
     	} 
		},
    error: function(content) { 
    	diaerror('数据获取失败'); 
	  }
   }); 
     
   return false;
} 
function Is_Chuname(str){
	 var reg = /[^\u4e00-\u9fa5]/;
    if(str.length < 1){
     return false;
    }else{
	    if (reg.test(str))
	    {
	  	 return false;
	    }else{
	   return true;
	   }
	} 
}
function Is_phone(str){
var reg = /^1+(3|5|8)+\d{9}$/;
return reg.test(str);
}
function AddFavorite(sURL, sTitle)
{
    try
    {
        window.external.addFavorite(sURL, sTitle);
    }
    catch (e)
    {
        try
        {
            window.sidebar.addPanel(sTitle, sURL, "");
        }
        catch (e)
        {
            alert("加入收藏失败，请使用Ctrl+D进行添加");
        }
    }
}
function checkall()
{  
	var checkinfo = $('#chkall').attr('checked'); 
	if(checkinfo ==  'checked'){ 
		$("input[name='id[]']").attr("checked",true); 
	}else{
		$("input[name='id[]']").attr("checked",false); 
	} 
}
function checkword(flag)
{ 
		 $('#chkall').attr('checked',flag); 
	checkall();
}
var click_button = false;
function doubleclick(){
    click_button = false;
}

function lockclick(){
    if(click_button == false){
        click_button = true;
        setTimeout("doubleclick()", 1000);
        return true;
    }else{
        return false;
    }
}
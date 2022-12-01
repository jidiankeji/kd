//重写toFixed方法  
Number.prototype.toFixed=function(len) {  
    var tempNum = 0;  
    var s,temp;  
    var s1 = this + "";  
    var start = s1.indexOf(".");  
    if(s1.substr(start+len+1,1)>=5) {
        tempNum=1; 
    } 
    var temp = Math.pow(10,len);  
    s = Math.floor(this * temp) + tempNum;  
    return s/temp;  
}
String.prototype.toFixed=function(len)  {  
    var tempNum = 0;  
    var s,temp;  
    var s1 = this + "";  
    var start = s1.indexOf(".");  
    if(s1.substr(start+len+1,1)>=5) {
        tempNum=1;
    }
    var temp = Math.pow(10,len);  
    s = Math.floor(this * temp) + tempNum;  
    return s/temp; 
}


window.Cookie = window.Cookie || {};
window.UxLocation = window.UxLocation || {"lat":0, "lng":0, "addr":""};
window.CFG = window.CFG || {"domain":"","url":"/", "title":"外卖系统", "res":"/static", "img":"/attachs","C_PREFIX":"KT-"};
window.LoadData = window.LoadData || {"LOCK":false, "LOAD_END":false, "params":{"page":1}};


Cookie._valid_key = function(key){
    return (new RegExp("^[^\\x00-\\x20\\x7f\\(\\)<>@,;:\\\\\\\"\\[\\]\\?=\\{\\}\\/\\u0080-\\uffff]+\x24")).test(key);
}
Cookie.set = function(key, value, expire, path){
    path = path || '/';
    var C_DOMAIN = window.CFG['C_DOMAIN'] || window.CFG['domain'];
    key = window.CFG['C_PREFIX']+key;
    if(Cookie._valid_key(key)){
        var a = key + "=" + value;
        if(typeof(expire) != 'undefined'){
            var date = new Date();
            expire = parseInt(expire,10);
            date.setTime(date.getTime() + expire*1000);
            a += "; expires="+date.toGMTString();
        }
        a += ";path="+path;
        a += ";domain="+C_DOMAIN;
        document.cookie = a;
    }
    return null;
};
Cookie.get = function(key){
    key = window.CFG['C_PREFIX']+key;
    if(Cookie._valid_key(key)){
        var reg = new RegExp("(^| )" + key + "=([^;]*)(;|\x24)"),
        result = reg.exec(document.cookie);
        if(result){
            //return unescape(result[2]) || null;
            return result[2] || null;
        }
    }
    return null;
};
Cookie.remove = function(key){
    key = window.CFG['C_PREFIX']+key;
    document.cookie = key+"=;expires="+(new Date(0)).toGMTString();
};

//生成全局GUID
function GGUID(){
    var guid = '';
    for (var i = 1; i <= 32; i++) {
        var n = Math.floor(Math.random() * 16.0).toString(16);
        guid += n;
    }
    return "KT"+guid.toUpperCase();
}


window.IJH = window.IJH || {};
window.JH = window.JH || {};
var IS_ANDROID = (navigator.userAgent.indexOf("Android") >=0) ? true : false;

IJH.app_login = function(params){
    IS_ANDROID ? JH.app_login(JSON.stringify(params)) : app_login(params);
}
IJH.app_loginout = function(params){
    IS_ANDROID ? JH.app_loginout(JSON.stringify(params)) : app_loginout(params);
}
IJH.app_return_items_title = function(params){
    IS_ANDROID ? JH.app_return_items_title(JSON.stringify(params)) : app_return_items_title(params);
}
IJH.app_go_pay = function(params){
    IS_ANDROID ? JH.app_go_pay(JSON.stringify(params)) : app_go_pay(params);
}
IJH.app_coin = function(params){
    IS_ANDROID ? JH.app_coin(JSON.stringify(params)) : app_coin(params);
}
IJH.app_return_link = function(params){
    IS_ANDROID ? JH.app_return_link(JSON.stringify(params)) : app_return_link(params);
}


//判断是否为手机访问
function checkIsMobile(){
    if(/(iphone|ipad|ipod|android|windows phone)/.test(navigator.userAgent.toLowerCase())){
        return true;
    }else{
        return false;
    }
}
//判断是否为腾讯手机浏览器
function checkIsMQQBrowser(){
    if(/(mqqbrowser)/.test(navigator.userAgent.toLowerCase())){
        return true;
    }else{
        return false;
    }
}
//判断是否微信
function checkIsWeixin(){
    if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
        return true;
    }else{
        return false;
    }
}
//判断是否为APPwebView调用
function checkIsAppClient(){
    if(/(ijh.waimai|com.jhcms)/.test(navigator.userAgent.toLowerCase())){
        if(navigator.userAgent.indexOf("Android") >=0){
            return 'Android';
        }else{
            return 'IOS';
        }
    }else{
        return false;
    }
}
function checkIsAndroid(){
    if(navigator.userAgent.indexOf("Android") >=0){
        return true;
    }else{
        return false;
    }
}
//Android版本
function getAndroidVersion(){
    var index = navigator.userAgent.indexOf("Android");
    if(index >= 0){
        var androidVersion = parseFloat(navigator.userAgent.slice(index+8));
        if(androidVersion > 1){
            return androidVersion;
        }else{
            return 100;
        }
    }else{
        return 100;
    }
}


//判断是否在微信小程序
function isWeChatApplet(callback) { 
    callback = callback || function(){};
    var ua = window.navigator.userAgent.toLowerCase(); 

    if (ua.indexOf('micromessenger') == -1) {
        //不在微信或者小程序中 
        callback(false);
    } else {
        wx.miniProgram.getEnv(function(res){
            if (res.miniprogram) {
                //在小程序中
                callback(true);
            } else {
                //在微信中
                callback(false);
            }
        }) 
    }  
}
        
//Gps转百度坐标
function GpsToBaidu(lng, lat) {

    //修改此方法直接返回原始坐标 2017 12 19
    return {"lng":lng, "lat": lat};

    var x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    var x = lng;
    var y = lat;
    var z = Math.sqrt(x * x + y * y) + 0.00002 * Math.sin(y * x_pi);
    var theta = Math.atan2(y, x) + 0.000003 * Math.cos(x * x_pi);
    var bdlng = z * Math.cos(theta) + 0.0065;
    var bdlat = z * Math.sin(theta) + 0.006;
    return {"lng":bdlng.toFixed(5), "lat": bdlat.toFixed(5)};
}

function setUxLocation(uxl){
    UxLocation = uxl || {};
    sessionStorage.setItem('UxLocation',JSON.stringify(UxLocation));
    Cookie.set('UxLocation',UxLocation['lat']+','+UxLocation['lng'], 86400,"/");
	Cookie.set('UxLocationAddr',UxLocation['addr'], 86400,"/");
}

function autoUxlocation(positionlink, callback){
    callback = callback || function (res){};
    Widget.MsgBox.load();
    var LocTimer = setTimeout(function(){
        alert("获取不到你的地址");
        window.location.href = positionlink;
    }, 10000);
    getUxLocation(function (ret){
        Widget.MsgBox.hide();
        var uxl = ret || {};
        clearTimeout(LocTimer);
        if(ret.error){
            alert(ret.message);
            window.location.href = positionlink;
        }else{
            callback(uxl);
        }
    });
}

//取到当前的坐标(Biadu系坐标)
function getUxLocation(callback){
	
	console.log('===getUxLocation= callback===');
	
	
    callback = callback || function(ret){};
    if(UxLocation.lat && UxLocation.lng && UxLocation.addr){
        Widget.MsgBox.hide();
        UxLocation["error"] = 0;
        callback(UxLocation);
		
		console.log('===#pCookie.setaddress1111====');
		Cookie.set('address',UxLocation.addr, 86400,"/");
		
        return true;
    }
    if(sessionStorage.getItem("UxLocation")){
        Widget.MsgBox.hide();
        try{
            uxl = JSON.parse(sessionStorage.getItem("UxLocation")) || {};
            if(uxl.lat && uxl.lng && uxl.addr){
                setUxLocation(uxl);
                UxLocation = uxl;
                UxLocation["error"] = 0;
                callback(UxLocation);
				
				console.log('===#pCookie.setaddress222====');
				Cookie.set('address',uxl.addr, 86400,"/");
				
                return true;
            }
        }catch(e){
            setUxLocation({"lat":0, "lng":0, "addr":""});
        }
    }

    if(checkIsWeixin()){ //微信获位置坐标
	 	//alert('checkIsWeixin');
		
		console.log('===checkIsWeixin===');
		
        wx.ready(function(){
			
			//alert('getLocation');
			console.log('===wx.getLocation===');
			
            wx.getLocation({
                type: 'gcj02',
                success: function (res) {
					console.log('==success==');
					
                    Widget.MsgBox.hide();
                    UxLocation["lat"] = res.latitude.toFixed(6);
                    UxLocation["lng"] = res.longitude.toFixed(6);
                    setUxLocation(UxLocation);
					
					console.log('==UxLocation==',UxLocation);
					
                    placeinfo(UxLocation.lng, UxLocation.lat, function(ret){
						
						console.log('==wx.getLocation-placeinfo==');
						
                        UxLocation["lat"] = ret.lat.toFixed(6);
                        UxLocation["lng"] = ret.lng.toFixed(6);
                        UxLocation['addr'] = ret.name;
                        UxLocation["error"] = 0;
                        setUxLocation(UxLocation);
						
						
						console.log('===#pCookie.setaddress微信获位置坐标====');
						Cookie.set('address',ret.name, 86400,"/");
                        callback(UxLocation);
                    });
					
					console.log('==placeinfo执行错误==');
                },
                fail: function (res) {
                    alert('微信获取位置失败');
                },
                cancel: function (res) {
                    alert('用户拒绝获取位置');
                }
            });
        });
    }else{ //使用高api 定位
        if(!document.querySelector("#amap_poistion_container")){
            var map_element = document.createElement("div")
            map_element.style.width = "1px";
            map_element.style.height = "1px";
            map_element.id = "amap_poistion_container";
            document.body.appendChild(map_element);
        }
        window.gaodemap = new AMap.Map('amap_poistion_container', {
            resizeEnable: true
        });
        var geolocation;
        //加载地图，调用浏览器定位服务
        window.gaodemap.plugin('AMap.Geolocation', function() {
            geolocation = new AMap.Geolocation({
                enableHighAccuracy: true,//是否使用高精度定位，默认:true
                timeout: 10000,          //超过10秒后停止定位，默认：无穷大
                buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
                zoomToAccuracy: true,      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
                buttonPosition:'RB',
                convert: true,
                noGeoLocation:0,
                noIpLocate:1,
                extensions:'base'
            });
            window.gaodemap.addControl(geolocation);
            geolocation.getCurrentPosition(function(status, data){
                if(status=='complete'){
                    UxLocation["lng"] = data.position.getLng().toFixed(6);
                    UxLocation["lat"] = data.position.getLat().toFixed(6);
                    setUxLocation(UxLocation);
                    placeinfo(UxLocation.lng, UxLocation.lat, function(ret){
                        UxLocation["lat"] = ret.lat.toFixed(6);
                        UxLocation["lng"] = ret.lng.toFixed(6);
                        UxLocation['addr'] = ret.name;
                        UxLocation["error"] = 0;
						
						console.log('===#pCookie.setaddress高德获位置坐标1====');
						Cookie.set('address',ret.name, 86400,"/");
						
                        setUxLocation(UxLocation);
                        callback(UxLocation);
                    });
                }else{
                    UxLocation["lng"] = window.gaodemap.getCenter().getLng().toFixed(6);
                    UxLocation["lat"] = window.gaodemap.getCenter().getLat().toFixed(6);
                    setUxLocation(UxLocation);
                    placeinfo(UxLocation.lng, UxLocation.lat, function(ret){
                        UxLocation["lat"] = ret.lat.toFixed(6);
                        UxLocation["lng"] = ret.lng.toFixed(6);
                        UxLocation['addr'] = ret.name||ret.addr;
                        UxLocation["error"] = 0;
						
						console.log('===#pCookie.setaddress高德获位置坐标2====');
						Cookie.set('address',ret.name||ret.addr, 86400,"/");
						
                        setUxLocation(UxLocation);
                        callback(UxLocation);
                    });
                }
            });
        });
    }
}




function geocoder(lng, lat, callback){
    callback = callback || function(ret){};
    var callfun = GGUID();
    window[callfun] = function(ret){callback(ret);}
    $.getScript('//restapi.amap.com/v3/geocode/regeo?s=rsv3&key='+window.MAP_WEB_KEY+"&radius=500"+"&location="+lng+','+lat+"&output=json"+"&callback="+callfun);
}

function placeinfo(lng, lat, callback){
	
	console.log('==执行函数function placeinfo==');
	
	
    callback = callback || function(ret){};
    var callfun = GGUID();
	
	console.log('==callfun==',callfun);
	
    window[callfun] = function(ret){
		
		console.log('==window[callfun]---ret==',ret);
		
        if(ret.infocode ==10000){
            if(ret.pois.length > 0){
                var pos = ret.pois[0];
                var pois = [];
                $.each(ret.pois, function (k, v){
                    var obj = {
                        "pname": v.pname,
                        "cityname": v.cityname,
                        "adname": v.adname,
                        "name": v.name,
                        "address": v.address,
                        "distance": v.distance,
                        "lng": v.location.split(",")[0],
                        "lat": v.location.split(",")[1]
                    };
                    pois.push(obj);
                });
                sessionStorage.setItem('UxLocationPois', JSON.stringify(pois));
                callback({"error":0, "name":pos.name,"addr":pos.address, "lng":pos.location.split(",")[0], "lat":pos.location.split(",")[1]});
            }else{
                geocoder(lng, lat, function(ret){
                    if(ret.infocode == 10000) {
                        var calldata = {"error":0, "lng":lng, 'lat':lat, "name":"", "addr":""};
                        if (ret.regeocode.formatted_address != undefined) {
                            calldata["name"] = ret.regeocode.formatted_address;
                            calldata["addr"] = ret.regeocode.formatted_address;
                        }else{
                            calldata["name"] = calldata["addr"] = ret.addressComponent.city + ret.addressComponent.district;
                        }
                        var obj = {
                            "pname": ret.addressComponent.province,
                            "cityname": ret.addressComponent.city,
                            "adname": ret.addressComponent.district,
                            "name": calldata["name"],
                            "address": ret.regeocode.formatted_address,
                            "distance": 0,
                            "lng": lng,
                            "lat": lat
                        };
                        sessionStorage.setItem('UxLocationPois', [obj]);
                        callback(calldata);
                    }
                });
            }
        }
    }
    //学校|商务住宅|门牌信息|医院|
    $.getScript("https://restapi.amap.com/v3/place/around?s=rsv3&key="+window.MAP_WEB_KEY+"&radius=500"+"&location="+lng+','+lat+"&types=学校|商务住宅|门牌信息|医院&output=json"+"&callback="+callfun);
}

function placeapi(keywrod, city, callback){
    city = city || localStorage['UxCity'] || Cookie.get("UxCity");
    callback = callback || function(ret){};
    var callfun = GGUID();
    window[callfun] = function(ret){callback(ret);}
    $.getScript("https://restapi.amap.com/v3/place/text?s=rsv3&key="+window.MAP_WEB_KEY+"&keywords="+keywrod+"&city="+city+"&types=学校|商务住宅|门牌信息|医院&offset=20&page=1&output=json&callback="+callfun);
}

//距离输出为公里
function GetDistance(lng1,lat1,lng2,lat2){
    var radLat1 = lat1 * Math.PI / 180.0;
    var radLat2 = lat2 * Math.PI / 180.0;
    var a = radLat1 - radLat2;
    var  b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
    var s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a/2),2) +
    Math.cos(radLat1)*Math.cos(radLat2)*Math.pow(Math.sin(b/2),2)));
    s = s *6378.137 ;// EARTH_RADIUS;
    s = Math.round(s * 10000) / 10; //输出为公里
    return (s/1000);
}

// 格式化输出距离单位
function formatDistance(dist){
    dist = parseFloat(dist, 10);  //解析一个字符串，并返回一个浮点数,第二个参数表示10进制
    if(dist < 1000){
        return dist.toFixed(2) + "米";
    }else{
        return (dist/1000).toFixed(2) + "千米";
    }
}

/*
 * 跳转页面
 * （默认页面往左滑动，即 left）
 * （页面往右滑动，即 right
 */
function linkLoadPage(url, type) {
    if(checkIsMQQBrowser()){
        window.location.href = url;
        return ;
    }
    var animateCss = {}, animateAfterCss = {};
    type = type || 'left';
    switch (type) {
        case 'left':
            animateCss = {'left': '-' + $(window).width() + 'px'};
            animateAfterCss = {'left': '0px'};
            break;
        case 'right':
            animateCss = {'left': $(window).width() + 'px'};
            animateAfterCss = {'left': '0px'};
            window.location.href = url;
            return;
            break;
    }
    $('header,footer,section,#downOption,#shangjia_tab,.dianpuPrompt,.switchTab_box,.saixuan_pull').animate(animateCss, function () {
        Widget.MsgBox.load();
        window.addEventListener("pagehide", function () {
            $('header,footer,section,#downOption,#shangjia_tab,.dianpuPrompt,.switchTab_box').css(animateAfterCss);
            Widget.MsgBox.hide();
        });
        setTimeout(function () {
            window.location.href = url;
        });
    });
}


function build_refresher_items(url, json, tmpl, wapper, theme , first,type) {
    if (theme) {
        $('#wrapper ul').append('<div class="loading_ico"><img src="' + theme + '/static/images/load.gif" />正在加载中...</div>');
    }
    $.post(url, json, function (ret) {
        if (ret.error != 0) {
            layer.open({'content': ret.message});
        } else if (!ret.data.items) {
            if(first){
                if(type == 'shop'){
                    $('.loading_ico').remove();
                    $('#wrapper ul').append('<div class="youhui_no"><div class="iconBg"><i class="ico7"></i> </div><h2>暂无商铺</h2><p class="black9">抱歉，暂时没有符合您搜索的商铺！</p></div>');
                }
            }else{
                $('.loading_ico').remove();
                $("#pullUp .pullUpLabel").html('没有更多了');
            }
        } else if (ret.data.items.length == 0) {
            if(first){
                if(type == 'shop'){
                    $('.loading_ico').remove();
                    $('#wrapper ul').append('<div class="youhui_no"><div class="iconBg"><i class="ico7"></i> </div><h2>暂无商铺</h2><p class="black9">抱歉，暂时没有符合您搜索的商铺！</p></div>');
                }
            }else{
                $('.loading_ico').remove();
                $("#pullUp .pullUpLabel").html('没有更多了');
            }
        } else {
            $('.loading_ico').remove();
            $(tmpl).tmpl(ret.data.items).appendTo(wapper);
        }
    }, 'json');
}


function select(addrs,from) { //参数1：地址数组 参数2：来源

   var contant = '';
   var str = '';
   var html = '';

   html+='<div class="mask" style="bottom:0;">';
   html+='<div class="nr" style="padding:0;position:absolute;width:100%;bottom:0;">';
   html+='<div class="mask_prompt">';
   html+='<div class="title"><em></em>地址选择<span class="fr mr10" id="add_addr_span">新增地址</span></div>';
   html+='<div class="cont" style="padding:0;"><div style="height:400px;overflow-y:scroll;">';
   
   var i = 1;
   $.each(addrs, function(key, value){
    html+='<p style="text-align:left;">';
       if(value){
           i = i +1;
           if(i = 1){
             html+=('&nbsp;&nbsp;<input type="radio" name="addr" checked="checked" value="'+key+'" id="v'+key+'" style="margin:0;padding:0;font-size:0;height:" />&nbsp;<span mp="'+value.house+'" mb="'+value.mobile+'"  ct="'+value.contact+'">'+value.addr+'</span>');
           }else{
               html+=('&nbsp;&nbsp;<input type="radio" name="addr" value="'+key+'" id="v'+key+'" style="margin:0;padding:0;font-size:0;" />&nbsp;<span mp="'+value.house+'" mb="'+value.mobile+'" ct="'+value.contact+'" >'+value.addr+'</span>');
           }
       }
   html+='</p>';
   })

   html+='</div><input type="button" class="pub_btn" id="dz_btn" value="确定"><input type="button" class="cancel_btn" value="取消">';
   html+='</div>';
   html+='</div>';
   html+='</div>';
   html+='</div>';

   $("section").append(html);
   $('#dz_btn').click(function(){
       var val = ($('input[name="addr"]:checked'));
       var vid = val.attr('id');
       $('#sd').val($('#'+vid).parent().find('span').html()).css('color','#2cca77');
       $('#sd_val').val(val.val());
       $('#mp_val').val($('#'+vid).parent().find('span').attr('mp')).css('color','#2cca77');
       $('#mb_val').val($('#'+vid).parent().find('span').attr('mb')).css('color','#2cca77');
       $('#c_val').val($('#'+vid).parent().find('span').attr('ct')).css('color','#2cca77');

       if(from){
           localStorage.setItem(from+'_sd',$('#'+vid).parent().find('span').html());
           localStorage.setItem(from+'_sd_val',val.val());
           localStorage.setItem(from+'_mp_val',$('#'+vid).parent().find('span').attr('mp'));
           localStorage.setItem(from+'_mb_val',$('#'+vid).parent().find('span').attr('mb'));
           localStorage.setItem(from+'_c_val',$('#'+vid).parent().find('span').attr('ct'));
       }

       $('.mask').remove();
   })

   $('.cancel_btn').click(function(){
       $('.mask').remove();
   })

}


function select_time(time,from) {
    //time获取当前小时数
    var html = '';

    html+='<div class="mask" style="bottom:0;">';
    html+='<div class="nr" style="padding:0;position:fixed;width:100%;bottom:0;left:0;">';
    html+='<div class="mask_prompt">';
    html+='<div class="title"><em></em>时间选择</div>';
    html+='<div class="cont" style="padding:0;">';
    //时间选择结构
    html+='<div class="time" style="width:100%;">';

    html+='<div class="top">';

    html+='<ul class="title">';
    html+='<li class="no sel" val="1">今天</li><li class="no" val="2">明天</li><li class="no" val="3">后天</li><li class="no" style="border:0px" val="4">大后天</li>';
    html+='</ul>';
    html+='<div style="clear:both;"></div>';

    html+='<ul class="timers" id="ctime1">';


    for(var a = 8;a<=19;a++){

            if(parseInt(a) < parseInt(time)+1){
                html+='<li val="0">'+a+':00<span  style="color:#ff0000;">(满)</span></li>';
            }else{
                html+='<li val="'+a+'">'+a+':00</li>';
            }
 
    }

    html+='</ul>';
    html+='<div style="clear:both;"></div>';


    for(var i = 2;i<=4;i++){

        html+='<ul class="timers" id="ctime'+i+'">';

            for(var o = 8;o<=19;o++){
              
                    html+='<li val="'+o+'">'+o+':00</li>';
   
            }

        html+='</ul>';
        html+='<div style="clear:both;"></div>';

    }


    html+='</div>';


    html+='</div>';


    html+='<input type="button" class="pub_btn" id="rl_btn" value="确定"><input type="button" class="cancel_btn" value="取消">';
    html+='</div>';
    html+='</div>';
    html+='</div>';
    html+='</div>';

    $("section").append(html);

    $('#rl_btn').click(function(){
        
       var d = $(this).attr('val');
       var t = $(this).attr('tval');
       var dd = $(this).attr('dhtml');
       var tt = $(this).attr('thtml');

       if(!dd){
           dd='今天';
       }
       if(!d){
           d=1;
       }

       if(!t || t==0){
           alert('时间没有选择!');return false;
       }
       $('#sday_val').val(d);
       $('#stime_val').val(t);
       $('#stime').val(dd+tt).css('color','#2cca77');

       if(from){
           localStorage.setItem(from+'_stime',dd+tt);
           localStorage.setItem(from+'_stime_val',t);
           localStorage.setItem(from+'_sday_val',d);
       }

       $("footer").show();
       $("section").css('bottom','0.65rem');
       $('.mask').remove();
       
   })


     $('.cancel_btn').click(function(){
       $("footer").show();
       $("section").css('bottom','0.65rem');
       $('.mask').remove();
   })

   $('.title li').click(function(){
       var val = $(this).attr('val');
       $('.title li').removeClass('sel');
       $(this).addClass('sel');
       $('.timers').hide();
       $('#ctime'+val).show();
       $('.pub_btn').attr('val',val);
       var day_html = $(this).text();
       $('.pub_btn').attr('dhtml',day_html);
   })

   $('.timers li').click(function(){
       var tval = $(this).attr('val');
       if(tval > 0){
           $('.timers li').css('background','#2cca77').css('color','#ffffff');
           $(this).css('background','#ffffff').css('color','#2cca77');
       }

       $('.pub_btn').attr('tval',tval);
       var time_html = $(this).text();
       $('.pub_btn').attr('thtml',time_html);
   })


}


/*+=======================================
 + 外卖JS购物车
 +=======================================*/
window.cart_goods = window.cart_goods || {};
window.ele = {
    init: function () {
        var json = window.cookies.get('ele') || {};
        cart_goods = window.cookies.parse(json);
    },
    addcart: function (shop_id, data) {
        cart_goods[shop_id] = cart_goods[shop_id] || {};
        data['num'] = data['num'] || 1;
        if (typeof (cart_goods[shop_id][data['product_id']]) == 'undefined') {
            cart_goods[shop_id][data['product_id']] = data;
        } else if (cart_goods[shop_id][data['product_id']]['num'] >= 99) {
            layer.open({content: "店里没有那么多商品了"});
        } else {
            var num = cart_goods[shop_id][data['product_id']]['num'] || 0;
            data['num'] += parseInt(num, 10);
            cart_goods[shop_id][data['product_id']] = data;
        }
        var goods = window.cookies.stringify(cart_goods);
        window.cookies.set('ele', goods);
    },
    getcart: function () {
        return window.cart_goods;
    },
    dec: function (shop_id, product_id, num) {
        num = num || 1;
        cart_goods[shop_id] = cart_goods[shop_id] || {};
        if (typeof (cart_goods[shop_id][product_id]) == 'undefined') {
            return true;
        } else {
            cart_goods[shop_id][product_id]['num'] -= parseInt(num, 10);
        }
        var product_list = {};
        for (var k in cart_goods[shop_id]) {
            if (cart_goods[shop_id][k]['num'] > 0) {
                product_list[k] = cart_goods[shop_id][k];
            }
        }
        cart_goods[shop_id] = product_list;
        var goods = window.cookies.stringify(cart_goods);
        window.cookies.set('ele', goods);
    },
    count: function (shop_id) {
        var count = 0;
        if (typeof (cart_goods[shop_id]) != 'undefined') {
            for (var pid in cart_goods[shop_id]) {
                count += parseInt(cart_goods[shop_id][pid]['num'], 10);
            }
        }
        return count;
    },
    itemcount: function (product_id) {
        var count = 0;
        for (var sid in cart_goods) {
            for (var pid in cart_goods[sid]) {
                if (pid = product_id) {
                    count = cart_goods[sid][pid]['num'];
                    break;
                }
            }
        }
        return count;
    },
    catecount: function (shop_id) {
        var count = {};
        var goods = cart_goods[shop_id] || {};
        for (var pid in goods) {
            count[goods[pid]['cate_id']] = parseInt(count[goods[pid]['cate_id']], 10) || 0;
            count[goods[pid]['cate_id']] += parseInt(goods[pid]['num'], 10);
        }
        return count;
    },
    totalprice: function (shop_id) {
        var total_price = 0;
        var goods = cart_goods[shop_id] || {};
        for (var pid in goods) {
            //total_price += (goods[pid]['price'] + goods[pid]['package_price']) * goods[pid]['num'];
            total_price += parseFloat(goods[pid]['price'], 10) * parseInt(goods[pid]['num'], 10);
        }
        return total_price;
    },
    packprice: function (shop_id) {
        var total_price = 0;
        var goods = cart_goods[shop_id] || {};
        for (var pid in goods) {
            //total_price += (goods[pid]['price'] + goods[pid]['package_price']) * goods[pid]['num'];
            total_price += parseFloat(goods[pid]['package_price'], 10) * parseInt(goods[pid]['num'], 10);
        }
        return total_price;
    },
    removeby: function (shop_id) {
        var obj = {};
        for (var sid in cart_goods) {
            if (sid != shop_id) {
                obj[sid] = cart_goods[sid];
            }
        }
        cart_goods = obj;
        var goods = window.cookies.stringify(cart_goods);
        window.cookies.set('ele', goods);
    }
}

// 送达时间选择
function time_select(start, start_quarter, end, end_quarter) {
    start = parseInt(start, 10);
    start_quarter = parseInt(start_quarter, 10);
    end = parseInt(end, 10);
    end_quarter = parseInt(end_quarter, 10);
    var html = '';
    if (start_quarter > 0) {
        for (var q = start_quarter; q <= 3; q++) {
            if (q == 3) {
                html += '<li>' + start + ':' + q * 15 + '-' + (start + 1) + ':00' + '</li>';
            } else {
                html += '<li>' + start + ':' + q * 15 + '-' + start + ':' + (q + 1) * 15 + '</li>';
            }
        }
        if(start+1<end){
            for (var i = start + 1; i < end; i++) {
                for (var q = 0; q <= 3; q++) {
                    var end_time = i + ':' + (q + 1) * 15;
                    if (q == 3) {
                        end_time = (i + 1) + ':00';
                    }
                    var begin_time = i + ':' + q * 15;
                    if (q == 0) {
                        begin_time = i + ':00';
                    }
                    html += '<li>' + begin_time + '-' + end_time + '</li>';
                }
            }
        }
    }else if (end_quarter > 0) {
        for (var i = start; i < end; i++) {
            for (var q = 0; q <= 3; q++) {
                var end_time = i + ':' + (q + 1) * 15;
                if (q == 3) {
                    end_time = (i + 1) + ':00';
                }
                var begin_time = i + ':' + q * 15;
                if (q == 0) {
                    begin_time = i + ':00';
                }
                html += '<li>' + begin_time + '-' + end_time + '</li>';
            }
        }
        for (var q = 0; q < end_quarter; q++) {
            if (q == 0) {
                html += '<li>' + end + ':00-' + end + ':' + (q + 1) * 15 + '</li>';
            } else {
                html += '<li>' + end + ':' + q * 15 + '-' + end + ':' + (q + 1) * 15 + '</li>';
            }
        }
    }else{
        for (var i = start; i < end; i++) {
            for (var q = 0; q <= 3; q++) {
                var end_time = i + ':' + (q + 1) * 15;
                if (q == 3) {
                    end_time = (i + 1) + ':00';
                }
                var begin_time = i + ':' + q * 15;
                if (q == 0) {
                    begin_time = i + ':00';
                }
                html += '<li>' + begin_time + '-' + end_time + '</li>';
            }
        }
    }
    return html;
}


function position() {
    if (localStorage.getItem('lat') && localStorage.getItem('lng')) {
        //如果存在wx则转化为bd，并且赋值road

        var y = localStorage.getItem('lat');
        var x = localStorage.getItem('lng');

        var ggPoint = new BMap.Point(x, y);

        //坐标转换完之后的回调函数
        translateCallback = function (data) {
            if (data.status === 0) {
                var xx = data.points[0].lat;
                var yy = data.points[0].lng;

                //var map = new BMap.Map("allmap");
                var point = new BMap.Point(yy, xx);
                //map.centerAndZoom(point,12);
                var geoc = new BMap.Geocoder();

                geoc.getLocation(point, function (rs) {
                    var addComp = rs.addressComponents;
                    if (addComp) {
                        localStorage['road'] = addComp.province + ", " + addComp.city + ", " + addComp.district + ", " + addComp.street + ", " + addComp.streetNumber;
                    }
                });
            }
        }
        setTimeout(function () {
            var convertor = new BMap.Convertor();
            var pointArr = [];
            pointArr.push(ggPoint);
            convertor.translate(pointArr, 1, 5, translateCallback)
        }, 1000);
    }
}


$(document).ready(function () {
    //FastClick.attach(document.body);
    $(document).on("click", "[link-load]", function () {
        var url = $(this).attr("link-url") || $(this).attr("href");
        var type = $(this).attr('link-type') || "left";
        linkLoadPage(url, type);
        return false;
    });
});

//获取地址参数
window.getURLArgs = function()
{
    var search = decodeURIComponent(location.search);
    if(search != ''){
        search = search.substring(1, search.length);
        search = search.split('&');
        for(i in search){
            kv = search[i].split('=');
            search[kv[0]] = kv[1];
            delete search[i];
        }
        return search;
    }
    return {};
}

//获取js对象长度
function getObjLen(data) {
    index = 0;
    for(i in data){
        index+=1;
    }
    return index;
}


/*数组去重复*/
function Unique(arr) {
    var result = [], hash = {};
    for (var i = 0, elem; (elem = arr[i]) != null; i++) {
        if (!hash[elem]) {
            result.push(elem);
            hash[elem] = true;
        }
    }
    return result;
}

//无数据
//function pubnodata(content){
//	var html = '';
//	html += '<div class="pubnodata_box"><div class="img"></div><div class="text">'+ content +'</div></div>';
//	$('body').append(html);
//}


//js 高精度加法
function accAdd(arg1, arg2) {
    var r1, r2, m, c;
    try {
        r1 = arg1.toString().split(".")[1].length;
    }
    catch (e) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split(".")[1].length;
    }
    catch (e) {
        r2 = 0;
    }
    c = Math.abs(r1 - r2);
    m = Math.pow(10, Math.max(r1, r2));
    if (c > 0) {
        var cm = Math.pow(10, c);
        if (r1 > r2) {
            arg1 = Number(arg1.toString().replace(".", ""));
            arg2 = Number(arg2.toString().replace(".", "")) * cm;
        } else {
            arg1 = Number(arg1.toString().replace(".", "")) * cm;
            arg2 = Number(arg2.toString().replace(".", ""));
        }
    } else {
        arg1 = Number(arg1.toString().replace(".", ""));
        arg2 = Number(arg2.toString().replace(".", ""));
    }
    return (arg1 + arg2) / m;
}

//给Number类型增加一个add方法，调用起来更加方便。
Number.prototype.add = function (arg) {
    return accAdd(arg, this);
};

/**
 ** 减法函数，用来得到精确的减法结果
 ** 说明：javascript的减法结果会有误差，在两个浮点数相减的时候会比较明显。这个函数返回较为精确的减法结果。
 ** 调用：accSub(arg1,arg2)
 ** 返回值：arg1加上arg2的精确结果
 **/
function accSub(arg1, arg2) {
    var r1, r2, m, n;
    try {
        r1 = arg1.toString().split(".")[1].length;
    }
    catch (e) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split(".")[1].length;
    }
    catch (e) {
        r2 = 0;
    }
    m = Math.pow(10, Math.max(r1, r2)); //last modify by deeka //动态控制精度长度
    n = (r1 >= r2) ? r1 : r2;
    return ((arg1 * m - arg2 * m) / m).toFixed(n);
}

// 给Number类型增加一个mul方法，调用起来更加方便。
Number.prototype.sub = function (arg) {
    return accSub(arg, this);
};


/**
 ** 乘法函数，用来得到精确的乘法结果
 ** 说明：javascript的乘法结果会有误差，在两个浮点数相乘的时候会比较明显。这个函数返回较为精确的乘法结果。
 ** 调用：accMul(arg1,arg2)
 ** 返回值：arg1乘以 arg2的精确结果
 **/
function accMul(arg1, arg2) {
    var m = 0, s1 = arg1.toString(), s2 = arg2.toString();
    try {
        m += s1.split(".")[1].length;
    }
    catch (e) {
    }
    try {
        m += s2.split(".")[1].length;
    }
    catch (e) {
    }
    return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) / Math.pow(10, m);
}

// 给Number类型增加一个mul方法，调用起来更加方便。
Number.prototype.mul = function (arg) {
    return accMul(arg, this);
};

/**
 ** 除法函数，用来得到精确的除法结果
 ** 说明：javascript的除法结果会有误差，在两个浮点数相除的时候会比较明显。这个函数返回较为精确的除法结果。
 ** 调用：accDiv(arg1,arg2)
 ** 返回值：arg1除以arg2的精确结果
 **/
function accDiv(arg1, arg2) {
    var t1 = 0, t2 = 0, r1, r2;
    try {
        t1 = arg1.toString().split(".")[1].length;
    }
    catch (e) {
    }
    try {
        t2 = arg2.toString().split(".")[1].length;
    }
    catch (e) {
    }
    with (Math) {
        r1 = Number(arg1.toString().replace(".", ""));
        r2 = Number(arg2.toString().replace(".", ""));
        return (r1 / r2) * pow(10, t2 - t1);
    }
}

//给Number类型增加一个div方法，调用起来更加方便。
Number.prototype.div = function (arg) {
    return accDiv(this, arg);
};

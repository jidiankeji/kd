<?php
namespace app\app\controller;
use think\Db;
use think\Cache;

use app\common\model\Setting;

class Exp extends Base{



	protected function _initialize(){
        parent::_initialize();
		$this->config  = Setting::config();
		$this->host = $this->config['site']['host'];
		$this->curl = new \Curl();
    }
	
	
	//PHP获取http请求的头信息
	public function getallheaders(){ 
       foreach($_SERVER as $name =>$value){ 
           if(substr($name,0,5) == 'HTTP_'){ 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } 
       } 
       return $headers; 
    } 
	
	
	//更新订单状态
	public function checkOrderUpdate($r,$v){
		
		
		//$performance['result'];die;
		
		$i = $i1 = $i2 = $i3 = 0;
		$preOrderFee = $r['freight']*100;//实际扣款
		$type = $r['type'];//快递公司状态
		$realWeight = $r['realWeight'];//站点重量
		
		
		
		if($type){
			if($preOrderFee > $v['sumMoneyYuan_old'] && $v['diffStatus'] == 0){
				
				$v['diffMoneyYuan'] = $preOrderFee-$v['sumMoneyYuan_old'];//实际扣费之间的差价
				$logoUrl = model('ExpressOrder')->logoUrl($v['kuaidi']);
				$jia = ($v['diffMoneyYuan']*$logoUrl['firstPrice'])/100;
				$v['diffMoneyYuan'] = $v['diffMoneyYuan']+$jia;
				$v['diffMoneyYuan'] = (int)$v['diffMoneyYuan'];
				
				if($v['diffMoneyYuan']){
					$i++; 
					//更新订单
					Db::name('express_order')->where(array('id'=>$v['id']))->update(array('diffStatus'=>1,'diffMoneyYuan'=>$v['diffMoneyYuan'])); 	
					//判断补差价
					model('Sms')->sendSmsTmplSend($v,$v['user_id'],$title = '补差价通知');
					model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '补差价通知');
					
				}
			}
		}
		
		
		//取消订单
		if($type=='下单取消' && $v['orderStatus'] != '-1'  && $v['orderStatus'] != '5' && $v['orderRightsStatus'] == 0){
			$i1++; 
			//已取消待后台退款
			Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>-1,'orderRightsStatus'=>1)); 	
		}
		
		//p($v['orderStatus']);
		
		//p($orderStatus);die;
		//p($v['orderStatus']);die;
		
		
		$u =0;
		if($v['orderStatus'] == 1 && $type=='揽收任务分配'){
			$u =1;
			$orderStatus =2;
		}elseif($v['orderStatus'] == 2 && $type=='揽收任务分配'){
			$u =1;
			$orderStatus =2;
		}elseif($v['orderStatus'] == 1 && $type=='已接单'){
			$u =1;
			$orderStatus =2;
		}elseif($v['orderStatus'] == 2 && $type=='已接单'){
			$u =1;
			$orderStatus =2;
		}elseif($v['orderStatus'] == 1 && $type=='已揽件'){
			$u =1;
			$orderStatus =3;
		}elseif($v['orderStatus'] == 1 && $type=='已正常收件状态'){
			$u =1;
			$orderStatus =3;
		}elseif($v['orderStatus'] == 2 && $type=='已正常收件状态'){
			$u =1;
			$orderStatus =3;
		}elseif($v['orderStatus'] == 1 && $type=='揽收成功'){
			$u =1;
			$orderStatus =3;
		}elseif($v['orderStatus'] == 2 && $type=='揽收成功'){
			$u =1;
			$orderStatus =3;
		}elseif(!$v['realOrderState']){
			$u =1;
			$orderStatus =2;
		}elseif(!$v['deliveryId']){
			$u =1;
			$orderStatus =2;
		}
	
		if($u==1){
			$i2++;
			$up['orderStatus'] = $orderStatus;
			$up['realOrderState'] = $r['comments'];
			$up['totalNumber'] = $r['packageCount'];
			$up['totalVolume'] = $r['parseWeight'];
			$up['review_weight'] =$r['realWeight'] ? $r['realWeight'] : $r['calWeight'];
			$up['review_vloumn'] = $r['volume'];
			$up['deliveryId'] = $r['waybill'];
	
			//p($r);die;
			//已接单
			Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
			//更新状态
			model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '接单成功提醒',$type);
		}
		
		
		
		
		//p($v['orderStatus']);die;
		
		if($type=='客户签收' && $v['orderStatus'] == 2){
			$i3++; 
			//订单完成
			Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>4)); 	
			//订单完成分销
			model('ExpressOrder')->profit($v,$v['user_id'],'分销');
			model('ExpressOrder')->orderAddIntegral($v,$v['user_id'],'给用户奖励积分');
			//赠送优惠券
			model('ExpressOrder')->giveCoupon($v,$v['user_id'],'赠送优惠券');
			//完成订单发送通知
			model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '签收成功通知');
		}
		$msg = '订单ID【'.$v['id'].'】更新成功其中补差价【'.$i.'】，取消订单【'.$i1.'】，已接单【'.$i2.'】，订单完成【'.$i3.'】<br>';
		return $msg;
	}


	//检测订单checkOrder
 	public function checkOrder(){
		$i = 0;
		$msg = '正在执行...';
		
		$id = (int)input('id','','trim,htmlspecialchars');		
		if($id){
			$list = Db::name('express_order')->where(array('orderStatus'=>array('in',array(1,2,3)),'type'=>2,'id'=>$id))->limit(0,100)->order('id desc')->select(); 
		}else{
			$list = Db::name('express_order')->where(array('orderStatus'=>array('in',array(1,2,3)),'type'=>2))->limit(0,100)->order('id desc')->select(); 
		}
		foreach($list as $k=>$v){
			$requestParams = array(
					'waybill'=>$v['deliveryId'],
					'shopbill'=>$v['expressNo'],
				);
				//p($requestParams);
				//查询订单详情
				$performance = model('Setting')->performance($requestParams,$Method ='QUERY_BILL_INFO');
				//p($performance);die;
				if($performance['code'] == 1){
					$i++;
					$msg .= $this->checkOrderUpdate($performance['result'],$v);
				}
		}
		echo '执行完毕：'.$msg; 
	}
	
	
	
	public function banner(){
		$data = model('Ad')->get_ad_list(array(),115);
		foreach($data as $k=>$v){
			$data[$k]['banner_url'] = config_weixin_img($v['photo']);
			$data[$k]['jump_url'] = $v['link_url'];
			$data[$k]['id'] = $v['ad_id'];
			$data[$k]['click'] = '1';
		}
		return json(array('code'=>1,'data'=>$data));
	}
	
	

	//首页
	public function configindex(){
		$data['hidden_other']= false;
		$data['host']= $this->config['site']['host'];
		$data['index_title']= $this->config['site']['sitename'];
		$data['kfmobile']= $this->config['site']['tel'];
		$data['kfphone']= $this->config['site']['tel'];
		$data['logo']=  config_weixin_img($this->config['site']['logo']);
		$data['wxcode']=  config_weixin_img($this->config['site']['wxcode']);
		$data['uu_appid']= "";
		$data['uu_h5_url']= "";
		$data['uu_mini_url']="";
		
		$data['tmpl'][0] = Db::name('weixin_tmpl')->where(array('title'=>'收益到账通知'))->value('template_id');
		$data['tmpl'][1] = Db::name('weixin_tmpl')->where(array('title'=>'优惠券发放通知'))->value('template_id');
		$data['tmpl'][2] = Db::name('weixin_tmpl')->where(array('title'=>'接单成功提醒'))->value('template_id');
		
		
		return json(array('code'=>1,'msg'=>"查询成功",'data'=>$data));
	}
	
	
	//获取数据[通知]
	public function settravel(){
		$detail = input('detail','','trim,htmlspecialchars');
		if($detail){
			$data = Db::name('article')->where(array('title'=>$detail))->find();
			$data['content'] = $data['details'];	
		}
		return json(array('code'=>1,'msg'=>"查询成功",'data'=>$data));
	}
	
	//xieyi
	public function xieyi(){
		$title = input('title','','trim,htmlspecialchars');
		if($title){
			$data = Db::name('article')->where(array('title'=>$title))->find();
			$data['content'] = $data['details'];	
		}
		return json(array('code'=>1,'msg'=>"查询成功",'data'=>$data));
	}
	
	
	
	//检测什么的【退费补差数量】
	public function myhandleCount(){
		$uid = $this->getUserId();
		$data =(int)Db::name('express_order')->where(array('user_id'=>$uid,'diffStatus'=>1))->count(); 	
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function bindMobile(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//获取签到信息
	public function getUserCheckin(){
		$uid = $this->getUserId();
		$num = input('num','','trim,htmlspecialchars');//第几天签到
		$integral = 0;
		if($num == 4){
			$integral = (int)$this->config['integral']['sign_4'];
		}elseif($num == 7){
			$integral = (int)$this->config['integral']['sign_7'];
		}else{
			$integral = (int)$this->config['integral']['sign_0'];
		}
		if(!empty($num)){
			$Data['user_id'] = $uid;
			$Data['num'] = $num;
			$Data['day'] = date('Y-m-d h:s:i',time());
			$Data['integral'] = $integral;
			$Data['create_time'] = time();
			$id = Db::name('user_sign_list')->insertGetId($Data);
			if($id && $integral){
				model('Users')->addIntegral($uid,$integral,$Data['day'].'签到奖励积分',1);
			}
		}
		
		$count = (int)Db::name('user_sign_list')->where(array('user_id'=>$uid))->count();
		if($count >=7){
			$count =0;
		}
		$pointsList = Db::name('user_sign_list')->where(array('user_id'=>$uid))->order('id desc')->limit(0,7)->select();
		foreach($pointsList as $k=>$v){
			$pointsList[$k]['day'] = $v['num'];
		}
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$count,'pointsList'=>$pointsList));
	}
	
    //获取签到信息
	public function useCheckin(){
		$uid = $this->getUserId();
		$num = input('num','','trim,htmlspecialchars');//第几天签到
		$integral = 0;
		if($num == 4){
			$integral = (int)$this->config['integral']['sign_4'];
		}elseif($num == 7){
			$integral = (int)$this->config['integral']['sign_7'];
		}else{
			$integral = (int)$this->config['integral']['sign_0'];
		}
		$bg_time = strtotime(TODAY);
		$user_sign_list = Db::name('user_sign_list')->where(array('create_time' => array(array('ELT', time()), array('EGT', $bg_time)),'user_id'=>$uid))->order('id desc')->find();
		if($user_sign_list){
			return json(array('code'=>0,'msg'=>"今日已签到"));
		}
		if(!empty($num)){
			$Data['user_id'] = $uid;
			$Data['num'] = $num;
			$Data['day'] = date('Y-m-d h:s:i',time());
			$Data['integral'] = $integral;
			$Data['create_time'] = time();
			$id = Db::name('user_sign_list')->insertGetId($Data);
			if($id && $integral){
				model('Users')->addIntegral($uid,$integral,$Data['day'].'签到奖励积分',1);
			}
		}
		$count = (int)Db::name('user_sign_list')->where(array('user_id'=>$uid))->count();
		if($count >=7){
			$count =0;
		}
		$pointsList = Db::name('user_sign_list')->where(array('user_id'=>$uid))->order('id desc')->limit(0,7)->select();
		foreach($pointsList as $k=>$v){
			$pointsList[$k]['day'] = $v['num'];
		}
		//p($pointsList);die;
		if(count($pointsList) >= 7){
			foreach($pointsList as $k=>$v){
				Db::name('user_sign_list')->where(array('id'=>$v['id']))->delete();
			}
			$pointsList = array();
		}

		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$count,'pointsList'=>$pointsList));
	}
	
	
	//积分兑换优惠券订单
	public function cashCoupon(){
		$uid = $this->getUserId();
		$promotion_id = input('promotion_id','','trim,htmlspecialchars');//优惠券列表
		$coupon = Db::name('coupon')->where(array('coupon_id'=>$promotion_id))->find();
		if($coupon['expire_date'] < TODAY){
			return json(array('code'=>0,'msg'=>'优惠券已过期'));
		}
		$integral = $coupon['integral'];
		
		
		$bg_time = strtotime(TODAY);
		$str = '-30 day';
        $bg_time_yesterday = strtotime(date('Y-m-d', strtotime($str)));
		$coupon_download = Db::name('coupon_download')->where(array('create_time' => array(array('ELT',time()),array('EGT', $bg_time_yesterday)),'user_id'=>$uid))->order('download_id desc')->find();
		if($coupon_download){
			return json(array('code'=>0,'msg'=>"一个月之内您已下载过优惠券"));
		}
		
		if($rest){
			//正常积分支付支付订单回调
			$updateCouponOrder = model('Setting')->updateCouponOrder($promotion_id,$integral,$log_id=0,$uid,1);
			if($updateCouponOrder == false){
				return json(array('code'=>0,'msg'=>'积分兑换优惠券付款回调失败未知错误'.model('Setting')->getError()));
			}
			return json(array('code'=>1,'msg'=>"积分兑换优惠券下单成功",'data'=>$data));
		}else{
			return json(array('code'=>0,'msg'=>'积分兑换优惠券扣费失败'));
		}
		
	}
	
	
	//购买优惠券
	public function buyCoupon(){
		$uid = $this->getUserId();
		$promotion_id = input('promotion_id','','trim,htmlspecialchars');//优惠券列表
		$paytype = input('paytype','','trim,htmlspecialchars');//支付方式
		
		$coupon = Db::name('coupon')->where(array('coupon_id'=>$promotion_id))->find();
		if($coupon['expire_date'] < TODAY){
			return json(array('code'=>0,'msg'=>'优惠券已过期'));
		}
		
		$bg_time = strtotime(TODAY);
		$str = '-30 day';
        $bg_time_yesterday = strtotime(date('Y-m-d', strtotime($str)));
		$coupon_download = Db::name('coupon_download')->where(array('create_time' => array(array('ELT',time()),array('EGT', $bg_time_yesterday)),'user_id'=>$uid))->order('download_id desc')->find();
		if($coupon_download){
			//return json(array('code'=>0,'msg'=>"一个月之内您已下载过优惠券"));
		}
		
		
		$need_pay = $coupon['money'];
		
		
		if($paytype== 'balance'){
			$money = $vip;
			$type = 'coupon';
			$code = 'money';
			$info = '优惠券购买';
		}else{
			$money = $money*100;
			$type = 'coupon';
			$code = 'wxapp';
			$info = '优惠券购买';
		}
		
		
		$logs = array(
			'type' => $type, 
			'types' => '1', 
			'user_id' => $uid, 
			'order_id' => $promotion_id, 
			'code' => $code, 
			'info' => $info, 
			'need_pay' =>$need_pay, 
			'create_time' => time(), 
			'create_ip' => request()->ip(), 
			'is_paid' => 0
		);
		$logs['log_id'] = Db::name('payment_logs')->insertGetId($logs);
			
		if($paytype== 'balance'){
			$users = Db::name('users')->where(array('user_id'=>$uid))->find();	
			if($users['money'] < $need_pay){
				return json(array('code'=>0,'msg'=>'余额不足'));
			}
			
			if($rest){
				//正常余额支付订单回调
				$updateCouponOrder = model('Setting')->updateCouponOrder($promotion_id,$need_pay,$log_id=0,$uid,1);
				if($updateCouponOrder == false){
					return json(array('code'=>0,'msg'=>'购买优惠券付款回调失败未知错误'.model('Setting')->getError()));
				}
				return json(array('code'=>1,'msg'=>"购买优惠券余额支付下单成功",'data'=>$data));
			}else{
				return json(array('code'=>0,'msg'=>'购买优惠券扣费失败'));
			}
		}else{
			$connect = Db::name('connect')->where(array('uid'=>$uid))->find();	
			$WX_OPENID = $connect['open_id'] ? $connect['open_id'] : $connect['openid'];	
			$Payment = model('Payment')->getPayment('wxapp');
			$out_trade_no = $logs['log_id'].'-'.time();
			$weixinpay = new \Wxpay($this->config['wxapp']['appid'],$WX_OPENID,$Payment['mchid'],$Payment['appkey'],$out_trade_no,$info,$need_pay);//支付接口
			$return = $weixinpay->pay();
			if($return['package'] == 'prepay_id='){
				return json(array('code'=>0,'msg'=>'预支付失败:'.$return['rest']['return_msg']));
			}
			$data['timeStamp']= $return['timeStamp'];
			$data['nonceStr'] =$return['nonceStr'];
			$data['package'] =$return['package'];
			$data['signType'] = 'MD5';
			$data['paySign'] = $return['paySign'];
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
		}
	}
	
	
	
	
	public function wxappLogin2(){
		$open_id = input('open_id','','trim,htmlspecialchars');
		$unionid = input('unionid','','trim,htmlspecialchars');
		$iv = input('iv','','trim,htmlspecialchars');
		$edata = input('edata','','trim,htmlspecialchars');
		$session_key = input('session_key','','trim');
		$parent_id = input('invite_id','','trim,htmlspecialchars');
			
		$result['phoneNumber'] = '';
		include ROOT_PATH.'extend/jiemi/WXBizDataCrypt.php';
		$WXBizDataCrypt = new \WXBizDataCrypt($this->config['wxapp']['appid'],$session_key);
		$errCode = $WXBizDataCrypt->decryptData($edata,$iv,$data);
		$result = json_decode($data,true);  
		$mobile = $result['phoneNumber'];
		if(!$mobile){
			//不1强制授权
			//return json(array('code'=>0,'msg'=>"手机号获取失败，请重新搜索小程序访问操作"));
		}
		
		$count = (int)Db::name('users')->where(array('mobile'=>$mobile))->count();
		if($count>1 && $mobile){
			return json(array('code'=>0,'msg'=>"手机号授权失败【数据库中存在多个相同手机号-".$mobile."，请联系网找客服处理】"));
		}
		
		
		$addData['unionid'] = $unionid;
		$addData['session_key'] = $session_key;
		if(!$open_id){
			return json(array('code'=>0,'msg'=>"open_id获取失败，请稍后再试试"));
		}
		
		$addData['openid'] = $open_id;
		$addData['parent_id'] = $parent_id;
		$addData['mobile'] = $mobile;
		
		
		$addRegisterUser = $this->addRegisterUser($addData);
		
		$addRegisterUser['token'] = $addRegisterUser['token'];
		$addRegisterUser['avatar'] = config_weixin_img($addRegisterUser['face']);
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$addRegisterUser));
	}
	
	
	
	public function byteLogin2(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function aliLogin2(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function wxappLogin1(){
		$code = input('code','','trim,htmlspecialchars');
		$url="https://api.weixin.qq.com/sns/jscode2session?appid=".$this->config['wxapp']['appid']."&secret=".$this->config['wxapp']['appsecret']."&js_code=".$code."&grant_type=authorization_code";
		$data = $this->httpRequest($url);
		$data = json_decode($data,true); 
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function wxappLogin3(){
		$uid = $this->getUserId();
		$nickname = input('nickname','','trim,htmlspecialchars');
		$avatar = input('avatar','','trim,htmlspecialchars');
		$Data['nickname'] = $nickname;
		$Data['face'] = $avatar;
		$Data['uc_id'] = 1;
		$Data['last_time'] = time();
		if(!$uid){
			return json(array('code'=>0,'msg'=>"没获取到UID请删除小程序后重新搜索小程序名字访问"));
		}
		$r = Db::name('users')->where('user_id',$uid)->update($Data);
		if($r){
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
		}else{
			return json(array('code'=>0,'msg'=>"更新失败【删除小程序》重新搜索小程序》再次登录】"));
		}
	}
	
	public function byteLogin1(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function aliLogin1(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function aliLogin3(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//提交问题反馈
	public function addopinion(){
		$content = input('content','','trim,htmlspecialchars');
		$contact = input('contact','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$images = input('images','','trim');
		//整合图片
		$images = @substr($images,1);
		$images = @substr($images,0,-1);
		$images = @explode(",",$images);
		$img = array();
		foreach($images as $k=>$v){
			$s = @substr($v,1);
			$s = @substr($s,0,-1);
			$img[$k] = $s;
		}
		$i = @implode(",",$img);
		$Data['user_id'] = $uid;
		$Data['content'] = $content;
		$Data['contact'] = $contact;
		$Data['images'] = $i;
		$Data['create_time'] = time();
		
		Db::name('express_msg')->insertGetId($Data);
		
		return json(array('code'=>1,'msg'=>"提交反馈成功",'data'=>$data));
	}
	
	public function getUserId(){
		$token = input('token','','trim,htmlspecialchars');
		$user_id = Db::name('users')->where(array('token'=>$token))->value('user_id');
		return (int)$user_id;
	}
	
	
	public function getUserInfo(){
		$user_id = $this->getUserId();
		$data = $this->getUserData($user_id);
		if(!$data){
			return json(array('code'=>0,'msg'=>'会员信息不存在'));
		}
		
		
		if($data['subscribe_status'] == 0){
			$subscribeUser = model('Weixin')->subscribeUser($uid);//0弹出 1不弹出
			if($subscribeUser == 1){
				//关注
				$update = Db::name('users')->where(array('user_id'=>$user_id))->update(array('subscribe_status'=>1));
				$data['subscribe_status'] = 1;
			}
		}
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function getall(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	//智能识别地址
	public function aiarea(){
		$content= input('content','','trim,htmlspecialchars');	
		$addr_type = (int)$this->config['wxapp']['addr_type'];
		if($addr_type == 0){
			$parArr = array('key' => '62897c0fd99c7c91cdf7bb98290170df','text' => $content);	
			$data = $this->curl->post('http://api.tianapi.com/addressparse/index',$parArr);
			$data = json_decode($data,true);//将json解析成数组
			if($data['code'] == 200){ //判断状态码
				$data['phone'] =  $data['newslist'][0]['mobile'];
				$data['area'] =  $data['newslist'][0]['district'];
				$data['addr'] =  $data['newslist'][0]['detail'];
				$data['name'] =  $data['newslist'][0]['name'];
				if($data['newslist'][0]['province'] =='天津'){
					$data['province'] =  '天津市';	
				}elseif($data['newslist'][0]['province'] =='重庆'){
					$data['province'] =  '重庆市';	
				}elseif($data['newslist'][0]['province'] =='北京'){
					$data['province'] =  '北京市';	
				}elseif($data['newslist'][0]['province'] =='上海'){
					$data['province'] =  '上海市';	
				}else{
					$data['province'] =  $data['newslist'][0]['province'];
				}
				$data['city'] =  $data['newslist'][0]['city'];
				$data['type'] =  1;
				return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
			}else{	
				return json(array('code'=>0,'msg'=>'错误提示：'.$data['msg']));
			}
		}else{
			$host = "https://jiexi8.market.alicloudapi.com";
			$path = "/address/analysis";
			$method = "GET";
			$appcode = trim($this->config['wxapp']['addr_app_code']);
			$headers = array();
			array_push($headers, "Authorization:APPCODE ".$appcode);
			$querys = "text=".urlencode($content)."";
			$bodys = "";
			$url = $host . $path . "?" . $querys;
			
			
			$curl = curl_init(); 
			curl_setopt($curl, CURLOPT_URL, $url);            
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			
			if(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);//使用自动跳转
			}
			curl_setopt($curl, CURLOPT_AUTOREFERER, 1); 
			curl_setopt($curl, CURLOPT_HTTPGET, 1); 
			curl_setopt($curl, CURLOPT_TIMEOUT, 30); 
			curl_setopt($curl, CURLOPT_HEADER, 0); 
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);    
			$tmpInfo = curl_exec($curl); // 执行操作      
			if (curl_errno($curl)) {
				echo 'Errno' . curl_error($curl);
			}
			curl_close($curl); // 关闭CURL会话      
			$tmpInfo = json_decode($tmpInfo,true);//将json解析成数组
			if($tmpInfo['showapi_res_erro'] != 0){
				return json(array('code'=>0,'msg'=>'错误提示：'.$tmpInfo['showapi_res_code']));
			}
			$showapi_res_body = $tmpInfo['showapi_res_body'];
			
			$data['phone'] = $showapi_res_body['phonenum'];
			$data['area'] =  $showapi_res_body['county'];
			$data['addr'] = $showapi_res_body['detail'];
			$data['name'] =  $showapi_res_body['person'];
			if($showapi_res_body['province'] =='天津'){
				$data['province'] =  '天津市';	
			}elseif($showapi_res_body['province'] =='重庆'){
				$data['province'] =  '重庆市';	
			}elseif($showapi_res_body['province'] =='北京'){
				$data['province'] =  '北京市';	
			}elseif($showapi_res_body['province'] =='上海'){
				$data['province'] =  '上海市';	
			}else{
				$data['province'] = $showapi_res_body['province'];
			}
			$data['city'] =  $showapi_res_body['city'];
			$data['type'] =  1;
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
		}
	}
	
	
	public function add(){
		$name= input('name','','trim,htmlspecialchars');
		$phone= input('phone','','trim,htmlspecialchars');
		$mobile= input('mobile','','trim,htmlspecialchars');
		$province= input('province','','trim,htmlspecialchars');
		$city= input('city','','trim,htmlspecialchars');
		$area= input('area','','trim,htmlspecialchars');
		$address= input('address','','trim,htmlspecialchars');
		$is_default= input('is_default','','trim,htmlspecialchars');	
		$uid = $this->getUserId();
		$mode= input('mode','','trim,htmlspecialchars');	
		
		$updateData['type'] = $mode;
		$updateData['name'] = $name;
		$updateData['linkMan'] = $name;
		$updateData['address'] = $address;
		$updateData['city'] =$city;		
		$updateData['province']  = $province;		
		$updateData['area']  = $area;	
		$updateData['phone']  = $phone;	
		$updateData['mobile']  = $mobile;		
		$updateData['user_id'] = $uid;
		$updateData['is_default'] = $is_default;
		$updateData['createTime'] = time();
		

		
		$addr_id = Db::name('user_addr')->insertGetId($updateData);
		
		$updateData['id'] = $addr_id;
		$updateData['sender_province'] = $province;
		$updateData['sender_city'] = $city;
		$updateData['sender_area'] = $area;
		$updateData['sender_address'] = $address;
		$updateData['sender_mobile'] = $mobile;
		$updateData['sender_name'] = $name;
		$updateData['sender_phone'] = $phone;
		
		$data['type'] = $mode;
		$data['info'] = $updateData;

		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data,'info'=>$updateData));
	}
	
	//编辑地址
	public function edit(){
		$addr_id= input('addr_id','','trim,htmlspecialchars');
		$name= input('name','','trim,htmlspecialchars');
		$phone= input('phone','','trim,htmlspecialchars');
		$province= input('province','','trim,htmlspecialchars');
		$city= input('city','','trim,htmlspecialchars');
		$area= input('area','','trim,htmlspecialchars');
		$address= input('address','','trim,htmlspecialchars');
		$is_default= (int)input('is_default','','trim,htmlspecialchars');	
		$uid = $this->getUserId();
		$mobile= input('mobile','','trim,htmlspecialchars');
		
		$addr = Db::name('user_addr')->where(array('addr_id'=>$addr_id))->find();
		
		
		$updateData['addr_id'] = $addr_id;
		$updateData['name'] = $name;
		$updateData['linkMan'] = $name;
		$updateData['address'] = $address;
		$updateData['city'] =$city;		
		$updateData['province']  = $province;		
		$updateData['area']  = $area;	
		$updateData['phone']  = $phone;	
		$updateData['mobile']  = $mobile;		
		$updateData['user_id'] = $uid;
		$updateData['is_default'] = $is_default;
		$updateData['createTime'] = time();
		
	
		
		
		$r = Db::name('user_addr')->update($updateData);
		
		$updateData['id'] = $addr_id;
		$updateData['sender_province'] = $province;
		$updateData['sender_city'] = $city;
		$updateData['sender_area'] = $area;
		$updateData['sender_address'] = $address;
		$updateData['sender_mobile'] = $mobile;
		$updateData['sender_name'] = $name;
		
		$data['mode'] = $addr['type'];
		$data['info'] = $updateData;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function del(){
		$addr_id= input('addr_id','','trim,htmlspecialchars');
		$user_id= $uid = $this->getUserId();
		if(!$user_id){
			return json(array('code'=>'0','msg'=>'TOKEN不存在'));
		}
		if(!$addr_id){
			return json(array('code'=>'0','msg'=>'ID不存在'));
		}
		$rest = Db::name('user_addr')->where(array('user_id'=>$user_id,'addr_id'=>$addr_id))->find();
		if($rest){
			$r = Db::name('user_addr')->where(array('user_id'=>$user_id,'addr_id'=>$addr_id))->delete();
			if($r){
				return json(array('code'=>1,'msg'=>"删除成功",'data'=>$data));
			}else{
				return json(array('code'=>'0','msg'=>'删除失败'));
			}
		}else{
			return json(array('code'=>'0','msg'=>'地址不存在'));
		}
	}
	
	public function getUserAreaList(){
		$uid = $this->getUserId();
		$addkey = input('addkey','','trim,htmlspecialchars');
		$type = input('type','','trim,htmlspecialchars');
		$page = input('page','','trim,htmlspecialchars');
		
		
		$map['closed'] =0;
		$map['user_id'] =$uid;
		if($addkey && $addkey != '' ){
            $map['address'] = array('LIKE', '%'.$addkey.'%');
        }
		if($type){
            $map['type'] = $type;
        }
		
		$count = Db::name('user_addr')->where($map)->count();
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('user_addr')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('addr_id desc')->select(); 	
			foreach($list as $k=>$v){
				$list[$k]['id'] = $v['addr_id'];
				$list[$k]['sender_name'] = $v['name'];
				$list[$k]['sender_phone'] = $v['phone'];
				$list[$k]['sender_mobile'] = $v['mobile'];
				$list[$k]['sender_address'] = $v['province'].$v['city'].$v['area'].$v['address'];
				$list[$k]['sender_province'] = $v['province'];
				$list[$k]['sender_area'] = $v['area'];
			}
		}
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//得物地址
	public function getfixed(){
		$type = (int)input('type','','trim,htmlspecialchars');
		$list = Db::name('user_addr_dewu')->where(array('type'=>$type))->select();
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function getUserArea(){
		$id = input('id','','trim,htmlspecialchars');
		$v = Db::name('user_addr')->where(array('addr_id'=>$id))->find();
		
		$info['sender_name'] = $v['name'];
		$info['sender_phone'] = $v['phone'];
		$info['sender_mobile'] = $v['mobile'];
		$info['addr'] = $v['address'];
		$info['sender_province'] = $v['province'];
		$info['sender_city'] = $v['city'];
		$info['sender_area'] = $v['area'];
		$data['info']= $info;			
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//提交货运信息
	public function queryAdd(){
		$data['sender_province'] = input('sender_province','','trim,htmlspecialchars');
		$data['sender_city'] = input('sender_city','','trim,htmlspecialchars');
		$data['sender_area'] = input('sender_area','','trim,htmlspecialchars');
		$data['recipients_province'] = input('recipients_province','','trim,htmlspecialchars');
		$data['recipients_city'] = input('recipients_city','','trim,htmlspecialchars');
		$data['recipients_area'] = input('recipients_area','','trim,htmlspecialchars');
		$data['mobile'] = input('mobile','','trim,htmlspecialchars');
		if(!$data['mobile']){
			return json(array('code'=>0,'msg'=>'手机号不能为空'));
		}
		$data['long'] = input('long','','trim,htmlspecialchars');
		$data['width'] = input('width','','trim,htmlspecialchars');
		$data['height'] = input('height','','trim,htmlspecialchars');
		$data['user_id'] = $this->getUserId();
		$data['create_time'] = time();
		
		
		
		$id = Db::name('express_transport')->insertGetId($data);//新建表
		if(!$id){
			return json(array('code'=>0,'msg'=>'提交货运信息时报'));
		}else{
			model('Sms')->sendSmsExpressTransport($data);//发短信
			return json(array('code'=>1,'msg'=>"提交成功",'data'=>$choosecom));
		}
	}
	
	
	
	//选择快递公司
	public function choosecom(){
		$data['sender_province'] = input('sender_province','','trim,htmlspecialchars');
		$data['sender_city'] = input('sender_city','','trim,htmlspecialchars');
		$data['sender_area'] = input('sender_area','','trim,htmlspecialchars');
		$data['sender_address'] = input('sender_address','','trim,htmlspecialchars');
		$data['sender_name'] = input('sender_name','','trim,htmlspecialchars');
		$data['sender_mobile'] = input('sender_mobile','','trim,htmlspecialchars');
		$data['sender_phone'] = input('sender_phone','','trim,htmlspecialchars');
		
		$data['recipients_province'] = input('recipients_province','','trim,htmlspecialchars');
		$data['recipients_city'] = input('recipients_city','','trim,htmlspecialchars');
		$data['recipients_area'] = input('recipients_area','','trim,htmlspecialchars');
		$data['recipients_address'] = input('recipients_address','','trim,htmlspecialchars');
		$data['recipients_name'] = input('recipients_name','','trim,htmlspecialchars');
		$data['recipients_mobile'] = input('recipients_mobile','','trim,htmlspecialchars');
		$data['recipients_phone'] = input('recipients_phone','','trim,htmlspecialchars');
		
		$data['totalWeight'] = input('totalWeight','','trim,htmlspecialchars');
		$data['long'] = input('long','','trim,htmlspecialchars');
		$data['width'] = input('width','','trim,htmlspecialchars');
		$data['height'] = input('height','','trim,htmlspecialchars');
		$data['type'] = (int)input('type','','trim,htmlspecialchars');
		$data['cate_id'] = (int)input('cate_id','','trim,htmlspecialchars');
		$data['uid'] = $this->getUserId();
		
		$choosecom = model('Setting')->choosecom($data);//查询快递
		//p($choosecom);
		if($choosecom == false){
			return json(array('code'=>0,'msg'=>'预下单错误:【'.model('Setting')->getError().'】'));
		}else{
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$choosecom));
		}
	}
	
	
	public function getOrderInfo($v,$t=0){
		$v['create_time'] = date("Y-m-d H:i:s",$v['create_time']);
		$logoUrl = model('ExpressOrder')->logoUrl($v['kuaidi']);
		$v['is_repay'] = '';//回复
		$v['id'] = $v['id'];
		$v['order_id'] = $v['id'];
		$v['mailNo'] = $v['deliveryId']?$v['deliveryId']:$v['id'];
		$v['expressNo'] = $v['deliveryId']?$v['deliveryId']:$v['id'];
		$v['yuyuetime'] = $v['yuyuetime']&&$v['yuyuetime']!=''&&$v['yuyuetime']!=' '?$v['yuyuetime']:false;
		$v['express_name'] = $logoUrl['expressName'];
		$v['sender_province'] = $v['sendCity'];
		$v['sender_name'] = $v['sendName'];
		$v['sender_phone'] = $v['sendMobile'];
		$v['sender_address'] = $v['sendAddress'];
		$v['logisticID'] = $v['id'];//渠道单号
		$v['courier'] = $v['realOrderState'];//快递员
		
		$v['recipients_province'] = $v['receiveCity'];
		$v['recipients_name'] = $v['receiveName'];
		$v['recipients_phone'] = $v['receiveMobile'];
		$v['recipients_address'] = $v['receiveAddress'];
		
		$v['cargoName'] = $v['cargoName'] ? $v['cargoName'] : '日用品';//寄托物
		$v['totalWeight'] = $v['wight'];//下单重量
		$v['sender_money'] = round($v['sumMoneyYuan']/100,2);//运单运费
		$v['coupon_pmt'] = round($v['coupon_pmt']/100,2);//已优惠
		$v['over_money'] = round($v['diffMoneyYuan']/100,2);//需补运费
		$v['order_money'] = round($v['sumMoneyYuan']/100,2);//sumMoneyYuan合计支付
		
		$v['charged_weight'] = $v['review_weight'];
		$v['cost_type'] = 0;
		$v['pay_money'] = round($v['sumMoneyYuan']/100,2);
		$v['money'] = round($v['diffMoneyYuan']/100,2);
		
		$v['insurancePrice'] = round($v['insurancePrice']/100,2);
		$v['insuranceValue'] = round($v['insuranceValue']/100,2);
		$v['packageServicePrice'] = round($v['packageServicePrice']/100,2);
		
		
		$v['typename'] = $v['message'] ? $v['message'] : '暂无';//异常类型
		$v['ctime'] = date("Y-m-d H:i:s",$v['pay_time']);
		
		
		if($t==1 && $v['diffStatus'] == 1){
			 $statusName = '待补差价';
			 $v['is_nocommission'] = '1';
			 $button_arr[0]['name'] = '补差价';
		}elseif($v['orderStatus'] == 0){
			 $statusName = '未付款';
			 $button_arr[0]['name'] = '立即支付';
			 $button_arr[1]['name'] = '撤销运单';
		}elseif($v['orderStatus'] == 1){
			 $statusName = '已付款';
			 $v['is_nocommission'] = '1';
			 $button_arr[0]['name'] = '再来一单';
			 $button_arr[1]['name'] = '撤销运单';
		}elseif($v['orderStatus'] == 2){
			 $statusName = '已接单';
			 $v['is_nocommission'] = '1';
			 $button_arr[0]['name'] = '再来一单';
			 $button_arr[1]['name'] = '撤销运单';
		}elseif($v['orderStatus'] == 3){
			 $statusName = '已派送';
			 $button_arr[0]['name'] = '再来一单';
			 $v['is_nocommission'] = '1';
		}elseif($v['orderStatus'] == 4){
			 $statusName = '已完成';
			 $button_arr[0]['name'] = '再来一单';
		}elseif($v['orderStatus'] == 5){
			 $statusName = '已取消退款';
		}elseif($v['orderStatus'] == -1){
			 $statusName = '已取消';
		}elseif($v['orderStatus'] == 9){
			 $statusName = '订单异常';
			 $button_arr[0]['name'] = '撤销运单';
		}else{
			 $statusName = '未知状态';
		}
		
		
		$v['status'] = $statusName;
		$v['button_arr'] = $button_arr;
		$v['logisticsInfo'] = $this->logisticsInfo($v);
		$v['is_logistics'] = 0;
		return $v;
	}
	
	//订单列表
	public function lists(){
		$mailNo = input('mailNo','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$handle = input('handle','','trim,htmlspecialchars');//1未处理1已处理
		
		$map = array('user_id'=>$uid,'closed'=>0);
		if($mailNo){
			$handle = 6;
			$map['expressNo|sendAddress|receiveAddress|receiveMobile|sendMobile'] = array('LIKE','%'.$mailNo.'%');
		}
		if($handle == 2){
			$map['orderStatus'] = array('in',array(1,2));
		}
		if($handle == 3){
			$map['orderStatus'] = 3;
		}
		if($handle == 4){
			$map['orderStatus'] = 4;
		}
		if($handle == 5){
			$map['orderStatus'] = array('in',array(5,9,-1));
		}
		$count = Db::name('express_order')->where($map)->count();
		
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		
		//p($map);die;
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
			if($mailNo){
				$getLogisticsInfo = $this->getLogisticsInfo($mailNo);
				$list = $getLogisticsInfo;
				$data['logisticsInfo'] = $list;
			}else{
				 $list = array();
			}
        }else{
			$list = Db::name('express_order')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				$getOrderInfo = $this->getOrderInfo($v,0);
				$list[$k] = $getOrderInfo;
			}
		}
		$data['Jicount'] = $count;
		$data['Shcount'] = '0';
		$data['handle'] = $handle;
		$data['list'] = $list;
		$data['activeTabJiNum1'] = (int)Db::name('express_order')->where(array('user_id'=>$uid,'orderStatus'=>array('in',array(1,2)),'closed'=>0))->count();
		$data['activeTabJiNum2'] = (int)Db::name('express_order')->where(array('user_id'=>$uid,'orderStatus'=>3,'closed'=>0))->count();
		$data['activeTabJiNum3'] = (int)Db::name('express_order')->where(array('user_id'=>$uid,'orderStatus'=>4,'closed'=>0))->count();
		$data['activeTabJiNum4'] = (int)Db::name('express_order')->where(array('user_id'=>$uid,'orderStatus'=>array('in',array(5,9,-1)),'closed'=>0))->count();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data,'firstRow'=>$Page->firstRow,'listRows'=>$Page->listRows));
	}
	
	
	
	public function getLogisticsInfo($mailNo){
		
		
		$post_data = array();
		$post_data["customer"] = $this->config['config']['express_api_customer'];
		$key= $this->config['config']['express_api_key'];
		
		$post_data["param"] = '{"com":"'.trim('').'","num":"'.$mailNo.'"}';
		$url='https://poll.kuaidi100.com/poll/query.do';
		$post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
		$post_data["sign"] = strtoupper($post_data["sign"]);
		$o=""; 
		foreach($post_data as $k=>$v){
			$o.= "$k=".urlencode($v)."&";
		}
		$post_data=substr($o,0,-1);
		$this->curl = new \Curl();
		$result = $this->curl->post($url,$post_data);
		$result = json_decode($result,true);
		$result['logistics_info']= $result['com'];
		$result['mailNo']= $mailNo;
		$result['lanshou_time']= '';
		$result['express_status']= '';
		return $result;
	}
	
	
	
	
	
	public function logisticsInfo($info){
		if($info['type'] == 1){
			//易达物流信息
			$logoUrl = model('ExpressOrder')->logoUrl($info['kuaidi']);
			$requestParams = array(
				'deliveryId'=>$info['deliveryId'],
				'deliveryType'=>$logoUrl['deliveryType'],
			);
			$execute = model('Setting')->execute($requestParams,$Method='DELIVERY_TRACE');
			$logistics_info = array();
			if($execute['data']){
				foreach($execute['data'] as $k=>$v){
					$logistics_info[$k][] = $v['desc'].$v['time'];
				}
			}
			
		}elseif($info['type'] == 2){
			$requestParams = array(
				'waybill'=>$info['deliveryId'],
				'shopbill'=>$info['expressNo'],
			);
			$performance = model('Setting')->performance($requestParams,$Method ='QUERY_TRANCE');
			//p($performance);die;
			$logistics_info = $performance['result'];
		}
		return $logistics_info;
	}
	
	
	//订单详情
	public function detail(){
		$recipients_id = input('recipients_id','','trim,htmlspecialchars');
		$info = Db::name('express_order')->where(array('id'=>$recipients_id))->find();
		$info = $this->getOrderInfo($info,0);
		$data = $info;
		$data['logistics_info'] =$this->logisticsInfo($info);
		$data['logistics'] =$this->logisticsInfo($info);
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	//退费补差
	public function myhandle(){
		$mailNo = input('mailNo','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$handle = input('handle','','trim,htmlspecialchars');//我的寄件
		
		$map = array('user_id'=>$uid,'orderStatus'=>array('in',array(0,1,2,3,4,5,8)),'diffMoneyYuan'=>array('GT',0),'closed'=>0);
		if($mailNo){
			$map['expressNo|sendAddress|receiveAddress|receiveMobile|sendMobile'] = array('LIKE','%'.mailNo.'%');
		}
		if($handle == 1){
			$map['diffStatus'] = 1;
		}
		if($handle == 2){
			$map['diffStatus'] = 2;
		}
		$count = Db::name('express_order')->where($map)->count();
		$Page = new \Page3($count,$limit);
        $show = $Page->show();
		
		$list = Db::name('express_order')->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $k => $v){
			$getOrderInfo = $this->getOrderInfo($v,1);
			$list[$k] = $getOrderInfo;
		}
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function repay(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	//补差价
	public function tosettle(){
		$logisticID = input('logisticID','','trim,htmlspecialchars');
		$overid = input('overid','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$data['order_id'] = $overid;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	//撤销订单
	public function cancel(){
		$data = array();
		$recipients_id = input('recipients_id','','trim,htmlspecialchars');
		$reason = input('reason','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$id = (int) $recipients_id;
		if(!$id){
			return json(array('code'=>0,'msg'=>'id不存在'));
		} 
		if(!($sign = Db::name('express_order')->where(array('id'=>$id))->find())){
			return json(array('code'=>0,'msg'=>'订单不存在'));
		}
		
		if($sign['user_id'] != $uid){
			return json(array('code'=>0,'msg'=>'非法操作'));
		}	
		if($sign['orderStatus'] == 0){
			$up2 = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>-1,'reason'=>$reason));
			if($up2){
				//如果订单未支付让优惠券生效
				if($sign['coupon_download_id']){
					Db::name('coupon_download')->where(array('download_id'=>$sign['coupon_download_id']))->update(array('used_time'=>'','is_used'=>0));
				}
				return json(array('code'=>1,'msg'=>"取消成功",'data'=>array()));
			}
			return json(array('code'=>0,'msg'=>'取消失败'));
		}
		
		
	
			if($sign['deliveryId']){
				if($sign['type'] == 1){
					$logoUrl = model('ExpressOrder')->logoUrl($sign['kuaidi']);
					$requestParams['deliveryId'] = $sign['deliveryId'];
					$requestParams['deliveryType'] = $logoUrl['deliveryType'];
					//易达取消订单
					$execute = model('Setting')->execute($requestParams,$Method='CANCEL_ORDER');
					if($execute['code'] == 200){
						model("express_order")->startTrans();
						try{	
							$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express',$id.'-易达用户取消订单退款');
							if($orderWeixinRefund == false){
								$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],$id.'-【'.model('PaymentLogs')->getError().'】易达订单用户取消订单退款',1);
								if($rest){
									$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2,'reason'=>$reason));
								}else{
									return json(array('code'=>0,'msg'=>'易达接口取消订单余额退款失败'));
								}
							}else{
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2,'reason'=>$reason));
							}
							model('express_order')->commit();
							return json(array('code'=>1,'msg'=>"退款成功",'data'=>$data));
						}catch(\Exception $e){
							model('express_order')->rollback();
							return json(array('code'=>0,'msg'=>$e->getMessage()));
						}
					}else{
						return json(array('code'=>0,'msg'=>'易达接口取消订单失败，退款失败，可以尝试人工退款，接口返回【'.$execute['msg'].'】'));
					}
				}elseif($sign['type'] == 2){
					
					$requestParams = array(
						'waybill'=>$sign['deliveryId'],
						'shopbill'=>$sign['expressNo'],
					);
					//取消订单接口
					$performance = model('Setting')->performance($requestParams,$Method ='CANCEL');
					if($performance['code'] ==1){
						model("express_order")->startTrans();
						try{	
							$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express',$id.'-云洋订单用户取消订单退款');
							if($orderWeixinRefund == false){
								$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],$id.'-【'.model('PaymentLogs')->getError().'】云洋订单用户取消订单退款',1);
								if($rest){
									$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2,'reason'=>$reason));
								}else{
									return json(array('code'=>0,'msg'=>'云洋接口取消订单余额退款失败'));
								}
							}else{
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2,'reason'=>$reason));
							}
							model('express_order')->commit();
							return json(array('code'=>1,'msg'=>"退款成功",'data'=>$data));
						}catch(\Exception $e){
							model('express_order')->rollback();
							return json(array('code'=>0,'msg'=>$e->getMessage()));
						}
					}else{
						return json(array('code'=>0,'msg'=>'云洋接口取消订单失败'.$performance['message']));
					}
				}
			}else{
				return json(array('code'=>0,'msg'=>'deliveryId不存在'));
			}
		
	}
	
	
	public function copy(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//余额日志
	public function mybalance(){
		$getMoneyTypes = model('Users')->getMoneyTypes();
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		
		$map = array('user_id'=>$uid);
		$count = Db::name('user_money_logs')->where($map)->count();
		
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('user_money_logs')->where($map)->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				$list[$k]['memo'] = $getMoneyTypes[$v['type']];
				$list[$k]['id'] = $v['log_id'];
				$list[$k]['money'] = round($v['money']/100,2);
				$list[$k]['curr_balance'] = round($v['new_num']/100,2);
				$list[$k]['createtime'] =  date("Y-m-d H:i:s",$v['create_time']);
			}
		}
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//分佣信息
	public function mycommission(){
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$map = array('user_id'=>$uid);
		$count = Db::name('user_profit_logs')->where($map)->count();
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('user_profit_logs')->where($map)->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				$list[$k]['id'] = $v['log_id'];
				$list[$k]['money'] = round($v['money']/100,2);
				$list[$k]['memo'] =$v['info'];
				$list[$k]['ctime'] =  date("Y-m-d H:i:s",$v['create_time']);
			}
		}
		$bg_time = strtotime(TODAY);
		$str = '-30 day';
        $bg_time_yesterday = strtotime(date('Y-m-d', strtotime($str)));
		
		$data['day_invite'] = (int) Db::name('users')->where(array('reg_time'=>array(array('ELT',time()),array('EGT',$bg_time)),'parent_id'=>$uid))->count();
		$data['month_invite'] = (int) Db::name('users')->where(array('reg_time'=>array(array('ELT',$bg_time), array('EGT',$bg_time_yesterday)),'parent_id'=>$uid))->count();;
		$data['sum_invite'] =(int) Db::name('users')->where(array('parent_id'=>$uid))->count();
		
		$ishave = Db::name('user_profit_logs')->where(array('user_id'=>$uid))->sum('money');
		$data['ishave'] = round($ishave/100,2);
		$data['nohave'] = 0.00;
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function comdetail(){
		$log_id = (int)input('commission_id','','trim,htmlspecialchars');
		$data = Db::name('user_profit_logs')->where(array('log_id'=>$log_id))->find();
		$data['id'] =$data['log_id'];
		$data['money'] = round($data['money']/100,2);
		$data['memo'] =$data['info'];
		$data['ctime'] =  date("Y-m-d H:i:s",$data['create_time']);
		$data['create_time'] =  date("Y-m-d H:i:s",$data['create_time']);
		$data['logisticID'] =$data['order_id'];
		$u1 = Db::name('users')->where(array('user_id'=>$data['user_id']))->field('user_id,nickname')->find();
		$data['receipt_name'] = $u1['nickname'];
		$order = Db::name('express_order')->where(array('id'=>$data['order_id']))->find();
		$u2 = Db::name('users')->where(array('user_id'=>$order['user_id']))->field('user_id,nickname')->find();
		$data['contribute_name'] = $u2['nickname'];
		$getorderStatus = model('Setting')->getorderStatus();
		$data['status'] =$getorderStatus[$order['orderStatus']];
		$data['totalWeight'] =$order['wight'];
		$data['review_weight'] =$order['review_weight'];
		$data['charged_weight'] ='';
		$data['sender_money'] =round($order['sumMoneyYuan']/100,2);
		$data['is_expire'] ='已分佣';
		$data['remark'] =$data['info'];
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function mywithdraw(){
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		
		$map = array('user_id'=>$uid);
		$count = Db::name('users_cash')->where($map)->count();
		
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('users_cash')->where($map)->order('cash_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				if($v['status'] == 0){
					$apply_status = '审核中';
				}elseif($v['status'] == 1){
					$apply_status = '已通过';
				}elseif($v['status'] == 2){
					$apply_status = '已拒绝';
				}
				$list[$k]['apply_status'] = $apply_status;
				$list[$k]['id'] = $v['cash_id'];
				$list[$k]['money'] = round($v['money']/100,2);
				$list[$k]['is_pay'] =$v['info'];
				$list[$k]['create_time'] =  date("Y-m-d H:i:s",$v['addtime']);
			}
		}
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	
	public function recharge(){
		$uid = $this->getUserId();
		if(!$uid){
			return json(array('code'=>0,'msg'=>'uid不能为空'));
		}
		$money = (int)input('money','','trim,htmlspecialchars');
		if($money <= 0){
			return json(array('code'=>0,'msg'=>'价格有误'));
		}
		$scene = input('scene','','trim,htmlspecialchars');//类型scene=vip充值balance
		
		if($scene == 'vip'){
			$vip = Db::name('user_rank')->where(array('rank_id'=>1))->value('price');	
			$money = $vip;
			$type = 'vip';
			$info = 'VIP购买';
		}else{
			$money = $money*100;
			$type = 'money';
			$info = '余额充值';
		}
		$need_pay = $money;
		$logs = array(
			'type' => $type, 
			'types' => '1', 
			'user_id' => $uid, 
			'order_id' => 0, 
			'code' => 'wxapp', 
			'info' => $info, 
			'need_pay' =>$need_pay, 
			'create_time' => time(), 
			'create_ip' => request()->ip(), 
			'is_paid' => 0
		);
		$logs['log_id'] = Db::name('payment_logs')->insertGetId($logs);
			
			
		$connect = Db::name('connect')->where(array('uid'=>$uid))->find();	
		$WX_OPENID = $connect['openid'] ? $connect['openid'] : $connect['open_id'];	
			
		$Payment = model('Payment')->getPayment('wxapp');
		if(!$Payment){
			return json(array('code'=>0,'msg'=>'支付信息不存在'));
		}
		
		$out_trade_no = $logs['log_id'].'-'.time();
		if(!$WX_OPENID){
			return json(array('code'=>0,'msg'=>'WX_OPENID不能为空'));
		}
		
		file_put_contents(ROOT_PATH.'/application/app/controller/_$WX_OPENID.txt', var_export($return,true));
		
		$weixinpay = new \Wxpay($this->config['wxapp']['appid'],$WX_OPENID,$Payment['mchid'],$Payment['appkey'],$out_trade_no,$info,$need_pay);//支付接口
		$return = $weixinpay->pay();
	
		file_put_contents(ROOT_PATH.'/application/app/controller/_$return.txt', var_export($return,true));
	
		if($return['package'] == 'prepay_id='){
			return json(array('code'=>0,'msg'=>'预支付失败:'.$return['rest']['return_msg'].''.$return['rest']['err_code_des']));
		}
		
		$data['timeStamp']= $return['timeStamp'];
		$data['nonceStr'] =$return['nonceStr'];
		$data['package'] =$return['package'];
		$data['signType'] = 'MD5';
		$data['paySign'] = $return['paySign'];
	
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function ttrecharge(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//申请提现
	public function withdraw(){
		$uid = $this->getUserId();
		$money = input('money','','trim,htmlspecialchars');
		$info = input('info','','trim,htmlspecialchars');
		if($info == 'undefined'){
			return json(array('code'=>0,'msg'=>'提现说明不能为空'));
		}
		$money = $money*100;
		$u = Db::name('users')->where(array('user_id'=>$uid))->find();
		if($u['money'] < $money){
			return json(array('code'=>0,'msg'=>'余额不足不能提现'));
		}
		if($detail['is_lock'] == 1){
			return json(array('code'=>0,'msg'=>'您的账户已被锁，暂时无法提现'));
		}
		if($money <100){
			return json(array('code'=>0,'msg'=>'提现金额不能低于1元'));
		}
		
		
		
		
		$data['account'] = $u['nickname'];
        $data['user_id'] = $uid;
		$data['shop_id'] = 0;
        $data['money'] = $money - $commission;//实际到账
		$data['commission'] =$commission;//手续费
		$data['info'] = $info;
		$data['re_user_name'] = '未填写';
		$data['alipay_account'] ='未填写';
		$data['alipay_real_name'] = '未填写';
		$data['bank_num'] = '未填写';
		$data['bank_realname'] = '未填写';
        $data['type'] = 'user';
        $data['addtime'] = time();
		$data['code'] = 'weixin';
		//写入数据库
		if($cash_id = Db::name('users_cash')->insertGetId($data)){
			//扣除资金
			model('Users')->addMoney($uid,-$money,$data['info'],3);
			return json(array('code'=>1,'msg'=>"提现成功",'data'=>$data));
		}
		return json(array('code'=>0,'msg'=>'操作错误'));
	}
	
	public function baldetail(){
		$balanace_id = input('balanace_id','','trim,htmlspecialchars');
		
		$data= Db::name('user_money_logs')->where(array('log_id'=>$balanace_id))->find();
		$u = Db::name('users')->where(array('user_id'=>$data['user_id']))->find();
		
		$data['money'] = round($data['money']/100,2);
		$data['affiliated'] = $data['log_id'];
		$data['username'] = $u['nickname'];
		$data['after'] =  round($data['new_num']/100,2);
		$data['memo'] = $data['intro'];
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//海报
	public function poster(){
		$uid = $this->getUserId();
		$page="pages/index/index";//路径
		$width = '200';
		for($i=0; $i<3; $i++){
			$poster_url[$i]['key'] = $i+1;
			$poster_url[$i]['url'] = model('Api')->getWxappPoster($uid,$page,$width,$i+1);
		}
		$data['poster_url'] = $poster_url;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//邀请海报
	public function posterCre(){
		$uid = $this->getUserId();
		$page="pages/index/index";//路径
		$width = '200';
		$poster_key = input('poster_key','','trim,htmlspecialchars');
		$u = Db::name('users')->where(array('user_id'=>$uid))->find(); 
		if($poster_key == 1 && empty($u['qrcode1'])){
			$user_poster_url = model('Api')->getWxappPoster($uid,$page,$width,1);
		}elseif($poster_key == 1 && !empty($u['qrcode1'])){
			$user_poster_url = $u['qrcode1'];
		}
		if($poster_key == 2 && empty($u['qrcode2'])){
			$user_poster_url = model('Api')->getWxappPoster($uid,$page,$width,2);
		}elseif($poster_key == 2 && !empty($u['qrcode2'])){
			$user_poster_url = $u['qrcode2'];
		}
		if($poster_key == 3 && empty($u['qrcode3'])){
			$user_poster_url = model('Api')->getWxappPoster($uid,$page,$width,3);
		}elseif($poster_key ==3 && !empty($u['qrcode3'])){
			$user_poster_url = $u['qrcode3'];
		}
		$data['user_poster_url'] = $user_poster_url;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function couponList(){
		$list = Db::name('coupon')->where(array('type'=>4,'closed'=>0))->limit(0,30)->select(); 	
		foreach($list as $k=>$v){
			$list[$k]['id'] = $v['coupon_id'];
			$list[$k]['money'] = round($v['reduce_price']/100,2);
			$list[$k]['name'] = $v['title'];
			$list[$k]['remark'] = $v['intro'];
			$list[$k]['etime'] = $v['expire_date'];
			$list[$k]['stime'] = TODAY;
			$list[$k]['counts'] = 0;
			$list[$k]['buy_money'] = round($v['money']/100,2);
			$list[$k]['cash_points'] = $v['integral'];
			if($v['limit_num']){
				$list[$k]['conditions_that'] = '第'.$v['limit_num'].'单可用';
			}else{
				$list[$k]['conditions_that'] = '无门槛优惠券';
			}
			if($v['title'] == '新人有礼'){
				$is_left =1;
			}
			if($v['title'] == '寄件返礼'){
				$is_left =0;
			}
			if($v['title'] == '满额返礼'){
				$is_left =0;
			}
			$list[$k]['is_left'] = $is_left;
		}
		
		$list2 = Db::name('coupon')->where(array('type'=>5,'closed'=>0))->limit(0,30)->select(); 	
		foreach($list2 as $k=>$v){
			$list2[$k]['id'] = $v['coupon_id'];
			$list2[$k]['money'] = round($v['reduce_price']/100,2);
			$list2[$k]['name'] = $v['title'];
			$list2[$k]['remark'] = $v['intro'];
			$list2[$k]['etime'] = $v['expire_date'];
			$list2[$k]['stime'] = TODAY;
			$list2[$k]['counts'] = 0;
			$list2[$k]['buy_money'] = round($v['money']/100,2);
			$list2[$k]['cash_points'] = $v['integral'];
			if($v['limit_num']){
				$list2[$k]['conditions_that'] = '第'.$v['limit_num'].'单可用';
			}else{
				$list2[$k]['conditions_that'] = '无门槛优惠券';
			}
			if($v['title'] == '新人有礼'){
				$is_left =1;
			}
			if($v['title'] == '寄件返礼'){
				$is_left =0;
			}
			if($v['title'] == '满额返礼'){
				$is_left =0;
			}
			$list2[$k]['is_left'] = $is_left;
		}
		
		$data['list'][2] = $list;
		$data['list'][1] = $list2;
		
		$vip = Db::name('user_rank')->where(array('rank_id'=>1))->value('price');	
		$data['vip'] = round($vip/100,2);
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function myCoupon(){
		$type = input('type','','trim,htmlspecialchars');//类型
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$pay_money = input('pay_money','','trim,htmlspecialchars');
		$pay_money = (int)($pay_money*100);
		
		
		$map = array('user_id'=>$uid,'is_used'=>0);
		if($type == 2){
			$map = array('user_id'=>$uid,'is_used'=>1);
		}
		
		$count = Db::name('coupon_download')->where($map)->count();
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('coupon_download')->where($map)->order(array('download_id'=>'asc'))->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				$list[$k]['id'] = $v['download_id'];
				$list[$k]['promotion_id'] = $v['download_id'];
				$list[$k]['coupon_code'] = $v['download_id'];
				$coupon = Db::name('coupon')->where(array('coupon_id'=>$v['coupon_id']))->find();
				$list[$k]['money'] = round($coupon['reduce_price']/100,2);
				$list[$k]['name'] = $coupon['title'];
				$list[$k]['remark'] = $coupon['intro'];
				$list[$k]['endtime'] = $coupon['expire_date'];
				$list[$k]['limit_num'] = $coupon['limit_num'];
				$list[$k]['limit_num_info'] = '限制第【'.$coupon['limit_num'].'】单使用';
				
				
				$list[$k]['ruletext'] = $ruletext;
				
				$list[$k]['no_sati'] = 0;
				if($v['is_used'] == 0){
					$list[$k]['type'] = 1;
				}
				if($v['is_used'] == 1){
					$list[$k]['type'] = 2;
				}
				if($pay_money && $pay_money < $coupon['full_price'] && $pay_money > $coupon['reduce_price']){
					unset($list[$k]);
				}
			}
		}
		
		
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//积分规则
	public function pointsRule(){
		$data[0]['title'] = $this->config['integral']['title_1'];
		$data[0]['content'][0] = $this->config['integral']['info_1'];
		
		$data[1]['title'] = $this->config['integral']['title_2'];
		$data[1]['content'][0] = $this->config['integral']['info_2'];
		
		$data[2]['title'] = $this->config['integral']['title_3'];
		$data[2]['content'][0] = $this->config['integral']['info_3'];
		
		$data[3]['title'] = $this->config['integral']['title_4'];
		$data[3]['content'][0] = $this->config['integral']['info_4'];
		
		$data[4]['title'] = $this->config['integral']['title_5'];
		$data[4]['content'][0] = $this->config['integral']['info_5'];
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//积分任务
	public function pointsTask(){
		$uid = $this->getUserId();
		$data[0]['title'] = '寄快递';
		$data[0]['content'] = '寄快递消费1元得'.(int)$this->config['integral']['exp'].'积分';
		$data[0]['status'] = 1;//1未完成0已完成
		$data[0]['button_name'] = '未完成';
		$data[0]['url'] = '/pages/find/index/index';
		$data[0]['tabbar'] = 1;//1代表tabbar0不是
		
		
		$data[1]['title'] = '邀请新用户';
		$data[1]['content'] = '邀请一个新用户获得'.(int)$this->config['integral']['yao'].'积分';
		$data[1]['status'] = 1;
		$data[1]['button_name'] = '去邀请';
		$data[1]['url'] = '/pages/member/invite/invite';
		$data[1]['tabbar'] =0;
		
		$data[2]['title'] = '关注服务号';
		$data[2]['content'] = '关注服务号获得'.(int)$this->config['integral']['follow'].'积分';
		$data[2]['status'] = 0;
		$data[2]['button_name'] = '去关注';
		$data[2]['id'] = 3;
		$data[2]['tabbar'] =0;
		
		$data[3]['title'] = '签到';
		$data[3]['content'] = '签到一次'.(int)$this->config['integral']['sign_0'].'积分，累计签到有惊喜';
		$data[3]['status'] = 0;
		$data[3]['button_name'] = '去签到';
		$data[3]['url'] = '/pages/gift/index/index';
		$data[3]['tabbar'] =1;
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	//积分明细列表
	public function pointsListApi(){
		$mailNo = input('mailNo','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$map = array('user_id'=>$uid);
		$count = Db::name('user_integral_logs')->where($map)->count();
		if($page == 1){
			 $firstRow = 0;
			 $listRows = $limit;
		}else{
			 $firstRow = $page*$limit;
			 $listRows = $limit;
		}
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('user_integral_logs')->where($map)->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($list as $k => $v){
				$list[$k]['points'] = $v['integral'];
				$list[$k]['remark'] = $v['intro'];
				$list[$k]['ctime'] = date('Y-m-d H:i:s',$v['create_time']);
			}
		}
		
		
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function getCoupon(){
		$data = array();
		$uid = $this->getUserId();
		$id = (int)input('id','','trim,htmlspecialchars');
		$coupon = Db::name('coupon')->where(array('coupon_id'=>$id,'type'=>3,'audit'=>1,'closed' => 0,'expire_date' => array('EGT', TODAY),'num'=>array('gt',0)))->find();	
		if(!$coupon){
			return json(array('code'=>0,'msg'=>"优惠券不存在"));
		}
		$download = (int)Db::name('coupon_download')->where(array('user_id'=>$uid,'is_used'=>0,'coupon_id'=>$coupon['coupon_id']))->count();	
		if($download==1){
			return json(array('code'=>0,'msg'=>"不能重复领取"));	
		}
		$sendCouponDownload = model('ExpressOrder')->sendCouponDownload($uid,$coupon['title']);//送优惠券寄件返礼
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getCount(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
    public function checkIndexHongbao(){
		$uid = $this->getUserId();
		$data = Db::name('coupon')->where(array('type'=>3,'audit'=>1,'closed' => 0, 'expire_date' => array('EGT', TODAY),'num'=>array('gt',0)))->limit(0,1)->find();	
		$data['reduce_price'] = round($data['reduce_price']/100,2);
		$data['title'] = cut_msubstr($data['title'],0,4,false);
		$download = (int)Db::name('coupon_download')->where(array('user_id'=>$uid,'is_used'=>0,'coupon_id'=>$data['coupon_id']))->count();	
		if($data['coupon_id'] && $download==0){
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));	
		}
	}
	
	
	public function recommend(){
		$uid = $this->getUserId();
		$page = input('page','','trim,htmlspecialchars');
		$limit = input('limit','','trim,htmlspecialchars');
		$type = input('type','','trim,htmlspecialchars');
		$map['closed'] =0;
		$map['parent_id'] =$uid;
		if($page == 1){
			$star = 0;
			$end = $limit;
		}else{
			$star = $limit*$page;
			$end = $limit;
		}
			
		$count = Db::name('users')->where($map)->count();	
		$Page = new \Page3($count,5);
        $show = $Page->show();
		if($Page->totalPages < $page){
            $list = array();
        }else{
			$list = Db::name('users')->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();	
			foreach($list as $k=>$v){
				$list[$k]['avatar'] = config_weixin_img($v['face']);
				$list[$k]['nickname'] = $v['nickname'];
				$list[$k]['mobile'] = $v['mobile'];
				$list[$k]['ctime'] = date('Y-m-d H:i:s',$v['reg_time']);
			}
		}	
			
		
		$data['list'] = $list;
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function getdefaultarea(){
		$uid = input('uid','','trim,htmlspecialchars');
		$v = Db::name('user_addr')->where(array('user_id'=>$uid,'type'=>1,'is_default'=>1))->find();
		if(!$v){
			$v = Db::name('user_addr')->where(array('user_id'=>$uid,'type'=>1,'is_default'=>0))->order('addr_id desc')->find();
		}
		if($v){
			$data['sender_name'] = $v['name'];
			$data['sender_phone'] = $v['phone'];
			$data['sender_mobile'] = $v['mobile'];
			if(strpos($v['address'],$v['province']) !== false){ 
			 	$data['sender_address'] = $v['address'];
			}else{
			 	$data['sender_address'] = $v['province'].$v['city'].$v['area'].$v['address'];
			}
			$data['sender_province'] = $v['province'];
			$data['sender_city'] = $v['city'];
			$data['sender_area'] = $v['area'];
			$data['id'] = $v['addr_id'];
		}
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function help(){
		$data = Db::name('article')->where(array('closed'=>0))->order('orderby asc')->limit(0,30)->select();
		foreach($data as $k=>$v){
			$data['content'] = $v['details'];
			$data[$k]['id'] = $v['article_id'];
		}
		return json(array('code'=>1,'data'=>$data));
	}
	public function helpdetail(){
		$id = input('id','','trim,htmlspecialchars');
		$data = Db::name('article')->where(array('article_id'=>$id))->find();
		$data['content'] = $data['details'];
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//运费计算规则
	public function getJFrule(){
		$data['0']= $this->config['wxapp']['first_gz_1'];
		$data['1']= $this->config['wxapp']['first_gz_2'];
		$data['2']= $this->config['wxapp']['first_gz_3'];
		$data['3']= $this->config['wxapp']['first_gz_4'];
		$data['4']= $this->config['wxapp']['first_gz_5'];
		$data['5']= $this->config['wxapp']['first_gz_6'];
		return json(array('code'=>1,'msg'=>"查询成功",'data'=>$data));
	}   

	public function addtickets(){
		$content = input('content','','trim,htmlspecialchars');
		$username = input('username','','trim,htmlspecialchars');
		$phone = input('phone','','trim,htmlspecialchars');
		$logisticID = input('logisticID','','trim,htmlspecialchars');
		$types = input('type','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$images = input('images','','trim');
		//整合图片
		$images = @substr($images,1);
		$images = @substr($images,0,-1);
		$images = @explode(",",$images);
		$img = array();
		foreach($images as $k=>$v){
			$s = @substr($v,1);
			$s = @substr($s,0,-1);
			$img[$k] = $s;
		}
		$i = @implode(",",$img);
		
		$Data['type'] = 2;
		$Data['user_id'] = $uid;
		$Data['content'] = $content;
		$Data['contact'] = $username;
		$Data['phone'] = $phone;
		$Data['username'] = $username;
		$Data['logisticID'] = $logisticID;
		$Data['types'] = $types;
		$Data['images'] = $i;
		$Data['create_time'] = time();
		
		Db::name('express_msg')->insertGetId($Data);
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function gettickets(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function getTicketsType(){
		$data[0] = '售后类型';
		$data[1] = '订单类型';
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function getDetailedrule(){
		$data[0] = $this->config['profit']['profit_xize_1'];
		$data[1] = $this->config['profit']['profit_xize_2'];
		$data[2] = $this->config['profit']['profit_xize_3'];
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	

    //获取时间戳
	public function getNextWeekOf($date){
		$dates = array();
		$time  = strtotime($date.' 12:00:00');
		$nextMonday = 0;
		for($i = $nextMonday; $i<$nextMonday + 3; $i++){
			$strDate = date('Y-m-d', $time + 3600*24*$i);
			if($i == 0){
				$dates[$i]['name'] = '今天';
			}
			if($i ==1){
				$dates[$i]['name'] = '明天';
			}
			if($i == 2){
				$dates[$i]['name'] = '后天';
			}
			$dates[$i]['dates']= $strDate;
		}
		$dates = array_values($dates);
		return $dates;
	}
	
	
	//获取小时
	public function getTimes($dates){
        $times =  array(
            1=>array('name' => '09:00-11:00', 'num' =>'9:00'),
            2=>array('name' => '11:00-13:00', 'num' =>'11:00'),
            3=>array('name' => '13:00-15:00', 'num' =>'12:00'),
            4=>array('name' => '15:00-17:00', 'num' =>'15:00'),
            5=>array('name' => '17:00-19:00', 'num' =>'17:00')
        );
		$t = array();
		foreach($times as $k => $v){
			$strtotime = strtotime($dates.$v['num']);
			if($strtotime < (time()+600)){
				unset($times[$k]);
			}else{
				$t[$k] = $v['name'];
			}
		}
		$t = @array_values($t);
		return $t;
    }
	
	

	
	
	//预约时间
	public function yuyuetime(){
		$getNextWeekOf = $this->getNextWeekOf(TODAY);
		$data = array();
		foreach($getNextWeekOf as $k => $v){
			$times= $this->getTimes($v['dates']);
			$h = date('H');
			if($v['name'] == '今天'){
				if($h >= 18){
					unset($k);
				}else{
					$data[$k]['children'] = $times;
					$data[$k]['key'] = $k;
					$data[$k]['name'] = $v['name'];
				}
			}else{
				$data[$k]['children'] = $times;
				$data[$k]['key'] = $k;
				$data[$k]['name'] = $v['name'];
			}
		}
		$data = array_values($data);
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}       
	
	//温馨提示
	public function xiadantxt(){
		//快递公司
		$express_code = input('express_code','','trim,htmlspecialchars');
		$express_channel = input('express_channel','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		
		$data = array();
		$eo= Db::name('express_order')->where(array('orderStatus'=>array('in',array(0,1,2,3)),'user_id'=>$uid))->limit(0,3)->select();
		foreach($eo as $k => $v){
			if($v['orderStatus'] == 0){
				$t = '订单ID-'.$v['id'].'未付款请支付';
			}elseif($v['orderStatus'] == 1){
				$t = '订单ID-'.$v['id'].'已付款等待取件，单号【'.$v['expressNo'].'】';
			}elseif($v['orderStatus'] == 2){
				$t = '订单ID-'.$v['id'].'已取件等待取件看快递员【'.$v['realOrderState'].'】';
			}elseif($v['orderStatus'] == 3){
				$t = '订单ID-'.$v['id'].'已取件等待取件看快递员【'.$v['realOrderState'].'】';
			}
			$data[] = $t;
		}
		//p($data);die;
		//0代表失败
		if(count($data)){
			return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
		}else{
			return json(array('code'=>0,'msg'=>"获取成功",'data'=>$data));
		}
	}
	public function autoGetNewRen(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function etSubscriptionsId(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getOrderInformation(){
		$express_code = input('express_code','','trim,htmlspecialchars');
		$data['title'] = $express_code;
		$data['volumetext'] = '如您寄的是抛货(如羽绒服)或外包装太大，体积重量大于实际包裹重量时，体积重量将作为计费重量来计算运费，体积重量=长度(cm)x宽度(cm)x高度(cm)/8000';
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	//订阅消息ID
	public function getSubscriptionsId(){
		$scene = input('scene','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		//订阅消息ID
		$data[0] = Db::name('weixin_tmpl')->where(array('title'=>'接单成功提醒'))->value('template_id');
		$data[1] = Db::name('weixin_tmpl')->where(array('title'=>'补差价通知'))->value('template_id');
		$data[2] = Db::name('weixin_tmpl')->where(array('title'=>'签收成功通知'))->value('template_id');
		
		return json(array('code'=>1,'msg'=>"获取模板消息成功",'data'=>$data));
	}
	public function newslist(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function navigationApi(){
		$data = Db::name('navigation')->where(array('status'=>0))->order('orderby asc')->limit(0,8)->select();
		foreach($data as $k=>$v){
			$data[$k]['name'] = $v['nav_name'];
			$data[$k]['info'] = $v['title'];
			$data[$k]['tag'] = $v['colour'];
			$data[$k]['url'] = $v['url'];
			$data[$k]['icon'] = config_weixin_img($v['photo']);
		}
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
			

	public function bannerdetail(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}    	 
		 
    public function informdetail(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	//更新模板消息
	public function updateUserSubscribe(){
		$uid = $this->getUserId();
		$data['uid'] =1;
		return json(array('code'=>1,'msg'=>"操作成功",'data'=>$data));
	}
	//最高保价
	public function computeOffer(){
		//保价金额
		$insuranceValue= input('insuranceValue','','trim,htmlspecialchars');
		//快递公司
		$express_code= input('express_code','','trim,htmlspecialchars');
		
		//保价/2
		//保价费率
		
		$baojia_rate = $this->config['wxapp']['baojia_rate'] ? $this->config['wxapp']['baojia_rate'] : '0.005';
		$data = round($insuranceValue*$baojia_rate,2);
		
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	//分享海报
	public function createOrderPoster(){
		$uid = $this->getUserId();
		$order_id= input('logisticID','','trim,htmlspecialchars');
		$page="pages/index/index";//路径
		$width = '200';
		$res = model('Api')->qrcodeWxapp($uid,$page,$width,$parameter='userId',$uid);
		$data['user_poster_url'] = config_weixin_img($res);
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	public function sms(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getSignPackage(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}   
	
    public function decactivity(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getCouponAll(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	
	//检测优惠券是否可用
	public function isUseCoupon(){
		$uid = $this->getUserId();
		$pay_money = input('pay_money','','trim,htmlspecialchars');
		$promotion_id = (int)input('promotion_id','','trim,htmlspecialchars');
		$download = Db::name('coupon_download')->where(array('download_id'=>$promotion_id))->find();
		if(!$download){
			return json(array('code'=>0,'msg'=>"未选择优惠券"));
		}
		$coupon = Db::name('coupon')->where(array('coupon_id'=>$download['coupon_id']))->find();
		if(!$coupon){
			return json(array('code'=>0,'msg'=>"参数错误"));
		}
		
		if($coupon['expire_date'] < TODAY){
			return json(array('code'=>0,'msg'=>"优惠券已过期"));
		}
		$count = (int)Db::name('express_order')->where(array('orderStatus'=>4,'user_id'=>$uid))->count();
		if($coupon['limit_num']){
			if($count < $coupon['limit_num']){
				return json(array('code'=>0,'msg'=>"单数不够，当前优惠券需要完成【".$coupon['limit_num']."】单才能使用，您已经完成【".$count."】单请重新选择"));
			}
		}
		
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	
	
	public function checkPayCode(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	public function comroll(){
		$data = Db::name('user_profit_logs')->order('log_id desc')->limit(0,5)->select();
		foreach($data as $k=>$v){
			$u = Db::name('users')->where(array('user_id'=>$v['user_id']))->field('user_id,nickname')->find();
			$data[$k]['title'] = '恭喜'.$u['nickname'].'获得'.round($v['money']/100,2).'元奖励，点击分享返佣';
			$data[$k]['jump_url'] = "/pages/member/invite/invite";
			$data[$k]['click'] = '1';
		}
		if(!$data){
			$data[0]['title'] = '快去分享获取佣金吧';
			$data[0]['jump_url'] = "/pages/member/invite/invite";
			$data[0]['click'] = '1';
		}
		return json(array('code'=>1,'data'=>$data));
	}
	public function TicketsListApi(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}  
	
	public function looks(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function addanswer(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function end(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function notirecip(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function checkNotire(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function checkNotireMob(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}  
	
	
	public function getCanList(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function applyInvoice(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getInvoiceTit(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function addInvoiceTit(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function editInvoiceTit(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function selectInvoiceTitById(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}  
	
	public function delInvoiceTit(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getInvoicehis(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function getInvoiceHelp(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function sendInvoiceEmail(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function sumOfMoney(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function applyAllInvoice(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}  
	
	
	public function sentoptxt(){
		$data['content'] = $this->config['wxapp']['tip1'] ? $this->config['wxapp']['tip1'] : "您好，如需修改运单信息，可点【再来一单】重新下单，并将错误运单撤消。";
		$data['id'] = 11;
		$data['title'] = "注意事项";
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function getFebCouList(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function checkFebRes(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	public function newspop(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}
	
	
	public function defendpop(){
		$close = (int)$this->config['site']['web_close'];
		if($close == 1){
			$data['status'] = 1;
		}else{
			$data['status'] = 0;
		}
		$data['content'] = $this->config['site']['web_close_title'];
		$data['title'] = "升级维护公告";
		$data['site'] = $this->config['site'];
		$data['integral'] = $this->config['integral'];
		$data['integral']['video_cover'] = config_weixin_img($this->config['integral']['video_cover']);
		$data['wxapp'] = $this->config['wxapp'];
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	} 
	
	
	
	public function defendpop1(){
		$defendpop = (int)$this->config['wxapp']['defendpop'];
		$close = (int)$this->config['site']['web_close'];
		
		if($defendpop == 1 && $close==0){
			$data['status'] = 1;
		}else{
			$data['status'] = 0;
		}
		$data['content'] = $this->config['wxapp']['defendpop_info'];
		$data['title'] = $this->config['wxapp']['defendpop_title'];
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	} 
	
	
	
	//下单准备
	public function create(){
		 
		$smail_id = input('smail_id','','trim,htmlspecialchars');
		$rmail_id = input('rmail_id','','trim,htmlspecialchars');
		$cargodata = input('cargodata','','trim');
		$totalNumber = input('totalNumber','','trim,htmlspecialchars');
		$totalWeight = input('totalWeight','','trim,htmlspecialchars');
		$long = input('long','','trim,htmlspecialchars');
		$width = input('width','','trim,htmlspecialchars');
		$height = input('height','','trim,htmlspecialchars');
		$height = input('height','','trim,htmlspecialchars');
		$sendStartTime = input('sendStartTime','','trim,htmlspecialchars');
		$sendEndTime = input('sendEndTime','','trim,htmlspecialchars');
		$remark = input('remark','','trim,htmlspecialchars');
		$insuranceValue = input('insuranceValue','','trim,htmlspecialchars');//保障金额
		$insurancePrice = input('insurancePrice','','trim,htmlspecialchars');//保费
		$coupon_code = (int)input('coupon_code','','trim,htmlspecialchars');
		$source = input('source','','trim,htmlspecialchars');//类型
		$is_dw = input('is_dw','','trim,htmlspecialchars');
		$uid = $this->getUserId();
		$cargodata =  json_decode($cargodata,true);
		//寄件地址
		$s = Db::name('user_addr')->where(array('addr_id'=>$smail_id))->find();
		//收件地址
		
		if($is_dw == 1){
			//得物地址
			$r = Db::name('user_addr_dewu')->where(array('id'=>$rmail_id))->find();
			$r['province'] = $r['sender_province'];
			$r['city'] = $r['sender_city'];
			$r['area'] = $r['sender_area'];
			$r['phone'] = $r['sender_phone'];
			$r['mobile'] = $r['sender_mobile'];
			$r['address'] = $r['sender_address'];
			$r['name'] = $r['sender_name'];
		}else{
			$r = Db::name('user_addr')->where(array('addr_id'=>$rmail_id))->find();
		}
		
		
		//返回订单号
		$t = (int)$cargodata['type'];
		if($t==0){
			return json(array('code'=>0,'msg'=>'接口模式有误'));
		}
		
		$u = Db::name('users')->where(array('user_id'=>$uid))->find();
		$e = Db::name('express_cate')->where(array('cate_name'=>$cargodata['express_code']))->find();
		
		$data['coupon_pmt'] = 0;
		if($coupon_code){
			$coupon_id = Db::name('coupon_download')->where(array('download_id'=>$coupon_code))->value('coupon_id');
			$co = Db::name('coupon')->where(array('coupon_id'=>$coupon_id))->find();
			if($co['expire_date'] > TODAY && $co['reduce_price']){
				$data['coupon_pmt'] = $co['reduce_price'];//优惠金额
				$data['coupon_download_id'] = $coupon_code;//优惠券使用ID
			}
		}
		if(!$s){
			return json(array('code'=>0,'msg'=>'寄件地址详情不存在'));
		}
		if(!$r){
			return json(array('code'=>0,'msg'=>'收件地址详情不存在'));
		}
		if(!$uid){
			return json(array('code'=>0,'msg'=>'会员信息不存在'));
		}
		$eos = Db::name('express_order')->where(array('user_id'=>$uid))->order('id desc')->field('user_id,id,create_time,diffStatus')->find();
		$tm = time();
		$ctm = $tm-$eos['create_time'];
		$catm = $ctm-30;
		//p();die;
		if($ctm < 30){
			return json(array('code'=>0,'msg'=>'下单速度过快，请稍后【'.abs($catm).'】秒后重试'));
		}
		$eos2 = Db::name('express_order')->where(array('user_id'=>$uid,'diffStatus'=>1,'diffMoneyYuan'=>array('gt',0)))->order('id desc')->field('user_id,id,create_time,diffStatus')->find();
		if($eos2){
			return json(array('code'=>0,'msg'=>'订单【'.$eos2['id'].'】还有差价【'.round($eos2['diffMoneyYuan']/100,2).'】元未补齐，补齐差价后下单'));
		}
		
		
		$d1 = strstr($r['address'],$r['province']);
		if($d1 == false){
			$r_address = $r['province'].''.$r['city'].''.$r['area'].''.$r['address'];
		}else{
			$r_address = $r['address'];
		}
		
		$d2 = strstr($s['address'],$s['province']);
		if($d2 == false){
			$s_address = $s['province'].''.$s['city'].''.$s['area'].''.$s['address'];
		}else{
			$s_address = $s['address'];
		}
		
		
		if($r['phone']!='' && $r['phone']){
			$receiveMobile = $r['phone'];
		}elseif($r['mobile']!='' && $r['mobile']){
			$receiveMobile = $r['mobile'];
		}else{
			$receiveMobile= '17194348715';
		}
		
		if($s['phone']!='' && $s['phone']){
			$senderMobile = $s['phone'];
		}elseif($s['mobile']!='' && $s['mobile']){
			$senderMobile = $s['mobile'];
		}else{
			$senderMobile= '17194348715';
		}
		
		
		//内部单号
		$oid = Db::name('express_order')->order('id desc')->limit(0,1)->value('id');
		$thirdNo = ($oid+1).rand_string(6,1);//外部单号
		//订单数据
		
		$data['is_pei'] = (int)$e['is_pei'];
		$data['orderType'] = 0;
		$data['kuaidi'] = $cargodata['express_code'];
		$data['cargoName'] = $cargodata['name'];//商品名称
		$data['pid'] = $u['parent_id'];
		$data['deliveryId'] = 0;//快递公司返回ID
		$data['expressId'] = 0;//快递公司ID
		$data['closed'] = 0;
		$data['expressNo'] = 0;//快递公司单号
		$data['user_id'] = $uid;
		$data['orderStatus'] = 0;//0待付款,1已付款-待接单2已接单-待取货,3已取件-配送中4已完成5已取消已退款
		$data['diffStatus'] = 0;//1补差价
		$data['orderNo'] = $thirdNo;//orderNo订单号
		$data['orderRightsStatus'] = 0;//0代取件1退款审核中2退款完成
		$data['createTime'] = time();
		$data['wight'] = $totalWeight;//重量
		$data['totalNumber'] = $totalNumber;//数量
		$data['insuranceValue'] = $insuranceValue*100;//保障金额
		$data['insurancePrice'] = $insurancePrice*100;//保费
		$data['wight'] = $totalWeight;//重量
		$data['totalVolume'] = '';//体积
		$data['sumMoneyYuan'] = 0;//支付金额
		$data['diffMoneyYuan'] =0;//差价金额
		$data['sendName'] = $s['name'];
		$data['sendMobile'] = $senderMobile;
		$data['sendCity'] = $s['city'];
		$data['sendAddress'] = $s_address;
		$data['receiveName'] = $r['name'];
		$data['receiveMobile'] = $receiveMobile;
		$data['receiveCity'] = $r['city'];
		$data['receiveAddress'] = $r_address;
		$data['create_time'] = time();
		$data['remark'] = $remark;//备注
		$data['yuyuetime'] = $sendStartTime.' '.$sendEndTime;
		
		
		if($sendEndTime == '09:00-11:00'){
			$st = $sendStartTime.' 09:00:00';
			$et = $sendStartTime.' 11:00:00';
		}elseif($sendEndTime == '11:00-13:00'){
			$st = $sendStartTime.' 11:00:00';
			$et = $sendStartTime.' 13:00:00';
		}elseif($sendEndTime == '13:00-15:00'){
			$st = $sendStartTime.' 13:00:00';
			$et = $sendStartTime.' 15:00:00';
		}elseif($sendEndTime == '15:00-17:00'){
			$st = $sendStartTime.' 15:00:00';
			$et = $sendStartTime.' 17:00:00';
		}elseif($sendEndTime == '17:00-19:00'){
			$st = $sendStartTime.' 17:00:00';
			$et = $sendStartTime.' 19:00:00';
		}
	
		if($t == 1){
	
			
			//易达接口下单保存数据
			if($cargodata['express_code'] == '京东'){
				$deliveryType = 'JD';
			}elseif($cargodata['express_code'] == '圆通'){
				$deliveryType = 'YTO';
			}elseif($cargodata['express_code'] == '申通'){
				$deliveryType = 'STO-INT';
			}elseif($cargodata['express_code'] == '德邦'){
				$deliveryType = 'DOP';
			}elseif($cargodata['express_code'] == '极兔'){
				$deliveryType = 'JT';
			}elseif($cargodata['express_code'] == '中通'){
				$deliveryType = 'ZTO';
			}elseif($cargodata['express_code'] == '顺丰'){
				$deliveryType = 'SF';
			}elseif($cargodata['express_code'] == '韵达'){
				$deliveryType = 'YUND';
			}elseif(strstr($cargodata['express_code'],'京东') == true){
				$deliveryType = 'JD';
			}elseif(strstr($cargodata['express_code'],'圆通') == true){
				$deliveryType = 'YTO';
			}elseif(strstr($cargodata['express_code'],'申通') == true){
				$deliveryType = 'STO-INT';
			}elseif(strstr($cargodata['express_code'],'中通') == true){
				$deliveryType = 'ZTO';
			}elseif(strstr($cargodata['express_code'],'德邦') == true){
				$deliveryType = 'DOP';
			}elseif(strstr($cargodata['express_code'],'极兔') == true){
				$deliveryType = 'JT';
			}elseif(strstr($cargodata['express_code'],'顺丰') == true){
				$deliveryType = 'SF';
			}elseif(strstr($cargodata['express_code'],'韵达') == true){
				$deliveryType = 'YUND';
			}
			
			if($deliveryType == 'SF' && !$data['yuyuetime']){
				return json(array('code'=>0,'msg'=>'顺丰必须填写预约时间'));
			}
			
			
			//p($r['province']);
			
			
			if($is_dw == 1){
				$customerType = 'poizon';
			}else{
				$customerType = 'kd';
			}
			
			
			$requestParams2['senderAddress']=$s_address;// 寄件人地址
			$requestParams2['goods']=$cargodata['name'];
			$requestParams2['thirdNo']=$thirdNo;
			$requestParams2['senderName']=$s['name'];
			$requestParams2['receiveName']= $r['name'];
			
			
			$isMobile = isMobile($receiveMobile);
			if(!$isMobile){
				$requestParams2['receiveTel']=$receiveMobile;
			}elseif($customerType == 'poizon'){
				$requestParams2['receiveTel']=$receiveMobile;
			}else{
				$requestParams2['receiveMobile']=$receiveMobile;
			}
		
			
			$requestParams2['receiveDistrict']=$r['area'];//收件区县
			$requestParams2['receiveAddress']=$r_address;//收件地址
			$requestParams2['senderDistrict']=$s['area'];//寄件区县
			$requestParams2['deliveryType']=$deliveryType;
			
			
			$isMobile1 = isMobile($senderMobile);
			if(!$isMobile1){
				$requestParams2['senderTel']=$senderMobile;
			}else{
				$requestParams2['senderMobile']=$senderMobile;
			}
			
			
			
			$requestParams2['weight']=$totalWeight;//重量
			$requestParams2['customerType']=$customerType;
			$requestParams2['senderProvince']=$s['province'];//收件省份
			$requestParams2['receiveProvince']=$r['province'];//寄件省份
			$requestParams2['senderCity']=$s['city'];//收件城市
			$requestParams2['receiveCity']=$r['city'];//寄件城市
			$requestParams2['unitPrice']=10;//申通情况必填 单价
			$requestParams2['qty']=$totalNumber;//申通情况必填 数量
			$requestParams2['pickUpStartTime']=$st;//顺丰预约时间
			$requestParams2['pickUpEndTime']=$et;
			$requestParams2['vloumLong']=$long ? $long : 1;//长
			$requestParams2['vloumHeight']=$height ? $height : 1;//高
			$requestParams2['vloumWidth']=$width ? $width : 1;//宽
			$requestParams2['packageCount']=$totalNumber;//包裹数
			$requestParams2['guaranteeValueAmount']=$insurancePrice;//保价
			$requestParams2['receiveProvinceCode']='';//收件省code-编码参照国务院最新颁布
			$requestParams2['senderProvinceCode']='';//寄件省code-编码参照国务院最新颁
			$requestParams2['channelId']=$cargodata['express_channel'];//寄件省code-编码参照国务院最新颁布
			
			$data['requestParams2'] = iserializer($requestParams2);//易达接口保存到数据库序列化
			
			//p($requestParams2);die;
			//易达
			$execute = model('Setting')->execute($requestParams2,$Method='PRE_ORDER');
			//p($execute);die;
			if($execute['code'] == 200){
				//获取快递
				$logoUrl = model('ExpressOrder')->logoUrl($cargodata['express_code']);
				//p($logoUrl);
				$limitWeight = (int)$execute['data']['limitWeight'];
				if($totalWeight > $limitWeight){
					return json(array('code'=>0,'msg'=>'所邮寄物品超过限重【'.$limitWeight.'】'));
				}
				//原价计费规则 
				$originalPrice = $execute['data']['originalPrice'];
				$originalPrice =  @json_decode($originalPrice,true);
				//计费规则
				$first = $execute['data']['price'];
				$first =  @json_decode($first,true);
				
			
				
				$v = $execute['data'];
				$getYidaPrice = model('Setting')->getYidaPrice($uid,$first,$v,$totalWeight,$e,$data['coupon_pmt'],$co,$execute['data']['preBjFee']);
				//p($getYidaPrice);die;
				$data['firstPrice'] = $getYidaPrice['firstPrice'];
				$data['addPrice'] =$getYidaPrice['addPrice'];
				$data['firstPrice_jia'] = $getYidaPrice['firstPrice_jia'];
				$data['addPrice_jia'] = $getYidaPrice['addPrice_jia'];
				$data['preOrderFee'] = $getYidaPrice['preOrderFee'];
				$data['sumMoneyYuan'] = $getYidaPrice['sumMoneyYuan'];
				$data['sumMoneyYuan_old'] =$getYidaPrice['sumMoneyYuan_old'];
				$data['sumMoneyYuan_jia'] = $getYidaPrice['sumMoneyYuan_jia'];
				$data['type'] =1;//接口模式
				//p($execute['data']);
			}else{
				return json(array('code'=>0,'msg'=>'YD获取预支付订单详情失败'.$execute['msg']));
			}
		}elseif($t == 2){
	
			//云洋接口	
			$content['sender']=$s['name'];
			$content['senderMobile']= $senderMobile;
			$content['senderProvince']= $s['province'];
			$content['senderCity']= $s['city'];
			$content['senderCounty']= $s['area'];
			$content['senderTown']=$s['area'];
			$content['senderLocation']= $s['address'];
			$content['senderAddress']= $s['address'];
			$content['receiver']=$r['name'];
			$content['receiverMobile']= $receiveMobile;
			$content['receiveProvince']= $r['province'];
			$content['receiveCity']= $r['city'];
			$content['receiveCounty']= $r['area'];
			$content['receiveTown']= $r['area'];
			$content['receiveLocation']= $r['address'];
			$content['receiveAddress']=$r['address'];
			$content['weight']= $totalWeight;
			$content['packageCount']= $totalNumber;
			$content['insured']= $insurancePrice;
			$content['vloumLong']= $long ? $long : 1;
			$content['vloumWidth']= $width ? $width : 1;
			$content['vloumHeight']=$height ? $height :1;
			$content['autoMatchLevel']= 1;
			$content['channelTag']= '智能';
			$content['billType']=0;
			$content['subType']= 'wds';
			
			
			
			//云洋下单保存数据
			$requestParams['channelTag']="智能";
			$requestParams['sender']=$s['name'];
			$requestParams['senderMobile']= $senderMobile;
			$requestParams['senderProvince']= $s['province'];
			$requestParams['senderCity']= $s['city'];
			$requestParams['senderCounty']= $s['area'];
			$requestParams['senderTown']= $s['area'];
			$requestParams['senderLocation']= $s['address'];
			$requestParams['senderAddress']= $s_address;
			$requestParams['receiver']= $r['name'];
			$requestParams['receiverMobile']= $receiveMobile;
			$requestParams['receiveProvince']= $r['province'];
			$requestParams['receiveCity']= $r['city'];
			$requestParams['receiveCounty']= $r['area'];
			$requestParams['receiveTown']= $r['area'];
			$requestParams['receiveLocation']= $r['address'];
			$requestParams['receiveAddress']= $r_address;
			$requestParams['weight']= $totalWeight;
			$requestParams['billType']= $e['cate_id'];
			$requestParams['packageCount']= $totalNumber;
			$requestParams['itemName']= $cargodata['name'];
			$requestParams['senderCompany']= "";
			$requestParams['receiveCompany']= "";
			$requestParams['insured']= $insurancePrice;//保费
			$requestParams['vloumLong']= $long ? $long : 1;
			$requestParams['vloumWidth']= $width ? $width : 1;
			$requestParams['vloumHeight']= $height ? $height :1;
			$requestParams['warehouseCode']= "";
			$requestParams['channelId']= $cargodata['express_channel'];
			
			
			$h = date('H');
			$h = $h+2;
			//$requestParams['pickupStartTime']= $sendStartTime ? $sendStartTime." ".$h.":00:00" : TODAY." ".$h.":00:00";//$sendStartTime." 09:00:00";
			//$requestParams['pickupStopTime']= $sendStartTime ? $sendStartTime." 19:00:00" : TODAY." 19:00:00";;//$sendStartTime ." ".$sendEndTime;
			$requestParams['pickupStartTime']= null;
			$requestParams['pickupStopTime']= null;
			
			$requestParams['collectionMoney']= 0;
			$requestParams['billRemark']= $remark;
			$requestParams['subType']= "wds";
			$requestParams['autoMatchLevel']= '1';
			$requestParams['modelType']="ZK";
			$data['requestParams'] = iserializer($requestParams);//保存到数据库序列化
			
			//检测价格
			//$performance = model('Setting')->performance($requestParams,$Method ='ADD_BILL');
			//p($performance);die;

			$performance = model('Setting')->performance($content,$Method ='CHECK_CHANNEL_INTELLECT');
			//云洋计算单价
			if($performance['code'] == 1){
				
				$result = $performance['result'];
				foreach($result as $ks=>$vs){
					if($cargodata['express_channel'] == $vs['channelId']){
						$v = $vs;
					}
				}
				
				//p($v);die;
				
				$getYidaPrice = model('Setting')->getYunyangPrice($uid,$firsts=array(),$v,$data['totalWeight'],$e,$data['coupon_pmt'],$co,$insurancePrice);//没有加价
				
				$data['firstPrice'] = $getYidaPrice['firstPrice'];
				$data['addPrice'] = $getYidaPrice['addPrice'];
				$data['firstPrice_jia'] = $getYidaPrice['firstPrice_jia'];
				$data['addPrice_jia'] = $getYidaPrice['addPrice_jia'];
				$data['preOrderFee'] = $getYidaPrice['preOrderFee'];
				$data['sumMoneyYuan'] = $getYidaPrice['sumMoneyYuan'];
				$data['sumMoneyYuan_old'] = $getYidaPrice['sumMoneyYuan_old'];
				$data['sumMoneyYuan_jia'] = $getYidaPrice['sumMoneyYuan_jia'];
				$data['type'] =2;//云洋接口模式
				
			}else{
				return json(array('code'=>0,'msg'=>'YY获取价格失败-'.$performance['message']));
			}
		}
		
		
		
		//用户实际支付金额【用于前台支付】
		$order_money = round($data['sumMoneyYuan']/100,2);
		
	    //($data);die;
	    //预下单
		
		if($order_money <= 0){
			return json(array('code'=>0,'msg'=>'获取价格失败，请重新选择快递公司下单'));
		}
		$order_id = Db::name('express_order')->insertGetId($data);
		if($order_id){
			$data['order_id'] = $order_id;	
			$data['recipients_id'] = $uid;	
			$data['order_money'] = $order_money;	
			$data['is_jump_list'] = false;	
			
			
			return json(array('code'=>1,'msg'=>"下单成功",'data'=>$data,'v'=>$v));
		}else{
			return json(array('code'=>0,'msg'=>'写入数据库失败'));
		}
	}  
	
	
	
	//下单支付
	public function submitpay(){
		
		 $order_id = input('order_id','','trim,htmlspecialchars');
		 $paytype= input('paytype','','trim,htmlspecialchars');
		 $ordertype = input('ordertype','','trim,htmlspecialchars');
		 $is_jump_list = input('is_jump_list','','trim,htmlspecialchars');
		 $platform = input('platform','','trim,htmlspecialchars');
		 $uid = $this->getUserId();
		 $u = Db::name('users')->where(array('user_id'=>$uid))->find();
		 $o = Db::name('express_order')->where(array('id'=>$order_id))->find();
		
		 
		 if($ordertype == 1){
			 $need_pay = $o['sumMoneyYuan']; 
			 $types = 1;
			 $info = '快递下单';
		 }elseif($ordertype == 3){
			 $need_pay = $o['diffMoneyYuan']; 
			 $types = 2;
			 $info = '差价订单';
		 }else{
			 $need_pay = $o['sumMoneyYuan']; 
			 $types = 1;
			 $info = '快递下单';
		 }
		 
		 
		 if($paytype =='wechat'){
			//微信支付
			$logs = array(
				'type' => 'express', 
				'types' => $types, 
				'user_id' => $uid, 
				'order_id' => $order_id, 
				'code' => 'wxapp', 
				'info' => $info, 
				'need_pay' =>$need_pay, 
				'create_time' => time(), 
				'create_ip' => request()->ip(), 
				'is_paid' => 0
			);
			$logs['log_id'] = Db::name('payment_logs')->insertGetId($logs);
			
			
			$connect = Db::name('connect')->where(array('uid'=>$uid))->find();	
			$WX_OPENID = $connect['openid'] ? $connect['openid'] : $connect['open_id'];	

			$Payment = model('Payment')->getPayment('wxapp');
			$out_trade_no = $logs['log_id'].'-'.time();
			$weixinpay = new \Wxpay($this->config['wxapp']['appid'],$WX_OPENID,$Payment['mchid'],$Payment['appkey'],$out_trade_no,$info,$need_pay);//支付接口
			$return = $weixinpay->pay();
			if($return['package'] == 'prepay_id='){
				return json(array('code'=>0,'msg'=>'预支付失败:'.$return['rest']['return_msg']));
			}
			$payInfo['timeStamp']= $return['timeStamp'];
			$payInfo['nonceStr'] =$return['nonceStr'];
			$payInfo['package'] =$return['package'];
			$payInfo['signType'] = 'MD5';
			$payInfo['paySign'] = $return['paySign'];

			return json(array('code'=>1,'msg'=>"微信支付下单成功",'data'=>$payInfo));
		}elseif($paytype == 'balance'){
			if($u['money'] < $need_pay){
				return json(array('code'=>0,'msg'=>'余额不足'));
			}
			$rest = model('Users')->addMoney($uid,-$need_pay,'余额支付订单id-'.$order_id,1);
			if($rest){
				
				 if($ordertype == 1){
			 		//正常余额支付订单回调
					$updateExpressOrder = model('Setting')->updateExpressOrder($order_id,$need_pay,$log_id=0,$uid,1);
				 }elseif($ordertype == 3){
					 //差价订单支付回调
					$updateExpressOrder = model('Setting')->updateExpressOrder($order_id,$need_pay,$log_id=0,$uid,2);
				 }
				
				if($updateExpressOrder == false){
					return json(array('code'=>0,'msg'=>'付款回调失败未知错误'.model('Setting')->getError()));
				}
				return json(array('code'=>1,'msg'=>"余额支付下单成功",'data'=>$data));
			}else{
				return json(array('code'=>0,'msg'=>'扣费失败'));
			}
		}
	}
	
	
	
	
    public function createOrder(){
		$data = array();
		return json(array('code'=>1,'msg'=>"获取成功",'data'=>$data));
	}      
    
	
	
	//调用云存储
    public function superUpload($model){
        $upinfo = model("Uploadset")->where("status = 1")->find();
        if(!empty($upinfo) && $upinfo['type'] != 'Local') {
            $conf = json_decode($upinfo['para'], true);
            $superup = new \Upload(array('exts'=>'jpeg,jpg,gif,png'), $upinfo['type'], $conf);
            $upres = $superup->upload(); 
            return  $upres;
        }else{
            return false;
        }
    }
	//图片上传
	public function upload(){
        $model = input('model');
        $yun = $this->superUpload($model);
        if($yun){
            foreach($yun as $pk => $pv){
                $picurl = $pv['url'];
            }
			return json(array('code'=>1,'data'=>config_weixin_img($picurl)));   
        }else{
            $upload = new \UploadFile(); 
            $upload->maxSize = 3145728; 
            $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg'); 
            $name = date('Y/m/d', time());
            $dir = ROOT_PATH . '/attachs/' . $name . '/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $upload->savePath = $dir; 
            if(!$upload->upload()){
                $this->error($upload->getErrorMsg());
            }else{
                $info = $upload->getUploadFileInfo();
                if($upload->thumb){
                    $picurl =  '/attachs/'.$name . '/thumb_' . $info[0]['savename'];
					return json(array('code'=>1,'data'=>config_weixin_img($picurl)));
                }else{
                    $picurl = '/attachs/'.$name . '/' . $info[0]['savename'];
					return json(array('code'=>1,'data'=>config_weixin_img($picurl)));                }
            }
        }
    }
	
	
	
	//获取会员详细数据
	public function getUserData($user_id){
		$data = Db::name('users')->where(array('user_id'=>$user_id))->find();
			if($data){
			$data['token'] = $data['token'];
			$data['avatar'] = config_weixin_img($data['face']);
			if($data['uc_id'] == 0){
				$data['avatar_auth'] = 1;
			}
			$coupon_download = (int)Db::name('coupon_download')->where(array('is_used'=>'0','user_id'=>$user_id))->count(); 	
			$data['coupon_num'] = $coupon_download;
			$data['points'] = $data['integral'];
			$data['promoteId'] = $data['user_id'];
			$data['money'] = round($data['money']/100,2);
			$data['can_withdraw'] = $data['money'];//可提现余额
			$data['withdraw'] = round($data['frozen_money']/100,2);//冻结金额
			$data['id'] = $data['user_id'];
			$data['grade'] = 1;
			if($data['rank_id']){
				$data['grade'] = 2;//2代表是VIP
			}
			$data['subscribe_status'] = $data['subscribe_status'];//1代表已关注	
			return $data;
		}
		return false;
	}
	
	
	//视频列表
	public function couponViewList(){
		$uid = (int)input('uid','','trim,htmlspecialchars');
		$d['list'] = array();
		return json(array('c'=>0,'d'=>$d));
	}
	
	
	//看广告领取积分
	public function reward(){
		$uid = $this->getUserId();
		if(!$uid){
			return json(array('c'=>20020,'m'=>'token失效'));
		}
		$integral = (int)$this->config['integral']['adunit_integral'];
		$bg_time = strtotime(TODAY);
        $logs = (int) Db::name('user_integral_logs')->where(array('create_time' => array(array('ELT', time()), array('EGT', $bg_time)), 'user_id' =>$uid, 'type' =>5))->count();
		if(!$logs && $integral){
			$rest = model('Users')->addIntegral($uid,$integral,'看视频奖励积分',5);
			if($rest){
				$d['money'] = $integral;
				$d['moneyYuan'] = $integral;
				return json(array('c'=>0,'d'=>$d));
			}else{
				//领取逻辑
				return json(array('c'=>20020,'m'=>'领取失败'));
			}
		}else{
			return json(array('c'=>20020,'m'=>'今日已领取过'));
		}
	}



	//注册
	public function addRegisterUser($result){
		
		//p($result);die;
		if($result['unionid'] && $result['unionid'] !='undefined'){
			$connect = Db::name('connect')->where(array('type'=>'weixin','unionid'=>$result['unionid']))->order(array('connect_id'=>'desc'))->find(); 	
		}elseif($result['openid']){
			$connect = Db::name('connect')->where(array('type'=>'weixin','openid'=>$result['openid']))->order(array('connect_id'=>'desc'))->find(); 	; 	
		}elseif($result['mobile']){
			$users = Db::name('users')->where(array('mobile'=>$result['mobile']))->order(array('user_id'=>'desc'))->find();
			$connect = Db::name('connect')->where(array('type'=>'weixin','uid'=>$users['user_id']))->order(array('connect_id'=>'desc'))->find(); 	
		}else{
			$connect = Db::name('connect')->where(array('type'=>'weixin','openid'=>$result['openid']))->order(array('connect_id'=>'desc'))->find(); 	
		}
		
		
		$users['user_id'] = 0;
		if($connect['uid']){
			$users = Db::name('users')->where(array('user_id'=>$connect['uid']))->find();
		}
		
		
		$data['unionid'] = $result['unionid'];
		$data['open_id'] = '';
		$data['openid'] = $result['openid'];
        $data['type'] = 'weixin';
		$data['session_key'] = $result['session_key'];
		$data['rd_session'] = md5(time().mt_rand(1,999999999));
		
		//注册会员
		if(!$users['user_id']){
			if(!$connect){
				$data['create_time'] = time();
				$data['create_ip'] = request()->ip();
				$connect_id = Db::name('connect')->insertGetId($data);//新建表
			}else{
				$connect_id = $connect['connect_id'];//新建表
			}
			
			$rand = rand(1000,9999);
			$account = 'Exp_'.$connect_id.'_'.$rand;
            $arr = array(
               'account' => $account, 
			   'mobile' => $result['mobile'],
               'password' => $rand,
               'unionid' => $result['unionid'], 
               'face' => '/attachs/default.jpg', 
               'nickname' => $account, 
               'reg_time' => time(), 
               'reg_ip' =>request()->ip()
            );
		
            $user_id = model('Passport')->register($arr,$result['parent_id'],1);
			if($user_id){
				Db::name('connect')->update(array('connect_id'=>$connect_id,'uid'=>$user_id));
				$user = Db::name('users')->where(array('user_id'=>$user_id))->find();
				return $this->getUserData($user_id);
			}
		}else{
			
			
			$updateData['connect_id'] = $connect['connect_id'];
			$updateData['openid'] = $result['openid'];
            $updateData['unionid'] = $result['unionid'];
            
			$token = md5(uniqid());
			$user = Db::name('users')->where(array('user_id'=>$connect['uid']))->update(array('token'=>$token));
			if($connect['connect_id']){
				Db::name('connect')->update($updateData);
			}
			
			return $this->getUserData($connect['uid']);
		}
		return true;
	}
	
	public function httpRequest($url,$data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	//获取token
	public function getaccess_token(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->config['wxapp']['appid'] . "&secret=" . $this->config['wxapp']['appsecret'] . "";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data, true);
        return $data['access_token'];
    }
	
	//获取小程序码
	public function set_msg($storeid,$page,$width){
        $access_token = $this->getaccess_token();
        $data2 = array("scene" =>$storeid,"page"=>$page,"width" =>$width);
        $data2 = json_encode($data2);
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token."";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data2);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
     }

	

	
}

<?php
namespace app\app\controller;
use think\Db;
use think\Cache;

use app\common\model\Setting;

class Api extends Base{



	protected function _initialize(){
        parent::_initialize();
		$this->config  = Setting::config();
		$this->host = $this->config['site']['host'];

    }
     
	 
	 //易达推送
	public function push(){
		$file_get_contents = file_get_contents("php://input");
		$input = @json_decode($file_get_contents,true);
		
		//file_put_contents(ROOT_PATH.'/application/app/controller/_易达推送_'.time().'_data.txt',var_export($input,true));
		
		$data['deliveryId'] = $input['deliveryId'];
		$data['orderNo'] = $input['orderNo'];
		$data['deliveryType'] = $input['deliveryType'];
		$data['pushType'] = $input['pushType'];
		$data['context'] = $input['context'];
		$data['type'] = 1;
		$data['status'] = 1;
		$data['create_time'] = time();
		//$id =Db::name('express_order_push')->insert($data);
		$id = 0;
		if($data['deliveryId']){
			$id =Db::name('express_order_push')->insert($data);
		}
		
		return json(array('success'=>true,'msg'=>'接收成功','code'=>200));
	}
	
	
	//云洋推送
	public function push1(){
		$file_get_contents = file_get_contents("php://input");
		$input = @json_decode($file_get_contents,true);
		
		//file_put_contents(ROOT_PATH.'/application/app/controller/_云洋推送_'.time().'_data.txt',var_export($input,true));
		
		$data['deliveryId'] = $input['waybill'];
		$data['orderNo'] = $input['shopbill'];
		$data['deliveryType'] = $input['billType'];
		$data['pushType'] = 1;
		$data['type'] = 2;
		$data['context'] = $file_get_contents;
		$data['status'] = 1;
		$data['create_time'] = time();
		//$id =Db::name('express_order_push')->insert($data);
		$id = 0;
		if($data['deliveryId']){
			$id =Db::name('express_order_push')->insert($data);
		}
		return json(array('message'=>'推送成功！','code'=>1));
	}
	
	

	//更新订单状态
	public function handlePushOrderUpdate($eop,$eo){
		$v = $eo;
		$context = @json_decode($eop['context'],true);
		
		if($eop['type'] == 2){
			
			//云洋
		  $transferWeight = $context["transferWeight"];//分拣称重
		  $freightInsured = $context["freightInsured"];//保价费
		  $comments = $context["comments"];//快递员信息
		  $parseWeight = $context["parseWeight"];//体积换算重量
		  $totalPrice = $context["totalPrice"];//原价
		  $calWeight = $context["calWeight"];//计费重量（最终的扣费重量）
		  $billType = $context["billType"];//快递类型
		  $freight = $context["freight"];//运费（当feeOver等于0时为预付冻结 等于1时则是最终的扣款 不在发生变化）
		  $weight = $context["weight"];//下单实际重量
		  $realWeight = $context["realWeight"];//站点称重（京东、申通将返回此重量 其他快递不返回）
		  $shopbill = $context["shopbill"];//商家单号
		  $type = $context["type"];//运单状态
		  $billOrderId = $context["billOrderId"];
		  $changeBillFreight = $context["changeBillFreight"];//逆向费
		  $linkName = $context ["linkName"];//下单账户
		  $volume =  $context["volume"];//体积 （京东、德邦、申通 将返回体积 其他不返回）
		  $feeOver = $context["feeOver"];//订单扣费状态（1:已扣费 0:冻结）
		  $changeBill =  $context["changeBill"];//换单号
		  $waybill =  $context["waybill"];///运单号
		  $freightHaocaii =  $context["freightHaocai"];//增值费用
		  //备用
		  $handle_info = '云洋【'.$type.'】';
		 
			
		    $falg =0;
			if($v['orderStatus'] == 1 && $type==''){
				$falg =1;
				$orderStatus =2;
			}elseif($v['orderStatus'] == 2 && $type==''){
				$falg =1;
				$orderStatus =2;
			}elseif($v['orderStatus'] == 1 && $type=='已正常收件状态'){
				$falg =1;
				$orderStatus =3;
			}elseif($v['orderStatus'] == 2 && $type=='已正常收件状态'){
				$falg =1;
				$orderStatus =3;
			}elseif($v['orderStatus'] == 1 && $type=='揽收成功'){
				$falg =1;
				$orderStatus =3;
			}elseif($v['orderStatus'] == 2 && $type=='揽收成功'){
				$falg =1;
				$orderStatus =3;
			}
			
		    if($falg=='1'){
			 	$up['totalNumber'] = $packageCount;
				$up['totalVolume'] = $parseWeight;
				$up['review_weight'] = $calWeight ? $calWeight: $realWeight;
				$up['review_vloumn'] = $volume;
				$up['realOrderState'] = $comments;
				$up['orderStatus'] = $orderStatus;
				if($waybill){
					$up['deliveryId'] = $waybill;
				}
				
				
				$up['insurancePrice'] = $freightInsured*100;
				$up['packageServicePrice'] = $freightHaocaii*100;
				$up['insuranceValue'] = $freightInsured*100;
				
				$handle_info = '云洋更新订单状态【'.$type.'】';		
				Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
				model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '接单成功提醒');//更新状态
		    }
		  
		  //补差价
		  $orderFee = $freight*100;
		  if($v['orderStatus'] != '0' && $v['orderStatus'] != '-1' && $v['orderStatus'] != '5'){
			if($orderFee > $v['sumMoneyYuan_old'] && $v['diffStatus'] == 0){
				$v['diffMoneyYuan'] = $orderFee-$v['sumMoneyYuan_old'];//实际扣费之间的差价
				$logoUrl = model('ExpressOrder')->logoUrl($v['kuaidi']);
				$jia = ($v['diffMoneyYuan']*$logoUrl['firstPrice'])/100;
				$v['diffMoneyYuan'] = $v['diffMoneyYuan']+$jia;
				$v['diffMoneyYuan'] = (int)$v['diffMoneyYuan'];
				if($v['diffMoneyYuan']){
					$handle_info = '云洋更新差价';					
					Db::name('express_order')->where(array('id'=>$v['id']))->update(array('diffStatus'=>1,'diffMoneyYuan'=>$v['diffMoneyYuan']));//更新订单
					model('Sms')->sendSmsTmplSend($v,$v['user_id'],$title = '补差价通知');//判断补差价
					model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '补差价通知');
				}
			  }
		    }
			
			//取消订单
			if($type=='下单取消' && $v['orderStatus'] != '-1'  && $v['orderStatus'] != '5' && $v['orderRightsStatus'] == 0){
				$handle_info = '云洋取消订单';
				Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>-1,'orderRightsStatus'=>1)); //已取消待后台退款	
			}
			
			$complete = 0;
			if($type=='已签收' && $v['orderStatus'] == 3){
				$complete = 1;
			}
			if($type=='已签收' && $v['orderStatus'] == 2){
				$complete = 1;
			}
			if($type=='签收' && $v['orderStatus'] == 3){
				$complete = 1;
			}
			if($type=='签收' && $v['orderStatus'] == 2){
				$complete = 1;
			}
			if($complete){
				$handle_info = '云洋订单已完成';
				Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>4)); 	//订单完成
				model('ExpressOrder')->profit($v,$v['user_id'],'分销');//订单完成分销
				model('ExpressOrder')->orderAddIntegral($v,$v['user_id'],'给用户奖励积分');//赠送优惠券
				model('ExpressOrder')->giveCoupon($v,$v['user_id'],'赠送优惠券');//完成订单发送通知
				model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '签收成功通知');
			}
		}
		
		
		//易达
		if($eop['type'] == 1){
			
			 $handle_info = '易达【'.$eop['pushType'].'】';
			
			//状态推送 
			//ydOrderStatus订单状态（根据易达） 1-待取件  2-运输中  3-已签收  6-异常     10-已取消 
			//ydOrderStatusDesc订单状态描述
			if($eop['pushType'] == 1){
				$ydOrderStatus = $context["ydOrderStatus"];//订单状态
				$orderStatus = $ydOrderStatus;
		  		$ydOrderStatusDesc = $context["ydOrderStatusDesc"];//订单状态描述
				
				$u =0;
				if($v['orderStatus'] == 1 && $orderStatus==1){
					$u =1;
					$os = 2;
				}elseif($v['orderStatus'] == 1 && $orderStatus==2){
					$u =1;
					$os = 3;
				}elseif(!$v['realOrderState'] && $orderStatus==1){
					$u =1;
					$os = 2;
				}elseif(!$v['realOrderState'] && $orderStatus==2){
					$u =1;
					$os = 3;
				}elseif(!$v['deliveryId'] == 1 && $deliveryId){
					$u =1;
					$os = 2;
				}
				if($u==1){
					$up['totalNumber'] = $packageCount;
					$up['totalVolume'] = $volume;
					$up['review_weight'] = $weight;
					$up['review_vloumn'] = $realVolume;
					$up['realOrderState'] = $realOrderState;
					$up['orderStatus'] = $os;
					if($deliveryId){
						$up['deliveryId'] = $deliveryId;
					}
					$handle_info = '易达推送订单状态【'.$ydOrderStatusDesc.'】';
					Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
					model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '接单成功提醒');//更新状态
				}
				
				//易达取消订单
				if($ydOrderStatus=='10' && $v['orderStatus'] != '-1'  && $v['orderStatus'] != '5' && $v['orderRightsStatus'] == 0){
					$handle_info = '易达取消订单';
					Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>-1,'orderRightsStatus'=>1)); //已取消待后台退款	
				}
				
				$q=0;
				if($orderStatus==3 && $v['orderStatus'] == 2){
					$q=1;
				}
				if($orderStatus==3 && $v['orderStatus'] == 3){
					$q=1;
				}
				//易达已派送
				if($q){
					$handle_info = '易达推送订单状已签收';
					Db::name('express_order')->where(array('id'=>$v['id']))->update(array('orderStatus'=>4)); 	//订单完成
					model('ExpressOrder')->profit($v,$v['user_id'],'分销');//订单完成分销
					model('ExpressOrder')->orderAddIntegral($v,$v['user_id'],'给用户奖励积分');
					model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '签收成功通知');//完成订单发送通知
				}
			}
			
			
			//推送计费信息
			if($eop['pushType'] == 2){
				$realWeight = $context["realWeight"];//实际重量
		  		$realVolume = $context["realVolume"];//实际体积
				
				$feeBlockList = $context["feeBlockList"];//费用明细
				$feeBlockList = @json_decode($feeBlockList,true);
				foreach($feeBlockList as $ka=>$va){
					if($va['name'] == '实收快递费'){
						$r= $va;
					}
				}
				
				$up['review_weight'] = $realWeight;
				$up['review_vloumn'] = $realVolume;
				
				$orderFee = $r['fee']*100;
				if($orderFee > $v['sumMoneyYuan_old'] && $v['diffStatus'] == 0){
					$v['diffMoneyYuan'] = $orderFee-$v['sumMoneyYuan_old'];//实际扣费之间的差价
					$logoUrl = model('ExpressOrder')->logoUrl($v['kuaidi']);
					$jia = ($v['diffMoneyYuan']*$logoUrl['firstPrice'])/100;
					$v['diffMoneyYuan'] = $v['diffMoneyYuan']+$jia;
					$v['diffMoneyYuan'] = (int)$v['diffMoneyYuan'];
					
					if($v['diffMoneyYuan']){
						$handle_info = '易达推送计费更新差价';
						$up['diffStatus'] = 1;
						$up['diffMoneyYuan'] = $v['diffMoneyYuan'];
						//更新订单
						Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
						//判断补差价
						model('Sms')->sendSmsTmplSend($v,$v['user_id'],$title = '补差价通知');
						model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '补差价通知');
					}
				}else{
					$handle_info = '易达推送计费更新重量';
					//更新订单重量体积
					Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
				}
			}
			//推送揽收信息
			if($eop['pushType'] == 3){
				
				$handle_info = '揽收执行订单状态2成功';
				$realOrderState = '揽件员：'.$context["courierName"].'-电话：'.$context["courierPhone"].'-取件码：'.$context["pickUpCode"];//快递员
		  		$up['realOrderState'] = $realOrderState;
				$up['orderStatus'] = 2;
				Db::name('express_order')->where(array('id'=>$v['id']))->update($up); 	
				model('WeixinTmpl')->getWeixinTmplSend($v,$v['user_id'],$title = '接单成功提醒');//更新状态
			}
		}
		
		
		//更新推送表
		$eopUp['status'] = 2;
		$eopUp['handle_time'] = time();
		$eopUp['handle_info'] = $handle_info;
		Db::name('express_order_push')->where(array('id'=>$eop['id']))->update($eopUp); 
		return true;
	}
	
	
	
	
	
	
 	public function handlePush(){
		$id = (int)input('id','','trim,htmlspecialchars');	
		$user_id = (int)input('user_id','','trim,htmlspecialchars');	
		if($id){
			$list = Db::name('express_order_push')->where(array('status'=>1,'id'=>$id))->order('id desc')->limit(0,100)->select(); 
		}elseif($user_id){
			$list = Db::name('express_order_push')->where(array('status'=>1,'user_id'=>$user_id,))->order('id desc')->limit(0,100)->select(); 
		}else{
			$list = Db::name('express_order_push')->where(array('status'=>1))->order('id desc')->limit(0,100)->select(); 
		}
		
		$i=0;
		foreach($list as $k=>$v){
			if($v['deliveryId']){
				$i++;
				$eo = Db::name('express_order')->where(array('deliveryId'=>$v['deliveryId']))->order('id desc')->find(); 
				$c .= $this->handlePushOrderUpdate($v,$eo);
			}
		}
		$msg .= '一共更新【'.$i.'】次';
		$msg .= $c.'';
		return json(array('c'=>0,'d'=>$d,'c'=>$c,'msg'=>$msg));
	}


	
	
	//分类筛选
	public function getPushData(){
	    $t = model('Setting')->getCompanyApiTypes();
        $push = Db::name('express_order_push')->where(array('status'=>2))->limit(0,10)->order('id desc')->select();
        $str = '';
		$i=0;
        foreach($push as $k=>$v){
			$i++;
			$u = $config['site']['host']."/admin/express/index/order_id/".$v['deliveryId'];
            $str.='<div class="n-'.$i.' n">'.$i.':'.$t[$v['type']].'接口运单号'.$v['deliveryId'].'已执行操作'.$v['handle_info'].'-执行时间：'.date('Y-m-d H:i:s ',$v['handle_time']).'<a href="'.$u.'">[查看]</a></div>'."\n\r";      
        }
        echo $str;die;
    }



	//小程序站点配置
 	public function getSetting(){
		$config = $this->config;
		$res['config'] = $config;
		$res['wxapp'] = $config['wxapp'];
		$res['site'] = $config['site'];
		$res['color'] = $config['other']['color'] ? $config['other']['color'] : '#2fbdaa';
		$res['url'] = $config['site']['host']."/wap/index/index?wxapp=1";
		$json_str = json_encode($res);
        exit($json_str);
	}


	
	//内容页面新增快捷导航
	public function getFastNavigationRule(){
		return jsonp(array('state'=>'101','info'=>'功能未开发'));
	}



	//H5绑定小程序openid
	public function Bind(){
		  $js_code = input('js_code','','trim,htmlspecialchars');
		  $uid = input('uid','','trim,htmlspecialchars');
		  $grant_type = input('grant_type','','trim,htmlspecialchars');


		  $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$this->config['wxapp']['appid']."&secret=".$this->config['wxapp']['appsecret']."&js_code=".$js_code."&grant_type=authorization_code";
		  $res = $this->httpRequest($url);
		  $result = json_decode($res,true);

		  if(!$uid){
			  return json(array('status'=>1,'msg'=>'无会员ID绑定失败'));
		  }
		  $users = Db::name('users')->where(array('user_id'=>$uid))->find();

		  if(empty($result['openid'])){
			 return json(array('status'=>1,'msg'=>'openid获取失败'));
		  }
		  //如果有unionid这里的开放平台可能不正确
		  if($this->config['weixin']['unionid'] && $result['unionid']){
			 $connect = Db::name('connect')->where(array('type'=>'weixin','unionid'=>$result['unionid']))->order(array('connect_id'=>'asc'))->find();
		  }else{
			 $connect = Db::name('connect')->where(array('type'=>'weixin','openid'=>$result['openid']))->order(array('connect_id'=>'asc'))->find();
		  }

		  if($connect){
			 if($res2 = Db::name('connect')->where(array('connect_id'=>$connect['connect_id']))->update(array('openid'=>$result['openid']))){
				return json(array('status'=>0,'msg'=>'绑定成功'));
			}else{
				return json(array('status'=>0,'msg'=>'绑定失败'));
			}
		 }else{
			$data['uid']  = $uid;
			$data['type']  = 'weixin';
			$data['openid']  = $result['openid'];
			$data['nickname']  = 'wxapp_bind_'.$uid;
			$data['headimgurl']  = $users['face'];
			if($res3 = Db::name('connect')->insert($data)){
				return json(array('status'=>0,'msg'=>'绑定成并注册成功'));
			}else{
				return json(array('status'=>0,'msg'=>'绑定失败2'));
			}
		 }
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




	//商城分类
	public function shopcate($parent_id=0){
        $datas = model('ShopCate')->fetchAll();
        $str = '';
        foreach($datas as $var){
            if($var['parent_id'] == 0 && $var['cate_id'] == $parent_id){
                foreach($datas as $var2){
                    if($var2['parent_id'] == $var['cate_id']){
                        $str.='<option value="'.$var2['cate_id'].'">'.$var2['cate_name'].'</option>'."\n\r";
                    }
                }
            }
        }
        echo $str;die;
    }




	//getInonfont
    public function getInonfont($url = '',$qq = '0'){
	    $config = Setting::config();
		if(!($url = input('url',0,'htmlspecialchars'))){
			return json(array('code'=>0,'msg'=>'非法操作'));
        }
		if(!($qq = input('qq',0,'htmlspecialchars'))){
			return json(array('code'=>0,'msg'=>'非法操作'));
        }
		if($config['config']['iconfont']){
			return json(array('code'=>1,'msg'=>'请复制这段连接：'.$config['config']['iconfont']));
		}else{
			return json(array('code'=>0,'msg'=>'站点没配置'));
		}
	}


	
	

	//获取验注册证码
	public function verify_register(){
		return $this->verify_build('register');
    }
	//获取登录验证码
	public function verify_login(){
		return $this->verify_build('login');
    }

	//获取短信API
	public function sendsms(){
		session('scode', null);
		if(!($verify = input('verify',0,'htmlspecialchars'))) {
			return json(array('code'=>0,'msg'=>'请输入正确的图片验证码'));
        }
		if(!$this->get_verify_check($verify,'register')){
			return json(array('code'=>0,'msg'=>'图片验证码错误'));
		}


		if(!($mobile = trim(input('mobile',0,'htmlspecialchars')))) {
			return json(array('code'=>0,'msg'=>'请输入正确的手机号码'));
        }
        if(!isMobile($mobile)){
			return json(array('code'=>0,'msg'=>'手机号码格式不正确'));
        }
        if($user = model('Users')->getUserByMobile($mobile)){
			return json(array('code'=>0,'msg'=>'手机号码已经存在'));
        }


		session('mobile', $mobile);
		$randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);


		if(model('Sms')->sms_yzm($mobile, $randstring)){
			return json(array('code'=>1,'msg'=>'短信发送成功'));
		}else{
			return json(array('code'=>0,'msg'=>'短信发送失败'));
		}
	}

	//获取短信API2
	public function sendsms2(){
		session('scode', null);
		if(!($mobile = trim(input('mobile',0,'htmlspecialchars')))){
			return json(array('code'=>0,'msg'=>'请输入正确的手机号码'));
        }
        if(!isMobile($mobile)){
			return json(array('code'=>0,'msg'=>'手机号码格式不正确'));
        }
        if($user = model('Users')->getUserByMobile($mobile)){
			return json(array('code'=>0,'msg'=>'手机号码已经存在'));
        }
		session('mobile', $mobile);
		$randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);

		if(model('Sms')->sms_yzm($mobile, $randstring)){
			return json(array('code'=>1,'msg'=>'恭喜短信发送成功'));
		}else{
			return json(array('code'=>0,'msg'=>'抱歉短信发送失败'));
		}
	}

	//获取短信API3
	public function sendsms3(){
		session('scode', null);
		if(!($mobile = trim(input('mobile',0,'htmlspecialchars')))){
			return json(array('code'=>0,'msg'=>'请输入正确的手机号码'));
        }
        if(!isMobile($mobile)){
			return json(array('code'=>0,'msg'=>'手机号码格式不正确'));
        }
		session('mobile', $mobile);
		$randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);
		if(model('Sms')->sms_yzm($mobile, $randstring)){
			return json(array('code'=>1,'msg'=>'恭喜短信发送成功'));
		}else{
			return json(array('code'=>0,'msg'=>'抱歉短信发送失败'));
		}
	}


	//获取短信API4后台登录
	public function adminLoginSms(){
		session('scode', null);
		if(!($username = trim(input('username',0,'htmlspecialchars')))){
			return json(array('code'=>0,'msg'=>'请输入管理员账户'));
        }
		if(!$detail = Db::name('admin')->where(array('username'=>$username))->find()){
			return json(array('code'=>0,'msg'=>'非法操作'));
        }
		if(empty($detail['mobile'])){
			return json(array('code'=>0,'msg'=>'该管理员未绑定手机号'));
        }
		if($detail['is_username_lock'] == 1){
			return json(array('code'=>0,'msg'=>'该管理员已经被冻结'));
        }
        if(!isMobile($detail['mobile'])){
			return json(array('code'=>0,'msg'=>'管理员手机号码格式不正确'));
        }
		session('mobile',$detail['mobile']);
		$randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);
		if(model('Sms')->sms_yzm($detail['mobile'],$randstring)){
			return json(array('code'=>1,'msg'=>'短信发送成功'));
		}else{
			return json(array('code'=>0,'msg'=>'短信发送失败'));
		}
	}




	
	//getConfig
    public function getConfig(){
	    $config = Setting::config();
		return json(array('code'=>1,'data'=>$config));
	}


	//上传微信视频
	public function fileVodie(){

		$config = Setting::config();

		$key = input('key', '','htmlspecialchars');
		$token = input('token', '','htmlspecialchars');
		$name = input('name', '','htmlspecialchars');


		$vname = $_FILES['file']['type'];
		//获取文件的名字
		$key = $_FILES['file']['name'];
		$filePath=$_FILES['file']['tmp_name'];

		//获取token值
		$upinfo = Db::name('uploadset')->where(array('type'=>'Qiniu'))->find();
		$conf = json_decode($upinfo['para'],true);
		$bucket = $conf['bucket'];
		$domain= $conf['domain'];
		//初始化签权对象
		$auth = new Auth($conf['accessKey'],$conf['secrectKey']);

		//生成上传Token
		$token = $auth->uploadToken($bucket);
		$uploadMgr = new UploadManager();
		//调用 UploadManager 的 putFile 方法进行文件的上传。
		list($ret,$err) = $uploadMgr->putFile($token, $key, $filePath);
		if($err !== null){
			return json(array('code'=>1,'message'=>'上传失败'.$err));
        }

		//获取视频的时长
		//第一步先获取到到的是关于视频所有信息的json字符串
		$shichang = file_get_contents('http://'.$domain.'/'.$key.'?avinfo');
		// 第二部转化为对象
		$shi =json_decode($shichang);

		// 第三部从中取出视频的时长
		$chang = $shi->format->duration;
		//获取封面
		$vpic = 'http://'.$domain.'/'.$key.'?vframe/jpg/offset/1';
		$path ='http://'.$domain.'/'.$ret['key'];


		$data['code'] = 0;
		$data['upType'] = 5;
		$data['name'] = $vname;
		$data['type'] = 'video/mp4';
		$data['size'] = $shi->format->size;
		$data['duration'] = $chang;
		$data['key'] = 'file';
		$data['width'] = $shi->streams[0]->width;
		$data['height'] = $shi->streams[0]->height;
		$data['extension'] = 'mp4';
		$data['savepath'] = $path;
		$data['savename'] = $vname;

		$data['cover']=$vpic;
		$data['path'] = $path;
		$data['url'] = $path;
		$data['preview'] = $path;
		$data['id'] = Db::name('thread_post_pic')->insertGetId($data);

		return json($data);
	}



	//生成二维码
    public function qrcode(){
        $data = input('data','','trim,htmlspecialchars');
		$token = 'share_qrcode_' .rand_string(6,0);
		$file = ToQrCode($token,$data,8,'');
		$file = config_weixin_img($file);
		$file = $file;
		header('Content-type:image/png');
		echo file_get_contents($file);
    }



	
	//json输出系谱图
	public function family($user_id){
		$user_id = (int) input('user_id');
        if(!$user_id){
            $user_id = '1';
        }
		
		$data = $this->getChildFamily($user_id,1);
		//$data =second_array_unique_bykey($data,'user_id');//去掉重复
		return json($data);
		

		/*
		$data = array();
		$data = Db::name('users')->where(array('parent_id'=>$user_id))->select();
		foreach($data as $k => $v){
			$data[$k] = $this->getChildDetail($v['user_id']);
			$data2 = Db::name('users')->where(array('parent_id'=>$v['user_id']))->select();
			$data[$k]['children'] = $data2;
			foreach($data2 as $k2 => $v2){
				$data2[$k2] = $this->getChildDetail($v2['user_id']);
				$data3 = Db::name('users')->where(array('parent_id'=>$v2['user_id']))->select();
				$data2[$k2]['children'] = $data3;
				foreach($data3 as $k3 => $v3){
					$data4 = Db::name('users')->where(array('parent_id'=>$v3['user_id']))->select();
					foreach($data4 as $k4 => $v4){
						$data5 = Db::name('users')->where(array('parent_id'=>$v4['user_id']))->select();
						$data4[$k4]['children'] = $data5;
					}
					$data3[$k3]['children'] = $data4;
				}
			}
		}
		$data[]=$data;
		return json($data);
	*/
		
	}
	
	
	//递归
	public function getChildFamily($user_id){
		static $arr=array();  
		$data=Db::name('users')->where(array('parent_id'=>$user_id))->select();
		foreach($data as $key => $value){
			$data[$key]= $this->getChildDetail($value['user_id']);
			$data[$key]['children'] = $data;
			$arr[] = $data[$key];
			$this->getChildFamily($value['user_id']);//循环
		}
		return $arr;
	}
	
	
	
	public function getChildDetail($user_id){
		
		$v = Db::name('users')->where(array('user_id'=>$user_id))->find();
		
		$data['nickname'] = $v['nickname'];
		$data['id'] = $v['user_id'];
		$data['user_id'] = $v['user_id'];
		$data['parent_id'] = $v['parent_id'];
		$data['mobile'] = $v['mobile'];
		$rank_name = Db::name('user_rank')->where('rank_id',$v['rank_id'])->value('rank_name');
		$data['rank_name'] = $rank_name ? $rank_name : '无等级';
		$data['yao_name'] = Db::name('users')->where('user_id',$v['parent_id'])->value('nickname');
		$data['yao_num'] = (int)model('UserProfitLogs')->getUserFuidRankCount($v['user_id'],0);
		$getUserFuidCount = model('UserProfitLogs')->getUserFuidCount($v['user_id']);
		$data['tuan_num'] = $getUserFuidCount;
		$getUserLevelPriceCount = model('UserProfitLogs')->getUserLevelPriceCount($v['user_id'],1);
		$data['user_price'] = $getUserLevelPriceCount['price2'];
		$data['tuan_price'] = $getUserLevelPriceCount['price3'];
		$data['time'] = date("Y-m-d H:i:s",$v['reg_time']);
		return $data;
	}
	
	
}

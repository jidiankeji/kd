<?php

namespace app\app\controller;
use think\Db;
use think\Cache;

use app\common\model\Setting;

class Pay extends Base{
	
	
	//SavePayLog
	
	//订单回调
	public function SavePayLog(){
		
		$testxml = file_get_contents("php://input");
		$jsonxml = json_encode(simplexml_load_string($testxml,'SimpleXMLElement',LIBXML_NOCDATA));
		$result = json_decode($jsonxml, true);
		
		
		file_put_contents(APP_PATH.'/app/controller/result.txt',var_export($result,true));
		
		//p($result);die;
		if($result){
			 //如果成功返回了
			 if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
		
		
				 $trade = explode('-',$result['out_trade_no']);
				 $log_id = $trade[0];
				 
				
				 
				 $logs = Db::name('payment_logs')->where(array('log_id'=>$log_id))->find();
				 
				 file_put_contents(APP_PATH.'/app/controller/logs.txt',var_export($logs,true));
				  
				  
				 if($logs['is_paid'] == 0){
					model('Payment')->logsPaid($log_id,$result['out_trade_no'],$result['transaction_id']);
				 }
				 return true;
		  	}
		}
		return true;
	}
	
	
	//H5绑定小程序支付回调
	public function yes2(){
		
		$log_id =(int) input('log_id');
		
		$return_order_id = input('return_order_id','','trim,htmlspecialchars');
		$return_trade_no = input('return_trade_no','','trim,htmlspecialchars');
		
		model('Payment')->logsPaid($log_id,$return_order_id,$return_trade_no);//通过订单ID回调，其他错误后期在写
		
		return json(array('status'=>0,'msg'=>'支付成功')); 
	}
	
	
	
	//封装小程序支付
	public function payh5(){
		 
		$log_id = input('log_id');
		$Paymentlogs = Db::name('payment_logs')->find($log_id);
		$connect = Db::name('connect')->where(array('uid'=>$Paymentlogs['user_id'],'type'=>'weixin'))->find();
		 
        $Payment = model('Payment')->getPayment('wxapp');
		$config = Setting::config();
		
		$out_trade_no = $Paymentlogs['log_id'].'-'.time();
		
        $weixinpay = new \Wxpay($config['wxapp']['appid'],$connect['openid'],$Payment['mchid'],$Payment['appkey'],$out_trade_no,'订单付款',$Paymentlogs['need_pay']);//支付接口
        $return = $weixinpay->pay();
		
		if($return['package'] == 'prepay_id='){
			 echo ('预支付失败--'.$return['rest']['return_msg']);
			 die;
		}
		
		
		
		$return['weixin_param']['timeStamp'] = $return['timeStamp'];
		$return['weixin_param']['nonceStr'] =$return['nonceStr'];
		$return['weixin_param']['paySign'] = $return['paySign'];
		$return['apk'] = $return['package'];
		$return['orderid'] = $log_id;
		$return['redirctUrl'] = $out_trade_no;
		
		$return['need_pay'] = $Paymentlogs['need_pay'];
		$return['mchid'] = $Payment['mchid'];
		$return['appkey'] = $Payment['appkey'];
		$return['openid'] = $connect['openid'];
		$return['appid'] = $config['wxapp']['appid'];
		$return['out_trade_no'] = $out_trade_no;
		
        echo json_encode($return);
    }
	
	
	//支付旧版备用
    public function Pay(){
		 $config = Setting::config();
         $res = model('Payment')->getPayment('weixin');
         $openid= input('openid');
		 
         $out_trade_no = $res['mchid']. time();
		 
         $total_fee = input('money');
         if(empty($total_fee)){
            $body = "订单付款";
            $total_fee = floatval(99*100);
         }else{
             $body = "订单付款";
             $total_fee = floatval($total_fee*100);
         }
		 $types = input('types'); 
		 $user_id = input('user_id');  
	     $order_id = input('order_id');  
		 $arr = array(
			'type' => $types, 
			'user_id' => $user_id, 
			'order_id' => $order_id, 
			'order_ids' =>'', 
			'code' => 'wxapp', 
			'need_pay' => $total_fee, 
			'create_time' => NOW_TIME, 
			'create_ip' => request()->ip(), 
			'is_paid' => 0
		);
        $log_id = Db::name('payment_logs')->insertGetId($arr);
		
        $weixinpay = new \Wxpay($config['wxapp']['appid'],$openid,$res['mchid'],$res['mchid'],$out_trade_no,$body,$total_fee);//支付接口
        $return = $weixinpay->pay();
		$return['log_id'] = $log_id;//支付ID
        echo json_encode($return);
    }
	

  
	
}

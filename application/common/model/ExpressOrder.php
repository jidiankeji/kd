<?php

namespace app\common\model;
use think\Db;
use think\Cache;

use app\common\model\Setting;

class ExpressOrder extends Base{


    protected $pk = 'id';
    protected $tableName = 'express_order';
    protected $token = 'express_order';
	
	public function getError(){
        return $this->error;
    }
	
    //获取优惠券核销码
	public function getCode(){       
        $i=0;
        while(true){
            $i++;
            $code = rand_string(8,1);
            $data = Db::name('coupon_download')->where(array('code'=>$code))->find();
            if(empty($data)) return $code;
            if($i > 10) return $code;
        }
    }
	
	//赠送优惠券
	public function giveCoupon($v,$user_id,$title){
		//新人有礼关注公众号送
		//满额返礼
		$flag = 0;
		if($v['sumMoneyYuan'] >= 3000){
			model('ExpressOrder')->sendCouponDownload($user_id,'满额返礼');//送优惠券满额返礼
		}else{
			model('ExpressOrder')->sendCouponDownload($user_id,'寄件返礼');//送优惠券寄件返礼
		}
		return true;
	}
	
	
	
	
 	public function sendCouponDownload($user_id,$title='新人有礼',$coupon_id=0,$need_pay=0){
		
		//购买的优惠券订单
		if($coupon_id){
			$c = Db::name('coupon')->where(array('audit'=>1,'expire_date'=>array('EGT',TODAY),'coupon_id'=>$coupon_id,'closed' => 0))->find();
			$cd = 0;
			$status = 1;
			$money = $need_pay;
		}else{
			$cd = (int)Db::name('coupon_download')->where(array('title'=>$title,'user_id'=>$user_id))->count();
			$c = Db::name('coupon')->where(array('audit'=>1,'expire_date'=>array('EGT',TODAY),'title'=>$title,'closed' => 0))->find();
			$money = 0;
			$status = 0;
		}
		//p($c);
		//p($cd);die;
		
		if($c && !$cd && $c['num']){
			$data = array(
				'user_id' => $user_id,
				'type' => $c['type'],
				'shop_id' => 0,
				'title' => $c['title'],
				'coupon_id' => $c['coupon_id'],
				'create_time' => time(),
				'mobile' => '',
				'status' => 0,
				'money' => 0,
				'expire_date' => $c['expire_date'],
				'full_price' => $c['full_price'],
				'limit_num' => $c['limit_num'],
				'reduce_price' => $c['reduce_price'],
				'create_ip' => request()->ip(),
				'code' => $this->getCode(),
			);
			$download_id = Db::name('coupon_download')->insertGetId($data);
			$num = $c['num']-1;
			if($num <=0){
				$num == 0;
			}
			Db::name('coupon')->where(array('coupon_id'=>$c['coupon_id']))->update(array('num'=>$num));
			model('WeixinTmpl')->getWeixinTmplSend($c,$user_id,$title = '优惠券发放通知');
			return true;
		}
		//更新状态
		return false;
	}
	
	
	//给用户奖励积分
	public function orderAddIntegral($v,$user_id,$title){
		$config = model('Setting')->fetchAll2();
		//快递消费奖励积分
		$np = (int)($v['sumMoneyYuan']/100);
		$exp = (int)$config['integral']['exp'];
		$integral = $np * $exp;
		if($integral){
			model('Users')->addIntegral($v['user_id'],$integral,'寄快递'.$id.'获取积分',4);
		}
		return true;
	}
	
	
	//分销
	public function profit($v,$user_id,$title){
		$id = $v['id'];
		$config = model('Setting')->fetchAll2();
		$moshi = (int)$config['profit']['moshi'];
		$moshi1 = (int)$config['profit']['moshi1'];
		$profit_guding_rate1 = (int)($config['profit']['profit_guding_rate1']*100);
		$profit_guding_rate2 = (int)($config['profit']['profit_guding_rate2']*100);
		$profit_guding_rate3 = (int)($config['profit']['profit_guding_rate3']*100);
		if($moshi == 1){
			$p = $v['sumMoneyYuan'];
		}else{
			//分成加价金额
			$p = $v['sumMoneyYuan_jia'];
		}
		$money1 = $money2 = $money3 = 0;
		
		
		return $money1+$money2+$money3;
	}
	
	
	
	
	//获取快递公司的图片价格
	public function logoUrl($kuaidi = '京东',$uid = 0){
		
		//易达接口下单保存数据
		if($kuaidi == '京东'){
			$kuaidi = '京东';
		}elseif($kuaidi == '圆通'){
			$kuaidi = '圆通';
		}elseif($kuaidi == '申通'){
			$kuaidi = '申通';
		}elseif($kuaidi == '德邦'){
			$kuaidi = '德邦';
		}elseif($kuaidi == '极兔'){
			$kuaidi = '极兔';
		}elseif($kuaidi == '顺丰'){
			$kuaidi = '顺丰';
		}elseif($kuaidi == '中通'){
			$kuaidi = '中通';
		}elseif($kuaidi == '韵达'){
			$kuaidi = '韵达';
		}elseif(strstr($kuaidi,'京东') == true){
			$kuaidi = '京东';
		}elseif(strstr($kuaidi,'圆通') == true){
			$kuaidi = '圆通';
		}elseif(strstr($kuaidi,'申通') == true){
			$kuaidi = '申通';
		}elseif(strstr($kuaidi,'德邦') == true){
			$kuaidi = '德邦';
		}elseif(strstr($kuaidi,'极兔') == true){
			$kuaidi = '极兔';
		}elseif(strstr($kuaidi,'顺丰') == true){
			$kuaidi = '顺丰';
		}elseif(strstr($kuaidi,'中通') == true){
			$kuaidi = '中通';
		}elseif(strstr($kuaidi,'韵达') == true){
			$kuaidi = '韵达';
		}
			
		$u = Db::name('users')->where(array('user_id'=>$uid))->find();
		
		$cate = Db::name('express_cate')->where(array('cate_name'=>$kuaidi))->find();
		if($cate){
			$deliveryType = $cate['pinyin'];
			$desc = $cate['info'] ? $cate['info'] : '该快递方式暂无说明';
			$expressId = $cate['cate_id'];
			$logoUrl = config_weixin_img($cate['photo']);
			$firstPrice = $cate['firstPrice'];
			$addPrice = $cate['addPrice'];
			$limitFirstPrice = $cate['limitFirstPrice'];
			$limitAddPrice = $cate['limitAddPrice'];
		}else{
			if($kuaidi == '京东'){
				$deliveryType = 'JD';
				$desc = '京东-JD';
				$expressId = 6;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/jd.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi == '圆通'){
				$deliveryType = 'YTO';
				$desc = '圆通-YTO';
				$expressId = 3;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/yt.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi == '申通'){
				$deliveryType = 'STO-INT';
				$desc = '申通-STO-INT';
				$expressId = 4;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/zt.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi== '德邦'){
				$deliveryType = 'DOP';
				$desc = '德邦-DB';
				$expressId = 2;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/db.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi== '极兔'){
				$deliveryType = 'JT';
				$desc = '极兔-JT';
				$expressId = 5;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/jt.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi== '顺丰'){
				$deliveryType = 'SF';
				$desc = '顺丰-DOP';
				$expressId = 6;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/sf.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi== '中通'){
				$deliveryType = 'ZTO';
				$desc = '中通-ZTO';
				$expressId = 7;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/zt.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}elseif($kuaidi== '韵达'){
				$deliveryType = 'YUND';
				$desc = '韵达-YUND';
				$expressId = 8;
				$logoUrl = $this->config['site']['host'].'/static/default/wap/img/yd.png';
				$firstPrice = $this->config['wxapp']['firstPrice'];
				$addPrice = $this->config['wxapp']['addPrice'];
				$limitFirstPrice = $this->config['wxapp']['limitFirstPrice'];
				$limitAddPrice = $this->config['wxapp']['limitAddPrice'];
			}
		}
		return array(
			'deliveryType'=>$deliveryType,
			'expressName'=>$kuaidi,
			'logoUrl'=>$logoUrl,
			'desc'=>$desc,
			'firstPrice'=>$firstPrice,
			'addPrice'=>$addPrice,
			'limitFirstPrice'=>$limitFirstPrice,
			'limitAddPrice'=>$limitAddPrice,
			'expressId'=>$expressId
		);
	}
}




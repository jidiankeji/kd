<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Cache;


class Setting extends Base{
 	protected $pk = 'k';
    protected $tableName = 'setting';
    protected $token = 'jin_setting';
    protected $settings = null;
	
	
	
	public function getError(){
        return $this->error;
    }
	
	public function getCompanyApiTypes(){
        return array(
			'1' => '易达接口',
			'2' => '云洋API',
		);
    }
	
	public function getorderStatus(){
        return array(
            '0' => '未付款',
            '1' => '已付款',
			'2' => '已接单',
			'3' => '已取件',
			'4' => '已完成',
			'5' => '已取消已退款',
			'9' => '订单异常',
			'-1' => '已取消',
        );
    }
	public function getdiffStatus(){
        return array(
            '0' => '暂无差价',
            '1' => '未补差价',
			'2' => '已完成补差价',
        );
    }
	
	public function getorderRightsStatus(){
        return array(
            '0' => '未申请退款',
            '1' => '退款审核中',
			'2' => '退款完成',
        );
    }
	
	
    public function fetchAll2(){
        $cache = cache(array('type' => 'File', 'expire' => $this->cacheTime));
        if(!($data = $cache->get($this->token))) {
            $result = $this->select();
            foreach ($result as $row) {
                $row['v'] = @unserialize($row['v']);
                $data[$row[$this->pk]] = $row['v'];
            }
            $cache->set($this->token, $data);
        }
        $this->settings = $data;
        return $this->settings;
    }
	
	
	//静态方法
	public static function config(){
		$config = model('Setting')->fetchAll2();
        return $config;
    }
	
	
	
	
	public function getExpressList($data){
		
		$config = model('Setting')->fetchAll2();
		
		
		if($data['recipients_phone']!='undefined' && $data['recipients_phone']){
			$recipients_mobile = $data['recipients_phone'];
		}elseif($data['recipients_mobile']!='undefined' && $data['recipients_mobile']){
			$recipients_mobile = $data['recipients_mobile'];
		}else{
			$recipients_mobile = '17194348715';
		}
		if($data['sender_phone']!='undefined' && $data['sender_phone']){
			$sender_mobile = $data['sender_phone'];
		}elseif($data['sender_mobile']!='undefined' && $data['sender_mobile']){
			$sender_mobile = $data['sender_mobile'];
		}else{
			$sender_mobile = '17194348715';
		}
		
		$cate_id = (int)$data['cate_id'];
		$expressList = $expressList2 = array();
		
		
		if($config['wxapp']['yy_appid'] && $config['wxapp']['yy_secretKey']){
			$content['channelTag']="智能";
			$content['sender']=$data['sender_name'];
			$content['senderMobile']= $sender_mobile;
			$content['senderProvince']= $data['sender_province'];
			$content['senderCity']= $data['sender_city'];
			$content['senderCounty']= $data['sender_area'];
			$content['senderTown']=$data['sender_area'];
			$content['senderLocation']= $data['sender_address'] ? $data['sender_address'] : $data['sender_province'].$data['sender_city'].$data['sender_area'];
			$content['senderAddress']= $data['sender_address'] ? $data['sender_address'] : $data['sender_province'].$data['sender_city'].$data['sender_area'];
			$content['receiver']=$data['recipients_name'];
			$content['receiverMobile']=$recipients_mobile;
			$content['receiveProvince']= $data['recipients_province'];
			$content['receiveCity']= $data['recipients_city'];
			$content['receiveCounty']= $data['recipients_area'];
			$content['receiveTown']= $data['recipients_province'].$data['recipients_city'].$data['recipients_area'];;
			$content['receiveLocation']= $data['recipients_address'] ? $data['recipients_address'] : $data['recipients_province'].$data['recipients_city'].$data['recipients_area'];
			$content['receiveAddress']=$data['recipients_address'] ? $data['recipients_address'] : $data['recipients_province'].$data['recipients_city'].$data['recipients_area'];
			$content['weight']= $data['totalWeight'];
			$content['packageCount']= 1;
			$content['insured']= 0;//保价金额
			$content['vloumLong']= $data['long'] ? $data['long'] : 1;
			$content['vloumWidth']= $data['width'] ? $data['width'] : 1;
			$content['vloumHeight']=$data['height'] ? $data['height'] : 1;
			$content['autoMatchLevel']= 1;
			if($data['type'] == 1){
				$subType = 'wds';
				$billType  = 0;
			}elseif($data['type'] == 2){
				$subType = 'dw';
				$billType  = 0;
			}elseif($data['type'] == 4){
				$subType = 'wds';
				$billType  = 2;
			}else{
				$subType = 'wds';
				$billType  = 0;
			}
			$content['billType']=$billType;
			$content['subType']= $subType;
			
			//p($content);
			$performance = $this->performance($content,$Method ='CHECK_CHANNEL_INTELLECT');
			//p($performance);die;
			
			if($performance['code'] == 0){
				$this->error = '云洋预下单错误请检查参数'.$performance['message'];
				return false;
			}else{
				foreach($performance['result'] as $k=>$v){
					$expressList[$k]['freightInsured'] = $v['freightInsured'];//保价费
					$c = Db::name('express_cate')->where(array('cate_name'=>$v['tagType']))->find();
					$expressList[$k]['c_type'] =$c['type'];
					$expressList[$k]['lanshou'] =$c['lanshou'];
					$expressList[$k]['img'] =config_weixin_img($c['photo']);
					$expressList[$k]['nickname'] = $v['tagType'];
					$expressList[$k]['name'] = $v['tagType'];
					$expressList[$k]['freight'] = $v['freight'];
					$expressList[$k]['channelId'] = $v['channelId'];
					$expressList[$k]['channel'] = $v['channelId'];
					$expressList[$k]['transportType'] = $v['channelId'];
					$expressList[$k]['type'] = 2;
					$getYunyangPrice = model('Setting')->getYunyangPrice($data['uid'],$firsts=array(),$v,$data['totalWeight'],$c,0,0);//没有加价
					$expressList[$k]['discount'] =$getYunyangPrice['discount'];//普通用户运费
					$expressList[$k]['vip_discount'] = $getYunyangPrice['vip_discount'];
					$expressList[$k]['original_cost'] = $getYunyangPrice['original_cost'];
					if($v['tagType'] == '顺丰'){
						$is_yuyue = 0;
					}else{
						$is_yuyue = 1;
					}
					$expressList[$k]['is_yuyue'] = $is_yuyue;
					$expressList[$k]['is_baojia'] = 1;
					
				}
				//p($expressList);
				$expressList =array_values($expressList);
			}
			
		}
		
		
		
		
		if($config['wxapp']['yd_name'] && $config['wxapp']['yd_secret']){
			if($data['type'] == 1){
				$customerType = 'kd';
			}elseif($data['type'] == 2){
				$customerType = 'poizon';
			}elseif($data['type'] == 4){
				$customerType = 'kd';
			}else{
				$customerType = 'kd';
			}
			
			
			$requestParams['senderAddress']=$data['sender_address']!='undefined'&& $data['sender_address']!='' ? $data['sender_address'] : $data['sender_province'].$data['sender_city'].$data['sender_area'];
			$requestParams['goods']='物品';
			$requestParams['thirdNo']='';
			$requestParams['senderName']=$data['sender_name']!='undefined' ? $data['sender_name'] : '李强';
			$requestParams['receiveName']=$data['recipients_name']!='undefined' ? $data['recipients_name'] : '李强';
			$requestParams['unitPrice']=0;//申通情况必填
			
			
			$isMobile = isMobile($recipients_mobile);
			if(!$isMobile){
				$requestParams['receiveTel']=$recipients_mobile;
			}elseif($customerType == 'poizon'){
				$requestParams['receiveTel']=$recipients_mobile;
			}else{
				$requestParams['receiveMobile']=$recipients_mobile;
			}
			
			$requestParams['receiveDistrict']=$data['recipients_area'];
			$requestParams['receiveAddress']=$data['recipients_address']!='undefined'&&$data['recipients_address']!=''?$data['recipients_address']:$data['recipients_province'].$data['recipients_city'].$data['recipients_area'];
			$requestParams['senderDistrict']=$data['sender_area'];//寄件区县
			$requestParams['deliveryType']='';
			
			
			$isMobile1 = isMobile($sender_mobile);
			if(!$isMobile1){
				$requestParams['senderTel']=$sender_mobile;
			}else{
				$requestParams['senderMobile']=$sender_mobile;
			}
			
			
			$requestParams['weight']= $data['totalWeight'];//重量
			$requestParams['customerType']=$customerType;
			$requestParams['senderProvince']=$data['sender_province'];
			$requestParams['receiveProvince']=$data['recipients_province'];
			$requestParams['senderCity']=$data['sender_city'];//收件城市
			$requestParams['receiveCity']=$data['recipients_city'];
			$requestParams['qty']=1;//申通情况必填 数量
			$requestParams['vloumLong']=$data['long'] ? $data['long'] :1;
			$requestParams['vloumHeight']=$data['height'] ? $data['height'] : 1;
			$requestParams['vloumWidth']= $data['width'] ? $data['width'] : 1;
			$requestParams['packageCount']='1';
			$requestParams['receiveProvinceCode']='';
			$requestParams['senderProvinceCode']='';
			
			//p($requestParams);die;
			
			$execute = model('Setting')->execute($requestParams,$Method='SMART_PRE_ORDER');
			
			//p($execute);die;
			
			if($execute['code'] != 200){
				$this->error = '易达预下单错误请检查参数'.$execute['msg'];
				return false;
			}else{
				
				$i = 0;
				foreach($execute['data'] as $key=>$val){
					if(is_array($val) && !empty($val)){
						foreach($val as $k=>$v){
							$first = array();
							$prices = $v['price'];
							$prices = @explode(";",$prices);
							foreach($prices as $vs){
								if($vs){
									$first[] = $vs;
								}
							}
							$firsts = array();
							foreach($first as $kt=>$vt){
								$j = @explode(",",$vt);
								$j1 = @explode("-",$j[0]);
								$add = $j1[0];
								$end = str_ireplace('公斤','',$j1[1]);
								$j2 = @explode("续",$j[1]);
								$first = @str_ireplace("价格",'',$j2[0]);
								$start = $j2[1];
								$firsts[$kt]['add'] = $add;
								$firsts[$kt]['end'] = $end;
								$firsts[$kt]['first'] = $first;
								$firsts[$kt]['start'] = $start;
							}
							$i++;
							//p($v);die;
							$expressList2[$i]['freightInsured'] = $v['preBjFee'];//保价费
							$c = Db::name('express_cate')->where(array('pinyin'=>$v['deliveryType']))->find();
							$expressList2[$i]['c_type'] =$c['type'];
							$expressList2[$i]['lanshou'] =$c['lanshou'];
							$expressList2[$i]['img'] =config_weixin_img($c['photo']);
							$expressList2[$i]['nickname'] = cut_msubstr($v['channelName'],0,2,true);
							$expressList2[$i]['name'] = cut_msubstr($v['channelName'],0,2,true);
							$expressList2[$i]['channelId'] = $v['channelId'];
							$expressList2[$i]['isBest'] = $v['isBest'];
							$expressList2[$i]['preOrderFee'] = $v['preOrderFee'];
							$expressList2[$i]['channel'] = $v['channelId'];
							$expressList2[$i]['transportType'] = $v['channelId'];
							$getYidaPrice = model('Setting')->getYidaPrice($data['uid'],$firsts,$v,$data['totalWeight'],$c,0,$v['preBjFee']);
							$expressList2[$i]['discount'] = $getYidaPrice['discount'];
							$expressList2[$i]['vip_discount'] = $getYidaPrice['vip_discount'];
							$expressList2[$i]['original_cost'] = $getYidaPrice['original_cost'];
							$expressList2[$i]['type'] = 1;
							if($v['tagType'] == 'SF'){
								$is_yuyue = 0;
							}else{
								$is_yuyue = 1;
							}
							$expressList2[$i]['is_yuyue'] = $is_yuyue;
							$expressList2[$i]['is_baojia'] = 1;
							
						}
					}
				}
				
				
				$expressList2 =array_values($expressList2);
			}
		}
		
		//p($expressList);
		//p($expressList2);
		
		
		if($expressList&&$expressList2){
			$e = @array_merge($expressList,$expressList2);	
		}elseif($expressList&&!$expressList2){
			$e = $expressList;	
		}elseif(!$expressList&&$expressList2){
			$e = $expressList2;	
		}
		foreach($e as $k3=>$v3){
			
		}
		//p($e);
		return array_values($e);
	}
	
	
	
	//【获取运费】云洋检测渠道接口
	public function choosecom($data){
		//全局配置
		$config = model('Setting')->fetchAll2();
		$u = Db::name('users')->where(array('user_id'=>$data['uid']))->find();
		if(!$u){
			$this->error = '您的会员信息不存在';
			return false;
		}
		
		$is_add_order = (int)$config['wxapp']['is_add_order'];
		$is_add_order_money = (int)($config['wxapp']['is_add_order_money']*100);
		$is_add_order_weight = (int)$config['wxapp']['is_add_order_weight'];
		
		if($is_add_order_weight >= 50){
			$weight = 50;
		}elseif($is_add_order_weight <= 0){
			$weight = 50;
		}else{
			$weight = $is_add_order_weight;
		}
		
		
		
		$sender_mobile = $data['sender_phone'] ? $data['sender_phone'] : $data['sender_mobile'];
		//查询易达黑名单
		$remark = Db::name('user_closed')->where(array('phone'=>$sender_mobile))->value('remark');
		if(!$remark){
			$recipients_mobile = $data['recipients_phone'] ? $data['recipients_phone'] : $data['recipients_mobile'];
			$remark = Db::name('user_closed')->where(array('phone'=>$data['recipients_mobile']))->value('remark');
		}
		if($remark){
			$this->error = '您被关进小黑屋了暂时无法下单【'.$remark.'】';
			return false;
		}
		
		
		
		//查询云洋黑名单
		$contents['phone']= $data['sender_mobile'];//发件人手机
		if($data['sender_mobile'] && $config['wxapp']['yy_appid'] && $config['wxapp']['yy_secretKey']){
			$performance = $this->performance($contents,$Method ='QUERY_BLACK');
			//p(1);die;
			if($performance['code'] == 0){
				//添加云洋黑名单
				$insert['name'] = $performance['result']['name'];
				$insert['type'] = 3;
				$insert['phone'] = $performance['result']['phone'];
				$insert['remark'] = $performance['result']['reason'];
				$insert['createTime'] = $v['createTime'];
				$insert['create_time'] = time();
				Db::name('user_closed')->insert($insert);
				$this->error = '云洋黑名单'.$performance['result']['reason'];
				return false;
			}
		}
		
		
		$getExpressList = $this->getExpressList($data);
		//p($getExpressList);die;
		return $getExpressList;
	}
	
	//云洋返回价格
	public function getYunyangPrice($uid,$f,$v,$totalWeight,$logoUrl,$coupon_pmt = 0,$co=array(),$insurancePrice=0){
		
		//用户折扣价格
		$zhe = $zhe2 = 10;
		$u = Db::name('users')->where(array('user_id'=>$uid))->field('rank_id,money')->find();
		$ecr = Db::name('express_cate_rank')->where(array('rank_id'=>$u['rank_id'],'cate_id'=>$logoUrl['cate_id']))->field('rank_id,zhe')->find();
		if($ecr){
			if((int)$ecr['zhe'] >10){
				$zhe = 10;
			}elseif((int)$ecr['zhe'] <=0){
				$zhe = 10;
			}else{
				$zhe = $ecr['zhe'];
			}
		}else{
			$zhe = 10;
		}
		
		//折扣价格
		$ecr2 = Db::name('express_cate_rank')->where(array('cate_id'=>$logoUrl['cate_id']))->field('rank_id,zhe')->order('rank_id asc')->find();
		if($ecr2){
			if((int)$ecr2['zhe'] >10){
				$zhe2 = 10;
			}elseif((int)$ecr2['zhe'] <=0){
				$zhe2 = 10;
			}else{
				$zhe2 = $ecr2['zhe'];
			}
		}else{
			$zhe2 = 10;
		}
		
		
		
		
		$data['firstPrice'] = 0;//快递公司首重价格
		$data['addPrice'] = 0;//快递公司续重价格
		$data['firstPrice_jia'] = 0;//后台加价首重价格
		$data['addPrice_jia'] = 0;//后台加价续重价格
		$data['preOrderFee'] = $v['freight']*100;//预支付金额
		
		if($v['originalPrice']){
			$data['originalFee'] = $v['originalPrice']*100;
		}else{
			$originalPrice = (($v['freight']*100)*100)/100;
			$data['originalFee'] = ($v['freight']*100)+$originalPrice;
		}
		
		
		
		$data['preBjFee'] =  0;//预保价金额
		
		if($logoUrl['firstPrice'] <= 0){
			$logoUrl['firstPrice'] = 10;
		}
		
		//原价的加价
		$firstPrice = ($data['preOrderFee']*$logoUrl['firstPrice'])/100;
		$preOrderFee = $data['preOrderFee']+$firstPrice;
		$vipFeeYuan = ($preOrderFee*$zhe)/10;
		
		$firstPrice2 = ($data['preOrderFee']*$logoUrl['firstPrice'])/100;
		$preOrderFee2 = $data['preOrderFee']+$firstPrice2;
		$vipFeeYuan2 = ($preOrderFee2*$zhe2)/10;
		
	
		
		$data['addPrice'] = 0;//续重原始价格
		$data['addPrice_jia'] =0;//续重加价
		
		$vipFeeYuan = $vipFeeYuan+($insurancePrice*100);
		if($vipFeeYuan > $co['full_price']){
			$vipFeeYuan = $vipFeeYuan+($insurancePrice*100)-$coupon_pmt;
		}
		//p($vipFeeYuan);
		//p($preOrderFee2);die;
		
		
		$data['coupon_pmt'] = $coupon_pmt;//支付金额
		$data['sumMoneyYuan'] = $vipFeeYuan;//支付金额
		$data['sumMoneyYuan_old'] = $data['preOrderFee'];//原始金额
		$data['sumMoneyYuan_jia'] = $vipFeeYuan-$data['preOrderFee'];//加价
		$data['discount'] = round($vipFeeYuan/100,1);//普通用户运费
		$data['vip_discount'] = round(($vipFeeYuan2)/100,1);//VIP价格运费
		$data['original_cost'] = round(($data['originalFee'])/100,0);//原价
		
		//p($data);die;
		return $data;
	}
	
	
	public function getYidaPrice($uid,$f,$v,$totalWeight,$logoUrl,$coupon_pmt = 0,$co=array(),$insurancePrice=0){
		
		$zhe = $zhe2 = 10;
		$u = Db::name('users')->where(array('user_id'=>$uid))->field('rank_id,money')->find();
		$ecr = Db::name('express_cate_rank')->where(array('rank_id'=>$u['rank_id'],'cate_id'=>$logoUrl['cate_id']))->field('rank_id,zhe')->find();
		if($ecr){
			if((int)$ecr['zhe'] >10){
				$zhe = 10;
			}elseif((int)$ecr['zhe'] <=0){
				$zhe = 10;
			}else{
				$zhe = $ecr['zhe'];
			}
		}else{
			$zhe = 10;
		}
		
		//折扣价格
		$ecr2 = Db::name('express_cate_rank')->where(array('cate_id'=>$logoUrl['cate_id']))->field('rank_id,zhe')->order('rank_id asc')->find();
		if($ecr2){
			if((int)$ecr2['zhe'] >10){
				$zhe2 = 10;
			}elseif((int)$ecr2['zhe'] <=0){
				$zhe2 = 10;
			}else{
				$zhe2 = $ecr2['zhe'];
			}
		}else{
			$zhe2 = 10;
		}
		
	
		
		
		$data['firstPrice'] = 0;//快递公司首重价格
		$data['addPrice'] = 0;//快递公司续重价格
		$data['firstPrice_jia'] = 0;//后台加价首重价格
		$data['addPrice_jia'] = 0;//后台加价续重价格
		$data['preOrderFee'] = $v['preOrderFee']*100;//预支付金额
		$data['originalFee'] = $v['originalFee']*100;//官网原价(仅供参考)
		$data['preBjFee'] =  $v['preBjFee']*100;//预保价金额
	
	
		$originalPrice = (($v['preOrderFee']*100)*100)/100;
		$data['originalFee'] = ($v['preOrderFee']*100)+$originalPrice;
		
		if($logoUrl['firstPrice'] <= 0 ){
			$logoUrl['firstPrice'] = 10;
		}
		
		
		//原价的加价
		$firstPrice = ($data['preOrderFee']*$logoUrl['firstPrice'])/100;
		$preOrderFee = $data['preOrderFee']+$firstPrice;
		$vipFeeYuan = ($preOrderFee*$zhe)/10;
		
	
		$firstPrice2 = ($data['preOrderFee']*$logoUrl['firstPrice'])/100;
		$preOrderFee2 = $data['preOrderFee']+$firstPrice2;
		$vipFeeYuan2 = ($preOrderFee2*$zhe2)/10;
		
		
		$data['addPrice'] = 0;//续重原始价格
		$data['addPrice_jia'] =0;//续重加价
		
		
		$vipFeeYuan = $vipFeeYuan+($insurancePrice*100);
		if($vipFeeYuan > $co['full_price']){
			$vipFeeYuan = $vipFeeYuan+($insurancePrice*100)-$coupon_pmt;
		}

		
		$data['coupon_pmt'] = $coupon_pmt;//支付金额
		$data['sumMoneyYuan'] = $vipFeeYuan;//支付金额
		$data['sumMoneyYuan_old'] = $v['preOrderFee']*100;//原始金额
		
		//p($vipFeeYuan);
		
		$data['sumMoneyYuan_jia'] = $vipFeeYuan-($v['preOrderFee']*100);//加价
		$data['discount'] = round($vipFeeYuan/100,1);//普通用户运费
		$data['vip_discount'] = round(($vipFeeYuan2)/100,1);//VIP价格运费
		$data['original_cost'] = round(($data['originalFee'])/100,0);//原价
		return $data;
	}
	
	
	public function performance($content,$Method ='CHECK_CHANNEL'){
		$config = model('Setting')->fetchAll2();
		$appid = $config['wxapp']['yy_appid'];
		$requestId = rand_string(32,3);
		list($t1,$t2) = explode(' ',microtime()); 
	    $timeStamp = (int)((floatval($t1)+floatval($t2))*1000);
		$timeStamp = (string) $timeStamp;
		$secretKey = $config['wxapp']['yy_secretKey'];
	
		
		 $body = array(
			 "serviceCode" =>$Method,
			 "timeStamp" => $timeStamp,
			 "requestId"=> $requestId,
			 "appid" => $appid,
			 "sign"=> $this->getSign($appid,$requestId,$timeStamp,$secretKey),
			 "content"=> $content,
		 );
		 //p(json_encode($body,320));
		 
		 //file_put_contents(ROOT_PATH.'/application/common/model/_yy_execute_'.time().'_'.rand(1111,9999).'.txt',var_export(json_encode($body,320),true));
		 
		 $header = array("Content-Type:application/json");
		 $curl = curl_init();
		 curl_setopt($curl, CURLOPT_URL,$config['wxapp']['yy_url']);
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		 curl_setopt($curl, CURLOPT_POST, 1);
		 curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body,320));
		 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 $result = curl_exec($curl);
		 curl_close($curl);
		 return json_decode($result, true);
	}
	
	
	
	public function getSign($appid,$requestId,$timeStamp,$secretKey){
		$sb = $appid.$requestId.$timeStamp.$secretKey;
        return md5($sb);
	}
	
	//购买优惠券支付
	public function updateCouponOrder($order_id,$need_pay,$log_id,$user_id,$types){
		$config = model('Setting')->fetchAll2();
		//发放优惠券
		model('ExpressOrder')->sendCouponDownload($user_id,'',$order_id,$need_pay);
        return true;
    }
	
	
	
	//支付后回调
	public function updateExpressOrder($order_id,$need_pay,$log_id,$user_id,$types){
		$config = model('Setting')->fetchAll2();
		$order = Db::name('express_order')->where(array('id'=>$order_id))->find();
		
		if($types == 1){
			$updateData['id'] = $order_id;
			$updateData['orderStatus'] = 1;
			$updateData['sumMoneyYuan'] = $need_pay;//支付金额
			$updateData['pay_time'] = time();
			$update = Db::name('express_order')->update($updateData);
			
			
			if($order['type'] == 1 || $config['wxapp']['type'] == 1){
				//易达反序列化数据库
				$requestParams = iunserializer($order['requestParams2']);
				//易达创建正式订单
				$execute = model('Setting')->execute($requestParams,$Method='SUBMIT_ORDER_V2');
				
				//p($execute);die;
				
				if($execute['code'] == 200){
					$ud['id'] = $order_id;
					$ud['orderStatus'] = 1;//已接单-待取货
					$ud['deliveryId'] =$execute['data']['deliveryId'];
					$ud['expressId'] =$execute['data']['upOrderId'];
					$ud['expressNo'] =$execute['data']['orderNo'];//运单编号
					$ud['sumMoneyYuan'] = $need_pay;//支付金额
					$update = Db::name('express_order')->update($ud);
				}else{
					$ud['id'] = $order_id;
					$ud['orderStatus'] = 9;//订单异常
					$ud['message'] =$execute['msg'];
					$update = Db::name('express_order')->update($ud);
					return ture;
				}
			}elseif($order['type'] == 2 || $config['wxapp']['type'] == 2){
				//云洋反序列化数据库
				$requestParams = iunserializer($order['requestParams']);
				//云洋创建正式订单
				$performance = model('Setting')->performance($requestParams,'ADD_BILL_INTELLECT');
				
				//p($requestParams);
				//p($performance);die;
				
				if($performance['code'] == 1){
					$ud['id'] = $order_id;
					$ud['orderStatus'] = 1;//已接单-待取货
					$ud['deliveryId'] =$performance['result']['waybill'];
					$ud['expressId'] =$performance['result']['waybill'];
					$ud['expressNo'] =$performance['result']['shopbill'];
					$ud['sumMoneyYuan'] = $need_pay;//支付金额
					//p($performance['result']);
					//p($ud);die;
					
					$update = Db::name('express_order')->update($ud);
				}else{
					$ud['id'] = $order_id;
					$ud['orderStatus'] = 9;//订单异常
					$ud['message'] =$performance['message'];
					$update = Db::name('express_order')->update($ud);
					return ture;
				}
			}
			
		}else{
			//更新差价支付
			$updateData['id'] = $order_id;
			$updateData['diffStatus'] = 2;
			$updateData['diffMoneyYuan'] = $need_pay;//差价金额
			$update = Db::name('express_order')->update($updateData);
		}
		
		if($order['coupon_download_id']){
			//让优惠券失效
			Db::name('coupon_download')->where(array('download_id'=>$order['coupon_download_id']))->update(array('used_time'=>time(),'is_used'=>1));
		}
		
		
		
        return true;
    }
	
	
	
	
	//执行接口
	public function execute($requestParams,$Method){
		 $config = model('Setting')->fetchAll2();
		 list($t1,$t2) = explode(' ',microtime()); 
		 $timestamp = (int)((floatval($t1)+floatval($t2))*1000);
		 $timestamp = (string) $timestamp;
		 
		 $sign_Array = array(
			  "privateKey" => $config['wxapp']['yd_secret'],
			  "timestamp"  => $timestamp,
			  "username"   => $config['wxapp']['yd_name']
			);
		 $sign  = strtoupper(MD5(json_encode($sign_Array,320)));
		 $body = array(
			 "apiMethod"        => $Method,
			 "businessParams"   => $requestParams,
			 "sign"             => $sign,
			 "timestamp"        => $timestamp,
			 "username"         => $config['wxapp']['yd_name']
		 );
		 
		 //file_put_contents(ROOT_PATH.'/application/common/model/_yd_execute_'.time().'_'.rand(1111,9999).'.txt',var_export(json_encode($body,320),true));
		 //p($body);die;
		 
		 $header = array("Content-Type:application/json");
		 $curl = curl_init();
		 curl_setopt($curl, CURLOPT_URL,$config['wxapp']['yd_url']);
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		 curl_setopt($curl, CURLOPT_POST, 1);
		 curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body,320));
		 curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 $result = curl_exec($curl);
		 curl_close($curl);
		 return json_decode($result, true);
	}
}
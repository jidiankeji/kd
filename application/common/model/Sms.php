<?php
namespace app\common\model;
use think\Db;
use think\Model;
use think\Cache;

use app\common\model\Setting;


class Sms extends Base{
    protected $pk = 'sms_id';
    protected $tableName = 'sms';
    protected $token = 'sms';




	//发送短信中间件
	public function send($code,$shop_id,$mobile, $data){
		$config = Setting::config();
		if($config['sms']['dxapi'] == 'dy') {
           $this->dayuSms($code,$shop_id, $mobile, $data);
        }elseif($config['sms']['dxapi'] == 'ihuyi'){
           $this->smsIhuyiSend($code,$shop_id, $mobile,$data);
        }elseif($config['sms']['dxapi'] == 'bo'){
           $this->smsBaoSend($code,$shop_id, $mobile, $data);
        }elseif($config['sms']['dxapi'] == 'qcloudsms'){
           $this->smsCloudSmsSend($code,$shop_id, $mobile, $data);
        }else{
			return false;
		}
		return true;
	}





	//互亿无线发送信息
	public function ihuyiPost($curlPost,$url){
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_HEADER, false);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl,CURLOPT_NOBODY, true);
		curl_setopt($curl,CURLOPT_POST, true);
		curl_setopt($curl,CURLOPT_POSTFIELDS, $curlPost);
		$return_str = curl_exec($curl);
		curl_close($curl);
		return $return_str;
	}


	//互亿无线短信发送接口
    public function smsIhuyiSend($code,$shop_id,$mobile,$data){
		$config = Setting::config();
		list($sms_id,$shop_id,$content) = $this->getIhuyiSmsContent($code,$shop_id,$mobile,$data);
		if($shop_id){
			$Smsshop = Db::name('sms_shop')->where(array('type'=>'shop','status'=>'0','shop_id'=>$shop_id))->find();
			if($Smsshop['num'] <= 0){
				model('SmsBao')->ToUpdate($sms_id,$shop_id,$res = '-1');//更新状态未-1
				return true;
			}
		}
		$areaCode = $config['sms']['areaCode'] ? $config['sms']['areaCode'] : '00855';
		if(strpos($mobile,$areaCode) !==false){
			$res = $this->postIhuyiInternationa($mobile,$content);//国际短信
		}else{
			$res = $this->postIhuyi($mobile,$content);//国内短信
		}
		model('SmsBao')->ToUpdate($sms_id,$shop_id,$res);
        return true;
	}


	//互亿无线短信宝获取发送详情国内
	public function getIhuyiSmsContent($code,$shop_id,$mobile,$data){
		$config = Setting::config();
		$tmpl = Db::name('sms')->where(array('sms_key'=>$code))->find();
        if(!empty($tmpl['is_open'])){
            $content = $tmpl['sms_tmpl'];
            $data['sitename'] = $config['site']['sitename'];
            $data['tel'] = $config['site']['tel'];
            foreach ($data as $k => $val) {
                $val = str_replace('【', '', $val);
                $val = str_replace('】', '', $val);
                $content = str_replace('{' . $k . '}','【变量】', $content);
            }
			$content =  str_replace("【【变量】】","",$content);
            if(is_array($mobile)) {
                $mobile = join(',', $mobile);
            }
            if($config['sms']['charset']){
                $content = auto_charset($content,'UTF8','gbk');
            }
			$sms_id = $this->sms_bao_add($mobile,$shop_id, $content);
            return array($sms_id,$shop_id,$content);
        }
	}

	//互亿无线
	public function ihuyiXmlToArray($xml){
		$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
		if(preg_match_all($reg, $xml, $matches)){
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++){
			$subxml= $matches[2][$i];
			$key = $matches[1][$i];
				if(preg_match($reg,$subxml)){
					$arr[$key] = $this->ihuyiXmlToArray($subxml);
				}else{
					$arr[$key] = $subxml;
				}
			}
		}
		return $arr;
	}


	//互亿无线国际短信接口
	public function postIhuyiInternationa($mobile,$content){
		$_config = Setting::config();
		$target = "http://api.isms.ihuyi.com/webservice/isms.php?method=Submit";
		$post_data = "account=".$_config['sms']['appid2']."&password=".$_config['sms']['apikey2']."&mobile=".$mobile."&content=".$content;
		$gets =  $this->ihuyiXmlToArray($this->ihuyiPost($post_data,$target));
		if($gets['SubmitResult']['code']==2){
			return '提交互亿无线成功';
		}else{
			return date("Y-m-d H:i:s").' 返回码 : '. $gets['SubmitResult']['code'] .', 返回描述 : '.$gets['SubmitResult']['msg'].' . 发送号码 : '.$mobile.',短信详情 : '.$content;
		}
	}

	//互亿无线国内短信接口
	public function postIhuyi($mobile,$content){
		$_config = Setting::config();
		$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
		$post_data = "account=".$_config['sms']['appid1']."&password=".$_config['sms']['apikey1']."&mobile=".$mobile."&content=".$content;
		$gets = $this->ihuyiXmlToArray($this->ihuyiPost($post_data,$target));
		if($gets['SubmitResult']['code']==2){
			return '提交互亿无线成功';
		}else{
			return date("Y-m-d H:i:s").' 返回码 : '.$gets['SubmitResult']['code'] .',返回描述 :'.$gets['SubmitResult']['msg'].'.发送号码 : '.$mobile.' ,短信详情 : '.$content;
		}
	}



	//腾讯云短信,大于模板跟短信宝通用
    public function smsCloudSmsSend($code,$shop_id,$mobile,$data){
		$_config = Setting::config();

		$tag = Db::name('dayu_tag')->where(array('dayu_local'=>$code))->find();
		$sms_id = $this->sms_dayu_add($_config['sms']['qcloudsms_sign'],$code,$shop_id,$mobile,$data,$tag['dayu_note']);

        foreach($data as $k => $val){
            $content[] = str_replace('${' . $k . '}', $val,$tag['dayu_note']);
        }

		try{
			$appid = $_config['sms']['qcloudsms_appid'];//qcloudsms_appid
			$appkey = $_config['sms']['qcloudsms_appkey'];	//qcloudsms_appkey
			$templId = $tag['qcloudsms_id'];//模板ID
			$nationCode = $_config['sms']['nationCode'] ? $_config['sms']['nationCode'] : '86';
			$singleSender = new \qcloudsms\SmsSingleSender($appid,$appkey);
			$params = $content;//内容
			$result = $singleSender->sendWithParam($nationCode, $mobile, $templId, $params, "", "", "");
			$res = (array)json_decode($result);

			if($res['result'] != 0){
				Db::name('dayu_sms')->where(array('sms_id' =>$sms_id))->update(array('status' =>0,'info'=>'【腾讯云】'.$res['errmsg']));//失败腾讯云短信状态
			}else{
				Db::name('dayu_sms')->where(array('sms_id' =>$sms_id))->update(array('status' =>1,'info'=>'【腾讯云】'.$res['errmsg']));//成功腾讯云短信状态
			}
		}catch(\Exception $e){
			return false;
			echo var_dump($e);
		}
    }






    //短信宝发接口
    public function smsBaoSend($code,$shop_id,$mobile,$data){
			$config = Setting::config();
		    list($sms_id,$shop_id,$content) = $this->getSmsContent($code, $shop_id,$mobile, $data);
            $local = array('mobile' => $mobile, 'content' => $content);
			if($shop_id){
				$sms_shop = Db::name('sms_shop')->where(array('type'=>'shop','status'=>'0','shop_id'=>$shop_id))->find();
				if($sms_shop['num'] <= 1){
					model('SmsBao')->ToUpdate($sms_id,$shop_id,$res = '-1');//更新状态未-1
					return true;
				}
			}

			$account='cuncunhui';

        $password = md5("cuncunhui888"); //短信平台密码
        $config['sms']['url']='http://api.smsbao.com/sms?u='.$account.'&p='.$password.'&m={mobile}&c={content}';
            $http = tmplToStr($config['sms']['url'], $local);

			if($config['sms']['curl'] == 'get'){
				$this->curl = new \Curl();
				$res = $this->curl->get($http);
				$res = json_decode($info, true);
			}else{
				$res = file_get_contents($http);
			}
			model('SmsBao')->ToUpdate($sms_id,$shop_id,$res);//更新短信宝状态
            return true;
    }






	//获取发送详情万能短信接口模板这里万能接口跟腾讯云公用
	public function getSmsContent($code,$shop_id,$mobile,$data){
		$config = Setting::config();
		if($detail = Db::name('sms')->where('sms_key',$code)->find()){
			$content = $detail['sms_tmpl'];
            $data['sitename'] = $config['site']['sitename'];
            $data['tel'] = $config['site']['tel'];
            foreach ($data as $k => $val) {
                $val = str_replace('【', '', $val);
                $val = str_replace('】', '', $val);
                $content = str_replace('{' . $k . '}', $val, $content);
            }
            if(is_array($mobile)) {
                $mobile = join(',', $mobile);
            }
            if($config['sms']['charset']) {
                $content = auto_charset($content, 'UTF8', 'gbk');
            }
			$sms_id = $this->sms_bao_add($mobile,$shop_id, $content);//添加数据
			if($detail['is_open'] == 1){
				return array($sms_id,$shop_id,$content);
			}else{
				return array(1,1,'【错误】当前模板已经关闭');
			}
		}else{
			return array(1,1,'【错误】没找到对应的模板');
		}
	}



	//大鱼发送接口

    public function dayuSms($code,$shop_id, $mobile, $data){
		$_config = Setting::config();
        $dycode = Db::name('dayu_tag')->where(array('dayu_local'=>$code))->find();
        if(!empty($dycode['is_open'])){
            $sms_id = $this->sms_dayu_add($_config['sms']['sign'],$code,$shop_id, $mobile, $data, $dycode['dayu_note']);


			if($_config['sms']['dayu_version'] ==1){
				$obj = new \Dayu($_config['sms']['dykey'], $_config['sms']['dysecret']);
				if($obj->sign($_config['sms']['sign'])->data($data)->sms_id($sms_id)->code($dycode['dayu_tag'])->send($mobile)) {
					return true;
				}
			}elseif($_config['sms']['dayu_version'] ==2){
				$obj = new \Alisms($_config['sms']['dykey'], $_config['sms']['dysecret']);
				if($obj->send($_config['sms']['sign'],$dycode['dayu_tag'],$mobile,$data,$sms_id)){
					return true;
				}
			}

        }
        return false;
    }


	//大于添加到短信记录
    public function sms_dayu_add($sign, $code, $shop_id,$mobile, $data, $dayu_note){
        foreach($data as $k => $val){
            $content = str_replace('${' . $k . '}', $val, $dayu_note);
            $dayu_note = $content;
        }

        $sms_data = array();
        $sms_data['sign'] = $sign . '-' .microtime();
        $sms_data['code'] = $code;
		$sms_data['shop_id'] = $shop_id;
        $sms_data['mobile'] = $mobile;
        $sms_data['content'] = $content;
        $sms_data['create_time'] = time();
        $sms_data['create_ip'] = request()->ip();
        if($sms_id = Db::name('dayu_sms')->insertGetId($sms_data)){
            return $sms_id;
        }
        return true;
    }



	//短信宝添加
    public function sms_bao_add($mobile,$shop_id, $content){
        $sms_data = array();
        $sms_data['mobile'] = $mobile;
		$sms_data['shop_id'] = $shop_id;
        $sms_data['content'] = $content;
        $sms_data['create_time'] = time();
        $sms_data['create_ip'] = request()->ip();
        if ($sms_id = Db::name('sms_bao')->insertGetId($sms_data)){
            return $sms_id;
        }
        return true;
    }







    //验证码
    public function sms_yzm($mobile, $randstring){
		$this->send('sms_yzm',$shop_id = '0', $mobile, array('code' => $randstring));
        return true;
    }

	 //用户重置新密码
    public function sms_user_newpwd($mobile, $password){
		$_config = Setting::config();
       $this->send('sms_user_newpwd',$shop_id = '0', $mobile, array(
			'sitename' => cut_msubstr($_config['site']['sitename'],0,16, false),
		    'newpwd' => $password
	   ));
       return true;
    }


    //用户下载优惠劵通知用户手机
    public function coupon_download_user($download_id, $uid){
        $Coupondownload = Db::name('coupon_download')->find($download_id);
        $Coupon = Db::name('coupon')->find($Coupondownload['coupon_id']);
        $user = Db::name('users')->find($uid);

		$this->send('coupon_download_user',$Coupondownload['shop_id'], $user['mobile'], array(
			'couponTitle' => cut_msubstr($Coupon['title'],0,16, false),
			'code' => $Coupondownload['code'],
			'expireDate' => $Coupon['expire_date']
		));

        return true;
    }







	 //后台账户异地登录通知管理员
    public function sms_admin_login_admin($mobile,$user_name,$time){
        $this->send('sms_admin_login_admin', $shop_id = '0',$mobile, array(
			'userName' => cut_msubstr($user_name, 0, 8, false),
			'time' => $time
		));
        return true;
    }


	//新用户注册短信通知接口，支持扣除商家短信
    public function register($user_id,$mobile,$account,$password,$shop_id){

		$this->send('register',0,$mobile, array(
			'userId' => $user_id,
			'userAccount' => cut_msubstr($account, 0, 8, false),
			'userPassword' => $password,
			'shopName' =>cut_msubstr($shop['shop_name'],0, 8, false),
		));
        return true;
    }




	//会员升级短信通知
	public function sms_user_rank_update($log_id){
		$logs = Db::name('user_rank_logs')->where(array('log_id'=>$log_id))->find();
		
		$users = Db::name('users')->find($logs['user_id']);
		$mobile = $users['mobile'];
		
		$this->send('sms_user_rank_update',0,$mobile, array(
			'userName' => cut_msubstr($users['nickname'],0, 12, false),
			'oldRankName' => cut_msubstr($logs['old_rank_name'],0, 12, false),
			'newRankName' => cut_msubstr($logs['new_rank_name'],0, 12, false),
			'logId' => $logs['log_id']
		));
	}
	
	
	//补差价
	public function sendSmsTmplSend($detail=array(),$user_id=0,$title = '补差价'){
		$users = Db::name('users')->where(array('user_id'=>$user_id))->find();
		$mobile = $users['mobile'];
		$this->send('send_sms_user_diff_money',0,$mobile, array(
			'userName' => cut_msubstr($users['nickname'],0,12,false),
			'orderId' => $detail['id']
		));
	}
	
	//提交货运信息
	public function sendSmsExpressTransport($data=array()){
		$_config = Setting::config();
		$mobile = $_config['site']['config_mobile'];
		//p($mobile );die;
		$this->send('send_sms_express_transport',0,$mobile, array(
			'sender' => $data['sender_province'].'-'.$data['sender_city'].'-'.$data['sender_area'],
			'recipients' => $data['recipients_province'].'-'.$data['recipients_city'].'-'.$data['recipients_area'],
			'mobile' => $data['mobile']
		));
	}
	//网站后台推送短信
    public function smsAdminPush($detail,$mobile){

		if($detail['title'] && $detail['intro'] && $detail['url']){
			$news_title = cut_msubstr($detail['title'],0,12, false).'内容：'.cut_msubstr($detail['intro'],0,38, false).'链接：'.cut_msubstr($detail['url'],0,80, false);
		}elseif($detail['title'] && $detail['intro']){
			$news_title = cut_msubstr($detail['title'],0,12, false).'内容：'.cut_msubstr($detail['intro'],0,38, false);
		}else{
			$news_title = cut_msubstr($detail['title'],0,12, false);
		}
		$this->send('sms_shop_news_push',$shop_id = 0, $mobile, array(
			'newsTitle' => cut_msubstr($news_title,0,16, false), //标题
			'newsSource' => cut_msubstr($config['site']['sitename'], 0, 8, false), //作者
		));
        return true;
    }




}

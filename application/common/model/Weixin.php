<?php

namespace app\common\model;
use think\Model;
use think\Db;
use app\common\model\Setting;


use EasyWeChat\Message\Text;
use EasyWeChat\Message\Image;
use EasyWeChat\Message\Voice;
use EasyWeChat\Message\News;
use EasyWeChat\Foundation\Application;



class Weixin{
  
    private $data = array();
    private $token = 'e10adc3949ba59abbe56e057f20f883e'; 
    private $access_token = '';
    private $_config = array();
    private $curl = null;

    //构造方法，用于实例化EasyWeChat
	public function _initialize(){
        parent::_initialize();
        $this->options = model('WeixinConfig')->weixinconfig();
        $this->app = new Application($this->options);//前端调用
    }
	
	//构造方法，用于实例化微信SDK
    public function __construct(){
        $this->curl = new \Curl();
    }
	

	//获取Token
    public function getToken($shop_id=0){
        if(!$shop_id){
			return  $this->getSiteToken($type = '0');
		}
        return $this->getShopToken($shop_id);
    }
	



	//判断会员关注公众号1不弹出0弹出
	public function subscribeUser($uid){
		
		$config = Setting::config();
		//获取cookie
		$subscribe = cookie('subscribe');
		if(empty($uid)){
			return 0;
		}
		if($uid && !$subscribe){
			$subscribe = model('Weixin')->subscribe($uid);
			if($subscribe['code'] == 1){
				cookie('subscribe',1,600);
				return 1;
			}
			return 0;
        }
		return 0;
	}
	
	
	//判断是否关注公众号接口
	public function subscribe($uid){
		$config = Setting::config();
		if(empty($uid)){
			return array('code'=>0,'errcode'=>'0','errmsg'=>'uid不存在');
		}
		$count = Db::name('connect')->where(array('uid'=>$uid,'type'=>'weixin'))->count();
		if($count > 1){
			return array('code'=>0,'errcode'=>'0','errmsg'=>'connect表统计不正确');
		}
		$open_id = Db::name('connect')->where(array('uid'=>$uid,'type'=>'weixin'))->value('open_id');
		if(empty($open_id)){
			return array('code'=>0,'errcode'=>'0','errmsg'=>'open_id不存在');
		}
		$token = $this->getSiteToken($type = '0');//$type == 1代表一直请求
		if(empty($token)){
			return array('code'=>0,'errcode'=>'0','errmsg'=>'token获取失败');
		}
		
		$info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid='.$open_id;
		$info = $this->curl->get($info_url);
		$info = json_decode($info,true);
		
		if(@$info['subscribe'] == 1 || @$info['errcode'] == 40001){
			return array('code'=>1,'errcode'=>'1','errmsg'=>'您已关注','data'=>$info);
		}else{
			return array('code'=>0,'errcode'=>'0','errmsg'=>'获取失败');
		}
	}
	
	//微信模板消息判断是否关注公众号接口
	public function weixinTmplSubscribe($uid){
		$config = Setting::config();
		$count = Db::name('connect')->where(array('uid'=>$uid,'type'=>'weixin','open_id'=>array('neq','')))->count();
		if($count > 1){
			$open_id = Db::name('connect')->where(array('uid'=>$uid,'type'=>'weixin'))->order('connect_id asc')->value('open_id');//如果有2账户就取第一次绑定的账户
		}elseif($count == 1){
			$open_id = Db::name('connect')->where(array('uid'=>$uid,'type'=>'weixin'))->value('open_id');
		}else{
			return false;
		}
		if(empty($open_id)){
			return false;
		}
		$token = $this->getSiteToken($type = '1');//$type == 1代表一直请求
		if(empty($token)){
			return false;
		}
		$info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid='.$open_id;
		$info = $this->curl->get($info_url);
		$info = json_decode($info,true);
		if(@$info['subscribe'] == 1){
			return array('code'=>1,'errcode'=>'','errmsg'=>'','subscribe'=>$info['subscribe']);
		}else{
			return array('code'=>2,'errcode'=>@$info['errcode'],'errmsg'=>@$info['errmsg'],'subscribe'=>@$info['subscribe']);
		}
		return false;
	}
	
	
	
	public function getUserInfo($open_id,$type){
		$token = $this->getSiteToken($type);
		$info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid='.$open_id;
		$info = $this->curl->get($info_url);
		$info = json_decode($info,true);
		return $info;
	}

	
	//获取主站的TOKEN，type默认度去缓存，如果值是1的话在线获取接口
	public function getSiteToken($type = '0'){
		$config = Setting::config();
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .$config['weixin']['appid'] . '&secret=' .$config['weixin']['appsecret'];
		$data = @file_get_contents(ROOT_PATH."/get_site_token.json");
		$data = json_decode($data); //序列化
		
		//p($data->expire_time);die;
		
		
		if($type == 1  || $data->expire_time < time()) {
		    $result = $this->curl->get($url);
            $result = json_decode($result, true);
			
			
			//p($result);die;
			if(!empty($result['errcode'])){
				return false;
			}else{
				$datas = new \stdClass();//申明空类
				$datas->expire_time = time() + 6800;
				$datas->access_token = $result['access_token'];
				$datas->create_time= time();
				$fp = fopen(ROOT_PATH."/get_site_token.json", "w");
				fwrite($fp, json_encode($datas));
				fclose($fp);
				return $result['access_token'];
			}
		}
		return $data->access_token;
    }
	
	
	
    
    public function getCode($soure_id,$type){ //生成二维码
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->getSiteToken($type = '0');
        $str = "";
        $data = array(
            'action_name' => 'QR_LIMIT_SCENE',
            'action_info' =>array(
                'scene' => array(
                    'scene_id' => $type.''.$soure_id,
                ),
            ),
        );
        $datastr = json_encode($data);
        $result = $this->curl->post($url, $datastr);
        $result = json_decode($result, true);
        
        if($result['errcode']){
            return false;
        }
        $ticket = urlencode($result['ticket']);
        $imgurl = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=". $ticket;
        return $imgurl;
    }
	
    
     //自定义菜单接口
    public function weixinmenu($data,$shop_id = 0 ){
		
        $datas = array();
        foreach($data['button'] as $key => $val){
            if(!empty($val)){
				if($val['types'] == 1){
					$local = array(
						'type' => 'view',
						'name' => urlencode($val['name']),
						'url' => $val['url'],
                	);
				}elseif($val['types'] == 2){
					$local = array(
						'type' => 'click',
						'name' => urlencode($val['name']),
						'key' => urlencode($val['key']),
                	);
				}elseif($val['types'] == 3){
					$local = array(
						'type' => 'miniprogram',
						'name' => urlencode($val['name']),
						'url' => trim($val['url']),
						'appid' => trim($val['appid']),
						'pagepath' => trim($val['pagepath']),
                	);
				}else{
					$local = array(
						'type' => 'view',
						'name' => urlencode($val['name']),
						'url' => $val['url'],
                	);
				}
				
                foreach($data['child'][$key] as $k => $v){
                    if(!empty($v['name'])){
						if($v['types'] == 1){
							 $local['sub_button'][] = array(
								'type' => 'view',
								'name' => urlencode($v['name']),
								'url' => $v['url'],
							);
						}elseif($v['types'] == 2){
							 $local['sub_button'][] = array(
								'type' => 'click',
								'name' => urlencode($v['name']),
								'key' => urlencode($v['key']),
							);
						}elseif($v['types'] == 3){
							 $local['sub_button'][] = array(
								'type' => 'miniprogram',
								'name' => urlencode($v['name']),
								'url' => trim($val['url']),
								'appid' => trim($v['appid']),
								'pagepath' => trim($v['pagepath']),
							);
						}else{
							$local['sub_button'][] = array(
								'type' => 'view',
								'name' => urlencode($v['name']),
								'url' => $v['url'],
							);
						}
                    }
					
                }
                $datas[] = $local;
            }
        }

	
        $datastr = urldecode(json_encode(array('button'=>$datas)));
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $this->getToken($shop_id);
        $result = $this->curl->post($url, $datastr);
        $result = json_decode($result, true);
		
	
		
        if ($result['errcode'] != 0) {
            return $result['errcode'].'错误原因：'.$result['errmsg'];
        }
        return true;
    }


    //此TOKEN 是由网站分配
    public function init($token) {
        if (!empty($_GET['echostr'])) {
            exit($_GET['echostr']);
        } else {
            $xml = file_get_contents("php://input");
            if (!empty($xml)) {
                $xml = new SimpleXMLElement($xml);

                $xml || exit;

                foreach ($xml as $key => $value) {
                    $this->data[$key] = strval($value);
                }
            }
        }
    }

  
}
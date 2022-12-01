<?php
namespace app\common\model;
use think\Model;
use think\Db;
use app\common\model\Setting;


class Users extends Base{
 	protected $pk = 'user_id';
    protected $tableName = 'users';



  public function getCommonTypes(){
        return array(
			'1' => '签到积分',
			'2' => '邀请新用户',
			'3' => '关注服务号',
			'4' => '寄快递',
			'5' => '其他操作',
			'6' => '后台操作',
			
		);
    }


	//积分消费类型
	public function getIntegralTypes(){
        return array(
			'1' => '签到积分',
			'2' => '邀请新用户',
			'3' => '关注服务号',
			'4' => '寄快递',
			'5' => '看视频',
			'6' => '后台操作',
		);
    }


	//余额消费类型
	public function getMoneyTypes(){
        return array(
			'1' => '订单支付',
			'2' => '后台操作',
			'3' => '申请提现',
			'4' => '分销分成',
			'5' => '订单退款',
			'6' => '余额充值'
		);
    }
	
	

    protected $_integral_type = array(
		'login' => '每日登陆',
		'mobile' => '手机认证',
		'email' => '邮件认证',
		'sign' => '用户签到',
	);

	protected $_type = array(
        'goods' => '商城',
    );

	public function getError(){
        return $this->error;
    }

	

    public function getUserByAccount($account){
        $data = Db::name('users')->where(array('account'=>$account,'closed'=>0))->find();
		if(!$data){
			$data = Db::name('users')->where(array('mobile'=>$account,'closed'=>0))->find();
		}
        return $data;
    }



    public function getUserByMobile($mobile){
        $data = Db::name('users')->where(array('mobile'=>$mobile,'closed'=>0))->find();
		if(!$data){
			$data = Db::name('users')->where(array('account'=>$mobile,'closed'=>0))->find();
		}
        return $data;
    }


    //邮件登录暂时不处理
    public function getUserByEmail($email){
        $data = Db::name('users')->where(array('email'=>$email))->find();
        return $data($data);
    }

    public function getUserByUcId($uc_id){
        $data = Db::name('users')->where(array('uc_id'=>$uc_id))->find();
        return $data($data);
    }




    public function integral($user_id, $mdl){

        $config = Setting::config();

        if(!isset($this->_integral_type[$mdl])){
            return false;
        }
        if($config['integral'][$mdl]){
            return $this->addIntegral($user_id, $config['integral'][$mdl], $this->_integral_type[$mdl],'5');
        }
        return false;
    }



	 //积分兑换商品返还积分给商家中间层
    public function return_integral($user_id, $jifen, $intro){

        $config = Setting::config();

        if(empty($config['integral']['return_integral'])){
            return false;
        }
        $integral = intval(($jifen * $config['integral']['return_integral'])/100);
        if($integral <= 0){
            return false;
        }
        return $this->addIntegral($user_id, $integral, $intro,'4');
    }






	//写入用户余额
    public function addMoney($user_id, $num, $intro = '',$types = '0',$order_id = '0'){


		$users= Db::name('users')->where('user_id',$user_id)->find();
		$money = $users['money'] + $num;

		Db::name('users')->where('user_id',$user_id)->update(array('money'=>$money));
		
		$rank = Db::name('user_rank')->where('rank_id',$users['rank_id'])->find();

		Db::name('user_money_logs')->insert(array(
			'user_id' => $user_id,
			'order_id' => $order_id,
			'rank_id' => $rank['rank_id'],
			'rank_name' => $rank['rank_name'],
			'money' => $num,
			'num' => $num,
			'old_num' => $users['money'],
			'new_num' => $money,
			'type' => $types,
			'year' => date('Y',time()),
			'month' => date('Ym',time()),
			'day' => date('Ymd',time()),
			'intro' => $intro,
			'create_time' => time(),
			'create_ip' =>request()->ip()
		));


        return true;
    }




	//用户积分增加修改
    public function addIntegral($user_id, $num, $intro = '', $type = '1'){


		$users = Db::name('users')->where('user_id',$user_id)->find();
		$integral = $users['integral'] + $num;

		Db::name('users')->where('user_id',$user_id)->update(array('integral'=>$integral));

		Db::name('user_integral_logs')->insert(array(
			'user_id' => $user_id,
			'type' => $type,
			'year' => date('Y',time()),
			'month' => date('Ym',time()),
			'day' => date('Ymd',time()),
			'integral' => $num,
			'num' => $num,
			'old_num' => $users['integral'],
			'new_num' => $integral,
			'intro' => $intro,
			'create_time' => time(),
			'create_ip' => request()->ip()
		));


		return true;
    }




	
	
	
	//三级分销封装
    public function addProfit($user_id, $orderType = 0, $type,$orderId,$shop_id, $num, $is_separate, $info){
        return model('UserProfitLogs')->insert(array(
			'order_type' => $orderType,
			'type' => $type,
			'order_id' => $orderId,
			'user_id' => $user_id,
			'money' => $num,
			'info' => $info,
			'year' => date('Y',time()),
			'month' => date('Ym',time()),
			'day' => date('Ymd',time()),
			'create_time' => time(),
			'is_separate' => $is_separate
		));
    }


	public function apiAnalysisPublic($analysis){

		$keyword=urlencode($analysis);//将关键字编码
		$keyword=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/",'',$keyword);
		$str=urldecode($keyword);//将过滤后的关键字解码
	
        $patt = '/1[2345678]\d{9}/';
        preg_match  ($patt,$str,$phone);
        if(empty($phone)){
            return 101;
        }
        $name = substr($str,0,strrpos($str,$phone[0]));
        if(empty($name)){
            return 101;
        }
        $address = substr($str,strripos($str,$phone[0])+11);
        $address_all = $address;
        if(empty($address_all)){
            return 101;
        }
        preg_match('/(.*?(省|自治区|北京市|天津市))/', $address, $matches);
        if (count($matches) > 1) {
            $province = $matches[count($matches) - 2];
            $address = str_replace($province, '', $address);
        }
        preg_match('/(.*?(市|自治州|地区|区划|县))/', $address, $matches);
        if (count($matches) > 1) {
            $city = $matches[count($matches) - 2];
            $address = str_replace($city, '', $address);
        }
        preg_match('/(.*?(区|县|镇|乡|街道))/', $address, $matches);
        if (count($matches) > 1) {
            $area = $matches[count($matches) - 2];
            $address = str_replace($area, '', $address);
        }
        $conurbation = [
            'province' => isset($province) ? trim($province) : '',
            'city' => isset($city) ? trim($city) : '',
            'area' => isset($area) ? trim($area) : '',
        ];
        if(empty($conurbation['province'])){
            $conurbation['province'] = $conurbation['city'];
        }
        if(empty($conurbation['city'])){
            $conurbation['city'] = $conurbation['province'];
        }
     

 		$address_all = @explode($conurbation['area'],$address_all);
 //p($address_all);
        $data = [
            'city'=>$conurbation['province'].' '.$conurbation['city'].' '.$conurbation['area'],
            'linkMan'=>trim($name),
            'mobile'=>trim($phone[0]),
            'address'=>trim($address_all[1]),
        ];
        return $data;
    }




    public function CallDataForMat($items){
        if(empty($items)){
            return array();
        }
        $obj = model('UserRank');
        $rank_ids = array();
        foreach($items as $k => $val){
            $rank_ids[$val['rank_id']] = $val['rank_id'];
        }
        $userranks = $obj->itemsByIds($rank_ids);
        foreach($items as $k => $val){
            $val['rank'] = $userranks[$val['rank_id']];
            $items[$k] = $val;
        }
        return $items;
    }








}

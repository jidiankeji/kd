<?php

namespace app\admin\controller;
use think\Db;
use think\Cache;

use app\common\model\Setting as SettingModel;
class Weixintmpl extends Base{
	
	
	protected function _initialize(){
        parent::_initialize();
			$this->config  = SettingModel::config();
			$this->curl = new \Curl();
    }
	

    public function index(){
        if($data = input('data/a', false)){
            $on = true;
			
            foreach($data as $item){
                $is = isset($item['tmpl_id']);
                if($is){
                    $item['update_time'] = time();
                }else{
                    $item['create_time'] = time();
                }
				
                if(!Db::name('weixin_tmpl')->update($item)){
                    $this->jinMsg(model('WeixinTmpl')->getError());
                    continue;
                }else{
                    if($is){
                        if(Db::name('weixin_tmpl')->update($item)){
                            $on = false;
                            $this->jinMsg('更新失败或者您未修改数据');
                            continue;
                        }
                    }else{
                        if(Db::name('weixin_tmpl')->insert($item)){
                            $on = false;
                            $this->jinMsg('添加失败');
                            continue;
                        }
                    }
                }
            }
            if($on){
                $this->jinMsg('操作成功', url('Weixintmpl/index'));
            }
        }else{
            $this->assign('list',$list = Db::name('weixin_tmpl')->order(array('tmpl_id desc'))->select());
            return $this->fetch();
        }
    }
	
	
	public function delete($tmpl_id = 0){
        if($tmpl_id){
            Db::name('weixin_tmpl')->where(array('tmpl_id'=>$tmpl_id))->delete();
            $this->jinMsg('删除成功', url('Weixintmpl/index'));
        }else{
            $this->jinMsg('删除失败');
        }
    }
	

	
	public function deleteUseAutoTemplate($type){
		@set_time_limit(0);
		
		$re_access_token = $this->getaccess_token();
		$send_url ="https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token={$re_access_token}";
		
		$result_json = $this->curl->get($send_url);
		$result = json_decode($result_json,true);
	
		$del_title_arr = array('签收成功通知','补差价通知','接单成功提醒');
		$del_template_arr = array();
		
		if($result['errcode'] == 0){
			foreach($result['data'] as $val){
				if(in_array($val['title'], $del_title_arr)){
					$del_template_arr[] = $val['priTmplId'];
				}
			}
		}
		if(!empty($del_template_arr)){
			foreach($del_template_arr as $vv){
				$del_url ="https://api.weixin.qq.com/wxaapi/newtmpl/deltemplate?access_token={$re_access_token}";
				$data['priTmplId'] = $vv;
				
				$result_del_json = $this->curl_datas($del_url,$data);
				$result_del = json_decode($result_del_json, true);	
			}
		}
		return true;
	}
	

	public function mangeTemplateAuto($type = 1){
		
		$delete = $this->deleteUseAutoTemplate($type);
		@set_time_limit(0);
		
		
		$re_access_token = $this->getaccess_token();
		$category_url = "https://api.weixin.qq.com/wxaapi/newtmpl/getcategory?access_token={$re_access_token}";
		
		$result_category =array();
		$result_category_json = $this->curl_category($category_url);
		$result_category = json_decode($result_category_json, true);	
		$name = array_column($result_category['data'],'name');
	
		$found_supermarket = in_array("查件",$name);
		
		if(empty($found_supermarket) && empty($found_clothing)){
			if(empty($found_supermarket)){
				$this->jinMsg('请在小程序后台添加 "物流服务->查件" 类目');
			}
		}else{
		
		
			$del_url = "https://api.weixin.qq.com/wxaapi/newtmpl/deltemplate?access_token={$re_access_token}";
			$send_url ="https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token={$re_access_token}";
		
			$data = $arr = array();
		
			$data['tid'] = '8817';
			$data['kidList'] = array(1,5,3,4);
			$data['sceneDesc'] ='签收成功通知';
			$add['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[0]['type'] = $type;
			$arr[0]['status'] = 1;
			$arr[0]['sort'] = 1;
			$arr[0]['title'] = '签收成功通知';
			$arr[0]['info'] = '签收成功通知后推送用户';
			$arr[0]['create_time'] = time();
			$arr[0]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[0]['template_id'] = $result['priTmplId'];
			}
		
		
			$data['tid'] = '22092';
			$data['kidList'] = array(1,2,5);
			$data['sceneDesc'] ='补差价通知';
			$add1['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add1['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add1);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[1]['type'] = $type;
			$arr[1]['status'] = 1;
			$arr[1]['sort'] = 2;
			$arr[1]['create_time'] = time();
			$arr[1]['title'] = '补差价通知';
			$arr[1]['info'] = '补差价通知推送用户';
			$arr[1]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[1]['template_id'] = $result['priTmplId'];
			}



			$data['tid'] = '3219';
			$data['kidList'] = array(7,14,4,5);
			$data['sceneDesc'] ='接单成功提醒';
			$add2['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add2['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add2);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[2]['type'] = $type;
			$arr[2]['status'] = 1;
			$arr[2]['sort'] = 3;
			$arr[2]['create_time'] = time();
			$arr[2]['title'] = '接单成功提醒';
			$arr[2]['info'] = '接单成功提醒推送用户';
			$arr[2]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[2]['template_id'] = $result['priTmplId'];
			}
			
			
			$data['tid'] = '24775';
			$data['kidList'] = array(1,2,3,5);
			$data['sceneDesc'] ='优惠券发放通知';
			$add2['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add2['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add2);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[3]['type'] = $type;
			$arr[3]['status'] = 1;
			$arr[3]['sort'] = 3;
			$arr[3]['create_time'] = time();
			$arr[3]['title'] = '优惠券发放通知';
			$arr[3]['info'] = '优惠券发放通知用户';
			$arr[3]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[3]['template_id'] = $result['priTmplId'];
			}
			
			$data['tid'] = '2614';
			$data['kidList'] = array(1,2,3);
			$data['sceneDesc'] ='收益到账通知';
			$add2['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add2['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add2);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[4]['type'] = $type;
			$arr[4]['status'] = 1;
			$arr[4]['sort'] = 3;
			$arr[4]['create_time'] = time();
			$arr[4]['title'] = '收益到账通知';
			$arr[4]['info'] = '收益到账通知';
			$arr[4]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[4]['template_id'] = $result['priTmplId'];
			}
			
			
			$data['tid'] = '21618';
			$data['kidList'] = array(1,2,3,4);
			$data['sceneDesc'] ='佣金到账提醒';
			$add2['priTmplId'] = Db::name('weixin_tmpl')->where(array('type'=>$type,'title'=>$data['sceneDesc']))->value('template_id');
			if(!empty($add2['priTmplId'])){
				$result_del_json = $this->curl_datas($del_url,$add2);
				$result_del = json_decode($result_del_json, true);			
			}
			$arr[5]['type'] = $type;
			$arr[5]['status'] = 1;
			$arr[5]['sort'] = 3;
			$arr[5]['create_time'] = time();
			$arr[5]['title'] = '佣金到账提醒';
			$arr[5]['info'] = '佣金到账提醒';
			$arr[5]['serial'] = '查件';
			$result_json = $this->curl_datas($send_url,$data);
			$result = json_decode($result_json, true);
			if($result['errcode'] == 0){
				$arr[5]['template_id'] = $result['priTmplId'];
			}
			
			
			$delete = Db::name('weixin_tmpl')->where(array('type'=>1))->delete();
			
			$i = 0;
			if(is_array($arr)){
				foreach($arr as $v){
					if($v['template_id']){
						$i++;
						Db::name('weixin_tmpl')->insert($v);
					}
				}
			}
			$this->jinMsg('已添加'.$i.'条订阅号模板消息', url('Weixintmpl/index'));
		}	
	}	
	
	
	//获取小程序
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
	
	public function curl_category($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
		    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$handles = curl_exec($ch);
		curl_close($ch);
		return $handles;
	}
	
	
	public function curl_datas($url,$data,$timeout=30){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
		    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$handles = curl_exec($ch);
		curl_close($ch);
		return $handles;
	}
	
	
	public function sendhttp_get($url){
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($curl,CURLOPT_POST,1);
		curl_setopt($curl,CURLOPT_POSTFIELDS,array());
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}


	public function sendhttps_post($url,$data){
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($curl,CURLOPT_POST,1);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($curl);
		if(curl_errno($curl)){
		  return 'Errno'.curl_error($curl);
		}
		curl_close($curl);
		return $result;
	}
	
	
	
}
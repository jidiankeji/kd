<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;


class Profit extends Base{
	
	public function _initialize(){
        parent::_initialize();
		$this->assign('ranks', model('UserRank')->fetchAll());
    }
	 //分销订单
     public function order(){
		$map = array();
        if($id = (int) input('id')){
            $map['id'] = $id;
            $this->assign('id', $id);
        }
		
		$getSearchDate = $this->getSearchDate();
		if(is_array($getSearchDate)){
			$map['create_time'] = $getSearchDate;
		}
		
		if($user_id = (int) input('user_id')){
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
		
        if($type = input('type')){
            if($type != 999){
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
		
		if($status = input('status')){
            if($status != 999){
                $map['status'] = $status;
            }
            $this->assign('status', $status);
        }else{
            $this->assign('status', 999);
        }
		
        $count = Db::name('user_profit_logs')->where($map)->count(); 
        $Page = new \Page($count, 25); 
        $show = $Page->show(); 
        $list = Db::name('user_profit_logs')->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            $list[$k]['users'] =  Db::name('users')->where(array('user_id'=>$val['user_id']))->find();
			$list[$k]['parent'] =  Db::name('users')->where(array('user_id'=>$val['parent_id']))->find();
        }
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        return $this->fetch(); 
    }
	
	
	//分销商图标统计
 	public function distributorstatistics(){
		$this->assign('count_mobile',$count_mobile = Db::name('users')->where(array('mobile'=>array('neq','')))->count());
		$this->assign('count_mail',$count_mail = Db::name('users')->where(array('email'=>array('neq','')))->count());
		$this->assign('count_weixin',$count_weixin = Db::name('connect')->where(array('type'=>'weixin'))->count());
		$this->assign('count_weibo',$count_weibo = Db::name('connect')->where(array('type'=>'weibo'))->count());
		$this->assign('count_qq',$count_qq = Db::name('connect')->where(array('type'=>'qq'))->count());
		return $this->fetch(); 
	}

	
	
	//分销改动记录
	public function update(){
		$map = array();
		
		$keyword = input('keyword','', 'htmlspecialchars');
        if($keyword){
            $map['info'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		$getSearchDate = $this->getSearchDate();
		if(is_array($getSearchDate)){
			$map['create_time'] = $getSearchDate;
		}
		if($user_id = (int) input('user_id')){
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        $count = Db::name('user_profit_update_logs')->where($map)->count(); 
        $Page = new \Page($count, 25); 
        $show = $Page->show(); 
        $list = Db::name('user_profit_update_logs')->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            $list[$k]['users'] =  Db::name('users')->where(array('user_id'=>$val['user_id']))->find();
			$list[$k]['old'] =  Db::name('users')->where(array('user_id'=>$val['old_pid']))->find();
			$list[$k]['new'] =  Db::name('users')->where(array('user_id'=>$val['new_pid']))->find();
        }
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        return $this->fetch(); 
    }
	
	
	
	
	

}


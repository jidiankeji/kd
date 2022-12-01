<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;


class Express extends Base{
	
	 public function _initialize(){
        parent::_initialize();
		$this->assign('getorderStatus', $getorderStatus = model('Setting')->getorderStatus());
		$this->assign('getdiffStatus', $getdiffStatus = model('Setting')->getdiffStatus());
        $this->assign('getorderRightsStatus', $getorderRightsStatus = model('Setting')->getorderRightsStatus());
		$this->assign('getCompanyApiTypes', $getCompanyApiTypes = model('Setting')->getCompanyApiTypes());
    }
	
	
	
	
    public function addr(){
		$map = array('closed'=>0);
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['name|mobile|addr'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($user_id = (int) input('user_id')){
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        $count = Db::name('user_addr')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('user_addr')->where($map)->order(array('addr_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
			$list[$k]['user'] = Db::name('users')->find($val['user_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

  
    public function addr_delete($addr_id = 0){
        if (is_numeric($addr_id) && ($addr_id = (int) $addr_id)){
			Db::name('user_addr')->update(array('addr_id' => $addr_id,'closed' => 1));
            $this->jinMsg('删除成功！', url('express/addr'));
        }else{
            $addr_id = input('addr_id/a', false);
            if(is_array($addr_id)){
                foreach ($addr_id as $id){
                    Db::name('user_addr')->update(array('addr_id' => $id, 'closed' => 1));
                }
                $this->jinMsg('删除成功', url('express/addr'));
            }
            $this->jinMsg('请选择要删除的收货地址');
        }
    }
	
	
	public function push(){
		$map = array();
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['deliveryId|orderNo'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		if($deliveryId = input('deliveryId','', 'trim,htmlspecialchars')){
            $map['deliveryId'] = $deliveryId;
            $this->assign('deliveryId', $deliveryId);
        }
        $getSearchDate = $this->getSearchDate();//时间搜索
		if(is_array($getSearchDate)){
			$map['create_time'] = $getSearchDate;
		}
        if($status= (int) input('status')){
            if($status != 999) {
                $map['status'] = $status;
            }
            $this->assign('status', $status);
        }else{
            $this->assign('status', 999);
        }
		if($pushType= (int) input('pushType')){
            if($pushType != 999) {
                $map['pushType'] = $pushType;
            }
            $this->assign('pushType', $pushType);
        }else{
            $this->assign('pushType',999);
        }
		if($type= (int) input('type')){
            if($type != 999) {
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type',999);
        }
        $count = Db::name('express_order_push')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('express_order_push')->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
			$list[$k]['order'] = Db::name('express_order')->where(array('deliveryId'=>$val['deliveryId']))->find();
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->assign('count',$count);
        return $this->fetch();
    }

  
  
    public function push_delete($id = 0){
        if (is_numeric($id) && ($id = (int) $id)){
			Db::name('express_order_push')>where(array('id' => $id))->delete();
            $this->jinMsg('删除成功', url('express/push'));
        }else{
            $ids = input('id/a', false);
            if(is_array($ids)){
                foreach ($ids as $id){
                    Db::name('express_order_push'>where(array('id'=> $id)))->delete();
                }
                $this->jinMsg('删除成功', url('express/push'));
            }
            $this->jinMsg('请选择要删除');
        }
    }
	
	
	public function push_detail($id = 0,$p = 0){
        $var = Db::name('express_order_push')->where(array('id' => $id))->find();
        $list = @json_decode($var['context'],true);
		$this->assign('var',$var);
   		$this->assign('list',$list);
        echo $this->fetch();
    }
	
	
	
	 public function dewu(){
		$map = array('closed'=>0);
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['name|mobile|addr'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($user_id = (int) input('user_id')){
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        $count = Db::name('user_addr_dewu')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('user_addr_dewu')->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

  
    public function dewu_delete($id = 0){
        if (is_numeric($id) && ($id = (int) $id)){
			Db::name('user_addr_dewu')->where(array('id' => $id))->delete();
            $this->jinMsg('删除成功', url('express/addr'));
        }else{
            $addr_id = input('addr_id/a', false);
            if(is_array($id)){
                foreach ($id as $id){
                    Db::name('user_addr_dewu')->where(array('id' => $id))->delete();
                }
                $this->jinMsg('删除成功', url('express/dewu'));
            }
            $this->jinMsg('请选择要删除的收货地址');
        }
    }
	
	public function article(){
		$map = array('closed' => 0);
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = Db::name('article')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('article')->where($map)->order(array('article_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

	
    public function article_create(){
        if(request()->post()){
            $data = $this->checkFields(input('data/a', false),array('title','details','orderby'));
			$data['title'] = htmlspecialchars($data['title']);
			if (empty($data['title'])) {
				$this->jinMsg('标题不能为空');
			}
			$data['details'] = SecurityEditorHtml($data['details']);
			if (empty($data['details'])) {
				$this->jinMsg('详细内容不能为空');
			}
			$data['create_time'] = time();
			$data['orderby'] = (int) $data['orderby'];
			$data['audit'] = 1;
            if(Db::name('article')->insert($data)){
                $this->jinMsg('添加成功', url('express/article'));
            }
            $this->jinMsg('操作失败');
        }else{
            return $this->fetch();
        }
    }
	
  
	
    public function article_edit($article_id = 0){
        if($article_id = (int) $article_id){
            if(!($detail = Db::name('article')->find($article_id))){
                $this->error('请选择要编辑的文章');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false),array('title','details','orderby'));
				$data['title'] = htmlspecialchars($data['title']);
				if(empty($data['title'])){
					$this->jinMsg('标题不能为空');
				}
				$data['details'] = SecurityEditorHtml($data['details']);
				if(empty($data['details'])) {
					$this->jinMsg('详细内容不能为空');
				}
				$data['orderby'] = (int) $data['orderby'];
				$data['audit'] = 1;
                $data['article_id'] = $article_id;
                if (false !== Db::name('article')->update($data)) {
                    $this->jinMsg('操作成功', url('express/article'));
                }
                $this->jinMsg('操作失败');
            }else{
                $this->assign('detail', $detail);
                return $this->fetch();
            }
        } else {
            $this->error('请选择要编辑的文章');
        }
    }
	
  

	
	
    public function article_delete($article_id = 0){
        if(is_numeric($article_id) && ($article_id = (int) $article_id)){
            Db::name('article')->update(array('article_id' => $article_id, 'closed' => 1));
            $this->jinMsg('删除成功', url('express/article'));
        }else{
            $article_id = input('article_id/a', false);
            if(is_array($article_id)){
                foreach ($article_id as $id){
                    Db::name('article')->update(array('article_id' => $id, 'closed' => 1));
                }
                $this->jinMsg('批量删除成功', url('express/article'));
            }
            $this->jinMsg('请选择要删除的文章');
        }
    }
	
	
	public function msg(){
		$map = array();
        $count = Db::name('express_msg')->where($map)->count();
        $Page = new \Page($count, 50);
        $show = $Page->show();
        $list = Db::name('express_msg')->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $k =>$v){
			$list[$k]['img'] = explode(",",$v['images']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	public function msg_delete($id = 0){
        if(is_numeric($id) && ($id = (int) $id)){
            Db::name('express_msg')->where(array('id'=>$id))->delete();
            $this->jinMsg('删除成功', url('express/msg'));
        }else{
            $ids = input('id/a', false);
            if(is_array($ids)){
                foreach($ids as $id){
                    Db::name('express_msg')->where(array('id'=>$id))->delete();
                }
                $this->jinMsg('删除成功', url('express/msg'));
            }
            $this->jinMsg('请选择要删除的');
        }
    }

	public function transport(){
		$map = array();
        $count = Db::name('express_transport')->where($map)->count();
        $Page = new \Page($count, 50);
        $show = $Page->show();
        $list = Db::name('express_transport')->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $k =>$v){
			$list[$k]['user'] = Db::name('users')->where(array('user_id'=>$v['user_id']))->find();
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	
	public function transport_delete($id = 0){
        if(is_numeric($id) && ($id = (int) $id)){
            Db::name('express_transport')->where(array('id'=>$id))->delete();
            $this->jinMsg('删除成功', url('express/transport'));
        }else{
            $ids = input('id/a', false);
            if(is_array($ids)){
                foreach($ids as $id){
                    Db::name('express_transport')->where(array('id'=>$id))->delete();
                }
                $this->jinMsg('删除成功', url('express/transport'));
            }
            $this->jinMsg('请选择要删除的');
        }
    }



	public function cate(){
        $list = Db::name('express_cate')->order("cate_id asc")->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	
	  public function expressCateRank($cate_id){
        $option = $_POST['options'];
		foreach($option['rank_id'] as $key => $val){
			$options[] = array(
				'rank_id' => $option['rank_id'][$key],
				'zhe' => $option['zhe'][$key],
			);
		}
		//先删除
		$delete = Db::name('express_cate_rank')->where(array('cate_id'=>$cate_id))->delete(); 
		foreach($options as $k => $val){
			if($val['rank_id']){
				$val['cate_id'] = $cate_id;
				$id = Db::name('express_cate_rank')->insert($val); 
			}
		}
		return true;
		
    }
	
    public function cate_create($parent_id = 0){
        if(request()->post()){
			$data = $this->checkFields(input('data/a', false),array(
			'cate_name','type','is_jia','name','pinyin','pinyin2','photo','firstPrice','lanshou','firstPrice1','firstPrice2','addPrice','addPrice1','addPrice2','limitFirstPrice','limitAddPrice','tel','orderby'));
			$data['cate_name'] = htmlspecialchars($data['cate_name']);
			if(empty($data['cate_name'])){
				$this->jinMsg('分类不能为空');
			}
			$data['orderby'] = (int) $data['orderby'];
            if($cate_id = Db::name('express_cate')->insertGetId($data)){
				$this->expressCateRank($cate_id); //更新等级折扣
                $this->jinMsg('添加成功', url('express/cate'));
            }
            $this->jinMsg('操作失败');
        }else{
			
			$ranks = model('UserRank')->fetchAll();
			foreach($ranks as $k => $v){
				$ecr = Db::name('express_cate_rank')->where(array('cate_id'=>$cate_id,'rank_id'=>$v['rank_id']))->find();
				$ranks[$k]['zhe'] = $ecr['zhe'];
				$ranks[$k]['id'] = $ecr['id'];
			}
			$this->assign('ranks',$ranks);
			
            echo $this->fetch();
        }
    }
 	
	
    public function cate_edit($cate_id = 0){
        if($cate_id = (int) $cate_id) {
            if(!($detail = Db::name('express_cate')->find($cate_id))){
                $this->error('请选择要编辑的类型');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false),array(
				'cate_name','is_jia','type','name','pinyin','pinyin2','photo','firstPrice','lanshou','firstPrice1','firstPrice2','addPrice','addPrice1','addPrice2','limitFirstPrice','limitAddPrice','tel','orderby'));
				$data['cate_name'] = htmlspecialchars($data['cate_name']);
				if(empty($data['cate_name'])){
					$this->jinMsg('分类不能为空');
				}
				$data['orderby'] = (int) $data['orderby'];
                $data['cate_id'] = $cate_id;
                if(false !== Db::name('express_cate')->update($data)){
					$this->expressCateRank($cate_id); //更新等级折扣
                    $this->jinMsg('操作成功', url('express/cate'));
                }
                $this->jinMsg('操作失败');
            }else{
				
				
				$ranks = model('UserRank')->fetchAll();
				foreach($ranks as $k => $v){
					$ecr = Db::name('express_cate_rank')->where(array('cate_id'=>$cate_id,'rank_id'=>$v['rank_id']))->find();
					$ranks[$k]['zhe'] = $ecr['zhe'];
					$ranks[$k]['id'] = $ecr['id'];
				}
				$this->assign('ranks',$ranks);
				
                $this->assign('detail', $detail);
                echo $this->fetch();
            }
        }else{
            $this->jinMsg('请选择要编辑的活动类型');
        }
    }
	

	
    public function cate_delete($cate_id = 0){
        if(is_numeric($cate_id) && ($cate_id = (int) $cate_id)){
            Db::name('express_cate')->where(array('cate_id'=>$cate_id))->delete();
            $this->jinMsg('删除成功', url('express/cate'));
        }else{
            $cate_id = input('cate_id/a', false);
            if(is_array($cate_id)){
                foreach($cate_id as $id){
                    Db::name('express_cate')->where(array('cate_id'=>$id))->delete();
                }
                $this->jinMsg('删除成功', url('express/cate'));
            }
            $this->jinMsg('请选择要删除的活动类型');
        }
    }
	
	
    public function cate_update(){
        $orderby = input('orderby/a', false);
        foreach($orderby as $key => $val){
            $data = array('cate_id' => (int) $key, 'orderby' => (int) $val);
            Db::name('express_cate')->update($data);
        }
        $this->jinMsg('更新成功', url('express/cate'));
    }
	
	
	
    public function index(){
        $map = array();
        $keyword = input('order_id','', 'trim,htmlspecialchars');
        if($keyword){
            $map['id|deliveryId'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $activity_id = (int) input('activity_id');
        if($activity_id){
            $map['activity_id'] = $activity_id;
        }		
		
		
        if($user_id = (int) input('user_id')){
            $map['user_id'] = $user_id;
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
		$getSearchDate = $this->getSearchDate();//时间搜索
		if(is_array($getSearchDate)){
			$map['create_time'] = $getSearchDate;
		}
		
        if($orderStatus= (int) input('orderStatus')){
            if($orderStatus != 999) {
                $map['orderStatus'] = $orderStatus;
            }
            $this->assign('orderStatus', $orderStatus);
        }else{
            $this->assign('orderStatus', 999);
        }
		if(isset($_GET['diffStatus']) || isset($_POST['diffStatus'])){
            $diffStatus= (int) input('diffStatus');
            if($diffStatus != 999){
                $map['diffStatus'] = $diffStatus;
            }
            $this->assign('diffStatus', $diffStatus);
        }else{
            $this->assign('odiffStatus', 999);
        }
		if($orderRightsStatus= (int) input('orderRightsStatus')){
            if($orderRightsStatus != 999) {
                $map['orderRightsStatus'] = $orderRightsStatus;
            }
            $this->assign('orderRightsStatus', $orderRightsStatus);
        }else{
            $this->assign('orderRightsStatus', 999);
        }
		
        $count = Db::name('express_order')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('express_order')->where($map)->order(array('id'=>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k =>$v){
			$list[$k]['user'] = Db::name('users')->find($v['user_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
		
		session('express_order_map',$map);
		
		$this->getOrderStatus = model('Setting')->getorderStatus();
		//统计数量
		$getOrderStatus = array();
		foreach($this->getOrderStatus as $k2 =>$v2){   
		    $getOrderStatus[$k2]['id'] = $k2; 
		    $getOrderStatus[$k2]['name'] = $v2; 
			$getOrderStatus[$k2]['count'] = (int)Db::name('express_order')->where(array('orderStatus'=>$k2,'closed'=>0))->count();
		}
		$this->assign('getOrderStatus',$getOrderStatus);
		$this->assign('count',$count);
		
		$this->assign('sumMoneyYuan',$sumMoneyYuan = (int)Db::name('express_order')->where($map)->sum('sumMoneyYuan'));
		$this->assign('sumMoneyYuan_old',$sumMoneyYuan_old = (int)Db::name('express_order')->where($map)->sum('sumMoneyYuan_old'));
		$this->assign('sumMoneyYuan_jia',$sumMoneyYuan_jia = (int)Db::name('express_order')->where($map)->sum('sumMoneyYuan_jia'));
		$this->assign('diffMoneyYuan',$diffMoneyYuan = (int)Db::name('express_order')->where($map)->sum('diffMoneyYuan'));
		
		
        return $this->fetch();
    }
    public function edit($id = 0){
        if($id = (int) $id){
            if(!($detail = Db::name('express_order')->find($id))){
                $this->error('请选择要编辑');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false),array('deliveryId','orderStatus','realOrderState','diffMoneyYuan','diffStatus'));
				$data['id'] = $id ;
                if(false !== Db::name('express_order')->update($data)) {
                    $this->jinMsg('操作成功', url('express/index'));
                }
                $this->jinMsg('操作失败');
            }else{
				$this->assign('var', $detail);
				$this->assign('detail', $detail);
                return $this->fetch();
            }
        }else{
            $this->error('请选择要编辑');
        }
    }
	
	//订单详情
	public function detail($id = 0){
        if($id = (int) $id){
            if(!($detail = Db::name('express_order')->find($id))){
                $this->error('请选择要编辑');
            }
            $this->assign('var', $detail);
			$this->assign('detail', $detail);
			
			
			
			if($detail['type'] == 1){
				//易达物流信息
				$logoUrl = model('ExpressOrder')->logoUrl($detail['kuaidi']);
				$requestParams = array(
					'deliveryId'=>$detail['deliveryId'],
					'deliveryType'=>$logoUrl['deliveryType'],
				);
				
				$execute = model('Setting')->execute($requestParams,$Method='DELIVERY_TRACE');
				//p($execute);die;
				$pressList = $execute['data'];
				$this->assign('pressList',$pressList);
			}elseif($detail['type'] == 2){
				$requestParams = array(
					'waybill'=>$detail['deliveryId'],
					'shopbill'=>$detail['expressNo'],
				);
				$performance = model('Setting')->performance($requestParams,$Method ='QUERY_TRANCE');
				$pressList = $performance['result'];
				//p($pressList);die;
				$this->assign('pressList',$pressList);
			}
			
			
			return $this->fetch();
           
        }else{
            $this->error('请选择要编辑的');
        }
    }
	
	
	
	
	
	
    public function export(){
        
        $orders = Db::name('express_order')->where($_SESSION['express_order_map'])->order(array('id'=>'asc'))->select();
        $date = date("Y_m_d H:i:s", time());
        $filetitle = "订单列表";
        $fileName = $filetitle . "_" . $date;
        $html = "﻿";
        $filter = array(
			'aa' => 'ID', 
			'bb' => '支付金额', 
			'cc' => '差价金额', 
			'dd' => '快递', 
			'ee' => '寄件地址', 
			'ff' => '收件地址', 
			'gg' => '订单状态', 
			'hh' => '退款状态', 
			'ii' => '重量', 
			'jj' => '用户ID', 
			'kk' => '时间' 
		);
        foreach ($filter as $key => $title){
            $html .= $title . "\t,";
        }
        $html .= "\n";
        foreach ($orders as $k => $v){
            $filter = array(
				'aa' => 'ID', 
				'bb' => '支付金额', 
				'cc' => '差价金额', 
				'dd' => '快递', 
				'ee' => '寄件地址', 
				'ff' => '收件地址', 
				'gg' => '订单状态', 
				'hh' => '退款状态', 
				'ii' => '重量', 
				'jj' => '用户ID', 
				'kk' => '时间' 
			);
            $orders[$k]['aa'] = $v['id'];
            $orders[$k]['bb'] = round($v['sumMoneyYuan']/100,2);
            $orders[$k]['cc'] = round($v['diffMoneyYuan']/100,2);
            $orders[$k]['dd'] = $v['kuaidi'];
            $orders[$k]['ee'] = $v['sendAddress'];
            $orders[$k]['ff'] = $v['receiveAddress'];
            $orders[$k]['gg'] = $getorderStatus[$v['orderStatus']];
            $orders[$k]['hh'] = $getorderRightsStatus[$v['orderRightsStatus']];
            $orders[$k]['ii'] = $v['wight'];
            $orders[$k]['jj'] = $v['user_id'];
            $orders[$k]['kk'] = date('Y-m-d H:i:s',$v['create_time']);
            foreach($filter as $key => $title){
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
        }
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename={$fileName}.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $html;
        exit;
    }
	
	
    public function delete($id = 0){
        if(is_numeric($id) && ($id = (int) $id)){
			model("express_order")->startTrans();
			try{
				$sign = Db::name('express_order')->where(array('id'=>$id))->find();
				if($sign['orderStatus'] == 0){
					Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>-1,'reason'=>'管理员删除'));
					//如果订单未支付让优惠券生效
					if($sign['coupon_download_id']){
						Db::name('coupon_download')->where(array('download_id'=>$sign['coupon_download_id']))->update(array('used_time'=>'','is_used'=>0));
					}
					Db::name('express_order')->where('id',$id)->delete();
					model('express_order')->commit();
					$this->jinMsg('删除成功', url('express/index'));
				}
			}catch(\Exception $e){
				model('express_order')->rollback();
				$this->jinMsg('操作失败'.$e->getMessage());
			}
        }else{
            $this->jinMsg('为了数据安全，不支持批量删除订单');
        }
    }
	
	
	//订单发货
	public function deliver($id = 0){
		$id = (int) $id;
		if(!$id){
			$this->jinMsg('id不存在');
		} 
		if(!($sign = Db::name('express_order')->where(array('id'=>$id))->find())){
			$this->jinMsg('订单不存在');
		}
		if($sign['orderStatus'] != 1){
			return json(array('code'=>'0','msg'=>'订单状态【'.$sign['orderStatus'].'】不正确'));
		}
		
		model('express_order')->startTrans();
		try{
			$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>2));
			model('express_order')->commit();
			$this->jinMsg('操作成功', url('express/index'));
		}catch(\Exception $e){
			model('express_order')->rollback();
			$this->jinMsg($e->getMessage());
		}
    }
	
	
	
	//管理员取消订单并退款remove
	public function remove($id = 0){
		$id = (int) $id;
		if(!$id){
			$this->jinMsg('id不存在');
		} 
		if(!($sign = Db::name('express_order')->where(array('id'=>$id))->find())){
			$this->jinMsg('订单不存在');
		}
		if($sign['orderStatus'] > 2){
			$this->jinMsg('订单状态【'.$sign['orderStatus'].'】不正确');
		}
		
		if($sign['deliveryId']){
			$logoUrl = model('ExpressOrder')->logoUrl($sign['kuaidi']);
			$requestParams['deliveryId'] = $sign['deliveryId'];
			$requestParams['deliveryType'] = $logoUrl['deliveryType'];
			if($sign['type'] == 1){
				//易达取消订单
				//p($requestParams);
				$execute = model('Setting')->execute($requestParams,$Method='CANCEL_ORDER');
				//p($execute);die;
				if($execute['code'] == 200){
					model("express_order")->startTrans();
					try{	
						$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express','易达订单用户取消订单退款');
						if($orderWeixinRefund == false){
							$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],'【'.model('PaymentLogs')->getError().'】易达订单用户取消订单退款',1);
							if($rest){
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
							}else{
								$this->jinMsg('易达接口取消订单余额退款失败');
							}
						}else{
							$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
						}
						model('express_order')->commit();
						$this->jinMsg('退款成功', url('express/index'));
					}catch(\Exception $e){
						model('express_order')->rollback();
						$this->jinMsg($e->getMessage());
					}
				}else{
					$this->jinMsg('易达接口取消订单失败'.$execute['msg']);
				}
			}elseif($sign['type'] == 2){
				$requestParams = array(
					'waybill'=>$sign['deliveryId'],
					'shopbill'=>$sign['expressNo'],
				);
				//p($requestParams);
				$performance = model('Setting')->performance($requestParams,$Method ='CANCEL');
				//p($performance);die;
				if($performance['code'] ==1){
					model("express_order")->startTrans();
					try{	
						$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express','云洋订单用户取消订单退款');
						if($orderWeixinRefund == false){
							$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],'【'.model('PaymentLogs')->getError().'】云洋订单用户取消订单退款',1);
							if($rest){
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
							}else{
								$this->jinMsg('易达接口取消订单余额退款失败');
							}
						}else{
							$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
						}
						model('express_order')->commit();
						$this->jinMsg('退款成功', url('express/index'));
					}catch(\Exception $e){
						model('express_order')->rollback();
						$this->jinMsg($e->getMessage());
					}
				}else{
					$this->jinMsg('云洋接口取消订单失败'.$performance['message']);
				}
			}
		}else{
			$this->jinMsg('deliveryId不存在');
		}
    }
	
	
	
	
	//用户取消订单并退款cancel
	public function cancel($id = 0){
		$id = (int) $id;
		if(!$id){
			$this->jinMsg('id不存在');
		} 
		if(!($sign = Db::name('express_order')->where(array('id'=>$id))->find())){
			$this->jinMsg('订单不存在');
		}
		if($sign['orderStatus'] == 0){
			$this->jinMsg('未付款订单不知此退款');
		}
		if($sign['orderStatus'] == 1){
			$this->jinMsg('已付款订单支持退款');
		}
		if($sign['orderStatus'] == 2){
			$this->jinMsg('已接单不支持退款');
		}
		if($sign['orderStatus'] == 3){
			$this->jinMsg('已取件不支持退款');
		}
		if($sign['orderStatus'] == 4){
			$this->jinMsg('已完成订单不支持退款');
		}
		if($sign['orderRightsStatus'] == 5){
			$this->jinMsg('已取消已退款订单不支持退款');
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
							$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express','易达用户取消订单退款');
							if($orderWeixinRefund == false){
								$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],'【'.model('PaymentLogs')->getError().'】云洋订单用户取消订单退款',1);
								if($rest){
									$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
								}else{
									$this->jinMsg('易达接口取消订单余额退款失败');
								}
							}else{
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
							}
							model('express_order')->commit();
							$this->jinMsg('退款成功', url('express/index'));
						}catch(\Exception $e){
							model('express_order')->rollback();
							$this->jinMsg($e->getMessage());
						}
					}else{
						$this->jinMsg('易达接口取消订单失败，退款失败，可以尝试人工退款，接口返回【'.$execute['msg'].'】');
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
							$orderWeixinRefund = model('PaymentLogs')->orderWeixinRefund($id,$sign['user_id'],$sign['sumMoneyYuan'],$type = 'express','云洋订单用户取消订单退款');
							if($orderWeixinRefund == false){
								$rest = model('Users')->addMoney($sign['user_id'],$sign['sumMoneyYuan'],'【'.model('PaymentLogs')->getError().'】云洋订单用户取消订单退款',1);
								if($rest){
									$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
								}else{
									$this->jinMsg('云洋接口取消订单余额退款失败');
								}
							}else{
								$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>5,'orderRightsStatus'=>2));
							}
							model('express_order')->commit();
							$this->jinMsg('退款成功', url('express/index'));
						}catch(\Exception $e){
							model('express_order')->rollback();
							$this->jinMsg($e->getMessage());
						}
					}else{
						$this->jinMsg('云洋接口取消订单失败'.$performance['message']);
					}
				}
			}else{
				$this->jinMsg('deliveryId不存在');
			}
		
		
    }
	
	
	
	
	//订单完成
	public function complete($id = 0){
		$id = (int) $id;
		if(!$id){
			$this->jinMsg('id不存在');
		} 
		if(!($sign = Db::name('express_order')->where(array('id'=>$id))->find())){
			$this->jinMsg('订单不存在');
		}
		if($sign['orderStatus'] != 2){
			$this->jinMsg('订单状态【'.$sign['orderStatus'].'】不正确');
		}
		model('express_order')->startTrans();
		try{
			$r = Db::name('express_order')->where(array('id'=>$id))->update(array('orderStatus'=>4));
			
			model('express_order')->commit();
			$this->jinMsg('删除成功', url('express/index'));
		}catch(\Exception $e){
			model('express_order')->rollback();
			$this->jinMsg($e->getMessage());
		}
    }


	
	
}
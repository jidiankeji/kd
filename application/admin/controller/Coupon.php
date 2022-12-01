<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;


class Coupon extends Base{

    private $create_fields = array('type','shop_id','cate_id','city_id','area_id','title', 'photo','price', 'money','integral','appId','path', 'full_price', 'reduce_price', 'expire_date', 'num', 'limit_num', 'intro');
    private $edit_fields = array('type','shop_id','cate_id','city_id','area_id','title', 'photo','price', 'money','integral','money','appId','path', 'full_price', 'reduce_price', 'expire_date', 'num', 'limit_num', 'intro');
	
	public function _initialize(){
        parent::_initialize();
    }

	
	
	
    public function index(){
		$map = array('closed' => 0);
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
        if($audit = (int) input('audit')){
            $map['audit'] = $audit === 1 ? 1 : 0;
            $this->assign('audit', $audit);
        }
        $count = Db::name('coupon')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('coupon')->where($map)->order(array('coupon_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	
	
	
	
	public function select(){
		$map = array('closed' => 0,'audit' =>1, 'expire_date' => array('EGT', TODAY));
        if($keyword = input('keyword','', 'trim,htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		
		$getSearchCityId = $this->getSearchCityId($this->city_id);
		if($getSearchCityId){
			$map['city_id'] = $getSearchCityId;
			$this->assign('city_id',$getSearchCityId);
		}
		
        $count = Db::name('coupon')->where($map)->count();
        $Page = new \Page($count,8);
        $show = $Page->show();
        $list = Db::name('coupon')->where($map)->order(array('coupon_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	
	
	
    public function create(){
        if(request()->post()){
           $data = $this->checkFields(input('data/a', false), $this->create_fields);
			$data['shop_id'] = (int) $data['shop_id'];
			$data['cate_id'] = $data['cate_id'];
			$data['city_id'] = $data['city_id'];
		
			
			$data['title'] = htmlspecialchars($data['title']);
			if (empty($data['title'])) {
				$this->jinMsg('标题不能为空');
			}
			$data['photo'] = htmlspecialchars($data['photo']);
			if (empty($data['photo'])) {
				$this->jinMsg('请上传优惠券图片');
			}
			if (!isImage($data['photo'])) {
				$this->jinMsg('优惠券图片格式不正确');
			}
			$data['expire_date'] = htmlspecialchars($data['expire_date']);
			if (empty($data['expire_date'])) {
				$this->jinMsg('过期日期不能为空');
			}
			if (!isDate($data['expire_date'])) {
				$this->jinMsg('过期日期格式不正确');
			}
			$data['intro'] = htmlspecialchars($data['intro']);
			if (empty($data['intro'])) {
				$this->jinMsg('优惠券描述不能为空');
			}
			$data['price'] = (int) ($data['price'] * 100);
			$data['money'] = (int) ($data['money'] * 100);
		
			
			$data['num'] = (int) $data['num'];
			$data['limit_num'] = (int) $data['limit_num'];
			$data['create_time'] = time();
			$data['create_ip'] = request()->ip();
			$data['audit'] = 1;
            if(Db::name('coupon')->insert($data)){
                $this->jinMsg('添加成功', url('coupon/index'));
            }
            $this->jinMsg('操作失败');
        }else{
            return $this->fetch();
        }
    }

	
    public function edit($coupon_id = 0){
		
        if($coupon_id = (int) $coupon_id){
            
            if(!($detail = Db::name('coupon')->find($coupon_id))){
                $this->error('请选择要编辑的优惠券');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false), $this->edit_fields);
				$data['shop_id'] = (int) $data['shop_id'];
				$data['cate_id'] = $data['cate_id'];
				$data['city_id'] = $data['city_id'];
				
				$data['title'] = htmlspecialchars($data['title']);
				if (empty($data['title'])) {
					$this->jinMsg('标题不能为空');
				}
				$data['photo'] = htmlspecialchars($data['photo']);
				if (empty($data['photo'])) {
					$this->jinMsg('请上传优惠券图片');
				}
				if (!isImage($data['photo'])) {
					$this->jinMsg('优惠券图片格式不正确');
				}
				$data['expire_date'] = htmlspecialchars($data['expire_date']);
				if (empty($data['expire_date'])) {
					$this->jinMsg('过期日期不能为空');
				}
				if (!isDate($data['expire_date'])) {
					$this->jinMsg('过期日期格式不正确');
				}
				$data['intro'] = htmlspecialchars($data['intro']);
				if (empty($data['intro'])){
					$this->jinMsg('优惠券描述不能为空');
				}
				$data['price'] = (int) ($data['price'] * 100);
				$data['money'] = (int) ($data['money'] * 100);
			
				$data['num'] = (int) $data['num'];
				$data['limit_num'] = (int) $data['limit_num'];
		
		
                $data['coupon_id'] = $coupon_id;
                if (false !== Db::name('coupon')->update($data)){
                    $this->jinMsg('操作成功', url('coupon/index'));
                }
                $this->jinMsg('操作失败');
            }else{
                $this->assign('detail', $detail);
                return $this->fetch();
            }
        }else{
            $this->error('请选择要编辑的优惠券');
        }
    }

    public function delete($coupon_id = 0){
        if(is_numeric($coupon_id) && ($coupon_id = (int) $coupon_id)){
            Db::name('coupon')->update(array('coupon_id' => $coupon_id, 'closed' => 1));
            $this->jinMsg('删除成功', url('coupon/index'));
        }else{
            $coupon_id = input('coupon_id/a', false);
            if(is_array($coupon_id)){
                foreach ($coupon_id as $id){
                    Db::name('coupon')->update(array('coupon_id' => $id,'closed' => 1));
                }
                $this->jinMsg('删除成功', url('coupon/index'));
            }
            $this->jinMsg('请选择要删除的优惠券');
        }
    }
	
	
    public function audit($coupon_id = 0){
        if(is_numeric($coupon_id) && ($coupon_id = (int) $coupon_id)){
            Db::name('coupon')->update(array('coupon_id' => $coupon_id, 'audit' => 1));
            $this->jinMsg('审核成功', url('coupon/index'));
        }else{
            $coupon_id = input('coupon_id/a', false);
            if(is_array($coupon_id)){
                foreach ($coupon_id as $id){
                    Db::name('coupon')->update(array('coupon_id' => $id, 'audit' => 1));
                }
                $this->jinMsg('审核成功', url('coupon/index'));
            }
            $this->jinMsg('请选择要审核的优惠券');
        }
    }
	
	public function give($coupon_id = 0){
		
        if($coupon_id = (int) $coupon_id){
           
            if(!($detail = Db::name('coupon')->find($coupon_id))){
                $this->error('请选择要操作的优惠券');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false),array('user_id'));
				$data['user_id'] = (int) $data['user_id'];
				if(empty($data['user_id'])){
					$this->jinMsg('用户不能为空');
				}
				$users = Db::name('users')->find($data['user_id']);
				if(empty($users)){
					$this->jinMsg('请选择正确的用户');
				}
				if(empty($detail['title'])){
					$this->jinMsg('优惠券不存在');
				}
				
				$this->jinMsg('操作成功', url('coupon/index'));
				
            }else{
                $this->assign('detail', $detail);
                return $this->fetch();
            }
        }else{
            $this->error('请选择要操作的优惠券');
        }
    }


}
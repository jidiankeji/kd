<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;

class Business extends Base{
	
 	
	private $create_fields = array('business_name','business_name1','user_id','ratio','rise','price','rise2','rise3','out_price','business_attorn_price','intro','business_attorn_intro','pic','photo','lng','lat','orderby');
    private $edit_fields = array('business_name','business_name1','user_id','ratio','rise','price','rise2','rise3','out_price','business_attorn_price','intro','business_attorn_intro','pic','photo','lng','lat','orderby');
    private $area_id = '';
	
	
    public function _initialize(){
        parent::_initialize();
        $this->area_id = (int) (int) input('area_id');
      
        $this->assign('area_id', $this->area_id);
		
		//p($this->area_id);die;
		
		
		$this->assign('orderStatus',$orderStatus = model('Business')->getBusinessOrderStatus());
		$this->assign('orderTypes', $orderTypes = model('Business')->getBusinessOrderTypes());
 
	
    }
	
	

	
	
	
	
    public function index(){
        import('ORG.Util.Page');
		
		if($this->area_id){
			$map = array('area_id' => $this->area_id);	
		}
        $keyword = input('keyword','','htmlspecialchars');
        if($keyword){
            $map['business_name'] = array('LIKE', '%' . $keyword . '%');
        }
		
		$city_id = (int) input('city_id');
		
        if($area_id = (int) input('area_id')){
            $map['area_id'] = $area_id;
			$area = Db::name('area')->find($area_id);
			$this->assign('cityId',$area['city_id'] ? $area['city_id'] : $city_id );
            $this->assign('areaId', $area_id);
        }
		
		
        $count = Db::name('business')->where($map)->count();
        $Page = new \Page($count, 25);
        $show = $Page->show();
        $list = Db::name('business')->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
			$user_ids[$val['user_id']] = $val['user_id'];
        }
        $this->assign('keyword', $keyword);
		$this->assign('users', model('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
		
		$this->assign('area_id',$this->area_id);
        return $this->fetch();
    }
	
	
    public function create(){
        if(request()->post()){
            $data = $this->checkFields(input('data/a', false), $this->create_fields);
			$data['business_name'] = htmlspecialchars($data['business_name']);
			if(empty($data['business_name'])){
				$this->jinMsg('商圈名称不能为空');
			}
			$data['user_id'] = (int) $data['user_id'];
			$data['ratio'] = (int) ($data['ratio']*100);
			$data['rise'] = (int) ($data['rise']*100);
			$data['rise2'] = (int) ($data['rise2']*100);
			$data['rise3'] = (int) ($data['rise3']*100);
			
			$data['price'] = (int) ($data['price']*100);
			$data['out_price'] = (int) ($data['out_price']*100);
			$data['business_attorn_price'] = (int) ($data['business_attorn_price']*100);
			$data['intro'] = htmlspecialchars($data['intro']);
			$data['business_attorn_intro'] = htmlspecialchars($data['business_attorn_intro']);
			$data['pic'] = htmlspecialchars($data['pic']);
			$data['photo'] = htmlspecialchars($data['photo']);
			$data['area_id'] = $this->area_id;
			if(empty($data['area_id'])){
				$this->jinMsg('所在区域不能为空，请从商圈列表里面点击进商圈后编辑');
			}
			$data['orderby'] = (int) $data['orderby'];
            if(Db::name('business')->insertGetId($data)) {
                $this->jinMsg('添加成功', url('business/index', array('area_id' => $this->area_id)));
            }
            $this->jinMsg('操作失败');
        }else{
            return $this->fetch();
        }
    }
	
	
	
    public function edit($business_id = 0){
        if($business_id = (int) $business_id){
            if(!($detail = Db::name('business')->find($business_id))){
                $this->jinMsg('请选择要编辑的商圈管理');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false), $this->edit_fields);
				$data['business_name'] = htmlspecialchars($data['business_name']);
				if (empty($data['business_name'])) {
					$this->jinMsg('商圈名称不能为空');
				}
				$data['user_id'] = (int) $data['user_id'];
				$data['ratio'] = (int) ($data['ratio']*100);
				$data['rise'] = (int) ($data['rise']*100);
				$data['rise2'] = (int) ($data['rise2']*100);
				$data['rise3'] = (int) ($data['rise3']*100);
				
				$data['price'] = (int) ($data['price']*100);
				$data['out_price'] = (int) ($data['out_price']*100);
				$data['business_attorn_price'] = (int) ($data['business_attorn_price']*100);
				$data['intro'] = htmlspecialchars($data['intro']);
				$data['business_attorn_intro'] = htmlspecialchars($data['business_attorn_intro']);
				$data['pic'] = htmlspecialchars($data['pic']);
				$data['photo'] = htmlspecialchars($data['photo']);
				$data['area_id'] = $detail['area_id'];
				$data['orderby'] = (int) $data['orderby'];
                $data['business_id'] = $business_id;
                if (false !== Db::name('business')->update($data)){
                    $this->jinMsg('操作成功', url('business/index', array('area_id' => $this->area_id)));
                }
                $this->jinMsg('操作失败');
            }else{
				$this->assign('user', Db::name('users')->where(array('user_id'=>$detail['user_id']))->find());
                $this->assign('detail', $detail);
                return $this->fetch();
            }
        }else{
            $this->jinMsg('请选择要编辑的商圈管理');
        }
    }
	
	
	
	
    public function hots($business_id){
        if($business_id = (int) $business_id){
            if(!($detail = Db::name('business')->find($business_id))){
                $this->jinMsg('请选择商圈');
            }
            $detail['is_hot'] = $detail['is_hot'] == 0 ? 1 : 0;
            Db::name('business')->update(array('business_id' => $business_id, 'is_hot' => $detail['is_hot']));
            $this->jinMsg('操作成功', url('business/index', array('area_id' => $this->area_id)));
        }else{
            $this->jinMsg('请选择商圈');
        }
    }
	
	
 
	
    
    public function delete(){
        if(is_numeric($_GET['business_id']) && ($business_id = (int) $_GET['business_id'])){
            Db::name('business')->where(array('business_id'=>$business_id))->delete();
            $this->jinMsg('删除成功', url('business/index', array('area_id' => $this->area_id)));
        }else{
            $business_id = input('business_id/a', false);
            if(is_array($business_id)){
                foreach($business_id as $id){
                    Db::name('business')->where(array('business_id'=>$id))->delete();
                }
            
                $this->jinMsg('删除成功', url('business/index', array('area_id' => $this->area_id)));
            }
            $this->jinMsg('请选择要删除的商圈管理');
        }
    }
	
	
    public function child($area_id = 0){
        $datas = model('Business')->fetchAll();
        $str = '<option value="0">请选择</option>';
        foreach($datas as $val){
            if($val['area_id'] == $area_id){
                $str .= '<option value="' . $val['business_id'] . '">' . $val['business_name'] . '</option>';
            }
        }
        echo $str;
        die;
    }
	
	
	
   
}
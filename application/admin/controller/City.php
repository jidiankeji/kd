<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;

class City extends Base{
	
   
    public function index(){
		$map = array();
        $keyword = input('keyword','', 'htmlspecialchars');
        if($keyword){
            $map['name|pinyin|areacode'] = array('LIKE', '%'.$keyword.'%');
        }    
        $this->assign('keyword',$keyword);
	    if($user_id = (int) input('user_id')){
            $map['user_id'] = $user_id;
            $users = Db::name('users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
		
		if($is_open = input('is_open','', 'htmlspecialchars')){
            if($is_open != 999){
                $map['is_open'] = $is_open;
            }
            $this->assign('is_open', $is_open);
        }else{
            $this->assign('is_open', 999);
        }
		
		$getSearchCityId = $this->getSearchCityId($this->city_id);
		if($getSearchCityId){
			$map['city_id'] = $getSearchCityId;
			$this->assign('city_id',$getSearchCityId);
		}
		
        $count = Db::name('city')->where($map)->count(); 
        $Page = new \Page($count, 25); 
        $show = $Page->show(); 
        $list = Db::name('city')->where($map)->order(array('city_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$agent_ids = $user_ids = array();
		foreach($list as $k => $val){
			$val['code'] = Db::name('city_code')->where(array('city'=>array('LIKE','%'.$val['name'].'%')))->find();//百度城市编码
			$user_ids[$val['user_id']] = $val['user_id'];
			$list[$k] = $val;
        }
		$this->assign('users', model('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show); 
        return $this->fetch();
    }
	
	//高德区域编码
	public function areacode(){
		$map = array();
        $keyword = input('keyword','', 'htmlspecialchars');
        if($keyword){
            $map['name'] = array('LIKE', '%'.$keyword.'%');
        }    
        $this->assign('keyword',$keyword);
        $count = Db::name('copy_city')->where($map)->count(); 
        $Page = new \Page($count,15); 
        $show = $Page->show(); 
        $list = Db::name('copy_city')->where($map)->order(array('city_id' =>'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach($list as $k => $val){
			$list[$k]['city'] = Db::name('city')->where(array('city_id'=>$val['city_id']))->find();
        }
        $this->assign('list', $list);
        $this->assign('page', $show); 
        return $this->fetch();
    }
	
	//百度区域编码
	public function citycode(){
		$map = array();
        $keyword = input('keyword','', 'htmlspecialchars');
        if($keyword){
            $map['city|citycode'] = array('LIKE','%'.$keyword.'%');
        }    
        $this->assign('keyword',$keyword);
        $count = Db::name('city_code')->where($map)->count(); 
        $Page = new \Page($count,15); 
        $show = $Page->show(); 
        $list = Db::name('city_code')->where($map)->order(array('id'=>'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show); 
        return $this->fetch();
    }

 	public function selectcitycode(){
		$map = array();
        $keyword = input('keyword','', 'htmlspecialchars');
        if($keyword){
            $map['city|citycode'] = array('LIKE','%'.$keyword.'%');
        }    
        $this->assign('keyword',$keyword);
        $count = Db::name('city_code')->where($map)->count(); 
        $Page = new \Page($count,15); 
        $show = $Page->show(); 
        $list = Db::name('city_code')->where($map)->order(array('id'=>'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show); 
        return $this->fetch(); 
        
    }
	

    public function create(){
        if(request()->post()){
            $data = $this->checkFields(input('data/a', false),array('name','name1','user_id','ratio','areacode','CityCode','pinyin','photo','is_open','lng','lat','orderby','first_letter'));
			$data['name'] = htmlspecialchars($data['name']);
			if (empty($data['name'])){
				$this->jinMsg('城市名称不能为空');
			} 
			$data['user_id'] = (int) $data['user_id'];
			$data['ratio'] = (int) ($data['ratio']*100);
			$data['areacode'] = $data['areacode'];
			$data['CityCode'] = $data['CityCode'];
			
			$data['pinyin'] = htmlspecialchars($data['pinyin']);
			if (empty($data['pinyin'])) {
				$this->jinMsg('城市拼音不能为空');
			}
			$data['photo'] = htmlspecialchars($data['photo']);
			$data['is_open'] = (int)($data['is_open']);
			$data['lng'] = htmlspecialchars($data['lng']);
			$data['lat'] = htmlspecialchars($data['lat']);
			$data['first_letter'] = htmlspecialchars($data['first_letter']);
			$data['orderby'] = (int)($data['orderby']);
			$data['create_time'] = time();
			$data['create_ip'] = request()->ip();
            if(Db::name('city')->insert($data)){
                $this->jinMsg('添加成功', url('city/index'));
            }
            $this->jinMsg('操作失败');
        }else{
            return $this->fetch();
        }
    }


  


    public function edit($city_id = 0){
        if($city_id = (int) $city_id){
            if(!$detail = Db::name('city')->find($city_id)){
                $this->error('请选择要编辑的城市站点');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false),array('name','name1','user_id','ratio','areacode','CityCode','pinyin','photo','is_open','lng','lat','orderby','first_letter'));
				$data['name'] = htmlspecialchars($data['name']);
				if(empty($data['name'])){
					$this->jinMsg('城市名称不能为空');
				} 
				$data['user_id'] = (int) $data['user_id'];
				$data['ratio'] = (int) ($data['ratio']*100);
				$data['areacode'] = $data['areacode'];
				$data['CityCode'] = $data['CityCode'];
				
				
				$data['pinyin'] = htmlspecialchars($data['pinyin']);
				if(empty($data['pinyin'])){
					$this->jinMsg('城市拼音不能为空');
				}
				$data['photo'] = htmlspecialchars($data['photo']);
				$data['is_open'] = (int)($data['is_open']);
				$data['lng'] = htmlspecialchars($data['lng']);
				$data['lat'] = htmlspecialchars($data['lat']);
				$data['first_letter'] = htmlspecialchars($data['first_letter']);
				$data['orderby'] = (int)($data['orderby']);
                $data['city_id'] = $city_id;
                if(false !== Db::name('city')->update($data)){
                    $this->jinMsg('操作成功', url('city/index'));
                }
                $this->jinMsg('操作失败');
            }else{
                $this->assign('detail', $detail);
				$this->assign('user', Db::name('users')->where(array('user_id'=>$detail['user_id']))->find());
                return $this->fetch();
            }
        }else{
            $this->error('请选择要编辑的城市站点');
        }
    }
	
   public function is_open($city_id = 0) {
        if($city_id = (int) $city_id){
			if(model('City')->isOpen($city_id)){
				$this->jinMsg('审核成功', url('city/index'));
			}else{
				$this->jinMsg(model('City')->getError());
			}
        }else{
            $this->jinMsg('请选择你要审核的站点');
        }
    }


	
	//根据分类获取价格
	public function getcitydata($city_id){
        if(!$city_id = (int)$city_id){
			return json(array('code'=>'0','msg'=>'ID不存在'));
        }
        if(!$detail = Db::name('city')->find($city_id)){
			return json(array('code'=>'0','msg'=>'没找到城市'));
        }
		return json(array('code'=>'1','msg'=>'成功匹配城市','lng'=>$detail['lng'],'lat'=>$detail['lat'])); 
    }
	
	//城市删除功能
    public function delete($city_id = 0){
        if($city_id = (int) $city_id){
			
				//查找区域列表
				$areas = Db::name('area')->where(array('city_id'=>$city_id))->select();
				if(is_array($areas)){
					$k1 = 1;
					foreach($areas as $var){
						//查找商圈列表
						$businesss = Db::name('business')->where(array('area_id'=>$var['area_id']))->select();
						if(is_array($businesss)){
							$k2 = 1;
							foreach($businesss as $var2){
								$k2++;
								Db::name('business')->where(array('business_id'=>$var2['business_id']))->delete();//删除商圈
							}
						}
						$k1++;
						Db::name('area')->where(array('area_id'=>$var['area_id']))->delete();//删除区域
					}
				}
				
				if($k1){
					$msg .= '删除区域'.$k1.'个<br>';
				}
				if($k2){
					$msg .= '删除商圈'.$k2.'个<br>';
				}
				if(Db::name('city')->where(array('city_id'=>$city_id))->delete()){
					model('City')->cleanCache();
           			$this->jinMsg($msg, url('city/index'));
				}else{
					$this->jinMsg('删除城市失败');	
				}
        }else{
            $this->jinMsg('请选择要删除的城市站点');
        }
    }


    //备用城市表中插入数据
	public function add($city_id = 0){
		if(!$city_id){
			$this->jinMsg('请选择你要添加的城市');
		}
		if(!$copy_city = Db::name('copy_city')->find($city_id)){
			$this->jinMsg('城市库没东西存在');
		}
		$res = Db::name('city')->where('name',$copy_city['name'])->find();
		if($res){
			$this->jinMsg('貌似您系统城市ID【'.$res['city_id'].'】已经添加了吧');
		}
		$res1 = Db::name('city')->where('areacode',$copy_city['city_id'])->find();
		if($res1){
			$this->jinMsg('系统城市ID【'.$res1['city_id'].'】跟城市库的ID有重复请检查');
		}
		
		
		$arr['city_id'] = $copy_city['city_id'];
		$arr['name'] = $copy_city['name'];
		$arr['areacode'] = $copy_city['city_id'];
		
		$code = Db::name('city_code')->where(array('city'=>array('LIKE','%'.$copy_city['name'].'%')))->find();//百度城市编码
		$arr['CityCode'] = $code['citycode'];
		
		$arr['pinyin'] = $copy_city['pinyin'];
		$arr['is_open'] = 1;
		$arr['lng'] = $copy_city['lng'];
		$arr['lat'] = $copy_city['lat'];
		$arr['first_letter'] = $copy_city['first_letter'];
		$arr['ShortName'] = $copy_city['ShortName'];
		$arr['LevelType'] = $copy_city['LevelType'];
		$arr['CityCode'] = $copy_city['CityCode'];
		$arr['ZipCode'] = $copy_city['ZipCode'];
		$arr['MergerName'] = $copy_city['MergerName'];
		$arr['ParentId'] = $copy_city['ParentId'];
		$arr['create_time'] = time();
		$arr['create_ip'] = request()->ip();
		//添加城市数据
		$city_ids = Db::name('city')->insertGetId($arr);
		
		//查找区域列表
		$copy_areas = Db::name('copy_area')->where(array('city_id'=>$city_id))->select();
		if(is_array($copy_areas)){
			$k1 = 1;
			foreach($copy_areas as $var){
			    //查找商圈列表
				$copy_businesss = Db::name('copy_business')->where(array('area_id'=>$var['area_id']))->select();
				if(is_array($copy_businesss)){
					$k2 = 1;
					foreach($copy_businesss as $var2){
						$k2++;
						$arr2['business_id'] = $var2['business_id'];
						$arr2['business_name'] = $var2['business_name'];
						$arr2['area_id'] = $var['area_id'];//这里应该是上一级商圈ID
						$arr2['areacode'] = $var2['business_id'];
						$arr2['lng'] = $var2['lng'];
						$arr2['lat'] = $var2['lat'];
						Db::name('business')->insert($arr2);//循环插入商圈
					}
				}
				$k1++;
				$arr1['area_id'] = $var['area_id'];
				$arr1['city_id'] = $copy_city['city_id'];//这里应该是上一级城市ID
				$arr1['area_name'] = $var['area_name'];
				$arr1['areacode'] = $var['area_id'];
				$arr1['areacode'] = $var['area_id'];
				$arr1['Name'] = $var['Name'];
				$arr1['LevelType'] = $var['LevelType'];
				$arr1['CityCode'] = $var['CityCode'];
				$arr1['ZipCode'] = $var['ZipCode'];
				$arr1['MergerName'] = $var['MergerName'];
				$arr1['lng'] = $var['lng'];
				$arr1['Lat'] = $var['Lat'];
				$arr1['pinyin'] = $var['pinyin'];
				Db::name('area')->insert($arr1);//循环插入区域数据
			}
		}
		
		if($city_ids){
			$msg .= '成功添加城市【'.$copy_city['name'].'】<br>';
		}
		if($k1){
			$msg .= '添加区域'.$k1.'个<br>';
		}
		if($k2){
			$msg .= '添加商圈'.$k2.'个<br>';
		}
		
        if($city_ids){
			model('City')->cleanCache();//清理缓存
           	$this->jinMsg($msg, url('city/index'));
        }else{
            $this->jinMsg('添加失败');
        }
    }
	
}

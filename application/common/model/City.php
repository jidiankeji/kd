<?php
namespace app\common\model;
use think\Db;
use think\Model;

class City extends Base{
 	protected $pk = 'city_id';
    protected $tableName = 'city';
    protected $token = 'city';
    protected $orderby = array('orderby' => 'asc');
	
    public function setToken($token){
        $this->token = $token;
    }
	
	public function isOpen($city_id){
        if($rest = Db::name('city')->update(array('city_id' => $city_id,'is_open' => 1))){
            return true;
        }else{
			$this->error = '审核失败';
			return false;
		}
		
	}
	
	
	
	public function getAreaNum($city_id){
        $Area = (int)Db::name('area')->where(array('city_id' =>$city_id))->count();
        return $Area;
	}
	
	
}
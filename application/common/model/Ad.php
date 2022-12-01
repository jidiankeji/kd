<?php
namespace app\common\model;
use think\Db;
use think\Model;
use think\Cache;

class Ad extends Base{
	
    protected $pk   = 'ad_id';
    protected $tableName =  'ad';
	
	public function click_number($ad_id){
	
		if(false!== Db::name('ad')->where(array('ad_id'=>$ad_id))->setInc('click',1)){
            return true;
        }else{
           return false;
        }
	}
	//获取广告接口，城市区间，广告ID
	public function get_ad_list($city_ids,$site_id){
		$ad = Db::name('ad')->where(array('closed'=>0,'site_id'=>$site_id,'city_id'=>array('IN', $city_ids),'bg_date' => array('ELT', TODAY),'end_date' => array('EGT', TODAY)))->limit(0,3)->select();
		if(!$ad){
			$ad = Db::name('ad')->where(array('closed'=>0,'site_id'=>$site_id,'bg_date' => array('ELT', TODAY),'end_date' => array('EGT', TODAY)))->limit(0,3)->select();
			if(!$ad){
				$ad = Db::name('ad')->where(array('closed'=>0,'bg_date' => array('ELT', TODAY),'end_date' => array('EGT', TODAY)))->limit(0,3)->select();
			}
		}
		return $ad;
	}
}
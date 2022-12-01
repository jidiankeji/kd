<?php
namespace app\app\controller;
use think\Db;
use think\Cache;

class Ad extends Base{
	
	public function click(){
		$ad_id = (int) input('ad_id');
		$aready = (int) input('aready');
		
		if(!$detail = Db::name('ad')->find($ad_id)){
            $this->error('没有该商家信息');
        }
		if($detail['closed'] ==1){
            $this->error('广告已关闭');
        }
		if ($detail['end_date'] < TODAY) {
            $this->error('广告已过期');
        }
		model('Ad')->click_number($ad_id);
		if(!empty($detail['link_url'])){
			$this->redirect(''.$detail['link_url'].'');
        }else{
			if($aready ==1){
				$this->redirect('wap/index/index');
			}else{
				$this->redirect('home/index/index');
			}
		}
	}
	
	public function community_click(){
		$ad_id = (int) input('ad_id');
		$aready = (int) input('aready');
		if(!$detail = Db::name('community_ad')->find($ad_id)){
            $this->error('没有该小区信息');
        }
		model('CommunityAd')->click_community_number($ad_id);
		if(!empty($detail['link_url'])){
            $this->redirect(''.$detail['link_url'].'');
        }else{
			if($aready ==1){
				$this->redirect('wap/index/index');
			}else{
				$this->redirect('home/index/index');
			}
		}
	}
}
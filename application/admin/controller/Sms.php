<?php

namespace app\admin\controller;
use think\Db;
use think\Cache;


class Sms extends Base{

    private $create_fields = array('sms_key', 'sms_explain', 'sms_tmpl');
    private $edit_fields = array('sms_key', 'sms_explain', 'sms_tmpl');
	
    public function index(){
        $map = array();
        $count = Db::name('sms')->where($map)->count();
        $Page = new \Page($count,40);
        $show = $Page->show();
        $list = Db::name('sms')->where($map)->order(array('sms_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
	
	
    public function create(){
        if(request()->post()){
            $data = $this->checkFields(input('data/a', false), $this->create_fields);
			$data['sms_key'] = htmlspecialchars($data['sms_key']);
			if(empty($data['sms_key'])){
				$this->jinMsg('标签不能为空');
			}
			$data['sms_explain'] = htmlspecialchars($data['sms_explain']);
			if (empty($data['sms_explain'])){
				$this->jinMsg('说明不能为空');
			}
			$data['sms_tmpl'] = htmlspecialchars($data['sms_tmpl']);
			if(empty($data['sms_tmpl'])){
				$this->jinMsg('模版不能为空');
			}
            if(Db::name('sms')->insert($data)){
                $this->jinMsg('添加成功', url('sms/index'));
            }
            $this->jinMsg('操作失败');
        }else{
            return $this->fetch();
        }
    }
	
   
	
    public function edit($sms_id = 0){
        if($sms_id = (int) $sms_id){
            if(!($detail = Db::name('sms')->find($sms_id))){
                $this->error('请选择要编辑的短信模版');
            }
            if(request()->post()){
                $data = $this->checkFields(input('data/a', false), $this->edit_fields);
				$data['sms_key'] = htmlspecialchars($data['sms_key']);
				if(empty($data['sms_key'])){
					$this->jinMsg('标签不能为空');
				}
				$data['sms_explain'] = htmlspecialchars($data['sms_explain']);
				if(empty($data['sms_explain'])){
					$this->jinMsg('说明不能为空');
				}
				$data['sms_tmpl'] = htmlspecialchars($data['sms_tmpl']);
				if(empty($data['sms_tmpl'])){
					$this->jinMsg('模版不能为空');
				}
		
                $data['sms_id'] = $sms_id;
                if(false !== Db::name('sms')->update($data)){
                    $this->jinMsg('操作成功', url('sms/index'));
                }
                $this->jinMsg('操作失败');
            }else{
                $this->assign('detail', $detail);
                return $this->fetch();
            }
        }else{
            $this->error('请选择要编辑的短信模版');
        }
    }
	
   
	
    public function delete($sms_id = 0){
        if(is_numeric($sms_id) && ($sms_id = (int) $sms_id)){
            Db::name('sms')->update(array('sms_id' => $sms_id, 'is_open' => 0));
            $this->jinMsg('关闭成功！', url('sms/index'));
        }else{
            $sms_id = input('sms_id/a', false);
            if(is_array($sms_id)){
                foreach($sms_id as $id){
                    Db::name('sms')->update(array('sms_id' => $id, 'is_open' => 0));
                }
                $this->jinMsg('关闭成功', url('sms/index'));
            }
            $this->jinMsg('请选择要关闭的短信模版');
        }
    }
	
    public function audit($sms_id = 0){
        if(is_numeric($sms_id) && ($sms_id = (int) $sms_id)){
            Db::name('sms')->update(array('sms_id' => $sms_id, 'is_open' => 1));
            $this->jinMsg('开启成功', url('sms/index'));
        }else{
            $sms_id = input('sms_id/a', false);
            if (is_array($sms_id)) {
                foreach ($sms_id as $id){
                    Db::name('sms')->update(array('sms_id' => $id,'is_open' => 1));
                }
                $this->jinMsg('开启成功！', url('sms/index'));
            }
            $this->jinMsg('请选择要开启的短信模版');
        }
    }
}
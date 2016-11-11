<?php

class Application_Model_Db_Save extends Application_Model_Db_Any{
	public function save(){
		if(!$this->exists()){
			if(method_exists($this,'preset_'.$this->_name))
				$this->{'preset_'.$this->_name}();
		}
		$this->clear_cache();
		return parent::save();
	}
	public function remove(){
		if($this->id){
			$this->clear_cache();
			$res = $this->_row->delete();
		}else $res='no';
		return$res;
	}
	public function get($data){
		$res = array();
		foreach($data as $name=>$value){
			if($value1 = $this->__get($name))
				$res[$name] = $value1;
		}
		$res[$this->_primary]=$this->{$this->_primary};
		return$res;
	}
	public function exists(){
		return(!!$this->{$this->_primary});
	}
	protected function preset_site(){
		$this->time_begin = date('d/m/Y H:i');//time_creat set
	}
	protected function preset_bid(){
		$this->time_creat = date('c');//time_creat set
		
		$session = Zend_Registry::get('session');
		$manager = new Application_Model_Db_Any(array('user_id'=>$session->user_id),'manager');
		if(isset($this->_ref['manager_id']))$this->_ref['manager_id'] = $manager;
		if($manager->id)$this->manager_id = $manager->id;
		
		$select = $this->_table->select()->from($this->_name,'*')->order($this->_primary.' desc');
		$res = $this->_table->fetchRow($select);
		$this->bid_code = $this->increment_code($res->bid_code);//bid_code set
	}
	//2012-05-11T17:00:00.000Z - dojo
	//2012-05-13T13:13:05+02:00 - php
	public function increment_code($code,$n=3){
		$repeat = str_pad('0',$n,'0');
		$code = str_replace('-','',$code);
		$code++;
		$code = str_pad($code,$n*4,'0',STR_PAD_LEFT);
		$code = str_split($code,$n);
		$code = join('-',$code);
		$code = str_replace($repeat.'-','',$code);
		return$code;
	}
	protected function clear_cache(){
		$cache_tabgs = array($this->_name);
		foreach($this->_ref as $id=>$ref){
			$cache_tabgs[]= $ref->_name;
		}
		if($this->_name == 'refid')$cache_tabgs = $this->table_1.'_'.$this->table_2;
		Zend_Registry::get('file_cache')->clean($cache_tabgs);
	}
}

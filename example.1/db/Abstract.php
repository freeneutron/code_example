<?php
abstract class Application_Model_Db_Abstract{
	protected $_row;
	protected $_rows;
	protected $_table;
	protected $_name;
	protected $_primary;
	
	public function __construct($id=null,$table_name=null){
		$this->init($table_name);
		
		if($id){
			$select = $this->_table->select();
			if(is_array($id)){
				foreach($id as $name=>$value){
					$name = $this->alias($name);
					$select->where($name.'=?',$value);
				}
			}elseif(is_int($id) || is_string($id) && (int)$id){
				$select->where($this->_primary.'=?',$id);
			}
			$this->_row = $this->_table->fetchRow($select);
			//$this->_rows = $this->_table->fetchAll($this->_table->select()->where($name.'=?',$value));
		}
		if(!$this->_row){
			$this->_row = $this->_table->createRow();
			$this->_row->save();
		}
		if($this->alias('exte')){
			if($this->exte)
				$this->exte = unserialize($this->exte);
			else
				$this->exte = new Zend_Config(array(),true);
		}
	}
	protected function init($name=null){
		if($name===null){
			$class_name = explode('_',str_replace(str_replace('abstract','',strtolower(get_class())),'',strtolower(get_called_class())));
			$name = $class_name[0];
		}
		$this->_name = $name;
		$this->_primary = $name.'_id';
		$this->_table = new Zend_Db_Table(array(
			'name'=>$this->_name,
			'primary'=>$this->_primary,
		));
	}
	
	protected function alias($name1){
		$name2 = $this->_name.'_'.$name1;
		$metadata = $this->_table->info('metadata');
		$isset1 = isset($metadata[$name1]);
		$isset2 = isset($metadata[$name2]);
		if($isset2&&!$isset1)return$name2;
		if($isset1)return$name1;
	}
	public function save(){
		if($this->alias('exte')){
			$this->exte = serialize($temp = $this->exte);
			$res = $this->_row->save();
			$this->exte = $temp;
		}else
			$res = $this->_row->save();
		return $res;
	}
	
	public function __get($name){
		$alias = $this->alias($name);
		if($alias)return $this->_row->$alias;
	}
	
	public function __set($name,$value){
		$alias = $this->alias($name);
		if($alias)return $this->_row->$alias = $value;
		//elseif(substr($name))
	}
	public function get_row(){
		$res = array();
		$exte = $this->_name.'_exte';
		foreach($this->_row as $name=>$value){
			if($exte == $name && $value instanceof Zend_Config)
				$res[$name] = $value->toArray();
			else
				$res[$name] = $value;
		}
		return $res;
	}
}

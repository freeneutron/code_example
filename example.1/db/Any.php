<?php

class Application_Model_Db_Any extends Application_Model_Db_Abstract{
	protected $_ref = array();
	protected $_meta;
	
	public function __construct($id=null,$table_name=null){
		$this->_meta = Zend_Registry::get('meta');
		parent::__construct($id,$table_name);
	}
	public function save(){
		foreach($this->_ref as $id=>$ref){
			$this->$id = $ref->save();
		}
		return parent::save();
	}
	
	public function set($array){
		$blocked = array();
		foreach($array as $name=>$value){
			$this->save_word($name,$value);
			if($this->__set($name,$value) === null){
				$blocked[$name] = $value;
			}
		}
		foreach($blocked as $field=>$value){
			$part = explode('-',$field,2);
			$name = $part[0];
			if($name == ($this->_name).'_exte' && $part[1])
				$this->exte->{$field} = $value;
			elseif($id = $this->_meta->table->{$this->_name}->field_to->{$table = $this->_meta->field->$name}){
				$ref = $this->get_ref($id,$table);
				if($name == $table.'_exte' && $part[1])
					$ref->exte->{$field} = $value;
				elseif($ref->__set($name,$value) === null){/*error*/}
			}
		}
		return $this;
	}
	protected function save_word($name,$value){
		if($type = $this->_meta->autocomplete->$name){
			$scheme = array('word_type'=>$type,'word_name'=>$value);
			$word = new Application_Model_Db_Any($scheme,'word');
			if(!$word->id){
				$word->set($scheme)->save();
			}
		}
	}
	protected function get_ref($id,$table){
		return isset($this->_ref[$id])?$this->_ref[$id]:$this->_ref[$id] = new self($this->$id,$table);
	}
	public function test(){
		return$this->_row;
	}
	public function toArray(){
		$a = array();
		$_exte = $this->_name.'_exte';
		foreach($this->_row as $field=>$value){
			if($_exte == $field)foreach($value as $field_exte=>$value_exte){
				$a[$field_exte]=$value_exte;
			}else
				$a[$field]=$value;
		}
		return $a;
	}
}

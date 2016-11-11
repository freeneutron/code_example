<?php

class Application_Model_Db_Invite extends Application_Model_Db_Abstract{
	public function __construct($id=null){
		if(is_int($id) || is_string($id) && (int)$id)
			parent::__construct(array('code'=>$id));
		else
			parent::__construct($id);
		if(!$this->code)$this->code = $this->generate();
	}
	public function generate($n=4){
		$codes = array();
		foreach($this->_table->fetchAll($this->_table->select()) as $item){
			$codes[$item['invite_code']] = 1;
		}
		while(isset($codes[$code = $this->_generate($n)])){}
		return$code;
	}
	protected function _generate($n){
		$s = array();
		for($i=0;$i<$n;$i++){
			$s1 = array();
			for($j=0;$j<3;$j++)
				$s1[]= rand(1,10);
			sort($s1);
			for($j=0;$j<3;$j++)
				$s1[$j]%= 10;
			$s[]= join($s1);
		}
		return join('i',$s);
	}
}

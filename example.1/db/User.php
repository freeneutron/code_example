<?php

class Application_Model_Db_User extends Application_Model_Db_Abstract{
	public function __construct($id=null,$table_name=null){
		parent::__construct($id,$table_name);
		if(!$this->role)$this->role= 'role_guest';
		if($this->user_id== 1){
			$this->email= 'e322@ya.ru';
			$this->name= 'Admin';
			$this->pass= '1111';
			$this->role= 'role_admin';
			$this->save();
		}
	}
}

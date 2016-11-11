<?php
class Application_Model_File{
	protected $type_dir = array();
	protected $error = array();
	protected $chmode = 0777;
	protected $user_id;
	protected $win = true; //Смена кодировки для Русской Windows 7
	
	function __construct($user_id){
		$config = Zend_Registry::get('config');
		$this->type_dir = $config->type_dir->toArray();
		$this->type_dir = array_map(array($this,'align_slash'),$this->type_dir);
		$this->user_id = $user_id;
	}
	protected function align_slash($string){
		return preg_replace('/[\/\\\\]+/','/',$string);
	}
	function get_file($option=array()){
		$dir = array();
		$option['path'] = trim($this->align_slash($option['path']),'/');
		if($this->valid_option1($option)){
			$path = $this->type_dir[$option['type']].'/'.$this->user_id.'/'.$option['path'];
			if(is_file($path))readfile($path);
			else $this->error['file-not-exists']= $option['path'];
		}
		return $dir;
	}
	function get_dir($option=array()){
		$dir = array();
		$option['path'] = trim($this->align_slash($option['path']),'/');
		if($this->valid_option1($option)){
			$path = $this->type_dir[$option['type']].'/'.$this->user_id.'/'.$option['path'];
			if(file_exists($path) && is_dir($path)){
				$path_list = array(array('path'=>$path,'link'=>&$dir));
				for($i=0; $i<count($path_list); $i++){
					$path = $path_list[$i]['path'];
					$link = &$path_list[$i]['link'];
					foreach($this->scandir($path) as $name){
						$path1 = "$path/$name";
						if($this->win)$name = $this->from_fs_format($name);
						if(is_dir($path1)){
							$link[$name] = array();
							$path_list[]= array('path'=>$path1,'link'=>&$link[$name]);
						}elseif(is_file($path1)){
							$link[$name] = 'file';
						}
					}
				}
				$dir = $this->json_path($option['path'],$dir);
			}
		}
		return $dir;
	}
	function upload($option=array()){
		$dir = array();
		$option['path'] = trim($this->align_slash($option['path']),'/');
		if($this->valid_option($option)){
			$path = $this->type_dir[$option['type']].'/'.$this->user_id.'/'.$option['path'];
			if($this->win)$path = $this->to_fs_format($path);
			$pathinfo = pathinfo($path);
			$this->md($pathinfo);
			if(!isset($pathinfo['extension']) && isset($option['extension']))$pathinfo['extension'] = $option['extension'];
			$dir_name = rtrim($pathinfo['dirname'],'/');
			$file_name = $pathinfo['filename'].(isset($pathinfo['extension'])? '.'.$pathinfo['extension']: '');
			if(isset($option['tmp_name']) && is_uploaded_file($option['tmp_name'])){
				$res = move_uploaded_file($option['tmp_name'], "$dir_name/$file_name");
				if($res)$dir = $this->json_path(pathinfo($option['path'],PATHINFO_DIRNAME),array($file_name=>'file'));
				else $this->error[]='error: move_uploaded_file';
			}elseif(isset($option['file'])){
				$res = file_put_contents("$dir_name/$file_name",$option['file']);
				if($res)$dir = $this->json_path(pathinfo($option['path'],PATHINFO_DIRNAME),array($file_name=>'file'));
				else $this->error[]="error: file_put_contents: $dir_name/$file_name";
			}
		}
		return $dir;
	}
	function delete($option=array()){
		$dir = array();
		$option['path'] = trim($this->align_slash($option['path']),'/');
		if($this->valid_option($option)){
			$path = $this->type_dir[$option['type']].'/'.$this->user_id.'/'.$option['path'];
			if($this->win)$path = $this->to_fs_format($path);
			$result = false;
			if(is_file($path)){
				$result = unlink($path);
			}elseif(is_dir($path)){
				$result = $this->rmdir($path);
			}else{
				$this->error[]='error: file not exists';
			}
			if($result){
				$pathinfo = pathinfo($option['path']);
				$dir_name = $pathinfo['dirname'];
				$file_name = $pathinfo['basename'];
				$dir = $this->json_path($dir_name,array($file_name=>'file'));
				if($dir_name){
					$path = $this->type_dir[$option['type']].'/'.$this->user_id.'/'.$dir_name;
					$result = false;
					foreach(array_reverse(explode('/',$dir_name)) as $name){
						if($this->is_empty_dir($path) && $name){
							$result = rmdir($path);
							$path = substr($path,0,-strlen($name)-1);
						}else{
							break;
						}
					}
				}
			}
		}
		return $dir;
	}
	protected function rmdir($path){
		if($dh = opendir($path)){
			while($path1 = readdir($dh)){
				if($path1 == '.' || $path1 == '..')continue;
				$path2 = "$path/$path1";
				if(is_file($path2))$result = unlink($path2);
				else $result = $this->rmdir($path2);
				if(!$result)return false;
			}
			closedir($dh);
			return rmdir($path);
		}
	}
	protected function is_empty_dir($path){
		if($dh = opendir($path)){
			$result = true;
			while($path1 = readdir($dh))if($path1 != '.' && $path1 != '..'){
				$result = false;
				break;
			}
			closedir($dh);
			return$result;
		}
	}
	protected function json_path($path,$dir = array()){
		foreach(array_reverse(explode('/',$path)) as $name){
			if(strlen($name))$dir = array($name=>$dir);
		}
		return $dir;
	}
	protected function scandir($path){
		$handle = opendir($path);
		$dir = array();
		while(false !== ($entry = readdir($handle))){
			if($entry != "." && $entry != ".."){
				$dir[]= $entry;
			}
		}
		closedir($handle);
		return $dir;
	}
	protected function md(&$pathinfo){
		$dirname = explode('/',$pathinfo['dirname']);
		$dir = '';
		for($i=0; $i<count($dirname); $i++){
			if($dirname[$i] == '..'){
				array_splice($dirname,$i-1,2);
				$i-=2;
			}
		}
		for($i=0; $i<count($dirname); $i++){
			{
				$dir.= $dirname[$i].'/';
				if(!file_exists($dir)){
					if($this->win)$dir = $this->to_fs_format($dir);
					mkdir($dir,$this->chmode);
				}
			}
		}
		return $pathinfo['dirname'] = $dir;
	}
	protected function to_fs_format($name){
		//if($this->win)$name = iconv("utf-8", "windows-1251",$name);
		//$name = urlencode($name);
		return$name;
	}
	protected function from_fs_format($name){
		//if($this->win)$name = iconv("windows-1251", "utf-8",$name);
		//$name = urldecode($name);
		return$name;
	}
	protected function valid_option($option){
		if(!$this->user_id){
			$this->error[]='!$this->user_id';
			return;
		}
		if(!isset($option['type'])){
			$this->error[]='!isset($option[type])';
			return;
		}
		if(!isset($this->type_dir[$option['type']])){
			$this->error[]='!isset($type_dir[$option[type]])';
			return;
		}
		if(!isset($option['path']) || !$option['path']){
			$this->error[]='!isset($option[path]) || !$option[path]';
			return;
		}
		if(strpos($option['path'],'/.') !== false){
			$this->error[]='strpos($option[path],/.) !== false';
			return;
		}
		return true;
	}
	protected function valid_option1($option){
		if(!$this->user_id){
			$this->error[]='!$this->user_id';
			return;
		}
		if(!isset($option['type'])){
			$this->error[]='!isset($option[type])';
			return;
		}
		if(!isset($this->type_dir[$option['type']])){
			$this->error[]='!isset($type_dir[$option[type]])';
			return;
		}
		return true;
	}
	function get_error(){
		if($this->error)return$this->error;
	}
}

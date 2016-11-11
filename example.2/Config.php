<?php

class Application_Model_Config extends Zend_Config{
	
	const EXTENDS_ARG_SPLITTER = '@';
	const EXTENDS_PLACE        = '#';
	
	public function __construct($config, $allowModifications = true){
		if(is_string($config))
			$config = $this->load_config($config);
		if($config instanceof Zend_Config){
			$config = $config->toArray();
		}
        $this->_allowModifications = (boolean) $allowModifications;
        $this->_loadedSection = null;
        $this->_index = 0;
        $this->_data = array();
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->_data[$key] = new self($value, $this->_allowModifications);
            } else {
                $this->_data[$key] = $value;
            }
        }
        $this->_count = count($this->_data);
		$this->correct($this);
	}
	
	protected function correct($config){
		$is_extends = true;
		$i1 = 0;
		while($is_extends){
			$is_extends = false;
			foreach($config as $name=>$value){
				//if('action_group_0_0' == $name && $i1++>1)S::dump($value->toArray(),1);
				if($value instanceof Zend_Config && $value->extends){
					$value->extends = $this->get_extends($value->extends);
					
					foreach($value->extends as $extends=>$arg)if($arg){
						//$extends = $arg->{0};
						
						$extends_part = explode('.',$extends);
						
						if($config->{$extends_part[0]} && $extends_part[0] != $name){
							$is_extends = true;
							
							if(count($extends_part) > 1){
								$ref_new = $new = new self(array());
								$ref_config = $config->{$extends_part[0]};
								for($i=1;$i+1 < count($extends_part);$i++){
									$ref_new    = $ref_new->   {$extends_part[$i]} = new self(array());
									$ref_config = $ref_config->{$extends_part[$i]};
								}
								$ref_new->{$extends_part[$i]} = clone $ref_config->{$extends_part[$i]};
							}else
								$new = clone $config->{$extends_part[0]};
								
							
							if(count($arg)>1){
								$arg1 = array();
								foreach($arg as $name1=>$value1)if($name1){
									$arg1[self::EXTENDS_PLACE.$name1] = $value1;
								}
								$new->replace($arg1);
							}
							
							if($new->extends)$new->extends = $this->get_extends($new->extends);
							unset($value->extends->$extends);
							$value = $new->merge($value);
						}
					}
					$config->$name = $value;
				}
			}
		}
		return $config;
	}
	
	protected function get_extends($extends){
		$extends2 = $extends;
		if(is_array($extends))
			$extends = new self($extends,true);
		elseif(is_string($extends))
			$extends = new self(array($extends),true);
		if($extends instanceof Zend_Config){
			$extends->krsort();
			$first = $extends->current();
			if(!(is_array($first) || $first instanceof Zend_Config)){
				$extends1 = $extends;
				$extends = new self(array(),true);
				foreach($extends1 as $name=>$value){
					//if(is_object($value))S::dump($extends1,1);
					$value = explode(self::EXTENDS_ARG_SPLITTER,$value);
					$extends->{$value[0]} = $value;
					//$extends->add(explode(self::EXTENDS_ARG_SPLITTER,$value));
				}
			}
		}else
			$extends = new self(array(),true);
		return$extends;
	}
	protected function get_value_list($value){
		return$value;
		$ret = array();
		if($value instanceof Zend_Config)
			foreach($this->get_value_list($value) as $value1)
				$ret[]= $value1;
		else
			$ret[]= $value;
		return $ret;
	}
	
	public function krsort($sort_flags = null){
		if($sort_flags)
			krsort($this->_data,$sort_flags);
		else
			krsort($this->_data);
	}
	
	public function load_config($config){
		$config = new Zend_Config_Ini(
			APPLICATION_PATH.'/configs/'.$config.'.ini',
			null,
			true
		);
		return$config;
	}
	
	public function replace(array $replace){
		foreach($this as $name=>$value){
			if($value instanceof Zend_Config){
				$value->replace($replace);
			}elseif(is_string($value)){
				$this->$name = strtr($value,$replace);
			}
		}
	}
	
	public function json($options = 0){
		return json_encode($this->toArray(),$options);
	}
	
    public function merge(Zend_Config $merge)
    {
        foreach($merge as $key => $item) {
            if(array_key_exists($key, $this->_data)) {
                if($item instanceof Zend_Config && $this->$key instanceof Zend_Config) {
                    $this->$key = $this->$key->merge(new self($item->toArray(), !$this->readOnly()));
                } else {
                    $this->$key = $item;
                }
            } else {
                if($item instanceof Zend_Config) {
                    $this->$key = new self($item->toArray(), !$this->readOnly());
                } else {
                    $this->$key = $item;
                }
            }
        }

        return $this;
    }
    
    public function add($add){
    	$tjos->_data[]= $add;
    }
    
}




<?php

abstract class JO_Cache_Abstract  {
	
	public function __construct($options = array()) {
		if(is_array($options)) {
			foreach($options AS $key => $value) {
				$method = 'set' . ucfirst(strtolower($key));
				if(method_exists($this, $method)) {
					$this->$method($value);
				}
			}
		}
	}

//	abstract public function store($key, $data);
//	abstract public function add($key, $data);
//	abstract public function get($key);
//	abstract public function clear();
//	abstract public function delete($key);
//	abstract public function deleteRegExp($regExp);
//	abstract public function deleteStrPos($pos);
//	abstract public function deleteExpired();

	
	public function store($key, $data){}
	public function add($key, $data){}
	public function get($key){}
	public function clear(){}
	public function delete($key){}
	public function deleteRegExp($regExp){}
	public function deleteStrPos($pos){}
	public function deleteExpired(){}
	
}
<?php

class JO_Cache_Apc extends JO_Cache_Abstract {

	private $is_apc = false;
	
	private $live_time = 3600;
	
	public function __construct($options = array()) {
		parent::__construct($options);
		if(function_exists('apc_fetch')) {
			$this->is_apc = true;
		}
		$this->deleteExpired();
	}
	
	public function setLiveTime($time) {
		$this->live_time = $time;
		return $this;
	}
	
	public function getLiveTime() {
		return $this->live_time;
	}
	
	public function isCacheEnable() {
		return (bool)$this->is_apc;
	}
	
	public function store($key, $data) {
		if($this->is_apc) {
			$this->delete($key);
			return apc_store($key, $data, $this->getLiveTime());
		}
		return false;
	}
	
	public function add($key, $data) {
		if($this->is_apc) {
			$this->delete($key);
			return apc_add($key, $data, $this->getLiveTime());
		}
		return false;
	}
	
	public function get($key) {
		if($this->is_apc) {
			$status = false;
			$data = apc_fetch($key, $status);
			if($status) {
				return $data;
			}
			return false;
		}
		return false;
	}
	
	public function clear() {
		if($this->is_apc) {
			$info = apc_cache_info("user");
			$deleted = array();
			foreach ($info['cache_list'] as $obj) {
			    $deleted[] = apc_delete($obj['info']);
			}
			return in_array(false, $deleted) ? false : true;
		}
		return false;
	}
	
	public function delete($key) {
		if($this->is_apc) {
			return apc_delete($key);
		}
		return false;
	}
	
	public function deleteRegExp($regExp) {
		if($this->is_apc && class_exists('APCIterator', false)) {
			$toDelete = new APCIterator('user', $regExp, APC_ITER_VALUE);
			return apc_delete($toDelete);
		}
		return false;
	}
	
	public function deleteStrPos($pos) {
		if($this->is_apc) {
			$info = apc_cache_info("user");
			$deleted = array();
			foreach ($info['cache_list'] as $obj) {
				if(strpos($obj['info'], $pos) !== false) {
			    	$deleted[] = apc_delete($obj['info']);
				}
			}
			return in_array(false, $deleted) ? false : true;
		}
		return false;
	}
	
	public function deleteExpired() {
		if($this->is_apc) {
			$info = apc_cache_info("user");
		    foreach ($info['cache_list'] as $obj) {
				if($obj['creation_time'] < (time() - $this->getLiveTime())) {
					apc_delete($obj['info']);
				}
		    }
		}
	}
	
}
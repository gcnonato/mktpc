<?php

class JO_Cache_File extends JO_Cache_Abstract {

	private $is_writable = false;
	
	private $cache_dir;
	
	private $live_time = 3600;
	
	public function __construct($options = array()) {
		parent::__construct($options);
		
		$host = str_replace('www.','',JO_Request::getInstance()->getServer('HTTP_HOST'));
		
		$cache_folder = BASE_PATH . '/cache/' . $host . '/';
		
		if(!file_exists($cache_folder . 'data') || !is_dir($cache_folder . 'data') || !is_writable($cache_folder . 'data')) {
			if(mkdir($cache_folder . 'data', 0777, true)) {
				$this->is_writable = true;
			}
		} else {
			$this->is_writable = true;
		}
		
		$this->cache_dir = $cache_folder . 'data/';
		
		//$this->deleteExpired();
	}
	
	public function setLiveTime($time) {
		$this->live_time = $time;
		return $this;
	}
	
	public function getLiveTime() {
		return $this->live_time;
	}
	
	public function isCacheEnable() {
		return (bool)$this->is_writable;
	}
	
	public function store($key, $data) {
		if($this->is_writable) {
			$this->delete($key);
			return file_put_contents($this->cache_dir . $key, serialize($data));
		}
		return false;
	}
	
	public function add($key, $data) {
		if($this->is_writable) {
			$this->delete($key);
			return @file_put_contents($this->cache_dir . $key, serialize($data));
		}
		return false;
	}
	
	public function get($key) {
		if($this->is_writable) {
			if(file_exists($this->cache_dir . $key)) {
				if(filemtime($this->cache_dir . $key) < (time() - $this->getLiveTime())) {
					$this->delete($key);
					return false;
				} else {
					return unserialize(file_get_contents($this->cache_dir . $key));	
				}
			}
			return false;
		}
		return false;
	}
	
	public function clear() {
		if($this->is_writable) {
			$info = glob($this->cache_dir);
			$deleted = array();
			if(is_array($info)) {
				foreach ($info as $obj) {
				    $deleted[] = unlink($obj);
				}
			}
			return in_array(false, $deleted) ? false : true;
		}
		return false;
	}
	
	public function delete($key) {
		if($this->is_writable) {
			if(file_exists($this->cache_dir . $key)) {
				return @unlink($this->cache_dir . $key);	
			}
		}
		return false;
	}
	
	public function deleteRegExp($regExp) {
		if($this->is_writable) {
			$info = glob($this->cache_dir . '*' . $regExp . '*');
			$deleted = array();
			if(is_array($info)) {
				foreach ($info as $obj) {
				    $deleted[] = @unlink($obj);
				}
			}
			return in_array(false, $deleted) ? false : true;
		}
		return false;
	}
	
	public function deleteStrPos($pos) {
		if($this->is_writable) {
			$info = glob($this->cache_dir . '*' . $pos . '*');
			$deleted = array();
			if(is_array($info)) {
				foreach ($info as $obj) {
				    $deleted[] = @unlink($obj);
				}
			}
			return in_array(false, $deleted) ? false : true;
		}
		return false;
	}
	
	public function deleteExpired() {
		if($this->is_writable) {
			$info = glob($this->cache_dir . '*');
			$deleted = array();
			if(is_array($info)) {
				foreach ($info as $obj) {
					if(filemtime($obj) < (time() - $this->getLiveTime())) {
				    	$deleted[] = @unlink($obj);
					}
				}
			}
			return in_array(false, $deleted) ? false : true;
		}
	}
	
}
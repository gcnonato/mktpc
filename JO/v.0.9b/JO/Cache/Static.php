<?php

class JO_Cache_Static extends JO_Cache_Abstract {

	private $is_writable = false;
	
	private $cache_dir;
	
	private $live_time = 3600;
	
	private $cache_name;
	
	private $cache_path;
	
	private $ignore_cache = array();
	
	public function __construct($options = array()) {
		parent::__construct($options);
		
		$cache_host = str_replace('www.','',JO_Request::getInstance()->getServer('HTTP_HOST'));
		
		$request_path = trim(JO_Request::getInstance()->getFullUri(),' /');
		$request_path = str_replace(array('?','&',' '),'/',$request_path);
		$request_path = date('Y-m-d') . '/' . JO_Request::getInstance()->getController() . '/' . $request_path;
		$request_path = preg_replace('/([\/]{2,})/','/',$request_path);
		$request_path = trim($request_path, '/');
		$request_path = self::fixEncoding($request_path);
		if(strpos($request_path,'/') !== false) {
			$path = dirname($request_path) . '/';
			$tmp = explode('/', $request_path);
			$name = $this->clearString(end($tmp));
			$name = ($name == 'index' ? ($name . '.' . mt_rand(00,5)) : $name);
			$this->cache_name =  $name . '.cache';
		} else {
			$path = '';
			$name = $this->clearString(trim($request_path) ? $request_path : 'home');
			$name = ($name == 'index' ? ($name . '.' . mt_rand(00,5)) : $name);
			$this->cache_name = $name . '.cache';
		}
		
		$folder = '';
		if(class_exists('WM_Currency')) {
			$folder =  '/' . WM_Currency::getCurrencyCode();
		}
		
		$cache_folder = BASE_PATH . '/cache/' . $cache_host . '/' . JO_Locale::findLocale() . $folder . '/' . $path;
		if(!file_exists($cache_folder) || !is_dir($cache_folder)) {
			if(!file_exists($cache_folder) || !is_dir($cache_folder)) {
				if(@mkdir($cache_folder, 0777, true)) {
					$this->is_writable = true;
				}
			} else {
				$this->is_writable = true;
			}
		} elseif(is_writable($cache_folder)) {
			$this->is_writable = true;
		}
		$this->cache_dir = $cache_folder;
		$this->cache_path = BASE_PATH . '/cache/' . $cache_host . '/';
		
		$this->ignore_cache[] = 'vieworder';
		$this->ignore_cache[] = 'prices';
		$this->ignore_cache[] = 'cron';
		$this->ignore_cache[] = 'bulidUrl';
		$this->ignore_cache[] = 'latest_view';
		
		
		if(in_array(JO_Request::getInstance()->getController(),$this->ignore_cache)) {
			$this->is_writable = false;
		} 
		if(in_array(JO_Request::getInstance()->getAction(),$this->ignore_cache)) {
			$this->is_writable = false;
		} 

	}
	
	// Fixes the encoding to uf8 
	public static function fixEncoding($in_str) { 
		$cur_encoding = mb_detect_encoding($in_str) ; 
		if($cur_encoding == "UTF-8" && mb_check_encoding($in_str,"UTF-8")) 
			return $in_str; 
		else 
			return utf8_encode($in_str); 
	} // fixEncoding 
	
	private function unlink_r($dir, $deleteRootToo = false, $only_expired = false) {
    	if(!$dh = @opendir($dir)) {
        	return false;
    	}
    	while (false !== ($obj = readdir($dh))) {
        	if($obj == '.' || $obj == '..') {
            	continue;
        	}

        	if(is_dir($dir . '/' . $obj)) {
        		$this->unlink_r($dir.'/'.$obj, true);
        	} else {
        		if($only_expired) {
        			if(filectime($dir . '/' . $obj) < (time() - $this->getLiveTime())) {
        				@unlink($dir . '/' . $obj);
        			}
        		} else {
        			@unlink($dir . '/' . $obj);
        		}
        	}
    	}
    	closedir($dh);
    	if ($deleteRootToo) {
        	@rmdir($dir);
    	}
    	return true;
	}
	
	private function clearString($string) {
		$string = preg_replace('/[^a-z0-9а-яА-Я\-\=\+\&\[\]\:\.]+/ium','-', $string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '- ');
	}
	
	public function setLiveTime($time) {
		$this->live_time = $time;
		return $this;
	}
	
	public function getLiveTime() {
		return $this->live_time;
	}
	
	public function getCacheFile($key = false) {
		if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
			return $this->cache_dir . ($key ? $key : $this->cache_name);
		} else {
			return false;
		}
	}
	
	public function getCacheFileCTime($key = false) {
		if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
			return filectime($this->cache_dir . ($key ? $key : $this->cache_name));
		} else {
			return false;
		}
	}
	
	public function getCacheFileMTime($key = false) {
		if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
			return filemtime($this->cache_dir . ($key ? $key : $this->cache_name));
		} else {
			return false;
		}
	}
	
	public function getCacheFileExpireTime($key = false) {
		if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
			return filectime($this->cache_dir . ($key ? $key : $this->cache_name)) + $this->getLiveTime();
		} else {
			return false;
		}
	}
	
	public function isCacheEnable() {
		return (bool)$this->is_writable;
	}
	
	public function store($key = false, $data) {
		if($this->isCacheEnable()) {
			$this->delete(($key ? $key : $this->cache_name));
			return @file_put_contents($this->cache_dir . ($key ? $key : $this->cache_name), $data);
		}
		return false;
	}
	
	public function add($key = false, $data) {
		if($this->isCacheEnable()) {
			$this->delete(($key ? $key : $this->cache_name));
			return @file_put_contents($this->cache_dir . ($key ? $key : $this->cache_name), $data);
		}
		return false;
	}
	
	public function get($key = false) {
		if($this->isCacheEnable()) { 
			if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
				$file_c_time = filemtime($this->cache_dir . ($key ? $key : $this->cache_name));
				if($file_c_time < (time() - $this->getLiveTime())) {
					$this->delete(($key ? $key : $this->cache_name));
					return false;
				} else {
					if(date('Ymd',$file_c_time) < date('Ymd')) {
						$this->delete(($key ? $key : $this->cache_name));
						return false;
					} else {
						ob_start();
						include $this->cache_dir . ($key ? $key : $this->cache_name);
						return ob_get_clean();
					}	
				}
			}
			return false;
		}
		return false;
	}
	
	public function clear() {
		if($this->isCacheEnable()) {
			return $this->unlink_r($this->cache_path);
		}
		return false;
	}
	
	public function delete($key = false) {
		if($this->isCacheEnable()) {
			if(file_exists($this->cache_dir . ($key ? $key : $this->cache_name))) {
				return @unlink($this->cache_dir . ($key ? $key : $this->cache_name));	
			}
		}
		return false;
	}
	
	public function deleteRegExp($regExp) {
		return $this->deleteStrPos($regExp);
	}
	
	public function deleteStrPos($pos, $patch = false) {
		$dir = ($patch ? $patch : $this->cache_path);
		if(!$dh = @opendir($dir)) {
        	return false;
    	}
    	while (false !== ($obj = readdir($dh))) {
        	if($obj == '.' || $obj == '..') {
            	continue;
        	}
			
    		if(is_dir($dir . '/' . $obj)) {
    			if(is_array($pos)) {
    				foreach($pos AS $pos1) { 
        				$this->deleteStrPos($pos1, $dir . '/');
        			}
    			} else {
	    			if(mb_strpos($obj, $pos, 0, 'utf-8') !== false) { 
	        			$this->unlink_r($dir.'/'.$obj, true);
	    			} else {
	    				$this->deleteStrPos($pos, $dir.'/'.$obj);
	    			}
    			}
        	} else {
        		if(is_array($pos)) {
        			foreach($pos AS $pos1) {
        				$this->deleteStrPos($pos1, $dir . '/');
        			}
        		} else {
	        		if(mb_strpos($obj, $pos, 0, 'utf-8') !== false) {
	        			@unlink($dir . '/' . $obj);
	        		}
        		}
        	}
    	}
    	closedir($dh);
    	
    	return true;
	}
	
	public function deleteExpired() {
		if($this->isCacheEnable()) {
			return $this->unlink_r($this->cache_path, false, true);
		}
		return false;
	}
	
}
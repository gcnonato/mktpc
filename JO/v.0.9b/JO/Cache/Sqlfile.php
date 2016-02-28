<?php

class JO_Cache_Sqlfile extends JO_Cache_Abstract {

	private $is_writable = false;
	
	private $cache_dir;
	
	private $live_time = 3600;
	
	private $tables;
	
	protected $info_table = '`information_schema`.`TABLES`';
	
	protected $is_update = false;
	
	public $cacheext = '.cache';
	
	private $Real_cache_name;
	
	private $db;
	
	public function __construct($options = array()) {
		parent::__construct($options);
		ini_set('date.timezone', 'Europe/Sofia');
		$cache_folder = BASE_PATH . '/cache/' . JO_Request::getInstance()->getServer('HTTP_HOST') . '/mysql_cache/';
		
		if(!file_exists($cache_folder . 'data/') || !is_dir($cache_folder . 'data/')) {
			if(@mkdir($cache_folder . 'data/', 0777, true)) {
				$this->is_writable = true;
			}
		} else {
			if(is_writable($cache_folder . 'data/')) {
				$this->is_writable = true;
			}
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
		return (bool)$this->is_apc;
	}
	
	public function store($query, $data) {
		$db = JO_Db::getDefaultAdapter();
		preg_match('/^(\w+)/i',$query, $match);
		
		if(!isset($match[1])) { return false; }
		
		$type = strtolower($match[1]); 
		switch($type) {
			case 'select':
				$this->tables = $this->queryAlias($query); 
			
				if(!count($this->tables)) { return; }
				
				$this->Real_cache_name = preg_replace('/`/','',implode('/', $this->tables)) . '/' . sprintf("%u", crc32($query)) . $this->cacheext;
				
				$this->smart_cache('WRITE', var_export($data, true));
				return $data;
				
			break;
			default:
				return false;
			break;
		}
	}
	
	public function add($key, $data) {
		return $this->store($key, $data);
	}
	
	public function get($query) {
		$db = JO_Db::getDefaultAdapter();
		preg_match('/^(\w+)/i',$query, $match);
		
		if(!isset($match[1])) { return false; }
		
		$type = strtolower($match[1]); 
		switch($type) {
			case 'select':
				$this->tables = $this->queryAlias($query); 
				$tmpar = array();
				if(!count($this->tables)) { return; }
				$query1 = '';
				foreach($this->tables AS $k=>$table) {
					$table = str_replace('`','',$table);
					$query1 .= $query1 ? ' OR ' : '';
					if(strpos($table, '.') !== false) {
						$query1 .= "(`TABLE_SCHEMA`='".array_shift(explode('.', $table))."' AND `TABLE_NAME`='".end(explode('.', $table))."')";
					} else {
						$query1 .= "(";
						if($this->db) {
							$query1 .= "`TABLE_SCHEMA`='".$this->db."' AND ";
						}
						$query1 .= "`TABLE_NAME`='".$table."')";
					}
					
				}
				
				$this->Real_cache_name = preg_replace('/`/','',implode('/', $this->tables)) . '/' . sprintf("%u", crc32($query)) . $this->cacheext;

				if($query1) {
					$query1 = "(" . $query1 . ") AND `UPDATE_TIME`>'".date("Y-m-d H:i:s", $this->getCacheFileCTime())."'";
					
					$r = $db->fetchOne("SELECT UPDATE_TIME FROM {$this->info_table} WHERE $query1 ORDER BY UPDATE_TIME DESC LIMIT 1");
					
					if($r && strtotime($r['UPDATE_TIME']) >= $this->getCacheFileCTime()) { 
						return false;
					}
				}
			
				if(file_exists($this->cache_dir . $this->Real_cache_name)) {
					return $this->var_import($this->smart_cache('READ'));
				}
				
			break;
		}
		return false;
	}
	
	public function clear() {
		
	}
	
	public function delete($key) {
		
	}
	
	public function deleteRegExp($regExp) {
		
	}
	
	public function deleteStrPos($pos) {
		
	}
	
	public function deleteExpired() {
		
	}
	
	public function queryAlias( $query ) {
		//$substr = strtolower($query);
		$substr = $query;
		//$substr = preg_replace ( '/\(.*\)/', "", $substr);
		$substr = preg_replace ( '/[^a-zA-Z0-9_,`.]/', ' ', $substr);
		$substr = preg_replace('/\s\s+/', ' ', $substr);
		$substr = strtolower(substr($substr, strpos(strtolower($substr),' from ') + 6));
		$substr = preg_replace(
					Array(
						'/ where .*+$/',
						'/ group by .*+$/',
						'/ limit .*+$/' ,
						'/ having .*+$/' ,
						'/ order by .*+$/',
						'/ into .*+$/'
					   ), "", $substr);

		$substr = preg_replace(
					Array(
						'/ left /',
						'/ right /',
						'/ inner /',
						'/ cross /',
						'/ outer /',
						'/ natural /',
						'/ as /'
					   ), ' ', $substr);
	   
		$substr = preg_replace(Array('/ join /', '/ straight_join /'), ',', $substr);
		$out_array = Array();
		$st_array = explode (',', $substr);
		foreach ($st_array as $col) {
		  $col = preg_replace(Array('/ on .*+/'), "", $col);
		  $tmp_array = explode(' ', trim($col));
		  if (!isset($tmp_array[0]))
			continue;
		  $first = $tmp_array[0];
		  if (isset($tmp_array[1]))
			$second = $tmp_array[1];
			else 
			$second = $first;
		   
		  if (strlen($first))
		   $out_array[str_replace('`','',$first)] = str_replace('`','',$first);
		 
		}
		return $out_array;
	}
	
	public function getCacheFileCTime($key = false) {
		if(file_exists($this->cache_dir . ($key ? $key : $this->Real_cache_name))) {
			return filectime($this->cache_dir . ($key ? $key : $this->Real_cache_name));
		} else {
			return 1;
		}
	}

	
	function smart_cache($type,$r=false){ 
		$path = $this->cache_dir . $this->Real_cache_name;
		if($type=='WRITE'){
			$dir=dirname($path);  if(!file_exists($dir)) { mkdir($dir,0777,true); }
			file_put_contents($path,$r);
		}else if($type=='READ'){ 
			if(file_exists($path)){	
				ob_start();
				include $path;
				return ob_get_clean();
			}
		}
	}
	
	function var_import( $data ) { 
		$ret = array();
		if($data) {
		    eval( '$ret='.$data.';' ) ;
		}
		return $ret;
	}
	
}

?>
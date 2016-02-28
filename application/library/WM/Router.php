<?php

class WM_Router {

	protected static $get_controller = false;

	public function __construct() {}
	
//	public static function routeModules($uri) {
//		$request = JO_Request::getInstance();
//		$parts = explode('/', trim($uri, '/'));
//		if($parts && in_array($parts[0], WM_Modules::getList())) {
//			$request->setModule($parts[0]);
//		}
//	}
	
	public static function route($uri) {
		
//		$parts = explode('/', trim($uri, '/'));
//		
//		$db = JO_Db::getDefaultAdapter();
//		$request = JO_Request::getInstance();
//		
//		$query = $db->select()
//					->from('url_alias');
//					
//		self::$get_controller = false;
//		
//		foreach($parts AS $part) {
//			$query->where('keyword = ?', $part);
//			
//			$results = $db->fetchRow($query);
//			
//			if($results) {
//				parse_str($results['query'], $data);
//				foreach ($data as $key => $value) { 
//					if($key == 'page_id' && $request->getRequest('page_id')) {
//						$request->setParams($key, $request->getRequest('page_id') . '_' . $value);
//					} elseif($key == 'controller') {
//						self::$get_controller = true;
//						$request->setController($value);
//					} else {
//						$request->setParams($key, $value);
//					}
//					
//				//	if(!self::$get_controller) {
//						if($key == 'page_id') {
//							$request->setController('pages')->setAction('index');
//						} 
//				//	}
//					
//				}
//			}
//			$query->reset(JO_Db_Select::WHERE);
//		}
	}
	
	public static function create($link) {
		
		static $cached_link = array();
		static $modules;
		
		if(isset($cached_link[$link])) return $cached_link[$link];
		
		$request = JO_Request::getInstance();
		
		if(!isset($modules)) {
			$modules = WM_Modules::getList(array('admin','update','install'));
		}
		
		$db = JO_Db::getDefaultAdapter();
	
		$created = false;
		
		$url_data = parse_url(str_replace('&amp;', '&', $link));
		
		$url = ''; 
		
		$data = array();
		if(isset($url_data['query'])) {
			parse_str($url_data['query'], $data);
		}
		foreach ($data as $key => $value) {
			if($key == 'name') {
				$keyword = self::clear($value);
				if($keyword) {
					$url .= '/'. $keyword;
					unset($data[$key]);
				}
				$created = true;
			} elseif($key == 'category_id') {
				$keyword = is_numeric($value) ? $value : self::clear($value);
				if($keyword) {
					//$url .= (is_numeric($value) ? '/' . $value : '') . '/' . $keyword;
					$url .= '/' . $keyword;
					unset($data[$key]);
				}
				$created = true;
			} elseif($key == 'item_id') {
				$keyword = $value; //self::getKeyword('items', 'id', $value, 'name');
				if($keyword) {
					$url .= '/' . $value; // . '/' . $keyword . '.html';
					unset($data[$key]);
				}
				$created = true;
			} elseif($key == 'collection_id') {
				$url .= '/' . $value;
				unset($data[$key]);
				$created = true;
			} elseif($key == 'page_id') {
				$keyword = $value; //self::getKeyword('pages', 'id', $value, 'key');
			//	if(!$keyword) {
			//		$keyword = self::getKeyword('pages', 'id', $value, 'name');
			//	}
				if($keyword) {
					$url .= '/' . $keyword; // . '/' . $keyword . '.html';
					unset($data[$key]);
				}
				$created = true;
			} elseif($key == 'controller') {
				if($value != 'index') {
					$url .= '/' . $value;
				} elseif(isset($data['action']) && $data['action'] != 'index') {
					$url .= '/' . $value;
				}
				unset($data[$key]);
			} elseif($key == 'action') {
				if($value != 'index') {
					$url .= '/' . $value;
				}
				unset($data[$key]);
			} elseif($key == 'page') {
				$url .= '/page/' . $value;
				unset($data[$key]);
			} elseif($key == 'sort') {
				$url .= '/sort/' . $value;
				unset($data[$key]);
			} elseif($key == 'order') {
				$url .= '/order/' . $value;
				unset($data[$key]);
			} elseif($key == 'username') {
				$url .= '/' . $value;
				unset($data[$key]);
			} elseif($key == 'tag') {
				$url .= '/' . urlencode($value); //(is_array($value) ? implode(':',$value) : $value);
				unset($data[$key]);
			} elseif($key == 'setFile') {
				$url .= '/' . $value;
				unset($data[$key]);
			} elseif($key == 'extension') {
				$url .= '/extensions/' . $value;
				unset($data[$key]);
			} elseif($key == 'module') {
//				if($value != $request->getModule()) {
//					$url_data['host'] = $value . '.' . str_replace('www.','',$url_data['host']);
//				}
				if(count($modules) > 1) {
					$url .= '/' . $value;
				} elseif(in_array($value, array('update','install'))) {
					$url .= '/' . $value;
				}
				unset($data[$key]);
			}
			
		}
		
		if ($url) {
			
			$query = '';
			if ($data) {
				if($created) {
					if(isset($data['controller'])) {
						unset($data['controller']);
					}
					if(isset($data['action'])) {
						unset($data['action']);
					}
				}
				$query .= http_build_query($data);
				
//				foreach ($data as $key => $value) {
//					if($created && (($key == 'controller') || ($key == 'action'))) continue;
//					$query .= '&' . $key . '=' . $value;
//				}
				
				if ($query) {
					$query = '?' . trim($query, '&');
				}	
			}
			
			$result = $url . $query;
			
			$cached_link[$url_data['scheme'] . '://' . $url_data['host'] . (isset($url_data['port']) ? ':' . $url_data['port'] : '') . str_replace('/index.php', '', rtrim($url_data['path'], '/')) . $result] = true;
			
			return $url_data['scheme'] . '://' . $url_data['host'] . (isset($url_data['port']) ? ':' . $url_data['port'] : '') . str_replace('/index.php', '', rtrim($url_data['path'], '/')) . $result;
		} else {
			
			$cached_link[$link] = true;
			
			return $link;
		}
			
	}
	
	//////////////////////
	
	public static function clearName($name) {
		if(strpos($name, '&amp;') !== false || strpos($name, '?')) {
			$name = str_replace(array('&amp;', '?'), '-', $name);
		}
		
		if(strpos($name, '&') !== false) {
			$name = str_replace('&', '-', $name);
		}
		
		return $name;
	} 
	
	public static function getKeyword($table, $id, $value = 0, $name = 'name') {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from($table, $name)
					->where($id ." = ?", (int)$value)
					->limit(1);
		return self::clear($db->fetchOne($query));
	}
	
	private function clear($string) {
		$string = trim(strip_tags(trim($string)));
		$string = preg_replace ( '%[.,:\'"/\\\\[\]{}\%\-_!?]%simx', ' ', $string );
//		$string = preg_replace('/[^a-z0-9а-яА-Я\-]+/ium','-', $string);
		$string = preg_replace('/([ ]{1,})/','-',$string);
		$string = preg_replace('/([-]{2,})/','-',$string);
		return trim($string, '-');
	}
	
}

?>
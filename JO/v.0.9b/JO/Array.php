<?php

class JO_Array {
	
	public static function array_sort($array, $on, $order='SORT_DESC') {
		$new_array = array();
		$sortable_array = array();
 
		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}
 
			switch($order) {
				case 'SORT_ASC':
					asort($sortable_array);
				break;
				case 'SORT_DESC':
					arsort($sortable_array);
				break;
			}
 
			foreach($sortable_array as $k => $v) {
				$new_array[] = $array[$k];
			}
		}
		return $new_array;
	} 
	
	public static function array_search($needle, array $haystack) {
		if (empty($needle) || empty($haystack)) {
            return false;
        }
        
        $return = array();
        foreach($haystack AS $key => $value) {
        	if($needle === $value) {
        		$return[] = $key;
        	}
        }
        return count($return) > 0 ? $return : false;
	}
	
	public static function mb_strpos_array($haystack, $needles, $offset =0, $charset = 'utf-8') {
	    if ( is_array($needles) ) {
	        foreach ($needles as $str) {
	            if ( is_array($str) ) {
	                $pos = self::mb_strpos_array($haystack, $str, $offset, $charset);
	            } else {
	                $pos = mb_strpos($haystack, $str, $offset, $charset);
	            }
	            if ($pos !== FALSE) {
	                return $pos;
	            }
	        }
	    } else {
	        return mb_strpos($haystack, $needles, $offset, $charset);
	    }
	}
	
	public static function multi_array_search($array, $key, $value)
	{
	    $results = array();
	
	    if(is_array($array)){
	        if (isset($array[$key]) && $array[$key] == $value)
	            $results[] = $array;
	
	        foreach ($array as $subarray)
	            $results = array_merge($results, self::multi_array_search($subarray, $key, $value));
	    }
	
	    return $results;
	}

	public static function multi_array_to_single_uniq($array, $maxdepth = NULL, $depth = 0) {
		$single = array();
		
		if(!is_array($array)){ 
      		return $single;
    	}

    	$depth++; 
    	foreach($array as $key => $value) {
      		if(($depth <= $maxdepth || is_null($maxdepth)) && is_array($value)) {
        		$single = array_merge($single, self::multi_array_to_single_uniq($value, $maxdepth, $depth));
      		} else {
        		array_push($single, $value);
      		}
    	}
    	
		return array_unique($single);
	}
}

?>
<?php

class Model_Settings {

	public static function updateAll($data) {
		$db = JO_Db::getDefaultAdapter();
		if(is_array($data)) {
			foreach($data AS $group => $value) { 
				if(is_array($value)) {
					$db->delete('system', array('`group` = ?' => $group));
					foreach($value AS $key => $val) {
						$serialize = false;
						if(is_array($val)) {
							$serialize = true;
							$val = serialize($val);
						} 
						$db->insert('system', array(
							'group' => $group,
							'key' => $key,
							'value' => $val,
							'system' => (int) ($group == 'config'),
							'serialize' => $serialize
						));
					}
				}
			}
		}
	} 
	
	public static function getSettingsPairs($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');
		
		if(isset($data['filter_group']) && !is_null($data['filter_group'])) {
			$query->where('`group` = ?', $data['filter_group']);
		}
		
		$response = array();
		$results = $db->fetchAll($query);
		if($results) {
			foreach($results AS $result) {
				if($result['serialize']) {
					$response[$result['key']] = self::mb_unserialize($result['value']);
				} else {
					$response[$result['key']] = $result['value'];
				}
			}
		}
		
		return $response;
	}
	
	public static function getCountriesPairs() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('countrycode', array('iso2','name'));
		
		return $db->fetchPairs($query);
	}
  	
	public function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 
	
}

?>
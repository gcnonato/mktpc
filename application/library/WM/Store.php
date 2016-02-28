<?php

class WM_Store {
	
	public function getStoreSettings() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('system');
					
		return $db->fetchAll($query);
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
  	
	public function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	} 
	
}
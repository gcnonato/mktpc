<?php

class Model_Extensions {

	public static function isInstaled($key) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('extensions', 'COUNT(id)')
					->where('code = ?', (string)$key)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public function install($key) {
		
		if(self::isInstaled($key)) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		return $db->insert('extensions', array(
			'code' => $key
		));
	}
	
	public function uninstall($key) {
		
		if(!self::isInstaled($key)) {
			return;
		}
		
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('extensions', array(
			'code = ?' => $key
		));
	}
	
}

?>
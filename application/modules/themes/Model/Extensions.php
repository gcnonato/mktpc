<?php

class Model_Extensions {
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('extensions');
		return $db->fetchPairs($query);
	}	
}

?>
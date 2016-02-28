<?php

class Model_Extensions_Topbanner {
	
	public static function get($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('topbanner')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	public static function getRandom() {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('topbanner')
					->where("(`from` <= CURDATE() OR `from` = '0000-00-00') and (`to` >=  CURDATE() OR `to` = '0000-00-00')")
					->limit(1);
		
		$clbanner = JO_Request::getInstance()->getCookie('clbanner');
		if($clbanner == true) {
			$query->where("close = 'false'");
		}
		
		return $db->fetchRow($query);
	}
	
	public static function updateClicks($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('topbanner', array(
			'clicks' => new JO_Db_Expr('clicks + 1')
		), array('id = ?' => (int)$id));
	}

	public static function updateViews($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$db->update('topbanner', array(
			'views' => new JO_Db_Expr('views + 1')
		), array('id = ?' => (int)$id));
	}
	
}

?>
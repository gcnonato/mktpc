<?php

class Model_Extensions_Slider {

	public static function getSliderImages() {
		$db = JO_Db::getDefaultAdapter();
		
		$tables = $db->listTables();
		if(!in_array('slider', $tables)) return array();
		
		$query = $db->select()
					->from('slider')
					->where('visible =?', 'true')
					->order('slider.order_index ASC');
		
		return $db->fetchAll($query);
					 
	}
	
	public function getImage($image_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$tables = $db->listTables();
		if(!in_array('slider', $tables)) return array();
		
		$query = $db->select()
					->from('slider')
					->where('visible =?', 'true')
					->where('id = ?', (int)$image_id);
		
		return $db->fetchRow($query);
	}
	
}

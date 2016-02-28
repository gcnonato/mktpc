<?php

class WM_RecordsType {
	
	public static function getRecordsTypeMenu($parent_id = 0) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('records_type')
					->joinLeft('records_type_description', "records_type.record_type_id = records_type_description.record_type_id", array('title'))
					->where('language_id = ?', JO_Registry::get('config_language_id'))
					->where('records_type.parent_id = ?', (int)$parent_id)
					->order('records_type.sort_order ASC');
		$data_info = $db->fetchAll($query);
		$result = array();
		foreach($data_info AS $info) {
			$result[] = $info;
		}
		return $result;
	}

}

?>
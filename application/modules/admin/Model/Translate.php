<?php

class Model_Translate {
	
	public static function setTranslate($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
	    $trans = self::getTranslate($data['mod'], $data['lid']);
		foreach($trans as $t) {
		    $db->delete('language_translate', array('language_id = ?' => (int)$data['lid'], 'language_keywords_id = ?' => (int)$t['language_keywords_id']));
		}
		
		if(isset($data['translate'])) {
			foreach($data['translate'] AS $id => $value) {
				if($value) {
					$db->insert('language_translate', array(
						'language_id' => $data['lid'],
						'language_keywords_id' => $id,
						'keyword' => $value
					));
				}
			}
		}
	}
	
	public static function getTranslate($mod, $language_id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('language_keywords')
					->joinLeft('language_translate', 'language_keywords.language_keywords_id = language_translate.language_keywords_id AND language_translate.language_id = ' . (int)$language_id, array('translate' => 'keyword'))
					->where('module = ?',$mod)
					->order('language_keywords.keyword ASC');
					
		return $db->fetchAll($query);
	}

}

?>
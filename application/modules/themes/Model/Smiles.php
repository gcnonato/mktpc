<?php

class Model_Smiles {

	public static function getSmiles($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$tables = $db->listTables();
		if(!in_array('smiles', $tables)) return false;
		
		$query = $db->select()
					->from('smiles')
					->order('name ASC')
					->where("visible = 'true'");
	
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		}
		
		return $db->fetchAll($query);
	}
	
	public static function getTotalSmiles() {
		$db = JO_Db::getDefaultAdapter();
		
		$tables = $db->listTables();
		if(!in_array('smiles', $tables)) return false;
		
		$query = $db->select()
					->from('smiles', 'COUNT(id)')
					->where("visible = 'true'");
		
		return $db->fetchOne($query);
	}

	public static function getSmile($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$tables = $db->listTables();
		if(!in_array('smiles', $tables)) return false;
		
		$query = $db->select()
					->from('smiles')
					->where('id = ?', (int)$id);
		
		return $db->fetchRow($query);
	}
	
	
	public static function getSmilesImages() {
		$request = JO_Request::getInstance();
		$result = '';
		$smiles = self::getSmiles();
		
		foreach($smiles as $smile) {
			$parts = explode(',', $smile['code']);
			if(count($parts) > 1) {
				$result .= '<img src="'. $request->getBaseUrl() .'/uploads'. $smile['photo'] .'" alt="'. $smile['name'] .'" title="'. $parts[0] .'" class="emot" />';
			} else {
				$result .= '<img src="'. $request->getBaseUrl() .'/uploads'. $smile['photo'] .'" alt="'. $smile['name'] .'"  title="'. $smile['code'] .'" class="emot" />';
			}
		}
		
		return $result;
	}
}

?>
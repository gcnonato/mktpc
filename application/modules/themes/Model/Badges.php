<?php
class Model_Badges {
    public function getAllFront() {
		
		$db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(Model_Users::getPrefixDB().'badges')
					->where('visible = ?', 'true');
		

		$return = array();
		foreach($db->fetchAll($query) as $d){
			if($d['type'] == 'system') {
				$return[$d['type']][$d['sys_key']] = array(
					'name' => $d['name'],
					'photo' => $d['photo']
				);
			} elseif($d['type'] == 'other') {
				$return[$d['type']][$d['id']] = array(
					'name' => $d['name'],
					'photo' => $d['photo']
				);
			} else {
				if(strpos($d['from'], '+') !== false) {
					$key = (int)$d['from'] . '-2147483646';
				} else {
					$key = $d['from'] . '-' . $d['to'];
				}
				$return[$d['type']][$key] = array(
					'name' => $d['name'],
					'photo' => $d['photo']
				);
			}
		}
		
		return $return;
	}

	
}
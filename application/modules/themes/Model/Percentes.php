<?php
class Model_Percentes {
    public function getPercentRow($user) {
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(Model_Users::getPrefixDB().'users', 'commission_percent')
					->where('user_id= ?', $user['user_id'])
					->limit(1, 0);
					$user_data = $db->fetchRow($query);	

		if($user_data && round($user_data['commission_percent']) > 0) {
			return array('percent' => floatval($user_data['commission_percent']), 'to' => 0);
		}
		
		$no_exclusive_author_percent = 30;
		if(JO_Registry::get('no_exclusive_author_percent')) {
			$no_exclusive_author_percent = (int)JO_Registry::get('no_exclusive_author_percent');
		}
		
		$exclusive_author_percent = 40;
		if(JO_Registry::get('exclusive_author_percent')) {
			$exclusive_author_percent = (int)JO_Registry::get('exclusive_author_percent');
		}
		
		
		if($user['exclusive_author'] == 'false') {
			$percent = array('percent' => $no_exclusive_author_percent, 'to' => 0);
		}
		else {		
		    
		    $query = $db->select()
					->from(Model_Users::getPrefixDB().'percents')
					->where("`from` <= ? AND (`to` > ? OR `to` = 0)", $user['sold']);

			$data = $db->fetchRow($query);	
							
			
			if(count($data) == 0) {
				$percent = array('percent' => $exclusive_author_percent, 'to' => 0);
			}
			else {
				$percent = $data;			
			}
		}
		
		return $percent;
	} 
}
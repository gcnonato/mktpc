<?php
    
class Model_Percents {

	public static function createPercent($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert(Model_Users::getPrefixDB().'percents', array(
			'percent' => (int)$data['percent'],
			'from' => (int)$data['from'],
			'to' => (int)$data['to']
		));
		return $db->lastInsertId();
	}

	public static function editePercent($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update(Model_Users::getPrefixDB().'percents', array(
			'percent' => (int)$data['percent'],
			'from' => (int)$data['from'],
			'to' => (int)$data['to']
		), array('id = ?' => (int)$id));
		return $id;
	}

	public static function deletePercent($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete(Model_Users::getPrefixDB().'percents', array('id = ?' => (int)$id));
	}

    public static function getAll() {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from(Model_Users::getPrefixDB()."percents")
            ->order("percent ASC");
        return $db->fetchAll($query);
    }

    public static function getPercent($id) {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from(Model_Users::getPrefixDB()."percents")
            ->where("id = ?", (int)$id);
        return $db->fetchRow($query);
    }
    
    public static function getPercentRow($user_id) {
        
        $user_data = Model_Users::getUser($user_id);
        
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
        
        if($user_data['exclusive_author'] == 'false') {
            $percent = array('percent' => $no_exclusive_author_percent, 'to' => 0);
        } else {
            $db = JO_Db::getDefaultAdapter();   
            $query = $db->select()
                        ->from(Model_Users::getPrefixDB().'percents')
                        ->where("`from` <= ? AND (`to` > ? OR `to` = 0)", $user_data['sold']);
        
            $results = $db->fetchRow($query);
                
            if(!$results) { 
                $percent = array('percent' => $exclusive_author_percent, 'to' => 0);
            }
            else { 
                $percent = $results;            
            }
        }
        
        return $percent;
    } 
    
}
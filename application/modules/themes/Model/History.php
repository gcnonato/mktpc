<?php
class Model_History {
    public static function getAll($start=0, $limit=0, $where='') {
        
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from(Model_Users::getPrefixDB().'history')
					->limit($limit, $start)
					->order('datetime DESC');
        if($where!='') {
			$query->where($where);
		}			
		return $db->fetchAll($query);	
	
	}
	
	public static function add($action, $transactionID, $userID=0) {
		
		if($userID == 0) {
			$userID = JO_Session::get('user_id');
		}
		$db = JO_Db::getDefaultAdapter();
		$db->insert(Model_Users::getPrefixDB().'history', array(
    		'user_id'	=> $userID,
    		'action'	=> $action,
    		'transaction_id'	=> $transactionID,
    		'datetime'	=> new JO_Db_Expr('NOW()')
		));
		
		
		return true;
	}
	
}
<?php

class Model_Balance {
    
    public static function getTotalUserBalanceByType($id) {
        
        $db = JO_Db::getDefaultAdapter();   
        
        $query = $db->select()
                    ->from(Model_Users::getPrefixDB().'deposit', new JO_Db_Expr("SUM(IF(`paid` = 'true', 1, 0)) AS `paid`,SUM(IF(`paid` = 'false', 1, 0)) AS `not_paid`"))
                    ->where('user_id = ?', (int)$id)
                    ->group('user_id');
                    
        $result = $db->fetchRow($query);
        if(!$result) {
            return array('paid' => 0, 'not_paid' => 0);
        }
        
        return $result;
    }
    
}
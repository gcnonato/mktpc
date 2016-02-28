<?php
class Model_Quiz {
    public function getAllQuestions($start=0, $limit=0, $where='', $order='`order_index` ASC') {
        
    
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('quiz')
					->limit($limit, $start)
					->order($order);

        if($where) 
            $query->where($where);
            
        
		return $db->fetchAll($query);
	} 
	
	public function getAllAnswers($start=0, $limit=0, $where='', $byQuiz=false) {
        
    
        $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('quiz_answers')
					->limit($limit, $start)
					->order('id ASC');

        if($where) 
            $query->where($where);
            
        $return = array(); 
        
	    foreach($db->fetchAll($query) as $d) {
			if($byQuiz) {
				$return[$d['quiz_id']][$d['id']] = $d;
			}
			else {
				$return[$d['id']] = $d;
			}
		}
            
        
		return $return;
	} 
}
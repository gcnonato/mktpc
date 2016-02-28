<?php

class Model_Quiz {

	public static function createQuiz($data) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = (JO_Request::getInstance()->getRequest('sub_of') ? 'quiz_answers' : 'quiz' );
		if($table == 'quiz_answers') {
			$insert = array(
				'quiz_id' => (int)JO_Request::getInstance()->getRequest('sub_of'),
				'name' => $data['name'],
				'right' => ( isset($data['right']) ? 'true' : 'false' )
			);
		} else {
			$insert = array(
				'name' => $data['name'],
				'order_index' => self::getMaxOrderIndex((int)JO_Request::getInstance()->getRequest('sub_of'))
			);
		}
		
		$db->insert($table, $insert);
		return $db->lastInsertId();
	}

	public static function editeQuiz($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = (JO_Request::getInstance()->getRequest('sub_of') ? 'quiz_answers' : 'quiz' );
		if($table == 'quiz_answers') {
			
			if(isset($data['right'])) {
				$db->update('quiz_answers', array('right' => 'false'), array('quiz_id = ?' => (int)JO_Request::getInstance()->getRequest('sub_of')));
			}
			
			$insert = array(
				'name' => $data['name'],
				'right' => ( isset($data['right']) ? 'true' : 'false' )
			);
		} else {
			$insert = array(
				'name' => $data['name']
			);
		}
		
		$db->update($table, $insert, array('id = ?' => (int)$id));
		return $id;
	}
	
	public static function getMaxOrderIndex($sub_of) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from( 'quiz', 'MAX(order_index)');
		
		return ((int)$db->fetchOne($query) + 1);
		
	}
	
	public static function getQuizes($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = 'quiz';
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$table = 'quiz_answers';
		}
		
		$query = $db->select()
					->from($table)
					->order( ($table == 'quiz' ? 'order_index ASC' : 'name ASC') );
	
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$query->where('quiz_id = ?', (int)$data['filter_sub_of']);
		}
					
		if(isset($data['start']) && isset($data['limit'])) {
			if($data['start'] < 0) {
				$data['start'] = 0;
			}
			$query->limit($data['limit'], $data['start']);
		} 
		
		return $db->fetchAll($query);
		
	}
	
	public static function getTotalQuizes($data = array()) {
		$db = JO_Db::getDefaultAdapter();
		
		$table = 'quiz';
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$table = 'quiz_answers';
		}
		
		$query = $db->select()
					->from($table, 'COUNT(id)');
		
		if(isset($data['filter_sub_of']) && $data['filter_sub_of']) {
			$query->where('quiz_id = ?', (int)$data['filter_sub_of']);
		}
		
		return $db->fetchOne($query);
		
	}
	
	public static function getQuiz($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from((JO_Request::getInstance()->getRequest('sub_of') ? 'quiz_answers' : 'quiz' ))
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	
	public static function getQuiz2($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('quiz')
					->where('id = ?', (int)$id);
		return $db->fetchRow($query);
	}
	

	public static function changeSortOrder($id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update( 'quiz', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$id));
	}
	
	public static function deleteQuiz($id) {
		$db = JO_Db::getDefaultAdapter();
		
		if(JO_Request::getInstance()->getRequest('sub_of')) {
			$db->delete('quiz_answers', array('id = ?' => (int)$id));
		} else {
			$db->delete('quiz', array('id = ?' => (int)$id));
			$db->delete('quiz_answers', array('quiz_id = ?' => (int)$id));
		}
	}
	
}

?>
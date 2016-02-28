<?php

class Model_Reports {

	public static function getReport($from, $to) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('orders')
					->where("`paid` = 'true'")
					->order('paid_datetime');

		$d = explode('-', $from);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`paid_datetime` >= ?', $from);
		}
		
		$d = explode('-', $to);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`paid_datetime` <= ?', $to);
		}
		
		$results = $db->fetchAll($query);
		
		$return = array();
		
		if($results) {
			foreach($results AS $result) {
				$date = explode(' ', $result['paid_datetime']);
				$date = $date[0];
				
				if(!isset($return[$date]['total'])) {
					$return[$date]['total'] = 0;
					$return[$date]['receive'] = 0;
					$return[$date]['referal'] = 0;
				}
					
				if($result['type'] == 'buy') {
					$return[$date]['total'] += $result['price'];
					$return[$date]['receive'] += $result['receive'];				
				}
				else {
					$return[$date]['referal'] += $result['receive'];				
				}
				
				$return[$date]['win'] = floatval($return[$date]['total']) - floatval($return[$date]['receive']) - floatval($return[$date]['referal']);
			}
		}
		
		return $return;
		
	}
	
	public static function getDeposits($from, $to) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'deposit')
					->where("`paid` = 'true'")
					->order('datetime');

		$d = explode('-', $from);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`datetime` >= ?', $from);
		}
		
		$d = explode('-', $to);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`datetime` <= ?', $to);
		}
		
		$results = $db->fetchAll($query);
		
		$return = array();
	
		if($results) {
			foreach($results AS $result) {
				$date = explode(' ', $result['datetime']);
				$date = $date[0];
				
				if(!isset($return[$date]['deposit'])) {
					$return[$date]['deposit'] = 0;
				}
					
				$return[$date]['deposit'] += $result['deposit'];
			}
		}
		
		return $return;
	}
	
	public static function getWithdraws($from, $to) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'withdraw')
					->where("`paid` = 'true'")
					->order('paid_datetime');

		$d = explode('-', $from);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`paid_datetime` >= ?', $from);
		}
		
		$d = explode('-', $to);
		if(count($d) == 3 && checkdate($d[1], $d[2], $d[0])) {
			$query->where('`paid_datetime` <= ?', $to);
		}
		
		$results = $db->fetchAll($query);
		
		$return = array();
		
		if($results) {
			foreach($results AS $result) {
				$date = explode(' ', $result['paid_datetime']);
				$date = $date[0];
				
				if(!isset($return[$date]['amount'])) {
					$return[$date]['amount'] = 0;
				}
					
				$return[$date]['amount'] += $result['amount'];
			}
		}
		
		return $return;
	}
	
}

?>
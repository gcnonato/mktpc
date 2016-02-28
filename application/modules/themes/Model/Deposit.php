<?php

class Model_Deposit {
	
    public static function getAll() {
	    $db = JO_Db::getDefaultAdapter();
        $query = $db->select()
					->from('deposit_sums')
					->order('deposit ASC');

		return $db->fetchAll($query);;
	}

	public static function addDeposit($amount) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert(Model_Users::getPrefixDB().'deposit', array(
			'user_id' => JO_Session::get('user_id'),
			'deposit' => (float)$amount,
			'datetime' => new JO_Db_Expr('NOW()')
		));
		
		return $db->lastInsertId();
	}

	public static function getDeposit($id) {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'deposit')
					->where('id = ?', (int)$id)
					->limit(1);
		
		return $db->fetchRow($query);
	}
	
	public function depositIsPay($id) {
		
		$row = self::getDeposit($id);
		
		if($row) {
			if($row['added'] == 'true') {
				return;
			}
		
			$db = JO_Db::getDefaultAdapter();
			
			if(JO_Session::get('user_id')) {
				$user_data = Model_Users::getUser($row['user_id']);
				
				/* razkomentirame, ako iskame da nachislqva pari na referral_id i pri deposit
				if($user_data['referal_id'] > 0) {
					
					$referal = Model_Users::getUser($user_data['referal_id']);
					
					$referal_cnt = JO_Registry::forceGet('referal_sum');
					
					$percent = str_replace('%', '', JO_Registry::forceGet('referal_percent'));
					$sum = $row['deposit'] * ((int) $percent / 100);
					
					if($referal_cnt > 0) {
						$cnt = Model_Users::getTotalReferals($user_data['user_id'], $user_data['referal_id']);
						if(($cnt + 1) >= $referal_cnt)
							$user_data['referal_id'] = 0;
					}
					
					$db->update(Model_Users::getPrefixDB().'users', array(
						'earning' => new JO_Db_Expr('earning + ' . $sum),
						'total' => new JO_Db_Expr('total + ' . $sum)
					), array('user_id = ?' => (int)$referal['user_id']));
					
					$db->insert(Model_Users::getPrefixDB().'users_referals_count', array(
						'user_id' => (int)$row['user_id'],
						'referal_id' => (int)$referal['user_id'],
						'datetime' => new JO_Db_Expr('NOW()'),
						'order_type' => 'deposit',
						'referal_sum' => (float) $sum
					));
				}
				*/
				
				$db->update(Model_Users::getPrefixDB().'users', array(
					'deposit' => new JO_Db_Expr('deposit + ' . $row['deposit']),
					'total' => new JO_Db_Expr('total + ' . $row['deposit']),
		//			'referal_id' => $user_data['referal_id']
				), array('user_id = ?' => (int)$row['user_id']));
				
				$db->update(Model_Users::getPrefixDB().'deposit', array(
					'added' => 'true'
				), array('id = ?' => (int)$id));
				
				if($user_data) {
					$groups = unserialize($user_data['groups']);
			    	if(is_array($groups) && count($groups) > 0) {
			    		$query_group = $db->select()
			    							->from(Model_Users::getPrefixDB().'user_groups')
			    							->where("ug_id IN (?)", new JO_Db_Expr(implode(',', array_keys($groups))));
			    		$fetch_all = $db->fetchAll($query_group);
			    		$user_data['access'] = array();
			    		if($fetch_all) {
			    			foreach($fetch_all AS $row) {
			    				$modules = unserialize($row['rights']);
			    				if(is_array($modules)) {
			    					foreach($modules AS $module => $ison) {
			    						$user_data['access'][$module] = $module;
			    					}
			    				}
			    			}
			    		}
			    	}
				}
				
				if($user_data) {
					if(isset($user_data['access']) && count($user_data['access'])) {
			    	    	$user_data['is_admin'] = true;
			    	}
    				JO_Session::set($user_data);
				}
			}
			
			$translate = JO_Translate::getInstance();
			
			Model_History::add($translate->translate('Deposit from') . ' ' . WM_Currency::format($row['deposit']), $id, $row['user_id']);
		
		}
		
	}
	
	public static function referalMoney($row, $user) {
		
		$db = JO_Db::getDefaultAdapter();
		
		$totals = self::getTotalReferals($user['user_id'], $user['referal_id']);

		if((int)JO_Registry::get('referal_sum') && ($totals+1) > (int)JO_Registry::get('referal_sum')) {
			$db->update(Model_Users::getPrefixDB().'users', array(
				'referal_id' => 0
			), array('user_id = ?' => (int)$user['user_id']));
			return false;
		}
		
		$referalMoney = floatval($row['deposit']) * (int)JO_Registry::get('referal_percent') / 100;

		$db->update(Model_Users::getPrefixDB().'users', array(
				'earning' => new JO_Db_Expr('earning + ' . $referalMoney),
				'total' => new JO_Db_Expr('total + ' . $referalMoney),
				'referal_money' => new JO_Db_Expr('referal_money + ' . $referalMoney),
			), array('user_id = ?' => (int)$user['user_id']));
			
		$db->insert('orders', array(
			'user_id' => (int)$user['user_id'],
			'owner_id' => (int)$user['referal_id'],
			'item_id' => 0,
			'item_name' => 'deposit',
			'price' => (float)$row['deposit'],
			'datetime' => new JO_Db_Expr('NOW()'),
			'receive' => (float)$referalMoney,
			'paid' => 'true',
			'paid_datetime' => new JO_Db_Expr('NOW()'),
			'type' => 'referal'
		));
		
	}
	
	public static function getTotalReferals($id, $referal_id) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from(Model_Users::getPrefixDB().'users_referals_count', 'COUNT(id)')
					->where('user_id = ?', (int)$id)
					->where('referal_id = ?', (int)$referal_id)
					->limit(1);
		return $db->fetchOne($query);
	}
	
	public static function addWithdrawal($data) {
	    $db = JO_Db::getDefaultAdapter();
	    $db->insert(Model_Users::getPrefixDB().'withdraw', array(
	    'user_id'	=>    $data['user_id'],
	    'amount'	=>    $data['amount'],
	    'method'	=>    $data['method'],
	    'text'		=>    $data['text'],
	    'australian'=>    $data['australian'],
	    'abn'		=>    $data['abn'],
	    'acn'		=>    $data['acn'],
	    'datetime'	=>    new JO_Db_Expr('NOW()')
	    ));
	}
	
	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		$db->delete('deposit', array('id = ?' => (int) $id, 'paid = ?' => 'false'));
	}
	
	public static function update($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = 'UPDATE deposit SET 
				`paid` = \''. $data['paid'] .'\',
				`order_status_id` = \''. (int)$data['order_status_id'] .'\',
				`paid_from` = \''. $data['paid_from'] .'\'
				WHERE id = \''. (int)$id .'\'';
				
		$db->query($query);
	}
}

?>
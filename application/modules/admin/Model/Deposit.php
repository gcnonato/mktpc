<?php

class Model_Deposit {
	
	public static function getWithdrawCount($whereQuery) {
		$db = JO_Db::getDefaultAdapter();
		
		if($whereQuery != '') {
			$whereQuery = " WHERE ".$whereQuery;
		}
		
		return $db->query("
			SELECT COUNT(`id`) AS `num`, SUM(`amount`) AS `total`
			FROM ".Model_Users::getPrefixDB()."`withdraw`
			$whereQuery
		")->fetch();	
	}
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('deposit_sums', array(
			'deposit' => (float)$data['deposit']
		));
		return $db->lastInsertId();
	}

	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('deposit_sums', array(
			'deposit' => (float)$data['deposit']
		), array('id = ?' => (int)$id));
		return $id;
	}

	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('deposit_sums', array('id = ?' => (int)$id));
	}

    public static function getAll() {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from("deposit_sums")
            ->order("deposit ASC");
        return $db->fetchAll($query);
    }

    public static function get($id) {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from("deposit_sums")
            ->where("id = ?", (int)$id);
        return $db->fetchRow($query);
    }
	
	public static function getNoPaid($data) {
		$db = JO_Db::getDefaultAdapter();    
        
		$where = '';
		if(isset($data['filter_id']) && !empty($data['filter_id'])) {
			$data['filter_id'] = preg_replace ( '#[^\d+]#i' , '' , $data['filter_id'] );
			if($data['filter_id'] != 0) {
				$where = ' AND d.id = '. $db->quote($data['filter_id']);
			}
		}
		
		if(isset($data['filter_username']) && !empty($data['filter_username'])) {
			$where .= ' AND u.username = '. $db->quote($data['filter_username']);
		}
		
		if(isset($data['filter_price']) && !empty($data['filter_price'])) {
			$data['filter_price'] = preg_replace ( '#[^\d+]#i' , '' , $data['filter_price'] );
			if($data['filter_price'] != 0) {
				$where .= ' AND d.deposit = '. $db->quote($data['filter_price']);
			}
		}
		
        $query = 'SELECT d.*, u.username
        		FROM deposit d
        		JOIN users u ON u.user_id = d.user_id
        		WHERE d.paid = \'false\''. $where .'
        		ORDER BY '. $data['order'] .' '. $data['sort'];
			
        return $db->fetchAll($query);
	}

	public static function delete_deposit($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('deposit', array('id = ?' => (int)$id));
	}
	
	public static function change_deposit($id) {
		$db = JO_Db::getDefaultAdapter();
		
		$row = $db->fetchRow('SELECT * FROM deposit WHERE id = '. $db->quote($id));
		
		if($row) {
			if($row['paid'] === 'true') {
				return;
			}
			
			$db->update(Model_Users::getPrefixDB().'users', array(
					'deposit' => new JO_Db_Expr('deposit + ' . $row['deposit']),
					'total' => new JO_Db_Expr('total + ' . $row['deposit'])
				), array('user_id = ?' => (int)$row['user_id']));
				
			$db->update(Model_Users::getPrefixDB().'deposit', array(
					'paid' => 'true',
					'added' => 'true'
				), array('id = ?' => (int)$id));
		}
	}
	
	public static function deleteMultiDeposit($order_ids) {
		$db = JO_Db::getDefaultAdapter();
		
		if(!is_array($order_ids)) return false;
		
		$query = 'DELETE FROM deposit WHERE id IN ('. implode(',', $order_ids) .') AND paid = \'false\'';
		$db->query($query);
	}
}

?>
<?php

class Model_Sizes {
	
	public static function getAll() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('sizes')
					->order('order_index ASC');
		return $db->fetchAll($query);
	}

    public static function get($id) {
        $db = JO_Db::getDefaultAdapter();    
        
        $query = $db->select()
            ->from("sizes")
            ->where("id = ?", (int)$id);
        return $db->fetchRow($query);
    }
	
	public static function create($data) {
		$db = JO_Db::getDefaultAdapter();
		$db->insert('sizes', array(
			'size' => (float)$data['size'],
			'name' => $data['name']
		));
		return $db->lastInsertId();
	}

	public static function edit($id, $data) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('sizes', array(
			'size' => (float)$data['size'],
			'name' => $data['name']
		), array('id = ?' => (int)$id));
		return $id;
	}

	public static function delete($id) {
		$db = JO_Db::getDefaultAdapter();
		return $db->delete('sizes', array('id = ?' => (int)$id));
	}
	
	public static function changeSortOrder($page_id, $sort_order) {
		$db = JO_Db::getDefaultAdapter();
		$db->update('sizes', array(
			'order_index' => $sort_order
		), array('id = ?' => (int)$page_id));
	}
	
	public static function initDB() {
		$db = JO_Db::getDefaultAdapter();
		$db->query("CREATE TABLE IF NOT EXISTS `sizes` (
		  `id` int(11) NOT NULL auto_increment,
		  `size` float NOT NULL,
		  `order_index` int(11) NOT NULL,
		  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `order_index` (`order_index`)
		) ENGINE=MyISAM;");
	}

}

?>
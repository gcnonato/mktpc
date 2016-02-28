<?php

class Model_Sitemap {
	
	public static function create() {
		$db = JO_Db::getDefaultAdapter(); 
		
		$query = "SELECT id, name, '0.9' AS priority, 
					'weekly' AS change_freq, DATE(datetime) AS datetime, 'item' AS tp
				FROM items
				WHERE status = 'active'
				UNION ALL
				SELECT c.id, c.name, '0.8' AS priority, 
					'daily' AS change_freq, DATE(MAX(datetime)) as datetime,
					'category' AS tp
				FROM categories c
				LEFT JOIN items_to_category ic ON 
					ic.categories LIKE CONCAT('%,',c.id ,',')
				LEFT JOIN items i ON i.id = ic.item_id
				WHERE i.status = 'active'
				GROUP BY c.id
				UNION ALL
				SELECT id, name, '0.7' AS priority, 
					'monthly' AS change_freq, 
					DATE(FROM_UNIXTIME(RAND() * 
						(UNIX_TIMESTAMP(NOW()) -  UNIX_TIMESTAMP(
							DATE_SUB(NOW(), interval 1 month))
						) + UNIX_TIMESTAMP(
							DATE_SUB(NOW(), interval 1 month))
						)) AS datetime,
					'page' AS tp
				FROM pages
				WHERE visible = 'true'
				UNION ALL
				SELECT user_id AS id, username AS name, '0.7' AS priority, 
					'daily' AS change_freq, DATE(last_login_datetime) AS datetime,
					'user' AS tp
				FROM users
				WHERE status = 'activate'";
		
		return $db->fetchAll($query);
	}
	
}

?>
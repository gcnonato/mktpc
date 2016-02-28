<?php

class Model_Notification {

	public static function getNotification($key) {
		$db = JO_Db::getDefaultAdapter();
		
		$query = $db->select()
					->from('notification_templates')
					->where('`key` = ?', $key)
					->limit(1);
		$row = $db->fetchRow($query);
		
		$request = JO_Request::getInstance();
		if(JO_Registry::get('site_logo') && file_exists(BASE_PATH .'/uploads/'.JO_Registry::get('site_logo'))) {
		    $site_logo = $request->getBaseUrl() .'/uploads/'. JO_Registry::get('site_logo'); 
		} else {
			$site_logo = $request->getBaseUrl() .'/data/themes/images/logo.png';
		}
		
		$row['template'] = '<html><body>
					<div style="background-color:#d6d6d6;width:100%;">
						<div style="background:#fff;width:600px;padding:10px;margin:0 auto;">
							<img src="'. $site_logo .'" alt="Logo" style="margin-left:20px;"><br/><br/>
							'. $row['template'] .'
						</div>
					</div>
					</body></htmlt>';
					
		return $row;
	}
	
}

?>
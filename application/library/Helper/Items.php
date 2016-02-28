<?php

class Helper_Items {
	
	public static function returnViewIndex($item, $view_name = 'index') {
		
		if(!isset($item['module']) || !$item['module']) return;
		
		$view = JO_View::getInstance();
		
		if(!isset($item['no_items'])) {
			static $results = array();
			
			$model_images = new Helper_Images();
			$request = JO_Request::getInstance();
			
			if(mb_strlen($item['name'], 'UTF-8') > 35)
				$item['name'] = JO_Utf8::mb_cut_text($item['name'], 0, 35, ' ');
			
			$item['price'] = WM_Currency::format($item['price']);
			
			switch($view_name) {
				case 'category':
					$height = JO_Registry::forceGet($item['module'].'_items_preview_height');
					
					$item['thumbnail'] = $model_images->resizeWidth($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_preview_width'));
					if(!empty($item['thumbnail'])) {
						$thumb_size = getimagesize($item['thumbnail']);
						if($thumb_size[1] > $height) {
							$image = new JO_GDThumb($item['thumbnail']);
							$image->crop(0, 0, $thumb_size[0], $height);
							$image->save($item['thumbnail']);
						}
					}
					
					/* CATEGORIES */
					$cats = array();
					$categories = Model_Categories::getCategoriesByIds($item['categories']);
					foreach($categories as $v) {
						$cats[] = array(
							'name' => $v['name'],
							'href' => WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=categories&category_id='. $v['id'] .'&name='. WM_Router::clearName($v['name']))
						);
					}
					$item['categories'] = $cats;
					
				break;
				case 'downloads':
					$height = JO_Registry::forceGet($item['module'].'_items_preview_height');
					
					$item['thumbnail'] = $model_images->resizeWidth($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_preview_width'));
					if(!empty($item['thumbnail'])) {
						$thumb_size = getimagesize($item['thumbnail']);
						if($thumb_size[1] > $height) {
							$image = new JO_GDThumb($item['thumbnail']);
							$image->crop(0, 0, $thumb_size[0], $height);
							$image->save($item['thumbnail']);
						}
					}
					
					$item['rate'] = Model_Items::isRate($item['id']);
					break;
				default:
					$height = JO_Registry::forceGet($item['module'].'_items_thumb_height');
					
					$item['thumbnail'] = $model_images->resizeWidth($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_thumb_width'), $height);
					if(!empty($item['thumbnail'])) {
						$thumb_size = getimagesize($item['thumbnail']);
						if($thumb_size[1] > $height) {
							$image = new JO_GDThumb($item['thumbnail']);
							$image->crop(0, 0, $thumb_size[0], $height);
							$image->save($item['thumbnail']);
						}
					}
			}
			
		
			$item['href'] = WM_Router::create($request->getBaseUrl() . '?controller=items&item_id='.$item['id'] .'&name='. WM_Router::clearName($item['name']));
	    
			$item['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='. WM_Router::clearName($item['username']));
		}
		
		$view->item = $item; 
		
		return $view->renderByModule('single_items/' . $view_name, 'items', $item['module']);
	}
	
	public function getCategory($id) {
		
		static $result = array();
		
		if(isset($result[$id])) return $result[$id];
	    
	    $db = JO_Db::getDefaultAdapter();
        $query = 'SELECT c.*, cd.name
        		FROM categories c
        		JOIN categories_description cd ON cd.id = c.id AND cd.lid = '. JO_Session::get('language_id') .'
        		WHERE c.id = '. $db->quote($id);
		
		$result[$id] = $db->fetchRow($query);
					
		return $result[$id];	
	}
}

?>
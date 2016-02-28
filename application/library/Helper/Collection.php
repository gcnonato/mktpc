<?php

class Helper_Collection {
		
	public static function returnViewIndex($item, $rv_button = false) {
		$view = JO_View::getInstance();
		$request = JO_Request::getInstance();
		
		self::getColectionThumb($item);
		if($rv_button) {
			$item['rv_button'] = $rv_button;
			$item['rate'] = Model_Collections::isRate($item['id']);
			//if(!$item['rate'] && JO_Session::get('user_id') == $item['user_id']) $item['rate'] = true;
		} else {
			$item['rate'] = true;
		}
		
		$item['userhref'] = WM_Router::create($request->getBaseUrl() .'?controller=users&username='. str_replace('&', '-', $item['username']));
		if(!isset($item['href']))
			$item['href'] = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=view&collection_id='.$item['id'].'&name='. str_replace('&', '-', $item['name']));

		$view->item = $item;
		return $view->renderByModule('single_items/collection', 'items', 'themes');
	}
	
	
	protected function getColectionThumb(& $item) {
		$model_images = new Helper_Images();
		
		if(!empty($item['photo'])) {
			$height = JO_Registry::forceGet('user_public_collection_height');
			
			$thumb = $model_images->resizeWidth($item['photo'], JO_Registry::forceGet('user_public_collection_width'));
			$thumb_size = getimagesize($thumb);
			if($thumb_size[1] > $height) {
				$image = new JO_GDThumb($thumb);
				$image->crop(0, 0, $thumb_size[0], $height);
				$image->save($thumb);
			}
			
			$item['photo'] = $thumb;
		} else {
			$item['photo'] = 'data/themes/images/no_collection_image.png';
		}
	}
}

?>
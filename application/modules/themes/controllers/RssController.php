<?php

class RssController extends JO_Action {

	public function indexAction() {
		
		$where = '';
		$category = null;
		if($this->getRequest()->getQuery('username')) {
			$userinfo = Model_Users::getByUsername($this->getRequest()->getQuery('username'));
			if($userinfo) {
				$where = "users.username = '".$this->getRequest()->getQuery('username')."'";
			}
		} 
		
		if($this->getRequest()->getQuery('category')) {
			$catinfo = Model_Categories::get($this->getRequest()->getQuery('category'));
			if($catinfo) {
				$category = $this->getRequest()->getQuery('category');
			}
		}
		
		$items = Model_Items::getAll($category, 0, 20, 'id desc', $where);
		
		$this->view->item = array();

		if($items) {
		
			$model_images = new Model_Images();
			
			$categories = Model_Categories::get_all();
			
			foreach($items AS $item) {
				
				$categories_string = '';
				if($category) {
					foreach($item['categories'] AS $cats) {
						if(in_array($category, $cats)) {
							foreach($cats AS $cat) {
								if(isset($categories[$cat]['name'])) {
									$categories_string .= $categories_string ? ' › ' : '';
									$categories_string .= $categories[$cat]['name'];
								}
							}
							break;
						}
					}
				} else {
					$cats = array_pop($item['categories']);
					if($cats && is_array($cats)) {
						foreach($cats AS $cat) {
							if(isset($categories[$cat]['name'])) {
								$categories_string .= $categories_string ? ' › ' : '';
								$categories_string .= $categories[$cat]['name'];
							}
						}
					}
				}
				
				if((int)JO_Registry::get($item['module'].'_items_preview_width') && (int)JO_Registry::get($item['module'].'_items_preview_height')) {
	            	$item['theme_preview_thumbnail'] = $this->getRequest()->getBaseUrl() . $model_images->resize($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_preview_width'), JO_Registry::forceGet($item['module'].'_items_preview_height'), true);
	            } elseif((int)JO_Registry::get($item['module'].'_items_preview_width')) {
	            	$item['theme_preview_thumbnail'] = $this->getRequest()->getBaseUrl() . $model_images->resizeWidth($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_preview_width'));
	            } elseif((int)JO_Registry::get($item['module'].'_items_preview_height')) {
	            	$item['theme_preview_thumbnail'] = $this->getRequest()->getBaseUrl() . $model_images->resizeHeight($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_preview_height'));
	            } else {
	            	$item['theme_preview_thumbnail'] = false;
	            }
				
				$this->view->item[] = array(
					'title' => $item['name'],
					'link' => WM_Router::create($this->getRequest()->getBaseUrl() . '?module='.$item['module'].'&controller=items&item_id='.$item['id']),
					'description' => html_entity_decode($item['description'], ENT_QUOTES, 'utf-8'),
					'author' => $item['username'],
					'category' => $categories_string,
					'guid' => $item['id'],
					'enclosure' => $item['theme_preview_thumbnail'],
					'pubDate' => JO_Date::getInstance($item['datetime'], JO_Date::RSS_FULL, true)->toString()
				);
			}
		}
		
		echo $this->renderScript('rss');
		
	}
	
}

?>
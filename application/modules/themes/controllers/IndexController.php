<?php

class IndexController extends JO_Action {	
	/*
	public function init() {
		$request = $this->getRequest();
		$this->cache = JO_Cache::getInstance(JO_Registry::forceGet('cache_driver'));
		$this->cache_key = md5($request->getFullUrl() .'@@@'. JO_Session::get('user_id') .'@@@'. JO_Session::get('currency') .'@@@'. JO_Session::get('language_id')); 
	}
	
	public function preDispatchCache() {
		$view = $this->cache->get($this->cache_key);
		if($view) {
			foreach($view AS $k => $v) {
				$this->view->{$k} = $v;
			}
			return true;
		}
	}
	
	public function postDispatch() {
		if(!$this->cache->get($this->cache_key)) {
			$cache_data = array();
			if($this->view->children) {
				foreach($this->view->children AS $k => $v) {
					$cache_data[$k] = $this->view->callChildren($v);
				}
			}
			
			$cache_data += $this->view->getAll();
			
			$this->cache->add($this->cache_key, $cache_data);
		}
		
		$this->cache->deleteExpired();
	}
	*/
	public function indexAction() {
	    $request = $this->getRequest();
		
		$model_images = new Model_Images;

		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
	    
		/* LAST ITEM */
		
		$lastItem = Model_Items::getLastItem();
		
		if($lastItem) {
			$thumb = $model_images->resizeWidth($lastItem['theme_preview_thumbnail'], 200);
			$thumb_size = getimagesize($thumb);
			if($thumb_size[1] > 161) {
				$image = new JO_GDThumb($thumb);
				$image->crop(0, 0, $thumb_size[0], 161);
				$image->save($thumb);
			}
			
			$this->view->lastItem = array(
				'name' => $lastItem['name'],
				'href' => WM_Router::create($request->getBaseUrl() . '?module='.$lastItem['module'].'&controller=items&item_id='.$lastItem['id'] .'&name='. $lastItem['name']),
				'thumb' => $thumb
			);
		}
		
		/* FREE ITEM */
		
	   	$this->view->freeItems = '';
	    $freeItems = Model_Items::getFreeFiles(); 
		
	   	if($freeItems) {
			foreach($freeItems as $fi) {
				$cats = explode(',', $fi['categories']);
				$cat_name = Helper_Items::getCategory($cats[1]);
				
				$thumb = $model_images->resizeWidth($fi['theme_preview_thumbnail'], JO_Registry::forceGet($fi['module'].'_items_thumb_width'));
				$thumb_size = getimagesize($thumb);
				if($thumb_size[1] > JO_Registry::forceGet($fi['module'].'_items_thumb_height')) {
					$image = new JO_GDThumb($thumb);
					$image->crop(0, 0, $thumb_size[0], JO_Registry::forceGet($fi['module'].'_items_thumb_height'));
					$image->save($thumb);
				}
				$this->view->freeItems[] = array(
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=items&item_id='.$fi['id'] .'&name='. WM_Router::clearName($fi['name'])),
					'thumb' => $thumb,
					'cat_name' => $cat_name['name'],
					'name' => $fi['name']
				);
			}
	   	}
		
		/* ITEMS */
		
		$this->view->items = array();
		
		$listType = $request->getParam('list_type');
		if(is_null($listType)) {
			$listType = 'recent';
		}
		
		$this->view->all_items = array(
			'name' => $this->view->translate('View all items'),
			'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&action='. $listType)
		);
		
		switch($listType) {
			case 'featured':
				JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
				$this->view->title_items = $this->view->translate('Featured Items');
				
				$featuredItems = Model_Items::getWeekly($request->getParam('category_filter'));
				
				if($featuredItems) {
					foreach($featuredItems as $n => $item) {
						if(!empty($item['demo_url'])) {
							$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&action=preview&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
						}
						
						$this->view->items[] = Helper_Items::returnViewIndex($item);
					}
				}
				
			break;
			case 'author':
				JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
				$this->view->title_items = $this->view->translate('Featured Author');
				$this->view->author = Model_Users::getFeatUser();
				
				if($this->view->author) {
					$this->view->author['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='.$this->view->author['username']);
					$this->view->author['userhref_pf'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='.$this->view->author['username']);
    				
					if($this->view->author['avatar']) {
						$this->view->author['avatar'] = $model_images->resize($this->view->author['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
					} else {
						$this->view->author['avatar'] = 'data/themes/images/avatar-author.png';
					}
					
					$this->view->items = array();
					$items = Model_Items::getByUser($this->view->author['user_id'], 0, 11, false);
					
					if(empty($items)) {
						$items = array(
							0 => array(
								'no_items' => true,
								'thumbnail' => 'data/themes/images/missing-item.png',
								'module' => 'themes'
							)
						);
					}
					
					if($items) {
						foreach($items as $n => $item) {
							if(!empty($item['demo_url'])) {
								$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&action=preview&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
							}
							$this->view->items[] = Helper_Items::returnViewIndex($item);
						}
					}
				} 
				
				$this->view->all_items = array(
					'name' => $this->view->translate('View portfolio'),
					'href' => $this->view->author['userhref_pf']
				);
				
			break;
			default:
				/* RECENT ITEMS */
				JO_Session::set('redirect', $request->getBaseUrl());
				$this->view->title_items = $this->view->translate('Recent Items');
				
				$recentItems = Model_Items::getRecent($request->getParam('category_filter'));
				
				if($recentItems) {
					foreach($recentItems as $n => $item) {
						if(!empty($item['demo_url'])) {
							$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&action=preview&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
						}
						$this->view->items[] = Helper_Items::returnViewIndex($item);
					}
				}
				shuffle($this->view->items);
		}
		
		$this->view->listType = $listType;
		
		$this->view->recent_items = WM_Router::create($request->getBaseUrl() . '?controller=recent');
		$this->view->featured = WM_Router::create($request->getBaseUrl() . '?controller=featured');
		$this->view->featuredAuthor = WM_Router::create($request->getBaseUrl() . '?controller=author');
		
		/* CATEGORIES */
		$this->view->categories = Model_Categories::getMain();
		$this->view->top_categories = $this->view->categories;
		if($this->view->categories) {
			if($this->view->categories) {
				foreach($this->view->categories AS $k=>$v) {
					$this->view->categories[$k]['href'] = WM_Router::create($request->getBaseUrl() . '?controller='. $listType .'&category_filter='.$v['id']);
				}
			}
		}
		
		if($this->view->top_categories) {
			
			foreach($this->view->top_categories AS $k=>$v) {
				$this->view->top_categories[$k]['href'] = WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='.$v['id'] .'&name='. $v['name']);
			}
			
			shuffle($this->view->top_categories);
			$this->view->top_categories = array_slice($this->view->top_categories, 0, 4);
		}
		
		$this->view->all_categories = WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='. $listType);
	    
		
        $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}

}

?>
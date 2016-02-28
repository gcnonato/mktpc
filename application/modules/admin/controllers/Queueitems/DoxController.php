<?php

class Queueitems_DoxController extends JO_Action {
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function editAction() {
		$this->setViewChange('form');
		$request = $this->getRequest();
		
		$url = '';
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_name')) { $url .= '&filter_name=' . $request->getQuery('filter_name'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_price')) { $url .= '&filter_price=' . $request->getQuery('filter_price'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_profit')) { $url .= '&filter_profit=' . $request->getQuery('filter_profit'); }
    	if($request->getQuery('filter_free_request')) { $url .= '&filter_free_request=' . $request->getQuery('filter_free_request'); }
    	if($request->getQuery('filter_free_file')) { $url .= '&filter_free_file=' . $request->getQuery('filter_free_file'); }
    	if($request->getQuery('filter_weekly')) { $url .= '&filter_weekly=' . $request->getQuery('filter_weekly'); }
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
		
		if($request->isPost()) {
			if($request->getPost('submit')) {
				$request->setParams('set_status','active');
	    		Model_Items_Dox::editItem($request->getQuery('id'), $request->getParams());
	    		$this->session->set('successfu_edite', true);
			} elseif($request->getPost('delete')) {
				Model_Items::deleteItem($request->getQuery('id'), $request->getParams());
	    		$this->session->set('successfu_delete', true);
			}
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/queueitems/?' . $url);
    	}
		$this->getForm();
	}
	
	
	private function getForm() {
		$request = $this->getRequest();
		
		$url = '';
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_name')) { $url .= '&filter_name=' . $request->getQuery('filter_name'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_price')) { $url .= '&filter_price=' . $request->getQuery('filter_price'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_profit')) { $url .= '&filter_profit=' . $request->getQuery('filter_profit'); }
    	if($request->getQuery('filter_free_request')) { $url .= '&filter_free_request=' . $request->getQuery('filter_free_request'); }
    	if($request->getQuery('filter_free_file')) { $url .= '&filter_free_file=' . $request->getQuery('filter_free_file'); }
    	if($request->getQuery('filter_weekly')) { $url .= '&filter_weekly=' . $request->getQuery('filter_weekly'); }
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	
    	$id = $request->getQuery('id');
    	
    	$info = Model_Items::getItem($id);
    	
    	if(!$info) {
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/queueitems/?' . $url);
    	}
    	
    	$this->view->cancel = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/queueitems/?' . $url;
    	
		$model_images = new Model_Images();
    	
		$info['thumbnail'] = $model_images->resize($info['thumbnail'], JO_Registry::forceGet($info['module'].'_items_thumb_width'), JO_Registry::forceGet($info['module'].'_items_thumb_height'), true); 	
		if((int)JO_Registry::get($info['module'].'_items_preview_width') && (int)JO_Registry::get($info['module'].'_items_preview_height')) {
			$info['theme_preview_thumbnail'] = $model_images->resize($info['theme_preview_thumbnail'], JO_Registry::forceGet($info['module'].'_items_preview_width'), JO_Registry::forceGet($info['module'].'_items_preview_height'), true);
		} elseif((int)JO_Registry::get($info['module'].'_items_preview_width')) {
			$info['theme_preview_thumbnail'] = $model_images->resizeWidth($info['theme_preview_thumbnail'], JO_Registry::forceGet($info['module'].'_items_preview_width'));
		} elseif((int)JO_Registry::get($info['module'].'_items_preview_height')) {
			$info['theme_preview_thumbnail'] = $model_images->resizeHeight($info['theme_preview_thumbnail'], JO_Registry::forceGet($info['module'].'_items_preview_height'));
		} else {
			$info['theme_preview_thumbnail'] = false;
		}
    	
    	$this->view->info = $info;
    	
    	$this->view->price_f = WM_Currency::format($info['suggested_price']);
    	
    	$this->view->categories = Model_Categories::getCategoriesFromParentByModule(0, $info['module']);
    	
    	
    	$cats_module = Model_Categories::getCategories(array(
    		'filter_sub_of' => 0,
    		'filter_module' => $info['module'],
    		'filter_concat' => true
    	));
    	
    	$where_attr = '';
    	if($cats_module) {
    		$tmp = array();
    		foreach($cats_module AS $c) {
    			$tmp[] = "categories LIKE '%,".$c.",%'";
    		}
    		if($tmp) {
    			$where_attr = implode(' OR ', $tmp);
    		}
    	}
    	
    	$this->view->attributes_list = array();
    	$attr_cat = Model_Attributes::getAttributes(array(), $where_attr);
    	
    	if($attr_cat) {
    		foreach($attr_cat AS $row => $attr) {
    			$attr_list = Model_Attributes::getAttributes(array(
    				'filter_sub_of' => $attr['id']
    			));
    			if($attr_list) {
    				$this->view->attributes_list[$row] = $attr;
    				$this->view->attributes_list[$row]['items'] = $attr_list;
    			}
    		}	
    	}
    	
    	if($request->getPost('name')) {
    		$this->view->name = $request->getPost('name');
    	} else {
    		$this->view->name = $info['name'];
    	}
    	
    	if($request->getPost('description')) {
    		$this->view->description = $request->getPost('description');
    	} else {
    		$this->view->description = $info['description'];
    	}
    	
    	if($request->getPost('price')) {
    		$this->view->price = $request->getPost('price');
    	} else {
    		$this->view->price = '';
    	}
    	
    	if($request->getPost('category_id')) {
    		$this->view->category_id = $request->getPost('category_id');
    	} else {
    		$this->view->category_id = Model_Items::getItemCategory($id);
    	}
    	
    	if($request->getPost('attributes')) {
    		$this->view->attributes = $request->getPost('attributes');
    	} else {
    		$this->view->attributes = Model_Items::getItemAttributes($id);
    	}
    	
    	if($request->getPost('tags')) {
    		$this->view->tags = $request->getPost('tags');
    	} else {
    		$this->view->tags = Model_Items::getItemTags($id);
    	}
    	
    	if($request->getPost('free_file')) {
    		$this->view->free_file = $request->getPost('free_file');
    	} else {
    		$this->view->free_file = $info['free_file'];
    	}
    	
    	if($request->getPost('demo_url')) {
    		$this->view->demo_url = $request->getPost('demo_url');
    	} else {
    		$this->view->demo_url = $info['demo_url'];
    	}
    	
    	if($request->getPost('weekly_from')) {
    		$this->view->weekly_from = $request->getPost('weekly_from');
    	} else {
    		$this->view->weekly_from = '';
    	}
    	
    	if($request->getPost('weekly_to')) {
    		$this->view->weekly_to = $request->getPost('weekly_to');
    	} else {
    		$this->view->weekly_to = '';
    	}
    	
	}
	

}

?>
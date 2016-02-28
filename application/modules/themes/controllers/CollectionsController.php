<?php

class CollectionsController extends JO_Action {
    private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
	
		$request = $this->getRequest();
		
		JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
		
		$this->getLayout()->meta_title = $this->translate('Collections');
    	$this->getLayout()->meta_description = $this->translate('Collections');
	    
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'desc';
		}
		
		$sort = $request->getRequest('sort');
		if(is_null($sort)) {
			$sort = 'datetime';
		}
		
		if($sort == 'username') {
			$prefix = 'users.';
		} else {
			$prefix = 'collections.';
		}
		
		$link = $request->getBaseUrl() . '?&controller=collections';
		$this->view->sort_by = array(
			array(
				'name' => $this->view->translate('date'),
				'href' => WM_Router::create($link .'&sort=datetime'),
				'is_selected' => ($sort == 'datetime' ? true : false)
			),
			array(
				'name' => $this->view->translate('title'),
				'href' => WM_Router::create($link . '&sort=name'),
				'is_selected' => ($sort == 'name' ? true : false)
			),
			array(
				'name' => $this->view->translate('rating'),
				'href' => WM_Router::create($link . '&sort=rating'),
				'is_selected' => ($sort == 'rating' ? true : false)
			),
			array(
				'name' => $this->view->translate('author name'),
				'href' => WM_Router::create($link . '&sort=username'),
				'is_selected' => ($sort == 'username' ? true : false)
			)
		);
		
		
		/* ORDER */
		$link .= '&sort='. $sort;
		$this->view->orders = array(
			array(
				'name' => '&raquo;',
				'href' => WM_Router::create($link . '&order=desc'),
				'is_selected' => ($order == 'desc' ? true : false)
			),
			array(
				'name' => '&laquo;',
				'href' => WM_Router::create($link . '&order=asc'),
				'is_selected' => ($order == 'asc' ? true : false)
			)
		);
		
		$this->view->collection = $this->view->translate('Public Collections');
		
		/* CRUMBS */
		$this->view->crumbs = array();
		
		$this->view->crumbs[] = array(
			'name' => $this->view->translate('Home'),
			'href' => $request->getBaseUrl()
		);
		
		/* PAGENATION */
		
		$link .= '&order='. $order;
		$total_records = Model_Collections::CountCollections(true);
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		$collections = Model_Collections::getAll($start, $limit, $prefix . $sort .' '. $order, true);
	   
		if($collections) {
			foreach($collections as $collection) {
				$this->view->items[] = Helper_Collection::returnViewIndex($collection);
			}
		}
		
		$pagination = new Model_Pagination;
		$pagination->setLimit($limit);
		$pagination->setPage($page);
		$pagination->setText(array(
			'text_prev' => $this->view->translate('Prev'),
			'text_next' => $this->view->translate('Next')
		));
		$pagination->setTotal($total_records);
		$pagination->setUrl(WM_Router::create($link .'&page={page}'));
		$this->view->pagination = $pagination->render();
		
		if(!empty($this->view->pagination)) {
			$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
		}
		
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part'; 
	}
	
	public function orderAction() {
		$this->forward('collections','index');
	}
	
	public function sortAction() {		
		$this->forward('collections','index');
	}
	
	public function viewAction() {
		
		$request = $this->getRequest();
		
		$collectionID = $request->getRequest('view');
	    $collection = Model_Collections::get($collectionID);
		
		$this->view->my_profil = $collection['username'] == JO_Session::get('username');
		if(!$collection || ($collection['public'] == 'false' && !$this->view->my_profil)) {
			return $this->forward('error', 'error404');
		}
		
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'desc';
		}
		
		$sort = $request->getRequest('sort');
		if(is_null($sort)) {
			$sort = 'datetime';
		}
		
		if($sort == 'username') {
			$prefix = 'users.';
		} else {
			$prefix = 'items.';
		}
		
	    $this->getLayout()->meta_title = $collection['name'];
    	$this->getLayout()->meta_description = isset($collection['description']) ? substr(strip_tags($collection['description']), 0, 255) : $this->translate('Collection');	
		
		$collection['without_bt'] = true;
		$this->view->collection_name = $collection['name'];
		
		$this->view->collection = Helper_Collection::returnViewIndex($collection, true);
		$this->view->rate_link = WM_Router::create($request->getBaseUrl() .'?controller=collections&action=rate_collection&collection_id='.$collection['id']);
		/* CRUMBS */	
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->view->translate('Public Collections'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=collections')
			)
		);
		$total_records = Model_Items::CountByCollection($collectionID);
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$last_page = max(ceil($total_records / $limit), 1);
			$start = (($last_page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		$items = Model_Items::getAllByCollection($collectionID, $start, $limit,  $prefix . $sort .' '. $order);
		
		if($items) {
			
			$link = $request->getBaseUrl() . '?&controller=collections&action=view&collection_id='. $collectionID .'&name='. WM_Router::clearName($collection['name']);
		
			$this->view->sort_by = array(
				array(
					'name' => $this->view->translate('date'),
					'href' => WM_Router::create($link .'&sort=datetime'),
					'is_selected' => ($sort == 'datetime' ? true : false)
				),
				array(
					'name' => $this->view->translate('title'),
					'href' => WM_Router::create($link . '&sort=name'),
					'is_selected' => ($sort == 'name' ? true : false)
				),
				array(
					'name' => $this->view->translate('rating'),
					'href' => WM_Router::create($link . '&sort=rating'),
					'is_selected' => ($sort == 'rating' ? true : false)
				)
			);
			
			/* ORDER */
			$link .= '&sort='. $sort;
			$this->view->orders = array(
				array(
					'name' => '&raquo;',
					'href' => WM_Router::create($link . '&order=desc'),
					'is_selected' => ($order == 'desc' ? true : false)
				),
				array(
					'name' => '&laquo;',
					'href' => WM_Router::create($link . '&order=asc'),
					'is_selected' => ($order == 'asc' ? true : false)
				)
			);
			$this->view->items = array();
			foreach($items as $item) {
				$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=preview&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name']));
				$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
			}
			
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setText(array(
				'text_prev' => $this->view->translate('Prev'),
				'text_next' => $this->view->translate('Next')
			));
			$pagination->setTotal($total_records);
			$pagination->setUrl(WM_Router::create($link .'&page={page}'));
			$this->view->pagination = $pagination->render();
			
			if(!empty($this->view->pagination)) {
				$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
			}
		}
			
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}

	/* DELETE COLLECTION AND ITEMS FROM COLLECTIONS */
	public function deleteAction()
	{
		$request = $this->getRequest();
		$collectionID = $request->getRequest('delete');
		
		if(!$collectionID || !is_numeric($collectionID)) {
			return $this->forward('error', 'error404');
		}
		
		$image = new Helper_Images;
		$referer = $request->getServer('HTTP_REFERER');
		
		if($collectionID) {
	    	$collection = Model_Collections::get($collectionID);
			
			if(!$collection) {
				return $this->forward('error', 'error404');
			}
			
			if(JO_Session::get('user_id') == $collection['user_id']) {
				$itemID = $request->getParam('item');
				if($itemID) {
					Model_Collections::deleteBookmark($collectionID, $itemID);
					JO_Session::set('msg_success', 'You have successfully delete this item!');
				} else {
					Model_Collections::deleteCollection($collectionID);
					$image->deleteImages($collection['photo']);
	    			JO_Session::set('msg_success', 'You have successfully delete this collection!');
					
					if(stripos($referer, 'view_collection') !== false) {
						$referer = WM_Router::create($request->getBaseUrl() .'?controller=users&action=collections&username='. WM_Router::clearName(JO_Session::get('username')) .'/public/'. ($collection['public'] == 'true' ? 1 : 0));
					}
				}
	    	}
		}
		
		$this->redirect($referer);
	}
	
	/* CHANGE PUBLIC AND UPDATE */
	public function changeAction()
	{
		$request = $this->getRequest();
		$collectionID = $request->getRequest('change');
		
		if(!$collectionID || !is_numeric($collectionID)) {
			return $this->forward('error', 'error404');
		}
		
		$referer = $request->getServer('HTTP_REFERER');
		
		if($collectionID) {
	    	$collection = Model_Collections::get($collectionID);
			
			if(!$collection) {
				return $this->forward('error', 'error404');
			}
			
			if(JO_Session::get('user_id') == $collection['user_id']) {
				
				if($request->isPost()) {
					$model_images = new Model_Images;
					$image = $request->getFile('file_upload');
					
					if($image) {
						$users_path = '/collections/' . date('Y/m') . '/' . $id.'/';
						$upload_folder  = realpath(BASE_PATH . '/uploads');
						$upload_folder .= $users_path;
					
					
						$upload = new JO_Upload;
						$upload->setFile($image)
								->setExtension(array('.jpg','.jpeg','.png','.gif'))
								->setUploadDir($upload_folder);
					
						$new_name = md5(time() . serialize($image));
						
						if($upload->upload($new_name)) {
							$info = $upload->getFileInfo();
							if($info) {
								$file_path = $users_path . $info['name'];
								Model_Collections::editImage($collection['id'], $file_path);
								$model_images->deleteImages($collection['photo']);
							
							} else {
								JO_Session::set('msg_error', $this->translate('There was an unexpected error with uploading the file'));
							}
						}
					}
					
					$collection['name'] = $request->getPost('name') ? $request->getPost('name') : $this->translate('Bookmark Collection');
					$collection['description'] = $request->getPost('description');
					$collection['public'] = $request->getPost('publically_visible') ? 'false' : 'true';
				}
				
	    	   	Model_Collections::edit(array(
		    	    'collection_id'	=>    $collectionID,
		    	    'name'			=>    $collection['name'],
		    	    'description'	=>    $collection['description'],
		    	    'publically_visible' =>    $collection['public'] == 'false' ? 'true' : 'false'
		    	    )
				);
				
	    		JO_Session::set('msg_success', 'You have successfully update this collection!');
	    	}
		}
		
		$this->redirect($referer);
	} 
	
	public function uploadAction()
	{
		$request = $this->getRequest();
		if(!JO_Session::get('user_id')) {
            JO_Session::set('msg_error', 'You must be logged to view your collections');
            $this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=login'));
        }
		
		$model_images = new Model_Images;
		
        if($request->isPost()) {
            $image = $request->getFile('file_upload');
			$public = $request->getPost('publically_visible');
            $id = Model_Collections::add(array(
            	'name'	=>    $request->getPost('name') ? $request->getPost('name') : $this->translate('Bookmark Collection'),
            	'description'	=>    $request->getPost('description'),
            	'publically_visible'	=>   $public ? 'true' : 'false' 			
            ));
        
			if($image and $id) {
				$users_path = '/collections/' . date('Y/m') . '/' . $id.'/';
				$upload_folder  = realpath(BASE_PATH . '/uploads');
				$upload_folder .= $users_path;
			
			
				$upload = new JO_Upload;
				$upload->setFile($image)
						->setExtension(array('.jpg','.jpeg','.png','.gif'))
						->setUploadDir($upload_folder);
			
				$new_name = md5(time() . serialize($image)); 
	         
				if($upload->upload($new_name)) {
			    
					$info = $upload->getFileInfo();
					if($info) {
						$file_path = $users_path . $info['name'];
						
						Model_Collections::editImage($id, $file_path);
					
					} else {
						JO_Session::set('msg_error', $this->translate('There was an unexpected error with uploading the file'));
					}
				}
			}
			
			if($id && $request->issetPost('item_id')) {
				$itemID = $request->getPost('item_id');
				Model_Collections::bookmark($itemID, $id);
		        JO_Session::set('msg_success', 'You have successfully create collection and this item has been added to your collection');   
		        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=items&item_id='. $itemID));
			}
			
	    	if($id) {
	    		JO_Session::set('msg_success', 'You have successfully create collection.');
	    	}
			
			$url = WM_Router::create($request->getBaseUrl() . '?controller=users&action=collections&username='. WM_Router::clearName(JO_Session::get('username')) .'/public/'. ($public ? '1' : '0'));
			$this->redirect($url);
        }   
	}
	
	public function rate_collectionAction() {
		$request = $this->getRequest();
	    if(JO_Session::get('user_id') && $request->isPost()) {
	    	$collectionID = $request->getRequest('rate_collection');
    	    $collection = Model_Collections::get($collectionID);
 
    	    if($request->getPost('rate')) {
	        	$rating = floatval($request->getPost('rate'));
			}
			
        	if(!is_numeric($rating) || $rating > 5) {
        		$rating = 5;
        	} elseif($rating < 1) {
        		$rating = 1;
        	}
     
        	$collection = Model_Collections::rate($collection, $collectionID, $rating);
			
			$response = array(
				'error' => false,
				'id' => $collectionID,
				'votes' => $collection['votes'] .' '. ($collection['votes'] == 1 ? $this->translate('Vote') : $this->translate('Votes')),
				'message' => str_repeat('<img src="data/themes/images/star.png" alt="Star" />', $collection['rating'])
			);        
			
            die(json_encode($response));
      }
	}
}
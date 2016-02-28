<?php

class QueueupdateditemsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Updated items for approval'),
			'has_permision' => true,
			'menu' => self::translate('Items'),
			'in_menu' => true,
			'permision_key' => 'items',
			'sort_order' => 34000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}

    	$request = $this->getRequest();
    	
    	$this->view->page_num = $page = $request->getRequest('page', 1);
    	$this->view->sort = $request->getRequest('sort', 'DESC');
    	$this->view->order = $request->getRequest('order', 'i.id');
    	
    	$this->view->filter_id = $request->getQuery('filter_id');
    	$this->view->filter_name = $request->getQuery('filter_name');
    	$this->view->filter_username = $request->getQuery('filter_username');
    	$this->view->filter_price = $request->getQuery('filter_price');
    	$this->view->filter_sales = $request->getQuery('filter_sales');
    	$this->view->filter_profit = $request->getQuery('filter_profit');
    	$this->view->filter_free_request = $request->getQuery('filter_free_request');
    	$this->view->filter_free_file = $request->getQuery('filter_free_file');
    	$this->view->filter_weekly = $request->getQuery('filter_weekly');
    	
    	$url = '';
    	if($this->view->filter_id) {
    		$url .= '&filter_id=' . $this->view->filter_id;
    	}
    	if($this->view->filter_name) {
    		$url .= '&filter_name=' . $this->view->filter_name;
    	}
    	if($this->view->filter_username) {
    		$url .= '&filter_username=' . $this->view->filter_username;
    	}
    	if($this->view->filter_price) {
    		$url .= '&filter_price=' . $this->view->filter_price;
    	}
    	if($this->view->filter_sales) {
    		$url .= '&filter_sales=' . $this->view->filter_sales;
    	}
    	if($this->view->filter_profit) {
    		$url .= '&filter_profit=' . $this->view->filter_profit;
    	}
    	if($this->view->filter_free_request) {
    		$url .= '&filter_free_request=' . $this->view->filter_free_request;
    	}
    	if($this->view->filter_free_file) {
    		$url .= '&filter_free_file=' . $this->view->filter_free_file;
    	}
    	if($this->view->filter_weekly) {
    		$url .= '&filter_weekly=' . $this->view->filter_weekly;
    	}
    	
    	$url1 = '';
    	if($this->view->sort) {
    		$url1 .= '&sort=' . $this->view->sort;
    	}
    	if($this->view->order) {
    		$url1 .= '&order=' . $this->view->order;
    	}
    	
    	$url2 = '&page=' . $page;
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit'),
    		'sort' => $this->view->sort,
    		'order' => $this->view->order,
    		'filter_id' => $this->view->filter_id,
    		'filter_name' => $this->view->filter_name,
    		'filter_username' => $this->view->filter_username,
    		'filter_price' => $this->view->filter_price,
    		'filter_sales' => $this->view->filter_sales,
    		'filter_profit' => $this->view->filter_profit,
    		'filter_free_request' => $this->view->filter_free_request,
    		'filter_free_file' => $this->view->filter_free_file,
    		'filter_weekly' => $this->view->filter_weekly,
    		'filter_update' => true
    	);
    	
    	$this->view->items = array(); 
    	$items = Model_Items::getTempItems($data);
    	
    	if($items) {
    		foreach($items AS $item) {
    			$this->view->items[] = array(
    				'id' => $item['id'],
    				'name' => $item['name'],
    				'username' => $item['username'],
    				'name' => $item['name'],
    				'price' => WM_Currency::format($item['price']),
    				'sales' => $item['sales'],
    				'profit' => WM_Currency::format($item['earning']),
    				'free_request' => $item['free_request'] == 'true',
    				'free_file' => $item['free_file'] == 'true',
    				'weekly_from' => ( $item['weekly_from'] != '0000-00-00' ? JO_Date::getInstance($item['weekly_from'],'dd.mm.yy',true)->toString() : '' ),
    				'weekly_to' => ( $item['weekly_to'] != '0000-00-00' ? JO_Date::getInstance($item['weekly_to'],'dd.mm.yy',true)->toString() : '' ),
    				'comments' => $item['comments'],
    				'edit' => $request->getModule() . '/queueupdateditems/edit/?m='.$item['module'].'&id=' . $item['id'] . $url . $url1 .$url2,
    				'comments_href' => $request->getModule() . '/queueupdateditems/comments/?id=' . $item['id'] . $url . $url1 .$url2,
    			);
    		}
    	}
    	
    	$this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $request->getModule() . '/queueupdateditems/?order=i.id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_name = $request->getModule() . '/queueupdateditems/?order=i.name&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $request->getModule() . '/queueupdateditems/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $request->getModule() . '/queueupdateditems/?order=i.price&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_sales = $request->getModule() . '/queueupdateditems/?order=i.sales&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_earning = $request->getModule() . '/queueupdateditems/?order=i.earning&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_free_request = $request->getModule() . '/queueupdateditems/?order=i.free_request&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_free_file = $request->getModule() . '/queueupdateditems/?order=i.free_file&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
    	$total_records = Model_Items::getTotalItems($data);
    	
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/queueupdateditems/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Items::deleteItemUpdate($this->getRequest()->getPost('id'));
	}
	
	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Items::deleteItemUpdate($record_id);
			}
		}
	}
	
	

	public function liveSearchAction() {
		$this->noViewRenderer();
		
		$request = $this->getRequest();
		$type = $request->getQuery('filter');
		
		$json = array();
		
		switch ($type) {
			case 'item':
				$items = Model_Items::getItems(array(
					'start' => 0,
					'limit' => 100,
					'filter_name' => $request->getQuery('term'),
    				'filter_status' => 'queue'
				));
				if($items) {
					$cache = array();
					foreach($items AS $item) {
						if(!isset($cache[$item['name']])) {
							$json[] = array(
								'id' 	=> $item['id'],
								'label' => $item['name'],
								'value' => $item['name']
							);
							$cache[$item['name']] = true;
						}
					}
				}
			break;
			case 'user':
				$users = Model_Users::getUsers(array(
					'start' => 0,
					'limit' => 100,
					'filter_username' => $request->getQuery('term')
				));
				if($users) {
					$cache = array();
					foreach($users AS $user) {
						if(!isset($cache[$user['username']])) {
							$json[] = array(
								'id' 	=> $user['user_id'],
								'label' => $user['username'],
								'value' => $user['username']
							);
							$cache[$user['username']] = true;
						}
					}
				}
			break;
			case 'tags':
				$tags = Model_Tags::getTags(array(
					'start' => 0,
					'limit' => 100,
					'filter_name' => $request->getQuery('term')
				));
				if($tags) {
					$cache = array();
					foreach($tags AS $tag) {
						if(!isset($cache[$tag['name']])) {
							$json[] = array(
								'id' 	=> $tag['id'],
								'label' => $tag['name'],
								'value' => $tag['name']
							);
							$cache[$tag['name']] = true;
						}
					}
				}
			break;
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		
    	echo JO_Json::encode($json);
	}
	
	public function editAction() {
		if(!$this->getRequest()->getQuery('m')) {
			$item = Model_Items::getItem($this->getRequest()->getQuery('id'));
			if($item) {
				$this->getRequest()->setQuery('m', $item['module']);
			}
		}
		$this->forward('queueupdateditems_' . $this->getRequest()->getQuery('m'), 'edit');
	}
	

}

?>
<?php

class ItemsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Item management'),
			'has_permision' => true,
			'menu' => self::translate('Items'),
			'in_menu' => true,
			'permision_key' => 'items',
			'sort_order' => 31000
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
    	$this->view->filter_user_id = $request->getQuery('filter_user_id');
    	$this->view->filter_web_profit = $request->getQuery('filter_web_profit');
    	$this->view->filter_refferals = $request->getQuery('filter_refferals');
    	
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
    	if($this->view->filter_user_id) {
    		$url .= '&filter_user_id=' . $this->view->filter_user_id;
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
    		'filter_user_id' => $this->view->filter_user_id,
    		'filter_web_profit' => $this->view->filter_web_profit,
    		'filter_refferals' => $this->view->filter_refferals,
    		'filter_status' => 'active'
    	);
    	
    	$this->view->items = array(); 
    	$items = Model_Items::getItems($data);
    	
    	if($items) {
    		foreach($items AS $item) {
    			$this->view->items[] = array(
    				'id' => $item['id'],
    				'user_id' => $item['user_id'],
    				'href' => WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&item_id=' . $item['id']),
    				'name' => $item['name'],
    				'username' => $item['username'],
    				'name' => $item['name'],
    				'price' => WM_Currency::format($item['price']),
    				'sales' => $item['sales'],					'receive' => WM_Currency::format($item['sum_receive']),
    				'profit' => WM_Currency::format($item['earning']),
    				'web_profit' => WM_Currency::format($item['web_profit']),
    				'web_profit2' => WM_Currency::format($item['web_profit2']),
    				'has_referral_sum' => $item['referral_sum'],
    				'referral_sum' => WM_Currency::format($item['referral_sum']),
    				'free_request' => $item['free_request'] == 'true',
    				'free_file' => $item['free_file'] == 'true',
    				'weekly_from' => ( $item['weekly_from'] != '0000-00-00' ? JO_Date::getInstance($item['weekly_from'],'dd.mm.yy',true)->toString() : '' ),
    				'weekly_to' => ( $item['weekly_to'] != '0000-00-00' ? JO_Date::getInstance($item['weekly_to'],'dd.mm.yy',true)->toString() : '' ),
    				'comments' => $item['comments'],
    				'edit' => $request->getModule() . '/items/edit/?m='.$item['module'].'&id=' . $item['id'] . $url . $url1 .$url2,
    				'comments_href' => $request->getModule() . '/items/comments/?id=' . $item['id'] . $url . $url1 .$url2,
    			);
    		}
    	}
    	
    	$this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $request->getModule() . '/items/?order=i.id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_name = $request->getModule() . '/items/?order=i.name&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $request->getModule() . '/items/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $request->getModule() . '/items/?order=i.price&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_sales = $request->getModule() . '/items/?order=i.sales&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_earning = $request->getModule() . '/items/?order=i.earning&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_free_request = $request->getModule() . '/items/?order=i.free_request&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_free_file = $request->getModule() . '/items/?order=i.free_file&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
    	$this->view->sort_web_profit = $request->getModule() . '/items/?order=web_profit2&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_referral_sum = $request->getModule() . '/items/?order=referral_sum&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
    	
    	$total_records = Model_Items::getTotalItems($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/items/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
    	
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Items::deleteItem($this->getRequest()->getPost('id'), $this->getRequest()->getPost('message'));
	}
	
	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Items::deleteItem($record_id);
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
    				'filter_status' => 'active'
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
		$this->forward('items_' . $this->getRequest()->getQuery('m'), 'edit');
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Items::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function commentsAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
    	
    	$request = $this->getRequest();
    	
		$url = '';
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_name')) { $url .= '&filter_name=' . $request->getQuery('filter_name'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_user_id')) { $url .= '&filter_user_id=' . $request->getQuery('filter_user_id'); }
    	if($request->getQuery('filter_price')) { $url .= '&filter_price=' . $request->getQuery('filter_price'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_profit')) { $url .= '&filter_profit=' . $request->getQuery('filter_profit'); }
    	if($request->getQuery('filter_free_request')) { $url .= '&filter_free_request=' . $request->getQuery('filter_free_request'); }
    	if($request->getQuery('filter_free_file')) { $url .= '&filter_free_file=' . $request->getQuery('filter_free_file'); }
    	if($request->getQuery('filter_weekly')) { $url .= '&filter_weekly=' . $request->getQuery('filter_weekly'); }
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	
    	$this->view->page_num = $page = $this->getRequest()->getRequest('p', 1);
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit'),
    		'filter_item_id' => $request->getQuery('id')
    	);
        
		$this->view->comments = array();
        $comments = Model_Comments::getComments($data);
        
        if($comments) {
        	foreach($comments AS $comment) {
        		$comment['datetime'] = JO_Date::getInstance($comment['datetime'], 'dd MM yy H:i:s', true)->toString();
        		$comment['href'] = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&action=comments&item_id=' . $comment['item_id'] . '&filter=' . ($comment['reply_to'] ? $comment['reply_to'] : $comment['id']));
            	$this->view->comments[] = $comment;
        	}
        } 
        
        $total_records = Model_Comments::getTotalComments($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/items/comments/?p={page}&id=' . $request->getQuery('id') . $url);
		$this->view->pagination = $pagination->render();
	}
	
	public function previewedCommentAction() {
		$this->noViewRenderer(true);
		Model_Comments::setPreviewed($this->getRequest()->getPost('id'));
	}
	
	public function deleteCommentAction() {
		$this->noViewRenderer(true);
		Model_Comments::deleteComment($this->getRequest()->getPost('id'));
	}
	
	
	
}

?>
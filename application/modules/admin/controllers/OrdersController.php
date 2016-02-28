<?php

class OrdersController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Sales'),
			'has_permision' => true,
			'menu' => self::translate('Sales'),
			'in_menu' => true,
			'permision_key' => 'sales',
			'sort_order' => 11000
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
    	$this->view->order = $request->getRequest('order', 'o.id');
    	
    	$this->view->filter_id = $request->getQuery('filter_id');
    	$this->view->filter_name = $request->getQuery('filter_name');
    	$this->view->filter_username = $request->getQuery('filter_username');
    	$this->view->filter_owner = $request->getQuery('filter_owner');
    	$this->view->filter_price = $request->getQuery('filter_price');
    	$this->view->filter_receive = $request->getQuery('filter_receive');
    	$this->view->filter_item_id = $request->getQuery('filter_item_id');
    	$this->view->filter_paid = $request->getQuery('filter_paid');
    	$this->view->filter_extended = $request->getQuery('filter_extended');
    	$this->view->filter_type = $request->getRequest('filter_type', 'buy');
    	$this->view->filter_from = $request->getQuery('filter_from');
    	$this->view->filter_to = $request->getQuery('filter_to');
    	$this->view->filter_paid_from = $request->getQuery('filter_paid_from');
    	$this->view->filter_paid_to = $request->getQuery('filter_paid_to');
    	$this->view->filter_order_id = $request->getQuery('filter_order_id');
    	$this->view->filter_web_receive = $request->getQuery('filter_web_receive');
    	
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
    	if($this->view->filter_owner) {
    		$url .= '&filter_owner=' . $this->view->filter_owner;
    	}
    	if($this->view->filter_price) {
    		$url .= '&filter_price=' . $this->view->filter_price;
    	}
    	if($this->view->filter_web_receive) {
    		$url .= '&filter_web_receive=' . $this->view->filter_web_receive;
    	}
    	if($this->view->filter_receive) {
    		$url .= '&filter_receive=' . $this->view->filter_receive;
    	}
    	if($this->view->filter_item_id) {
    		$url .= '&filter_item_id=' . $this->view->filter_item_id;
    	}
    	if($this->view->filter_paid) {
    		$url .= '&filter_paid=' . $this->view->filter_paid;
    	}
    	if($this->view->filter_extended) {
    		$url .= '&filter_extended=' . $this->view->filter_extended;
    	}
    	if($this->view->filter_type) {
    		$url .= '&filter_type=' . $this->view->filter_type;
    	}
    	if($this->view->filter_from) {
    		$url .= '&filter_from=' . $this->view->filter_from;
    	}
    	if($this->view->filter_to) {
    		$url .= '&filter_to=' . $this->view->filter_to;
    	}
    	if($this->view->filter_paid_from) {
    		$url .= '&filter_paid_from=' . $this->view->filter_paid_from;
    	}
    	if($this->view->filter_paid_to) {
    		$url .= '&filter_paid_to=' . $this->view->filter_paid_to;
    	}
    	if($this->view->filter_order_id) {
    		$url .= '&filter_order_id=' . $this->view->filter_order_id;
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
	    	'filter_owner' => $this->view->filter_owner,
	    	'filter_price' => $this->view->filter_price,
	    	'filter_receive' => $this->view->filter_receive,
	    	'filter_item_id' => $this->view->filter_item_id,
	    	'filter_paid' => $this->view->filter_paid,
	    	'filter_extended' => $this->view->filter_extended,
	    	'filter_type' => $this->view->filter_type,
	    	'filter_from' => $this->view->filter_from,
	    	'filter_to' => $this->view->filter_to,
	    	'filter_paid_from' => $this->view->filter_paid_from,
	    	'filter_paid_to' => $this->view->filter_paid_to,
	    	'filter_order_id' => $this->view->filter_order_id,
    		'filter_web_receive' => $this->view->filter_web_receive
    	);

    	$this->view->items = array(); 
    	$items = Model_Orders::getAll($data);
    	
    	$this->view->price = 0;
    	$this->view->web_profit2 = 0;
    	$this->view->profit = 0;
    	
    	if($items) {
    		foreach($items AS $item) { 
    			
				$item['web_profit2'] = $item['referral_sum'] > 0 ? ($item['web_profit'] - $item['referral_sum']) : $item['web_profit'];
				
    			$this->view->price += $item['price'];
		    	$this->view->web_profit2 += $item['web_profit2'];
		    	$this->view->profit += $item['receive'];
    			
    			$this->view->items[] = array(
    				'id' => $item['id'],
    				'order_id' => $item['order_id'],
    				'name' => $item['item_name'],
    				'username' => $item['username'],
    				'owner' => $item['owner'],
    				'href' => WM_Router::create($request->getBaseUrl() . '?module=' . $item['module'] . '&controller=items&item_id=' . $item['item_id']),
    				'price' => WM_Currency::format($item['price']),
    				'profit' => WM_Currency::format($item['receive']),
    				'web_profit' => WM_Currency::format($item['web_profit']),
    				'web_profit2' => WM_Currency::format($item['web_profit2']),
    				'has_referral_sum' => $item['referral_sum'],
    				'referral_sum' => WM_Currency::format($item['referral_sum']),
    				'datetime' => JO_Date::getInstance($item['datetime'],'dd.mm.yy',true)->toString(),
    				'paid' => $item['paid'] == 'true',
    				'paid_datetime' => ( $item['paid'] == 'true' ? JO_Date::getInstance($item['paid_datetime'],'dd.mm.yy',true)->toString() : '' ),
    				'extended' => $item['extended'] == 'true',
    				'type' => $item['type'],
    				'edit' => $request->getModule() . '/orders/edit/?id=' . $item['id'] . $url . $url1 .$url2,
    				'has_referal' => ( $item['type'] == 'buy' ? Model_Orders::getTotal(array('filter_order_id' => $item['id'], 'filter_type' => 'referal')) : 0 ),
    				'has_buy' => ( $item['type'] != 'buy' ? Model_Orders::getTotal(array('filter_id' => $item['order_id'], 'filter_type' => 'buy')) : 0 )
    			);
    		}
    	}
    	
    	$this->view->price = WM_Currency::format($this->view->price);
    	$this->view->web_profit2 = WM_Currency::format($this->view->web_profit2);
    	$this->view->profit = WM_Currency::format($this->view->profit);
    	
    	$this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $request->getModule() . '/orders/?order=o.id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_name = $request->getModule() . '/orders/?order=o.name&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $request->getModule() . '/orders/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_owner = $request->getModule() . '/orders/?order=u2.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $request->getModule() . '/orders/?order=o.price&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_receive = $request->getModule() . '/orders/?order=o.receive&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_web_receive = $request->getModule() . '/orders/?order=web_profit2&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_datetime = $request->getModule() . '/orders/?order=o.datetime&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_paid = $request->getModule() . '/orders/?order=o.paid&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_paid_datetime = $request->getModule() . '/orders/?order=o.paid_datetime&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_extended = $request->getModule() . '/orders/?order=o.extended&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_type = $request->getModule() . '/orders/?order=o.type&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
    	$total_records = Model_Orders::getTotal($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/orders/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
		
		$this->view->order_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=index');
		$this->view->deposit_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=deposit');
		$this->view->membership_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=membership');
	}
	
	public function changeStatusAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Orders::changeStatus($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Orders::delete($this->getRequest()->getPost('id'));
	}

	public function deleteMultiAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Orders::deleteMulti($this->getRequest()->getPost('action_check'));
	}

	public function liveSearchAction() {
		$this->noViewRenderer();
		
		$request = $this->getRequest();
		$type = $request->getQuery('filter');
		
		$json = array();
		
		switch ($type) {
			case 'item':
				$items = Model_Orders::getAll(array(
					'start' => 0,
					'limit' => 100,
					'filter_name' => $request->getQuery('term')
				));
				if($items) {
					$cache = array();
					foreach($items AS $item) {
						if(!isset($cache[$item['item_name']])) {
							$json[] = array(
								'id' 	=> $item['id'],
								'label' => $item['item_name'],
								'value' => $item['item_name']
							);
							$cache[$item['item_name']] = true;
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
	
	public function depositAction() {
		$request = $this->getRequest();
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
    	
    	$this->view->page_num = $page = $request->getRequest('page', 1);
    	$this->view->sort = strtolower($request->getRequest('sort', 'ASC'));
    	$this->view->order = $request->getRequest('order', 'd.datetime');
    	
    	$this->view->filter_id = $request->getQuery('filter_id');
    	$this->view->filter_username = $request->getQuery('filter_username');
    	$this->view->filter_price = $request->getQuery('filter_price');
    	$this->view->filter_extended = $request->getQuery('filter_datetime');
    	
    	$url = '';
    	if($this->view->filter_id) {
    		$url .= '&filter_id=' . $this->view->filter_id;
    	}
    	if($this->view->filter_username) {
    		$url .= '&filter_username=' . $this->view->filter_username;
    	}
    	if($this->view->filter_price) {
    		$url .= '&filter_price=' . $this->view->filter_price;
    	}
		
    	$url1 = '';
    	if($this->view->sort) {
    		$url1 .= '&sort=' . $this->view->sort;
    	}
    	if($this->view->order) {
    		$url1 .= '&order=' . $this->view->order;
    	}
    	
    	$url2 = '&page=' . $page;
    	
		$this->view->sort_id = $request->getModule() . '/orders/deposit/?order=d.id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $request->getModule() . '/orders/deposit/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $request->getModule() . '/orders/deposit/?order=d.deposit&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_datetime = $request->getModule() . '/orders/deposit/?order=d.datetime&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
		
    	$data = array(
    		'sort' => $this->view->sort,
    		'order' => $this->view->order,
    		'filter_id' => $this->view->filter_id,
	    	'filter_username' => $this->view->filter_username,
	    	'filter_price' => $this->view->filter_price
    	);
		
		$this->view->items = array();
		$items = Model_Deposit::getNoPaid($data);
		if($items) {
			$total_records = count($items);
			$limit = JO_Registry::get('admin_limit');
			
			$this->view->total_pages = ceil($total_records / $limit);
			$this->view->total_rows = $total_records;
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setTotal($total_records);
			$pagination->setUrl($this->getRequest()->getModule() . '/orders/deposit/?page={page}' . $url . $url1);
			$this->view->pagination = $pagination->render();
			
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
			
			$items = array_slice($items, $start, $limit);
			
			foreach($items as $item) {
				$item['price'] = WM_Currency::format($item['deposit']);
				$this->view->items[] = $item;
			}
		}
		
		$this->view->order_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=index');
		$this->view->deposit_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=deposit');
		$this->view->membership_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=membership');
	}

	public function change_depositAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Deposit::change_deposit($this->getRequest()->getPost('id'));
	}

	public function delete_depositAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Deposit::delete_deposit($this->getRequest()->getPost('id'));
	}
	
	public function deleteMultiDepositAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Deposit::deleteMulti($this->getRequest()->getPost('action_check'));
	}
	
	public function membershipAction() {
		$request = $this->getRequest();
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
    	
    	$this->view->page_num = $page = $request->getRequest('page', 1);
    	$this->view->sort = strtolower($request->getRequest('sort', 'ASC'));
    	$this->view->order = $request->getRequest('order', 'm.datetime');
    	
    	$this->view->filter_id = $request->getQuery('filter_id');
    	$this->view->filter_username = $request->getQuery('filter_username');
    	$this->view->filter_price = $request->getQuery('filter_price');
    	$this->view->filter_extended = $request->getQuery('filter_datetime');
    	
    	$url = '';
    	if($this->view->filter_id) {
    		$url .= '&filter_id=' . $this->view->filter_id;
    	}
    	if($this->view->filter_username) {
    		$url .= '&filter_username=' . $this->view->filter_username;
    	}
    	if($this->view->filter_price) {
    		$url .= '&filter_price=' . $this->view->filter_price;
    	}
		
    	$url1 = '';
    	if($this->view->sort) {
    		$url1 .= '&sort=' . $this->view->sort;
    	}
    	if($this->view->order) {
    		$url1 .= '&order=' . $this->view->order;
    	}
    	
    	$url2 = '&page=' . $page;
    	
		$this->view->sort_id = $request->getModule() . '/orders/membership/?order=m.id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $request->getModule() . '/orders/membership/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_price = $request->getModule() . '/orders/membership/?order=m.amount&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_datetime = $request->getModule() . '/orders/membership/?order=m.datetime&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
		
    	$data = array(
    		'sort' => $this->view->sort,
    		'order' => $this->view->order,
    		'filter_id' => $this->view->filter_id,
	    	'filter_username' => $this->view->filter_username,
	    	'filter_price' => $this->view->filter_price
    	);
		
		$this->view->items = array();
		$items = Model_Membership::getNoPaid($data);
		if($items) {
			$total_records = count($items);
			$limit = JO_Registry::get('admin_limit');
			
			$this->view->total_pages = ceil($total_records / $limit);
			$this->view->total_rows = $total_records;
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setTotal($total_records);
			$pagination->setUrl($this->getRequest()->getModule() . '/orders/membership/?page={page}' . $url . $url1);
			$this->view->pagination = $pagination->render();
			
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
			
			$items = array_slice($items, $start, $limit);
			
			foreach($items as $item) {
				$item['amounte'] = WM_Currency::format($item['amount']);
				$this->view->items[] = $item;
			}
		}
		
		$this->view->order_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=index');
		$this->view->deposit_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=deposit');
		$this->view->membership_link = WM_Router::create($request->getBaseUrl() .'?controller=admin/orders&action=membership');
	}

	public function delete_membershipAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Membership::delete_membership($this->getRequest()->getPost('id'));
	}
	
	public function deleteMultiMembershipAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Membership::deleteMulti($this->getRequest()->getPost('action_check'));
	}
	
	public function change_membershipAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Membership::change_membership($this->getRequest()->getPost('id'));
	}
}

?>
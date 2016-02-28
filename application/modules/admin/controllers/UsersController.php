<?php

class UsersController extends JO_Action {
	
	public static function config() {
		$is_singlesignon = false;
		if(JO_Registry::get('singlesignon_db_users') && JO_Registry::get('singlesignon_db_users') != JO_Db::getDefaultAdapter()->getConfig('dbname')) {
			$is_singlesignon = true;	
		}
		
		return array(
			'name' => self::translate('Users management'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 21000,
			'is_singlesignon' => $is_singlesignon
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
        
    	$reques = $this->getRequest();
    	
    	$this->view->sort = $reques->getRequest('sort', 'DESC');
    	$this->view->order = $reques->getRequest('order', 'i.id');
    	$this->view->page_num = $page = $reques->getRequest('page', 1);
    	
    	$this->view->filter_id = $reques->getQuery('filter_id');
    	$this->view->filter_username = $reques->getQuery('filter_username');
    	$this->view->filter_total = $reques->getQuery('filter_total');
    	$this->view->filter_sales = $reques->getQuery('filter_sales');
    	$this->view->filter_sold = $reques->getQuery('filter_sold');
    	$this->view->filter_web_profit2 = $reques->getQuery('filter_web_profit2');
    	$this->view->filter_commission = $reques->getQuery('filter_commission');
    	$this->view->filter_items = $reques->getQuery('filter_items');
    	$this->view->filter_referals = $reques->getQuery('filter_referals');
    	$this->view->filter_referal_money = $reques->getQuery('filter_referal_money');
    	$this->view->filter_featured_author = $reques->getQuery('filter_featured_author');
    	
    	$url = '';
    	if($this->view->filter_id) { $url .= '&filter_id=' . $this->view->filter_id; }
    	if($this->view->filter_username) { $url .= '&filter_username=' . $this->view->filter_username; }
    	if($this->view->filter_total) { $url .= '&filter_total=' . $this->view->filter_total; }
    	if($this->view->filter_sales) { $url .= '&filter_sales=' . $this->view->filter_sales; }
    	if($this->view->filter_sold) { $url .= '&filter_sold=' . $this->view->filter_sold; }
    	if($this->view->filter_web_profit2) { $url .= '&filter_web_profit2=' . $this->view->filter_web_profit2; }
    	if($this->view->filter_commission) { $url .= '&filter_commission=' . $this->view->filter_commission; }
    	if($this->view->filter_items) { $url .= '&filter_items=' . $this->view->filter_items; }
    	if($this->view->filter_referals) { $url .= '&filter_referals=' . $this->view->filter_referals; }
    	if($this->view->filter_referal_money) { $url .= '&filter_referal_money=' . $this->view->filter_referal_money; }
    	if($this->view->filter_featured_author) { $url .= '&filter_featured_author=' . $this->view->filter_featured_author; }
    	
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
    		'filter_username' => $this->view->filter_username,
    		'filter_total' => $this->view->filter_total,
    		'filter_sales' => $this->view->filter_sales,
    		'filter_sold' => $this->view->filter_sold,
    		'filter_web_profit2' => $this->view->filter_web_profit2,
    		'filter_commission' => $this->view->filter_commission,
    		'filter_items' => $this->view->filter_items,
    		'filter_referals' => $this->view->filter_referals,
    		'filter_referal_money' => $this->view->filter_referal_money,
    		'filter_featured_author' => $this->view->filter_featured_author
    	);
    	
		$this->view->users = array();
        $users = Model_Users::getUsers($data);
        
        $percentsClass = new Model_Percents;
        
        if($users) {
            
            foreach($users AS $user) {
            	if(!isset($user['sum_referals'])) $user['sum_referals'] = 0;
                
                $user['deposit'] = WM_Currency::format($user['deposit']);
                $user['earning'] = WM_Currency::format($user['earning']);
                $user['total'] = WM_Currency::format($user['total']);
                $user['sold'] = WM_Currency::format($user['sum_price']);
				$user['has_referral_sum'] = $user['sum_referals'];
								$user['web_profit'] = WM_Currency::format($user['sum_price'] - $user['sum_receive']);
				$user['web_profit2'] = WM_Currency::format($user['sum_price'] - $user['sum_receive'] - $user['sum_referals']);
				$user['referal_money'] = WM_Currency::format($user['sum_referals']);
                
                $comision = $percentsClass->getPercentRow($user['user_id']);
                $user['commission'] = round($comision['percent']);
                $user['sum'] = Model_Balance::getTotalUserBalanceByType($user['user_id']);
                
    			$user['referral_sum'] = WM_Currency::format($user['sum_referals']);
                $user['edit_href'] = $reques->getModule() . '/users/edite/?id=' . $user['user_id'] . $url . $url1 . $url2;
                $user['balance_href'] = $reques->getModule() . '/users/balance/?id=' . $user['user_id'] . $url . $url1 . $url2;
    			
                $this->view->users[] = $user;
            }
        } 
        
        $this->view->sort = strtolower($this->view->sort);
    	
    	$this->view->sort_id = $reques->getModule() . '/users/?order=u.user_id&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_username = $reques->getModule() . '/users/?order=u.username&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_total = $reques->getModule() . '/users/?order=u.total&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_sales = $reques->getModule() . '/users/?order=u.sales&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_sold = $reques->getModule() . '/users/?order=u.sold&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_web_profit2 = $reques->getModule() . '/users/?order=u.web_profit2&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_commission = $reques->getModule() . '/users/?order=u.commission&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_items = $reques->getModule() . '/users/?order=u.items&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_referals = $reques->getModule() . '/users/?order=u.referals&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_referal_money = $reques->getModule() . '/users/?order=u.referal_money&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	$this->view->sort_featured_author = $reques->getModule() . '/users/?order=u.featured_author&sort=' . ($this->view->sort == 'asc' ? 'DESC' : 'ASC') . $url . $url2;
    	
        
        $total_records = Model_Users::getTotalUsers($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/users/?page={page}' . $url . $url1);
		$this->view->pagination = $pagination->render();
        
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
	
//	public function createAction() {
//		$this->setViewChange('form');
//		
//		if($this->getRequest()->isPost()) {
//    		Model_Users::createUser($this->getRequest()->getParams());
//    		$this->session->set('successfu_edite', true);
//    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/');
//    	}
//		
//		$this->getForm();
//	}
	
	public function editeAction() {
		$this->setViewChange('form');
		
		$request = $this->getRequest();
		
		if($request->isPost()) {
    		Model_Users::editeUser($request->getQuery('id'), $request->getParams());
    		$this->session->set('successfu_edite', true);
    		$url = '';
    		if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    		if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    		if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    		if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    		if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    		if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
    		if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    		if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
    		if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
    		if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
    		if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    		if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
    		if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
    		if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
    		
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url);
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Users::deleteUser($this->getRequest()->getPost('id'));
	}
	
	public function changeAuthorAction() {
		$this->setInvokeArg('noViewRenderer',true);
		Model_Users::changeAuthor($this->getRequest()->getPost('id'));
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$user_id = $request->getQuery('id');
		
		$url = '';
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
    	if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
    	if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    	if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
    	if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
    	if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
    		
    	$this->view->cancel_href = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url;
		
		$model_users = new Model_Users;
		
		if($user_id) {
			$user_info = $model_users->getUser($user_id);
		}
		
		if(isset($user_info)) {
			$this->view->user_id = $user_id;
			$this->view->username = $user_info['username'];
			$this->view->names = $user_info['firstname'] . ' ' . $user_info['lastname'];
			$this->view->email = $user_info['email'];
			$this->view->exclusive_author = $user_info['exclusive_author'];
			$model_images = new Model_Images();
			$this->view->avatar = $model_images->resize($user_info['avatar'], 80, 80, true);
			$this->view->profile_title = $user_info['profile_title'];
			$this->view->profile_desc = $user_info['profile_desc'];
			$this->view->country_id = $user_info['country_id'];
			$this->view->author_status = $user_info['author_status'];
			$this->view->author_status_description = $user_info['author_status_description'];
			$country_info = Model_Countries::getCountry($user_info['country_id']);
			if($country_info) {
				$this->view->country = $country_info['name'];
			}
			$this->view->live_city = $user_info['live_city'];
			
			$register_datetime = new JO_Date($user_info['register_datetime'], 'dd MM yy');
			$this->view->register_datetime = $register_datetime->toString();
			$last_login_datetime = new JO_Date($user_info['last_login_datetime'], 'dd MM yy');
			$this->view->last_login_datetime = $last_login_datetime->toString();
			
			$other = Model_Users::getStatistic($user_id);
			
			$this->view->deposit = WM_Currency::format($other['deposit']);
			$this->view->total = WM_Currency::format($other['total']);
			
			$this->view->items = array();
			foreach($other['items'] AS $item) {
				
				$datetime = new JO_Date($item['datetime'], 'dd MM yy');
				
				$this->view->items[] = array(
					'id' => $item['item_id'],
					'item_name' => $item['item_name'],
					'price' => WM_Currency::format($item['price']),
					'href' => WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&item_id=' . $item['item_id']),
					'datetime' => $datetime->toString()
				);
			}
			
		}
		
		if($request->getPost('commission_percent')) {
			$this->view->commission_percent = $request->getPost('commission_percent');
		} elseif(isset($user_info)) {
			$this->view->commission_percent = $user_info['commission_percent'];
		}
		
		if($request->getPost('featured_author')) {
			$this->view->featured_author = $request->getPost('featured_author');
		} elseif(isset($user_info)) {
			$this->view->featured_author = $user_info['featured_author'];
		}
		
		if($request->getPost('status')) {
			$this->view->status = $request->getPost('status');
		} elseif(isset($user_info)) {
			$this->view->status = $user_info['status'];
		}
		
		if($request->getPost('groups')) {
			$this->view->groups = $request->getPost('groups');
		} elseif(isset($user_info)) {
			$this->view->groups = (array)unserialize($user_info['groups']);
		} else {
			$this->view->groups = array();
		}
		
		if($request->getPost('badges')) {
			$this->view->badges = $request->getPost('badges');
		} elseif(isset($user_info)) {
			$this->view->badges = explode(',', $user_info['badges']);
		} else {
			$this->view->badges = array();
		}
		
		$this->view->groups_list = Model_Usergroups::getGroups();
		
		$this->view->badges_list = Model_Badges::getBadges(array(
			'filter_type' => 'other'
		));
		
	} 
	
	////////////////////////// balance
	
	public function balanceAction() {
		
		$this->view->page_num = $this->getRequest()->getQuery('page');
		
		$this->view->user_id = $balance_id = $this->getRequest()->getQuery('id');
		
		$request = $this->getRequest();
		
		$url = '';
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
    	if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
    	if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    	if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
    	if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
    	if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
    	if($request->getQuery('id')) { $url .= '&id=' . $request->getQuery('id'); }
    		
		$user_info = Model_Users::getUser($balance_id);
		
		if(!$user_info) {
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url);
		}
		
		$this->view->createBalans = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/createBalance/?' . $url;
		
		$this->view->back_href = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?' . $url;
		
		
		$this->view->username = $user_info['username'];
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
        
		$this->view->balances = array();
        $balances = Model_Users::getBalances($balance_id);
        
        if($balances) {
            
            foreach($balances AS $balance) {
            	
            	$last_login_datetime = new JO_Date($balance['datetime'], 'dd MM yy');
                $balance['datetime'] = $last_login_datetime->toString();
                $balance['deposit'] = WM_Currency::format($balance['deposit']);
                $balance['edit'] = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/editBalance/?bid=' . $balance['id'] . $url;
                $this->view->balances[] = $balance;
            }
        }    
	}
	
	public function createBalanceAction() {
		$this->setViewChange('form_balance');
		
		if($this->getRequest()->isPost()) {
    		Model_Users::createBalance($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$request = $this->getRequest();
			
			$url = '';
	    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
	    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
	    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
	    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
	    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
	    	if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
	    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
	    	if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
	    	if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
	    	if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
	    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
	    	if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
	    	if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
	    	if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
	    	if($request->getQuery('id')) { $url .= '&id=' . $request->getQuery('id'); }
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/balance/?id=' . $this->getRequest()->getQuery('id') . $url);
    	}
		
		$this->getBalanceForm();
	}
	
	public function editBalanceAction() {
		$this->setViewChange('form_balance');
		
		if($this->getRequest()->isPost()) {
    		Model_Users::editeBalance($this->getRequest()->getQuery('bid'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
			$request = $this->getRequest();
			
			$url = '';
	    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
	    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
	    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
	    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
	    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
	    	if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
	    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
	    	if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
	    	if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
	    	if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
	    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
	    	if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
	    	if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
	    	if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
	    	if($request->getQuery('id')) { $url .= '&id=' . $request->getQuery('id'); }
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/balance/?id=' . $this->getRequest()->getQuery('id') . $url);
    	}
		
		$this->getBalanceForm();
	}
	
	public function deleteBalanceAction() {
		$this->noViewRenderer(true);
		if($this->getRequest()->isPost()) {
    		Model_Users::deleteBalance($this->getRequest()->getPost('id'));
    	}
	}
	
	private function getBalanceForm() {
		$request = $this->getRequest();
		
		$this->view->page_num = $this->getRequest()->getQuery('page');
		
		$this->view->user_id = $balance_id = $this->getRequest()->getQuery('id');
		
		$user_info = Model_Users::getUser($balance_id);
		
		if(!$user_info) {
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/?page=' . $this->view->page_num);
		}
		
		
		$request = $this->getRequest();
		
		$url = '';
    	if($request->getQuery('sort')) { $url .= '&sort=' . $request->getQuery('sort'); }
    	if($request->getQuery('order')) { $url .= '&order=' . $request->getQuery('order'); }
    	if($request->getQuery('page')) { $url .= '&page=' . $request->getQuery('page'); }
    	if($request->getQuery('filter_id')) { $url .= '&filter_id=' . $request->getQuery('filter_id'); }
    	if($request->getQuery('filter_username')) { $url .= '&filter_username=' . $request->getQuery('filter_username'); }
    	if($request->getQuery('filter_total')) { $url .= '&filter_total=' . $request->getQuery('filter_total'); }
    	if($request->getQuery('filter_sales')) { $url .= '&filter_sales=' . $request->getQuery('filter_sales'); }
    	if($request->getQuery('filter_sold')) { $url .= '&filter_sold=' . $request->getQuery('filter_sold'); }
    	if($request->getQuery('filter_web_profit2')) { $url .= '&filter_web_profit2=' . $request->getQuery('filter_web_profit2'); }
    	if($request->getQuery('filter_commission')) { $url .= '&filter_commission=' . $request->getQuery('filter_commission'); }
    	if($request->getQuery('filter_items')) { $url .= '&filter_items=' . $request->getQuery('filter_items'); }
    	if($request->getQuery('filter_referals')) { $url .= '&filter_referals=' . $request->getQuery('filter_referals'); }
    	if($request->getQuery('filter_referal_money')) { $url .= '&filter_referal_money=' . $request->getQuery('filter_referal_money'); }
    	if($request->getQuery('filter_featured_author')) { $url .= '&filter_featured_author=' . $request->getQuery('filter_featured_author'); }
    	if($request->getQuery('id')) { $url .= '&id=' . $request->getQuery('id'); }
    	
    	$this->view->back_href = $this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/users/balance/?' . $url;
		
		$this->view->username = $user_info['username'];
		
		$balance_id = $request->getQuery('bid');
		
		$model_users = new Model_Users;
		
		if($balance_id) {
			$balance_info = $model_users->getBalance($balance_id);
		}
		
		if($request->getPost('deposit')) {
			$this->view->deposit = $request->getPost('deposit');
		} elseif(isset($balance_info)) {
			$this->view->deposit = $balance_info['deposit'];
		}
		
		
	} 
	
	
	
}

<?php

class CategoriesController extends JO_Action {
	
    public function indexAction() {
		$request = $this->getRequest();
		
		JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
		
    	$category_id = $request->getParam('category_id');
		
		if(!$category_id) {
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
			
		if(is_numeric($category_id)) {
			
			$this->view->category = Model_Categories::get($category_id);
			$this->getLayout()->meta_title = $this->view->category['name'];
    	    $this->getLayout()->meta_description = $this->view->category['name'];
			
			if($this->view->category['sub_of'] == 0) {
			
				$categories = Model_Categories::getMain();
				
			} else {
			
				$categories = Model_Categories::getCategories($this->view->category['sub_of']);
				
				$crumbs_categories = Model_Categories::get_all();
				
				if($crumbs_categories) {
				
					$parents = Model_Categories::getCategoryParents($crumbs_categories, $category_id);
					$parents = explode(',', $parents);
					$parents = array_reverse($parents);
					
					$cnt = count($parents) - 1;
					$crumbs = array();
					for($i = 1; $i < $cnt; $i++) {
						$crumbs[$i] = array(
							'name' => $crumbs_categories[$parents[$i]]['name'],
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='. $parents[$i] .'&name='. WM_Router::clearName($crumbs_categories[$parents[$i]]['name']))
						);
					}
				}
			}
			
			/* SORT */
		
			$link = $request->getBaseUrl() . '?&controller=categories&category_id='. $category_id;
			
			$total_records = Model_Items::CountItems($category_id);
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
			$items = Model_Items::getAll($category_id, $start, $limit, ($sort == 'price' ? 'rprice' : $prefix . $sort) .' '. $order);
			
		} else if(in_array($category_id, array('recent', 'featured', 'popular'))) {

			switch($category_id) {
				case 'featured':
					$where = 'items.weekly_to >= \''. date('Y-m-d') .'\'';
					$this->view->category['name'] = $this->view->translate('Featured Items');
					$total_records = Model_Items::countWeekly();
				break;
				default:
					$this->view->category['name'] = $this->view->translate('Recent Items');
					$total_records = Model_Items::countItems();
			}
			
			$this->getLayout()->meta_title = $this->view->category['name'];
    	    $this->getLayout()->meta_description = $this->view->category['name'];
			
			/* SORT */
		
			$link = $request->getBaseUrl() . '?controller=categories&action='. $category_id;
			
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$last_page = max(ceil($total_records / $limit), 1);
				$start = (($last_page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}

			$items = Model_Items::getAll($category_id, $start, $limit, ($sort == 'price' ? 'rprice' : $prefix . $sort) .' '. $order, (!empty($where) ? $where : ''));
			$categories = Model_Categories::getMain();
		}
		
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
				'name' => $this->view->translate('sales'),
				'href' => WM_Router::create($link . '&sort=sales'),
				'is_selected' => ($sort == 'sales' ? true : false)
			),
			array(
				'name' => $this->view->translate('price'),
				'href' => WM_Router::create($link . '&sort=price'),
				'is_selected' => ($sort == 'price' ? true : false)
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
		
		/* CRUMBS */
		$this->view->crumbs = array();
		
		$this->view->crumbs[] = array(
			'name' => $this->view->translate('Home'),
			'href' => $request->getBaseUrl()
		);
		
		if(isset($crumbs)) {
			$this->view->crumbs += $crumbs;
		}
		
		/* CATEGORIES */
		if($categories) {
			$this->view->all_categories_name = $this->translate('All Categories');
			$this->view->categories = array();
			
			foreach($categories AS $k => $v) {
				$this->view->categories[$k] = array(
					'name' => $v['name'],
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='.$v['id'] .'&name='. WM_Router::clearName($v['name']))
				);
			}
		}
		
		/* PAGENATION */
		
		$link .= '&order='. $order;
		
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
		
		/* ITEMS */
		if($items) {
			
			$this->view->items = array();
			
	        foreach($items as $n => $item) {
	        	if(!empty($item['demo_url'])) {
	        		$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=preview&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name']));
				}
				
				$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }	
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
 
	public function recentAction() 
	{
		$this->noViewRenderer(true);
		$this->getRequest()->setParams('category_id', 'recent');
		
		$this->forward('categories','index');
	}
	
	public function featuredAction()
	{
		$this->noViewRenderer(true);
		$this->getRequest()->setParams('category_id', 'featured');
		
		$this->forward('categories','index');
	}
	
	public function popularAction()
	{
		$request = $this->getRequest();
    	$category_id = $request->getParam('category_id');
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'desc';
		}
		
		$sort = $request->getRequest('sort');
		if(is_null($sort)) {
			$sort = 'sales';
		}
		
		if($sort == 'username') {
			$prefix = 'users.';
		} else {
			$prefix = 'items.';
		}
					
		$date = $request->getRequest('date');
		
		if(empty($date)) 
			$date = date("m-Y", strtotime("-1 month"));
		
		$date_parts = explode('-', $date);
		
		$where = 'MONTH(`paid_datetime`) = \''. $date_parts[0] .'\' AND YEAR(`paid_datetime`) = \''. $date_parts[1] .'\'';
		$this->view->category['name'] = $this->view->translate('Popular Items');
		$this->getLayout()->meta_title = $this->view->category['name'];
    	$this->getLayout()->meta_description = $this->view->category['name'];
			
		$link = $request->getBaseUrl() . '?controller=categories&action=popular&page_id=date/'. $date;	
			
		$this->view->sort_by = array(
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
				'name' => $this->view->translate('sales'),
				'href' => WM_Router::create($link . '&sort=sales'),
				'is_selected' => ($sort == 'sales' ? true : false)
			),
			array(
				'name' => $this->view->translate('price'),
				'href' => WM_Router::create($link . '&sort=price'),
				'is_selected' => ($sort == 'price' ? true : false)
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
		
		/* CRUMBS */
		$this->view->crumbs = array();
		
		$this->view->crumbs[] = array(
			'name' => $this->view->translate('Home'),
			'href' => $request->getBaseUrl()
		);
		
		$total_records = Model_Items::getTopSellersCount($where);
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$last_page = max(ceil($total_records / $limit), 1);
			$start = (($last_page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		$items = Model_Items::getTopSellers($start, $limit, $where, $prefix . $sort .' '. $order);
		$categories = Model_Items::getPopularFilesDates();
		
		if($categories) {
			$this->view->all_categories_name = $this->translate('Period');
			$this->view->categories = array();
				
			foreach($categories as $k => $v) {
				$this->view->categories[$k] = array(
					'name' => $v['paid_date'],
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id=popular&page_id=date/'. str_replace(' ', '', $v['paid_date']))
				);
			}
		}
		
		/* PAGENATION */
		
		$link .= '&order='. $order;
		
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
		
		/* ITEMS */
		if($items) {
			$this->view->items = array();
			
	        foreach($items as $n => $item) {
	        	if(!empty($item['demo_url'])) {
	        		$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=preview&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name']));
        		}

				$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }	
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
		$this->setViewChange('index');
	}
}
<?php

class ItemsController extends JO_Action {
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	/* SHOW ITEM */
    public function indexAction() {
       if(JO_Session::get('order_id')) {
			JO_Session::clear('order_id');
		}
	   
       $request = $this->getRequest();
	   $image_model = new Helper_Images();
	   
	   JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
	   
       $this->view->currency = WM_Currency::getCurrency();

	   	$itemID = $request->getRequest('item_id');
    	$item = Model_Items::get($itemID);
    	
        if(!is_array($item) || $item['status'] == 'deleted' || (JO_Session::get('username') && $item['status'] == 'unapproved' && $item['user_id'] != $_SESSION['user']['user_id']) || $item['status'] == 'queue' || $item['status'] == 'extended_buy') {
    	    return $this->forward('error', 'error404');
    	}
		
		if(JO_Session::get('order_id')) {
			Model_Orders::delete(JO_Session::get('order_id'));
			JO_Session::clear('order_id');
		}
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
    	
		$user = Model_Users::getUser($item['user_id']);
		$user['userhref'] =WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($user['username']));
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Portfolio'),
				'href' => $user['userhref']
			),
			array(
				'name' => $item['name']
			)
		);
		
		$this->view->item_href = $this->view->form_action = $this->view->item_link = WM_Router::create($request->getBaseUrl() .'?controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$faqs_data = Model_Faq::getAll($itemID);
		if(JO_Session::get('user_id') == $item['user_id'] || $faqs_data) {
			$this->view->faq_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=faq&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		if(JO_Session::get('user_id') == $item['user_id']) {
			$this->view->edit_link = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&action=edit&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
			$this->view->delete_link = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&action=delete&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		$this->view->comment_link = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$this->view->screenshots = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=screenshots&item_id='.$item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		if(!empty($item['demo_url']) ) {
			$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=preview&item_id='.$item['id'].'&name='.WM_Router::clearName($item['name']));
		}
		
		$item['description'] = strip_tags(html_entity_decode($item['description']),'<br><p><span><h1><h2><h3><a><img><big><small><ul><ol><li>');
		
		$this->getLayout()->meta_title = $item['name'];
		$meta_description = substr(strip_tags(html_entity_decode($item['description'], ENT_QUOTES, 'utf-8')), 0, 255);
    	$this->getLayout()->meta_description = $meta_description;	
		
		if((int)JO_Registry::get($item['module'].'_items_screenshots_width') && (int)JO_Registry::get($item['module'].'_items_screenshots_height')) {
        	$item['big_image'] = $image_model->resize($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_screenshots_width'), JO_Registry::forceGet($item['module'].'_items_screenshots_height'), true);
        } elseif((int)JO_Registry::get($item['module'].'_items_screenshots_width')) {
        	$item['big_image'] = $image_model->resizeWidth($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_screenshots_width'));
        } elseif((int)JO_Registry::get($item['module'].'_items_screenshots_height')) {
        	$item['big_image'] = $image_model->resizeHeight($item['theme_preview_thumbnail'], JO_Registry::forceGet($item['module'].'_items_screenshots_height'));
        } else {
        	$item['big_image'] = false;
        }
		
		$this->view->otherItems = array();
    	$otherItems = Model_Items::getByUser($item['user_id'], 0, 4, false, 'id <> '. (int)$item['id']);
    	if($otherItems) {
    	    foreach($otherItems as $i => $ot) {
    	    	$this->view->otherItems[] = Helper_Items::returnViewIndex($ot);
    	    }
    	}
		
		$files = glob(dirname(__FILE__) . '/Payments/*.php');
		if($files) {
			$payments_data = $sort_order = $order_obj = array();
	    		foreach($files AS $row => $file) {
	    			if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
	    				$key = mb_strtolower($match[1], 'utf-8');
	    				if(JO_Registry::forceGet($key . '_status')) {
	    					JO_Loader::loadFile($file);
	    					
							$sort_order[$row] = (int)JO_Registry::forceGet($key . '_sort_order'); 
							
							$logo = JO_Registry::forceGet($key . '_logo');
							if($logo){
								list($width) = getimagesize('uploads/' . $logo);
								if($width > 70) {
									$logo = $image_model->resizeWidth($logo, 70);
								} else {
									$logo = 'uploads/' . $logo;
								}
							} else {
								$logo = '';
							}
							
							$payments_data[$row] = array(
								'name' => $this->translate($match[1]),
								'sort' => (int)JO_Registry::forceGet($key . '_sort_order'),
								'logo' => $logo,
							);
	    				}
	    			}
	    		}
	    	
	    	array_multisort($sort_order, SORT_ASC, $payments_data);
	    	
	    	$this->view->payments = $payments_data;
		}
		
		$this->view->your_profit = WM_Currency::format($item['your_profit']);
		$this->view->usertotal = WM_Currency::format(JO_Session::get('total'));
		$item['price'] = WM_Currency::format($item['price']);
		
		
		$this->view->user = $user;
		$this->view->item = $item;
		
		$membership = Model_Membership::getAll();
		if($membership) {
			$this->view->membership_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=membership');
			$this->view->membership_pay_link = WM_Router::create($request->getBaseUrl() . '?controller=items&action=pay_membership');
		}
		
        $this->view->deposit = WM_Router::create($request->getBaseUrl() . '?controller=users&action=deposit');
        $this->view->deposit_link = WM_Router::create($request->getBaseUrl() . '?controller=items&action=pay_deposit');
        $this->view->payment_link = WM_Router::create($request->getBaseUrl() . '?controller=items&action=payment');
		
    	$this->view->children = array();
		$this->view->children['rightside'] = 'items/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
		$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function rightsideAction() {
		$request = $this->getRequest();
		$model_images = new Helper_Images();
		
		if($request->getRequest('item_id')) {
			$itemID = $request->getRequest('item_id');
		} elseif($request->getRequest('comments')) {
			$itemID = $request->getRequest('comments');
		} else {
			$itemID = $request->getRequest('faq');
		}
		
    	$item = Model_Items::get($itemID);
		$user = Model_Users::getUser($item['user_id']);
		
		if($user['avatar']) {
			$user['avatar'] = $model_images->resize($user['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
		} else 
			$user['avatar'] = 'data/themes/images/noavatar.png';
		
		if(JO_Session::get('user_id')) {
			if($item['user_id'] != JO_Session::get('user_id')) {
	    		if(Model_Orders::isBuyed($item['id'], JO_Session::get('user_id'))) {
	    			$item['is_buyed'] = $this->translate('You have already bought this item. You can download it from <a href="#">here</a>, but if you like you can buy it again.');
					$replace = WM_Router::create($request->getBaseUrl() .'?controller=users&action=downloads');
					$item['is_buyed'] = str_replace('#', $replace, $item['is_buyed']);
	    		}
	    	} else {
	    		$item['is_buyed'] = $this->translate('This is one of your files');
	    	}
		}
		
		if(JO_Session::get('user_id') && JO_Session::get('user_id') == $item['user_id']) {
			$this->view->owner = true;
		}
		
		$item['price'] = WM_Currency::format($item['price']);
		
		if($item['extended_price']) {
			 $item['extended_price'] = WM_Currency::format( $item['extended_price']);
		}

		$item['datetime'] = JO_Date::getInstance($item['datetime'], 'd M yy')->getDate();
		
		$this->view->attributes = Model_Items::getAttributes($item['id']);
		$this->view->attributes_pic = array();
		if($this->view->attributes) { 
			foreach($this->view->attributes as $k => $v) {
				if($v['search'] == 'true') {
					$this->view->attributes[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?module='.$item['module'].'&controller=search&action=attributes/'.urlencode(mb_strtolower($v['category'], 'UTF-8')) .'/'.urlencode(mb_strtolower($v['name'], 'UTF-8')));
				}
				
				if(!empty($v['photo'])) {
					$this->view->attributes[$k]['photo'] = 'uploads/attributes/'. $v['photo'];
					$this->view->attributes_pic[] = $this->view->attributes[$k];
				}
				
				if(empty($v['name'])) {
					$this->view->attributes[$k]['name'] = $v['attribute_id'];
				}
			}
		}
		
		$user['homeimage'] = $model_images->resize($user['homeimage'], JO_Registry::forceGet('user_profile_photo_width'), JO_Registry::forceGet('user_profile_photo_height'), true);
		$user['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='.WM_Router::clearName($user['username']));
		
		$comments = Model_Comments::getTotal("item_id=". $item['id'] .' AND reply_to = 0');
		$item['comments'] = (int)$comments;
		
		$this->view->user_badges = Helper_Author::userBadges($user);
		
		if($item['prepaid_price'] == '0.00')
			$this->view->prepaid_price =  false;
		else
			$this->view->prepaid_price = WM_Currency::format($item['prepaid_price']);
		
		#COLLECTIONS
    	$this->view->bookmark_link = WM_Router::create($request->getBaseUrl() . '?controller=items&action=add_to_collection');
    	$this->view->upload_link = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=upload');
    	$this->view->base_url = urlencode($request->getBaseUrl());
		$this->view->full_url = urlencode($request->getFullUrl());
		
    	if(JO_Session::get('user_id')) {
    	    $this->view->bookcollections = Model_Collections::getByUser(0, 0, JO_Session::get('user_id'));
    	}
    	
		$this->view->tags = array();
	    if(isset($item['tags'])) {
	    	foreach($item['tags'] AS $key => $tag) {
	    		if($tag) {
		    		$this->view->tags[] = array(
		    			'name' => $tag,
		    			'href' => WM_Router::create($request->getBaseUrl() . '?controller=tags&tag='. $tag)
		    		);
	    		}
	    	}
	    }
		
		if($request->getAction() != 'comments') {
			$this->view->comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		if($request->getAction() == 'index') {
			$this->view->is_index = true;
		}
		
		$this->view->free_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=downloads&item_id='. $item['id']);
		$this->view->login_link = WM_Router::create($request->getBaseUrl() . '?controller=users&action=login');
		$user['license'] = unserialize($user['license']);
		
		$this->view->user = $user;
		$this->view->item = $item;
	}
	
	/* ITEM PAY */
	public function success_paymentAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
	    $info = Model_Orders::get(JO_Session::get('order_id'));
		
		if($info && $info['paid'] == 'true') {
			Model_Orders::orderIsPay($info['id']);
		    
		    JO_Session::set('msg_success', $this->translate('You have successfully made a payment!'));
		} else {
			JO_Session::clear('order_id');
		    JO_Session::set('msg_error', $this->translate('Your payment have status '. WM_Orderstatuses::orderStatuses($info['order_status_id'])));
		}
		
		$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads'));
	}
	
	/* RATE */
	public function rate_itemAction()
	{
		$request = $this->getRequest();
		if(JO_Session::get('user_id') && $request->isPost()) {
			
			if($request->getPost('rate')) {
	        	$rating = floatval($request->getPost('rate'));
			}
			
        	if(!is_numeric($rating) || $rating < 1) {
        		$rating = 1;
        	} elseif($rating > 5) {
        		$rating = 5;
        	}
			
			$id = $request->getPost('idBox');
			$item = Model_Items::rate($id, $rating);
			
			$response = array(
				'error' => false,
				'id' => $id,
				'votes' => $item['votes'] .' '. ($item['votes'] == 1 ? $this->translate('Vote') : $this->translate('Votes')),
				'message' => str_repeat('<img src="data/themes/images/star.png" alt="Star" />', $item['rating'])
			);
				
			die(json_encode($response));
		}
	}
	
	/* PAY WITH DEPOSIT */
	public function pay_depositAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->issetPost('item_id')) {
			
			$item = Model_Items::get($request->getPost('item_id'));
			
			if($request->getPost('licence') == 'personal') {
				
				if(JO_Session::get('total') < $item['prepaid_price']) {
					JO_Session::set('msg_error', 'You don\'t have enought money in your account');
				} else {
					Model_Orders::buy($item, $item['prepaid_price']);
					JO_Session::set('msg_success', 'You have successfully bought this item.');
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads'));
				}
			} elseif($request->getPost('licence') == 'extended') {
	    		if(JO_Session::get('total') < $item['extended_price']) {
					JO_Session::set('msg_error', 'You don\'t have enought money in your account');
				} else {
					Model_Orders::buy($item, $item['extended_price'], true);
					JO_Session::set('msg_success', 'You have successfully bought this item.');
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads'));
				}
			}
						
			$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name'])));
		} else {
			$this->redirect($request->getServer('HTTP_REFERER'));
		}
	}

	/* MEMBERSHIP BUY */
	public function pay_membershipAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->issetPost('item_id')) {
			if($request->getPost('licence') == 'personal') {
				$item = Model_Items::get($request->getPost('item_id'));
				$downloads = Model_Membership::getByUser(JO_Session::get('user_id'));
				
				if(!$downloads) {
					JO_Session::set('msg_error', $this->translate('Your membership download quota has been exhausted.'));
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=membership'));
				} else {
					Model_Orders::buy($item, $item['price']);
					Model_Membership::buy(JO_Session::get('user_id'));
					JO_Session::set('msg_success', $this->translate('You have successfully bought this item.'));
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads'));
				}
			} elseif($request->getPost('licence') == 'extended') {
	    		JO_Session::set('msg_error', $this->translate('Membership customers can only purchase items with personal license.'));
			}
		}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
	}

	/* BOOKMARK ITEM */
	public function add_to_collectionAction() {
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->isPost() && $request->issetPost('collection_id')) {
			$collectionID = $request->getPost('collection_id');
			$itemID = $request->getPost('item_id');
			
			Model_Collections::bookmark($itemID, $collectionID);
	        JO_Session::set('msg_success', $this->translate('This item has been added to your collection'));   
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=items&item_id='. $itemID));
		} else {
			JO_Session::set('msg_error', $this->translate('You need to create a collection to add the item to'));
			$this->redirect($request->getServer('HTTP_REFERER'));
		}
	}
	
    /* PAY PAYMENT */
	public function paymentAction() {
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
			JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
		}
		
		if($request->isPost() && $request->issetPost('item_id')) {
		
			$item = Model_Items::get($request->getPost('item_id'));
			
			if($request->getPost('licence') == 'personal') {
				$orderID = Model_Orders::add($item);
			} elseif($request->getPost('licence') == 'extended') {
				$orderID = Model_Orders::add($item, 'true');
			}
			
			JO_Session::set('order_id', $orderID);
			
			$this->view->crumbs = array(
				array(
					'name' => $this->translate('Home'),
					'href' => $request->getBaseUrl()
				),
				array(
					'name' => $this->translate('Profile'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName(JO_Session::get('username')))
				),
				array(
					'name' => $this->translate('Payment')
				)
			);
			
			$model_images = new Model_Images();
			$files = glob(dirname(__FILE__) . '/Payments/*.php');
			
			if($files) {
				$payments_data = $sort_order = $order_obj = array();
				
				foreach($files AS $row => $file) {
					if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
						$key = mb_strtolower($match[1], 'utf-8'); 
						if(JO_Registry::forceGet($key . '_status')) {
							JO_Loader::loadFile($file);
							
							$form = $this->view->callChildren('payments_'.$key.'/itemForm');
							if($form) {
								$sort_order[$row] = (int)JO_Registry::forceGet($key . '_sort_order'); 
								
								$logo = JO_Registry::forceGet($key . '_logo');
								if($logo){
									list($width) = getimagesize('uploads/' . $logo);
									if($width > 300) {
										$logo = $image_model->resizeWidth($logo, 300);
									} else {
										$logo = 'uploads/' . $logo;
									}
								} else {
									$logo = '';
								}
								
								$payments_data[$row] = array(
									'key' => $key,
									'edit' => $request->getModule() . '/payments_' . $key,
									'name' => $this->translate($match[1]),
									'sort' => (int)JO_Registry::forceGet($key . '_sort_order'),
									'logo' => ( JO_Registry::forceGet($key . '_logo') ? 'uploads/' . JO_Registry::forceGet($key . '_logo') : ''),
									'form' => $form
								);
							}
						}
					}
				}
				
				array_multisort($sort_order, SORT_ASC, $payments_data);
				
				$this->view->payments = $payments_data;
			
			}
		} else {
			$this->redirect($request->getServer('HTTP_REFERER'));
		}
		$this->view->usertotal = WM_Currency::format(JO_Session::get('total'));
		
		$this->view->children = array();
		$this->view->children['header_part'] = 'layout/header_part';
		$this->view->children['footer_part'] = 'layout/footer_part';
		
	}

    /* COMMENT REPORT */
	public function reportAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->getRequest('report')) {
    		$s = Model_Comments::report($request->getRequest('report'));
    		JO_Session::set('msg_success', $this->translate('Thank you for reporting the comment'));
    	}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
	}
		
	/* ADD COMMENT */
	public function add_commentAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->getRequest('add_comment')) {
			$item = Model_Items::get($request->getRequest('add_comment'));
			$user = Model_Users::getUser($item['user_id']);
			if($item) {
				$comment = trim($request->getPost('comment'));
				
				if(!empty($comment)) {
		    		$data = array(
		    			'owner_id' => (int)$item['user_id'],
		    			'item_id' => (int)$item['id'],
		    			'item_name' => $item['name'],
		    			'user_id' => JO_Session::get('user_id'),
		    			'comment' => $comment,
		    			'notify' => ($request->getPost('reply_notification') ? 'true' : 'false'),
		    			'reply_to' => 0,
		    		);
					
		    		$id = Model_Comments::add($data);
					
			    	if($id && JO_Session::get('user_id') != $item['user_id']) {
						$domain = $request->getDomain();
						$translate = JO_Translate::getInstance();
						
						$mail = new JO_Mail;
						if(JO_Registry::get('mail_smtp')) {
							$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
						}
						$mail->setFrom('no-reply@'.$domain);
						
		    			$href = '<a href="' . WM_Router::create($request->getBaseUrl() . '?controller=items&action=comments&item_id=' . $item['id'] .'&name='. WM_Router::clearName($item['name'])) .'">' . $item['name'] .' - '. $this->translate('Comments') . '</a>';
				
		    			$not_template = Model_Notification::getNotification('new_comment_item');
		    			
						if($not_template) {
							$title = $not_template['title'];
							$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
							$html = str_replace('{ITEMNAME}', $item['name'], $html);
							$html = str_replace('{URL}', $href, $html);
						} else {
							$title = "[".$domain."] " . $translate->translate('Have new reply to your comment');
							$html = nl2br($translate->translate('A reply is added to your comment').'
									
							 '. $href .'
							');
						}
						
						$mail->setSubject($title);
						
						$mail->setHTML($html);
						
						$mail->send(array($user['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));	
			    	}
					
		    		JO_Session::set('msg_success', $this->translate('The comment has been added successfully'));			    		
		    	} else {
		    		JO_Session::set('msg_error', $this->translate('Your comment is empty'));
		    	}
		    } else {
		    	JO_Session::set('msg_error', $this->translate('The item not found'));
		    }
	    } else {
	    	JO_Session::set('msg_error', $this->translate('The comment has not been added'));
	    }
		
		$this->redirect($request->getServer('HTTP_REFERER') . ($id ? '#c_'.$id : ''));
	}
		
	/* ADD REPLY */
	public function replyAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$link = $request->getServer('HTTP_REFERER');
		
		if($request->getRequest('c_id')) {
			$item = Model_Comments::get($request->getRequest('c_id'));
			if($item) {
				$comment = trim($request->getPost('comment_reply'));
				
				if($comment) {
		    		$data = array(
		    			'owner_id' => (int)$item['owner_id'],
		    			'item_id' => $item['item_id'],
		    			'item_name' => $item['item_name'],
		    			'user_id' => JO_Session::get('user_id'),
		    			'comment' => $comment,
		    			'notify' => 'false',
		    			'reply_to' => $item['id'],
		    		);
	
		    		$id = Model_Comments::add($data);
		    		JO_Session::set('msg_success', $this->translate('Your reply has been added successfully'));
				
					if($item['notify'] == 'true' && JO_Session::get('user_id') != $item['owner_id']) {
						$user = Model_Users::getUser($item['owner_id']);
						
						if($user && !empty($user['email'])) {
							if(JO_Session::get('user_id') != $item['user_id']) {
			    	    		$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
			    				$not_template = Model_Notification::getNotification('comment_reply_to');			
			    				$mail = new JO_Mail;
			    				if($is_mail_smtp) {
			    					$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
			    				}
			    				$domain = $request->getDomain();
			    				$mail->setFrom('no-reply@'. $domain);
			    				$mail->setReturnPath('no-reply@'. $domain);
			    				$mail->setSubject($this->translate('New comment on your item') .' - '. JO_Registry::forceGet('meta_title'));
								
								$lnk = '<a href="' . WM_Router::create($request->getBaseUrl() . '?controller=items&action=comments&item_id=' . $data['item_id']) .'">' . $data['name'] . '</a>';
								
								if($not_template) {
			            			$title = $not_template['title'];
			            			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
			            			$html = str_replace('{URL}', $lnk , $html);
			            			$html = str_replace('{ITEMNAME}',  $item['item_name'], $html);
			                    } else {
			        				$html = nl2br('Hello,
			    
			    					There is a new comment on your item '.$item['name'].'. You can see it on '. WM_Router::create($lnk .'&filter='. $id) .'
			        				');
			                    }
			                       
			    		        $mail->setHTML($html);
			    		        $result = (int)$mail->send(array($user['email']), ($is_mail_smtp ? 'smtp' : 'mail'));
		    		      
			    			}
						}
					}
				} else {
		    		JO_Session::set('msg_error', $this->translate('Your reply is empty'));
		    	}
			} else {
		    	JO_Session::set('msg_error', $this->translate('The item not found'));
		    }
		} else {
	    	JO_Session::set('msg_error', $this->translate('The comment has not been added'));
	    }
		
		$this->redirect($link . ($item['id'] ? '#c_'. $item['id'] : ''));
	}
		
    /* COMMENTS */
    public function commentsAction() {
    	$request = $this->getRequest();
    	$this->view->currency = WM_Currency::getCurrency();
    	
	    $itemID = $request->getRequest('comments');
    	$item = Model_Items::get($itemID);
		
        if(!is_array($item) || $item['status'] == 'deleted' || (JO_Session::get('username') && $item['status'] == 'unapproved' && $item['user_id'] != $_SESSION['user']['user_id']) || $item['status'] == 'queue' || $item['status'] == 'extended_buy') {
    	    return $this->forward('error', 'error404');
    	}
		
		$this->view->item_link = WM_Router::create($request->getBaseUrl() .'?controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$faqs_data = Model_Faq::getAll($itemID);
		if(JO_Session::get('user_id') == $item['user_id'] || $faqs_data) {
			$this->view->faq_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=faq&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		if(JO_Session::get('user_id') == $item['user_id']) {
			$this->view->edit_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=edit&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
			$this->view->delete_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=delete&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		$this->view->comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		JO_Session::set('redirect', $this->view->comment_link);
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$user = $item['user'] = Model_Users::getUser($item['user_id']);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Portfolio'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($user['username']))
			),
			array(
				'name' => $item['name'],
				'href' => $this->view->item_link
			),
			array(
				'name' => $this->translate('Comments')
			)
		);
    	
    	$model_images = new Model_Images();
		
		$filter = '';
		if($request->getRequest('filter')) {
			$filter = ' AND items_comments.id = ' . (int)$request->getRequest('filter');
		}
		
		$bayers = Model_Orders::getBayers($itemID);
    	$comments = Model_Comments::getAll(0, 0, "item_id = '" . (int)$itemID . "' AND reply_to = 0" . $filter, true);
    	
		$this->view->smiles = Model_Smiles::getSmilesImages();
		
		$this->view->comments = array();
		if($comments) {
			$bbcode_parser = new WM_BBCode_Parser();
			$bbcode_parser->loadDefaultCodes();
			
	    	foreach($comments AS $comment) {
	    		if($comment['avatar']) {
					$comment['avatar'] = $model_images->resize($comment['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else 
					$comment['avatar'] = 'data/themes/images/noavatar.png';
	    		
				$bbcode_parser->parse($comment['comment']);
				$comment['comment'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
				$comment['badges'] = Helper_Author::userBadges( Model_Users::getUser($comment['owner_id']) );
	    		$comment['datetime'] = WM_Date::format($comment['datetime'], 'dd M yy', true);
	    		$comment['is_buy'] = ($bayers && in_array($comment['user_id'], $bayers));
	    		$comment['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($comment['username']));
	    		$comment['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=report/'.$comment['id']);
	    		$comment['replyhref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=reply&c_id='.$comment['id']);
	    		
	    		foreach($comment['reply'] AS $key => $reply) {
	    			if($comment['reply'][$key]['avatar']) {
						$comment['reply'][$key]['avatar'] = $model_images->resize($comment['reply'][$key]['avatar'], 50, 50, true);
					} else 
						$comment['reply'][$key]['avatar'] = 'data/themes/images/small_noavatar.png';
	    			$bbcode_parser->parse($comment['reply'][$key]['comment']);
	    			$comment['reply'][$key]['comment'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
		    		$comment['reply'][$key]['badges'] = Helper_Author::userBadges( Model_Users::getUser($comment['reply'][$key]['owner_id']) );
		    		$comment['reply'][$key]['datetime'] = WM_Date::format($reply['datetime'], 'dd M yy', true);
		    		$comment['reply'][$key]['is_buy'] = ($bayers && in_array($reply['user_id'], $bayers));
		    		$comment['reply'][$key]['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($comment['reply'][$key]['username']));
		    		$comment['reply'][$key]['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=report/'.$comment['reply'][$key]['id']);
	    		}
	    		
	    		$this->view->comments[] = $comment;
	    	}
	    }
		
		$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=add_comment&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$this->getLayout()->meta_title = $item['name'];
    	$this->getLayout()->meta_description = substr(strip_tags(html_entity_decode($item['description'], ENT_QUOTES, 'utf-8')), 0, 255);
		
    	$this->view->item = $item;
		$this->view->user = $user;
		
    	$this->view->children = array();
		$this->view->children['rightside'] = 'items/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
	/* ADD FAQ */
	public function add_faqAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$item = Model_Items::get($request->getRequest('add_faq'));
		
		if($item && JO_Session::get('user_id') == $item['user_id']) {
    	    if($request->isPost()) {
    	    	$question = trim($request->getPost('question'));
				$answer = $request->getPost('answer');
				if(!empty($question) && !empty($answer)) {
	    	        Model_Faq::add(array(
	    	            'item_id'	=>    $item['id'],
	    	            'user_id'	=>    JO_Session::get('user_id'),
	    	            'question'	=>    $question,
	    	        	'answer'	=>    $answer
	    	        ));
	    	        JO_Session::set('msg_success', $this->translate('Your question has been added!'));
				} else {
					JO_Session::set('msg_error', $this->translate('Your question or answer is empty!'));
				}	
    	    } else {
    	    	JO_Session::set('msg_error', $this->translate('Your question has not been added!'));
    	    }
    	}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
	}

	/* DELETE FAQ */
	public function delete_faqAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$item = Model_Items::get($request->getRequest('delete_faq'));
		
		if($item && JO_Session::get('user_id') == $item['user_id']) {
			$del_id = $request->getRequest('del');
    	    if(is_numeric($del_id) && Model_Faq::isOwner(JO_Session::get('user_id'), $del_id)) {
    	        Model_Faq::delete($del_id);
    	        JO_Session::set('msg_success', $this->translate('Your question has been deleted!'));
    	    }
    	} else {
    		JO_Session::set('msg_error', $this->translate('Your question has not been deleted!'));
    	}

		$this->redirect($request->getServer('HTTP_REFERER'));
	}
	
	public function update_faqAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$item = Model_Items::get($request->getRequest('update_faq'));
		
		if($item && JO_Session::get('user_id') == $item['user_id']) {
			$update_id = $request->getRequest('faq');
			$question = trim($request->getPost('question'));
			$answer = trim($request->getPost('answer'));
			if(!empty($question) && !empty($answer)) {
	    	    if(is_numeric($update_id) && Model_Faq::isOwner(JO_Session::get('user_id'), $update_id)) {
	    	        Model_Faq::update($update_id, array(
		    	            'item_id'	=>    $item['id'],
		    	            'user_id'	=>    JO_Session::get('user_id'),
		    	            'question'	=>    $question,
		    	        	'answer'	=>    $answer
		    	        ));
	    	        JO_Session::set('msg_success', $this->translate('Your question has been updated!'));
	    	    } else {
	    	    	JO_Session::set('msg_error', $this->translate('You are not the owner!'));
	    	    }
			} else {
				JO_Session::set('msg_error', $this->translate('Your question or answer is empty!'));
			}
    	} else {
    		JO_Session::set('msg_error', $this->translate('Your question has not been updated!'));
    	}

		$this->redirect($request->getServer('HTTP_REFERER'));
	}
	
	/* FAQ */
    public function faqAction() {
    	$request = $this->getRequest();
    	$this->view->currency = WM_Currency::getCurrency();
    	
	    $itemID = $request->getRequest('faq');
    	$item = Model_Items::get($itemID);
		
        if(!is_array($item) || $item['status'] == 'deleted' || (JO_Session::get('username') && $item['status'] == 'unapproved' && $item['user_id'] != $_SESSION['user']['user_id']) || $item['status'] == 'queue' || $item['status'] == 'extended_buy') {
    	    return $this->forward('error', 'error404');
    	}
		
		$this->view->item_link = WM_Router::create($request->getBaseUrl() .'?controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$faqs_data = Model_Faq::getAll($itemID);
		if(JO_Session::get('user_id') == $item['user_id'] || $faqs_data) {
			$this->view->faq_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=faq&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		if(JO_Session::get('user_id') == $item['user_id']) {
			$this->view->edit_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=edit&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
			$this->view->delete_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=delete&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		$this->view->comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		JO_Session::set('redirect', $this->view->comment_link);
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}

		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$user = $item['user'] = Model_Users::getUser($item['user_id']);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Portfolio'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($user['username']))
			),
			array(
				'name' => $item['name'],
				'href' => $this->view->item_link
			),
			array(
				'name' => $this->translate('FAQ')
			)
		);
		
		$filter = '';
		if((int) $request->getRequest('filter')) {
			$filter = ' AND items_comments.id = ' . (int)$request->getRequest('filter');
		}
   		
		$link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$this->view->faq = array();
    	
    	if($faqs_data) {
    		foreach($faqs_data AS $f) {
    			$f['question'] = nl2br($f['question']);
    			$f['answer'] = html_entity_decode($f['answer']);
    			$f['delete'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=delete_faq&item_id='.$item['id'].'&del='.$f['id']);
    			$f['update'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=update_faq&item_id='.$item['id'].'&faq='.$f['id']);
    			$this->view->faq[] = $f;
    		}
    	}
		
		$this->view->add_faq_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=add_faq&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$this->getLayout()->meta_title = $item['name'];
    	$this->getLayout()->meta_description = substr(strip_tags(html_entity_decode($item['description'], ENT_QUOTES, 'utf-8')), 0, 255);
		
    	$this->view->item = $item;
		$this->view->user = $user;
		
    	$this->view->children = array();
		$this->view->children['rightside'] = 'items/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
    public function featureAction() {
    	$this->getLayout()->placeholder('checkItemsType',true);
      $this->getLayout()->meta_title = $this->translate('Featured Files');
	    $this->getLayout()->meta_description = $this->translate('Featured Files');	   
        	$sixMonthsAgo = date('Y-m-d', mktime(0, 0, 0, (date('m')-6), date('d'), date('Y')));

	$items = Model_Items::getAll(false, 0, 0, 'datetime DESC', " `items`.`status` = 'active' AND `items`.`weekly_to` >= '".date('Y-m-d')."' AND `items`.`weekly_to` >= '".$sixMonthsAgo."' ");
    $model_images = new Model_Images;
 
	$this->view->items = array();
        if($items) {
	        foreach($items as $n => $item) {
	        	$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }
    	if(is_array($this->view->items)) {
		    $this->view->topItem = array_shift($this->view->items);
		    
    	} 
    	
    	
    $authors = Model_Users::getFeatAuthors();
    if($authors) {
        foreach($authors as $v => $aut) {
            $authors[$v]['avatar'] = $model_images->resize($aut['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
            $authors[$v]['userhref'] = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&username='.WM_Router::clearName($aut['username']));
        }
    }    
    
    $this->view->featuredAuthors = $authors;
        $this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
    public function top_sellersAction() {
          $this->getLayout()->meta_title = $this->translate('Top sellers');
	    $this->getLayout()->meta_description = $this->translate('Top sellers');	
    $this->getLayout()->placeholder('checkItemsType',true);
          $model_images = new Model_Images;
        $year = $this->getRequest()->getRequest('top_sellers');
    	$month = $this->getRequest()->getRequest($year);
    	$day = $this->getRequest()->getRequest($month);
    	
    	
    	if(!checkdate(intval($month), intval($day), intval($year))) {
    		$year = date('Y');
    		$month = date('m');
    		$day = date('d');
    	}
    	

    	
    	$dayOfWeek = date('N', mktime(0, 0, 0, $month, $day, $year));
    	$dayOfWeek = 7-$dayOfWeek;
    	if($dayOfWeek > 0) {
    		$endDate = date('Y-m-d', mktime(0, 0, 0, $month, ($day + $dayOfWeek), $year));
    	}
    	else {
    		if(strlen($month) == 1) {
    			$month = '0'.$month;
    		}
    		if(strlen($day) == 1) {
    			$day = '0'.$day;
    		}
    		$endDate = $year.'-'.$month.'-'.$day;
    	}
    	
    	$startDate = date('Y-m-d', (strtotime($endDate) - 604800));
    	$this->view->endDate = $endDate;
    	
    #GENERATE PREV AND NEXT
    	if(strtotime($endDate) < strtotime(date('Y-m-d'))) {
    		$this->view->nextDate = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&action=top_sellers/'.date('Y/m/d', (strtotime($endDate) + 604800)));
    	}	
    	$this->view->prevDate = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&action=top_sellers/'.date('Y/m/d', strtotime($startDate)));
        
    	
    	$month = date('m', strtotime($endDate)) - 1;
    	
    	$endMonthlyDate = date('Y-m-d', mktime(0, 0, 0, $month, date('t', mktime(0, 0, 0, $month, 1, date('Y'))), date('Y')));
    	$startMonthlyDate = date('Y-m-d', mktime(0, 0, 0, ($month-3), 1, date('Y')));
    	$this->view->endMonthlyDate = $endMonthlyDate;
    	$endMonthlyDate2 = date('Y-m-d', mktime(0, 0, 0, $month, date('t', mktime(0, 0, 0, $month, 1, date('Y'))), date('Y')));
    	$startMonthlyDate2 = date('Y-m-d', mktime(0, 0, 0, $month, 1, date('Y')));
    	
    	$this->view->month = $month;
        
    	
    	
    	#GET ITEMS	

    	$topSellItems = Model_Items::getTopSellers(0, 50, "`paid_datetime` > '$startDate 23:59:59' AND `paid_datetime` < '$endDate 23:59:59' ");
    	$this->view->topSellItems = array();
    	if($topSellItems) {
     		$position = 1;
	        foreach($topSellItems as $n => $item) {
	            $item['position'] = $position++;
	        	$this->view->topSellItems[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }
	    
    	
    	$topMonthlyItems = Model_Items::getTopSellers(0, 50, "`paid_datetime` > '$startMonthlyDate 00:00:00' AND `paid_datetime` < '$endMonthlyDate 23:59:59' ");
    	$this->view->topMonthlyItems = array();
    	if($topMonthlyItems) {
        	$position = 1;
	        foreach($topMonthlyItems as $n => $item) {
	        	$item['position'] = $position++;
	        	$this->view->topMonthlyItems[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }
    	
        $authors = Model_Users::getTopAuthors("AND `paid_datetime` > '$startMonthlyDate2 00:00:00' AND `paid_datetime` < '$endMonthlyDate2 23:59:59'");
        if($authors) {
            foreach($authors as $v => $aut) {
                $authors[$v]['avatar'] = $model_images->resize($aut['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
                $authors[$v]['href'] = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&username='.WM_Router::clearName($aut['username']));
            }
        }    
        
        
        $this->view->monthList = array();
        for($i=1; $i<=12; $i++) {
        	$this->view->monthList[] = JO_Date::getInstance(date('Y').'-'.sprintf('%02d',$i).'-01','MM',true)->toString();
        }
        

        $this->view->featuredAuthors = $authors;
        $this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
	/* PREVIEW */
    public function previewAction() {
		$request = $this->getRequest();
        
        $itemID = $request->getRequest('preview');
    	$item = Model_Items::get($itemID);
    	$this->view->item = $item;
		
		$this->view->itemhref = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$this->getLayout()->placeholder('title', $item['name'] . ' - ' . JO_Registry::get('meta_title'));
		$meta_description = substr(strip_tags(html_entity_decode($item['description'], ENT_QUOTES, 'utf-8')), 0, 255);
    	$this->getLayout()->placeholder('description', $meta_description);
		
    	if(JO_Registry::get('site_logo') && file_exists(BASE_PATH .'/uploads/'.JO_Registry::get('site_logo'))) {
		    $this->view->site_logo = JO_Registry::get('site_logo'); 
		}
    	
    }
    
	/* GALLERY*/
    public function screenshotsAction() {
    	$request = $this->getRequest();
		$model_images = new Model_Images;
		
        $itemID = $request->getRequest('screenshots');
        $item = Model_Items::get($itemID);
    	$this->view->item = $item;
    	
		$fileTypes_allow = JO_Registry::get('upload_theme');
        $this->view->itemhref = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
				
		$allow_images = array();
		if(isset($fileTypes_allow['images'])) {
			$ew = explode(',', $fileTypes_allow['images']);
			foreach($ew AS $ar) {
				$allow_images[] = '.'. strtolower($ar);
			}
		}
		
		$items_path = '/items/'. JO_Date::getInstance($item['datetime'], 'yy/mm', true) .'/'. $itemID .'/preview/';
		$folder = realpath(BASE_PATH . '/uploads') . $items_path;
		
		$files = scandir($folder);
		
		$this->view->screenshots_thumbs = array();
		$this->view->screenshots_file = '';
		
		if(!empty($files)) {
			foreach($files as $file) {
				if(in_array($file, array('.', '..'))) continue;
				
				$ext = strtolower(strrchr($file, '.'));
				if(in_array($ext, $allow_images)) {
					$this->view->screenshots_thumbs[] = array(
						'thumb' => $model_images->resizeWidth($items_path . $file, JO_Registry::forceGet($item['module'].'_items_thumb_width')),
						'file' => $file
					);
				}
			}
			
			if(is_array($this->view->screenshots_thumbs[0])) {
				$this->view->screenshots_file = $model_images->resizeWidth($items_path . $this->view->screenshots_thumbs[0]['file'], (int)JO_Registry::forceGet($item['module'].'_items_screenshots_width') * 1.5);
			}
		}
		
		$this->view->ajax_link = WM_Router::create($request->getBaseUrl() .'?module='. $item['module'] .'&controller=items&action=gallery_image');
    }
    
    public function gallery_imageAction() {
        $model_images = new Model_Images;
        $this->noLayout(true);
        $request = $this->getRequest();
		
        $item_id = $request->getPost('item_id');
		$file_name = $request->getPost('file');
        $item = Model_Items::get($item_id);
		$items_path = '/items/' . JO_Date::getInstance($item['datetime'], 'yy/mm', true) . '/' . $item_id.'/preview/';
		
        $fileTypes_allow = JO_Registry::get('upload_theme');
        $ext = strtolower(strrchr($file_name, '.'));		
		
		$allow_images = array();
		if(isset($fileTypes_allow['images'])) {
			$ew = explode(',', $fileTypes_allow['images']);
			foreach($ew AS $ar) {
				$allow_images[] = '.'. strtolower($ar);
			}
		}
        
		$folder  = realpath(BASE_PATH . '/uploads') . $items_path;
		
		if(file_exists($folder . $param[1]) && in_array($ext, $allow_images)) {
			$screenshots_file = $model_images->resizeWidth($items_path . $file_name, (int)JO_Registry::forceGet($item['module'].'_items_screenshots_width') * 1.5);
		}
		
		die(json_encode(array('src' => $screenshots_file)));
    }
    
	/* AUTO */
    public function autoAction() {
		
		$request = $this->getRequest();
		$tags = $request->getParam('q');
		
		$temp_tags = array(); 
		$id = Model_Tags::getTagsByTitle($tags);
			
		if($id) {
			foreach($id as $t) {
			    echo $t['name']."\n";
			}
		}
		exit;
	}
	
	/* DELETE */
	public function deleteAction()
	{
		$request = $this->getRequest();
    	if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
	    $itemID = $request->getRequest('delete');
		if(!$itemID || !is_numeric($itemID)) {
			return $this->forward('error', 'error404');
		}
		
		$item = Model_Items::get($itemID);
		
		$referer = $request->getServer('HTTP_REFERER');	
		if($item && $item['user_id'] == JO_Session::get('user_id')) {
			Model_Items::delete($itemID);
			JO_Session::set('msg_success', 'You have successfully delete this item!');
			
			if(strpos($referer, '/items/') !== false) {
				$referer = WM_Router::create($request->getBaseUrl() .'?controller=users&action=dashboard');
			}
		}
		
		$this->redirect($referer);
		
	}
	
	/* EDIT */
	public function editAction() {
		
	    $request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
	    $itemID = $request->getRequest('edit');
	    $item = Model_Items::get($itemID);
		
	    if(JO_Session::get('user_id') != $item['user_id']) {
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?module='.$item['module'].'&controller=items&item_id='. $item['id'] .'&name='.WM_Router::clearName($item['name'])));
	    }
		
		$this->getLayout()->meta_title = $item['name'];
	    $this->getLayout()->meta_description = substr(strip_tags(html_entity_decode($item['description'], ENT_QUOTES, 'utf-8')), 0, 255);	
	    
		$this->view->item_link = WM_Router::create($request->getBaseUrl() .'?controller=items&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$faqs_data = Model_Faq::getAll($itemID);
		if(JO_Session::get('user_id') == $item['user_id'] || $faqs_data) {
			$this->view->faq_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=faq&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		if(JO_Session::get('user_id') == $item['user_id']) {
			$this->view->edit_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=edit&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
			$this->view->delete_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=delete&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		}
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Portfolio'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName(JO_Session::get('username')))
			),
			array(
				'name' => $item['name'],
				'href' => $this->view->item_link
			),
			array(
				'name' => $this->translate('Edit')
			)
		);
		
		$this->view->comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=comments&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
		$categories = Model_Categories::getMain();		
		if($categories) {
			foreach($categories as $category) {
				$this->view->categories[] = array(
					'id' => $category['id'],
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=upload&action=get_categories&category_id='. $category['id']),
					'name' => $category['name']
				);
			} 
		}
		$this->view->mainCategories = $categories;
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			$data = JO_Session::get('data');
			
			JO_Session::clear('msg_error');
			JO_Session::clear('data');
		}
		
		$this->view->sel_category = isset($data) ? $data['category_id'] : reset($item['categories'][0]);
		
		$allCategories = Model_Categories::getWithChilds();
		$this->view->categoriesSelect = array();
		$categoriesSelect = Model_Categories::generateSelect($allCategories, $this->view->sel_category, $this->view->sel_category);
		
		if($categoriesSelect) {
	    	$categories = explode('|', $categoriesSelect);
			foreach($categories as $category) {
				if(!empty($category)) {
					$c = explode('>', $category);
					$this->view->categoriesSelect[] = array(
						'id' => $c[0],
						'name' => $c[1]
					);
				}
			}
		}
		
		$this->view->attributes = Model_Attributes::getAllWithCategories("attributes_categories.categories LIKE '%,".(int) $this->view->sel_category .",%'");
		
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->total_balance = WM_Currency::format(JO_Session::get('total'));
		$user = array(
			'user_id' => JO_Session::get('user_id'),
			'sold' => JO_Session::get('sold')
		);
		$this->view->percent = Model_Percentes::getPercentRow($user);
		
		if(isset($data)) {
			$item['name'] = $data['name'];
			$item['description'] = $data['description'];
			$item['theme_preview'] = $data['theme_preview'];
			$item['theme_preview_zip'] = $data['theme_preview_zip'];
			$item['main_file'] = $data['main_file'];
			$item['reviewer_comment'] = $data['reviewer_comment'];
			$item['attributes'] = $data['attributes'];
			
			isset($data['source_license']) && $item['source_license'] = $data['source_license'];
			isset($data['free_request']) && $item['free_file'] = true;
		}
			
		$item['suggested_price'] = isset($data['suggested_price']) ? $data['suggested_price'] : $item['price'];
		$item['selected_categories'] = isset($data['category']) ? $data['category'] : JO_Array::multi_array_to_single_uniq($item['categories']);
		$item['selected_attributes'] = isset($data['attributes']) ? JO_Array::multi_array_to_single_uniq($data['attributes']) : JO_Array::multi_array_to_single_uniq($item['attributes']);
		$item['selected_tags'] = isset($data['tags']) ? $data['tags'] : implode(', ', JO_Array::multi_array_to_single_uniq($item['tags']));
		
		$help = Model_Pages::get(JO_Registry::forceGet('page_upload_item'));
        if($help) {
        	$this->view->page_upload_item = array(
        		'name' => $help['name'],
        		'href' => WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='. $help['id'])
        	);
        }
		
		$this->view->uploaded_files = JO_Session::get('uploaded_files');
		$this->view->uploaded_arhives = JO_Session::get('uploaded_arhives');
		
		$this->view->file_upload = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=upload&action=doupload');
		$this->view->action_upload = WM_Router::create($request->getBaseUrl() . '?module='. $item['module'] .'&controller=items&action=doedit');
		$this->view->d = $item;
		
		$this->view->autocomplete = WM_Router::create($request->getBaseUrl() . '?controller=items&action=auto');
		
        $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
		
	}
	
	public function doeditAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		if($request->issetPost('item_id')) {
			$itemID = (int)$request->getPost('item_id');
			$item = Model_Items::get($itemID);
			$error = array(); 
            
			if(!$item) {
				$error['msg_error'] = $this->translate('Item not found');
			}
			
            if(trim($request->getPost('name')) == '') {
    			$error['ename'] = $this->translate('You have to input a name');
    		}
        
    		if(trim($request->getPost('description')) == '') {
    			$error['edescription'] = $this->translate('You have to input a description');
    		}
        	
			$base_upload_folder  = realpath(BASE_PATH . '/uploads');
			$temp_upload_folder = $base_upload_folder .'/temporary/'. JO_Date::getInstance(JO_Session::get('register_datetime'), 'yy/mm', true) . '/';
			  
			$fileTypes = JO_Registry::get('upload_theme');
			
			if(isset($fileTypes['archives'])) {
    			$ew = explode(',', $fileTypes['archives']);
    			foreach($ew AS $ar) {
    				$allow_archives[] = '.'.strtolower($ar);
    			}
    		}
			
			$allow_images = array();
			if(isset($fileTypes['images'])) {
				$ew = explode(',', $fileTypes['images']);
				foreach($ew AS $ar) {
					$allow_images[] = '.'.strtolower($ar);
				}
			}
			
    		if(trim($request->getPost('theme_preview')) != '') {
        		if(!in_array(strtolower(strrchr($request->getPost('theme_preview'), '.')), $allow_images)) {
        			$error['etheme_preview'] = $this->translate('Theme preview should be '.implode(', ',$allow_images).' file');
        		}
        	}
        	
			if(trim($request->getPost('theme_preview_zip')) == '') {
        		$error['etheme_preview_zip'] = $this->translate('You have to choose a file');
        	} else {
    			if(!in_array(strtolower(strrchr($request->getPost('theme_preview_zip'), '.')), $allow_archives)) {
    				$error['etheme_preview_zip'] = $this->translate('Preview archive file should be '.implode(', ',$allow_archives).' file');
    			} elseif(!file_exists($temp_upload_folder . $request->getPost('theme_preview_zip'))) {
    				$error['etheme_preview_zip'] = $this->translate('Preview archive file should be '.implode(', ',$allow_archives).' file');
    			}
    		}
				
        	if(trim($request->getPost('main_file')) != '') {
    			if(!in_array(strtolower(strrchr($request->getPost('main_file'), '.')), $allow_archives)) {
    				$error['emain_file'] = $this->translate('Main file should be '.implode(', ',$allow_archives).' file');
    			} elseif(!file_exists($temp_upload_folder . $request->getPost('main_file'))) {
    				$error['emain_file'] = $this->translate('Main file should be '.implode(', ',$allow_archives).' file');
    			}
    		}
        
    		if(!$request->getPost('category')) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		} elseif(!is_array($request->getPost('category'))) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		} elseif(!count($request->getPost('category'))) {
    			$error['ecategory'] = $this->translate('You have to choose a category');
    		}
        	
			$attributes = Model_Attributes::getAllWithCategories("attributes_categories.categories LIKE '%,".(int) $request->getPost('category_id') .",%'");	
    		
    		if(is_array($attributes)) {
    			$attributesError = false;
				$cnt = count($attributes);
    			for($i = 0; $i < $cnt; $i++) {			

    				if(!$request->getPost('attributes['.$attributes[$i]['head_id'].']') && $attributes[$i]['required']) {
    					$attributesError = true;
    					break;
    				}				
    			}
    			
    			if($attributesError) {
    				$error['eattributes'] = $this->translate('You have to mark all the attributes');
    			}
    		}
        		
    		if(trim($request->getPost('tags')) == '') {
    			$error['etags'] = $this->translate('You have to fill the field with tags');
    		}
        		
    		if(!$request->getPost('source_license')) {
    			$error['esource_license'] = $this->translate('You have to confirm that you have rights to use all the materials in your template');
    		}
        		
    		if($request->getPost('demo_url') && filter_var($request->getPost('demo_url'), FILTER_VALIDATE_URL) === false) {
    			$error['edemo_url'] = $this->translate('Please enter valid url for demo preview');
    		}
        	
    		if(!$request->getPost('suggested_price') || !preg_match('#^\d+(?:\.\d{1,})?$#', $request->getPost('suggested_price'))) {
    			$error['esuggested_price'] = $this->translate('Suggested price should be in the format: number(.number)');
    		}       		
			
    		if(count($error) > 0) {
				$error['msg_error'] = $this->translate('Upload error');
    		    JO_Session::set('msg_error', $error);
				JO_Session::set('data', $request->getParams());
				$this->redirect($request->getServer('HTTP_REFERER'));
    		} else {
	
        	    $free_request = $request->getPost('free_request') ? 'true' : 'false';
       
	   			if(!$request->getPost('free_request')) {
        			$free_request = 'false';
        		} else {
        			$free_request = 'true';
				}
				
        		Model_Items::updateItem(array(
        		    'id'		=>        $itemID,
        		    'name'		=>		  $request->getPost('name'),
        		    'description'	=>        $request->getPost('description'),
        		    'demo_url'		=>        $request->getPost('demo_url'),
        		    'free_request'	=>        $free_request,
        		    'reviewer_comment'	=>    $request->getPost('reviewer_comment'),
        		    'suggested_price'	=>	  $request->getPost('suggested_price'),
    		    	'default_module'	=> 	  $item['module']
        		));
				
				Model_Attributes::deleteItem($itemID);
				if($request->getPost('attributes')) {
        			Model_Attributes::addToItem($itemID, $request->getPost('attributes'));
				}
      			
				if($request->getPost('theme_preview') != '' || $request->getPost('theme_preview_zip') != '' || $request->getPost('main_file') != '') {
	        		$upload_folder = $base_upload_folder. '/items/'. JO_Date::getInstance($item['datetime'], 'yy/mm/',true)->toString() . $item['id'].'/';
					
					if(!file_exists($upload_folder .'temp/') || !is_dir($upload_folder .'temp/')) {
						mkdir($upload_folder .'temp/', 0777, true);
					}
					
					if(trim($request->getPost('theme_preview')) != '') {
						$theme_preview = $request->getPost('theme_preview');
						copy($temp_upload_folder .$theme_preview, $upload_folder .'temp/'. $theme_preview);
					}
					
					if(trim($request->getPost('theme_preview_zip')) != '') {
						$zip_file = $request->getPost('theme_preview_zip');
						copy($temp_upload_folder .$zip_file, $upload_folder .'temp/'. $zip_file);
					}
					
					if(trim($request->getPost('main_file')) != '') {
						$main_file = $request->getPost('main_file');
						copy($temp_upload_folder .$main_file, $upload_folder .'temp/'. $main_file);
					}
					
					$uploaded_files = JO_Session::get('uploaded_files');
					$upload_file = array();
					
					if(isset($theme_preview)) {
						$found = false;
						
						foreach($uploaded_files AS $k => $uf) {
							foreach($uf as $f) {
								if($f['filename'] == $theme_preview) {
									$upload_file = $f;
									break;
								}
							}
						}
					
						if($upload_file && file_exists($temp_upload_folder . $upload_file['filename'])) {
							$preview = $upload_folder .'temp/'. $upload_file['filename'];
							copy($temp_upload_folder . $upload_file['filename'], $preview);
							$found = true;
						} 
						
					} else {
						$found = true;
					}
					
					$zip = new ZipArchive;
					
					if(isset($zip_file)) {
		        		$res = $zip->open($upload_folder .'temp/'. $zip_file);
		        		
		        		if($res == true) {
							if(is_dir($upload_folder .'temp/preview/')) {
								Model_Items::unlink($upload_folder .'temp/preview/', false);
							} else {
								mkdir($upload_folder .'temp/preview/', 0777, true);
							}
						
							for($i = 0; $i < $zip->numFiles; $i++) {
								$file = $zip->getNameIndex($i);
								if( stripos($file, '_MACOSX') !== false ) { continue; }
								if(in_array(strtolower(strrchr($file, '.')), $allow_images)) {
									$fileinfo = pathinfo($file);
									
									$prw_filename = $this->rename_if_exists($upload_folder .'temp/preview/', $fileinfo['basename']);
									copy("zip://" . $upload_folder .'temp/'. $zip_file ."#". $file, $upload_folder .'temp/preview/'. $prw_filename);
									
									if(!$found && isset($theme_preview) && !empty($fileinfo['basename']) && $fileinfo['basename'] == $upload_file['name']) {
										$found = true;
										$filename = $this->rename_if_exists($upload_folder .'temp/', $fileinfo['basename']);
										
										if(copy("zip://" . $upload_folder .'temp/'. $zip_file ."#". $file, $upload_folder . 'temp/' . $filename)) {
											$preview = $filename;
										}
									}
								}
							}
						
							$zip->close();
						}
					}
					
					if(isset($main_file)) {
						$res = $zip->open($upload_folder .'temp/'. $main_file);
					
						for($i = 0; $i < $zip->numFiles; $i++) {
							$file = $zip->getNameIndex($i);
							if( stripos($file, '_MACOSX') !== false ) { continue; }
							if(in_array(strtolower(strrchr($file, '.')), $allow_images)) {
								$fileinfo = pathinfo($file);
								
								if(!$found && !empty($fileinfo['basename']) && $fileinfo['basename'] == $upload_file['name']) {
									$filename = $this->rename_if_exists($upload_folder, $fileinfo['basename']);
									
									if(copy("zip://" . $upload_folder .'temp/'. $main_file ."#". $file, $upload_folder .'temp/'. $filename)) {
										$preview = $filename;
									}
								}
							}
						}
					
						$zip->close();
					}
					
	            	$item_folder = 	str_replace( $base_upload_folder, '', $upload_folder);
					$uploaded_arhives = JO_Session::get('uploaded_arhives');
					
					$upload_zip = array();
					foreach($uploaded_arhives[0] as $f) {
						
						if($f['filename'] == $request->getPost('main_file')) {
							$upload_zip = $f;
							break;
						}
					}
					
					$preview = isset($preview) ? str_replace( $base_upload_folder, '', $preview) : '';
					if($preview && strpos($preview,'temp/') === false) {
						$preview = $item_folder .'temp/'. $preview;
					}
					
	        		Model_Items::updateTempPics(array(
	        		    'id'	=>    $itemID,
	        		    'thumbnail'	=>  $preview /*? $item_folder .'temp/'. $preview : ''*/,
	        		    'theme_preview_thumbnail'	=>  $preview /*isset($preview) ? $item_folder .'temp/'. $preview : ''*/,
	        		    'theme_preview'	=>    isset($zip_file) ? $item_folder .'temp/'. $zip_file : '',
	        		    'main_file'		=>    isset($main_file) ? $item_folder .'temp/'. $main_file : '',
	        		    'main_file_name'	=>   isset($main_file) ? $item_folder .'temp/'. $upload_zip['name'] : ''
	        		));
        		}
				
				Model_Categories::deleteTempToItem($itemID);
				Model_Categories::updateToItem($itemID, $request->getPost('category'), $request->getPost('category_id'));
	   
	   			Model_Attributes::deleteTempToItem($itemID);
				if($request->getPost('attributes')) {
	   				Model_Attributes::updateToItem($itemID, $request->getPost('attributes'));
				}
				
				Model_Tags::deleteTempToItem($itemID);
				$arr = explode(',', $request->getPost('tags'));
				Model_Tags::updateToItem($itemID, $arr);
        		
				if($uploaded_files) {
        			foreach($uploaded_files[0] as $f) {
        				if(file_exists($temp_upload_folder . $f['filename']))
        					unlink($temp_upload_folder . $f['filename']);
        			}
        		}	
        		JO_Session::clear('uploaded_files');	
	           	
				if($uploaded_arhives) {
        			foreach($uploaded_arhives[0] as $f) {
        				
        				if(file_exists($temp_upload_folder . $f['filename']))
        					unlink($temp_upload_folder . $f['filename']);
        			}
        		}	
        		JO_Session::clear('uploaded_arhives');
				
        		$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
				$not_template = Model_Notification::getNotification('item_added');			
				$mail = new JO_Mail;
				if($is_mail_smtp) {
					$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
				}
				$domain = $request->getDomain();
				$mail->setFrom('no-reply@'.$domain);
				$mail->setReturnPath('no-reply@'.$domain);
				$mail->setSubject($this->translate('Updated item for approval') . ' ' . JO_Registry::get('store_meta_title'));
                if($not_template) {
        			$title = $not_template['title'];
        			$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
        			$html = str_replace('{URL}', $request->getBaseUrl().'/admin/queueupdateditems/edit/?m='.$item['module'].'&id='. $itemID , $html);
                } else {
    				$html = nl2br('Hello,

					There is a updated item waiting for approval. You can see it on '. $request->getBaseUrl().'/admin/queueupdateditems/edit/?m='. $item['module'] .'&id='. $itemID .'');
                }
		        $mail->setHTML($html);
				
				$result = (int)$mail->send(array(JO_Registry::get('report_mail')), ($is_mail_smtp ? 'smtp' : 'mail'));
        		
        		JO_Session::set('msg_success', $this->translate('Your item has been updated successfully!'));
	            $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=dashboard'));
        	}
		}

		$this->redirect($request->getServer('HTTP_REFERER'));
	}
	
	private function scandir($dir) {
		$dir = rtrim($dir, '/');
		$data = scandir($dir);
		$files = array();
		if($data) {
			foreach($data AS $d) {
				if(!in_array($d, array('.','..'))) {
					if(is_dir($dir . '/' . $d)) {
						$res = self::scandir($dir . '/' . $d);
						$files = array_merge($files, $res);
					} else {
						$files[] = $dir . '/' . $d;
					}
				}
			}
		}
		return $files;
	}
	
	private function rename_if_exists($dir, $filename) {
    	$dir = rtrim($dir, '/');
	    $ext = strrchr($filename, '.');
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
}
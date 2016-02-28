<?php

class UsersController extends JO_Action {
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	/* PORTFOLIO */
	public function indexAction() {
			
		$request = $this->getRequest();
		$username = $request->getRequest('username');
		
		$action_method = $username . 'Action';
		if(method_exists('UsersController', $action_method)) {
			return $this->forward('users', $username);
		}
		
		$user = $this->view->users = Model_Users::getByUsername($username);
		
		if(!$user) {
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
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		$this->getLayout()->meta_title = $user['firstname'] .' '. $user['lastname'] .' - '.$user['username'];
    	$this->getLayout()->meta_description = $user['firstname'] .' '. $user['lastname'] .' - '. $user['username'];
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Authors'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=authors')
			),
			array(
				'name' => $user['username']
			)
		);
		if(JO_Session::get('user_id') == $user['user_id']) $my_profile = true;
		else $my_profile = false;
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$link = $request->getBaseUrl() . '?controller=users&username='. $user['username'];
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
		
		$total_records = Model_Items::countByUser($user['user_id']);
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$items = Model_Items::getByUser($user['user_id'], $start, $limit, ($sort == 'price' ? 'rprice' : 'items.'. $sort) .' '. $order, (isset($where) ? $where : ''));
		
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
	        	if($my_profile) {
	        		$item['delete_txt'] = $this->translate('Are you sure you want to delete the item? Once deleted it can not ne restored again!');
					$item['delete_href'] = WM_Router::create($request->getBaseUrl() .'?controller=items&action=delete&item_id='. $item['id']);
				}
	        	$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=preview&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
				$this->view->items[] = Helper_Items::returnViewIndex($item, 'category');
	        }
	    }
		
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* EDIT USER SETTINGS */
	public function editAction() {
		
		$request = $this->getRequest();
		
	    if(!JO_Session::get('user_id')) {
	        	
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
			$redir = WM_Router::create($request->getBaseUrl() . '?controller=users&action=login');
			
			if($request->getRequest('tool') == 'change_avatar') {
				die(json_encode(array('logout' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=login'))));
			} else {
	        	$this->redirect($redir);
			}
	    }
	    
		$this->getLayout()->meta_title = $this->translate('Edit settings');
    	$this->getLayout()->meta_description = $this->translate('Edit settings');
    	
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$tool = $request->getRequest('tool');
		
		$username = JO_Session::get('username');
		
		$this->view->user = Model_Users::getByUserName($username);
		$this->view->author_header = Helper_Author::authorHeader($this->view->user);
		
		$this->view->settings_box = Helper_Author::getSettingsBox($tool);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $username)
			),
			array(
				'name' => $this->translate('Settings'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit')
			)
		);
		
		switch($tool) {
			case 'change_avatar':
				$image = $request->getFile('file');
				if($image) { 
					$users_path = '/users/' . JO_Date::getInstance(JO_Session::get('register_datetime'), 'yy/mm') . '/' . JO_Session::get('user_id').'/';
		
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
					
							$model_images = new Model_Images;
							if(JO_Session::get('avatar')) {
								$model_images->deleteImages(JO_Session::get('avatar'), true);
							}
							$thumb = $model_images->resize($file_path, JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
						
							Model_Users::editAvatar(JO_Session::get('user_id'), $file_path);
							
							die('{ "avatar": "'. $thumb .'", "msg_success": "'. $this->translate('You have successfully changed your avatar') .'"}');
						} else {
							die('{ "msg_error": "'. $this->translate('There was an unexpected error with uploading the file') .'"}');
						}
					} else
						die('{ "msg_error": "'. $this->translate('The file must be valid image') .'" }');
				}
			break;
			case 'change_password':				
				if($request->isPost()) {
					$s = Model_Users::editPassword(
						JO_Session::get('user_id'), 
							array(
								'password' => $request->getPost('password'), 
								'new_password'=> $request->getPost('new_password'), 
								'new_password_confirm' => $request->getPost('new_password_confirm')
							)
						);
					
					if($s === true) {
						$this->session->set('msg_success', $this->translate('You have successfully updated your password'));
					} else {
						$this->session->set('msg_error', $s);
					}
					
					$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=edit&tool=change_password'));		
				}
				
				$this->view->formtitle = $this->translate('Change your password');
				$this->view->crumbs[] = array(
					'name' => $this->view->formtitle
				);
				
				$this->view->author_form = $this->view->renderByModule('single_user/change_password', 'users', 'themes');
			break;
			case 'exclusive_author':
				if($request->isPost()) {
					
					$exclusive_author = $request->getPost('exclusive_author');
					Model_Users::editExclusive(JO_Session::get('user_id'), $exclusive_author);
					
					if($exclusive_author == 'true') {
						JO_Session::set('msg_success', $this->translate('You have successfully changed to exclusive author'));
					} else {
						JO_Session::set('msg_success', $this->translate('You have successfully changed to non exclusive author'));
					}
					
					$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=edit&tool=exclusive_author'));
				}
				
				if($this->view->user['exclusive_author'] == 'true') {
					$this->view->formtitle = $this->translate('Exclusive Author');
					$this->view->button = $this->translate('Unsubscribe me as exclusive author');
				} else {
					$this->view->formtitle = $this->translate('Non-Exclusive Author');
					$this->view->button = $this->translate('Subscribe me as exclusive author');
				}
				
				$this->view->top_text =  $this->translate('Agreeing to keep your portfolio of items for sale exclusive to the Marketplaces entitles you to a higher percentage of each sale - from 40% to 70%. You can still sell other items elsewhere (on other marketplaces, your own site) however any items you place on an Marketplace must be exclusively sold there.');
				$this->view->bottom_text = $this->translate('You can opt-out of the exclusivity program by clicking the button below. You will be given a 30 day grace period wherein the agreement is still observed after which your payments will return to normal and you may commence selling your items elsewhere.');
				
				$this->view->crumbs[] = array(
					'name' => $this->view->formtitle
				);
								
				$this->view->author_form = $this->view->renderByModule('single_user/exclusive_author', 'users', 'themes');
			break;
			case 'sale_license':
				if($request->isPost()) {
				  		
					if($request->getPost('license')) {
						Model_Users::editLicense(JO_Session::get('user_id'), $request->getPost('license'));
				  		JO_Session::set('msg_success', $this->translate('You have successfully changed the license types'));
					} else {
						JO_Session::set('msg_error', $this->translate('You have to choose your license'));
					}
					
				  	$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=edit&tool=sale_license'));
				}	
				
				$this->view->formtitle = $this->translate('Sale License');
				
				$this->view->crumbs[] = array(
					'name' => $this->view->formtitle
				);
				
				$this->view->license = unserialize($this->view->user['license']);
				
				$this->view->author_form = $this->view->renderByModule('single_user/sale_license', 'users', 'themes');
			break;
			case 'social':
				
				if($request->issetParam('sn')) {
					$sn = (int) $request->getParam('sn');
					unset($this->view->user['social'][$sn - 1]);
					$this->view->user['social'] = array_values($this->view->user['social']);
					Model_Users::editSocial(JO_Session::get('user_id'), $this->view->user['social']);
					
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=social'));
				}
				
				if($request->isPost()) {
					$socials = array();
					$errors = array();
					$social_links = $request->getPost('social_link');
					$social_names = $request->getPost('social_name');
					
					$cnt = count($social_links) < count($social_names) ? count($social_names) : count($social_links);
					for($i = 0; $i < $cnt; $i++) {
						
						$social_names[$i] = trim($social_names[$i]);
						$social_links[$i] = trim($social_links[$i]);
						
						if(empty($social_names[$i]) && empty($social_links[$i])) break;
						
						if(empty($social_names[$i])) {
							$errors[$i]['social_name'] = $this->translate('You must fill the name of the social media');
						}

						if(empty($social_links[$i])) {
							$errors[$i]['social_link'] = $this->translate('You must fill valid link for your profile');
						}
						
						$socials[] = array(
							'name' => $social_names[$i],
							'href' => $social_links[$i]
						);	
					}
					
					if(empty($errors)) {
						Model_Users::editSocial(JO_Session::get('user_id'), $socials);
						JO_Session::set('msg_success', $this->translate('You have successfully changed your social media profiles'));
					} else {
						JO_Session::set('msg_error', $errors);
						$this->session->set('data', $socials);
					}
			
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=social')); 
				}
				
				if($this->session->issetKey('data')) {
					$social = $this->session->get('data');
					$this->session->clear('data');
					
					if(count($social) > count($this->view->user['social'])) {
						$last = end($social);
						$this->view->new_user = $last['name'];
						$this->view->new_href = $last['href']; 
					}
				}
				
				
				
				$this->view->formtitle = $this->translate('Social Media profiles');
				$this->view->crumbs[] = array(
					'name' => $this->view->formtitle
				);
				
				$this->view->author_form = $this->view->renderByModule('single_user/social', 'users', 'themes');
			break;
			default: 
				if($request->isPost()) {
					$firstname = trim($request->getPost('firstname'));
					$lastname = trim($request->getPost('lastname'));
					$email = trim($request->getPost('email'));
					
					if(empty($firstname)) {
						$error['firstname'] = $this->translate('You must fill your firstname');
					}
					
					if(empty($lastname)) {
						$error['lastname'] = $this->translate('You must fill your lastname');
					}		
					
					if(empty($email)) {
						$error['email'] = $this->translate('You must fill your email');
					} elseif(!Model_Users::ValidMail($email)) {
						$this->view->error['email'] = $this->translate('You must fill valid email');
					}
					
					if($request->getPost('facebook') == 1) {
						if($this->view->user['fb_id'] == 0) {
								
							$facebook = new WM_Facebook_Api(array(
					   			'appId' => JO_Registry::forceGet('facebook_appid'),
					   			'secret' => JO_Registry::forceGet('facebook_secret')  
					  		));
							
							$fbData = $facebook->api('/me');
							
							$request->setParams('fb_id', $fbData['id']);
						} else {
							$request->setParams('fb_id', $this->view->user['fb_id']);
						}
						
					} else {
						$request->setParams('fb_id', 0);
					}
					
					if(!count($error)) {
						Model_Users::editPersonal($this->view->user['user_id'], $request->getParams());
						JO_Session::set('msg_success', $this->translate('Your personal data has been successfully saved'));
					} else {
						JO_Session::set('msg_error', $error);
					}
					
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'));
				}
			
				$this->view->formtitle = $this->translate('Avatar and Personal Information');
				$this->view->crumbs[] = array(
					'name' => $this->view->formtitle
				);
				
				$model_images = new Helper_Images();
				if($this->view->user['avatar']) {
					$thumb = $model_images->resize($this->view->user['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else 
					$this->view->user['avatar'] = 'data/themes/images/noavatar.png';
				
				$this->view->upl_form_action = WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=change_avatar');
				
				$this->view->countries = Model_Countries::getCountries();
				
				$this->view->author_form = $this->view->renderByModule('single_user/avatar', 'users', 'themes');
		}
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
		$this->view->children['footer_part'] = 'layout/footer_part';	
	}
	
	/* LOGOUT */
    public function logoutAction() {
    	
		$base_upload_folder  = realpath(BASE_PATH . '/uploads');
		$temp_upload_folder = $base_upload_folder .'/temporary/'. JO_Date::getInstance(JO_Session::get('register_datetime'), 'yy/mm') . '/';
		
		$upload_files = JO_Session::get('uploaded_files');
		if($uploaded_files) {
			foreach($uploaded_files[0] as $f) {
				if(file_exists($temp_upload_folder . $f['filename']))
					unlink($temp_upload_folder . $f['filename']);
			}
		}	
		
		$uploaded_arhives = JO_Session::get('uploaded_arhives');
		if($uploaded_arhives) {
			foreach($uploaded_arhives[0] as $f) {
				
				if(file_exists($temp_upload_folder . $f['filename']))
					unlink($temp_upload_folder . $f['filename']);
			}
		}
    	JO_Session::clear();
		JO_Cookie::delete('currency');
		
		$temp_upload_folder = $base_upload_folder .'/temporary/';
		
		JO_Directories::deleteOldFiles($temp_upload_folder);
		
    	$this->redirect(JO_Request::getInstance()->getBaseUrl());
    }
    
	/* FOLLOWING & FOLLOWERS */
	public function followingAction()
	{
		$request = $this->getRequest();
		
		$list_type = $request->getParam('list_type');
		if($request->getParam('list_type') == 'followers')
			$this->view->following = $following =  true;
		else {
			$this->view->following = $following = false;
		}
		
		if(!$following)
			$username =  $request->getParam('following'); 
		else 
			$username = $request->getParam('followers');
		
		$user = $this->view->users = Model_Users::getByUsername($username);
		
		if(!$user) {
			return $this->forward('error', 'error404');
		}

		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'asc';
		}
		
		$sort = $request->getRequest('sort');
		if(is_null($sort)) {
			$sort = 'username';
		}
		
		if($following)
			$name = $this->translate('Following');
		else
			$name = $this->translate('Followers');
			
		$this->getLayout()->meta_title = $user['firstname'] .' '. $user['lastname'] .' - '.$user['username'] .' - '. $name;
    	$this->getLayout()->meta_description = $user['firstname'] .' '. $user['lastname'] .' - '. $user['username'] .' - '. $name;
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Authors'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=authors')
			),
			array(
				'name' => $user['username']
			)
		);
		
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$link = $request->getBaseUrl() . '?controller=users&action='. (!$following ? 'following' : 'followers') .'&username='. $user['username'];
		$this->view->sort_by = array(
			array(
				'name' => $this->view->translate('rating'),
				'href' => WM_Router::create($link . '&sort=rating'),
				'is_selected' => ($sort == 'rating' ? true : false)
			),
			array(
				'name' => $this->view->translate('sales'),
				'href' => WM_Router::create($link . '&sort=sales'),
				'is_selected' => ($list_type == 'top' ? ($sort == 'position' ? true : false) : ($sort == 'sales' ? true : false))
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
		
		$total_records = Model_Users::countFollowers($user['user_id'], $following);
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$items = Model_Users::getFollowers($user['user_id'], $start, $limit, $following, 'users.'. $sort .' '. $order);
		
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
	        	$this->view->items[] = Helper_Author::returnFollowing($item, $user, ($following ? false : true));
	        }
	    }
		
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
		
	}
	
	/* SHOW FOLLOWERS */
	public function followersAction()
	{
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		$request->setParams('list_type', 'followers');
		$this->forward('users', 'following');
	}
	
	/* ADD FOLLOWING */
	public function followAction() {
        $request = $this->getRequest();
		
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$follow_name = $request->getParam('follow');
		$user_id = JO_Session::get('user_id');
		
		if($user_id) {
			$follow = Model_Users::getByUsername($follow_name);
			if($user_id != $follow['user_id']) {
				Model_Users::addFollow($follow['user_id'], $user_id);
				$fl = Model_Users::getFollowers($user_id);
				JO_Session::set('following', $fl);
			}
		}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
    }
    
	/* REMOVE FOLLOW */
	public function unfollowAction() {
        $request = $this->getRequest();
		
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$follow_name = $request->getParam('unfollow');
		$user_id = JO_Session::get('user_id');
		
		if($user_id) {
			$follower = Model_Users::getByUsername($follow_name);
			if($user_id != $follow['user_id']) {
				Model_Users::deleteFollow($follower['user_id'], $user_id);
				$fl = Model_Users::getFollowers($user_id);
				JO_Session::set('following', $fl);
			}
		}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
	}
	
    public function topauthorsAction() {
        $this->setInvokeArg('noViewRenderer', true);
            $page = $this->getRequest()->getRequest('p');
            $page = $page>0 ? $page : 1;
            $start = ($page-1)*9;
            $users = Model_Users::topUsers($start);
            $text = '';
            if($users) {
            	$model_images = new Model_Images();
                foreach($users as $a) {
                    $avatar = $model_images->resize($a['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
                    $text .= '<a class="user cleared" href="'.WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&username='.$a['username']).'"><img src="'.$avatar.'" width="80"  height="80" border="0" alt="'.$a['username'].'" title="'.$a['username'].'" /></a>';
    		    }
            }

            if($page > 1) {
				$text = '<a href="javascript: void(0);" onclick="$.ajax({complete: function(request) { screenshotPreview(); hideLoading(); }, beforeSend: function() { showLoading(); }, dataType: &quot;script&quot;, type: &quot;post&quot;, url: &quot;'.WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=topauthors&p='.($page-1)).'&quot;}); return false;" class="left-arrow"><img src="data/theme/images/left-arrow.png" alt="" /></a>'.$text;
			}	
			else {
				$text = '<a href="javascript: void(0);" title="" class="left-arrow"><img src="data/theme/images/left-arrow.png" alt="" /></a>'.$text;
			}		
			
			if(Model_Users::CountTopUsers() > ($page*9)) {
				$text .= '<a href="javascript: void(0);" onclick="$.ajax({complete: function(request) { screenshotPreview(); hideLoading(); }, beforeSend: function() { showLoading(); }, dataType: &quot;script&quot;, type: &quot;post&quot;, url: &quot;'.WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=topauthors&p='.($page+1)).'&quot;}); return false;" class="right-arrow"><img src="data/theme/images/right-arrow.png" alt="" /></a>';
			}	
			else {
				$text .= '<a href="javascript: void(0);" title="" class="right-arrow"><img src="data/theme/images/right-arrow.png" alt="" /></a>';
			}
			
			$text = 'jQuery("#top_authors_container").html(\''.$text.'\')';
            
			echo $text;
    }
    
	/* ALL AUTHORS */
	public function authorsAction()
	{
		$this->getRequest()->setParams('list_type', 'authors');
		$this->forward('users', 'top');
	}
	
	/* TOP AUTHORS */
    public function topAction() {
    	$request = $this->getRequest();
		
		JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
		
		$list_type = $request->getParam('list_type');
		if(!$list_type) {
			$this->getLayout()->meta_title = $this->translate('Top authors');
    		$this->getLayout()->meta_description = $this->translate('Top authors');
			$this->view->top_authors_name = $this->translate('Top Authors');
			$list_type = 'top';
		} else {
			$this->getLayout()->meta_title = $this->translate('All authors');
    		$this->getLayout()->meta_description = $this->translate('All authors');
			$this->view->top_authors_name = $this->translate('All Authors');
		}
		
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'desc';
		}
		
		$sort = $request->getRequest('sort');
		
		if($list_type == 'top') {
			if(is_null($sort) || $sort == 'sales') {
				$sort = 'position';
			}
		} else {
			if(is_null($sort)) {
				$sort = 'username';
			}
		}
		
		$order = $request->getRequest('order');
		if(is_null($order)) {
			$order = 'asc';
		}
		
		$prefix = 'users.';
		
		$link = $request->getBaseUrl() . '?controller=users&action='. $list_type;
		$this->view->sort_by = array(
			array(
				'name' => $this->view->translate('rating'),
				'href' => WM_Router::create($link . '&sort=rating'),
				'is_selected' => ($sort == 'rating' ? true : false)
			),
			array(
				'name' => $this->view->translate('sales'),
				'href' => WM_Router::create($link . '&sort=sales'),
				'is_selected' => ($list_type == 'top' ? ($sort == 'position' ? true : false) : ($sort == 'sales' ? true : false))
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
		
		/* PAGENATION */
		
		$link .= '&order='. $order;
		
		$total_records = Model_Users::CountTopUsers();
		
		if($list_type == 'top')
			$total_records > 999 && $total_records = 999;
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
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
				
		$users = Model_Users::topUsers($start, $limit, ($sort != 'position' ? $prefix : '') . $sort .' '. $order, $list_type);
		
		if($users) {
			$this->view->users = array();
			foreach($users as $user) {
				$this->view->users[] = Helper_Author::getTopAuthor($user, $list_type);
			}
		}
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
    /* by joro */
    /* DOWNLOADS */
    public function downloadsAction() {
        
		$request = $this->getRequest();
		
        if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to view your downloads'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
        
		$this->getLayout()->meta_title = $this->translate('Downloads');
    	$this->getLayout()->meta_description = $this->translate('Downloads');
    	
    	if(is_numeric($request->getParam('download_id'))) {
    		
    		if($request->getParam('certificate')) {
    			
    			$item = Model_Items::get($request->getParam('download_id'));
    			$order_info = Model_Orders::isBuyed($request->getParam('download_id'), JO_Session::get('user_id'), $request->getParam('oid'));
    			if($item && $order_info) {
	    			$this->noViewRenderer(true);
	    			$response = $this->getResponse(); 
	    			$response->addHeader('Content-Type: text/plain; charset=UTF-8');
					$response->addHeader('Content-Disposition: attachment; filename="item_licence('.$order_info['id'].').txt"');
					$response->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					$response->addHeader('Pragma: public');
					$response->addHeader("Content-Transfer-Encoding: binary");
					$response->addHeader('Expires: 0');
				
					if($order_info['extended'] == 'true') {
						$this->view->licence = $this->translate('ONE EXTENDED LICENCE');
					} else {
						$this->view->licence = $this->translate('ONE REGULAR LICENCE');
					}
					
					$this->view->username = JO_Session::get('username');
					$this->view->userfirstlastname = JO_Session::get('firstname') . ' ' . JO_Session::get('lastname');
					$this->view->itemname = $order_info['item_name'];
					$this->view->item_href = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&item_id='.$order_info['item_id']);
					$this->view->item_id = $order_info['item_id'];
					$this->view->order_id = $order_info['id'];
					$this->view->admin_mail = JO_Registry::get('admin_mail');
					if(isset($order_info['main_file_info']) && $order_info['main_file_info']) {
						$this->view->main_file_info = $order_info['main_file_info'];
					}
					
					$this->view->domain = $request->getDomain();
					
					$response->appendBody(str_replace("\n", "\n\r", $this->view->render('certificate','users')));
					exit;
					
    			} else {
    				$this->forward('error', 'error404');
    			}
    			
    		} else {
    		
	    		$item = Model_Items::get($request->getParam('download_id'));
	    		
	    		if($item['free_file'] == 'true' || $item['user_id'] == JO_Session::get('user_id') || Model_Orders::isBuyed($item['id'], JO_Session::get('user_id'))) {
	    			if(file_exists(BASE_PATH . '/uploads' . $item['main_file']) && is_file(BASE_PATH . '/uploads' . $item['main_file'])) {
	    				$this->noViewRenderer(true);
	    				$response = $this->getResponse();   				
						$response->addHeader('Content-Type: application/zip');
						$response->addHeader('Content-Disposition: attachment; filename="'.$item['main_file_name'].'"');
						$response->addHeader("Content-Length:".filesize(BASE_PATH . '/uploads' . $item['main_file']));
						$response->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						$response->addHeader('Pragma: public');
						$response->addHeader("Content-Transfer-Encoding: binary");
						$response->addHeader('Expires: 0');
						$response->addHeader('Content-Description: '.JO_Registry::get('meta_title').' Download');
						readfile(BASE_PATH . '/uploads' . $item['main_file']);
						
	    			} else {
	    				$this->forward('error', 'error404');
	    			}
	    		} else {
	    			$this->forward('error', 'error404');
	    		}
    		}
    	}
    	
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$orders = Model_Orders::getAllUserOrders(0 , false, 'item_id');
		
		$this->view->orders = array();
		
		$username = JO_Session::get('username');
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $username,
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($username))
			),
			array(
				'name' => $this->view->translate('Downloads')
			)
		);
		
		$user = $this->view->users = Model_Users::getByUsername($username);
		$this->view->author_header = Helper_Author::authorHeader($user);
		$this->view->rate_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=rate_item');
		
	    if($orders) {
			foreach($orders AS $order) {
				$order['download'] = true;
				$order['regular_href'] = WM_Router::create($request->getBaseUrl() .'?controller=licence');
				$order['licence_href'] = WM_Router::create($request->getBaseUrl() .'?controller=users&action=downloads&item_id='. $order['item_id'].'/certificate/1');
				$order['download_href'] = WM_Router::create($request->getBaseUrl() .'?controller=users&action=downloads&item_id='. $order['item_id']);
				$this->view->orders[] = Helper_Items::returnViewIndex($order, 'downloads');
			}
		}
		
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
	
	/* DASHBOARD */
    public function dashboardAction() {
    	$request = $this->getRequest();
		$model_images = new Model_Images();
		
        if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to view your dashboard'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
		$this->getLayout()->meta_title = $this->translate('Dashboard');
    	$this->getLayout()->meta_description = $this->translate('Dashboard');
	    
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$username = JO_Session::get('username');
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $username,
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($username))
			),
			array(
				'name' => $this->translate('Dashboard')
			)
		);
		
		$user = Model_Users::getByUsername($username);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$where = "`items_comments`.`user_id` = '" . $user['user_id'] . "' AND `items_comments`.`reply_to` = 0";
		$total_records = Model_Comments::getTotal($where);
		
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$bayers = Model_Orders::getClients($user['user_id']);
    	$comments = Model_Comments::getAll($start, $limit, $where, true, "datetime DESC");
		
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
	    		$comment['datetime'] = WM_Date::format($comment['datetime'], 'dd M yy');
	    		$comment['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($comment['username']));
	    		$comment['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=report/'.$comment['id']);
	    		$comment['replyhref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=reply&c_id='.$comment['id']);
				$comment['item_href'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=comments&item_id='.$comment['item_id'].'&name='.WM_Router::clearName($comment['name'])).'#c_'.$comment['id'];
				
	    		foreach($comment['reply'] AS $key => $reply) {
	    			if($comment['reply'][$key]['avatar']) {
						$comment['reply'][$key]['avatar'] = $model_images->resize($comment['reply'][$key]['avatar'], 50, 50, true);
					} else 
						$comment['reply'][$key]['avatar'] = 'data/themes/images/small_noavatar.png';
					$bbcode_parser->parse($comment['reply'][$key]['comment']);
	    			$comment['reply'][$key]['comment'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
		    		$comment['reply'][$key]['datetime'] = WM_Date::format($reply['datetime'], 'dd M yy');
		    		$comment['reply'][$key]['is_buy'] = ($bayers && in_array($reply['user_id'], $bayers));
		    		$comment['reply'][$key]['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($comment['reply'][$key]['username']));
		    		$comment['reply'][$key]['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=items&action=report/'.$comment['reply'][$key]['id']);
	    		}
	    		
	    		$this->view->comments[] = $comment;
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
		$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?action=users&action=dashboard&page={page}'));
		$this->view->pagination = $pagination->render();
		
		if(!empty($this->view->pagination)) {
			$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
		}
		
		//$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=items&action=add_comment&item_id='. $item['id'] .'&name='. WM_Router::clearName($item['name']));
		
    	$this->view->mainCategories = array();
		$categories = Model_Categories::getMain();		
		if($categories) {
			$this->view->mainCategories = array(
				array(
					'href' => '',
					'name' => $this->translate('Please select category')
				)
			);
			
			foreach($categories as $category) {
				$this->view->mainCategories[] = array(
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=upload&action=form&category_id='. $category['id']),
					'name' => $category['name']
				);
			} 
		}
		
		$this->view->total_balance = WM_Currency::format($user['total']);
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->percent = Model_Percentes::getPercentRow($user);
		
		$help = Model_Pages::get(JO_Registry::forceGet('page_upload_item'));
        if($help) {
        	$this->view->page_upload_item = array(
        		'name' => $help['name'],
        		'href' => WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='. $help['id'] .'&name='.WM_Router::clearName($help['name']))
        	);
        }
		
		$weekStats = Model_Orders::getWeekStats($user['user_id']);
    	
    	$this->view->weekStats_earning = WM_Currency::format($weekStats['earning']);
    	$this->view->weekStats_sold = $weekStats['sold'];
		
    	$user['total'] = WM_Currency::format($user['total']);
    	$this->view->user = $user;
    	
    	$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    }
    
    public function historyAction() {
        
        if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to view your history'));
	        $this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
		   $this->getLayout()->meta_title = $this->translate('History');
    	$this->getLayout()->meta_description = $this->translate('History');
        $this->view->history = array();
    	$history = Model_History::getAll(0, 0, " `user_id` = '".JO_Session::get('user_id')."'");
    	if($history) {
    		foreach($history AS $his) {
    			$his['datetime'] = JO_Date::getInstance($his['datetime'], 'dd MM yy H:i:s')->toString();
    			$this->view->history[] = $his;
    		}
    	}
    	
    	$this->view->usertotal = WM_Currency::format(JO_Session::get('total'));
    	
        $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
    	$this->view->children['users_toolbar'] = 'layout/users_toolbar';
    }
    
    public function view_collectionAction() {
  
		$request = $this->getRequest();
		
		$collection_id = $request->getRequest('view_collection');
		
		if(!$collection_id) {
			return $this->forward('error', 'error404');
		}
		
		$collection = Model_Collections::get($collection_id);
        
		$this->view->my_profil = $collection['username'] == JO_Session::get('username');
		if(!$collection || ($collection['public'] == 'false' && !$this->view->my_profil)) {
			return $this->forward('error', 'error404');
		}
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} else if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$this->getLayout()->meta_title = $collection['firstname'] .' '. $collection['lastname'] .' - '.$collection['username'] .' - '. $collection['name'];
    	$this->getLayout()->meta_description = $collection['firstname'] .' '. $collection['lastname'] .' - '. $collection['username'] .' - '. $collection['name'];
		
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
		
		$user = $this->view->users = Model_Users::getByUsername($collection['username']);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$this->view->form_name = $this->translate('Edit Collection');
		$this->view->form_data = $collection;
		$this->view->upload_link = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=change&collection_id='.$collection['id']);
		
		$collection['without_bt'] = true;
		$this->view->collection_name = $collection['name'];
		
		if($this->view->my_profile) {
			$collection['delete_href'] = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=delete&collection_id='.$collection['id']);
			$collection['delete_txt'] = $this->translate('Are you sure you want to delete the collection? Once deleted it can not ne restored again!');
			$collection['edit_public_href'] = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=change&collection_id='.$collection['id']);
		}
		$this->view->rate_link = WM_Router::create($request->getBaseUrl() .'?controller=collections&action=rate_collection&collection_id='.$collection['id']);
		$this->view->collection = Helper_Collection::returnViewIndex($collection, true);
		
		/* CRUMBS */	
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $collection['username'],
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='.$collection['username'])
			),
			array(
				'name' => $this->view->translate('Collections'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=collections&username='.$collection['username'])
			)
		);
		
		$total_records = Model_Items::CountByCollection($collection_id);
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$last_page = max(ceil($total_records / $limit), 1);
			$start = (($last_page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$items = Model_Items::getAllByCollection($collection_id, $start, $limit,  $prefix . $sort .' '. $order);
		if($items) {
			
			$link = $request->getBaseUrl() . '?&controller=users&action=view_collection&collection_id='. $collection_id .'&name='. $collection['name'];
		
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
				$item['delete_txt'] = $this->translate('Are you sure you want to delete the item?');
				$item['delete_href'] = WM_Router::create($request->getBaseUrl() .'?controller=collections&action=delete&collection_id='.$collection['id'].'&item='. $item['id']);
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
    
    /* and by joro */
    /* DEPOSIT */
	public function depositAction() {
		
		$request = $this->getRequest();
	    
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }

		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}

		if(JO_Session::get('deposit_id')) {
			Model_Deposit::delete(JO_Session::get('deposit_id'));
			JO_Session::clear('deposit_id');
		}

		$user = Model_Users::getUser(JO_Session::get('user_id'));
		$user['total'] = WM_Currency::format($user['total']);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $user['username'])
			),
			array(
				'name' => $this->translate('Deposit')
			)
		);
		
		JO_Session::set('deposit_id', 0);		
		
		$this->getLayout()->meta_title = $this->translate('Deposit');
    	$this->getLayout()->meta_description = $this->translate('Deposit');
		
		$prepaid_price_discount = JO_Registry::get('prepaid_price_discount');
		if($prepaid_price_discount) {
			if(strpos($prepaid_price_discount, '%')) {
				$this->view->discount = $prepaid_price_discount;
			} else {
				$this->view->discount = WM_Currency::format($prepaid_price_discount);
			}
		}
		
		$this->view->deposits = array();
		$deposits = Model_Deposit::getAll();
	
		if($deposits) {
			foreach($deposits AS $key => $deposit) {
				$this->view->deposits[$key] = $deposit;
				$this->view->deposits[$key]['deposit_formated'] = '$ '. $deposit['deposit']; //WM_Currency::getCurrencySymbol() .' '. $deposit['deposit'];
			}
		}
		
		$image_model = new Helper_Images();
		
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
							'logo' => $logo
						);
					}
				}
			}
	    	
	    	array_multisort($sort_order, SORT_ASC, $payments_data);
	    	
	    	$this->view->payments = $payments_data;
		}
		
		$this->view->deposit_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=payment');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* MEMBERSHIP */
	public function membershipAction() {
		
		$request = $this->getRequest();
	    
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }

		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}

		if(JO_Session::get('membership_id')) {
			Model_Membership::delete(JO_Session::get('membership_id'));
			JO_Session::clear('membership_id');
		}

		$user = Model_Users::getUser(JO_Session::get('user_id'));
		$user['total'] = WM_Currency::format($user['total']);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $user['username'])
			),
			array(
				'name' => $this->translate('Membership')
			)
		);
		
		JO_Session::set('membership_id', 0);		
		
		$this->getLayout()->meta_title = $this->translate('Membership');
    	$this->getLayout()->meta_description = $this->translate('Membership');
		
		$this->view->membership = array();
		$membership = Model_Membership::getAll();
		
		if($membership) {
			foreach($membership AS $key => $value) {
				$this->view->membership[$key] = $value;
				$this->view->membership[$key]['formated_price'] = WM_Currency::format($value['price']);
			}
		}
		
		$image_model = new Helper_Images();
		
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
							'logo' => $logo
						);
					}
				}
			}
	    	
	    	array_multisort($sort_order, SORT_ASC, $payments_data);
	    	
	    	$this->view->payments = $payments_data;
		}
		
		$this->view->membership_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=payment_membership');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* MEMBERSHIP */
	public function payment_membershipAction() {
		$this->setViewChange('payment');
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$this->view->title = $this->translate('Membership pre-paid cash');
		
		if($request->isPost()) {
			if($request->issetPost('amount')) {
				$amount = $request->getPost('amount');
				$image_model = new Helper_Images();
		
				$files = glob(dirname(__FILE__) . '/Payments/*.php');
				if($files) {
					
					foreach($files as $file) {
						if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
							$key = mb_strtolower($match[1], 'utf-8');
							if($request->issetPost($key)) {
								$memID = Model_Membership::add($amount);
								if($memID) {
									JO_Session::set('membership_id', $memID);
									if(JO_Registry::forceGet($key . '_status')) {
										JO_Loader::loadFile($file);
										$form = $this->view->callChildren('payments_'.$key.'/membershipForm');
										if($form) {
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
											
											$this->view->payment = array(
												'key' => $key,
												'edit' => $request->getModule() . '/payments_' . $key,
												'name' => $this->translate($match[1]),
												'logo' => $logo,
												'description' => $this->view->callChildren('payments_'.$key.'/description'),
												'form' => $form
											);
											
											break;
										}
									}
								}
							}
						}
					}	
				}
			}
		}
				
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $user['username'])
			),
			array(
				'name' => $this->translate('Membership')
			)
		);
		
		$this->view->total = WM_Currency::format(JO_Session::get('total'));
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* PAYMENT */
	public function paymentAction() {
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$this->view->title = $this->translate('Deposit pre-paid cash');
		
		if($request->isPost()) {
			if($request->issetPost('amount')) {
				$amount = $request->getPost('amount');
				$image_model = new Helper_Images();
		
				$files = glob(dirname(__FILE__) . '/Payments/*.php');
				if($files) {
					
					foreach($files as $file) {
						if(preg_match('/^([\w]{1,})Controller$/i', basename($file, '.php'), $match)) {
							$key = mb_strtolower($match[1], 'utf-8');
							if($request->issetPost($key)) {
								$depositID = Model_Deposit::addDeposit($amount);
								if($depositID) {
									JO_Session::set('deposit_id', $depositID);
									if(JO_Registry::forceGet($key . '_status')) {
										JO_Loader::loadFile($file);
										$form = $this->view->callChildren('payments_'.$key.'/depositForm');
										if($form) {
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
											
											$this->view->payment = array(
												'key' => $key,
												'edit' => $request->getModule() . '/payments_' . $key,
												'name' => $this->translate($match[1]),
												'logo' => $logo,
												'description' => $this->view->callChildren('payments_'.$key.'/description'),
												'form' => $form
											);
											
											break;
										}
									}
								}
							}
						}
					}	
				}
			}
		}
				
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. JO_Session::get('username'))
			),
			array(
				'name' => $this->translate('Deposit')
			)
		);
		
		$this->view->total = WM_Currency::format(JO_Session::get('total'));
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* IS PAID */
	public function success_depositAction() {
		$this->noViewRenderer(true);
	    $request = $this->getRequest();
		
	    $info = Model_Deposit::getDeposit(JO_Session::get('deposit_id'));
		
		if($info && $info['paid'] == 'true') {
			Model_Deposit::depositIsPay($info['id']);
		    
		    JO_Session::set('msg_success', $this->translate('You have successfully made a deposit!'));
		} else {
			JO_Session::clear('deposit_id');
		    JO_Session::set('msg_error', $this->translate('Your payment have status '. WM_Orderstatuses::orderStatuses($info['order_status_id'])));
		}
		
		$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=deposit'));
	}
	
	/* IS PAID */
	public function success_membershipAction() {
		$this->$this->noViewRenderer(true);
		$request = $this->getRequest();
		
	    $info = Model_Membership::get(JO_Session::get('membership_id'));
		
		if($info && $info['paid'] == 'true') {
			Model_Membership::membershipIsPay($info['id']);
		    
		    JO_Session::set('msg_success', $this->translate('You have successfully made a payment!'));
		} else {
			JO_Session::clear('membership_id');
		    JO_Session::set('msg_error', $this->translate('There was error with your payment!'));
		}
		
		$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=membership'));
	}
	
	/* COLLECTIONS */
	public function collectionsAction()
	{
		$request = $this->getRequest();
		
		$username = $request->getRequest('collections');
		
		if(!$username) {
			return $this->forward('error', 'error404');
		}
		
		$user = $this->view->users = Model_Users::getByUsername($username);
		
		if(!$user) {
			return $this->forward('error', 'error404');
		}
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
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
		
		$this->view->public = $request->getRequest('public');
		if(is_null($this->view->public)) {
			$this->view->public = 1;
		}
		
		$oredr = $request->getRequest('order');
		if(!$order) {
			$order = 'desc';
		}
		
		if($user['user_id'] == JO_Session::get('user_id'))
			$this->view->my_profile = true;
		
		$this->getLayout()->meta_title = $user['firstname'] .' '. $user['lastname'] .' - '.$user['username'] .' - '. $this->translate('Collections');
    	$this->getLayout()->meta_description = $user['firstname'] .' '. $user['lastname'] .' - '. $user['username'] .' - '. $this->translate('Collections');
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Authors'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=authors')
			),
			array(
				'name' => $user['username']
			)
		);
		
		$this->view->author_header = Helper_Author::authorHeader($user);
		$this->view->form_name = $this->translate('New Collection');
		
		$link = $request->getBaseUrl() . '?controller=users&action=collections&username='. $user['username'];
		$link .= $this->view->public ? '/public/1' : '/public/0';
		
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
		
		$this->view->public_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=collections&username='. $user['username'] .'/public/1');
		$this->view->private_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=collections&username='. $user['username'] .'/public/0');
		$this->view->upload_link = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=upload');
		
		$total_records = Model_Collections::countByUser($user['user_id'], $this->view->public);
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$collections = Model_Collections::getByUser($start, $limit, $user['user_id'], 'collections.' . $sort .' '. $order, $this->view->public);
		if($collections) {
			foreach($collections as $collection) {
					
				if($this->view->my_profile) {
					$collection['delete_txt'] = $this->translate('Are you sure you want to delete the collection? Once deleted it can not ne restored again!');
					$collection['delete_href'] = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=delete&collection_id='.$collection['id']);
					$collection['edit_public_href'] = WM_Router::create($request->getBaseUrl() . '?controller=collections&action=change&collection_id='.$collection['id']);
				}
				
				$collection['href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=view_collection&collection_id='.$collection['id'].'&name='. $collection['name']);
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
	
	/* REGISTRATION */
	public function registrationAction() {
		
		$request = $this->getRequest();

		if(JO_Session::get('user_id')){
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'));
	    }
		
		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
		
	    $this->getLayout()->meta_title = $this->translate('Registration');
    	$this->getLayout()->meta_description = $this->translate('Registration');
		
	    $captcha = new Model_Recaptcha;
		$captcha->publicKey = JO_Registry::get('recaptcha_public_key'); 
		$captcha->privateKey = JO_Registry::get('recaptcha_private_key');
		
		if(JO_Session::issetKey('data')) {
			if(JO_Session::issetKey('msg_error')) {
				$this->view->msg_error = JO_Session::get('msg_error');
				JO_Session::clear('msg_error');
			} elseif(JO_Session::issetKey('error')) {
				$this->view->error = JO_Session::get('error');
				JO_Session::clear('error');
			}
			
			$this->view->user = JO_Session::get('data');
			JO_Session::clear('data');
		}
		
		if(JO_Session::issetKey('fb_data')) {
			$this->view->user = JO_Session::get('fb_data');
			JO_Session::clear('fb_data');
		}
		
		if($request->isPost()) {
			$captcha->checkCaptcha();
			$error = array();
			
			$this->view->firstname = trim($request->getPost('firstname'));
			$this->view->lastname = trim($request->getPost('lastname'));
			$this->view->email = trim($request->getPost('email'));
			$this->view->email_confirm = trim($request->getPost('email_confirm'));
			$this->view->username = trim($request->getPost('username'));
			$this->view->password = trim($this->getRequest()->getPost('password'));
			$this->view->password_confirm = trim($request->getPost('password_confirm'));
			
			if(empty($this->view->firstname)) {
				$error['efirstname'] = $this->translate('You must type your first name');
			}

			if(empty($this->view->lastname)) {
				$error['elastname'] = $this->translate('You must type your last name');
			}
			
			if(empty($this->view->email)) {
			
				$error['eemail'] = $this->translate('You must type your email');
				
			} elseif(!Model_Users::ValidMail($this->view->email)) {
			
				$error['eemail'] = $this->translate('You must type valid email');
				
			} elseif(Model_Users::isExistEmail($this->view->email)) {
			
				$error['eemail'] = $this->translate('The email you have entered is already in our database');
			
			}
			
			if(empty($this->view->email_confirm)) {
			
				$error['eemail_confirm'] = $this->translate('You must retype your email');
			
			} elseif($this->view->email_confirm != $this->view->email) {
			
				$error['eemail_confirm'] = $this->translate('The email adresses you have entered, does not match');
			
			}
			
			$methodNames  = array();
			if (version_compare(PHP_VERSION, '5.2.6') === -1) {
				$class = new ReflectionObject($this);
				$classMethods = $class->getMethods();

				foreach ($classMethods as $method) {
					$methodNames[] = $method->getName();
				}
			} else {
				$methodNames = get_class_methods($this);
			}
			
			$temp_methodNames = array(); 
			foreach($methodNames AS $methodName) {
				if(preg_match('/^([\w]{1,})Action$/i', $methodName, $match)) {
					$temp_methodNames[] = $match[1];
				}
			}
			
			$temp_methodNames = array_change_key_case($temp_methodNames, CASE_LOWER);
			
			if(empty($this->view->username)) {
			
				$error['eusername'] = $this->translate('You must type your username');
			
			} elseif(!preg_match('/^[a-zA-Z0-9_]+$/i', $this->view->username)) {
			
				$error['eusername'] = $this->translate('The username you have entered is not valid');
			
			} elseif(Model_Users::isExistUsername($this->view->username)) {
			
				$error['eusername'] = $this->translate('There is already registration with that username');
			
			} elseif(in_array(strtolower($this->view->username), $temp_methodNames)) {
			
				$error['eusername'] = $this->translate('This username can not be registered');
			
			}
			
			if(empty($this->view->password)) {
				$error['epassword'] = $this->translate('You must type your password');
			}
			
			if(empty($this->view->password_confirm)) {
			
				$error['epassword_confirm'] = $this->translate('You must retype your password');
			
			} elseif($this->view->password_confirm != $this->view->password) {
			
				$error['epassword_confirm'] = $this->translate('The passwords you have entered does not match');
			
			}
			
			if($captcha->getError()) {
				$error['ecaptcha'] = $this->translate('You must fill correct captcha');
			}
			
			if(!$request->getPost('terms')) {
				$error['eterms'] = $this->translate('You must agree with the terms');
			}
			
			if(!count($error)) {
				$activationKey = md5(rand(0,10000) . date('HisdmY') . rand(0,10000));
					
				if(!is_null(JO_Cookie::get('referral'))) {
					$referal = Model_Users::getUser(JO_Cookie::get('referral'));
					JO_Cookie::delete('referral');	
				}
				
				Model_Users::register(array(
					'username' => $this->view->username,
					'password' => md5(md5($this->view->password)),
					'email'	=> $this->view->email,
					'firstname'	=> $this->view->firstname,
					'lastname' => $this->view->lastname,
					'activate_key' => $activationKey,
					'referal_id' => isset($referal['user_id']) ? $referal['user_id'] : 0,
					'fb_id' => $request->issetPost('fb_id') ? $request->getPost('fb_id') : 0     		
				));
					
				if($request->getPost('subscribed')) {        			
					Model_Bulletin::add(array(
					'fname' => $this->view->firstname,
					'lname' => $this->view->lastname,
					'email' => $this->view->email
					));
				}
					
					
				$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
				$not_template = Model_Notification::getNotification('registration');			
				$mail = new JO_Mail;
				if($is_mail_smtp) {
					$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
				}
			
				$domain = $request->getDomain();
				$mail->setFrom('noreply@'.$domain);
				$mail->setReturnPath('noreply@'.$domain);
				$mail->setSubject($this->translate('Email activation') . ' ' . JO_Registry::get('store_meta_title'));
				
				if($not_template) {
				
					$title = $not_template['title'];
					$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
					$html = str_replace('{USERNAME}', $this->view->username, $html);
					$html = str_replace('{URL}', WM_Router::create($request->getBaseUrl() . '?controller=users&action=login&command=activate&user='.$this->view->username.'&key='.$activationKey), $html);
				
				} else {
					$link = WM_Router::create($request->getBaseUrl() . '?controller=users&action=login&command=activate&user='. $this->view->username .'&key='.$activationKey);
					$html = nl2br('To activate your profile in '.JO_Registry::get('meta_title').', please click the following link:
							<a href="'. $link .'">'. $link .'</a>');
				}
			
				$mail->setHTML($html);
			
				$result = (int)$mail->send(array($this->view->email), ($is_mail_smtp ? 'smtp' : 'mail'));
				
				if($result) {
					$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=verify'));
				} else {
					JO_Session::set('msg_error', $this->translate('The email was not send. Please try again later'));
				}

			} else {
				JO_Session::set('error', $error);
			}
			$request->setParams('username', $this->view->username);
			JO_Session::set('data', $request->getParams());
			$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=registration'));
		}

		$this->view->recaptcha = $captcha->getCaptcha();
		$this->view->terms = Model_Pages::get(JO_Registry::forceGet('page_terms'));
		
		$this->view->checkAvaibility = WM_Router::create($request->getBaseUrl() . '?controller=users&action=checkAvaibility');
		
		if($this->view->terms) {
			$this->view->terms['text'] = html_entity_decode($this->view->terms['text'], ENT_QUOTES, 'utf-8');
		}
		
		$facebook = new WM_Facebook_Api(array(
   			'appId' => JO_Registry::forceGet('facebook_appid'), 
   			'secret' => JO_Registry::forceGet('facebook_secret') 
  		));
		
		$this->view->facebook_link = $facebook->getLoginUrl(array(
   			'redirect_uri' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=callback_facebook'),
   			'req_perms' => JO_Registry::forceGet('facebook_req_perms'),
   			'scope' => JO_Registry::forceGet('facebook_req_perms')
  		));
		
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
		
	}
	
	//
	public function verifyAction() {
		
		$request = $this->getRequest();
		$this->getLayout()->meta_title = $this->translate('Registration');
    	$this->getLayout()->meta_description = $this->translate('Registration');
		
		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
		
		if($request->issetParam('completed')) {
			
			if(!JO_Session::get('user_id')){
				$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=registration'));
			}
			
			$this->view->complete = true;
			
			$this->view->title = $this->view->translate('Complete');
			$this->view->status = $this->view->translate('Congratulations');
			$this->view->description = $this->view->translate('Your account is now ready to roll! You can now:');
			$this->view->options = array(
				array(
					'name' => $this->view->translate('Deposit Cash and Download Files'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=deposit')
				),
				array(
					'name' => $this->view->translate('Have Your Own Profile Page'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&username='.JO_Session::get('username'))
				),
				array(
					'name' => $this->view->translate('Browse files'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=recent&action=category')
				),
				array(
					'name' => $this->view->translate('Popular Files'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&action=popular')
				)
			);
		} else {
			$this->view->title = $this->view->translate('Check Your Email');
			$this->view->status = $this->view->translate('Check Your Email');
			$this->view->description = $this->view->translate('You\'ll receive an email with a verification link.');
		}
	
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* LOGIN & ACTIVATION */
	public function loginAction() {
		
	    if(JO_Session::get('user_id')) {
			$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=dashboard'));
	    }
		
		if(JO_Session::issetKey('msg_error')) {
			$this->view->check_error = JO_Session::get('msg_error');
			$this->view->user = JO_Session::get('data');
			JO_Session::clear('msg_error');
			JO_Session::clear('data');
		}
		
	    $this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
	
		$this->getLayout()->meta_title = $this->translate('Login');
    	$this->getLayout()->meta_description = $this->translate('Login');
		
	    $request = $this->getRequest();
		
	    if($request->isPost()) {
		    
			$result = Model_Users::checkLogin($request->getPost('username'), $request->getPost('password'));
		
		    if($result) {
		    	$result['following'] = Model_Users::getFollowers($result['user_id']);
				$groups = unserialize($result['groups']);
				if(is_array($groups) and count($groups)>1) {
					unset($result['groups']);
					$fetch_all = Model_Users::getGroups($groups);
					$result['access'] = array();
					if($fetch_all) {
						foreach($fetch_all AS $row) {
							$modules = unserialize($row['rights']);
							if(is_array($modules)) {
								foreach($modules AS $module => $ison) {
									$result['access'][$module] = $module;
								}
							}
						}
					}
				}
	    	
				if(isset($result['access']) && count($result['access'])) {
						$result['is_admin'] = true;
				}
				
    			JO_Session::set($result);
    			
				if(JO_Session::get('redirect')) {
					$this->redirect(JO_Session::get('redirect'));
				} elseif($request->getServer('HTTP_REFERER')) {
					$this->redirect($request->getServer('HTTP_REFERER'));
				} else
					$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=dashboard'));
		    } else {
                JO_Session::set('msg_error', $this->translate('Invalid username or password or the account is not activated.'));
				
				$request->setParams('username', $request->getPost('username'));
				JO_Session::set('data', $request->getParams());
				
				$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=login'));
			}
		}	    

	    
	    if($request->getRequest('command')=='activate' and $request->getRequest('user') and $request->getRequest('key')) {
	            
            if(Model_Users::checkActivation($request->getRequest('user'), $request->getRequest('key'))) {
                 
				 Model_Users::Activate($request->getRequest('user'));
                 $result = Model_Users::getByUsername($request->getRequest('user'));
                 
				 if($result) {
        			$groups = unserialize($result['groups']);
        	    	
					if(is_array($groups) and count($groups)>1) {
        	    		unset($result['groups']);
        	    	    $fetch_all = Model_Users::getGroups($groups);
        	    		$result['access'] = array();
        	    		
						if($fetch_all) {
        	    			foreach($fetch_all AS $row) {
        	    				$modules = unserialize($row['rights']);
        	    				if(is_array($modules)) {
        	    					foreach($modules AS $module => $ison) {
        	    						$result['access'][$module] = $module;
        	    					}
        	    				}
        	    			}
        	    		}
        	    	}
        	    	
        	    	if(isset($result['access']) && count($result['access'])) {
        	    	    	$result['is_admin'] = true;
        	    	}
        
            			JO_Session::set($result);
                 }  		
                
				$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=verify/completed/yes'));
            }
	        
	    }
		
		$facebook = new WM_Facebook_Api(array(
   			'appId' => JO_Registry::forceGet('facebook_appid'), 
   			'secret' => JO_Registry::forceGet('facebook_secret') 
  		));
		
		$this->view->facebook_link = $facebook->getLoginUrl(array(
   			'redirect_uri' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=callback_facebook'),
   			'req_perms' => JO_Registry::forceGet('facebook_req_perms'),
   			'scope' => JO_Registry::forceGet('facebook_req_perms')
  		));
		
	    $this->view->lost_username = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=lost_username');
	    $this->view->reset_password = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=reset_password');
	    $this->view->registration = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=registration');
	    
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* CHECK AVAILABLE USERNAME */
	public function checkAvaibilityAction() {
		$request = $this->getRequest();
		$username = trim($request->getPost('username'));
	    
		if(empty($username)) {
    		die('$("#username_check").removeClass("box-succeess").addClass("box-error").html("<p>'. $this->translate('Please type username') .'</p>");');
    	}
    	
		if(!preg_match('/^[a-zA-Z0-9_]+$/i', $username)) {
			die('$("#username_check").removeClass("box-succeess").addClass("box-error").html("<p>'. $this->translate('The username you have typed is not valid') .'</p>");');
    	}

    	$methodNames  = array();
		if (version_compare(PHP_VERSION, '5.2.6') === -1) {
			$class        = new ReflectionObject($this);
			$classMethods = $class->getMethods();

			foreach ($classMethods as $method) {
				$methodNames[] = $method->getName();
			}
		} else {
			$methodNames = get_class_methods($this);
		}
		
		$temp_methodNames = array(); 
		foreach($methodNames AS $methodName) {
			if(preg_match('/^([\w]{1,})Action$/i', $methodName, $match)) {
				$temp_methodNames[] = $match[1];
			}
		}
		
		$temp_methodNames = array_change_key_case($temp_methodNames, CASE_LOWER);
    	
		if(in_array(strtolower($request->getPost('username')), $temp_methodNames)) {
			die('$("#username_check").removeClass("box-succeess").addClass("box-error").html("<p>'. $this->translate('This username can not be registered') .'</p>");');
		}
		
		if(Model_Users::isExistUsername($request->getPost('username'))) {
			die('$("#username_check").removeClass("box-succeess").addClass("box-error").html("<p>'. $this->translate('There is already registration with that username') .'</p>");');
    	}
    	
		die('$("#username_check").removeClass("box-error").addClass("box-success").html("<p>'. $this->translate('That username is free') .'</p>");');
	}
	
	/* SEND USER NAME */
	public function lost_usernameAction() {
		
		$request = $this->getRequest();
		
	    if(JO_Session::get('user_id')){
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'));
	    }
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		} elseif(JO_Session::get('error')) {
			$this->view->error = JO_Session::get('error');
			JO_Session::clear('error');
		}
		
	    $this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
	    
		$this->getLayout()->meta_title = $this->translate('Lost username');
    	$this->getLayout()->meta_description = $this->translate('Lost username');
		
	    if($request->isPost() && $request->getPost('send')) {
			
			$this->view->email = trim($request->getPost('email'));
			
			if(empty($this->view->email)) {
			
				$this->view->error = $this->translate('You must type your email');
				
			} elseif(!Model_Users::ValidMail($this->view->email)) {
			
				$this->view->error = $this->translate('You must type valid email');
				
			} 
			
			if(!isset($this->view->error)) {
			
				$user = Model_Users::getByEmail($this->view->email);
			
				if($user) {
					$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
								
					$mail = new JO_Mail;
					if($is_mail_smtp) {
						$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
					}
					$domain = $request->getDomain();
					$mail->setFrom('noreply@'.$domain);
					$mail->setReturnPath('noreply@'.$domain);
					$mail->setSubject($this->translate('Lost username') . ' ' . JO_Registry::get('store_meta_title'));

					$html = 'Your username for '.JO_Registry::get('meta_title').' is '.$user['username'];
					$mail->setHTML($html);
					
					$result = (int)$mail->send(array($request->getPost('email')), ($is_mail_smtp ? 'smtp' : 'mail'));
					
					JO_Session::set('msg_success', $this->translate('Your username was send to your email'));
					$this->view->email = '';
				} else {
					JO_Session::set('msg_error', $this->translate('You have entered a non existing email address'));
				}
			} else
				JO_Session::set('error', $this->view->error);
			
			$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=lost_username'));
		}
		
	    $this->view->reset_password = WM_Router::create($request->getBaseUrl() . '?controller=users&action=reset_password');
	    $this->view->new_account = WM_Router::create($request->getBaseUrl() . '?controller=users&action=registration');
	    
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* PASSWORD RESET */
	public function reset_passwordAction() {
	
		$request = $this->getRequest();
	
	    if(JO_Session::get('user_id')){
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'));
	    }
		
		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
	    
		$this->getLayout()->meta_title = $this->translate('Reset password');
    	$this->getLayout()->meta_description = $this->translate('Reset password');
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		} elseif(JO_Session::get('error')) {
			$this->view->error = JO_Session::get('error');
			JO_Session::clear('error');
		}
		
	    if($request->isPost() && $request->getPost('send')) {
			
			$this->view->username = trim($request->getPost('username'));
			$this->view->email = trim($request->getPost('email'));
			
			$this->view->error = array();
			if(empty($this->view->username)) {
				$this->view->error['username'] = $this->translate('You must type your username');
			} elseif(!preg_match('/^[a-zA-Z0-9_]+$/i', $this->view->username)) {
				$this->view->error['username'] = $this->translate('The username you have entered is not valid');
			} 
			
			if(empty($this->view->email)) {
				$this->view->error['email'] = $this->translate('You must type your email');
			} elseif(!Model_Users::ValidMail($this->view->email)) {
				$this->view->error['email'] = $this->translate('You must type valid email');
			} 
			
			if(empty($this->view->error)) {
			
				$user = Model_Users::getByEmail($this->view->email);
			
				if($user) {
					
					$alphabet = array (
						'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'M', 'N', 'P', 'R', 'S', 'T', 'W', 'X', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '2', '3', '4', '5', '6', '7', '8', '9' 
					);
			
					$password = '';
					for($i = 0; $i < 7; $i ++) {
						$random_number = rand ( 0, count ( $alphabet ) - 1 );
						$password .= $alphabet [$random_number];
					}
					
					Model_Users::editPass($user['user_id'], $password);
					
					$is_mail_smtp = JO_Registry::forceGet('config_mail_smtp');
								
					$mail = new JO_Mail;
					if($is_mail_smtp) {
						$mail->setSMTPParams(JO_Registry::forceGet('config_mail_smtp_host'), JO_Registry::forceGet('config_mail_smtp_port'), JO_Registry::forceGet('config_mail_smtp_user'), JO_Registry::forceGet('config_mail_smtp_password'));
					}
					
					$domain = $request->getDomain();
					$mail->setFrom('noreply@'.$domain);
					$mail->setReturnPath('noreply@'.$domain);
					$mail->setSubject($this->translate('Reset password') . ' ' . JO_Registry::get('store_meta_title'));

					$html = 'Your new password for '.$user['username'].' is '.$password;
					$mail->setHTML($html);
					
					$result = (int)$mail->send(array($request->getPost('email')), ($is_mail_smtp ? 'smtp' : 'mail'));
					JO_Session::set('msg_success', $this->translate('Your new password was send to your email'));
					
				} else
					JO_Session::set('msg_error', $this->translate('No match found between your username and the email you have input '));
			} else
				JO_Session::set('error', $this->view->error);
				
			$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=reset_password'));
	    }
	    
		$this->view->lost_username = WM_Router::create($request->getBaseUrl() . '?controller=users&action=lost_username');
	    $this->view->new_account = WM_Router::create($request->getBaseUrl() . '?controller=users&action=registration');
	 	
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* WITHDRAWAL */
	public function withdrawalAction() {
	    
		$request = $this->getRequest();
	    
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }

		$this->view->user = Model_Users::getUser(JO_Session::get('user_id'));
		if(!$this->view->user) {
			return $this->forward('error', 'error404');
		}

        if($request->isPost()) {
            $error = array();
			if(!is_numeric($request->getPost('amount'))) {
				$error['amount'] = $this->translate('You have entered wrong amount');
			} else {
				if($request->getPost('service') == 'swift') {
					if($request->getPost('amount') < 500) {
						$error['amount'] = $this->translate('The amount you have entered is bellow the minimum');
					}
					$maxAmount = $this->view->user['earning'] - 35;			
				} else {
					if($request->getPost('amount') < 50) {
						$error['amount'] = $this->translate('The amount you have entered is bellow the minimum');
					}
					$maxAmount = $this->view->user['earning'];
				}
								
				if($request->getPost('amount') > $maxAmount) {
					$error['amount'] = $this->translate('The amount you have entered is bellow the minimum');
				}
			}
			
			if(!$request->getPost('service')) {
				$error['service'] = $this->translate('You have selected wrong service');
			} else {
				if($request->getPost('service') == 'swift' && (trim($request->getPost('instructions_from_author')) == '')) {
					$error['service2'] = $this->translate('There is an error with your details');
				} 
				
				if($request->getPost('service') != 'swift' && (!$request->getPost('payment_email_address') || !$request->getPost('payment_email_address_confirmation') || trim($request->getPost('payment_email_address')) == '' || $request->getPost('payment_email_address') !== $request->getPost('payment_email_address_confirmation'))) {
					$error['service2'] = $this->translate('There is an error with your payment address');				
				}
			}

			if(count($error) > 0) {
				JO_Session::set('msg_error', $error);
			} else {
				$data = array();
			/*	if(!$request->getPost('taxable_australian_resident')) {
					$data['taxable_australian_resident'] = 'false';
				} else {
					if($request->getPost('hobbyist') == 'true') {
						$data['taxable_australian_resident'] = 'iam';
					} elseif($request->getPost('hobyist') == 'false') {			
						$data['taxable_australian_resident'] = 'iamnot';
					}
				} */
				
				if(!$request->getPost('abn')) {
					$data['abn'] = '';
				}
				if(!$request->getPost('acn')) {
					$data['acn'] = ''; 
				}
				
				$text = '';
				if($request->getPost('service') == 'swift') {
					$text = $request->getPost('instructions_from_author');
				} else {
					$text = $request->getPost('payment_email_address');
				}
				
				if($request->getPost('maximum_at_period_end') == 'true') {
					$data['amount'] = $this->view->user['earning'];
				}
				
				Model_Deposit::addWithdrawal(array(    		
					'user_id'	=>    JO_Session::get('user_id'),
					'amount'	=>    isset($data['amount']) ? $data['amount'] : $request->getPost('amount'),
					'method'	=>    $request->getPost('service'),
					'text'		=>    $text,
					'australian'=>    isset($data['taxable_australian_resident']) ? $data['taxable_australian_resident'] : $this->getRequest()->getPost('taxable_australian_resident'),
					'abn'		=>    $data['abn'],
					'acn'		=>    $data['acn'],	
				));
				
				JO_Session::set('msg_success', $this->translate('Your request has been submitted'));
			} 
			
			$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=withdrawal'));   
        }
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} elseif(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$this->getLayout()->meta_title = $this->translate('Withdrawal');
    	$this->getLayout()->meta_description = $this->translate('Withdrawal');
		
		$this->view->author_header = Helper_Author::authorHeader($this->view->user);
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $this->view->user['username'])
			),
			array(
				'name' => $this->translate('Withdrawal')
			)
		);
		
		$this->view->user['earning'] = WM_Currency::normalize($this->view->user['earning']);
		$this->view->user['total'] = WM_Currency::format($this->view->user['total']);
        $this->view->user['total_f'] = WM_Currency::format($this->view->user['total']);
        $this->view->user['deposit_f'] = WM_Currency::format($this->view->user['deposit']);
        $this->view->user['earning_f'] = WM_Currency::format($this->view->user['earning']);
		
	    $this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
    
	/* by joro */
	/* EARNINGS */
	public function earningsAction() {
		$request = $this->getRequest();
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		$this->getLayout()->meta_title = $this->translate('Earnings');
    	$this->getLayout()->meta_description = $this->translate('Earnings');
		
		$user = $this->view->users = Model_Users::getUser(JO_Session::get('user_id'));
		if(!$user) {
			return $this->forward('error', 'error404');
		}
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $user['username'])
			),
			array(
				'name' => $this->translate('Earnings')
			)
		);
		
		$user['total'] = WM_Currency::format($user['total']);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		
		$this->view->total_balance = $user['total'];
		
		$earnings = Model_Orders::getEarnings($user['user_id']);
		
		$this->view->earnings = array();
		$this->view->total_clicks = $this->view->total_registred = $this->view->total_deposits = $this->view->total_earnings = $this->view->total = 0;
		
		if($earnings) {
			foreach($earnings as $earning) {
				$this->view->total_clicks += $earning['gast'];
				$this->view->total_registred += $earning['register'];
				$this->view->total_deposits += $earning['deposit'];
				$this->view->total_earnings += $earning['sales'];
				$earning['sales'] = WM_Currency::format($earning['sales']);
				$this->view->earnings[] = $earning;
			}
			
			$this->view->total += $this->view->total_earnings;
			$this->view->total_earnings = WM_Currency::format($this->view->total_earnings);
		}
		
		
		$sales = Model_Orders::getSalesByUser($user['user_id']);
		$this->view->sales = array();
		if($sales) {
				
			$month = $sales[0]['month'];
			$total_sales = $total_earnings = $this->view->total_sales_cnt = $this->view->total_earnings_cnt = 0;
			$cnt = count($sales);
			for($i = 0; $i < $cnt; $i++) {

				$total_sales += $sales[$i]['sales'];
				$total_earnings += $sales[$i]['earnings'];
				
				$sales[$i]['earnings'] = WM_Currency::format($sales[$i]['earnings']);
				$this->view->sales[] = $sales[$i];
				
				if(isset($sales[$i+1]['month']) && $month != $sales[$i+1]['month']) {
										
					$this->view->sales[] = array(
						'month' => $sales[$i]['month'],
						'total_sales' => $total_sales,
						'total_earnings' => WM_Currency::format($total_earnings)
					);
					
					$this->view->total_sales_cnt += $total_sales;
					$this->view->total_earnings_cnt += $total_earnings;
					$this->view->total += $total_earnings;
					
					$total_sales = $total_earnings = 0;
					$month = $sales[$i+1]['month'];
				} elseif($i == ($cnt - 1)) {
					$this->view->sales[] = array(
						'month' => $sales[$i]['month'],
						'total_sales' => $total_sales,
						'total_earnings' => WM_Currency::format($total_earnings)
					);
					
					$this->view->total_sales_cnt += $total_sales;
					$this->view->total_earnings_cnt += $total_earnings;
					$this->view->total += $total_earnings;
				}
			}
		}
		
		$this->view->sales = array_reverse($this->view->sales);
		
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->total_earnings_cnt = WM_Currency::format($this->view->total_earnings_cnt);
		$this->view->total = WM_Currency::format($this->view->total);
		
		$this->view->withdrawal_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=withdrawal');
		$this->view->settings_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=edit');
		$this->view->history_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=statement');
		
		$this->view->percent = Model_Percentes::getPercentRow($user);
		$this->view->csv_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=csv_earnings');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	/* RETURN CSV FILE */
	public function csv_earningsAction() {
		$this->setInvokeArg('noViewRenderer', true);
		if(JO_Session::get('user_id')) {
			$data = array();
			$earnings = Model_Orders::getEarnings(JO_Session::get('user_id'));
			$total_f = 0;
			$total_clicks_f = $total_registred_f = $total_deposits_f = $total_earnings_f = $total_f = 0;
			
			if($earnings) {
				
				$data = array(
					array($this->translate('Referrals')),
					array($this->translate('Month'), 
						$this->translate('Clickthoughs'), 
						$this->translate('Registered'), 
						$this->translate('Deposits'),
						$this->translate('Earnings')
						)
				);
				
				foreach($earnings as $earning) {
					$total_clicks_f += $earning['gast'];
					$total_registred_f += $earning['register'];
					$total_deposits_f += $earning['deposit'];
					$total_earnings_f += $earning['sales'];
					$earning['sales'] = WM_Currency::normalize($earning['sales']);
					$data[] = array(
						$earning['date'],
						$earning['gast'],
						$earning['register'],
						$earning['deposit'],
						$earning['sales']
					);
				}
				
				$total_f += $total_earnings_f;
				$total_earnings_f = WM_Currency::normalize($total_earnings_f);
			
				$data[] = array(
					$this->translate('Totals'),
					$total_clicks_f,
					$total_registred_f,
					$total_deposits_f,
					$total_earnings_f
				);
			}
			
			
			$sales = Model_Orders::getSalesByUser(JO_Session::get('user_id'));
			$sales_f = array();
			if($sales) {
					
				$data[] = array($this->translate('Sales'));
				
				$data[] =array(
						$this->translate('Month'),
						$this->translate('Sales'),
						$this->translate('Earnings')
					);	
					
				$month = $sales[0]['month'];
				$total_sales = $total_earnings = $total_sales_cnt_f = $total_earnings_cnt_f = 0;
				$cnt = count($sales);
				for($i = 0; $i < $cnt; $i++) {
	
					$total_sales += $sales[$i]['sales'];
					$total_earnings += $sales[$i]['earnings'];
					
					$sales[$i]['earnings'] = WM_Currency::normalize($sales[$i]['earnings']);
					$sales_f[] = array(
						$sales[$i]['day'],
						$sales[$i]['sales'],
						$sales[$i]['earnings']
					);
					
					if(isset($sales[$i+1]['month']) && $month != $sales[$i+1]['month']) {
											
						$sales_f[] = array(
							$sales[$i]['month'],
							$total_sales,
							WM_Currency::normalize($total_earnings)
						);
						
						$total_sales_cnt_f += $total_sales;
						$total_earnings_cnt_f += $total_earnings;
						$total_f += $total_earnings;
						
						$total_sales = $total_earnings = 0;
						$month = $sales[$i+1]['month'];
					} elseif($i+1 == $cnt) {
						$sales_f[] = array(
							$sales[$i]['month'],
							$total_sales,
							WM_Currency::normalize($total_earnings)
						);
						
						$total_sales_cnt_f += $total_sales;
						$total_earnings_cnt_f += $total_earnings;
						$total_f += $total_earnings;
					}
				}
			}
			
			$sales_f = array_reverse($sales_f);
			
			$data = array_merge($data, $sales_f);
			
			$data[] = array(
				$this->translate('Totals'),
				$total_sales_cnt_f,
				WM_Currency::normalize($total_earnings_cnt_f)
			);
			
			$data[] = array(
				$this->translate('Total Earnings'),
				WM_Currency::normalize($total_f)
			);

			$csv = new Helper_CSVWriter($data);
			$csv->headers(JO_Session::get('firstname') .'_'. JO_Session::get('lastname') .'_'. $this->translate('Earnings'));
			$csv->output();
		}
		
		die();
	}
	
	public function earningsajaxAction() {
	    	$this->setInvokeArg('noViewRenderer', true);
	if($this->getRequest()->getPost('ajax')) {
		
		$month = $this->getRequest()->getRequest('earningsajax');
		$year = $this->getRequest()->getRequest($month);
		
		$text = '';
		$sales = Model_Orders::getAll2(" paid_datetime > '".date('Y-m-d 23:59:59', mktime(0, 0, 0, ($month-1), date('t', mktime(0, 0, 0, ($month-1), 1, $year)), $year))."' AND paid_datetime < '".date('Y-m-d 00:00:00', mktime(0, 0, 0, ($month+1), 1, $year))."' AND paid = 'true' AND type = 'buy' AND owner_id = '".intval(JO_Session::get('user_id'))."' ", "paid_datetime ASC");
		
		if(is_array($sales)) {
			$buff = array();
			foreach($sales as $s) {
				$day = explode(' ', $s['paid_datetime']);
				$day = explode('-', $day[0]);

				if(!isset($buff[$day[2]])) {
					$buff[$day[2]]['sale'] = 1;
					$buff[$day[2]]['earning'] = $s['receive'];
				}
				else {
					$buff[$day[2]]['sale']++;
					$buff[$day[2]]['earning'] += $s['receive'];
				}
			}
			
			foreach($buff as $day=>$r) {
				$text .= '<tr><td>'.$day.'</td><td>'.$r['sale'].' '.$this->translate('Sales').'</td><td>'.WM_Currency::format($r['earning']).'</td></tr>';
			}
		}
		
		die('
			jQuery("#month_'.$month.'_'.$year.'_details").html(\''.$text.'\');
			jQuery("#month_'.$month.'_'.$year.'_show").hide();
			jQuery("#month_'.$month.'_'.$year.'_hide").show(); 
		');
	}
	}
	
	/* STATEMENT */
	public function statementAction() {
	    
		$request = $this->getRequest();
	    if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to access that page!'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
	    
		$this->getLayout()->meta_title = $this->translate('Statement');
    	$this->getLayout()->meta_description = $this->translate('Statement');
	    
		$user = $this->view->users = Model_Users::getUser(JO_Session::get('user_id'));
		if(!$user) {
			return $this->forward('error', 'error404');
		}
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Profile'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&username='. $user['username'])
			),
			array(
				'name' => $this->translate('Statement')
			)
		);
		
		$user['total'] = WM_Currency::format($user['total']);
		$this->view->author_header = Helper_Author::authorHeader($user);
		
		$statements = Model_Orders::getStatement(JO_Session::get('user_id'));
    	if($statements) {
    		$month = $statements[0]['month'];
			
			$cnt = count($statements);
    		for($i = 0; $i < $cnt; $i++) {
    			if($statements[$i]['type'] == 'deposit') {
    				$statements[$i]['price'] = WM_Currency::format($statements[$i]['price']);
					$statements[$i]['details'] = $this->translate('Deposit money');
    			} elseif($statements[$i]['type'] == 'withdraw') {
    				$statements[$i]['price'] = WM_Currency::format(-$statements[$i]['price']);
					$statements[$i]['details'] = $this->translate('Earning money');
    			} elseif($statements[$i]['type'] == 'order') {
    					
    				if($statements[$i]['user_id'] == JO_Session::get('user_id')) {
						$statements[$i]['type'] = $this->translate('buy');
						$statements[$i]['price'] = WM_Currency::format($statements[$i]['price']);
    				} else {
    					$statements[$i]['type'] = $this->translate('sale');
						$statements[$i]['price'] = WM_Currency::format($statements[$i]['receive']);
    				}
					
					$statements[$i]['details'] = $statements[$i]['item_name'];
					
    			} elseif($statements[$i]['type'] == 'referrals') {
    				if($statements[$i]['item_name'] == 1) {
    					$statements[$i]['details'] = $this->translate('Referral / deposit');
    				} else {
    					$statements[$i]['details'] = $this->translate('Referral / sale');
    				}
					
    				$statements[$i]['price'] = WM_Currency::format($statements[$i]['price']);
    			} elseif($statements[$i]['type'] == 'membership') {
    				$statements[$i]['price'] = WM_Currency::format($statements[$i]['price']);
					$statements[$i]['details'] = $this->translate('Membership money');
    			}
				
				$this->view->statements[] = $statements[$i];
				
				if(isset($statements[$i+1]['month']) && $month != $statements[$i+1]['month']) {
					$this->view->statements[] = array(
						'month' => $statements[$i]['month'],
						'total_earnings' => true
					);
					
					$month = $statements[$i+1]['month'];
				} elseif($i == ($cnt - 1)) {
					$this->view->statements[] = array(
						'month' => $statements[$i]['month'],
						'total_earnings' => true
					);
				}
    		}
    	}

        $this->view->statements = array_reverse($this->view->statements); 
		
		$this->view->withdrawal_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=withdrawal');
		$this->view->settings_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=edit');
		$this->view->history_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=statement');
		
		$this->view->total_balance = $user['total'];
		$this->view->total_sales_cnt = JO_Session::get('sales');
		$this->view->percent = Model_Percentes::getPercentRow($user);
		$this->view->csv_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=csv_statement');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}

	/* CSV STATEMENT */
	public function csv_statementAction() {
		$this->setInvokeArg('noViewRenderer', true);
		if(JO_Session::get('user_id')) {
			$data = array();
			
			$statements = Model_Orders::getStatement(JO_Session::get('user_id'));
	    	if($statements) {
	    		$month = $statements[0]['month'];
				
				$data[] = array(
					ucfirst($this->translate('Date')),
					$this->translate('Type'),
					$this->translate('Amount'),
					$this->translate('Details')
				);
				$st = array();
				$cnt = count($statements);
	    		for($i = 0; $i < $cnt; $i++) {
	    			if($statements[$i]['type'] == 'deposit') {
	    				$statements[$i]['price'] = WM_Currency::normalize($statements[$i]['price']);
						$statements[$i]['details'] = $this->translate('Deposit money');
	    			} elseif($statements[$i]['type'] == 'withdraw') {
	    				$statements[$i]['price'] = WM_Currency::normalize(-$statements[$i]['price']);
						$statements[$i]['details'] = $this->translate('Earning money');
	    			} elseif($statements[$i]['type'] == 'order') {
	    					
	    				if($statements[$i]['user_id'] == JO_Session::get('user_id')) {
							$statements[$i]['type'] = $this->translate('buy');
							$statements[$i]['price'] = WM_Currency::normalize($statements[$i]['price']);
	    				} else {
	    					$statements[$i]['type'] = $this->translate('sale');
							$statements[$i]['price'] = WM_Currency::normalize($statements[$i]['receive']);
	    				}
						
						$statements[$i]['details'] = $statements[$i]['item_name'];
						
	    			} elseif($statements[$i]['type'] == 'referrals') {
	    				if($statements[$i]['item_name'] == 1) {
	    					$statements[$i]['details'] = $this->translate('Referral / deposit');
	    				} else {
	    					$statements[$i]['details'] = $this->translate('Referral / sale');
	    				}
						
	    				$statements[$i]['price'] = WM_Currency::normalize($statements[$i]['price']);
	    			} elseif($statements[$i]['type'] == 'membership') {
	    				$statements[$i]['price'] = WM_Currency::format($statements[$i]['price']);
						$statements[$i]['details'] = $this->translate('Membership money');
	    			}
					
					$st[] = array(
						$statements[$i]['day'],
						$statements[$i]['type'],
						$statements[$i]['price'],
						$statements[$i]['details']
					);
					
					if(isset($statements[$i+1]['month']) && $month != $statements[$i+1]['month']) {
						$st[] = array(
							'month' => $statements[$i]['month']
						);
						
						$month = $statements[$i+1]['month'];
					} elseif($i == ($cnt - 1)) {
						$st[] = array(
							'month' => $statements[$i]['month']
						);
					}
	    		}
	    	}
	
	        $st = array_reverse($st); 
			$data = array_merge($data, $st);
			
			$csv = new Helper_CSVWriter($data);
			$csv->headers(JO_Session::get('firstname') .'_'. JO_Session::get('lastname') .'_'. $this->translate('Statement'));
			$csv->output();
		}
		die();	
	}
	
	/* BULLETIN */
	public function bulletinAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
	    if($request->isPost() ) {
	    	$error = array();
			
    	    if(!Model_Users::ValidMail($request->getPost('bulletin_email'))) {
    			JO_Session::set('msg_error_bulletin', $this->translate('You must fill valid email'));
				JO_Session::set('data_bulletin', $request->getParams());
    		} elseif(!Model_Bulletin::checkMail($request->getPost('bulletin_email'))) {
	            Model_Bulletin::add(array(
	                'fname'	 => $request->getPost('bulletin_fname'),
	                'lname'	 => $request->getPost('bulletin_lname'),
	                'email'	 => $request->getPost('bulletin_email')
	            ));
	            JO_Session::set('msg_success_bulletin', $this->translate('You have been successfully added into our newsletter'));
	        } else {
	            JO_Session::set('msg_error_bulletin', $this->translate('The email is already in our newsletter'));
				JO_Session::set('data_bulletin', $request->getParams());
	        }
	    }
		$this->redirect($request->getServer('HTTP_REFERER').'#bulletin');	
	}

	public function callback_facebookAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		$facebook = new WM_Facebook_Api(array(
   			'appId' => JO_Registry::forceGet('facebook_appid'),
   			'secret' => JO_Registry::forceGet('facebook_secret')  
  		));
		
		$fbData = $facebook->api('/me');
		
		if($fbData['verified'] == 'true' && isset($fbData['id'])) {
			$user = Model_Users::getFBuser($fbData['id']);
			
			if($user) {
				$user['following'] = Model_Users::getFollowers($user['user_id']);
				$groups = unserialize($user['groups']);
				if(is_array($groups) and count($groups)>1) {
					unset($user['groups']);
					$fetch_all = Model_Users::getGroups($groups);
					$user['access'] = array();
					if($fetch_all) {
						foreach($fetch_all AS $row) {
							$modules = unserialize($row['rights']);
							if(is_array($modules)) {
								foreach($modules AS $module => $ison) {
									$result['access'][$module] = $module;
								}
							}
						}
					}
				}
	    	
				if(isset($user['access']) && count($user['access'])) {
						$user['is_admin'] = true;
				}
				
    			JO_Session::set($user);
				$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=dashboard'));
			} else {
				$userData = array(
					'fb_id' => $fbData['id'],
					'firstname' => $fbData['first_name'],
					'lastname' => $fbData['last_name'],
					'email' => $fbData['email'],
					'email_confirm' => $fbData['email'],
					'username' => $fbData['username']
				);
				
				JO_Session::set('fb_data', $userData);
				$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=registration'));
			}
		} else {
			JO_Session::set('msg_error', $this->translate('Facebook login error. Please try again later.'));
			$this->redirect(WM_Router::create($request->getBaseUrl() .'?controller=users&action=login'));
		}
	}

	public function daily_summary_mailAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		
		$users = Model_Users::getDailySummary();
		
		if($users) {
			
			$domain = $request->getDomain();
			$mail = new JO_Mail;
			if(JO_Registry::get('mail_smtp')) {
				$mail->setSMTPParams(JO_Registry::forceGet('mail_smtp_host'), JO_Registry::forceGet('mail_smtp_port'), JO_Registry::forceGet('mail_smtp_user'), JO_Registry::forceGet('mail_smtp_password'));
			}
			$mail->setFrom('no-reply@'.$domain);
			
			$not_template = Model_Notification::getNotification('daily_summary');
			
			foreach($users as $user) {
				$user['to_date'] = WM_Date::format($user['to_date'], 'Y-mm-dd H:i');
				$user['from_date'] = WM_Date::format($user['from_date'], 'Y-mm-dd H:i');
				
				if($not_template) {
					$title = $not_template['title'];
					$html = html_entity_decode($not_template['template'], ENT_QUOTES, 'utf-8');
					$html = str_replace('{USERNAME}', $user['username'], $html);
					$html = str_replace('{SALES}', $user['cnt'], $html);
					$html = str_replace('{SUM}', WM_Currency::format($user['daily_sum']), $html);
					$html = str_replace('{TO_DATE}', $user['to_date'], $html);
					$html = str_replace('{FROM_DATE}', $user['from_date'], $html);
				} else {
					$title = "[".$domain."] " . $this->translate('Daily summary');
					$html = nl2br($this->translate('Daily summary').'
					
					 from '. $user['from_date'] .' to '. $user['to_date'] .'		
					 Sales: '. $user['cnt'] .', Receive money: '. WM_Currency::format($user['daily_sum']) .'
					 
					 ===============================================================================================
					 
					 '. JO_Registry::forceGet('meta_title') .'
					');
				}
				
				$mail->setSubject($title);
				
				$mail->setHTML($html);
				
				$mail->send(array($user['email']), (JO_Registry::get('mail_smtp') ? 'smtp' : 'mail'));
			}
		}
	}
}

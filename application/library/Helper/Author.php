<?php

class Helper_Author {
	
	public static function authorHeader($user) {
		$view = JO_View::getInstance();
		$model_images = new Helper_Images();
		$request = JO_Request::getInstance();
		
		$view->my_profile = JO_Session::get('username') == $user['username'] ? true : false;
		
		$cnts = Model_Items::getPortfolioCounts($user['user_id']);
		$view->portfolio_link = WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($user['username']));
		$action = $request->getAction();
		$controller = $request->getController();
		$list_type = $request->getParam('list_type');
		if(JO_Session::get('user_id')) {
			if($view->my_profile) {				
				if(in_array($action, array('dashboard', 'edit', 'earnings', 'statement', 'withdrawal', 'deposit', 'membership'))) {
					$view->stats = array(
						array(
							'name' => $view->translate('Dashboard'),
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=dashboard'),
							'is_selected' => $action == 'dashboard' ? true : false
						),
						array(
							'name' => $view->translate('Settings'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=edit'),
							'is_selected' => $action == 'edit' ? true : false
						),
						array(
							'name' => $view->translate('Earnings'),
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=earnings'),
							'is_selected' => $action == 'earnings' ? true : false
						),
						array(
							'name' => $view->translate('Statement'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=statement'),
							'is_selected' => $action == 'statement' ? true : false
						),
						array(
							'name' => $view->translate('Withdrawal'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=withdrawal'),
							'is_selected' => $action == 'withdrawal' ? true : false
						),
						array(
							'name' => $view->translate('Deposit'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=deposit'),
							'is_selected' => $action == 'deposit' ? true : false
						)
					);
					
					$membership = Model_Membership::getAll();
					if($membership) {
						$view->stats[] = array(
							'name' => $view->translate('Membership'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=membership'),
							'is_selected' => $action == 'membership' ? true : false
						);
					}
					
					$user['edit_link'] = array(
						'name' => $view->translate('Portfolio'),
						'href' => $view->portfolio_link
					);
				} else {
					$view->portfolio = true;
					$view->stats = array(
						array(
							'name' => $cnts[4]['total'] .' '. $view->translate('Items'),
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&username='.str_replace('&', '-', $user['username'])),
							'is_selected' => $action == 'index' && $controller == 'users' ? true : false
						),
						array(
							'name' => $cnts[0]['total'] .' '. $view->translate('Collections'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=collections&username='.str_replace('&', '-', $user['username'])),
							'is_selected' => in_array($action, array('collections', 'view_collection')) ? true : false
						),
						array(
							'name' => $cnts[1]['total'] .' '. $view->translate('Downloads'),
							'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads&username='.str_replace('&', '-', $user['username'])),
							'is_selected' => $action == 'downloads' ? true : false
						),
						array(
							'name' => $cnts[2]['total'] .' '. $view->translate('Following'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=following&username='. str_replace('&', '-', $user['username'])),
							'is_selected' => $action == 'following' && $list_type != 'followers' ? true : false
						),
						array(
							'name' => $cnts[3]['total'] .' '. $view->translate('Followers'),
							'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=followers&username='. str_replace('&', '-', $user['username'])),
							'is_selected' => $list_type == 'followers' ? true : false
						)
					);
					
					$user['edit_link'] = array(
						'name' => $view->translate('My account'),
						'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=dashboard')
					);
				}
			} else {
				$following = JO_Session::get('following');
				if($following) {
					$is_followed = JO_Array::multi_array_search($following, 'username', $user['username']);
				}
				if(!empty($is_followed)) {
					$user['edit_link'] = array(
						'name' => $view->translate('Unfollow'),
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=unfollow&username='. str_replace('&', '-', $user['username']))
					);
				} else {
					$user['edit_link'] = array(
						'name' => $view->translate('Follow'),
						'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=follow&username='. str_replace('&', '-', $user['username']))
					);
				}
			}
		}

		if(!isset($view->stats)) {
			$view->portfolio = true;
			$view->stats = array(
					array(
						'name' => $cnts[4]['total'] .' '. $view->translate('Items'),
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&username='. str_replace('&', '-', $user['username'])),
						'is_selected' => $action == 'index' && $controller == 'users' ? true : false
					),
					array(
						'name' => $cnts[0]['total'] .' '. $view->translate('Collections'),
						'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=collections&username='. str_replace('&', '-', $user['username'])),
						'is_selected' => in_array($action, array('collections', 'view_collection')) ? true : false
					),
					array(
						'name' => $cnts[2]['total'] .' '. $view->translate('Following'),
						'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=following&username='.str_replace('&', '-', $user['username'])),
						'is_selected' => $action == 'following' && $list_type != 'followers' ? true : false
					),
					array(
						'name' => $cnts[3]['total'] .' '. $view->translate('Followers'),
						'href' => WM_Router::create($request->getBaseUrl() .'?controller=users&action=followers&username='. str_replace('&', '-', $user['username'])),
						'is_selected' => $list_type == 'followers' ? true : false
					)
				);
		}
		
		if($user['country_id']) {
			$country = Model_Countries::get($user['country_id']);
			$user['country_name'] = (!empty($user['live_city']) ? $user['live_city'] .', ' : '') . $country['name'];
		}
		
		$user['register_datetime'] = JO_Date::getInstance($user['register_datetime'], 'MM yy')->getDate();
		
		$time_parts = explode(' ', $user['register_datetime']);
		if(mb_strlen($time_parts[0], 'UTF-8') > 5) {
			$user['register_datetime'] = mb_substr($time_parts[0], 0, 3, 'UTF-8') .'. '. $time_parts[1];
		}
		
		if($user['user_site']) {
			$pos = mb_stripos($user['user_site'], 'http://', 'UTF-8');
			
			if($pos === 0)
				$user['user_site'] = str_replace('http://', '', mb_strtolower($user['user_site'], 'UTF-8'));
		
			if(strlen($user['user_site']) > 22) {
				$pos = mb_stripos($user['user_site'], '/', 15, 'UTF-8');
				if($pos !== false) {
					$user['user_site'] = str_replace('/', '/&#8203;', $user['user_site']);
				}
			}
		}
		
		$view->badges = self::userBadges($user);
		
		if($user['social']) {
			$user['social'] = unserialize($user['social']);
		}
		
		if($user['avatar']) {
			$user['avatar'] = $model_images->resize($user['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
		} else 
			$user['avatar'] = 'data/themes/images/noavatar.png';
		
		$view->user = $user;
		
		return $view->renderByModule('single_user/author_header', 'users', 'themes');
	}
	
	public static function getSettingsBox($tool = null) {
		$view = JO_View::getInstance();
		$request = JO_Request::getInstance();
		
		if(is_null($tool)) $tool = 'personal';
		
		$view->options = array(
			array(
				'name' => $view->translate('Avatar and Personal Information'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'),
				'is_selected' => $tool == 'personal' ? true : false
			),
			array(
				'name' => $view->translate('Change your password'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=change_password'),
				'is_selected' => $tool == 'change_password' ? true : false
			),
			array(
				'name' => $view->translate('Exclusive Author'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=exclusive_author'),
				'is_selected' => $tool == 'exclusive_author' ? true : false
			),
			array(
				'name' => $view->translate('Sale License'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=sale_license'),
				'is_selected' => $tool == 'sale_license' ? true : false
			),
			array(
				'name' => $view->translate('Social Media profiles'),
				'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit&tool=social'),
				'is_selected' => $tool == 'social' ? true : false
			)
		);
		
		return $view->renderByModule('single_user/settings_box', 'users', 'themes');
	}
	
	public static function getTopAuthor($user, $list_type = 'top')
	{
		$view = JO_View::getInstance();
		$model_images = new Helper_Images();
		$request = JO_Request::getInstance();
		
		$view->list_type = $list_type;
		$view->badges = self::userBadges($user);
		
		if($user['avatar']) {
			$user['avatar'] = $model_images->resize($user['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
		} else 
			$user['avatar'] = 'data/themes/images/noavatar.png';
		
		$user['portfolio_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='. str_replace('&', '-', $user['username']));
		
		$log_user = JO_Session::get('user_id');
		if($log_user && $log_user != $user['user_id']) {
			$following = JO_Session::get('following');
			if($following) {
				$is_followed = JO_Array::multi_array_search($following, 'username', $user['username']);
			}
			if(!empty($is_followed)) {
				$user['follow_name'] = $view->translate('Unfollow');
				$user['follow_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=unfollow&username='. str_replace('&', '-', $user['username']));
			} else {
				$user['follow_name'] = $view->translate('Follow');	
				$user['follow_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=follow&username='. str_replace('&', '-', $user['username']));
			}
		}
		
		$user['followers_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=followers&username='. str_replace('&', '-', $user['username']));
		$user['following_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=following&username='. str_replace('&', '-', $user['username']));
		
		$view->user = $user;
		
		return $view->renderByModule('single_user/top_author', 'users', 'themes');
	}
	
	public static function returnFollowing($follower, $owner, $followers = false)
	{
		$view = JO_View::getInstance();
		$model_images = new Helper_Images();
		$request = JO_Request::getInstance();
		
		$list_type = $request->getParam('list_type');
		
		$user = Model_Users::getByUsername($follower['username']);
		
		$view->badges = self::userBadges($user);
		
		if($user['avatar']) {
			$user['avatar'] = $model_images->resize($user['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
		} else 
			$user['avatar'] = 'data/themes/images/noavatar.png';
		
		$user['portfolio_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&username='. str_replace('&', '-',$user['username']));
		
		$log_user = JO_Session::get('user_id');
		if($log_user) {
			$following = JO_Array::multi_array_search(JO_Session::get('following'), 'follow_id', $user['user_id']);
			if($following) {
				$user['follow_href_name'] = $view->translate('Unfollow');
				$user['follow_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=unfollow&username='. str_replace('&', '-', $user['username']));
			} elseif($log_user != $user['user_id']) {
				$user['follow_href_name'] = $view->translate('Follow');
				$user['follow_href'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=follow&username='. str_replace('&', '-', $user['username']));
			}
		}
		
		$view->user = $user;
		
		$user_items = Model_Items::getByUser($user['user_id'], 0, 3, 'rand()');
		
		if(empty($user_items)) {
			$user_items = array(
				array(
					'no_items' => true,
					'thumbnail' => 'data/themes/images/missing-item.png',
					'module' => 'themes'
				)
			);
		}
		
		$view->user_items = array();
		if($user_items) {			
			foreach($user_items as $item) {
				if(isset($item['demo_url']))
					$item['demo_url'] = WM_Router::create($request->getBaseUrl() . '?controller=demo&href='. $item['demo_url']);
				
				$view->user_items[] = Helper_Items::returnViewIndex($item);
			}
		}
		
		return $view->renderByModule('single_user/following', 'users', 'themes');
	}
	
	public static function userBadges($user) {
		
		$user_badges = array();
		if(!$user) {
			return $user_badges;
		}
	
		$badges_data = Model_Badges::getAllFront();
		
		$other_badges = isset($user['badges']) ? array_map('trim', explode(',', $user['badges'])) : array();
		
		if(isset($user['exclusive_author']) && $user['exclusive_author'] == 'true' && isset($badges_data['system']['is_exclusive_author'])) {
			if($badges_data['system']['is_exclusive_author']['photo'] && file_exists("uploads/badges/" . $badges_data['system']['is_exclusive_author']['photo'])) {
				$user_badges[] = array(
					'name' => $badges_data['system']['is_exclusive_author']['name'],
					'photo' => 'uploads/badges/' . $badges_data['system']['is_exclusive_author']['photo']
				);
			}
		}
		
		if(isset($user['featured_author']) && $user['featured_author'] == 'true' && isset($badges_data['system']['has_been_featured'])) {
			if($badges_data['system']['has_been_featured']['photo'] && file_exists("uploads/badges/" . $badges_data['system']['has_been_featured']['photo'])) {
				$user_badges[] = array(
					'name' => $badges_data['system']['has_been_featured']['name'],
					'photo' => 'uploads/badges/' . $badges_data['system']['has_been_featured']['photo']
				);
			}
		}
		
		if(isset($user['statuses']['freefile']) && $user['statuses']['freefile'] && isset($badges_data['system']['has_free_file_month'])) {
			if($badges_data['system']['has_free_file_month']['photo'] && file_exists("uploads/badges/" . $badges_data['system']['has_free_file_month']['photo'])) {
				$user_badges[] = array(
					'name' => $badges_data['system']['has_free_file_month']['name'],
					'photo' => 'uploads/badges/' . $badges_data['system']['has_free_file_month']['photo']
				);
			}
		}
		
		if(isset($user['statuses']['featured']) && $user['statuses']['featured'] && isset($badges_data['system']['has_had_item_featured'])) {
			if($badges_data['system']['has_free_file_month']['photo'] && file_exists("uploads/badges/" . $badges_data['system']['has_had_item_featured']['photo'])) {
				$user_badges[] = array(
					'name' => $badges_data['system']['has_had_item_featured']['name'],
					'photo' => 'uploads/badges/' . $badges_data['system']['has_had_item_featured']['photo']
				);
			}
		}
		
		if(isset($user['buy']) && $user['buy'] && isset($badges_data['buyers']) && is_array($badges_data['buyers'])) {
			foreach($badges_data['buyers'] AS $k => $v) {
				list($from, $to) = explode('-', $k);
				if($from <= $user['buy'] && $to >= $user['buy']) {
					if($v['photo'] && file_exists("uploads/badges/" . $v['photo'])) {
						$user_badges[] = array(
							'name' => $v['name'],
							'photo' => 'uploads/badges/' . $v['photo']
						);
					}
					break;
				}
			}
		}
		
		if(isset($user['sold']) && $user['sold'] && isset($badges_data['authors']) && is_array($badges_data['authors'])) {
			foreach($badges_data['authors'] AS $k => $v) {
				list($from, $to) = explode('-', $k);
				if($from <= $user['sold'] && $to >= $user['sold']) {
					if($v['photo'] && file_exists( "uploads/badges/" . $v['photo'])) {
						$user_badges[] = array(
							'name' => $v['name'],
							'photo' => 'uploads/badges/' . $v['photo']
						);
					}
					break;
				}
			}
		}
		
		if(isset($user['referals']) && $user['referals'] && isset($badges_data['referrals']) && is_array($badges_data['referrals'])) {
			foreach($badges_data['referrals'] AS $k => $v) {
				list($from, $to) = explode('-', $k);
				if($from <= $user['referals'] && $to >= $user['referals']) {
					if($v['photo'] && file_exists("uploads/badges/" . $v['photo'])) {
						$user_badges[] = array(
							'name' => $v['name'],
							'photo' => 'uploads/badges/' . $v['photo']
						);
					}
					break;
				}
			}
		}
		
		if(isset($badges_data['other']) && is_array($badges_data['other'])) {
			foreach($badges_data['other'] AS $k => $b) {
				if(in_array($k, $other_badges) && $b['photo'] && file_exists("uploads/badges/" . $b['photo'])) {
					$user_badges[] = array(
						'name' => $b['name'],
						'photo' => 'uploads/badges/' . $b['photo']
					);
				}
			}
		}
		
		if(isset($user['country_id']) && $user['country_id']) {
			$country = Model_Countries::get($user['country_id']);
			if($country) {
				$user['country'] = $country;
			}
		}
		
		if(isset($user['country']['photo']) && $user['country']['photo'] && file_exists("uploads/countries/" . $user['country']['photo'])) {
			$user_badges[] = array(
				'name' => $user['country']['name'],
				'photo' => 'uploads/countries/' . $user['country']['photo']
			);
		} elseif(isset($badges_data['system']['location_global_community']) && $badges_data['system']['location_global_community']['photo'] && file_exists("uploads/badges/" . $badges_data['system']['location_global_community']['photo'])) {
			$user_badges[] = array(
				'name' => $badges_data['system']['location_global_community']['name'],
				'photo' => 'uploads/badges/' . $badges_data['system']['location_global_community']['photo']
			);
		}
		
		return $user_badges;
	}
}
?>
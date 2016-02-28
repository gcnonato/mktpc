<?php

class LayoutController extends JO_Action {	
	
	public function header_partAction() {

		$request = $this->getRequest();
		
		if($this->getLayout()->meta_title) {
			$this->getLayout()->placeholder('title', ($this->getLayout()->meta_title . ' - ' . JO_Registry::get('meta_title')));
		} else {
			$this->getLayout()->placeholder('title', JO_Registry::get('meta_title'));
		}
  
		if($this->getLayout()->meta_description) {
			$this->getLayout()->placeholder('description', $this->getLayout()->meta_description);
		} else {
			$this->getLayout()->placeholder('description', JO_Registry::get('meta_description'));
		}
  
		if($this->getLayout()->meta_keywords) {
			$this->getLayout()->placeholder('keywords', $this->getLayout()->meta_keywords);
		} else {
			$this->getLayout()->placeholder('keywords', JO_Registry::get('meta_keywords'));
		}
		
		$this->getLayout()->placeholder('google_analytics', html_entity_decode(JO_Registry::get('google_analytics'), ENT_QUOTES, 'utf-8'));
		
		if(JO_Registry::get('site_logo') && file_exists(BASE_PATH .'/uploads/'.JO_Registry::get('site_logo'))) {
		    $this->view->site_logo = JO_Registry::get('site_logo'); 
		}
		
		$this->view->home_action = $request->getBaseUrl();
		$this->getCategories();
	    
		$this->view->menuPages = Model_Pages::getPagesMenu();
		
		if(isset($this->view->menuPages[0])) {
        	foreach($this->view->menuPages[0] AS $k=>$v) {
        		$this->view->menuPages[0][$k]['href'] = $v['url'] ? $v['url'] : WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='.$v['id'] .'&name='. WM_Router::clearName($v['name']));
        		if(isset($this->view->menuPages[$v['id']])) {
        			foreach($this->view->menuPages[$v['id']] AS $r => $t) {
        				$this->view->menuPages[$v['id']][$r]['href'] = $t['url'] ? $t['url'] : WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='.$t['id'] .'&name='.WM_Router::clearName($t['name']));
        			}
        		}
        	}
        }
		
		if(JO_Session::get('msg_success'))  {
		    $this->view->msg_success = JO_Session::get('msg_success');
		    JO_Session::clear('msg_success');
		}
		if(JO_Session::get('msg_error')) {
		    $this->view->msg_error = JO_Session::get('msg_error');
		    JO_Session::clear('msg_error');
		}
		
		$this->view->recent_href = WM_Router::create($request->getBaseUrl() . '?controller=categories&action=recent');
        $this->view->top_sellers_href = WM_Router::create($request->getBaseUrl() . '?controller=categories&action=popular');
        $this->view->feature_href = WM_Router::create($request->getBaseUrl() . '?controller=categories&action=featured');
        $this->view->collections_href = WM_Router::create($request->getBaseUrl() . '?controller=collections');
        $this->view->top_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=top');
		$this->view->all_authors_href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=authors');
		$this->view->search = WM_Router::create($request->getBaseUrl() . '?controller=search');
        
		////////// CURRENCY
		//autoupdate currency if set
		if(JO_Registry::get('config_currency_auto_update')) {
			WM_Currency::updateCurrencies();
		
			$currencies = WM_Currency::getCurrencies();
			$this->view->currencies = array();
			if($currencies) {
				foreach($currencies AS $currency) {
					$currency['active'] = $currency['code'] == WM_Currency::getCurrencyCode();
					$this->view->currencies[] = $currency;
				}
			}
		}
		///////// LANGUAGES
		$languages = WM_Locale::getLanguages();
		if($languages && count($languages) > 1) {
			$this->view->languages = array();
			$config_language_id = JO_Registry::get('config_language_id');
			
			foreach($languages AS $language) {
				if($language['language_id'] == $config_language_id) {
					$this->view->current_language = array(
						'name' => $language['name'],
						'id' => $language['language_id'],
						'image' => 'data/themes/images/flags/'. $language['image']
					);
				} else {
					$this->view->languages[] = array(
						'name' => $language['name'],
						'id' => $language['language_id'],
						'image' => 'data/themes/images/flags/'. $language['image']
					);
				}
			}
		}
		
		$username = JO_Session::get('username');
		if($username) {
			$this->view->user = Model_Users::getUser(JO_Session::get('user_id'));
			
			$this->view->user['total'] = WM_Currency::format($this->view->user['total']);
			$ind = 0;
			$this->view->options = array(
				array(
					'name' => $this->view->translate('Portfolio'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&username='. WM_Router::clearName($username)),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('My Account'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=edit'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Downloads'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=downloads'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Collections'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=collections&username='. WM_Router::clearName($username)),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Deposit'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=deposit'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Dashboard'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=dashboard'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Upload'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=upload'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Earnings'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=earnings'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Statement'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=statement'),
					'css' => 'icon-'.(++$ind)
				),
				array(
					'name' => $this->view->translate('Withdrawal'),
					'href' => WM_Router::create($request->getBaseUrl() . '?controller=users&action=withdrawal'),
					'css' => 'icon-'.(++$ind)
				)
			);
			
			$this->view->user_logout = WM_Router::create($request->getBaseUrl() . '?controller=users&action=logout');
		} else {
			$this->view->user_registration = WM_Router::create($request->getBaseUrl() . '?controller=users&action=registration');
			$this->view->user_login = WM_Router::create($request->getBaseUrl() . '?controller=users&action=login');
			$this->view->user_lost_username = WM_Router::create($request->getBaseUrl() . '?controller=users&action=lost_username');
			$this->view->user_reset_password = WM_Router::create($request->getBaseUrl() . '?controller=users&action=reset_password');
		}
		
		$threads = Model_Forum::getAll();
		if($threads) {
			$this->view->forum_link = WM_Router::create($request->getBaseUrl() .'?controller=forum');
			$this->view->threads = array();
			foreach($threads as $thread) {
				$this->view->threads[] = array(
					'name' => $thread['name'],
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'.$thread['id'].'&name='.WM_Router::clearName($thread['name']))
				);
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
		
		$this->view->children = array();
		$this->view->children['extensions_top'] = 'extensions/top';
	//	$this->view->children['extensions_topmiddle'] = 'extensions/topmiddle';
	}	
	
    public function footer_partAction() {
	
		$request = $this->getRequest();
        $this->getCategories();
        
        $this->view->footerPages = Model_Pages::getPagesFooter();
        if(isset($this->view->footerPages[0])) {
        	foreach($this->view->footerPages[0] AS $k=>$v) {
        		$this->view->footerPages[0][$k]['href'] = $v['url'] ? $v['url'] : WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='.$v['id'] .'&name='. WM_Router::clearName($v['name']));
        	}
        }
        
        $about = Model_Pages::get(JO_Registry::forceGet('page_about'));
        if($about) {
        	$this->view->about = array(
        		'name' => $about['name'],
        		'href' => WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='.$about['id'] .'&name='.WM_Router::clearName($about['name']))
        	);
        }
		
        $this->view->socials = Model_Items::getSocials(true);
        $this->view->rss = WM_Router::create($request->getBaseUrl() . '?controller=rss');
        $this->view->contacts = WM_Router::create($request->getBaseUrl() . '?controller=contacts');
        
		//BULLETIN
		$this->view->bulletin_link = WM_Router::create($request->getBaseUrl() . '?controller=users&action=bulletin');
		if(JO_Session::get('msg_success_bulletin')) {
			$this->view->msg_success_bulletin = JO_Session::get('msg_success_bulletin');
			JO_Session::clear('msg_success_bulletin');
		}

		if(JO_Session::get('msg_error_bulletin')) {
			$this->view->msg_error_bulletin = JO_Session::get('msg_error_bulletin');
			$this->view->data_bulletin = JO_Session::get('data_bulletin');
			JO_Session::clear('msg_error_bulletin');
		}
		
		$this->view->children = array();
	//	$this->view->children['extensions_bottommiddle'] = 'extensions/bottommiddle';
		$this->view->children['extensions_bottom'] = 'extensions/bottom';
	}
	
	private function getCategories() {
		$request = $this->getRequest();
		
		$mainCategories = Model_Categories::getMain();
        if($mainCategories) {
			$i = 0;
			$this->view->mainCategories = array();
        	foreach($mainCategories AS $k => $v) {
            	if($i < 6) {
					$this->view->mainCategories[$i] = array(
						'name' => $v['name'],
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='.$v['id'] .'&name='. WM_Router::clearName($v['name'])),
						'sub_cats' => Model_Categories::getCategories($v['id'])
					);
					
					$v['sub_cats'] = Model_Categories::getCategories($v['id']);
					
					if($v['sub_cats']) {
						foreach($v['sub_cats'] as $sk => $sv) {
							$this->view->mainCategories[$i]['sub_cats'][$sk]['href'] = WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='.$sv['id'] .'&name='.WM_Router::clearName($sv['name']));
						}
					}
				} else {
					if($i == 6) {
						$this->view->mainCategories[6] = array(
							'name' => $this->view->translate('More'),
							'href' => '#',
							'no_link' => 1
						);
					}	

					$this->view->mainCategories[6]['sub_cats'][] = array(
						'name' => $v['name'],
						'href' => WM_Router::create($request->getBaseUrl() . '?controller=categories&category_id='.$v['id'] .'&name='. WM_Router::clearName($v['name']))
					);
					
				}
				
				$i++;
            }
        }
	}

}

?>
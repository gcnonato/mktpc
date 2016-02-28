<?php

class Extensions_SinglesignonController extends JO_Action {

	public function indexAction() {
		$this->noViewRenderer(true);
		
		$request = $this->getRequest();
		
		if($request->getQuery('openId') && strlen($request->getQuery('openId')) == 32 && $request->getServer('HTTP_REFERER')) {
			$referer = str_replace('www.', '', JO_Validate::validateHost($request->getServer('HTTP_REFERER')));
			$domain = $request->getDomain();
			
			if($referer && $referer != $domain && self::allowledReferal($referer)) {
				//check user
				if($result = Model_Extensions_Singlesignon::checkUser($referer, $domain, $request->getQuery('openId'))) {
					if($result && $result['status'] == 'activate') {
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
				} elseif(!JO_Session::get('user_id')) {
					$url = 'http://'.$referer.'/public/extensions_singlesignon/getUserData/?openId=' . $request->getQuery('openId') . '&referer=' . $referer . '&domain=' . $domain;
					
					if (ini_get('allow_url_fopen')) {
						$response = file_get_contents($url);
					} elseif(function_exists('curl_init')) {
						$response = $this->file_get_contents_curl($url);
					}
					
					if($response) {
						$response = JO_Json::decode(JO_Encrypt_Md5::decrypt($response, $domain), true);
						
						//register user
						if($response && is_array($response)) {
							if($result = Model_Extensions_Singlesignon::createUser($response)) {
								$groups = self::mb_unserialize($result['groups']);
						    	if(is_array($groups) and count($groups)>1) {
						    		unset($result['groups']);
						    	    $fetch_all = Model_Users::getGroups($groups);
						    		$result['access'] = array();
						    		if($fetch_all) {
						    			foreach($fetch_all AS $row) {
						    				$modules = self::mb_unserialize($row['rights']);
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
						}
					}
				}
			}
			$this->redirect($request->getBaseUrl());
		}
		
		$this->view->single_sign_on = array();
    	$single_sign_on = WM_Store::getSettingsPairs(array(
    		'filter_group' => 'single_sign_on'
    	));
    	
    	$model_images = new Model_Images();
    	
    	$sort_order = array();
    	foreach($single_sign_on AS $row => $data) {
    		$sort_order[$row] = isset($data['sort_order']) ? $data['sort_order'] : 0;
    		if($data['site_logo'] && file_exists(BASE_PATH . '/uploads/' . $data['site_logo'])) {
    			$data['preview'] = 'uploads/' . $data['site_logo'];
    		} else {
    			$data['preview'] = '';
    		}
    		
    		$data['preview'] = $model_images->resize($data['site_logo'], 140, 30);
    		
    		$data['href'] = 'http://' . $data['url'] .'/public/';
    		if(JO_Registry::get('singlesignon_enable_login') && JO_Session::get('user_id')) {
    			$data['href'] .= '?openId=' . md5($request->getDomain() . $data['url'] . JO_Session::get('username') . JO_Session::get('email'));
    		}
    		
    		$this->view->single_sign_on[$row] = $data;
    	}

    	array_multisort($sort_order, SORT_ASC, $this->view->single_sign_on);

    	if(JO_Registry::get('singlesignon_enable_dropdown') && count($this->view->single_sign_on)) {
    		
            $this->view->ext_css = WM_Router::create($request->getBaseUrl() . '?controller=cache&extension=singlesignon&action=css&setFile=css.css');
            $this->view->ext_js = WM_Router::create($request->getBaseUrl() . '?controller=cache&extension=singlesignon&action=js&setFile=js.js');
    		
    		$this->getLayout()->placeholder('singlesignon', $this->view->render('index','extensions_singlesignon'));
    	}
    	
	}
	
	public function getUserDataAction() {
		$request = $this->getRequest();
		$this->noViewRenderer(true);
		$json = array();
		if($request->getQuery('openId') && strlen($request->getQuery('openId')) == 32) {
			$referer = $request->getQuery('referer');
			$domain = $request->getQuery('domain');
			
			if(!$referer) {
				return;
			} elseif(!$domain) {
				return;
			} elseif($referer == $domain) {
				return;
			} elseif(!self::allowledReferal($domain)) {
				return;
			}
			
			$result = Model_Extensions_Singlesignon::checkUser($referer, $domain, $request->getQuery('openId'));

			if($result && $result['status'] == 'activate') {
				
				$json['username'] = $result['username'];
				$json['password'] = $result['password'];
				$json['email'] = $result['email'];
				$json['firstname'] = $result['firstname'];
				$json['lastname'] = $result['lastname'];
				if($result['avatar'] && file_exists(BASE_PATH . '/uploads/' . $result['avatar'])) {
					$json['avatar'] = $request->getBaseUrl() . '/uploads/' . $result['avatar'];
				} else {
					$json['avatar'] = '';
				}
				if($result['homeimage'] && file_exists(BASE_PATH . '/uploads/' . $result['homeimage'])) {
					$json['homeimage'] = $request->getBaseUrl() . '/uploads/' . $result['homeimage'];
				} else {
					$json['homeimage'] = '';
				}
				$json['firmname'] = $result['firmname'];
				$json['profile_title'] = $result['profile_title'];
				$json['profile_desc'] = $result['profile_desc'];
				$json['register_datetime'] = $result['register_datetime'];
			}
		}
		
		$this->getResponse()->appendBody(JO_Encrypt_Md5::encrypt(JO_Json::encode($json), $domain));
		
	}
	
	private function file_get_contents_curl($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if(!ini_get('safe_mode') && !ini_get('open_basedir')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);	
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$Rec_Data = curl_exec($ch);
		curl_close($ch);
		return $Rec_Data;	
	}
  	
	private function mb_unserialize($serial_str) {
		$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str );
		return unserialize($out);
	}
	
	private function allowledReferal($referal) {
		$single_sign_on = WM_Store::getSettingsPairs(array(
    		'filter_group' => 'single_sign_on'
    	));
    	
    	foreach($single_sign_on AS $data) {
    		if(!$data['url']) continue;
    		if(!$referal) continue;
    		if($data['url'] == $referal) {
    			return true;
    		} elseif(str_replace('www.', '', $data['url']) == $referal) {
    			return true;
    		} elseif($data['url'] == str_replace('www.', '', $referal)) {
    			return true;
    		} elseif(str_replace('www.', '', $data['url']) == str_replace('www.', '', $referal)) {
    			return true;
    		}
    	}
    	return false;
	}
	
}

?>
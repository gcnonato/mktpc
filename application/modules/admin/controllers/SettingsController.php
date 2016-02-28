<?php

class SettingsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Settings'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'in_menu' => true,
			'permision_key' => 'system',
			'sort_order' => 80100
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
		
		if($request->isPost()) {
			$avatar_width = $request->getParam('user_avatar_width');
			if($avatar_width > 120)
				$request->getParam('user_avatar_width', 120);
			
			$avatar_height = $request->getParam('user_avatar_height');
			if($avatar_height > 120)
				$request->getParam('user_avatar_height', 120);
			
			Model_Settings::updateAll($request->getParams());
			$config = $request->getPost('config');
			if(isset($config['config_currency_auto_update']) && $config['config_currency_auto_update'] == 1) {
    			WM_Currency::updateCurrencies($config['config_currency']);
    		}
			$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/settings/');
		}
		
		$this->view->templates = $this->getTemplates();
		
		$this->view->modules = WM_Modules::getList(array('update', 'install', 'admin'));
		
		$image_setings_get = WM_Modules::getConfig();
		$image_setings = array();
		$watermark_setings = array();
		$domains_setings = array();
		
		foreach($image_setings_get AS $module_get => $ims) {
			$image_setings[$module_get] = isset($ims['images']) && is_array($ims['images']) ? $ims['images'] : array();
			$watermark_setings[$module_get] = isset($ims['watermark']) && is_array($ims['watermark']) ? $ims['watermark'] : array();
			$domains_setings[$module_get] = isset($ims['domain']) && is_array($ims['domain']) ? $ims['domain'] : array();
		}
		
		
		$config = $request->getPost('config');
		$images = $request->getPost('images');
		$pages = $request->getPost('pages');
		
		$store_config = Model_Settings::getSettingsPairs();
		
		$this->view->currencies = Model_Currency::getCurrencies();
		
		$this->view->pages = Model_Pages::getPagesFromParent(0);
		
		$this->view->languages = array();
    	$languages = Model_Language::getLanguages();
    	if($languages) {
    		$this->view->languages = $languages;
    	}
		
		//////////////////////////////////////// GENERAL ////////////////////////////////////////
		if(isset($config['referal_sum'])) {
    		$this->view->referal_sum = $config['referal_sum'];
    	} elseif(isset($store_config['referal_sum'])) {
    		$this->view->referal_sum = $store_config['referal_sum'];
    	} else {
    		$this->view->referal_sum = 0;
    	}
	
		if(isset($config['referal_percent'])) {
    		$this->view->referal_percent = str_replace('%', '', $config['referal_percent']).'%';
    	} elseif(isset($store_config['referal_percent'])) {
    		$this->view->referal_percent = str_replace('%', '', $store_config['referal_percent']).'%';
    	} else {
    		$this->view->referal_percent = 0;
    	}
	
		if(isset($config['prepaid_price_discount'])) {
    		$this->view->prepaid_price_discount = $config['prepaid_price_discount'];
    	} elseif(isset($store_config['prepaid_price_discount'])) {
    		$this->view->prepaid_price_discount = $store_config['prepaid_price_discount'];
    	} else {
    		$this->view->prepaid_price_discount = 0;
    	}
	
		if(isset($config['extended_price'])) {
    		$this->view->extended_price = $config['extended_price'];
    	} elseif(isset($store_config['extended_price'])) {
    		$this->view->extended_price = $store_config['extended_price'];
    	} else {
    		$this->view->extended_price = 0;
    	}
	
		if(isset($config['no_exclusive_author_percent'])) {
    		$this->view->no_exclusive_author_percent = $config['no_exclusive_author_percent'];
    	} elseif(isset($store_config['no_exclusive_author_percent'])) {
    		$this->view->no_exclusive_author_percent = $store_config['no_exclusive_author_percent'];
    	} else {
    		$this->view->no_exclusive_author_percent = 0;
    	}
	
		if(isset($config['exclusive_author_percent'])) {
    		$this->view->exclusive_author_percent = $config['exclusive_author_percent'];
    	} elseif(isset($store_config['exclusive_author_percent'])) {
    		$this->view->exclusive_author_percent = $store_config['exclusive_author_percent'];
    	} else {
    		$this->view->exclusive_author_percent = 0;
    	}
    	
	    if(isset($config['facebook_appid'])) {
    		$this->view->facebook_appid = $config['facebook_appid'];
    	} elseif(isset($store_config['facebook_appid'])) {
    		$this->view->facebook_appid = $store_config['facebook_appid'];
    	} else {
    		$this->view->facebook_appid = '';
    	}
    	
		if(isset($config['facebook_secret'])) {
    		$this->view->facebook_secret = $config['facebook_secret'];
    	} elseif(isset($store_config['facebook_secret'])) {
    		$this->view->facebook_secret = $store_config['facebook_secret'];
    	} else {
    		$this->view->facebook_secret = '';
    	}
		
	    if(isset($config['recaptcha_public_key'])) {
    		$this->view->recaptcha_public_key = $config['recaptcha_public_key'];
    	} elseif(isset($store_config['recaptcha_public_key'])) {
    		$this->view->recaptcha_public_key = $store_config['recaptcha_public_key'];
    	} else {
    		$this->view->recaptcha_public_key = 0;
    	}
    	
	    if(isset($config['recaptcha_private_key'])) {
    		$this->view->recaptcha_private_key = $config['recaptcha_private_key'];
    	} elseif(isset($store_config['recaptcha_private_key'])) {
    		$this->view->recaptcha_private_key = $store_config['recaptcha_private_key'];
    	} else {
    		$this->view->recaptcha_private_key = 0;
    	}
    	
	    if(isset($config['google_translate_key'])) {
    		$this->view->google_translate_key = $config['google_translate_key'];
    	} elseif(isset($store_config['google_translate_key'])) {
    		$this->view->google_translate_key = $store_config['google_translate_key'];
    	} else {
    		$this->view->google_translate_key = '';
    	}
    	
    	//////////////////////////////////////// SEO ////////////////////////////////////////
		if(isset($config['meta_title'])) {
    		$this->view->meta_title = $config['meta_title'];
    	} elseif(isset($store_config['meta_title'])) {
    		$this->view->meta_title = $store_config['meta_title'];
    	}
	
    	if(isset($config['meta_keywords'])) {
    		$this->view->meta_keywords = $config['meta_keywords'];
    	} elseif(isset($store_config['meta_keywords'])) {
    		$this->view->meta_keywords = $store_config['meta_keywords'];
    	}
	
    	if(isset($config['meta_description'])) {
    		$this->view->meta_description = $config['meta_description'];
    	} elseif(isset($store_config['meta_description'])) {
    		$this->view->meta_description = $store_config['meta_description'];
    	}
	
    	if(isset($config['google_analytics'])) {
    		$this->view->google_analytics = $config['google_analytics'];
    	} elseif(isset($store_config['google_analytics'])) {
    		$this->view->google_analytics = $store_config['google_analytics'];
    	}
    	
    	//////////////////////////////////////// Options ////////////////////////////////////////
		if(isset($config['admin_limit'])) {
    		$this->view->admin_limit = $config['admin_limit'];
    	} elseif(isset($store_config['admin_limit'])) { 
    		$this->view->admin_limit = $store_config['admin_limit'];
    	} else {
    		$this->view->admin_limit = 15;
    	}
    	
		if(isset($config['front_limit'])) {
    		$this->view->front_limit = $config['front_limit'];
    	} elseif(isset($store_config['front_limit'])) { 
    		$this->view->front_limit = $store_config['front_limit'];
    	} else {
    		$this->view->front_limit = 10;
    	}
    	
		if(isset($config['template'])) {
    		$this->view->template = $config['template'];
    	} elseif(isset($store_config['template'])) { 
    		$this->view->template = $store_config['template'];
    	}
    	
		if(isset($config['default_module'])) {
    		$this->view->default_module = $config['default_module'];
    	} elseif(isset($store_config['default_module'])) { 
    		$this->view->default_module = $store_config['default_module'];
    	}
    	
		if(isset($config['currency_position'])) {
    		$this->view->currency_position = $config['currency_position'];
    	} elseif(isset($store_config['currency_position'])) { 
    		$this->view->currency_position = $store_config['currency_position'];
    	} else {
    		$this->view->currency_position = 'left';
    	}
    	
		if(isset($config['currency_decimal_places'])) {
    		$this->view->currency_decimal_places = $config['currency_decimal_places'];
    	} elseif(isset($store_config['currency_decimal_places'])) { 
    		$this->view->currency_decimal_places = $store_config['currency_decimal_places'];
    	} else {
    		$this->view->currency_decimal_places = 2;
    	}
    	
		if(isset($config['currency_decimal_point'])) {
    		$this->view->currency_decimal_point = $config['currency_decimal_point'];
    	} elseif(isset($store_config['currency_decimal_point'])) { 
    		$this->view->currency_decimal_point = $store_config['currency_decimal_point'];
    	} else {
    		$this->view->currency_decimal_point = '.';
    	}
    	
		if(isset($config['currency_thousand_point'])) {
    		$this->view->currency_thousand_point = $config['currency_thousand_point'];
    	} elseif(isset($store_config['currency_thousand_point'])) { 
    		$this->view->currency_thousand_point = $store_config['currency_thousand_point'];
    	} else {
    		$this->view->currency_thousand_point = ',';
    	}
    	
		if(isset($config['config_language_id'])) {
    		$this->view->config_language_id = $config['config_language_id'];
    	} elseif(isset($store_config['config_language_id'])) { 
    		$this->view->config_language_id = $store_config['config_language_id'];
    	}
    	
    	//////////////////////////////////////// Contacts ////////////////////////////////////////
		if(isset($config['admin_mail'])) {
    		$this->view->admin_mail = $config['admin_mail'];
    	} elseif(isset($store_config['admin_mail'])) { 
    		$this->view->admin_mail = $store_config['admin_mail'];
    	}
    	
		if(isset($config['report_mail'])) {
    		$this->view->report_mail = $config['report_mail'];
    	} elseif(isset($store_config['report_mail'])) {
    		$this->view->report_mail = $store_config['report_mail'];
    	}
    	
		if(isset($config['mail_smtp'])) {
    		$this->view->mail_smtp = $config['mail_smtp'];
    	} elseif(isset($store_config['mail_smtp'])) {
    		$this->view->mail_smtp = $store_config['mail_smtp'];
    	} else {
    		$this->view->mail_smtp = 0;
    	}
    	
		if(isset($config['mail_smtp_host'])) {
    		$this->view->mail_smtp_host = $config['mail_smtp_host'];
    	} elseif(isset($store_config['mail_smtp_host'])) {
    		$this->view->mail_smtp_host = $store_config['mail_smtp_host'];
    	}
    	
		if(isset($config['mail_smtp_port'])) {
    		$this->view->mail_smtp_port = $config['mail_smtp_port'];
    	} elseif(isset($store_config['mail_smtp_port'])) {
    		$this->view->mail_smtp_port = $store_config['mail_smtp_port'];
    	}
    	
		if(isset($config['mail_smtp_user'])) {
    		$this->view->mail_smtp_user = $config['mail_smtp_user'];
    	} elseif(isset($store_config['mail_smtp_user'])) {
    		$this->view->mail_smtp_user = $store_config['mail_smtp_user'];
    	}
    	
		if(isset($config['mail_smtp_password'])) {
    		$this->view->mail_smtp_password = $config['mail_smtp_password'];
    	} elseif(isset($store_config['mail_smtp_password'])) {
    		$this->view->mail_smtp_password = $store_config['mail_smtp_password'];
    	}
    	
    	//////////////////////////////////////// Images ////////////////////////////////////////
    	/////// logo
		$image_model = new Model_Images;
    	
    	if(isset($images['site_logo']) && $images['site_logo']) {
    		$this->view->site_logo = $images['site_logo'];
    	} elseif(isset($store_config['site_logo']) && $store_config['site_logo']) {
    		$this->view->site_logo = $store_config['site_logo'];
    	} else {
    		$this->view->site_logo = '';
    	}
    	
    	if($this->view->site_logo) {
    		$this->view->preview_logo = $image_model->resize($this->view->site_logo, 100, 100);
    	} else {
    		$this->view->preview_logo = $image_model->resize('/logo.png', 100, 100);
    	}
    	
    	if(!$this->view->preview_logo) {
    		$this->view->preview_logo = $image_model->resize('/logo.png', 100, 100);
    	}
    	
    	////// no image
		if(isset($images['no_image']) && $images['no_image']) {
    		$this->view->no_image = $images['no_image'];
    	} elseif(isset($store_config['no_image']) && $store_config['no_image']) {
    		$this->view->no_image = $store_config['no_image'];
    	} else {
    		$this->view->no_image = '/no_image.png';
    	}
    	
    	if($this->view->no_image) {
    		$this->view->preview_no_image = $image_model->resize($this->view->no_image, 100, 100);
    	} else {
    		$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	if(!$this->view->preview_no_image) {
    		$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	$this->view->preview = $image_model->resize('/logo.png', 100, 100);
//    	$this->view->preview_no_image = $image_model->resize('/no_image.png', 100, 100);
    	
	
    	/////items
    	
    	$this->view->generate_item_image_form = array();
    	
    	foreach($image_setings AS $mod => $data) {
    		foreach($data AS $imagetype => $value) {
    			$this->view->generate_item_image_form[$mod][] = array(
    				'name' => $value['name'],
    				'info' => isset($value['info']) ? $value['info'] : '',
    				'key_width' => $mod . '_items_'.$imagetype.'_width',
    				'key_height' => $mod . '_items_'.$imagetype.'_height'
    			);
    			
    			
	    		if(isset($config[$mod . '_items_'.$imagetype.'_width'])) {
		    		$this->view->{$mod . '_items_'.$imagetype.'_width'} = $config[$mod . '_items_'.$imagetype.'_width'];
		    	} elseif(isset($store_config[$mod . '_items_'.$imagetype.'_width'])) {
		    		$this->view->{$mod . '_items_'.$imagetype.'_width'} = $store_config[$mod . '_items_'.$imagetype.'_width'];
		    	} else {
		    		$this->view->{$mod . '_items_'.$imagetype.'_width'} = isset($value['width']) ? $value['width'] : '';
		    	}
		    	
	    		if(isset($config[$mod . '_items_'.$imagetype.'_height'])) {
		    		$this->view->{$mod . '_items_'.$imagetype.'_height'} = $config[$mod . '_items_'.$imagetype.'_height'];
		    	} elseif(isset($store_config[$mod . '_items_'.$imagetype.'_height'])) {
		    		$this->view->{$mod . '_items_'.$imagetype.'_height'} = $store_config[$mod . '_items_'.$imagetype.'_height'];
		    	} else {
		    		$this->view->{$mod . '_items_'.$imagetype.'_height'} = isset($value['height']) ? $value['height'] : '';
		    	}
    			
    		}
    	}
    	
    	//// watermark
    	$this->view->generate_watermark_form = array();
    	foreach($watermark_setings AS $mod => $data) {
    		foreach($data AS $imagetype => $value) { 
    			$this->view->generate_watermark_form[$mod] = array(
    				'name' => $value['name'],
    				'info' => $value['info'],
    				'key' => $mod . '_watermark_' . $imagetype
    			);
    			
    			if(isset($images[$mod . '_watermark_' . $imagetype]) && $images[$mod . '_watermark_' . $imagetype]) {
		    		$this->view->{$mod . '_watermark_' . $imagetype} = $images[$mod . '_watermark_' . $imagetype];
		    	} elseif(isset($store_config[$mod . '_watermark_' . $imagetype]) && $store_config[$mod . '_watermark_' . $imagetype]) {
		    		$this->view->{$mod . '_watermark_' . $imagetype} = $store_config[$mod . '_watermark_' . $imagetype];
		    	} else {
		    		if(file_exists(BASE_PATH . '/uploads' . $value['image'])) {
		    			$this->view->{$mod . '_watermark_' . $imagetype} = $value['image'];
		    		} else {
		    			$this->view->{$mod . '_watermark_' . $imagetype} = '';
		    		}
		    	}
		    	
		    	if($this->view->{$mod . '_watermark_' . $imagetype}) {
		    		$this->view->{'preview_' . $mod . '_watermark_' . $imagetype} = $image_model->resize($this->view->{$mod . '_watermark_' . $imagetype}, 100, 100);
		    	} else {
		    		$this->view->{'preview_' . $mod . '_watermark_' . $imagetype} = $image_model->resize($value['image'], 100, 100);
		    	}
		    	
		    	if(!$this->view->{'preview_' . $mod . '_watermark_' . $imagetype}) {
		    		$this->view->{'preview_' . $mod . '_watermark_' . $imagetype} = $image_model->resize($this->view->no_image, 100, 100);
		    	}
		    	
		    	$this->view->{'default_' . $mod . '_watermark_' . $imagetype} = $image_model->resize($value['image'], 100, 100);
    			
    		}
    	}

    	
			
    	////////////////user
    	
		if(isset($config['user_avatar_width'])) {
    		$this->view->user_avatar_width = $config['user_avatar_width'];
    	} elseif(isset($store_config['user_avatar_width'])) {
    		$this->view->user_avatar_width = $store_config['user_avatar_width'];
    	} else {
    		$this->view->user_avatar_width = 80;
    	}
    	
		if(isset($config['user_avatar_height'])) {
    		$this->view->user_avatar_height = $config['user_avatar_height'];
    	} elseif(isset($store_config['user_avatar_height'])) {
    		$this->view->user_avatar_height = $store_config['user_avatar_height'];
    	} else {
    		$this->view->user_avatar_height = 80;
    	}
    	
		if(isset($config['user_avatar2_width'])) {
    		$this->view->user_avatar2_width = $config['user_avatar2_width'];
    	} elseif(isset($store_config['user_avatar2_width'])) {
    		$this->view->user_avatar2_width = $store_config['user_avatar2_width'];
    	} else {
    		$this->view->user_avatar2_width = 40;
    	}
    	
		if(isset($config['user_avatar2_height'])) {
    		$this->view->user_avatar2_height = $config['user_avatar2_height'];
    	} elseif(isset($store_config['user_avatar2_height'])) {
    		$this->view->user_avatar2_height = $store_config['user_avatar2_height'];
    	} else {
    		$this->view->user_avatar2_height = 40;
    	}
    	
		if(isset($config['user_profile_photo_width'])) {
    		$this->view->user_profile_photo_width = $config['user_profile_photo_width'];
    	} elseif(isset($store_config['user_profile_photo_width'])) {
    		$this->view->user_profile_photo_width = $store_config['user_profile_photo_width'];
    	} else {
    		$this->view->user_profile_photo_width = 590;
    	}
    	
		if(isset($config['user_profile_photo_height'])) {
    		$this->view->user_profile_photo_height = $config['user_profile_photo_height'];
    	} elseif(isset($store_config['user_profile_photo_height'])) {
    		$this->view->user_profile_photo_height = $store_config['user_profile_photo_height'];
    	} else {
    		$this->view->user_profile_photo_height = 242;
    	}
    	
		if(isset($config['user_public_collection_width'])) {
    		$this->view->user_public_collection_width = $config['user_public_collection_width'];
    	} elseif(isset($store_config['user_public_collection_width'])) {
    		$this->view->user_public_collection_width = $store_config['user_public_collection_width'];
    	} else {
    		$this->view->user_public_collection_width = 260;
    	}
    	
		if(isset($config['user_public_collection_height'])) {
    		$this->view->user_public_collection_height = $config['user_public_collection_height'];
    	} elseif(isset($store_config['user_public_collection_height'])) {
    		$this->view->user_public_collection_height = $store_config['user_public_collection_height'];
    	} else {
    		$this->view->user_public_collection_height = 140;
    	}
    	
		//////////////////////////////////////// PAGES ////////////////////////////////////////
		if(isset($pages['page_about'])) {
    		$this->view->page_about = $pages['page_about'];
    	} elseif(isset($store_config['page_about'])) {
    		$this->view->page_about = $store_config['page_about'];
    	} else {
    		$this->view->page_about = 0;
    	}
    	
		if(isset($pages['page_upload_item'])) {
    		$this->view->page_upload_item = $pages['page_upload_item'];
    	} elseif(isset($store_config['page_upload_item'])) {
    		$this->view->page_upload_item = $store_config['page_upload_item'];
    	} else {
    		$this->view->page_upload_item = 0;
    	}
    	
		if(isset($pages['page_terms'])) {
    		$this->view->page_terms = $pages['page_terms'];
    	} elseif(isset($store_config['page_terms'])) {
    		$this->view->page_terms = $store_config['page_terms'];
    	} else {
    		$this->view->page_terms = 0;
    	}
    	
		if(isset($pages['page_regular_licence'])) {
			$this->view->page_regular_licence = $pages['page_regular_licence'];
		} elseif(isset($store_config['page_regular_licence'])) {
			$this->view->page_regular_licence = $store_config['page_regular_licence'];
		} else {
			$this->view->page_regular_licence = 0;
		}
		
		if(isset($pages['page_forum_rules'])) {
			$this->view->page_forum_rules = $pages['page_forum_rules'];
		} elseif(isset($store_config['page_forum_rules'])) {
			$this->view->page_forum_rules = $store_config['page_forum_rules'];
		} else {
			$this->view->page_forum_rules = 0;
		}
		
		if(isset($pages['page_affiliate_program'])) {
			$this->view->page_affiliate_program = $pages['page_affiliate_program'];
		} elseif(isset($store_config['page_affiliate_program'])) {
			$this->view->page_affiliate_program = $store_config['page_affiliate_program'];
		} else {
			$this->view->page_affiliate_program = 0;
		}
		
    	/////////////////////////////// CURRENCY ///////////////////////
    	$this->view->currencies = array();
    	$currencies = Model_Currency::getCurrencies(array('status' => 1)); //WM_Currency::getCurrencies();
    	if($currencies) {
    		$this->view->currencies = $currencies;
    	}
    	
    	if(isset($config['config_currency'])) {
    		$this->view->config_currency = $config['config_currency'];
    	} elseif(isset($store_config['config_currency'])) { 
    		$this->view->config_currency = $store_config['config_currency'];
    	}
    	
    	if(isset($config['config_currency_auto_update'])) {
    		$this->view->config_currency_auto_update = $config['config_currency_auto_update'];
    	} elseif(isset($store_config['config_currency_auto_update'])) {
    		$this->view->config_currency_auto_update = $store_config['config_currency_auto_update'];
    	} else {
    		$this->view->config_currency_auto_update = 1;
    	}
    	
    	/////////////////////////////// DOMAINS ///////////////////////
    	$this->view->show_domain_tab = count($domains_setings) > 1;
		$this->view->generate_domains_setings_form = array();
    	foreach($domains_setings AS $mod => $data) { 
    		
    		$this->view->generate_domains_setings_form[$mod] = array(
    			'key' => $mod
    		);
    		
    		if(isset($config['default_domain'][$mod])) {
    			$this->view->generate_domains_setings_form[$mod]['value'] = $config['default_domain'][$mod];
    		} elseif(isset($store_config['default_domain'][$mod])) {
    			$this->view->generate_domains_setings_form[$mod]['value'] = $store_config['default_domain'][$mod];
    		} elseif(isset($data['default'])) {
    			$this->view->generate_domains_setings_form[$mod]['value'] = $data['default'];
    		} else {
    			$this->view->generate_domains_setings_form[$mod]['value'] = '';
    		}
    		
    	}
    	
    	$this->view->show_domain_tab = false;
		
	}
    
    private function getTemplates() {
    	$template_path = JO_Layout::getInstance()->getTemplatePath();
    	$list = glob($template_path . '*');
    	$templates = array();
    	
    	if($list) {
    		foreach($list AS $dir) {
    			$templates[] = basename($dir);
    		}
    	}
    	return $templates;
    }

}

?>
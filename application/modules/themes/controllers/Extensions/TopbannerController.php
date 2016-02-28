<?php

class Extensions_TopbannerController extends JO_Action {

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$banner_info = Model_Extensions_Topbanner::getRandom();
		
		$this->view->has_banner = false;
		
		if($banner_info && ($request->getCookie('clbanner') != true || $banner_info['close'] == 'false')) {
			$this->view->has_banner = true;	
			Model_Extensions_Topbanner::updateViews($banner_info['id']);
			$banner_info['html'] = html_entity_decode($banner_info['html'], ENT_QUOTES, 'utf-8');
			if($banner_info['url'] && strpos($banner_info['url'], 'http') === false) {
				$banner_info['url'] = 'http://' . $banner_info['url'];
			} 
			$this->view->banner_info = $banner_info;
			$this->view->banner_info['ajax_url'] = WM_Router::create($request->getBaseUrl() . '?controller=extensions_topbanner&action=click&id=' . $banner_info['id']);
		
			if(!trim(strip_tags($banner_info['html'])) && $banner_info['photo'] && !file_exists(BASE_PATH . '/uploads/' . $banner_info['photo'])) {
				$this->view->has_banner = false;	
			}
			
//			if($banner_info['photo'] && file_exists(BASE_PATH . '/uploads/' . $banner_info['photo'])) {
//				$model_images = new Model_Images();
//				$this->view->banner_info['photo'] = $model_images->resize($banner_info['photo'], $banner_info['width'], $banner_info['height'], true);
//			}
			
		}
	}
	
	public function clickAction() {	
		$request = $this->getRequest();
		if($request->isXmlHttpRequest() && $request->getQuery('id')) {
			Model_Extensions_Topbanner::updateClicks($request->getQuery('id'));
		} else {
			$this->redirect($request->getBaseUrl());
		}
	}
	
}

?>
<?php

class PagesController extends JO_Action {
	
    public function indexAction() {
		
		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
	
	    $pageID = $this->getRequest()->getRequest('page_id');
    	
		$this->view->page = Model_Pages::get($pageID);
		
		if($this->view->page['sub_of'] == 0) {
		
			$headPage = $this->view->page;
			$subPages = Model_Pages::getSubPages($pageID);
	//		if(!$subPages) {
			
	//			$this->view->usersCount = Model_Users::countUsers();
	//			$this->view->itemsCount = Model_Items::countItems();
	//		}
		} else {
			$headPage = Model_Pages::get($this->view->page['sub_of']);
		
			$subPages = Model_Pages::getSubPages($this->view->page['sub_of']);
			
			$crumbs = array();
			
			$parentPages = Model_Pages::getPageParents($this->view->page['sub_of']);
			
			if($parentPages) {
				$cnt = count($parentPages);
				for($i = 0; $i < $cnt; $i++) {
					$crumbs[$i+1] = array(
						'name' => $parentPages[$i]['name'],
						'href' => WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&page_id='. $parentPages[$i]['id'] .'&name='. WM_Router::clearName($parentPages[$i]['name']))
					);
				}
			}
		}
		
		$this->view->crumbs = array(
			array(
				'name' => $this->view->translate('Home'),
				'href' => $this->getRequest()->getBaseUrl()
			)
		);
		
		if(isset($crumbs)) {
			$this->view->crumbs += $crumbs;
		}
		
		if($subPages) {
		
			$this->view->subPages[] = array(
				'name' => $headPage['name'],
				'href' => WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&page_id='. $headPage['id'] .'&name='. WM_Router::clearName($headPage['name'])),
				'is_selected' => ($headPage['id'] == $pageID ? true : false)
			);
			
			foreach($subPages as $page) {
				$this->view->subPages[] = array(
					'name' => $page['name'],
					'href' => WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=pages&page_id='. $page['id'] .'&name='. WM_Router::clearName($page['name'])),
					'is_selected' => ($page['id'] == $pageID ? true : false)
				);
			}
		}
			
		if(!$this->view->page) {
    		$this->forward('error', 'error404');
    	}
    	
    	$model_images = new Model_Images();
    	
		$this->view->page['text'] = html_entity_decode($this->view->page['text'], ENT_QUOTES, 'utf-8');
		$this->view->page['text'] = $model_images->fixEditorText($this->view->page['text']);
    	   
    	$this->getLayout()->meta_title = $this->view->page['meta_title'];
		$this->getLayout()->meta_description = $this->view->page['meta_description'];
		$this->getLayout()->meta_keywords = $this->view->page['meta_keywords'];
    	
		$this->view->quiz_link = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=quiz');
		
		$this->view->children = array();
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
}
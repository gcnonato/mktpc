<?php

class CacheController extends JO_Action {
	
	public function indexAction() {
		$this->forward('error', 'error404');
	}
	
	public function imagesAction() {
        $file = $this->getRequest()->getParam('file');
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file || strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderImage('images/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
    }
    
    public function jsAction() {
        $file = $this->getRequest()->getParam('file'); 
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file ||  strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderJs('js/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
    }
    
    public function cssAction() {
        $file = $this->getRequest()->getParam('file');
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file ||  strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderCss('css/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
		
    }
	
}

?>
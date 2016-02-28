<?php

class ExtensionsController extends JO_Action {

	public function topAction() {
		$getall = Model_Extensions::getAll();
		$this->view->extensions = array();
		$sort_order = array();
		$this->view->children = array();
		foreach($getall AS $row => $extension) {
			if(JO_Registry::forceGet($extension . '_position') == 'top' && (int)JO_Registry::forceGet($extension . '_status') == 1) {
				$sort_order[$row] = (int)JO_Registry::forceGet($extension . '_sort_order'); 
				$this->view->children['extensions_' . $extension] = 'extensions_' . $extension;
				$this->view->extensions[] = 'extensions_' . $extension;
			}
		}
		array_multisort($sort_order, SORT_ASC, $this->view->extensions);
	}
	
	public function topmiddleAction() {
		$getall = Model_Extensions::getAll();
		$this->view->extensions = array();
		$sort_order = array();
		$this->view->children = array();
		foreach($getall AS $row => $extension) {
			if(JO_Registry::forceGet($extension . '_position') == 'topmiddle' && (int)JO_Registry::forceGet($extension . '_status') == 1) {
				$sort_order[$row] = (int)JO_Registry::forceGet($extension . '_sort_order'); 
				$this->view->children['extensions_' . $extension] = 'extensions_' . $extension;
				$this->view->extensions[] = 'extensions_' . $extension;
			}
		}
		array_multisort($sort_order, SORT_ASC, $this->view->extensions);
	}
	
	public function bottommiddleAction() {
		$getall = Model_Extensions::getAll();
		$this->view->extensions = array();
		$sort_order = array();
		$this->view->children = array();
		foreach($getall AS $row => $extension) {
			if(JO_Registry::forceGet($extension . '_position') == 'bottommiddle' && (int)JO_Registry::forceGet($extension . '_status') == 1) {
				$sort_order[$row] = (int)JO_Registry::forceGet($extension . '_sort_order'); 
				$this->view->children['extensions_' . $extension] = 'extensions_' . $extension;
				$this->view->extensions[] = 'extensions_' . $extension;
			}
		}
		array_multisort($sort_order, SORT_ASC, $this->view->extensions);
	}
	
	public function bottomAction() {
		$getall = Model_Extensions::getAll();
		$this->view->extensions = array();
		$sort_order = array();
		$this->view->children = array();
		foreach($getall AS $row => $extension) {
			if(JO_Registry::forceGet($extension . '_position') == 'bottom' && (int)JO_Registry::forceGet($extension . '_status') == 1) {
				$sort_order[$row] = (int)JO_Registry::forceGet($extension . '_sort_order'); 
				$this->view->children['extensions_' . $extension] = 'extensions_' . $extension;
				$this->view->extensions[] = 'extensions_' . $extension;
			}
		}
		array_multisort($sort_order, SORT_ASC, $this->view->extensions);
	}
	
}

?>
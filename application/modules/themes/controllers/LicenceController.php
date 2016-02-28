<?php

class LicenceController extends JO_Action {
   	
   public function indexAction() {
   	
   		$page = JO_Registry::get('page_regular_licence');
		$this->getRequest()->setParams('page_id', $page);
		$this->forward('pages','index');
	}
}
?>
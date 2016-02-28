<?php

class FeaturedController extends JO_Action {

	public function indexAction() {
		$this->getRequest()->setParams('list_type', 'featured');
		$this->forward('index','index');
	}	
}
?>
<?php

class RecentController extends JO_Action {

	public function indexAction() {
		$this->getRequest()->setParams('list_type', 'recent');
		$this->forward('index','index');
	}
}
?>
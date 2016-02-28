<?php

class AuthorController extends JO_Action {
	
	public function indexAction() {
		$this->getRequest()->setParams('list_type', 'author');
		$this->forward('index','index');
	}
}
?>
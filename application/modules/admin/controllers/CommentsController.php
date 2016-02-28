<?php

class CommentsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Reported comments'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'users',
			'sort_order' => 24000
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
    	
    	$this->view->page_num = $page = $this->getRequest()->getRequest('page', 1);
    	
    	$data = array(
    		'start' => ($page * JO_Registry::get('admin_limit')) - JO_Registry::get('admin_limit'),
			'limit' => JO_Registry::get('admin_limit')
    	);
        
		$this->view->comments = array();
        $comments = Model_Comments::getReportedComments($data);
       
        if($comments) {
        	$bbcode_parser = new WM_BBCode_Parser();
			$bbcode_parser->loadDefaultCodes();
			
        	foreach($comments AS $comment) {
        		$comment['href'] = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&action=comments&item_id=' . $comment['item_id'] . '&filter=' . ($comment['reply_to'] ? $comment['reply_to'] : $comment['id']));
            	$bbcode_parser->parse($comment['comment']);
            	$comment['comment'] = $bbcode_parser->getAsHtml();
        		$this->view->comments[] = $comment;
        	}
        } 
        
        $total_records = Model_Comments::getTotalReportedComments($data);
		
		$this->view->total_pages = ceil($total_records / JO_Registry::get('admin_limit'));
		$this->view->total_rows = $total_records;
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(JO_Registry::get('admin_limit'));
		$pagination->setPage($page);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/comments/?page={page}');
		$this->view->pagination = $pagination->render();
        
	}
	
	public function previewedAction() {
		$this->noViewRenderer(true);
		Model_Comments::setPreviewed($this->getRequest()->getPost('id'));
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		Model_Comments::deleteComment($this->getRequest()->getPost('id'));
	}

}

?>
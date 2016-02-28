<?php

class QuizController extends JO_Action {

	public function indexAction() {
		$request = $this->getRequest();
	    if(!JO_Session::get('user_id')){
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_error = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		} 
		if(JO_Session::get('msg_error')) {
			$this->view->msg_error = JO_Session::get('msg_error');
			JO_Session::clear('msg_error');
		}
		
		$this->view->questions = $questions = Model_Quiz::getAllQuestions(0, 0, '', 'RAND()');
	    $this->view->answers = $answers = Model_Quiz::getAllAnswers(0, 0, '', true);
		
	    if($request->isPost()) {
	    	
			$rightAnswers = 0;
			$user_answers = $request->getPost('answers');
			
			if(is_array($user_answers)) {
				foreach($user_answers as $question=>$answer) {
					if(isset($answers[$question][$answer]) && $answers[$question][$answer]['right'] == 'true') {
						$rightAnswers++;
					}
				}				
			}
			
			if($rightAnswers > 0 && count($questions) == $rightAnswers) {
				$_SESSION['user']['quiz'] = 'true';
				JO_Session::set('quiz', 'true');
				
				Model_Users::updateQuiz(JO_Session::get('user_id'), 'true');
				JO_Session::set('msg_success', 'You have successfully completed the quiz');
				$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=upload'));
			} else {
			    JO_Session::set('msg_error', 'You have to answer all questions correctly. You have '.$rightAnswers.' right answers from '.$question.' questions');
				$this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=quiz'));
				
			}
		}	
	    
		$this->view->usersCount = Model_Users::countUsers();
		$this->view->itemsCount = Model_Items::countItems();
		
	    $this->getLayout()->meta_title = $this->translate('Quiz');
    	$this->getLayout()->meta_description = $this->translate('Quiz');
		
		$this->view->page_name = $this->translate('Quiz');
		
		/* CRUMBS */
		$this->view->crumbs = array();
		
		$this->view->crumbs[] = array(
			'name' => $this->view->translate('Home'),
			'href' => $request->getBaseUrl()
		);
		
	    $this->view->children = array();
        $this->view->children['header_part'] = 'layout/header_part';
        $this->view->children['footer_part'] = 'layout/footer_part';
	}
	
}

?>
<?php
	
class ForumController extends JO_Action {
	
	public function indexAction() {
		$request = $this->getRequest();
		$model_images = new Model_Images();
		
		JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
		
		$page = (int) $request->getRequest('page', 1);
		if($page < 1) $page = 1;
		$limit = JO_Registry::get('front_limit');
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			$this->view->data = JO_Session::get('data');
			JO_Session::clear('msg_error');
			JO_Session::clear('data');
		}
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
		
		$this->view->headline = $this->translate('All Topics');
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum')
			)
		);
		
		$this->view->threads = array();
		$threads = Model_Forum::getAllThreads();
		
		$total_records = count($threads);
		$start = (($page * $limit) - $limit);
		if($start > $total_records) {
			$page = max(ceil($total_records / $limit), 1);
			$start = (($page * $limit) - $limit);
		} elseif($start < 0) {
			$start = 0;
		}
		
		$threads = array_slice($threads, $start, $limit);
		$this->view->smiles = Model_Smiles::getSmilesImages();
		
		if($threads) {
			foreach($threads as $thread) {
				if($thread['avatar']) {
					$thread['avatar'] = $model_images->resize($thread['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else {
					$thread['avatar'] = 'data/themes/images/noavatar.png';
				}
				
				$thread['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['owner']));
				$thread_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $thread['id'] .'/'. WM_Router::clearName($thread['name']));
				$thread['threadhref'] = $thread_link;
				$thread['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $thread['id']);
				$thread['badges'] = Helper_Author::userBadges($thread['badges']);
				$thread['first_date'] = WM_Date::format($thread['datetime'], 'dd M yy H:i');
				
				$last_page = ceil($thread['cnt'] / $limit);
				
				if(!empty($thread['lusername'])) {
					if($thread['lavatar']) {
						$thread['lavatar'] = $model_images->resize($thread['lavatar'], 50, 50, true);
					} else {
						$thread['lavatar'] = 'data/themes/images/small_noavatar.png';
					}
					
					$thread['lasthref'] = $thread_link .($last_page > 1 ? '/page/'. $last_page : '');	
					$thread['lhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['lusername']));
					$thread['last_date'] = WM_Date::format($thread['last_post'], 'dd M yy H:i');
				}

				$this->view->threads[] = $thread;
			}
		}
		
		$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=add_new_topic');
		
		$pagination = new Model_Pagination;
		$pagination->setLimit($limit);
		$pagination->setPage($page);
		$pagination->setText(array(
			'text_prev' => $this->view->translate('Prev'),
			'text_next' => $this->view->translate('Next')
		));
		$pagination->setTotal($total_records);
		$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
		$this->view->pagination = $pagination->render();
		
		if(!empty($this->view->pagination)) {
			$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
		}
		
		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function add_new_topicAction() {
		$request = $this->getRequest();
		$link = $request->getServer('HTTP_REFERER');
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->issetPost('name') && $request->issetPost('comment')) {
				
			$thread_id = trim($request->getPost('thread_id'));
			$name = trim($request->getPost('name'));
			$comment = trim($request->getPost('comment'));
			$error = array();
			
			if(empty($name)) {
				$error['name'] = $this->translate('Subject cannot be empty');
			}
			if(empty($comment)) {
				$error['comment'] = $this->translate('Comment cannot be empty');
			}
			
			if(empty($error)) {
				$id = Model_Forum::setThread($thread_id, array(
					'user_id' => JO_Session::get('user_id'),
					'name' => strip_tags(html_entity_decode($name)),
					'comment' => strip_tags(html_entity_decode($comment),'<br><p><span><h1><h2><h3><a><img><big><small><ul><ol><li><quote>'),
					'notify' => ($request->getPost('reply_notification') == 1 ? 'true' : 'false'),
					'reply_to' => ($request->getPost('reply_to') ? (int)$request->getPost('reply_to') : 0)
				));
				JO_Session::set('msg_success', $this->translate('The thread has been successfully posted'));
				$this->redirect($link .'#c_'. $id);
			} else {
				$error['msg_error'] = $this->translate('There was an error posting your thread');
				JO_Session::set('msg_error', $error);
				JO_Session::set('data', $request->getParams());
			}
		}
		
		$this->redirect($link);
	}
	
	public function add_new_commentAction() {
		$request = $this->getRequest();
		$link = $request->getServer('HTTP_REFERER');
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->issetPost('thread_id') && $request->issetPost('comment') && $request->issetPost('topic_id')) {
				
			$thread_id = trim($request->getPost('thread_id'));
			$comment = trim($request->getPost('comment'));
			$error = array();
			if(empty($comment)) {
				$error['comment'] = $this->translate('Comment cannot be empty');
			}
			
			if(!empty($comment)) {
				$id = Model_Forum::setThread($thread_id, array(
					'user_id' => JO_Session::get('user_id'),
					'name' => '',
					'comment' => strip_tags(html_entity_decode($comment),'<br><p><span><h1><h2><h3><a><img><big><small><ul><ol><li><quote>'),
					'notify' => ($request->getPost('reply_notification') == 1 ? 'true' : 'false'),
					'reply_to' => $request->getPost('topic_id')
				));
				JO_Session::set('msg_success', $this->translate('The reply has been successfully posted'));
				$this->redirect($link .'#c_'. $id);
			} else {
				$error['msg_error'] = $this->translate('There was an error posting your comment');
				JO_Session::set('msg_error', $error);
			}
		}
		
		$this->redirect($link);
	}
	
	public function reportAction() {
		$request = $this->getRequest();
		$link = $request->getServer('HTTP_REFERER');
		$this->noViewRenderer(true);
		
		if(!JO_Session::get('user_id')) {
	        JO_Session::set('msg_error', $this->translate('You must be logged to change your profile'));
	        $this->redirect(WM_Router::create($request->getBaseUrl() . '?controller=users&action=login'));
	    }
		
		if($request->getRequest('report')) {
    		$s = Model_Forum::report($request->getRequest('report'));
    		JO_Session::set('msg_success', $this->translate('Thank you for reporting the comment'));
    	}
		
		$this->redirect($request->getServer('HTTP_REFERER'));
	}
	
	public function topicAction() {
		$request = $this->getRequest();
		$thread_id = $request->getRequest('topic');
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
			)
		);
		
		$thread = Model_Forum::getThread($thread_id);
		$this->view->sel_thread = $thread['thread_id'];
		$this->view->headline = $thread['name'];
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'id' => 0,
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
			
		if($thread) {
			JO_Session::set('redirect', $request->getBaseUrl() . $request->getUri());
			$model_images = new Model_Images();
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			if(JO_Session::get('msg_success')) {
				$this->view->msg_success = JO_Session::get('msg_success');
				JO_Session::clear('msg_success');
			}
			
			if(JO_Session::get('msg_error')) {
				$this->view->error = JO_Session::get('msg_error');
				$this->view->data = JO_Session::get('data');
				JO_Session::clear('msg_error');
				JO_Session::clear('data');
			}
			
			$this->view->crumbs[] = array(
				'name' => $thread['name']
			);
			
			$threads = Model_Forum::getSubComments($thread['id']);
			$this->view->smiles = Model_Smiles::getSmilesImages();
			
			if($threads) {
				$total_records = count($threads);
				$start = (($page * $limit) - $limit);
				if($start > $total_records) {
					$page = max(ceil($total_records / $limit), 1);
					$start = (($page * $limit) - $limit);
				} elseif($start < 0) {
					$start = 0;
				}
			
				$threads = array_slice($threads, $start, $limit);
				
				$bbcode_parser = new WM_BBCode_Parser();
				$bbcode_parser->loadDefaultCodes();
				
				foreach($threads as $th) {
					if($th['avatar']) {
						$th['avatar'] = $model_images->resize($th['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
					} else {
						$th['avatar'] = 'data/themes/images/noavatar.png';
					}
					
					
					$th['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($th['username']));
					
					$th_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $th['id'] .'/'. WM_Router::clearName($th['name']));
					$th['threadhref'] = $th_link;
					$th['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $th['id']);
					
					$th['recent_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=recents_for_user&username='. WM_Router::clearName($th['username']));
					$th['threads_user_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=threads_for_user&username='. WM_Router::clearName($th['username']));
					
					$bbcode_parser->parse($th['comment']);
					$th['comment_formated'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
					
					$last_page = ceil($total_records / $limit);
					$th['datetime'] = WM_Date::format($th['datetime'], 'dd M yy H:i');
					$th['badges'] = Helper_Author::userBadges($th['badges']);
					
					$this->view->threads[] = $th;
				}
			}
			
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setText(array(
				'text_prev' => $this->view->translate('Prev'),
				'text_next' => $this->view->translate('Next')
			));
			$pagination->setTotal($total_records);
			$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
			$this->view->pagination = $pagination->render();
			
			if(!empty($this->view->pagination)) {
				$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
			}
		}

		$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=add_new_comment');

		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function threadAction() {
		$request = $this->getRequest();
		$this->setViewChange('index');
		$this->view->sel_thread = $thread_id = $request->getRequest('thread');
		$model_images = new Model_Images();
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
			)
		);
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'id' => 0,
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
		
		foreach($this->view->mainCategories as $cat) {
			if($cat['id'] == $thread_id) {
				$this->view->headline = $cat['name']; 
				break;
			}
		}
		$this->view->smiles = Model_Smiles::getSmilesImages();
		$threads = Model_Forum::getThreadComments($thread_id);
		if($threads) {
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			if(JO_Session::get('msg_success')) {
				$this->view->msg_success = JO_Session::get('msg_success');
				JO_Session::clear('msg_success');
			}
			
			if(JO_Session::get('msg_error')) {
				$this->view->error = JO_Session::get('msg_error');
				$this->view->data = JO_Session::get('data');
				JO_Session::clear('msg_error');
				JO_Session::clear('data');
			}
			
			$this->view->crumbs[] = array(
				'name' => $threads[0]['name']
			);
			
			$total_records = count($threads);
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
		
			$threads = array_slice($threads, $start, $limit);
		
			foreach($threads as $thread) {
				if($thread['avatar']) {
					$thread['avatar'] = $model_images->resize($thread['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else {
					$thread['avatar'] = 'data/themes/images/noavatar.png';
				}
				
				$thread['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['owner']));
				$thread_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $thread['id'] .'/'. WM_Router::clearName($thread['name']));
				$thread['threadhref'] = $thread_link;
				$thread['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $thread['id']);
				$thread['badges'] = Helper_Author::userBadges($thread['badges']);
				$thread['first_date'] = WM_Date::format($thread['datetime'], 'dd M yy H:i');
				
				$last_page = ceil($thread['cnt'] / $limit);
				
				if(!empty($thread['lusername'])) {
					if($thread['lavatar']) {
						$thread['lavatar'] = $model_images->resize($thread['lavatar'], 50, 50, true);
					} else {
						$thread['lavatar'] = 'data/themes/images/small_noavatar.png';
					}
					
					$thread['lasthref'] = $thread_link .($last_page > 1 ? '/page/'. $last_page : '');	
					$thread['lhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['lusername']));
					$thread['last_date'] = WM_Date::format($thread['last_post'], 'dd M yy H:i');
				}
				
				$this->view->threads[] = $thread;
			}
		}
		
		$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=add_new_topic');
		
		$pagination = new Model_Pagination;
		$pagination->setLimit($limit);
		$pagination->setPage($page);
		$pagination->setText(array(
			'text_prev' => $this->view->translate('Prev'),
			'text_next' => $this->view->translate('Next')
		));
		$pagination->setTotal($total_records);
		$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
		$this->view->pagination = $pagination->render();
		
		if(!empty($this->view->pagination)) {
			$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
		}
		
		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function rightsideAction() {
		$request = $this->getRequest();
		$limit = JO_Registry::get('front_limit');
		
		if($request->getAction() == 'topic') {
			$this->view->add_link_head = $this->translate('Post a reply');
		} else {
			$this->view->add_link_head = $this->translate('Post new thread');
		}
		
		$help = Model_Pages::get(JO_Registry::forceGet('page_forum_rules'));
        if($help) {
        	$this->view->rules_link = WM_Router::create($request->getBaseUrl() . '?controller=pages&page_id='. $help['id'] .'&name='.WM_Router::clearName($help['name']));
        }
		
		if(JO_Session::get('user_id')) {
			$lnk = $request->getFullUrl();
			$lnk = explode('#', $lnk);
			$this->view->post_link = $lnk[0] .'#cform';
		} else {
			$this->view->post_link = WM_Router::create($request->getBaseUrl() .'?controller=users&action=login');
		}
		$threads = Model_Forum::getLastThreads();
		$this->view->threads = array();
		if($threads) {
			foreach($threads as $thread) {
				$thread['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=topic/'.$thread['id'] .'/'. WM_Router::clearName($thread['name']));
				
				if($thread['lusername']) {
					$thread['last_href'] = '';
					$thread['datetime'] = WM_Date::format($thread['last_post'], 'dd M yy H:i');
					$last_page = ceil($thread['cnt'] / $limit);
					$thread['lasthref'] = $thread['href'] .($last_page > 1 ? '/page/'. $last_page : '');
				}

				$this->view->threads[] = $thread;
			}
		}
		
		if(JO_Session::get('user_id')) {
			$rthreads = Model_Forum::getRecentThreads(JO_Session::get('user_id'));
			$this->view->recent_threads = array();
			if($rthreads) {
				foreach($rthreads as $rthread) {
					$rthread['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=topic/'.$rthread['id'] .'/'. WM_Router::clearName($rthread['name']));
					
					if($rthread['lusername']) {
						$rthread['last_href'] = '';
						$rthread['datetime'] = WM_Date::format($rthread['last_post'], 'dd M yy H:i');
						$last_page = ceil($rthread['cnt'] / $limit);
						$rthread['lasthref'] = $rthread['href'] .($last_page > 1 ? '/page/'. $last_page : '');
					}
	
					$this->view->recent_threads[] = $rthread;
				}
			}
		}
		
		$this->view->srch_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=search');
		$this->view->rss_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=rss');	
	}

	public function rssAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		$threads = Model_Forum::getRss();
		$limit = JO_Registry::get('front_limit');
		
		$this->view->url = array();
		if($threads) {
			foreach($threads as $k => $thread) {
				$this->view->url[$k] = array(
					'id' => 'tag:'. $request->getBaseUrl() .','. $thread['datetime'] .':'. $this->translate('Forum') .'::'. $this->translate('Message') .'/'. $thread['id'],
					'published' => $thread['datetime'],
				);
				
				$last_page = ceil($thread['cnt'] / $limit);
				
				$this->view->url[$k]['link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=topic/'. $thread['thread_id'] .'/'. WM_Router::clearName($thread['thread_name']) . ($last_page > 1 ? '/page/'. $last_page : ''));
				$this->view->url[$k]['comment'] = $thread['comment'];
				$this->view->url[$k]['author'] = $thread['username'];
			}
		}

		echo $this->renderScript('sitemap');
	}

	public function searchAction() {
		$request = $this->getRequest();
		$keyword = $request->getRequest('keyword_comment');
		$keyword = trim(mb_strtolower(urldecode($keyword), 'UTF-8'));
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
			),
			array(
				'name' => $this->translate('Search')
			)
		);
		
		
		if(JO_Session::get('msg_success')) {
			$this->view->msg_success = JO_Session::get('msg_success');
			JO_Session::clear('msg_success');
		}
		
		if(JO_Session::get('msg_error')) {
			$this->view->error = JO_Session::get('msg_error');
			$this->view->data = JO_Session::get('data');
			JO_Session::clear('msg_error');
			JO_Session::clear('data');
		}
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
		
		$this->view->headline = $this->translate('All Topics');
		$this->view->sel_thread = -1;
		
		$this->view->headline = $this->translate('Search') .': '. $keyword;
		
		$threads = Model_Forum::getSearch($keyword);
		$total_records = count($threads);
		
		if($threads) {
			$model_images = new Model_Images();
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			$total_records = count($threads);
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
		
			$threads = array_slice($threads, $start, $limit);
			$this->view->smiles = Model_Smiles::getSmilesImages();
			
			$bbcode_parser = new WM_BBCode_Parser();
			$bbcode_parser->loadDefaultCodes();
			
			foreach($threads as $th) {
				if($th['avatar']) {
					$th['avatar'] = $model_images->resize($th['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else {
					$th['avatar'] = 'data/themes/images/noavatar.png';
				}
				
				
				$th['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($th['username']));
				
				$th_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $th['id'] .'/'. WM_Router::clearName($th['name']));
				$th['threadhref'] = $thread_link;
				$th['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $th['id']);
				
				$th['recent_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=recents_for_user&username='. WM_Router::clearName($th['username']));
				$th['threads_user_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=threads_for_user&username='. WM_Router::clearName($th['username']));
				
				$bbcode_parser->parse($th['comment']);
				$th['comment_formated'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
				
				$last_page = ceil($thread['cnt'] / $limit);
				$th['datetime'] = WM_Date::format($th['datetime'], 'dd M yy H:i');
				$th['badges'] = Helper_Author::userBadges($th['badges']);
				
				$this->view->threads[] = $th;
			}
			
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setText(array(
				'text_prev' => $this->view->translate('Prev'),
				'text_next' => $this->view->translate('Next')
			));
			$pagination->setTotal($total_records);
			$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
			$this->view->pagination = $pagination->render();
			
			if(!empty($this->view->pagination)) {
				$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
			}
			
			$this->view->add_comment_link = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=add_new_comment');
		}
		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}

	public function recents_for_userAction() {
		$request = $this->getRequest();
		$this->setViewChange('search');
		$username = $request->getRequest('recents_for_user');
		$username = trim(mb_strtolower(urldecode($username), 'UTF-8'));
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
			),
			array(
				'name' => $username
			)
		);
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
		
		$this->view->headline = $this->translate('All Topics');
		$this->view->sel_thread = -1;
		
		$this->view->headline = $this->translate('Recent') .': '. $username;
		
		$threads = Model_Forum::getRecentByUser($username);
		$total_records = count($threads);
		
		if($threads) {
			$model_images = new Model_Images();
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			$total_records = count($threads);
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
		
			$threads = array_slice($threads, $start, $limit);
			
			$bbcode_parser = new WM_BBCode_Parser();
			$bbcode_parser->loadDefaultCodes();
			
			foreach($threads as $th) {
				if($th['avatar']) {
					$th['avatar'] = $model_images->resize($th['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else {
					$th['avatar'] = 'data/themes/images/noavatar.png';
				}
				
				
				$th['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($th['username']));
				
				$th_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $th['id'] .'/'. WM_Router::clearName($th['name']));
				$th['threadhref'] = $thread_link;
				$th['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $th['id']);
				
				$th['recent_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=recents_for_user&username='. WM_Router::clearName($th['username']));
				$th['threads_user_link'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=threads_for_user&username='. WM_Router::clearName($th['username']));
				
				$bbcode_parser->parse($th['comment']);
				$th['comment_formated'] = Model_Comments::replaceEmoticons($bbcode_parser->getAsHtml());
				
				$last_page = ceil($thread['cnt'] / $limit);
				$th['datetime'] = WM_Date::format($th['datetime'], 'dd M yy H:i');
				$th['badges'] = Helper_Author::userBadges($th['badges']);
				
				$this->view->threads[] = $th;
			}
			
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setText(array(
				'text_prev' => $this->view->translate('Prev'),
				'text_next' => $this->view->translate('Next')
			));
			$pagination->setTotal($total_records);
			$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
			$this->view->pagination = $pagination->render();
			
			if(!empty($this->view->pagination)) {
				$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
			}
		}
		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
	
	public function threads_for_userAction() {
		$request = $this->getRequest();
		$this->setViewChange('index');
		$username = $request->getRequest('threads_for_user');
		$username = trim(mb_strtolower(urldecode($username), 'UTF-8'));
		
		$this->view->crumbs = array(
			array(
				'name' => $this->translate('Home'),
				'href' => $request->getBaseUrl()
			),
			array(
				'name' => $this->translate('Forum'),
				'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
			),
			array(
				'name' => $username
			)
		);
		
		$this->view->mainCategories = array();
		$this->view->mainCategories = Model_Forum::getAll();
		foreach($this->view->mainCategories as $k => $v) {
			$this->view->mainCategories[$k]['href'] = WM_Router::create($request->getBaseUrl() .'?controller=forum&action=thread/'. $this->view->mainCategories[$k]['id'] .'/'. WM_Router::clearName($this->view->mainCategories[$k]['name']));
		}
		$this->view->mainCategories = array_merge(
			array(
				0 => array(
					'name' => $this->translate('All Topics'),
					'href' => WM_Router::create($request->getBaseUrl() .'?controller=forum')
				)
			), $this->view->mainCategories);
		
		$this->view->headline = $this->translate('All Topics');
		$this->view->sel_thread = -1;
		
		$this->view->headline = $this->translate('Threads') .': '. $username;
		
		$threads = Model_Forum::getThreadsByUser($username);
		$total_records = count($threads);
		
		if($threads) {
			$model_images = new Model_Images();
			
			$page = (int) $request->getRequest('page', 1);
			if($page < 1) $page = 1;
			$limit = JO_Registry::get('front_limit');
			
			if(JO_Session::get('msg_success')) {
				$this->view->msg_success = JO_Session::get('msg_success');
				JO_Session::clear('msg_success');
			}
			
			if(JO_Session::get('msg_error')) {
				$this->view->error = JO_Session::get('msg_error');
				$this->view->data = JO_Session::get('data');
				JO_Session::clear('msg_error');
				JO_Session::clear('data');
			}
			
			$total_records = count($threads);
			$start = (($page * $limit) - $limit);
			if($start > $total_records) {
				$page = max(ceil($total_records / $limit), 1);
				$start = (($page * $limit) - $limit);
			} elseif($start < 0) {
				$start = 0;
			}
		
			$threads = array_slice($threads, $start, $limit);
		
			foreach($threads as $thread) {
				if($thread['avatar']) {
					$thread['avatar'] = $model_images->resize($thread['avatar'], JO_Registry::forceGet('user_avatar_width'), JO_Registry::forceGet('user_avatar_height'), true);
				} else {
					$thread['avatar'] = 'data/themes/images/noavatar.png';
				}
				
				$thread['userhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['owner']));
				$thread_link = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=topic/'. $thread['id'] .'/'. WM_Router::clearName($thread['name']));
				$thread['threadhref'] = $thread_link;
				$thread['reporthref'] = WM_Router::create($request->getBaseUrl() . '?controller=forum&action=report/'. $thread['id']);
				$thread['badges'] = Helper_Author::userBadges($thread['badges']);
				$thread['first_date'] = WM_Date::format($thread['datetime'], 'dd M yy H:i');
				
				$last_page = ceil($thread['cnt'] / $limit);
				
				if(!empty($thread['lusername'])) {
					if($thread['lavatar']) {
						$thread['lavatar'] = $model_images->resize($thread['lavatar'], 50, 50, true);
					} else {
						$thread['lavatar'] = 'data/themes/images/small_noavatar.png';
					}
					
					$thread['lasthref'] = $thread_link .($last_page > 1 ? '/page/'. $last_page : '');	
					$thread['lhref'] = WM_Router::create($request->getBaseUrl() . '?controller=users&action=index&username='. WM_Router::clearName($thread['lusername']));
					$thread['last_date'] = WM_Date::format($thread['last_post'], 'dd M yy H:i');
				}
				
				$this->view->threads[] = $thread;
			}
			
			$pagination = new Model_Pagination;
			$pagination->setLimit($limit);
			$pagination->setPage($page);
			$pagination->setText(array(
				'text_prev' => $this->view->translate('Prev'),
				'text_next' => $this->view->translate('Next')
			));
			$pagination->setTotal($total_records);
			$pagination->setUrl(WM_Router::create($request->getBaseUrl() .'?controller=forum&action=index&page={page}'));
			$this->view->pagination = $pagination->render();
			
			if(!empty($this->view->pagination)) {
				$this->view->pagination = str_replace('{of}', $this->view->translate('OF'), $this->view->pagination);
			}
		}

		$this->view->children = array();
		$this->view->children['rightside'] = 'forum/rightside';
    	$this->view->children['header_part'] = 'layout/header_part';
    	$this->view->children['footer_part'] = 'layout/footer_part';
	}
}
?>
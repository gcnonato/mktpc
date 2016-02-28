<?php

class SitemapController extends JO_Action {
	
	public function indexAction() {
		$this->noViewRenderer(true);
		$request = $this->getRequest();
		$result = Model_Sitemap::create();
		
		$this->view->url = array(
			array(
				'loc' => $request->getBaseUrl(),
				'lastmod' => date("Y-m-d"),
				'changefreq' => 'daily',
				'priority' => '1.0'
			)
		);
		
		if($result) {
			foreach($result as $row) {
				
				$loc = '';
				if($row['tp'] == 'item') {
					$loc = self::fixURL(WM_Router::create($request->getBaseUrl() .'?controller=items&item_id='. $row['id'] .'&name='. WM_Router::clearName($row['name']))); 
				} elseif($row['tp'] == 'category') {
					$loc =  self::fixURL(WM_Router::create($request->getBaseUrl() .'?controller=categories&category_id='. $row['id'] .'&name='. WM_Router::clearName($row['name'])));
				} elseif($row['tp'] == 'page') {
					$loc = self::fixURL(WM_Router::create($request->getBaseUrl() .'?controller=pages&page_id='. $row['id'] .'&name='. WM_Router::clearName($row['name'])));
				} elseif($row['tp'] == 'user') {
					$loc = self::fixURL(WM_Router::create($request->getBaseUrl() .'?controller=users&username='. WM_Router::clearName($row['name'])));
				}
				
				$this->view->url[] = array(
						'lastmod' => $row['datetime'],
						'changefreq' => $row['change_freq'],
						'priority' => $row['priority'],
						'loc' => $loc
					);
				
				
			}
		}
		
		echo $this->renderScript('sitemap');	
	}
	
	private static function fixURL($in) {
		return htmlspecialchars(html_entity_decode($in, ENT_QUOTES, 'utf-8'), ENT_QUOTES, 'utf-8');
	}
	
}

?>
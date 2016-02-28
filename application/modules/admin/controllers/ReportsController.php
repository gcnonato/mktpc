<?php

class ReportsController extends JO_Action {
	
	public static function config() {
		return array(
			'name' => self::translate('Reports'),
			'has_permision' => true,
			'menu' => self::translate('Reports'),
			'in_menu' => true,
			'permision_key' => 'reports',
			'sort_order' => 61000
		);
	}
	
	/////////////////// end config
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		if($request->getQuery('from')) {
			$this->view->from = $request->getQuery('from');	
		} else {
			$date = new JO_Date(null, 'yy-mm-01');
			$this->view->from = $date->toString();
		}
		
		if($request->getQuery('to')) {
			$this->view->to = $request->getQuery('to');	
		} else {
			$date = new JO_Date(null, 'yy-mm-t');
			$this->view->to = $date->toString();
		}
		
		$reportData = Model_Reports::getReport($this->view->from, $this->view->to);
		$depositData = Model_Reports::getDeposits($this->view->from, $this->view->to);
		$withdrawData = Model_Reports::getWithdraws($this->view->from, $this->view->to);
		
		$data = array();
		
		foreach($reportData as $date=>$v) {
			$data[$date] = array();
		}
		
		foreach($depositData as $date=>$v) {
			$data[$date] = array();
		}
		
		foreach($withdrawData as $date=>$v) {
			$data[$date] = array();
		}
		
		foreach($data as $k=>$v) {
				
			if(isset($reportData[$k])) {
				$data[$k]['total'] = $reportData[$k]['total'];
				$data[$k]['receive'] = $reportData[$k]['receive'];
				$data[$k]['referal'] = $reportData[$k]['referal'];
				$data[$k]['win'] = $reportData[$k]['win'];
				$data[$k]['total_for'] = WM_Currency::format($reportData[$k]['total']);
				$data[$k]['receive_for'] = WM_Currency::format($reportData[$k]['receive']);
				$data[$k]['referal_for'] = WM_Currency::format($reportData[$k]['referal']);
				$data[$k]['win_for'] = WM_Currency::format($reportData[$k]['win']);
			}
			else {
				$data[$k]['total'] = 0;
				$data[$k]['receive'] = 0;
				$data[$k]['referal'] = 0;
				$data[$k]['win'] = 0;
				$data[$k]['total_for'] = WM_Currency::format(0);
				$data[$k]['receive_for'] = WM_Currency::format(0);
				$data[$k]['referal_for'] = WM_Currency::format(0);
				$data[$k]['win_for'] = WM_Currency::format(0);
			}
			
			if(isset($depositData[$k])) {
				$data[$k]['deposit'] = $depositData[$k]['deposit'];
				$data[$k]['deposit_for'] = WM_Currency::format($depositData[$k]['deposit']);
			}
			else {
				$data[$k]['deposit'] = 0;
				$data[$k]['deposit_for'] = WM_Currency::format(0);
			}
			
			if(isset($withdrawData[$k])) {
				$data[$k]['withdraw'] = $withdrawData[$k]['amount'];
				$data[$k]['withdraw_for'] = WM_Currency::format($withdrawData[$k]['amount']);
			}
			else {
				$data[$k]['withdraw'] = 0;
				$data[$k]['withdraw_for'] = WM_Currency::format(0);
			}
		}
		
		$this->view->reports = $data;
		
		
	}

}

?>
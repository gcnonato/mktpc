<?php 

class IndexController extends JO_Action {

    public function init() {}

    public function indexAction() {
    	$request = $this->getRequest();
    	$this->view->base_url = $request->getBaseUrl();
    	
    	#LINKS FOR HEAD PARTS IN BOX
    	$this->view->finance_href = $request->getModule() . '/reports/';
    	$this->view->sales_href = $request->getModule() . '/orders/';
    	$this->view->users_href = $request->getModule() . '/users/';
    	$this->view->approval_href = $request->getModule() . '/queueitems/';
    	$this->view->queue_href = $request->getModule() . '/queueupdateditems/';
    	$this->view->contacts_href = $request->getModule() . '/contacts/';
    	$this->view->gainpayingup_href = $request->getModule() . '/gainpayingup/';
    	$this->view->tags_href = $request->getModule() . '/tags/?filter_visible=false';
    	
		
    	#LOAD ORDERS COUNT
    	$this->view->total = Model_Orders::getSalesStatus();
    	if($this->view->total) {
    		$this->view->total['total_f'] = WM_Currency::format($this->view->total['total']);
    	}
    	
    	$ref = Model_Orders::getSalesStatus(" AND `datetime` > '".date('Y-m')."-01 00:00:00' ", 'referal');
		$sales = Model_Orders::getSalesStatus(" AND `datetime` > '".date('Y-m')."-01 00:00:00' ");
		if($sales) {
			if($ref) {
				$sales['referal'] = $ref['receive'];
			} else {
				$sales['referal'] = 0;
			}
			$sales['win'] = floatval($sales['total']) - floatval($sales['receive']) - floatval($sales['referal']);
			
			$sales['total_f'] = WM_Currency::format($sales['total']);
			$sales['receive_f'] = WM_Currency::format($sales['receive']);
			$sales['referal_f'] = WM_Currency::format($sales['referal']);
			$sales['win_f'] = WM_Currency::format($sales['win']);
			
		}

		$this->view->sales = $sales;
		unset($ref);
		
		$ref = Model_Orders::getSalesStatus("", 'referal');
		$sales = Model_Orders::getSalesStatus("");
		if($sales) {
			if($ref) {
				$sales['referal'] = $ref['receive'];
			} else {
				$sales['referal'] = 0;
			}
			$sales['win'] = floatval($sales['total']) - floatval($sales['receive']) - floatval($sales['referal']);
			
			$sales['total_f'] = WM_Currency::format($sales['total']);
			$sales['receive_f'] = WM_Currency::format($sales['receive']);
			$sales['referal_f'] = WM_Currency::format($sales['referal']);
			$sales['win_f'] = WM_Currency::format($sales['win']);
			
		}

		$this->view->sales2 = $sales;
		unset($ref);
		
		#LOAD USERS COUNT
		$this->view->users = array();
		$this->view->users['month'] = Model_Users::getUsersCount(" `register_datetime` > '".date('Y-m')."-01 00:00:00' AND `status` = 'activate' ");
		$this->view->users['total'] = Model_Users::getUsersCount(" `status` = 'activate' ");
    	
		$this->view->topAuthors = array();
		$topAuthors = Model_Users::getAll(0, 5, " `status` = 'activate' ", "`sales` DESC");
		if($topAuthors) {
			$percentsClass = new Model_Percents;
			foreach($topAuthors AS $user) {
				$user['deposit'] = WM_Currency::format($user['deposit']);
                $user['earning'] = WM_Currency::format($user['earning']);
                $user['total'] = WM_Currency::format($user['total']);
                $user['sold'] = WM_Currency::format($user['sold']);
                $user['referal_money'] = WM_Currency::format($user['referal_money']);
                
                $comision = $percentsClass->getPercentRow($user['user_id']);
                $user['commission'] = round($comision['percent']);
                $user['sum'] = Model_Balance::getTotalUserBalanceByType($user['user_id']);
                
                $user['web_profit'] = WM_Currency::format($user['web_profit']);
    			$user['web_profit2'] = WM_Currency::format($user['web_profit2']);
    			$user['has_referral_sum'] = $user['referral_sum'];
    			$user['referral_sum'] = WM_Currency::format($user['referral_sum']);
                $user['edit_href'] = $request->getModule() . '/users/edite/?id=' . $user['user_id'];
                $user['balance_href'] = $request->getModule() . '/users/balance/?id=' . $user['user_id'];
    			
				$this->view->topAuthors[] = $user;
			}
		}
		
		#LOAD WITHDRAW
		$this->view->withdraw = array();
		$this->view->withdraw['no'] = Model_Deposit::getWithdrawCount(" `paid` = 'false' AND `datetime` > '".date('Y-m')."-01 00:00:00' ");
		if($this->view->withdraw['no']) {
			$this->view->withdraw['no']['total_f'] = WM_Currency::format($this->view->withdraw['no']['total']);
		}
		$this->view->withdraw['paid'] = Model_Deposit::getWithdrawCount(" `paid` = 'true' AND `paid_datetime` > '".date('Y-m')."-01 00:00:00' ");
		if($this->view->withdraw['paid']) {
			$this->view->withdraw['paid']['total_f'] = WM_Currency::format($this->view->withdraw['paid']['total']);
		}
		
		#LOAD THEMES
		$this->view->items = Model_Items::getItems(array(
			'filter_status' => 'queue',
			'start' => 0,
			'limit' => 5
		));
		
		$this->view->updated_items = Model_Items::getItems(array(
			'filter_update' => true,
			'start' => 0,
			'limit' => 5
		));
		
		#LOAD LAST REQUEST
		$this->view->contacts = array();
        $contacts = Model_Contacts::getContacts(array(
			'filter_answer_datetime' => '0000-00-00',
			'start' => 0,
			'limit' => 5
		));
    	if($contacts) {
        	foreach($contacts AS $contact) {
        		$data = new JO_Date($contact['datetime'], 'dd MM yy');
        		$contact['datetime'] = $data->toString();
        		$contact['has_response'] = $contact['answer_datetime'] != '0000-00-00 00:00:00';
        		if($contact['answer_datetime'] != '0000-00-00 00:00:00') {
        			$data = new JO_Date($contact['answer_datetime'], 'dd MM yy');
        			$contact['answer_datetime'] = $data->toString();
        		} else {
        			$contact['answer_datetime'] = '';
        		}
				$this->view->contacts[] = $contact;
        	}
        }
		
        #Withdrawals
   	 	$this->view->withdraws = array();
        $withdraws = Model_Users::getWithdraws(array(
        	'start' => 0,
        	'limit' => 5
        ));
        
        if($withdraws) {
        	foreach($withdraws AS $withdraw) {
        		$withdraw['earning'] = WM_Currency::format($withdraw['earning']);
        		$date = new JO_Date($withdraw['datetime'], 'dd MM yy');
        		$withdraw['datetime'] = $date->toString();
        		if($withdraw['paid'] == 'true') {
	        		$date = new JO_Date($withdraw['paid_datetime'], 'dd MM yy');
	        		$withdraw['paid_datetime'] = $date->toString();
        		} else {
        			$withdraw['paid_datetime'] = '';
        		}
        		$withdraw['amount'] = WM_Currency::format($withdraw['amount']);
            	$this->view->withdraws[] = $withdraw;
        	}
        }
        
        #TAGS NO ACTIVE
        $this->view->tags = Model_Tags::getTags(array(
        	'filter_visible' => 'false',
        	'start' => 0,
        	'limit' => 20
        ));
        
		#DRAW GRAPHCS
		$referal_sum = Model_Orders::getSalesStatusByDay(" AND `datetime` > '".date('Y-m')."-01 00:00:00' ", 'referal');
		$sales_sum = Model_Orders::getSalesStatusByDay(" AND `datetime` > '".date('Y-m')."-01 00:00:00' ");
		
		$referal_money = array();
		$sales_money = array();
		$user_money = array();
		$win_money = array();
		$sales_num = array();
		$referal_num = array();
		$days = array();
//		for($i=1; $i<= date('t'); $i++) {
//			if(isset($referal_sum[date("Y-m-") . sprintf('%02d', $i)])) {
//				$referal_money[] = number_format($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'], 2, '.', '');
//			} else {
//				$referal_money[] = 0;
//			}
//			if(isset($sales_sum[date("Y-m-") . sprintf('%02d', $i)])) {
//				$sales_money[] = number_format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total'], 2, '.', '');
//				$user_money[] = number_format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'], 2, '.', '');
//				if(isset($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'])) {
//					$sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'] = $referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'];
//				}
//				if(!isset($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'])) {
//					$sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'] = 0;
//				}
//				$sales_num[] = $sales_sum[date("Y-m-") . sprintf('%02d', $i)]['num'];
//				$win_money[] = number_format( floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal']), 2, '.', '');
//			} else {
//				$sales_money[] = 0;
//				$user_money[] = 0;
//				$win_money[] = 0;
//				$sales_num[] = 0;
//			}
//			$days[] = $i;
//		}
		
    	for($i=1; $i<= date('t'); $i++) {
			if(isset($referal_sum[date("Y-m-") . sprintf('%02d', $i)])) {
				$referal_money[] = array(
					WM_Currency::format($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive']),
					(float)WM_Currency::format($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'], '', '', false)
				);
			} else {
				$referal_money[] = array(
					WM_Currency::format(0),0
				);
			}
			
			if(isset($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['num'])) {
				$referal_num[] = array(
					$i,
					$referal_sum[date("Y-m-") . sprintf('%02d', $i)]['num']
				);
			} else {
				$referal_num[] = array(
					$i,
					0
				);
			}
			
			if(isset($sales_sum[date("Y-m-") . sprintf('%02d', $i)])) {
				$sales_money[] = array(
					WM_Currency::format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total']),
					(float)WM_Currency::format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total'], '', '', false)
				);
				$user_money[] = array(
					WM_Currency::format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive']),
					(float)WM_Currency::format($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'], '', '', false)
				);
				if(isset($referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'])) {
					$sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'] = $referal_sum[date("Y-m-") . sprintf('%02d', $i)]['receive'];
				}
				if(!isset($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'])) {
					$sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'] = 0;
				}
				$sales_num[] = array(
					$i,
					$sales_sum[date("Y-m-") . sprintf('%02d', $i)]['num']
				);
				$win_money[] = array(
					WM_Currency::format(($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal'])),
					(float)WM_Currency::format(($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['total']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['receive']) - floatval($sales_sum[date("Y-m-") . sprintf('%02d', $i)]['referal']), '', '', false)
				);
			} else {
				$sales_money[] = array(
					WM_Currency::format(0),0
				);
				$user_money[] = array(
					WM_Currency::format(0),0
				);
				$win_money[] = array(
					WM_Currency::format(0),0
				);
				$sales_num[] = array(
					$i,
					0
				);
			}
			$days[] = $i;
		}
		
		$new_array = array();
		$new_array[] = array('name' => $this->translate('Total'), 'data' => $sales_money);
		$new_array[] = array('name' => $this->translate('User\'s profit'), 'data' => $user_money);
		$new_array[] = array('name' => $this->translate('Net total'), 'data' => $win_money);
		$new_array[] = array('name' => $this->translate('Referent'), 'data' => $referal_money);
		$new_array2 = array();
		$new_array2[] = array('name' => $this->translate('Referent'), 'data' => $referal_num);
		$new_array2[] = array('name' => $this->translate('Sales'), 'data' => $sales_num);
		
		$this->view->finance_array = JO_Json::encode($new_array);
		$this->view->sales_array = JO_Json::encode($new_array2);
		$this->view->days = JO_Json::encode($days);
		$this->view->currency = WM_Currency::getCurrency();
		
    }
    
    
    //////////// translated
    public function i18nAction() {
    	
    	$this->view->error_validate_1 = $this->translate('You have not filled out a field. Check what is it!');
    	$this->view->error_validate_2 = $this->translate('You have not filled %d boxes. Check who they are!');
    	$this->view->select_all = $this->translate('Select all');
    	$this->view->remove_all = $this->translate('Remove all');
    	$this->view->confirm = $this->translate('Do you really want to perform the selected action?');
    	
    	echo 'var lang = ' . $this->renderScript('json');
    }

}

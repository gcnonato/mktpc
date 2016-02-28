<?php

class Payments_PaypalController extends JO_Action {
	
	public function callback_depositAction() {
		$request = $this->getRequest();
		$order_id = $request->getPost('custom');
		
		$order_info = Model_Deposit::getDeposit($order_id);
		
		if($order_info) {
			$request1 = 'cmd=_notify-validate';
			foreach ($_POST as $key => $value) {
				$request1 .= '&' . $key . '=' . urlencode($value);
			}
			
			if(JO_Registry::forceGet('paypal_sandbox_mode')) {
				$ch = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
			} else {
				$ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
			}
			
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($request1)));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($ch);
			$paid = 'false';
			
			if((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && $request->issetPost('payment_status')) {
					
				$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				
				switch($request->getPost('payment_status')) {
					case 'Canceled_Reversal':
						$order_status_id = JO_Registry::get('pp_standard_canceled_reversal_status_id');
						break;
					case 'Completed':
						if ((strtolower($request->getPost('receiver_email')) == strtolower(JO_Registry::get('paypal_email'))) && ((float)$request->getPost('mc_gross') == (float)$order_info['deposit'])) {
							$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
							$paid = 'true';
						} else {
							JO_Log::write('PP_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($request->getPost('receiver_email')));
						}
						break;
					case 'Denied':
						$order_status_id = JO_Registry::get('pp_standard_denied_status_id');
						break;
					case 'Expired':
						$order_status_id = JO_Registry::get('pp_standard_expired_status_id');
						break;
					case 'Failed':
						$order_status_id = JO_Registry::get('pp_standard_failed_status_id');
						break;
					case 'Pending':
						$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
						break;
					case 'Processed':
						$order_status_id = JO_Registry::get('pp_standard_processed_status_id');
						break;
					case 'Refunded':
						$order_status_id = JO_Registry::get('pp_standard_refunded_status_id');
						break;
					case 'Reversed':
						$order_status_id = JO_Registry::get('pp_standard_reversed_status_id');
						break;
					case 'Voided':
						$order_status_id = JO_Registry::get('pp_standard_voided_status_id');
						break;
				}

				Model_Deposit::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			} else {
				if($request->getPost('payment_status') == 'Completed') {
					$paid = 'true';
					$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
				} else {
					$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				}
				
				Model_Deposit::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			}
			
			curl_close($ch);
		}
		
		$this->noViewRenderer(true);
		$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=success_deposit'));
	}
	
	public function callback_membershipAction() {
		$request = $this->getRequest();
		$order_id = $request->getPost('custom');
		
		$order_info = Model_Membership::get($order_id);
		
		if($order_info) {
			$request1 = 'cmd=_notify-validate';
			foreach ($_POST as $key => $value) {
				$request1 .= '&' . $key . '=' . urlencode($value);
			}
			
			if(JO_Registry::forceGet('paypal_sandbox_mode')) {
				$ch = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
			} else {
				$ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
			}
			
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($request1)));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($ch);
			$paid = 'false';
			
			if((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && $request->issetPost('payment_status')) {
					
				$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				
				switch($request->getPost('payment_status')) {
					case 'Canceled_Reversal':
						$order_status_id = JO_Registry::get('pp_standard_canceled_reversal_status_id');
						break;
					case 'Completed':
						if ((strtolower($request->getPost('receiver_email')) == strtolower(JO_Registry::get('paypal_email'))) && ((float)$request->getPost('mc_gross') == (float)$order_info['amount'])) {
							$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
							$paid = 'true';
						} else {
							JO_Log::write('PP_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($request->getPost('receiver_email')));
						}
						break;
					case 'Denied':
						$order_status_id = JO_Registry::get('pp_standard_denied_status_id');
						break;
					case 'Expired':
						$order_status_id = JO_Registry::get('pp_standard_expired_status_id');
						break;
					case 'Failed':
						$order_status_id = JO_Registry::get('pp_standard_failed_status_id');
						break;
					case 'Pending':
						$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
						break;
					case 'Processed':
						$order_status_id = JO_Registry::get('pp_standard_processed_status_id');
						break;
					case 'Refunded':
						$order_status_id = JO_Registry::get('pp_standard_refunded_status_id');
						break;
					case 'Reversed':
						$order_status_id = JO_Registry::get('pp_standard_reversed_status_id');
						break;
					case 'Voided':
						$order_status_id = JO_Registry::get('pp_standard_voided_status_id');
						break;
				}

				Model_Membership::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			} else {
				if($request->getPost('payment_status') == 'Completed') {
					$paid = 'true';
					$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
				} else {
					$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				}
				
				Model_Membership::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			}
			
			curl_close($ch);
		}
		
		$this->noViewRenderer(true);
		$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=success_deposit'));
	}

	public function callback_itemAction() {
		$request = $this->getRequest();
		$order_id = $request->getPost('custom');
		
		$order_info = Model_Orders::get($order_id);
		
		if($order_info) {
			$request1 = 'cmd=_notify-validate';
			foreach ($_POST as $key => $value) {
				$request1 .= '&' . $key . '=' . urlencode($value);
			}
			
			if(JO_Registry::forceGet('paypal_sandbox_mode')) {
				$ch = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
			} else {
				$ch = curl_init('https://www.paypal.com/cgi-bin/webscr');
			}
			
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($request1)));
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
			$response = curl_exec($ch);
			$paid = 'false';
			
			if((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0) && $request->issetPost('payment_status')) {
					
				$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				
				switch($request->getPost('payment_status')) {
					case 'Canceled_Reversal':
						$order_status_id = JO_Registry::get('pp_standard_canceled_reversal_status_id');
						break;
					case 'Completed':
						if ((strtolower($request->getPost('receiver_email')) == strtolower(JO_Registry::get('paypal_email'))) && ((float)$request->getPost('mc_gross') == (float)$order_info['price'])) {
							$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
							$paid = 'true';
						} else {
							JO_Log::write('PP_STANDARD :: RECEIVER EMAIL MISMATCH! ' . strtolower($request->getPost('receiver_email')));
						}
						break;
					case 'Denied':
						$order_status_id = JO_Registry::get('pp_standard_denied_status_id');
						break;
					case 'Expired':
						$order_status_id = JO_Registry::get('pp_standard_expired_status_id');
						break;
					case 'Failed':
						$order_status_id = JO_Registry::get('pp_standard_failed_status_id');
						break;
					case 'Pending':
						$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
						break;
					case 'Processed':
						$order_status_id = JO_Registry::get('pp_standard_processed_status_id');
						break;
					case 'Refunded':
						$order_status_id = JO_Registry::get('pp_standard_refunded_status_id');
						break;
					case 'Reversed':
						$order_status_id = JO_Registry::get('pp_standard_reversed_status_id');
						break;
					case 'Voided':
						$order_status_id = JO_Registry::get('pp_standard_voided_status_id');
						break;
				}

				Model_Orders::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			} else {
				if($request->getPost('payment_status') == 'Completed') {
					$paid = 'true';
					$order_status_id = JO_Registry::get('pp_standard_completed_status_id');
				} else {
					$order_status_id = JO_Registry::get('pp_standard_pending_status_id');
				}
				
				Model_Orders::update($order_id, array(
					'order_status_id' => $order_status_id,
					'paid' => $paid,
					'paid_from' => 'PayPal'
				));
			}
			
			curl_close($ch);
		}
		
		$this->noViewRenderer(true);
		$this->redirect(WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=items&action=success_payment'));
	}
	
	public function descriptionAction() {}
	
	public function itemFormAction() {
		
		$this->view->order_info = Model_Orders::get(JO_Session::get('order_id'));
		
		if(!$this->view->order_info) {
			return;
		}
		
		$this->view->currency = WM_Currency::getCurrency();
		
		if(JO_Registry::forceGet('paypal_sandbox_mode')) {
			$this->view->url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			$this->view->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		
		$this->view->paypal_email = JO_Registry::forceGet('paypal_email');
		
		$this->view->return = WM_Router::create($this->getRequest()->getBaseUrl() . '?module='. $this->view->order_info['module'] .'&controller=items&action=success_payment');
		$this->view->notify_url = WM_Router::create($this->getRequest()->getBaseUrl() . '?module='. $this->view->order_info['module'] .'&controller=payments_paypal&action=callback_item');
		$this->view->cancel_return = WM_Router::create($this->getRequest()->getBaseUrl() . '?module='. $this->view->order_info['module'] .'&controller=items&item_id='.$this->view->order_info['item_id'] .'&name='. WM_Router::clearName($this->view->order_info['name']));
		
		$this->view->email = JO_Session::get('email');
		$this->view->first_name = JO_Session::get('firstname');
		$this->view->last_name = JO_Session::get('lastname');
		
	}
	
	public function depositFormAction() {
		
		$this->view->order_info = Model_Deposit::getDeposit(JO_Session::get('deposit_id'));
		
		if(!$this->view->order_info) {
			return;
		}
		
		$this->view->currency = WM_Currency::getCurrency();
		
		if(JO_Registry::forceGet('paypal_sandbox_mode')) {
			$this->view->url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			$this->view->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		
		$this->view->paypal_email = JO_Registry::forceGet('paypal_email');
		
		$this->view->return = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=success_deposit');
		$this->view->notify_url = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=payments_paypal&action=callback_deposit');
		$this->view->cancel_return = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=deposit');
		
		$this->view->email = JO_Session::get('email');
		$this->view->first_name = JO_Session::get('firstname');
		$this->view->last_name = JO_Session::get('lastname');
		
	}
	
	public function membershipFormAction() {
		$this->view->order_info = Model_Membership::get(JO_Session::get('membership_id'));
		
		if(!$this->view->order_info) {
			return;
		}
		
		$this->view->currency = WM_Currency::getCurrency();
		
		if(JO_Registry::forceGet('paypal_sandbox_mode')) {
			$this->view->url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			$this->view->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		
		$this->view->paypal_email = JO_Registry::forceGet('paypal_email');
		
		$this->view->return = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=success_membership');
		$this->view->notify_url = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=payments_paypal&action=callback_membership');
		$this->view->cancel_return = WM_Router::create($this->getRequest()->getBaseUrl() . '?controller=users&action=membership');
		
		$this->view->email = JO_Session::get('email');
		$this->view->first_name = JO_Session::get('firstname');
		$this->view->last_name = JO_Session::get('lastname');
	}
	
	private function file_get_contents_curl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		
		$data = curl_exec($ch);
		
		curl_close($ch);
		
		return $data;
	}
	
}

?>
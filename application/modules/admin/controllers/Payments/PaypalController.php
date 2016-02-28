<?php

class Payments_PaypalController extends JO_Action {
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		if($request->isPost()) {
			Model_Settings::updateAll($request->getParams());
			$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/payments/');
		}
		
		$store_config = Model_Settings::getSettingsPairs(array(
			'filter_group' => 'paypal'
		));
		
		$confog = $request->getPost('paypal');
		
		if(isset($confog['paypal_sandbox_mode'])) {
    		$this->view->paypal_sandbox_mode = $confog['paypal_sandbox_mode'];
    	} elseif(isset($store_config['paypal_sandbox_mode'])) {
    		$this->view->paypal_sandbox_mode = $store_config['paypal_sandbox_mode'];
    	} else {
    		$this->view->paypal_sandbox_mode = 0;
    	}
		
		if(isset($confog['paypal_status'])) {
    		$this->view->paypal_status = $confog['paypal_status'];
    	} elseif(isset($store_config['paypal_status'])) {
    		$this->view->paypal_status = $store_config['paypal_status'];
    	} else {
    		$this->view->paypal_status = 0;
    	}
		
		if(isset($confog['paypal_email'])) {
    		$this->view->paypal_email = $confog['paypal_email'];
    	} elseif(isset($store_config['paypal_email'])) {
    		$this->view->paypal_email = $store_config['paypal_email'];
    	} else {
    		$this->view->paypal_email = '';
    	}
		
		if(isset($confog['paypal_sort_order'])) {
    		$this->view->paypal_sort_order = $confog['paypal_sort_order'];
    	} elseif(isset($store_config['paypal_sort_order'])) {
    		$this->view->paypal_sort_order = $store_config['paypal_sort_order'];
    	} else {
    		$this->view->paypal_sort_order = 0;
    	}
		
		if(isset($confog['paypal_pdt_token'])) {
    		$this->view->paypal_pdt_token = $confog['paypal_pdt_token'];
    	} elseif(isset($store_config['paypal_pdt_token'])) {
    		$this->view->paypal_pdt_token = $store_config['paypal_pdt_token'];
    	} else {
    		$this->view->paypal_pdt_token = '';
    	}
    	
    	/**************** STATUSES ****************/
		
		/*if(isset($confog['config_order_status_id'])) {
    		$this->view->config_order_status_id = $confog['config_order_status_id'];
    	} elseif(isset($store_config['config_order_status_id'])) {
    		$this->view->config_order_status_id = $store_config['config_order_status_id'];
    	} else {
    		$this->view->config_order_status_id = '';
    	}*/
		
		if(isset($confog['pp_standard_canceled_reversal_status_id'])) {
    		$this->view->pp_standard_canceled_reversal_status_id = $confog['pp_standard_canceled_reversal_status_id'];
    	} elseif(isset($store_config['pp_standard_canceled_reversal_status_id'])) {
    		$this->view->pp_standard_canceled_reversal_status_id = $store_config['pp_standard_canceled_reversal_status_id'];
    	} else {
    		$this->view->pp_standard_canceled_reversal_status_id = '';
    	}
		
		if(isset($confog['pp_standard_completed_status_id'])) {
    		$this->view->pp_standard_completed_status_id = $confog['pp_standard_completed_status_id'];
    	} elseif(isset($store_config['pp_standard_completed_status_id'])) {
    		$this->view->pp_standard_completed_status_id = $store_config['pp_standard_completed_status_id'];
    	} else {
    		$this->view->pp_standard_completed_status_id = '';
    	}
		
		if(isset($confog['pp_standard_denied_status_id'])) {
    		$this->view->pp_standard_denied_status_id = $confog['pp_standard_denied_status_id'];
    	} elseif(isset($store_config['pp_standard_denied_status_id'])) {
    		$this->view->pp_standard_denied_status_id = $store_config['pp_standard_denied_status_id'];
    	} else {
    		$this->view->pp_standard_denied_status_id = '';
    	}
		
		if(isset($confog['pp_standard_expired_status_id'])) {
    		$this->view->pp_standard_expired_status_id = $confog['pp_standard_expired_status_id'];
    	} elseif(isset($store_config['pp_standard_expired_status_id'])) {
    		$this->view->pp_standard_expired_status_id = $store_config['pp_standard_expired_status_id'];
    	} else {
    		$this->view->pp_standard_expired_status_id = '';
    	}
		
		if(isset($confog['pp_standard_failed_status_id'])) {
    		$this->view->pp_standard_failed_status_id = $confog['pp_standard_failed_status_id'];
    	} elseif(isset($store_config['pp_standard_failed_status_id'])) {
    		$this->view->pp_standard_failed_status_id = $store_config['pp_standard_failed_status_id'];
    	} else {
    		$this->view->pp_standard_failed_status_id = '';
    	}
		
		if(isset($confog['pp_standard_pending_status_id'])) {
    		$this->view->pp_standard_pending_status_id = $confog['pp_standard_pending_status_id'];
    	} elseif(isset($store_config['pp_standard_pending_status_id'])) {
    		$this->view->pp_standard_pending_status_id = $store_config['pp_standard_pending_status_id'];
    	} else {
    		$this->view->pp_standard_pending_status_id = '';
    	}
		
		if(isset($confog['pp_standard_processed_status_id'])) {
    		$this->view->pp_standard_processed_status_id = $confog['pp_standard_processed_status_id'];
    	} elseif(isset($store_config['pp_standard_processed_status_id'])) {
    		$this->view->pp_standard_processed_status_id = $store_config['pp_standard_processed_status_id'];
    	} else {
    		$this->view->pp_standard_processed_status_id = '';
    	}
		
		if(isset($confog['pp_standard_refunded_status_id'])) {
    		$this->view->pp_standard_refunded_status_id = $confog['pp_standard_refunded_status_id'];
    	} elseif(isset($store_config['pp_standard_refunded_status_id'])) {
    		$this->view->pp_standard_refunded_status_id = $store_config['pp_standard_refunded_status_id'];
    	} else {
    		$this->view->pp_standard_refunded_status_id = '';
    	}
		
		if(isset($confog['pp_standard_reversed_status_id'])) {
    		$this->view->pp_standard_reversed_status_id = $confog['pp_standard_reversed_status_id'];
    	} elseif(isset($store_config['pp_standard_reversed_status_id'])) {
    		$this->view->pp_standard_reversed_status_id = $store_config['pp_standard_reversed_status_id'];
    	} else {
    		$this->view->pp_standard_reversed_status_id = '';
    	}
		
		if(isset($confog['pp_standard_voided_status_id'])) {
    		$this->view->pp_standard_voided_status_id = $confog['pp_standard_voided_status_id'];
    	} elseif(isset($store_config['pp_standard_voided_status_id'])) {
    		$this->view->pp_standard_voided_status_id = $store_config['pp_standard_voided_status_id'];
    	} else {
    		$this->view->pp_standard_voided_status_id = '';
    	}
    	
    	$this->view->statuses = WM_Orderstatuses::orderStatuses();
    	
		/////// logo
		$image_model = new Helper_Images();
    	
    	if(isset($confog['paypal_logo']) && $confog['paypal_logo']) {
    		$this->view->paypal_logo = $confog['paypal_logo'];
    	} elseif(isset($store_config['paypal_logo']) && $store_config['paypal_logo']) {
    		$this->view->paypal_logo = $store_config['paypal_logo'];
    	} else {
    		$this->view->paypal_logo = '';
    	}
    	
    	if($this->view->paypal_logo) {
    		$this->view->preview_logo = $image_model->resize($this->view->paypal_logo, 100, 100);
    	} else {
    		$this->view->preview_logo = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	}
    	
    	if(!$this->view->preview_logo) {
    		$this->view->preview_logo = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	}
    	
    	if(!$this->view->preview_logo) {
    		$this->view->preview_logo = $image_model->resize('/no_image.png', 100, 100);
    	}
    	
    	$this->view->preview = $image_model->resize(JO_Registry::forceGet('no_image'), 100, 100);
    	
    	if(!$this->view->preview) {
    		$this->view->preview = $image_model->resize('/no_image.png', 100, 100);
    	}
		
	}
	
}

?>
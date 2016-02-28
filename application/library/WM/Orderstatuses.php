<?php

class WM_Orderstatuses extends JO_Model {

	public static function orderStatuses($key = null) {
		$data = array(
				1=>self::translate('Canceled Reversal'),
				2=>self::translate('Completed'),
				3=>self::translate('Denied'),
				4=>self::translate('Expired'),
				5=>self::translate('Failed'),
				6=>self::translate('Pending'),
				7=>self::translate('Processed'),
				8=>self::translate('Refunded'),
				9=>self::translate('Reversed'),
				10=>self::translate('Voided')
		);
		
		if($key !== null) {
			$data[0] = self::translate('Abandoned Orders');
			return isset($data[$key]) ? $data[$key] : false;
		}
		
		return $data;
	}

}

?>
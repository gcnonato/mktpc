<?php

class WM_Date {
	
	public static function format($date, $format = null) {
		if(!$format) {
			$format = JO_Translate::getInstance()->translate('dd.mm.yy');
		}
		return (string) new JO_Date($date, $format);
	}
	
	public static function getDaysListBetweenTwoDate($startDate, $endDate = 'now') {
	    $date = JO_Date::getInstance();
	    $date->setFormat('yy-mm-dd');
		$pastDateTS = $date->dateToUnix($startDate);
	    $currentDateArray = array();
	    for ($currentDateTS = $pastDateTS; $currentDateTS < strtotime($endDate); $currentDateTS += (86400)) {
			$currentDateArray[] = $date->setDate($currentDateTS)->toString();
	    }
	    return $currentDateArray;
	}

}

?>